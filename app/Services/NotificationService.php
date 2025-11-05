<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    public function sendNotification($userId, $deviceToken, $title, $body)
    {

        \App\Models\Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body
        ]);

        \App\Models\Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(30) // skip latest 30
            ->take(PHP_INT_MAX) // all older
            ->delete();

        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $messaging = $factory->createMessaging();

        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        try {
            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
