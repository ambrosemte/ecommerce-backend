<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\User;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function show(string $id)
    {
        $product = Product::select(['id', 'store_id', 'name', 'description'])
            ->with([
                'productVariations.productMedia',
                'productVariations.productSpecifications.specificationKey',
                'store:id,name,image_url',
            ])
            ->find($id);

        if (!$product) {
            return Response::notFound(message: "Product not found");
        }

        $currency = '';
        // Check if the product is in the user's wishlist
        if (auth('sanctum')->check()) {
            $user = User::find(auth('sanctum')->id());
            $isWished = $user->wishlists()->where('product_id', $product->id)->exists();
            $currency = $user->preferred_currency;
        }

        // Convert each variation price
        $product->productVariations->transform(function ($variation) use ($currency) {
            $conversion = $this->currencyService->convert($variation->price, $currency);

            $variation->converted_price = $conversion['amount'];
            $variation->currency = $conversion['currency'];


            $variation->discounted_price = ($variation->discount != null)
                ? round($variation->price - (($variation->discount * $variation->price) / 100), 2)
                : $variation->price;

            $conversion = $this->currencyService->convert($variation->discounted_price, $currency);

            $variation->converted_discounted_price = $conversion['amount'];

            return $variation;
        });

        $product->wished_list = $isWished ?? false;

        return Response::success(message: "Product retrieved", data: $product->toArray());
    }

    public function store(Request $request)
    {
        $request->validate([
            "store_id" => "required|exists:stores,id",
            "category_id" => "required|exists:categories,id",
            "name" => "required|string|max:100",
            "description" => "required|string|max:255",
            "variations" => "required|array|min:1",
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
            foreach ($variation['images'] as $image) {
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

        $products->getCollection()->transform(function ($product) {
            $currency = '';
            // Check if the product is in the user's wishlist
            if (auth('sanctum')->check()) {
                $user = User::find(auth('sanctum')->id());
                $isWished = $user->wishlists()->where('product_id', $product->id)->exists();
                $currency = $user->preferred_currency;
            }

            // Convert each variation price
            $product->productVariations->transform(function ($variation) use ($currency) {
                $conversion = $this->currencyService->convert($variation->price, $currency);

                $variation->converted_price = $conversion['amount'];
                $variation->currency = $conversion['currency'];

                return $variation;
            });

            // Add wishedlist field to the product object
            $product->wished_list = $isWished ?? false;

            return $product;
        });

        return Response::success(message: "Featured products retrieved", data: $products->toArray());
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string'
        ]);

        $products = Product::select(['id', 'store_id', 'name', 'description'])
            ->with(['productVariations', 'productVariations.productMedia', 'wishlist'])
            ->where('name', 'LIKE', '%' . $request['query'] . '%')
            ->paginate(30);

        $products->getCollection()->transform(function ($product) {
            $currency = '';
            // Check if the product is in the user's wishlist
            if (auth('sanctum')->check()) {
                $user = User::find(auth('sanctum')->id());
                $isWished = $user->wishlists()->where('product_id', $product->id)->exists();
                $currency = $user->preferred_currency;
            }

            // Convert each variation price
            $product->productVariations->transform(function ($variation) use ($currency) {
                $conversion = $this->currencyService->convert($variation->price, $currency);

                $variation->converted_price = $conversion['amount'];
                $variation->currency = $conversion['currency'];

                return $variation;
            });

            // Add wishedlist field to the product object
            $product->wished_list = $isWished ?? false;

            return $product;
        });

        return Response::success(message: "Searched products retrieved", data: $products->toArray());
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
}
