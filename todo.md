# Limit-Order Exchange Mini Engine - Task List

## Project Setup
- [x] Initialize Laravel project
- [x] Initialize Vue.js frontend
- [x] Configure database (MySQL/PostgreSQL)
- [x] Configure Pusher for real-time broadcasting

## Backend - Database & Models
- [x] Create `users` migration (add `balance` column)
- [x] Create `assets` migration (`user_id`, `symbol`, `amount`, `locked_amount`)
- [x] Create `orders` migration (`user_id`, `symbol`, `side`, `price`, `amount`, `status`: 1=open, 2=filled, 3=cancelled)
- [x] Create `trades` migration (optional - bonus)
- [x] Create Eloquent models with relationships

## Backend - API Endpoints
- [x] `GET /api/profile` - Return user's USD balance + asset balances
- [x] `GET /api/orders?symbol=` - Return all open orders for orderbook
- [x] `POST /api/orders` - Create a limit order
- [x] `POST /api/orders/{id}/cancel` - Cancel order & release locked funds
- [x] `GET /api/user/orders` - Return user's order history

## Backend - Core Business Logic
- [x] Implement Buy Order logic (check balance, deduct, lock USD)
- [x] Implement Sell Order logic (check assets, lock amount)
- [x] Implement Order Matching engine (full match only)
  - [x] BUY matches SELL where `sell.price <= buy.price`
  - [x] SELL matches BUY where `buy.price >= sell.price`
- [x] Implement Commission calculation (1.5% of matched USD value)
- [x] Ensure atomic execution & race safety (database transactions/locks)

## Backend - Real-Time Integration
- [x] Create `OrderMatched` event
- [x] Configure private channels (`private-user.{id}`)
- [x] Broadcast on successful match

## Frontend - Vue.js
- [x] Setup authentication (Login/Register/Logout) - Already configured with Fortify
- [x] Create Limit Order Form component
  - [x] Symbol dropdown (BTC/ETH)
  - [x] Side selector (Buy/Sell)
  - [x] Price & Amount inputs
  - [x] Place Order button
- [x] Create Orders & Wallet Overview screen
  - [x] Display USD and Asset balances
  - [x] Display all orders (open/filled/cancelled)
  - [x] Display Orderbook for selected symbol
- [x] Integrate Pusher/Laravel Echo for real-time updates
  - [x] Listen for `OrderMatched` event
  - [x] Update balance, assets, and order list instantly

## Bonus Features (Optional)
- [ ] Order filtering (by symbol/side/status)
- [ ] Toast notifications/alerts
- [ ] Volume calculation preview
- [ ] Store executed trades in `trades` table

## Final Steps
- [ ] Write README.md with setup instructions
- [ ] Clean up code & security validation
- [ ] Meaningful git commits
- [ ] Push to GitHub/GitLab
