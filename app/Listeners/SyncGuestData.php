<?php

namespace App\Listeners;

use App\Events\Register;
use App\Services\CartService;
use App\Services\RecentlyViewedService;
use App\Services\UserService;
use App\Services\WishlistService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncGuestData
{
    protected CartService $cartService;
    protected WishlistService $wishlistService;
    protected RecentlyViewedService $recentlyViewedService;
    protected UserService $userService;


    public function __construct(
        CartService $cartService,
        WishlistService $wishlistService,
        RecentlyViewedService $recentlyViewedService,
        UserService $userService
    ) {
        $this->cartService = $cartService;
        $this->wishlistService = $wishlistService;
        $this->recentlyViewedService = $recentlyViewedService;
        $this->userService = $userService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login|Registered $event): void
    {
        $userId = $event->user->id ?? null;
        $guestId = request()->header('X-Guest-Id');

        if (!$guestId) {
            return;
        }

        if (!$userId) {
            return;
        }

        // Prevent duplicate syncs in the same session
        if (session()->pull('guest_synced', false)) {
            return;
        }

        // Sync each feature
        $this->cartService->syncFromGuest($userId, $guestId);
        $this->wishlistService->syncFromGuest($userId, $guestId);
        $this->recentlyViewedService->syncFromGuest($userId, $guestId);
        $this->userService->syncFromGuest($userId, $guestId);

        session(['guest_synced' => true]);
    }
}
