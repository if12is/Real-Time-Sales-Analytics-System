@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Dashboard</h5>
                <div>
                    <span id="last-updated">Last updated: Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Key Metrics -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Key Metrics</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="dashboard-metric">
                            <div class="metric-value" id="total-revenue">$0.00</div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dashboard-metric">
                            <div class="metric-value" id="last-minute-orders">0</div>
                            <div class="metric-label">Orders (Last Minute)</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dashboard-metric">
                            <div class="metric-value" id="last-minute-revenue">$0.00</div>
                            <div class="metric-label">Revenue (Last Minute)</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dashboard-metric">
                            <div class="metric-value" id="weather-temp">--°C</div>
                            <div class="metric-label" id="weather-condition">Weather</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Top Products</div>
            <div class="card-body">
                <canvas id="top-products-chart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Category Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Sales by Category</div>
            <div class="card-body">
                <canvas id="category-chart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- AI Recommendations -->
    <div class="col-md-6">
        <div class="card recommendations-card">
            <div class="card-header">AI Recommendations</div>
            <div class="card-body" id="recommendations-container">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">Recent Orders</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders">
                            <tr>
                                <td colspan="6" class="text-center">No recent orders</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Global charts
    let topProductsChart = null;
    let categoryChart = null;

    // Initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Get initial data
        fetchAnalytics();
        fetchRecommendations();

        // Debug Pusher connection
        pusher.connection.bind('connected', function() {
            console.log('✅ Connected to Pusher');
        });

        pusher.connection.bind('error', function(err) {
            console.error('❌ Pusher connection error:', err);
        });

        // Subscribe to channels
        const ordersChannel = pusher.subscribe('orders');
        const analyticsChannel = pusher.subscribe('analytics');

        // Listen for subscription success
        ordersChannel.bind('pusher:subscription_succeeded', function() {
            console.log('✅ Subscribed to orders channel');
        });

        // Listen for subscription error
        ordersChannel.bind('pusher:subscription_error', function(status) {
            console.error('❌ Orders channel subscription error:', status);
        });

        // Listen for new orders
        ordersChannel.bind('new.order', function(data) {
            console.log('📦 New order received:', data);
            // Add to recent orders table
            updateRecentOrders(data);
            // Refresh analytics
            fetchAnalytics();
        });

        // Listen for analytics updates
        analyticsChannel.bind('analytics.updated', function(data) {
            console.log('📊 Analytics update received:', data);
            updateDashboard(data.analytics);
        });
    });

    function fetchAnalytics() {
        fetch('/api/analytics')
            .then(response => response.json())
            .then(data => {
                console.log(data);
                updateDashboard(data);
            })
            .catch(error => {
                console.error('Error fetching analytics:', error);
            });
    }

    function fetchRecommendations() {
        fetch('/api/recommendations')
            .then(response => response.json())
            .then(data => {
                updateRecommendations(data);
            })
            .catch(error => {
                console.error('Error fetching recommendations:', error);
            });
    }

    function updateDashboard(data) {
        // Update metrics
        document.getElementById('total-revenue').textContent = '$' + parseFloat(data.total_revenue).toFixed(2);
        document.getElementById('last-minute-orders').textContent = data.orders_last_minute;
        document.getElementById('last-minute-revenue').textContent = '$' + parseFloat(data.revenue_last_minute).toFixed(2);
        document.getElementById('last-updated').textContent = 'Last updated: ' + data.timestamp;

        // Update charts
        updateTopProductsChart(data.top_products);
        updateCategoryChart(data.category_analytics);
    }

    function updateTopProductsChart(topProducts) {
        const labels = topProducts.map(p => p.name);
        const data = topProducts.map(p => p.total_revenue);

        if (topProductsChart) {
            topProductsChart.data.labels = labels;
            topProductsChart.data.datasets[0].data = data;
            topProductsChart.update();
        } else {
            const ctx = document.getElementById('top-products-chart').getContext('2d');
            topProductsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    function updateCategoryChart(categories) {
        const labels = categories.map(c => c.category);
        const data = categories.map(c => c.total_revenue);

        if (categoryChart) {
            categoryChart.data.labels = labels;
            categoryChart.data.datasets[0].data = data;
            categoryChart.update();
        } else {
            const ctx = document.getElementById('category-chart').getContext('2d');
            categoryChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    }

    function updateRecommendations(data) {
        const container = document.getElementById('recommendations-container');
        container.innerHTML = '';

        if (data.weather_data) {
            document.getElementById('weather-temp').textContent = data.weather_data.temperature + '°C';
            document.getElementById('weather-condition').textContent = data.weather_data.conditions;
        }

        if (data.recommendations && data.recommendations.length > 0) {
            data.recommendations.forEach(rec => {
                // Create recommendation card
                const card = document.createElement('div');
                card.className = 'alert alert-' + getAlertClass(rec.type) + ' mb-2';

                // Create content
                const content = document.createElement('div');
                content.innerHTML = `
                    <h6 class="mb-1">${getIconForType(rec.type)} ${formatRecommendationType(rec.type)}</h6>
                    <p class="mb-1">${rec.message}</p>
                    <small class="text-muted">Confidence: ${(rec.confidence * 100).toFixed(0)}%</small>
                `;

                card.appendChild(content);
                container.appendChild(card);
            });
        } else {
            container.innerHTML = '<p class="text-center">No recommendations available</p>';
        }
    }

    function updateRecentOrders(order) {
        const container = document.getElementById('recent-orders');

        // Clear "no orders" message if present
        if (container.innerHTML.includes('No recent orders')) {
            container.innerHTML = '';
        }

        // Add the new order at the top
        const row = document.createElement('tr');
        row.className = 'table-success';
        row.innerHTML = `
            <td>${order.id}</td>
            <td>${order.product.name}</td>
            <td>${order.quantity}</td>
            <td>$${parseFloat(order.price).toFixed(2)}</td>
            <td>$${parseFloat(order.total).toFixed(2)}</td>
            <td>${order.created_at}</td>
        `;

        container.insertBefore(row, container.firstChild);

        // Highlight effect
        setTimeout(() => {
            row.className = '';
        }, 3000);
    }

    function getAlertClass(type) {
        switch(type) {
            case 'top_product_promo': return 'primary';
            case 'weather_based': return 'success';
            case 'category_boost': return 'warning';
            case 'dynamic_pricing': return 'info';
            default: return 'secondary';
        }
    }

    function getIconForType(type) {
        switch(type) {
            case 'top_product_promo': return '🏆';
            case 'weather_based': return '🌦️';
            case 'category_boost': return '📈';
            case 'dynamic_pricing': return '💲';
            default: return '💡';
        }
    }

    function formatRecommendationType(type) {
        return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }
</script>
@endsection