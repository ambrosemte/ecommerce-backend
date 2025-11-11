<?php

namespace App\Services;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\RecentlyViewed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecentlyViewedService
{
    /*
     *log users recently viewed items
     */
    public function logView($productId)
    {
        if (auth('sanctum')->check()) {
            $this->logForUser($productId);
        } else {
            $this->logForGuest($productId);
        }
    }

    /**
     * Log recently viewed product for authenticated users
     */
    private function logForUser($productId)
    {
        $userId = auth('sanctum')->id();

        RecentlyViewed::updateOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
            ['updated_at' => now()]
        );

        // Keep only 10 latest
        $idsToDelete = RecentlyViewed::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->skip(10)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            RecentlyViewed::whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Log recently viewed product for guest users
     */
    private function logForGuest($productId)
    {
        $guestId = request()->get('guest_id');

        $sessionKey = SessionKey::RecentlyViewed->format($guestId);

        $recent = Cache::get($sessionKey, []);
        $recent = array_values(array_diff($recent, [$productId]));
        array_unshift($recent, $productId);
        $recent = array_slice($recent, 0, 10);

        Cache::put($sessionKey, $recent, now()->addDays(30));
    }

    /*
     * Sync guest recently viewed items to the logged-in user account
     */
    public function syncFromGuest(array $guestIds)
    {
        // if (!Auth::check() || empty($guestIds)) {
        //     return false;
        // }

        // foreach ($guestIds as $productId) {
        //     RecentlyViewed::updateOrCreate(
        //         ['user_id' => Auth::id(), 'product_id' => $productId],
        //         ['updated_at' => now()]
        //     );
        // }

        // // Trim to last 10
        // RecentlyViewed::where('user_id', Auth::id())
        //     ->orderByDesc('updated_at')
        //     ->skip(10)
        //     ->take(PHP_INT_MAX)
        //     ->delete();

        // return true;
    }
}
