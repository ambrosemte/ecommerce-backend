<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        $categories = Category::all()->toArray();

        return Response::success(message: "Categories retrieved", data: $categories);
    }

    public function getProductByCategory(Request $request, string $categoryId)
    {
        $validated = $request->validate([
            'sort' => "nullable|string"
        ]);

        $category = Category::find($categoryId);
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

}
