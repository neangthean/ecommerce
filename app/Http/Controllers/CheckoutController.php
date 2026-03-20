<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function processCheckout(Request $request)
    {
        // 1. Check Authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = Auth::user();

        // Load cart items with product relationship to get the name for the snapshot
        $cartItems = Cart::with('products')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'Your cart is empty!',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $subtotal = $cartItems->sum(fn($item) => $item->price * $item->quantity);

            // 2. Create the Order
            $order = Order::create([
                'user_id'          => $user->id,
                'order_number'     => 'ORD-' . strtoupper(uniqid()),
                'status'           => 'pending',
                'subtotal'         => $subtotal,
                'total_amount'     => $subtotal + 10.00,
                'shipping_address' => [
                    'name'    => $request->full_name,
                    'address' => $request->address,
                    'city'    => $request->city,
                    'zip'     => $request->zip_code,
                ],
                'payment_status'   => 'unpaid',
            ]);

            // 3. Move Cart items to Order Items
            foreach ($cartItems as $cartItem) {
                $order->orderItems()->create([
                    'product_id'   => $cartItem->product_id,
                    'color_id'     => $cartItem->color_id,
                    'quantity'     => $cartItem->quantity,
                    'price'        => $cartItem->price,
                    'product_name' => $cartItem->product->name,
                ]);
            }

            // 4. Clear the user's cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            // 5. Success JSON Response
            return response()->json([
                'status' => 201,
                'message' => 'Order placed successfully!',
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function shipOrder(Request $request)
    {
        // 1. Validate incoming data
        $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'carrier'       => 'required|string|max:50',
            'tracking_code' => 'required|string|unique:shipping_trackings,tracking_number',
        ]);

        // 2. Mark the order as 'processing' or 'shipped'
        $order = Order::findOrFail($request->order_id);
        $order->update(['status' => 'processing']);

        // 3. Insert into shipping_trackings
        $tracking = $order->tracking()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'carrier' => $request->carrier, // e.g., 'DHL'
                'tracking_number' => $request->tracking_code, // Provided by courier
                'status' => 'label_created',
                'estimated_delivery' => now()->addDays(3),
            ]
        );

        return response()->json([
            'status' => 201,
            'message' => 'Order marked as shipped and tracking info saved!',
            'data' => $tracking,
        ], 201);
    }
}
