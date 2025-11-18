<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Services\RecentlyViewedService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function sellerProducts()
    {
        $products = Product::select('id', 'name', 'store_id', 'category_id')
            ->where('user_id', Auth::id())
            ->with(['productVariations.productMedia', 'store', 'category'])
            ->paginate(15);

        return Response::success(data: $products->toArray());
    }

    public function adminProducts()
    {
        $products = Product::select('id', 'name', 'store_id', 'category_id')
            ->with(['productVariations.productMedia', 'store', 'category'])
            ->paginate(15);

        return Response::success(data: $products->toArray());
    }

    public function show(string $id)
    {
        $product = Product::select(['id', 'store_id', 'category_id', 'name', 'description'])
            ->with([
                'productVariations.productMedia',
                'productVariations.productSpecifications.specificationKey',
                'productVariations.reviews' => function ($query) {
                    $query->where('approved', true)
                        ->latest()
                        ->limit(5)
                        ->with('user:id,name');
                },
                'store:id,name,image_url',
            ])
            ->find($id);

        if (!$product) {
            return Response::notFound(message: "Product not found");
        }

        app(RecentlyViewedService::class)->logView($id);

        return Response::success(message: "Product retrieved", data: $product->toArray());
    }

    public function store(Request $request)
    {
        $request->validate([
            "store_id" => "required|exists:stores,id",
            "category_id" => "required|exists:categories,id",
            "name" => "required|string|max:100",
            "description" => "required|string|max:255",
            "variations" => "required|array|min:1|max:10",
            "variations.*.quantity" => "required|numeric|min:1",
            "variations.*.price" => "required|numeric|min:0|max_digits:14",
            "variations.*.discount" => "nullable|numeric|min:1|max:100",
            "variations.*.images" => "required|array|min:1|max:10", // Media for the variation
            "variations.*.images.*" => "required|file|max:5120|mimes:png,jpg,jpeg,heic,heif,webp", // Image validation
            "variations.*.specifications" => "required|array", // Specifications array
            "variations.*.specifications.*.key_id" => "required|exists:specification_keys,id", // Specification key (e.g., "Color")
            "variations.*.specifications.*.value" => "required|string"
        ]);

        $user = User::find(Auth::id());
        $product = $user->stores()->find($request['store_id'])->products()->create([
            "name" => $request['name'],
            "description" => $request['description'],
            "user_id" => Auth::id(),
            "category_id" => $request['category_id']
        ]);

        foreach ($request['variations'] as $variation) {
            $imagePaths = [];
            $imageHashes = [];
            foreach ($variation['images'] as $image) {
                $hash = md5_file($image->getRealPath());

                // Check if this hash already exists in this variation
                if (in_array($hash, $imageHashes)) {
                    return Response::error(message: "Duplicate image detected in this variation");
                }

                $imageHashes[] = $hash;

                $imagePaths[] = $image->store("product-image", "public");
            }

            $productVariation = $product->productVariations()->create([
                "quantity" => $variation['quantity'],
                "price" => $variation['price'],
            ]);

            $productVariation->productMedia()->create([
                "media_type" => "image", // image or video
                "featured_media_url" => $imagePaths[0], // First image as featured
                "media_url" => json_encode($imagePaths),
                "product_id" => $product->id,
            ]);

            foreach ($variation['specifications'] as $specification) {
                $productVariation->productSpecifications()->create([
                    "product_id" => $product->id,
                    "specification_key_id" => $specification['key_id'], // e.g., "Color"
                    "specification_value" => $specification['value'], // e.g., "Blue"
                ]);
            }
        }

        return Response::success(message: "Product created");
    }

    public function featuredProducts()
    {
        $products = Product::select(['id', 'store_id', 'name', 'description'])
            ->with(['productVariations', 'productVariations.productMedia', 'wishlist'])
            ->paginate(10);

        return Response::success(message: "Featured products retrieved", data: $products->toArray());
    }

    public function searchProducts(Request $request)
    {
        $request->validate([
            'query' => 'required|string'
        ]);

        $products = Product::select(['id', 'store_id', 'name', 'description'])
            ->with(['productVariations', 'productVariations.productMedia', 'wishlist'])
            ->where('name', 'LIKE', '%' . $request['query'] . '%')
            ->paginate(15);

        return Response::success(message: "Searched products retrieved", data: $products->toArray());
    }

    public function searchSellerProducts(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'store_id' => 'required|string|exists:stores,id'
        ]);

        $user = Auth::user();

        $products = Product::select(['id', 'name'])
            ->where('user_id', $user->id)
            ->where('store_id', $request['store_id'])
            ->where('name', 'LIKE', '%' . $request['query'] . '%')
            ->get();


        return Response::success(message: "Searched seller products retrieved", data: $products->toArray());
    }


    public function delete(string $id)
    {
        $product = Product::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$product) {
            return Response::notFound(message: "Product not found");
        }

        $product->delete();

        return Response::success(message: "Product deleted successfully");
    }

    public function deleteVariation(string $productId, string $variationId)
    {
        $variation = ProductVariation::where('id', $variationId)
            ->where('product_id', $productId)
            ->whereHas('product', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();

        if (!$variation) {
            return Response::notFound(message: "Variation not found");
        }

        $totalVariations = ProductVariation::where('product_id', $productId)->count();

        if ($totalVariations <= 1) {
            return Response::error(message: "Cannot delete variation. Product must have at least one variation.");
        }

        $variation->delete();

        return Response::success(message: "Variation deleted");
    }

    public function update(Request $request, string $productId)
    {
        $request->validate([
            "name" => "required|string|max:100",
            "description" => "required|string|max:255",

            "variations" => "required|array|min:1|max:10",
            "variations.*.id" => "nullable|exists:product_variations,id",
            "variations.*.quantity" => "required|numeric|min:1",
            "variations.*.price" => "required|numeric|min:0|max_digits:14",
            "variations.*.discount" => "nullable|numeric|min:1|max:100",

            "variations.*.images" => "nullable|array|max:10",
            "variations.*.images.*" => "nullable|file|max:5120|mimes:png,jpg,jpeg,heic,heif,webp",

            "variations.*.existing_images" => "nullable|array",
            "variations.*.existing_images.*" => "nullable|string",

            "variations.*.specifications" => "required|array",
            "variations.*.specifications.*.key_id" => "required|exists:specification_keys,id",
            "variations.*.specifications.*.value" => "required|string"
        ]);

        $user = User::find(Auth::id());

        $query = Product::where('id', $productId);
        if ($user->hasRole(RoleEnum::SELLER)) {
            $query->where('user_id', $user->id);
        }

        $product = $query->first();
        if (!$product) {
            return Response::notFound(message: "Product not found");
        }

        $product->update([
            "name" => $request['name'],
            "description" => $request['description'],
        ]);

        $variationIdsToKeep = [];

        foreach ($request['variations'] as $variationData) {
            // Get or create variation
            $productVariation = !empty($variationData['id'])
                ? ProductVariation::where('id', $variationData['id'])->where('product_id', $product['id'])->first()
                : null;

            if ($productVariation) {
                $productVariation->update([
                    "quantity" => $variationData['quantity'],
                    "price" => $variationData['price'],
                    "discount" => $variationData['discount'] ?? null,
                ]);
            } else {
                $productVariation = $product->productVariations()->create([
                    "quantity" => $variationData['quantity'],
                    "price" => $variationData['price'],
                    "discount" => $variationData['discount'] ?? null,
                ]);
            }

            $variationIdsToKeep[] = $productVariation->id;

            // Handle images
            $finalImages = array_map(function ($url) {
                $path = parse_url($url, PHP_URL_PATH);
                return ltrim(str_replace('/storage/', '', $path), '/');
            }, $variationData['existing_images'] ?? []);

            if (!empty($variationData['images'])) {
                $hashes = [];
                foreach ($variationData['images'] as $image) {
                    $hash = md5_file($image->getRealPath());
                    if (in_array($hash, $hashes)) {
                        return Response::error(message: "Duplicate image detected in variation");
                    }
                    $hashes[] = $hash;

                    $path = $image->store("product-image", "public");
                    $finalImages[] = $path;
                }
            }

            $media = $productVariation->productMedia()->first();
            if ($media) {
                // Decode media_url robustly
                $raw = $media->getRawOriginal('media_url');
                $decoded = json_decode($raw, true);

                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $oldImages = is_array($decoded) ? $decoded : [];

                // delete removed images from storage
                foreach ($oldImages as $oldPath) {
                    if (!in_array($oldPath, $finalImages)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $media->update([
                    "featured_media_url" => $finalImages[0] ?? null,
                    "media_url" => json_encode($finalImages),
                ]);
            } else {
                $productVariation->productMedia()->create([
                    "media_type" => "image",
                    "featured_media_url" => $finalImages[0] ?? null,
                    "media_url" => json_encode($finalImages),
                    "product_id" => $product->id,
                ]);
            }

            // Handle specifications
            $productVariation->productSpecifications()->delete();
            foreach ($variationData['specifications'] as $spec) {
                $productVariation->productSpecifications()->create([
                    "product_id" => $product->id,
                    "specification_key_id" => $spec['key_id'],
                    "specification_value" => $spec['value'],
                ]);
            }
        }

        // Remove deleted variations
        $product->productVariations()->whereNotIn('id', $variationIdsToKeep)->delete();

        return Response::success(message: "Product updated successfully");
    }




}
