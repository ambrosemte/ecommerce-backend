<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Helpers\Response;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => "required|exists:products,id",
            'product_variation_id' => "required|exists:product_variations,id",
            'message' => 'nullable|string|max:255',
            'rating' => [
                'required',
                'numeric',
                'in:1.0,1.5,2.0,2.5,3.0,3.5,4.0,4.5,5.0',
            ],
        ]);

        try {
            $userId = Auth::id();

            $hasOrdered = Order::where('user_id', $userId)
                ->where('product_id', $request->product_id)
                ->where('product_variation_id', $request->product_variation_id)
                ->whereHas('latestStatus', function ($query) {
                    $query->where('status', OrderStatusEnum::DELIVERED);
                })
                ->exists();

            if (!$hasOrdered) {
                return Response::error(message: "You can only review products you have purchased");
            }


            $alreadyReviewed = Review::where('user_id', $userId)
                ->where('product_id', $request['product_id'])
                ->where('product_variation_id', $request['product_variation_id'])
                ->exists();

            if ($alreadyReviewed) {
                return Response::error(message: "You have already reviewed this product variation");
            }

            Review::create([
                'user_id' => $userId,
                'product_id' => $request['product_id'],
                'product_variation_id' => $request['product_variation_id'],
                'title' => null,
                'message' => $request['message'],
                'rating' => $request['rating'],
                'approved' => false
            ]);
        } catch (Exception $e) {
            Log::error("Create Review Error: " . $e->getMessage());
            return Response::error(statusCode: 500, message: "An error occured");
        }

        return Response::success(message: "Review submitted successfully");
    }

    //ADMIN, AGENT
    public function accept(string $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return Response::notFound(message: "Review not found");
        }

        $review->update(['approve' => true]);

        return Response::success(message: "Review approved");
    }

    //ADMIN, AGENT
    public function decline(string $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return Response::notFound(message: "Review not found");
        }

        $review->update(['approve' => false]);

        return Response::success(message: "Review declined");
    }
}
