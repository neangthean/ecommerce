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

    // Add a product to the authenticated user's cart.
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

            // Check color validity
            if (!empty($validated['color_id'])) {
                // User provided a color, make sure it's in the product's colors
                if (!$product->colors->contains('id', $validated['color_id'])) {
                    return response()->json(
                        [
                            'status' => 422,
                            'message' => 'Selected color is not available for this product.',
                        ],
                        422
                    );
                }
            } else {
                // User did not provide color, only allow if product has no colors
                if ($product->colors->isNotEmpty()) {
                    return response()->json(
                        [
                            'status' => 422,
                            'message' => 'You must select a color for this product.',
                        ],
                        422
                    );
                }
            }


            // $color = Color::findOrFail($validated['color_id']);
            $color = null;
            if (!empty($validated['color_id'])) {
                $color = Color::findOrFail($validated['color_id']);
            }

            // 3. Find an existing cart entry for this user and product
            // $cartItem = Cart::where('user_id', $user->id)
            //     ->where('product_id', $product->id)
            //     ->first();
            $cartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('color_id', $color ? $color->id : null) // strict match on color_id
                ->first();

            $priceDiscount = round($product->price - (($product->price * $product->discount) / 100), 2);

            if ($cartItem) {
                // If the item exists, update its quantity
                $cartItem->quantity += $validated['quantity'];
                // Update total price
                $cartItem->price = round($priceDiscount * $cartItem->quantity, 2);
                $cartItem->save();
            } else {
                // If not, create a new cart entry
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'color_id' => $color ? $color->id : null,
                    'quantity' => $validated['quantity'],
                    // 'price' => $product->price, // Store the current product price
                    // 'price' => $priceDiscount,
                    'price' => round($priceDiscount * $validated['quantity'], 2),
                ]);
            }

            // Commit the transaction
            DB::commit();

            // 4. Return the updated cart for this user
            // We use 'product' to eager load the product details for each cart entry
            $cart = Cart::with('product')->where('user_id', $user->id)->get();
            // Load only the matching color per cart item
            foreach ($cart as $item) {
                if ($item->color_id) {
                    // Only fetch the one color that matches
                    $item->selected_color = $item->product
                        ->colors()
                        ->where('colors.id', $item->color_id)
                        ->first();
                } else {
                    $item->selected_color = null;
                }
            }

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Product added to cart successfully!',
                    'cart' => $cart,
                ],
                200
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 422,
                    'message' => 'Validation Error',
                    'errors' => $e->errors(),
                ],
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 500,
                    'message' => 'An error occurred while adding to the cart.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Get all cart entries for the authenticated user.
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
        // Load only the matching color per cart item
        foreach ($cart as $item) {
            if ($item->color_id) {
                // Only fetch the one color that matches
                $item->selected_color = $item->product
                    ->colors()
                    ->where('colors.id', $item->color_id)
                    ->first();
            } else {
                $item->selected_color = null;
            }
        }

        // $cart = Cart::with('product.colors', 'color')->where('user_id', $user->id)->get();
        // $cart = Cart::with('product.colors')->where('user_id', $user->id)->get();
        // $cart->transform(function ($item) {
        //     if ($item->color_id) {
        //         $item->selected_color = $item->product->colors
        //             ->firstWhere('id', $item->color_id);
        //     } else {
        //         $item->selected_color = null;
        //     }

        //     return $item;
        // });

        if ($cart->isEmpty()) {
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Cart not found or is empty.',
                    'cart' => [],
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => 200,
                'message' => 'Cart retrieved successfully.',
                'cart' => $cart,
            ],
            200
        );
    }

    // Update cart by quantity
    public function updateCartByQuantity(Request $request)
    {
        // Start a database transaction for atomicity
        DB::beginTransaction();

        try {
            $request->validate([
                'cart_id' => 'required|exists:carts,id',
                'quantity' => 'required|integer|min:1'
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
            // Find cart item that belongs to this user
            $cartItem = Cart::where('id', $request->cart_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$cartItem) {
                return response()->json(
                    [
                        'status' => 404,
                        'message' => 'Cart item not found.',
                    ],
                    404
                );
            }

            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            DB::commit();

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Update cart by quantity successfully.',
                ],
                200
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 422,
                    'message' => 'Validation Error',
                    'errors' => $e->errors(),
                ],
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'status' => 500,
                    'message' => 'An error occurred while edit cart by quantity.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Delete a product from the authenticated user's cart.
    public function deleteFromCart(Request $request, $cartId)
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

        // Find cart item that belongs to this user
        $cartItem = Cart::where('id', $cartId)
            ->where('user_id', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json(
                [
                    'status' => 404,
                    'message' => 'Cart item not found.',
                ],
                404
            );
        }

        $cartItem->delete();

        return response()->json(
            [
                'status' => 200,
                'message' => 'Cart item deleted successfully.',
            ],
            200
        );
    }
}
