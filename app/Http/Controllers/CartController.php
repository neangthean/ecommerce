<?php

// app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * Add a product to the authenticated user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        // Start a database transaction for atomicity
        DB::beginTransaction();

        try {
            // 1. Validate the incoming request data
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'color_id' => 'nullable|exists:colors,id',
                'quantity' => 'required|integer|min:1',
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

            // 2. Find the product to get its current price
            $product = Product::findOrFail($validated['product_id']);
            // $color = Color::findOrFail($validated['color_id']);
            $color = null;
            if (!empty($validated['color_id'])) {
                $color = Color::findOrFail($validated['color_id']);
            }

            // 3. Find an existing cart entry for this user and product
            $cartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            $priceDiscount = round($product->price - (($product->price * $product->discount) / 100), 2);

            if ($cartItem) {
                // If the item exists, update its quantity
                $cartItem->quantity += $validated['quantity'];
                $cartItem->save();
            } else {
                // If not, create a new cart entry
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'color_id' => $color->id,
                    'quantity' => $validated['quantity'],
                    // 'price' => $product->price, // Store the current product price
                    'price' => $priceDiscount,
                ]);
            }

            // Commit the transaction
            DB::commit();

            // 4. Return the updated cart for this user
            // We use 'product' to eager load the product details for each cart entry
            $cart = Cart::with('product')->where('user_id', $user->id)->get();

            return response()->json([
                'status' => 200,
                'message' => 'Product added to cart successfully!',
                'cart' => $cart,
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while adding to the cart.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all cart entries for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCart(Request $request)
    {
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

        // Get all cart entries for the user and eager load the product details
        $cart = Cart::with('product')->where('user_id', $user->id)->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => 'Cart not found or is empty.',
                'cart' => [],
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Cart retrieved successfully.',
            'cart' => $cart,
        ], 200);
    }
}
