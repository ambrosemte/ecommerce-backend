<?php

namespace App\Http\Controllers;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\RecentlyViewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecentlyViewedController extends Controller
{
    public function index()
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $recent = RecentlyViewed::where('user_id', $userId)
                ->latest()
                ->limit(10)
                ->pluck('product_id');

            $products = Product::with('productVariations.productMedia')
                ->whereIn('id', $recent)
                ->get();

            $recent = collect($recent)
                ->map(fn($id) => $products->firstWhere('id', $id))
                ->filter()
                ->values()
                ->toArray();
        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::RecentlyViewed->format($guestId);
            $ids = Cache::get($sessionKey, []);

            $products = Product::with('productVariations.productMedia')
                ->whereIn('id', $ids)
                ->get();

            // Sort products to match the order of IDs in session
            $recent = collect($ids)
                ->map(fn($id) => $products->firstWhere('id', $id))
                ->filter()
                ->toArray();
        }

        return Response::success(message: "Recently viewed retrieved", data: $recent);
    }

}
