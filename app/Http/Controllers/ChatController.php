<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AblyService;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $firebaseService;
    protected $ablyService;


    public function __construct(FirebaseService $firebaseService, AblyService $ablyService)
    {
        $this->firebaseService = $firebaseService;
        $this->ablyService = $ablyService;
    }

    public function createConversation()
    {
        $customerId = Auth::id();

        $conversation = Conversation::firstOrCreate([
            'user_one_id' => $customerId,
            'is_completed' => false,
        ]);

        return Response::success(message: "Conversation started", data: [
            'id' => $conversation->id,
            'is_completed' => $conversation->is_completed
        ]);
    }

    public function getConversationCustomer()
    {
        $customerId = Auth::id();

        $conversation = Conversation::where('user_one_id', $customerId)
            ->where('is_completed', false)
            ->orderBy('created_at', 'desc')
            ->first();

        return Response::success(
            message: "Conversation retrieved",
            data: $conversation ? $conversation->toArray() : []
        );
    }

    public function getConversationAgent()
    {
        $user = User::find(Auth::id());

        $query = Conversation::with([
            'customer:id,name,email',
            'agents:id,name'
        ])->orderBy('created_at', 'desc');

        // Role-based filtering
        if ($user->hasRole(RoleEnum::AGENT)) {
            // Agents only see incomplete conversations
            $query->where('is_completed', false);
        }
        // Admins see all conversations, including completed

        $conversations = $query->get();

        return Response::success(
            message: "Conversations retrieved",
            data: $conversations->toArray()
        );
    }


    public function getMessages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Optional: authorize that the user is part of the conversation
        $messages = $conversation->messages()
            ->with('sender:id,name,email,image_url')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();

        return Response::success(message: "Messages retrieved", data: $messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required_without:media|string',
            'media' => 'nullable|file',
            'type' => 'required_with:media|string|in:image,video,file',
        ]);

        $senderId = Auth::id();
        $conversationId = $request['conversation_id'];

        $conversation = Conversation::find($conversationId);

        $message = Message::create([
            'conversation_id' => $request['conversation_id'],
            'sender_id' => Auth::id(),
            'message' => $request['message'],
            'is_user_message' => true
        ]);

        $isAgentSending = $conversation->user_one_id !== $senderId;

        if ($isAgentSending) {
            // $firebaseToken = User::find($conversation->user_one_id)->firebase_token;
            // if ($firebaseToken) {
            //     $this->firebaseService->sendNotification(
            //         $conversation->user_one_id,
            //         $firebaseToken,
            //         'New Message',
            //         $request['message'] ?? ($request->file('media') ? 'Sent a file' : '')
            //     );
            // }
        }

        $message = $message->load('sender');

        $this->ablyService->publish(
            "conversation.$conversationId",
            'new-message',
            $message->toArray()
        );

        return Response::success(message: "Message sent");
    }

    public function joinConversation($conversationId)
    {
        $agentId = Auth::id();

        $conversation = Conversation::where('id', $conversationId)
            ->where('is_completed', false)
            ->first();

        if (!$conversation) {
            return Response::error(message: "Conversation not found or already completed");
        }

        if ($conversation->agents()->where('user_id', $agentId)->exists()) {
            return Response::error(message: "You are already part of this conversation");
        }

        $conversation->agents()->attach($agentId);

        $message = $conversation->messages()->create([
            'sender_id' => $agentId,
            'message' => Auth::user()->name . " joined the chat",
            'is_user_message' => false, //system type message
        ]);

        $message = $message->load('sender');

        $this->ablyService->publish(
            "conversation.$conversationId",
            'agent-joined',
            $message->toArray()
        );

        return Response::success(message: "Conversation joined", data: [
            'name' => Auth::user()->name,
        ]);
    }

    public function transferConversation(Request $request, $conversationId)
    {
        $request->validate([
            "new_agent_id" => "required|string|exists:users,id"
        ]);

        $currentAgentId = Auth::id();
        $newAgentId = $request['new_agent_id'];

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return Response::notFound(message: "Conversation not found");
        }

        if (!$conversation->agents()->where('user_id', $currentAgentId)->exists()) {
            return Response::error(message: "You are not part of this conversation");
        }

        $conversation->agents()->updateExistingPivot($currentAgentId, [
            'left_at' => now(),
        ]);

        //current agent left broadcasr
        $message = $conversation->messages()->create([
            'sender_id' => $currentAgentId,
            'message' => User::find($currentAgentId)->name . " left the chat",
            'is_user_message' => false, //system type message
        ]);

        $message = $message->load('sender');

        $this->ablyService->publish(
            "conversation.$conversationId",
            'agent-left',
            $message->toArray()
        );

        if ($newAgentId && !$conversation->agents()->where('user_id', $newAgentId)->exists()) {
            $conversation->agents()->attach($newAgentId);
        }

        //new agent joined broadcast
        $message = $conversation->messages()->create([
            'sender_id' => $newAgentId,
            'message' => User::find($newAgentId)->name . " joined the chat",
            'is_user_message' => false, //system type message
        ]);

        $message = $message->load('sender');

        $this->ablyService->publish(
            "conversation.$conversationId",
            'agent-joined',
            $message->toArray()
        );

        return Response::success(message: "Conversation transferred successfully");
    }


    public function closeConversation($conversationId)
    {
        $agentId = Auth::id();

        $conversation = Conversation::where('id', $conversationId)
            ->first();

        if (!$conversation) {
            return Response::error(message: "Conversation not found");
        }

        if ($conversation->agents()->where('user_id', $agentId)->exists()) {

            $conversation->update(['is_completed' => true]);

            $conversation->agents()->updateExistingPivot($agentId, [
                'left_at' => now(),
            ]);

            $message = $conversation->messages()->create([
                'sender_id' => $agentId,
                'message' => Auth::user()->name . " left the chat",
                'is_user_message' => false, //system type message
            ]);

            $message = $message->load('sender');

            $this->ablyService->publish(
                "conversation.$conversationId",
                'agent-left',
                $message->toArray()
            );

            return Response::success(message: "You have closed the conversation");
        }

        return Response::error(message: "You are not part of this conversation");
    }


}
