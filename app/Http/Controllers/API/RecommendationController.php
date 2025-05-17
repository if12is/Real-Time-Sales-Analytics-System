<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    /**
     * Get product recommendations based on sales data and external factors.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRecommendations()
    {
        try {
            // Get the recent sales data
            $recentOrders = Order::with('product')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

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

            // Get sales by category
            $categorySales = DB::table('orders')
                ->join('products', 'orders.product_id', '=', 'products.id')
                ->select(
                    'products.category',
                    DB::raw('SUM(orders.quantity) as total_quantity'),
                    DB::raw('SUM(orders.total) as total_revenue')
                )
                ->groupBy('products.category')
                ->orderBy('total_revenue', 'desc')
                ->get();

            // Get weather data
            $weatherData = $this->getWeatherData();

            // Generate recommendations based on sales data and weather
            $recommendations = $this->generateRecommendations($topProducts, $categorySales, $weatherData, $recentOrders);

            return response()->json([
                'recommendations' => $recommendations,
                'weather_data' => $weatherData,
                'top_products' => $topProducts,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate recommendations: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate recommendations', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current weather data from OpenWeather API.
     *
     * @return array
     */
    private function getWeatherData()
    {
        try {
            $apiKey = config('services.openweather.key');
            $city = 'Cairo'; // Default city, could be made configurable

            // Using a simple cache to avoid frequent API calls
            $cacheKey = 'weather_data_' . $city;

            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Mock data for demonstration if no API key is provided
            if (empty($apiKey) || $apiKey === 'your-api-key') {
                $mockData = [
                    'location' => $city,
                    'temperature' => rand(5, 35), // Random temperature between 5 and 35 degrees
                    'conditions' => ['Clear', 'Cloudy', 'Rainy', 'Sunny', 'Stormy'][rand(0, 4)],
                    'is_hot' => rand(0, 1) === 1,
                ];

                Cache::put($cacheKey, $mockData, 60 * 30); // Cache for 30 minutes
                return $mockData;
            }

            $client = new Client();
            $response = $client->get("https://api.openweathermap.org/data/2.5/weather", [
                'query' => [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric'
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            $weatherData = [
                'location' => $data['name'],
                'temperature' => $data['main']['temp'],
                'conditions' => $data['weather'][0]['main'],
                'is_hot' => $data['main']['temp'] > 25, // Consider hot if above 25°C
            ];

            Cache::put($cacheKey, $weatherData, 60 * 30); // Cache for 30 minutes
            return $weatherData;
        } catch (\Exception $e) {
            Log::error('Failed to fetch weather data: ' . $e->getMessage());

            // Return mock data on error
            return [
                'location' => 'Unknown',
                'temperature' => 22,
                'conditions' => 'Unknown',
                'is_hot' => false,
            ];
        }
    }

    /**
     * Generate recommendations using Gemini API based on sales data and weather.
     *
     * @param  \Illuminate\Support\Collection  $topProducts
     * @param  \Illuminate\Support\Collection  $categorySales
     * @param  array  $weatherData
     * @param  \Illuminate\Support\Collection  $recentOrders
     * @return array
     */
    private function generateRecommendations($topProducts, $categorySales, $weatherData, $recentOrders)
    {
        try {
            // Get Gemini API key from environment
            $apiKey = env('GOOGLE_API_KEY');

            // If API key is not available, fall back to the simulated response
            if (empty($apiKey)) {
                Log::warning('Gemini API key not found. Using simulated AI response.');
                return $this->generateSimulatedRecommendations($topProducts, $categorySales, $weatherData);
            }

            // Prepare data to send to Gemini API
            $salesData = [
                'top_products' => $topProducts->toArray(),
                'category_sales' => $categorySales->toArray(),
                'recent_orders' => $recentOrders->map(function ($order) {
                    return [
                        'product_name' => $order->product->name,
                        'product_category' => $order->product->category,
                        'quantity' => $order->quantity,
                        'total' => $order->total,
                        'date' => $order->created_at->toDateTimeString()
                    ];
                })->toArray(),
                'weather' => $weatherData
            ];

            // Create prompt for Gemini
            $prompt = "You are a retail analytics expert. Given the following sales data and weather information, provide 3-4 specific product promotion recommendations to increase revenue.\n\n";
            $prompt .= "Current Weather: " . $weatherData['conditions'] . ", " . $weatherData['temperature'] . "°C\n\n";

            $prompt .= "Top Selling Products:\n";
            foreach ($topProducts as $product) {
                $prompt .= "- {$product->name} (Category: {$product->category}): Quantity: {$product->total_quantity}, Revenue: \${$product->total_revenue}\n";
            }

            $prompt .= "\nCategory Sales:\n";
            foreach ($categorySales as $category) {
                $prompt .= "- {$category->category}: Quantity: {$category->total_quantity}, Revenue: \${$category->total_revenue}\n";
            }

            $prompt .= "\nGiven this sales data, which products should we promote for higher revenue? Consider weather conditions, top-selling products, and category performance. Format your response as a JSON array of recommendations, where each recommendation has these properties: type (string), message (string), product_id (number, if applicable), category (string, if applicable), and confidence (number between 0-1).";

            // Call Gemini API
            $client = new Client();
            $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'key' => $apiKey
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generation_config' => [
                        'temperature' => 0.4,
                        'max_output_tokens' => 1024
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Gemini API Response: ' . json_encode($result));
            // Parse the response to extract recommendations
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'];

                // Extract JSON part from the response
                preg_match('/\[.*\]/s', $aiResponse, $matches);

                if (!empty($matches)) {
                    $recommendationsJson = $matches[0];
                    $recommendations = json_decode($recommendationsJson, true);

                    // If we successfully parsed the recommendations, return them
                    if (is_array($recommendations)) {
                        // Make sure each recommendation has the required fields
                        foreach ($recommendations as &$rec) {
                            if (!isset($rec['confidence'])) {
                                $rec['confidence'] = 0.7; // Default confidence
                            }
                            // Map product names to IDs if missing
                            if (isset($rec['product_name']) && !isset($rec['product_id'])) {
                                foreach ($topProducts as $product) {
                                    if ($product->name === $rec['product_name']) {
                                        $rec['product_id'] = $product->id;
                                        break;
                                    }
                                }
                            }
                        }
                        Log::info('Recommendations: ' . json_encode($recommendations));
                        return $recommendations;
                    }
                }
            }

            // If we couldn't parse the response, fall back to simulated recommendations
            Log::warning('Failed to parse Gemini API response. Using simulated AI response.');
            return $this->generateSimulatedRecommendations($topProducts, $categorySales, $weatherData);
        } catch (\Exception $e) {
            Log::error('Error generating recommendations with Gemini API: ' . $e->getMessage());
            // Fallback to simulated recommendations
            return $this->generateSimulatedRecommendations($topProducts, $categorySales, $weatherData);
        }
    }

    /**
     * Generate simulated recommendations (fallback method).
     *
     * @param  \Illuminate\Support\Collection  $topProducts
     * @param  \Illuminate\Support\Collection  $categorySales
     * @param  array  $weatherData
     * @return array
     */
    private function generateSimulatedRecommendations($topProducts, $categorySales, $weatherData)
    {
        try {
            $recommendations = [];

            // Basic recommendations based on top products
            $recommendations[] = [
                'type' => 'top_product_promo',
                'message' => 'Promote your top selling product: ' . $topProducts[0]->name,
                'product_id' => $topProducts[0]->id,
                'confidence' => 0.95,
            ];

            // Weather-based recommendations
            if ($weatherData['is_hot']) {
                // If hot, recommend cold drinks
                $coldDrinks = Product::where('category', 'Cold Drinks')->get();
                if ($coldDrinks->count() > 0) {
                    $randomColdDrink = $coldDrinks->random();
                    $recommendations[] = [
                        'type' => 'weather_based',
                        'message' => 'It\'s hot outside! Promote cold drinks like ' . $randomColdDrink->name,
                        'product_id' => $randomColdDrink->id,
                        'confidence' => 0.85,
                    ];
                }
            } else {
                // If not hot, recommend hot drinks
                $hotDrinks = Product::where('category', 'Hot Drinks')->get();
                if ($hotDrinks->count() > 0) {
                    $randomHotDrink = $hotDrinks->random();
                    $recommendations[] = [
                        'type' => 'weather_based',
                        'message' => 'It\'s cool outside! Promote hot drinks like ' . $randomHotDrink->name,
                        'product_id' => $randomHotDrink->id,
                        'confidence' => 0.85,
                    ];
                }
            }

            // Category recommendations
            if (count($categorySales) > 0) {
                $leastPopularCategory = $categorySales->last();
                $recommendations[] = [
                    'type' => 'category_boost',
                    'message' => 'Consider promoting products in the ' . $leastPopularCategory->category . ' category to boost sales',
                    'category' => $leastPopularCategory->category,
                    'confidence' => 0.75,
                ];
            }

            // Dynamic pricing recommendation
            $recommendations[] = [
                'type' => 'dynamic_pricing',
                'message' => 'Consider ' . ($weatherData['is_hot'] ? 'increasing' : 'decreasing') . ' prices of ' . ($weatherData['is_hot'] ? 'cold' : 'hot') . ' drinks by 5-10% based on current weather',
                'confidence' => 0.70,
            ];

            return $recommendations;
        } catch (\Exception $e) {
            Log::error('Error generating simulated recommendations: ' . $e->getMessage());
            return [
                [
                    'type' => 'error',
                    'message' => 'Unable to generate detailed recommendations at this time',
                    'confidence' => 0.5,
                ]
            ];
        }
    }
}
