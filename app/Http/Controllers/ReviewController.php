<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    // Store or update a review
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(
                    [
                        'status' => 401,
                        'message' => 'Unauthenticated.'
                    ],
                    401
                );
            }

            $product = Product::findOrFail($validated['product_id']);

            // Update or create review
            $review = Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ],
                [
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                ]
            );

            DB::commit();

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Review submitted successfully.',
                    'review' => $review
                ],
                200
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 422,
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ],
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 500,
                    'message' => 'An error occurred while submitting review.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Show reviews for a product
    public function showByProduct(Request $request, $productID)
    {
        try {
            $product = Product::with(['reviews.user'])->find($productID);

            if (!$product) {
                return response()->json(
                    [
                        'status' => 404,
                        'message' => 'Product not found.'
                    ],
                    404
                );
            }

            if ($product->reviews->isEmpty()) {
                return response()->json(
                    [
                        'status' => 200,
                        'message' => 'This product has no reviews yet.',
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->title,
                            'average_rating' => 0,
                            'total_reviews' => 0,
                            'ratings' => [],
                        ],
                        'reviews' => [],

                    ],
                    200
                );
            }

            // average of rating
            $average = Review::where('product_id', $productID)->avg('rating');
            $average = round($average, 1); // e.g., 4.2

            // Query counts per rating
            $ratingsQuery = Review::where('product_id', $productID)
                ->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->get()
                ->keyBy('rating'); // key by rating for easy lookup

            // Build full 1–5 array
            $ratings = [];
            for ($i = 1; $i <= 5; $i++) {
                $ratings[] = [
                    'rating' => $i,
                    'count'  => $ratingsQuery[$i]->count ?? 0, // use 0 if missing
                ];
            }

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Review retrieved successfully.',
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->title,
                        'average_rating' => $average,
                        'total_reviews' => $product->reviews->count(),
                        'ratings' => $ratings,
                    ],
                    'reviews' => $product->reviews->map(function ($review) {
                        return [
                            'id' => $review->id,
                            // 'user' => $review->user->name,
                            'user' => [
                                'id' => $review->user->id,
                                'name' => $review->user->name,
                                'profile_url' => $review->user->profile_url,
                            ],
                            'rating' => $review->rating,
                            'comment' => $review->comment,
                            // 'created_at' => $review->created_at->toDateTimeString(),
                            'created_at' => $review->created_at,
                            'time_ago' => $review->created_at->diffForHumans(),
                        ];
                    }),
                ],
                200
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status' => 422,
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ],
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 500,
                    'message' => 'An error occurred while submitting review.',
                    'error'  => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Delete a review
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(
                    [
                        'status' => 401,
                        'message' => 'Unauthenticated.'
                    ],
                    401
                );
            }

            $review = Review::where('id', $id)->where('user_id', $user->id)->first();

            if (!$review) {
                return response()->json(
                    [
                        'status' => 404,
                        'message' => 'Review not found.'
                    ],
                    404
                );
            }

            $review->delete();

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Review deleted successfully.'
                ],
                200
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status' => 422,
                    'message' => 'Validation error.',
                    'errors'  => $e->errors(),
                ],
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 500,
                    'message' => 'An error occurred while submitting review.',
                    'error'   => $e->getMessage(),
                ],
                500
            );
        }
    }
}
