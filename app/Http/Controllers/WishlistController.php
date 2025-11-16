<?php

namespace App\Http\Controllers;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    protected $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index()
    {
        try {
            $data = $this->wishlistService->getWishlistItems();
        } catch (\Exception $e) {
            return Response::error(500, 'Failed to fetch wishlist items');
        }

        return Response::success(message: "Wishlist retrieved", data: $data);
    }


    public function show($id)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $wishlist = User::find($userId)->wishlists()->where('id', $id)->first();

            if (!$wishlist) {
                return Response::notFound(message: "Wishlist not found for this user");
            }

        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Wishlist->format($guestId);
            $ids = Cache::get($sessionKey, []);

            $wishlist = Wishlist::find($ids[0]);

        }

        return Response::success(message: "Wishlist retrieved", data: $wishlist->toArray());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
        ]);

        try {
            $this->wishlistService->addToWishlist($validated);
        } catch (\Exception $e) {
            return Response::error(400, $e->getMessage());
        }

        return Response::success(message: "Product added to wishlist");
    }

    public function delete($id)
    {
        try {
            $this->wishlistService->removeFromWishlist($id);
        } catch (\Exception $e) {
            return Response::error(400, $e->getMessage());
        }

        return Response::success(message: "Product removed from wishlist");
    }
}
