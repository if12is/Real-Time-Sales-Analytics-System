<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\AnalyticsUpdated;

class AnalyticsController extends Controller
{
    /**
     * Get real-time sales analytics.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAnalytics()
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

        return response()->json($analyticsData);
    }
}
