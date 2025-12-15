# Limit-Order Exchange Mini Engine - Task List

## Project Setup
- [x] Initialize Laravel project
- [x] Initialize Vue.js frontend
- [x] Configure database (MySQL/PostgreSQL)
- [ ] Configure Pusher for real-time broadcasting

## Backend - Database & Models
- [x] Create `users` migration (add `balance` column)
- [x] Create `assets` migration (`user_id`, `symbol`, `amount`, `locked_amount`)
- [x] Create `orders` migration (`user_id`, `symbol`, `side`, `price`, `amount`, `status`: 1=open, 2=filled, 3=cancelled)
- [x] Create `trades` migration (optional - bonus)
- [x] Create Eloquent models with relationships

## Backend - API Endpoints
- [ ] `GET /api/profile` - Return user's USD balance + asset balances
- [ ] `GET /api/orders?symbol=` - Return all open orders for orderbook
- [ ] `POST /api/orders` - Create a limit order
- [ ] `POST /api/orders/{id}/cancel` - Cancel order & release locked funds

## Backend - Core Business Logic
- [ ] Implement Buy Order logic (check balance, deduct, lock USD)
- [ ] Implement Sell Order logic (check assets, lock amount)
- [ ] Implement Order Matching engine (full match only)
  - [ ] BUY matches SELL where `sell.price <= buy.price`
  - [ ] SELL matches BUY where `buy.price >= sell.price`
- [ ] Implement Commission calculation (1.5% of matched USD value)
- [ ] Ensure atomic execution & race safety (database transactions/locks)

## Backend - Real-Time Integration
- [ ] Create `OrderMatched` event
- [ ] Configure private channels (`private-user.{id}`)
- [ ] Broadcast on successful match

## Frontend - Vue.js
- [ ] Setup authentication (Login/Register/Logout)
- [ ] Create Limit Order Form component
  - [ ] Symbol dropdown (BTC/ETH)
  - [ ] Side selector (Buy/Sell)
  - [ ] Price & Amount inputs
  - [ ] Place Order button
- [ ] Create Orders & Wallet Overview screen
  - [ ] Display USD and Asset balances
  - [ ] Display all orders (open/filled/cancelled)
  - [ ] Display Orderbook for selected symbol
- [ ] Integrate Pusher/Laravel Echo for real-time updates
  - [ ] Listen for `OrderMatched` event
  - [ ] Update balance, assets, and order list instantly

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
