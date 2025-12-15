<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trade $trade;
    private int $userId;

    public function __construct(Trade $trade, int $userId)
    {
        $this->trade = $trade;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('private-user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    public function broadcastWith(): array
    {
        $isBuyer = $this->userId === $this->trade->buyer_id;

        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'total' => $this->trade->total,
                'commission' => $this->trade->commission,
                'side' => $isBuyer ? 'buy' : 'sell',
                'created_at' => $this->trade->created_at->toISOString(),
            ],
            'order_id' => $isBuyer ? $this->trade->buy_order_id : $this->trade->sell_order_id,
        ];
    }
}
