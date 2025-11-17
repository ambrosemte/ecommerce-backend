<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    /**
     * Get all stores for the authenticated user (Seller & Agent)
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $stores = Store::where('user_id', Auth::id())
            ->with(['products.productVariations.productMedia'])
            ->withCount('followers')
            ->get(); // Get all stores instead of just one

        if ($stores->isEmpty()) {
            return Response::notFound(message: "No stores found for this user");
        }

        return Response::success(message: "Stores retireved", data: $stores->toArray());
    }

    /**
     * Get store details by ID
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $store = Store::with(['products.productVariations.productMedia'])
            ->withCount('followers')
            ->find($id);

        if (!$store) {
            return Response::notFound(message: "Store not found");
        }

        $user = auth('sanctum')->user();

        $store->is_following = $user ? $store->isFollowing($user) : false;

        return Response::success(message: "Store retireved", data: $store->toArray());
    }

    /**
     * Create a new store (Seller & Agent)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:100",
            "image" => "required|max:5120|mimes:png,jpg,jpeg"
        ]);
        $user = User::find(Auth::id());

        $imagePath = $request->file("image")->store("store-image", "public");

        $user->stores()->create([
            "name" => $request['name'],
            "image_url" => $imagePath,
        ]);

        return Response::success(message: "Store created");
    }

    /**
     * Follow a store
     * @param string $storeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function follow(string $storeId)
    {
        $store = Store::find($storeId);

        if (!$store) {
            return Response::notFound(message: "Store not found");
        }
        try {
            $store->follow(Auth::user());
        } catch (\Exception) {
            return Response::error(message: "Already following store");
        }

        return Response::success(message: "Store followed");
    }

    /**
     * unfollow a store
     * @param string $storeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfollow(string $storeId)
    {
        $store = Store::find($storeId);

        if (!$store) {
            return Response::notFound(message: "Store not found");
        }
        try {
            $store->unfollow(Auth::user());
        } catch (\Exception) {
            return Response::error(message: "Already unfollowed store");
        }

        return Response::success(message: "Store unfollowed");
    }

    /**
     * Delete a store (Seller & Agent)
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $id)
    {
        $store = Store::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$store) {
            return Response::notFound(message: "Store not found.");
        }

        $store->delete();

        return Response::success(message: "Store deleted successfully.");
    }

    /**
     * Get all stores with products and followers count (Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStores()
    {
        $stores = Store::select()
            ->with(['products.productVariations.productMedia'])
            ->withCount('followers')
            ->latest()
            ->paginate(15)
            ->toArray();

        return Response::success(200, 'Stores retrieved', $stores);
    }
}
