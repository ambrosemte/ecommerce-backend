<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = Auth::user()
            ->wishlists()
            ->with([
                "product.productVariations.productMedia",
            ])
            ->latest()
            ->get()
            ->toArray();

        return Response::success(message: "Wishlist retrieved", data: $wishlist);
    }

    public function show($id)
    {
        $wishlist = Auth::user()->wishlists()->where('id', $id)->first();

        if (!$wishlist) {
            return Response::notFound(message: "Wishlist not found for this user");
        }

        return Response::success(message: "Wishlist retrieved", data: $wishlist->toArray());
    }

    public function store(Request $request)
    {
        $wishlistRequestData = $request->validate([
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
        ]);

        Auth::user()->wishlists()->create($wishlistRequestData);
        return Response::success(message: "Product added to wishlist");
    }

    public function delete($id)
    {
        $wishlist = Auth::user()->wishlists()->where('id', $id)->first();

        if (!$wishlist) {
            return Response::notFound(message: "Wishlist not found for this user");
        }

        $wishlist->delete();

        return Response::success(message: "Product removed from wishlist");
    }
}
