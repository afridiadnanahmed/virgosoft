<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Order {
    id: number;
    price: string;
    amount: string;
}

defineProps<{
    symbol: string;
    buyOrders: Order[];
    sellOrders: Order[];
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Orderbook - {{ symbol }}</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-4">
                <!-- Sell Orders (Asks) -->
                <div>
                    <div class="text-sm font-medium text-red-500 mb-2">Sell Orders</div>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <div class="grid grid-cols-2 text-xs text-muted-foreground pb-1 border-b">
                            <span>Price</span>
                            <span class="text-right">Amount</span>
                        </div>
                        <div
                            v-for="order in sellOrders"
                            :key="order.id"
                            class="grid grid-cols-2 text-sm py-1 hover:bg-muted/50 rounded"
                        >
                            <span class="text-red-500">${{ parseFloat(order.price).toFixed(2) }}</span>
                            <span class="text-right">{{ parseFloat(order.amount).toFixed(8) }}</span>
                        </div>
                        <div v-if="!sellOrders?.length" class="text-xs text-muted-foreground text-center py-4">
                            No sell orders
                        </div>
                    </div>
                </div>

                <!-- Buy Orders (Bids) -->
                <div>
                    <div class="text-sm font-medium text-green-500 mb-2">Buy Orders</div>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <div class="grid grid-cols-2 text-xs text-muted-foreground pb-1 border-b">
                            <span>Price</span>
                            <span class="text-right">Amount</span>
                        </div>
                        <div
                            v-for="order in buyOrders"
                            :key="order.id"
                            class="grid grid-cols-2 text-sm py-1 hover:bg-muted/50 rounded"
                        >
                            <span class="text-green-500">${{ parseFloat(order.price).toFixed(2) }}</span>
                            <span class="text-right">{{ parseFloat(order.amount).toFixed(8) }}</span>
                        </div>
                        <div v-if="!buyOrders?.length" class="text-xs text-muted-foreground text-center py-4">
                            No buy orders
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
