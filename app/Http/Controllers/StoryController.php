<?php
namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Story;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoryView;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov|max:10240',
            'caption' => 'nullable|string|max:1000',
            'product_id' => 'nullable|string|exists:products,id',
            'duration_hours' => 'nullable|integer|min:1|max:168'
        ]);

        $store = Store::find($request['store_id']);

        if ($request->filled('product_id')) {
            $product = $store->products()->where('id', $request['product_id'])->first();
            if (!$product) {
                return Response::success(
                    message: "The selected product is not valid for this store. Please select a product that belongs to this store.",
                );
            }
        }

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $type = in_array(strtolower($ext), ['mp4', 'mov']) ? 'video' : 'image';
        $mediaPath = $file->store("stories/{$store->id}", "public");

        $expiresAt = now()->addHours($request['duration_hours'] ?? 24);

        Story::create([
            'store_id' => $store->id,
            'product_id' => $product->id ?? null,
            'media_url' => $mediaPath,
            'type' => $type,
            'caption' => $request['caption'],
            'expires_at' => $expiresAt,
        ]);

        return Response::success(message: "Story uploaded");
    }

    public function feed(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $storeIds = $user->followedStores()->pluck('stores.id');

            $stories = Story::with(['store', 'product'])
                ->whereIn('store_id', $storeIds)
                ->active()
                ->latest()
                ->get();
        } else {
            // For guests, optionally show recent stories or popular stores
            $stories = Story::with('store')
                ->active()
                ->latest()
                ->limit(50)
                ->get();
        }

        // Attach view counts
        $stories->transform(function ($story) use ($request) {
            $story->views_count = $story->views()->count();

            return $story;
        });

        return Response::success(message: "Stories retrieved", data: $stories->toArray());
    }

    public function showStoreStories($storeId)
    {
        $stories = Story::with(['product'])
            ->where('store_id', $storeId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($story) {
                $story->views_count = $story->views()->count();
                return $story;
            });

        return Response::success(message: "", data: $stories->toArray());
    }

    public function logView(Request $request, $storyId)
    {
        $story = Story::findOrFail($storyId);

        $user = $request->user();
        $guestId = $request->input('guest_id');

        $exists = StoryView::where('story_id', $story->id)
            ->when($user, fn($q) => $q->where('user_id', $user->id), fn($q) => $q->where('guest_id', $guestId))
            ->exists();

        if (!$exists) {
            StoryView::create([
                'story_id' => $story->id,
                'user_id' => $user ? $user->id : null,
                'guest_id' => $user ? null : $guestId,
                'viewed_at' => now(),
            ]);
        }

        return Response::success(message: "view recorded");
    }

    public function adminStories()
    {
        $user = request()->user();

        if ($user->hasRole([RoleEnum::SELLER])) {

            $storeIds = $user->stores()->pluck('id');

            $stories = Story::select(['id', 'store_id', 'product_id', 'caption', 'expires_at', 'media_url', 'type'])
                ->with(['store', 'product'])
                ->whereIn('store_id', $storeIds)
                ->latest()
                ->paginate(15);
        } else {
            $stories = Story::select(['id', 'store_id', 'product_id', 'caption', 'expires_at', 'media_url', 'type'])
                ->with(['store', 'product'])
                ->latest()
                ->paginate(15);
        }


        return Response::success(data: $stories->toArray());
    }

}
