<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use App\Models\Notification as NotificationModel;

class FirebaseService
{
    protected $firestore;
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));

        // Messaging (FCM)
        $this->messaging = $factory->createMessaging();

    }

    /**
     * Send push notification via FCM and save it in DB
     */
    public function sendNotification($userId, $deviceToken, $title, $body)
    {
        // Save in MySQL
        NotificationModel::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body
        ]);

        // Keep only last 30 notifications
        $idsToDelete = NotificationModel::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(30)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            NotificationModel::whereIn('id', $idsToDelete)->delete();
        }

        // Send FCM
        $notification = FirebaseNotification::create($title, $body);
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        try {
            $this->messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
