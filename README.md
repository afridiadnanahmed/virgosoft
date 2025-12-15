# Limit-Order Exchange Mini Engine

A full-stack cryptocurrency limit order exchange built with Laravel and Vue.js, featuring real-time order matching and Pusher-based live updates.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 (Composition API) + TypeScript + Inertia.js
- **Styling**: Tailwind CSS + shadcn-vue components
- **Database**: MySQL
- **Real-time**: Pusher + Laravel Echo
- **Authentication**: Laravel Fortify + Sanctum

## Features

- **Limit Order Trading**: Place buy/sell orders for BTC and ETH
- **Order Matching Engine**: Automatic matching when buy price >= sell price
- **Real-time Updates**: Live orderbook and balance updates via Pusher
- **Commission System**: 1.5% commission on matched trades (deducted from seller)
- **Wallet Management**: USD balance and crypto asset tracking
- **Order History**: View and filter orders by symbol, side, and status
- **Toast Notifications**: Real-time alerts for order events

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL 8.0+
- Pusher account (for real-time features)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd virgosoft-test
```

### 2. Install Dependencies

```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Update `.env` with your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=virgosoft
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:

```bash
mysql -u root -p -e "CREATE DATABASE virgosoft;"
```

### 5. Configure Pusher

1. Create a free account at [pusher.com](https://pusher.com)
2. Create a new Channels app
3. Update `.env` with your Pusher credentials:

```env
BROADCAST_CONNECTION=pusher
QUEUE_CONNECTION=sync

PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=ap2

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Create a Test User (Optional)

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'balance' => '10000.00000000', // $10,000 USD
]);

// Add some crypto assets
$user->assets()->create(['symbol' => 'BTC', 'amount' => '1.00000000', 'locked_amount' => '0']);
$user->assets()->create(['symbol' => 'ETH', 'amount' => '10.00000000', 'locked_amount' => '0']);
```

### 8. Build Frontend Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 9. Start the Application

```bash
# Start Laravel server
php artisan serve

# In a separate terminal, start Vite dev server (for development)
npm run dev
```

Visit `http://localhost:8000` in your browser.

## API Endpoints

All API endpoints require authentication via session cookie.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/profile` | Get user's USD balance and asset balances |
| GET | `/api/orders?symbol=BTC` | Get orderbook (open orders) for a symbol |
| GET | `/api/user/orders` | Get user's order history |
| POST | `/api/orders` | Create a new limit order |
| POST | `/api/orders/{id}/cancel` | Cancel an open order |

### Create Order Request

```json
{
    "symbol": "BTC",
    "side": "buy",
    "price": "50000.00",
    "amount": "0.1"
}
```

### Create Order Response

```json
{
    "order": {
        "id": 1,
        "user_id": 1,
        "symbol": "BTC",
        "side": "buy",
        "price": "50000.00000000",
        "amount": "0.10000000",
        "status": 1,
        "created_at": "2025-12-15T12:00:00.000000Z"
    },
    "message": "Order created successfully"
}
```

## Order Status Codes

| Status | Description |
|--------|-------------|
| 1 | Open (active in orderbook) |
| 2 | Filled (matched and executed) |
| 3 | Cancelled (cancelled by user) |

## Business Logic

### Order Matching

- **Buy orders** match with sell orders where `sell.price <= buy.price`
- **Sell orders** match with buy orders where `buy.price >= sell.price`
- Only **full matches** are supported (partial fills not implemented)
- Orders are matched at the **maker's price** (the order already in the book)

### Commission

- **Rate**: 1.5% of the matched USD value
- **Deducted from**: Seller's proceeds
- **Example**: Selling 1 BTC at $50,000 = $50,000 total, $750 commission, seller receives $49,250

### Balance Locking

- **Buy orders**: USD is locked when order is placed
- **Sell orders**: Crypto asset is locked when order is placed
- **On cancel**: Locked funds are released back to available balance
- **On match**: Locked funds are transferred to counterparty

## Real-time Events

The application broadcasts events to private user channels:

- **Channel**: `private-user.{userId}`
- **Event**: `order.matched`

```typescript
// Frontend listener example
window.Echo.private(`private-user.${userId}`)
    .listen('.order.matched', (data) => {
        console.log('Trade executed:', data.trade);
    });
```

## Project Structure

```
├── app/
│   ├── Events/
│   │   └── OrderMatched.php          # Real-time trade event
│   ├── Http/Controllers/Api/
│   │   ├── OrderController.php       # Order CRUD & matching engine
│   │   └── ProfileController.php     # User profile API
│   └── Models/
│       ├── Asset.php                 # User crypto holdings
│       ├── Order.php                 # Limit orders
│       ├── Trade.php                 # Executed trades
│       └── User.php                  # User with balance
├── database/migrations/              # Database schema
├── resources/js/
│   ├── components/trading/           # Trading UI components
│   │   ├── OrderForm.vue
│   │   ├── Orderbook.vue
│   │   ├── OrderHistory.vue
│   │   └── WalletBalance.vue
│   ├── composables/
│   │   ├── useTrading.ts             # Trading API & Echo listeners
│   │   └── useToast.ts               # Toast notifications
│   └── pages/
│       └── Trading.vue               # Main trading page
└── routes/
    ├── api.php                       # API routes
    └── channels.php                  # Broadcasting channels
```

## Testing the Application

1. Register two user accounts
2. Add USD balance and crypto assets to both users via tinker
3. User A: Place a **sell** order (e.g., sell 0.1 BTC at $50,000)
4. User B: Place a **buy** order (e.g., buy 0.1 BTC at $50,000)
5. Orders should match automatically
6. Both users receive real-time notifications
7. Balances update instantly

## Security Considerations

- All API endpoints are protected by authentication middleware
- CSRF protection on all POST requests
- Database transactions with row locking for race safety
- Input validation on all order parameters
- Balance/asset validation before order creation

## License

This project was created for the Virgosoft technical assessment.
