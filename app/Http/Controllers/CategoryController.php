<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Contracts\Role;

class CategoryController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        $query = Category::query();

        if (auth('sanctum')->check()) {
            $user = User::find(auth('sanctum')->id());

            if ($user->hasRole([RoleEnum::ADMIN, RoleEnum::AGENT, RoleEnum::SELLER])) {
                // no filter
            } else {
                $query->where('is_active', true);
            }
        } else {
            $query->where('is_active', true);
        }

        $categories = $query->get()->toArray();

        return Response::success(message: "Categories retrieved", data: $categories);
    }

    public function getProductByCategory(Request $request, string $id)
    {
        $validated = $request->validate([
            'sort' => "nullable|string"
        ]);

        $category = Category::find($id);
        if (!$category) {
            return Response::notFound(message: "Category not found");
        }

        $query = Product::where('category_id', $category->id)
            ->with(['productVariations.productMedia']);

        switch ($validated['sort'] ?? '') {
            case 'price_asc':
                $query->withMin('productVariations', 'price')
                    ->orderBy('product_variations_min_price', 'asc');
                break;

            case 'price_desc':
                $query->withMax('productVariations', 'price')
                    ->orderBy('product_variations_max_price', 'desc');
                break;
        }

        $products = $query->get();

        $currency = '';
        $wishlistProductIds = [];

        if (auth('sanctum')->check()) {
            $user = User::find(auth('sanctum')->id());
            $wishlistProductIds = $user->wishlists()->pluck('product_id')->toArray();
            $currency = $user->preferred_currency;
        }

        // Loop through each product and update variations + wishlist flag
        foreach ($products as $product) {
            // Convert each variation price
            foreach ($product->productVariations as $variation) {
                $conversion = $this->currencyService->convert($variation->price, $currency);
                $variation->converted_price = $conversion['amount'];
                $variation->currency = $conversion['currency'];
            }

            // Add wishlist field
            $product->wished_list = in_array($product->id, $wishlistProductIds);
        }

        return Response::success(message: "Products by category retrieved", data: $products->toArray());
    }

    /**
     * Get category details by ID (Seller & Agent)
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = Category::with([
            'specificationKeys',
            'specificationKeys.specificationValues'
        ])->find($id);

        if (!$category) {
            return Response::notFound(message: "Category not found", );
        }

        return Response::success(message: "Category retrieved", data: $category->toArray());
    }

    /**
     * Create a new category (Seller & Agent)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:100",
            "description" => "nullable|string|max:255",
            "image" => "required|max:5120|mimes:png,jpg,jpeg"
        ]);

        $imagePath = $request->file("image")->store("category-image", "public");

        Category::create([
            "name" => $request['name'],
            "description" => $request['description'],
            "image_url" => $imagePath,
        ]);

        return Response::success(message: "Category created");
    }

    /**
     * Update a category (Seller & Agent)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            "name" => "required|string|max:100",
            "description" => "nullable|string|max:255",
            "image" => "nullable|max:5120|mimes:png,jpg,jpeg",
            "is_active" => "required|boolean"
        ]);

        $category = Category::find($id);

        if (!$category) {
            return Response::notFound(message: "Category not found");
        }

        $imagePath = $category->getRawOriginal('image_url'); //keep current raw url

        // If a new image is uploaded, replace and delete the old one
        if ($request->hasFile('image')) {
            if ($category->image_url && Storage::disk('public')->exists($category->image_url)) {
                Storage::disk('public')->delete($category->image_url);
            }

            $imagePath = $request->file('image')->store('category-image', 'public');
        }

        $category->update([
            "name" => $request['name'],
            "description" => $request['description'],
            "image_url" => $imagePath,
            "is_active" => $request['is_active']
        ]);

        return Response::success(message: "Category updated");
    }

    /**
     * Delete a category (Seller & Agent)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return Response::notFound(message: "Category not found");
        }

        if ($category->image_url && Storage::disk('public')->exists($category->image_url)) {
            Storage::disk('public')->delete($category->image_url);
        }

        $category->delete();

        return Response::success(message: "Category deleted");
    }

}
