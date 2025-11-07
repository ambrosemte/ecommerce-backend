<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function sendNotification(Request $request)
    {

        $user = $request->user();
        $userId = $user->id;
        $token = $user->firebase_token;

        $isSent = app(FirebaseService::class)->sendNotification(
            $userId,
            $token,
            'Hello!',
            'This is a test notification'
        );


        if (!$isSent) {
            return Response::error();
        }

        return Response::success(message: "Notification sent successfully");
    }

    public function getUserNotifications(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get()->toArray();

        return Response::success(message: "Notifications retrieved", data: $notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return Response::notFound(message: "Notification not found");
        }

        $notification->update(['is_read' => true]);

        return Response::success(message: "Notification marked as read");
    }
}
