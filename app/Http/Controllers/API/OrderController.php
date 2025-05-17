<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use App\Events\NewOrderCreated;
use App\Events\AnalyticsUpdated;

class OrderController extends Controller
{
    /**
     * Store a newly created order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Get the product
            $product = Product::findOrFail($request->product_id);

            // Calculate total
            $total = $request->price * $request->quantity;

            // Create the order
            $order = new Order([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'total' => $total,
                'order_date' => Carbon::now(),
            ]);

            $order->save();

            // Commit transaction
            DB::commit();

            // Broadcast order created event for real-time updates
            broadcast(new NewOrderCreated($order));

            // Get updated analytics and broadcast them
            $this->broadcastUpdatedAnalytics();

            return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            Log::error('Failed to create order: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get and broadcast updated analytics after a new order.
     */
    private function broadcastUpdatedAnalytics()
    {
        // Calculate total revenue
        $totalRevenue = Order::sum('total');

        // Get top products by sales
        $topProducts = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.category',
                DB::raw('SUM(orders.quantity) as total_quantity'),
                DB::raw('SUM(orders.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->get();

        // Get revenue change in the last 1 minute
        $oneMinuteAgo = Carbon::now()->subMinute();

        $revenueLastMinute = Order::where('created_at', '>=', $oneMinuteAgo)
            ->sum('total');

        // Count orders in the last 1 minute
        $orderCountLastMinute = Order::where('created_at', '>=', $oneMinuteAgo)
            ->count();

        // Get analytics by category
        $categoryAnalytics = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select(
                'products.category',
                DB::raw('SUM(orders.quantity) as total_quantity'),
                DB::raw('SUM(orders.total) as total_revenue')
            )
            ->groupBy('products.category')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $analyticsData = [
            'total_revenue' => $totalRevenue,
            'top_products' => $topProducts,
            'revenue_last_minute' => $revenueLastMinute,
            'orders_last_minute' => $orderCountLastMinute,
            'category_analytics' => $categoryAnalytics,
            'timestamp' => Carbon::now()->toDateTimeString()
        ];

        // Broadcast the updated analytics to clients
        broadcast(new AnalyticsUpdated($analyticsData));
    }
}
