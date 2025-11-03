<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getOrCreateConversation()
    {
        $userOne = Auth::id();

        $conversation = Conversation::where('user_one_id', $userOne)
            ->where('is_completed', false)
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $userOne,
            ]);
        }

        return Response::success(message: "Conversation retrieved", data: [
            'id' => $conversation->id,
            'is_completed' => $conversation->is_completed
        ]);
    }

    public function getMessages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Optional: authorize that the user is part of the conversation
        $messages = $conversation->messages()->with('sender')->orderBy('created_at', 'asc')->get()->toArray();

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

        Message::create([
            'conversation_id' => $request['conversation_id'],
            'sender_id' => Auth::id(),
            'message' => $request['message'],
        ]);

        // Optionally broadcast here

        return Response::success(message: "Message send");
    }

    public function joinConversation($conversationId)
    {
        $userTwo = Auth::id();

        $conversation = Conversation::where('id', $conversationId)
            ->where('is_completed', false)
            ->where('user_two_id', '')
            ->first();

        if (!$conversation) {
            return Response::error(message: "Conversation not found or already completed");
        }

        $conversation->user_two_id = $userTwo;
        $conversation->save();

        return Response::success(message: "Conversation joined", data: [
            'name' => Auth::user()->name,
        ]);
    }

}
