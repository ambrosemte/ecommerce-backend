<?php

namespace App\Services;

use App\Enums\SessionKey;
use App\Models\Product;
use App\Models\RecentlyViewed;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RecentlyViewedService
{
    public function getRecentlyViewedItems()
    {
        if (auth('sanctum')->check()) {

            $user = User::find(auth('sanctum')->id());

            $recentItems = $user->recentlyViewed()
                ->orderBy('updated_at')
                ->get(['id', 'product_id']);
        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::RecentlyViewed->format($guestId);
            $recentItems = Cache::get($sessionKey, []);

            $recentItems = collect($recentItems); // make sure it's a Collection
        }

        // Extract all product IDs
        $productIds = $recentItems->pluck('product_id')->toArray();

        // Fetch products along with variations and media
        $products = Product::with('productVariations.productMedia')
            ->whereIn('id', $productIds)
            ->get();

        // Map cart items to include product and recently viewed ID
        $recent = $recentItems->map(function ($recentItem) use ($products) {
            $product = $products->firstWhere('id', $recentItem['product_id'] ?? $recentItem->product_id);

            return [
                'id' => $recentItem['id'] ?? $recentItem->id ?? null,
                'product' => $product,
            ];
        });

        return $recent->toArray();
    }

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
        $guestId = request()->input('guest_id');
        $sessionKey = SessionKey::RecentlyViewed->format($guestId);
        $recent = Cache::get($sessionKey, []);

        $recent = array_filter($recent, function ($item) use ($productId) {
            return $item['product_id'] != $productId;
        });

        array_unshift($recent, [
            'id' => (string) Str::uuid(),
            'product_id' => $productId,
        ]);

        $recent = array_slice($recent, 0, 10);

        $recent = array_values($recent);

        Cache::put($sessionKey, $recent, now()->addDays(30));
    }

    /*
     * Sync guest recently viewed items to the logged-in user account
     */
    public function syncFromGuest(string $userId, string $guestId)
    {
        $sessionKey = SessionKey::RecentlyViewed->format($guestId);

        $recentItems = Cache::get($sessionKey, []);

        if (!empty($recentItems)) {
            foreach ($recentItems as $item) {
                RecentlyViewed::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'updated_at' => now(),
                    ]
                );
            }

            Cache::forget($sessionKey);
        }
    }
}
