<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with real-time analytics.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * Show the order form.
     *
     * @return \Illuminate\Http\Response
     */
    public function orderForm()
    {
        $products = Product::where('active', true)->get();
        return view('dashboard.order-form', compact('products'));
    }
}
