<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use App\Models\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{

    public function processCheckout(Request $request)
    {
        // 1. Validate incoming data
        $request->validate([
            'subtotal'          => 'required|numeric|min:0',
            'shipping_cost'     => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'payment_method'    => 'required|in:cod,paypal', // Required for logic
            'formatted_address' => 'required|string|max:500',
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = Auth::user();
        // Load cart items with product relationship to get the name for the snapshot
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => 400, 'message' => 'Your cart is empty!'], 400);
        }

        // Check stock availability for each cart item
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if ($cartItem->quantity > $product->stock) {
                return response()->json([
                    'status' => 400,
                    'message' => "Insufficient stock for product '{$product->title}'. Available stock: {$product->stock}, requested: {$cartItem->quantity}."
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            // --- 1. Generate Unique Order Number (Locked for Safety) ---
            $today = date('dmY');
            $lastOrder = Order::where('order_number', 'like', 'ORD-' . $today . '-%')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $newSequence = $lastOrder
                ? str_pad((int)substr($lastOrder->order_number, -4) + 1, 4, '0', STR_PAD_LEFT)
                : '0001';

            $order_number = 'ORD-' . $today . '-' . $newSequence;

            // --- 2. Create the Order ---
            // For COD, we set status to 'processing' immediately. 
            // For PayPal, it stays 'pending' until the webhook confirms payment.
            $order = Order::create([
                'user_id'         => $user->id,
                'order_number'    => $order_number,
                'status'          => ($request->payment_method === 'cod') ? 'processing' : 'pending',
                'payment_method'  => $request->payment_method,
                'payment_status'  => ($request->payment_method === 'cod') ? 'pending_cod' : 'unpaid',
                'subtotal'        => $request->subtotal,
                'shipping_cost'   => $request->shipping_cost,
                'total_amount'    => $request->total_amount,
                'shipping_address' => $request->formatted_address, // Snapshot
            ]);

            // --- 3. Create Order Items & Delivery Address ---
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $discountedPrice = $product->price - (($product->price * $product->discount) / 100);
                $totalPrice = round($discountedPrice * $cartItem->quantity, 2);
                $productName = $product->title ?? $product->name ?? 'Unknown Product';

                $order->orderItems()->create([
                    'product_id'   => $cartItem->product_id,
                    'color_id'     => $cartItem->color_id,
                    'size_id'      => $cartItem->size_id,
                    'quantity'     => $cartItem->quantity,
                    'price'        => $totalPrice,
                    'product_name' => $productName,
                ]);

                // Reduce stock in products table by cart quantity
                if ($product) {
                    $newStock = max(0, $product->stock - $cartItem->quantity);
                    $product->update(['stock' => $newStock]);
                }
            }

            $order->deliveryAddress()->create([
                'formatted_address' => $request->formatted_address,
                'latitude'          => $request->latitude,
                'longitude'         => $request->longitude,
            ]);

            // --- 4. Handle Payments Table for COD ---
            if ($request->payment_method === 'cod') {
                $order->payments()->create([
                    'transaction_id' => null, // No ID yet for cash
                    'payment_method' => 'cod',
                    'amount'         => $request->total_amount,
                    'status'         => 'pending', // Stays pending until driver gets cash
                ]);
            }

            // --- 5. Clear Cart & Commit ---
            Cart::where('user_id', $user->id)->delete();
            DB::commit();

            // --- 6. API Response ---
            $responseData = [
                'status'       => 201,
                'message'      => 'Order placed successfully!',
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
            ];

            // If PayPal, return a URL for the mobile app to open in WebView
            if ($request->payment_method === 'paypal') {
                $responseData['paypal_url'] = url('/api/paypal/pay/' . $order->id);
            }

            return response()->json($responseData, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function listOrders(Request $request)
    {
        $user = Auth::user();

        $orders = Order::with(['orderItems.product', 'orderItems.color', 'orderItems.size', 'deliveryAddress', 'payments', 'tracking'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'orders' => $orders,
        ], 200);
    }

    public function getOrderDetail(Request $request, $orderId)
    {
        $user = Auth::user();

        $order = Order::with(['orderItems.product', 'orderItems.color', 'orderItems.size', 'deliveryAddress', 'payments', 'tracking'])
            ->where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found or access denied.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'order' => $order,
        ], 200);
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
                'status' => 'Label created',
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
