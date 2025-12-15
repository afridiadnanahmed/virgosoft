<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const COMMISSION_RATE = 0.015; // 1.5%

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
        ]);

        $symbol = $request->input('symbol');

        $buyOrders = Order::open()
            ->buy()
            ->forSymbol($symbol)
            ->orderByDesc('price')
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'price', 'amount', 'created_at']);

        $sellOrders = Order::open()
            ->sell()
            ->forSymbol($symbol)
            ->orderBy('price')
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'price', 'amount', 'created_at']);

        return response()->json([
            'symbol' => $symbol,
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
            'side' => ['required', Rule::in(['buy', 'sell'])],
            'price' => 'required|numeric|gt:0',
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = $request->user();

        try {
            $order = DB::transaction(function () use ($user, $validated) {
                // Lock the user row to prevent race conditions
                $user = User::lockForUpdate()->find($user->id);

                if ($validated['side'] === 'buy') {
                    return $this->createBuyOrder($user, $validated);
                } else {
                    return $this->createSellOrder($user, $validated);
                }
            });

            // Try to match the order
            $this->matchOrder($order);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->fresh(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            DB::transaction(function () use ($user, $id) {
                $order = Order::where('id', $id)
                    ->where('user_id', $user->id)
                    ->where('status', Order::STATUS_OPEN)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Lock user for balance update
                $user = User::lockForUpdate()->find($user->id);

                if ($order->side === 'buy') {
                    // Refund locked USD
                    $lockedAmount = bcmul($order->price, $order->amount, 8);
                    $user->balance = bcadd($user->balance, $lockedAmount, 8);
                    $user->save();
                } else {
                    // Release locked assets
                    $asset = Asset::where('user_id', $user->id)
                        ->where('symbol', $order->symbol)
                        ->lockForUpdate()
                        ->first();

                    if ($asset) {
                        $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                        $asset->save();
                    }
                }

                $order->status = Order::STATUS_CANCELLED;
                $order->save();
            });

            return response()->json([
                'message' => 'Order cancelled successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found or already processed',
            ], 404);
        }
    }

    public function userOrders(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    private function createBuyOrder(User $user, array $data): Order
    {
        $totalCost = bcmul($data['price'], $data['amount'], 8);

        if (bccomp($user->balance, $totalCost, 8) < 0) {
            throw new \Exception('Insufficient USD balance');
        }

        // Deduct USD from balance
        $user->balance = bcsub($user->balance, $totalCost, 8);
        $user->save();

        return Order::create([
            'user_id' => $user->id,
            'symbol' => $data['symbol'],
            'side' => 'buy',
            'price' => $data['price'],
            'amount' => $data['amount'],
            'status' => Order::STATUS_OPEN,
        ]);
    }

    private function createSellOrder(User $user, array $data): Order
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $data['symbol'])
            ->lockForUpdate()
            ->first();

        if (!$asset) {
            throw new \Exception('No ' . $data['symbol'] . ' assets available');
        }

        $availableAmount = bcsub($asset->amount, $asset->locked_amount, 8);

        if (bccomp($availableAmount, $data['amount'], 8) < 0) {
            throw new \Exception('Insufficient ' . $data['symbol'] . ' balance');
        }

        // Lock the assets
        $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
        $asset->save();

        return Order::create([
            'user_id' => $user->id,
            'symbol' => $data['symbol'],
            'side' => 'sell',
            'price' => $data['price'],
            'amount' => $data['amount'],
            'status' => Order::STATUS_OPEN,
        ]);
    }

    private function matchOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Reload order with lock
            $order = Order::lockForUpdate()->find($order->id);

            if ($order->status !== Order::STATUS_OPEN) {
                return;
            }

            if ($order->side === 'buy') {
                // Find matching sell order: sell.price <= buy.price
                $matchingOrder = Order::open()
                    ->sell()
                    ->forSymbol($order->symbol)
                    ->where('price', '<=', $order->price)
                    ->where('amount', $order->amount) // Full match only
                    ->where('user_id', '!=', $order->user_id)
                    ->orderBy('price')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();
            } else {
                // Find matching buy order: buy.price >= sell.price
                $matchingOrder = Order::open()
                    ->buy()
                    ->forSymbol($order->symbol)
                    ->where('price', '>=', $order->price)
                    ->where('amount', $order->amount) // Full match only
                    ->where('user_id', '!=', $order->user_id)
                    ->orderByDesc('price')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$matchingOrder) {
                return;
            }

            $this->executeMatch($order, $matchingOrder);
        });
    }

    private function executeMatch(Order $order, Order $matchingOrder): void
    {
        // Determine buyer and seller
        $buyOrder = $order->side === 'buy' ? $order : $matchingOrder;
        $sellOrder = $order->side === 'sell' ? $order : $matchingOrder;

        // Use the price of the order that was placed first (maker price)
        $executionPrice = $matchingOrder->price;
        $amount = $order->amount;
        $total = bcmul($executionPrice, $amount, 8);
        $commission = bcmul($total, self::COMMISSION_RATE, 8);

        // Lock users
        $buyer = User::lockForUpdate()->find($buyOrder->user_id);
        $seller = User::lockForUpdate()->find($sellOrder->user_id);

        // If buyer paid more than execution price, refund the difference
        $buyerPaid = bcmul($buyOrder->price, $amount, 8);
        $refund = bcsub($buyerPaid, $total, 8);
        if (bccomp($refund, '0', 8) > 0) {
            $buyer->balance = bcadd($buyer->balance, $refund, 8);
        }

        // Buyer receives assets (commission deducted from USD they pay)
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $buyOrder->symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );
        $buyerAsset = Asset::lockForUpdate()->find($buyerAsset->id);
        $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
        $buyerAsset->save();

        // Seller receives USD minus commission
        $sellerReceives = bcsub($total, $commission, 8);
        $seller->balance = bcadd($seller->balance, $sellerReceives, 8);
        $seller->save();
        $buyer->save();

        // Release seller's locked assets
        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->lockForUpdate()
            ->first();
        $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
        $sellerAsset->amount = bcsub($sellerAsset->amount, $amount, 8);
        $sellerAsset->save();

        // Mark orders as filled
        $buyOrder->status = Order::STATUS_FILLED;
        $sellOrder->status = Order::STATUS_FILLED;
        $buyOrder->save();
        $sellOrder->save();

        // Create trade record
        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $buyOrder->symbol,
            'price' => $executionPrice,
            'amount' => $amount,
            'total' => $total,
            'commission' => $commission,
        ]);

        // TODO: Broadcast OrderMatched event via Pusher
    }
}
