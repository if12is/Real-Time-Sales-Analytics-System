# Real-Time Sales Analytics System

This is a real-time sales analytics system built with Laravel, featuring WebSockets, charts, and AI-powered recommendations. The system provides real-time updates on sales data and offers intelligent product promotion suggestions based on sales patterns and external factors like weather.

## Features

-   **Real-time Sales Analytics Dashboard**

    -   Live metrics (total revenue, last-minute orders, etc.)
    -   Interactive charts for top products and category performance
    -   WebSocket-based real-time updates

-   **Order Management**

    -   Simple order creation form
    -   Real-time order notifications

-   **AI-Powered Recommendations**

    -   Product promotion suggestions using Gemini API
    -   Weather-based recommendations
    -   Category-based insights
    -   Dynamic pricing suggestions

-   **External API Integration**
    -   Weather API integration for context-aware recommendations
    -   Google Gemini API for intelligent product suggestions

## Technology Stack

-   **Backend**: Laravel, PHP, SQLite
-   **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
-   **Real-time**: Pusher for WebSockets
-   **Charts**: Chart.js
-   **External APIs**:
    -   OpenWeather API for weather data
    -   Google Gemini API for AI-powered recommendations

## Installation & Setup

1. Clone the repository

    ```
    git clone https://github.com/if12is/Real-Time-Sales-Analytics-System.git
    cd realtime
    ```

2. Install PHP dependencies

    ```
    composer install
    ```

3. Create and configure the environment file

    ```
    cp .env.example .env
    php artisan key:generate
    ```

4. Configure the database in the .env file

    ```
    DB_CONNECTION=sqlite
    ```

5. Configure Pusher for WebSockets in the .env file

    ```
    PUSHER_APP_ID=your-app-id
    PUSHER_APP_KEY=your-app-key
    PUSHER_APP_SECRET=your-app-secret
    PUSHER_APP_CLUSTER=mt1
    ```

6. Set your API keys in the .env file

    ```
    OPENWEATHER_API_KEY=your-openweather-api-key
    GOOGLE_API_KEY=your-gemini-api-key
    ```

7. Run migrations and seeds

    ```
    php artisan migrate --seed
    ```

8. Start the development server

    ```
    php artisan serve
    ```

9. Visit http://localhost:8000/dashboard to access the dashboard

## Project Structure

-   **Controllers**

    -   `DashboardController.php`: Handles web views
    -   `API/OrderController.php`: Manages order creation
    -   `API/AnalyticsController.php`: Provides analytics data
    -   `API/RecommendationController.php`: Generates AI-powered recommendations using Gemini API

-   **Models**

    -   `Product.php`: Product model
    -   `Order.php`: Order model

-   **Events**

    -   `NewOrderCreated.php`: Broadcast when a new order is created
    -   `AnalyticsUpdated.php`: Broadcast when analytics are updated

-   **Views**
    -   `layouts/app.blade.php`: Main layout template
    -   `dashboard/index.blade.php`: Dashboard view
    -   `dashboard/order-form.blade.php`: Order creation form

## API Endpoints

-   `POST /api/orders`: Create a new order
-   `GET /api/analytics`: Get real-time sales analytics
-   `GET /api/recommendations`: Get AI-powered product recommendations from Gemini

## AI-Assisted vs. Manual Implementation

### AI-Assisted

-   Gemini API for intelligent product recommendations
-   Chart visualization configurations
-   Basic API endpoint structure

### Manual Implementation

-   Database design and implementation
-   Real-time WebSocket integration
-   Custom analytics logic
-   Weather API integration

## Testing

Run the tests with:

```
php artisan test
```

## License

This project is open-source and available under the [MIT license](LICENSE).
