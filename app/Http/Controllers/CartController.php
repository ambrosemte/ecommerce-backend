<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {

        try {
            $data = $this->cartService->getCartItems();
        } catch (\Exception $e) {
            return Response::error(500, 'Failed to fetch cart items');
        }
        
        return Response::success(message: 'Cart retrieved', data: $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "store_id" => "required|exists:stores,id",
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
            "quantity" => "required|numeric|min:1|max_digits:11",
            "delivery_detail_id" => "nullable|exists:delivery_details,id",
        ]);

        try {
            $this->cartService->addToCart($validated);
        } catch (\Exception $e) {
            return Response::error(400, $e->getMessage());
        }

        return Response::success(message: "Product added to cart");
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            "cart" => "array|required",
        ]);

        try {
            $this->cartService->updateCart($validated);
        } catch (\Exception $e) {
            return Response::error(500, $e->getMessage());
        }

        return Response::success(message: "Cart updated successfully");
    }

    public function delete(string $id)
    {
        try {
            $this->cartService->removeFromCart($id);
        } catch (\Exception $e) {
            return Response::error(500, $e->getMessage());
        }

        return Response::success(message: "Product removed from cart");
    }

}
