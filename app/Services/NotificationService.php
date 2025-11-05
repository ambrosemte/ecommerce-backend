<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use App\Models\Notification as NotificationModel;
class NotificationService
{
    public function sendNotification($userId, $deviceToken, $title, $body)
    {

        NotificationModel::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body
        ]);

        $idsToDelete = NotificationModel::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(30)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            NotificationModel::whereIn('id', $idsToDelete)->delete();
        }

        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $messaging = $factory->createMessaging();

        $notification = FirebaseNotification::create($title, $body);

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
