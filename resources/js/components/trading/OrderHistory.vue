<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Order {
    id: number;
    symbol: string;
    side: 'buy' | 'sell';
    price: string;
    amount: string;
    status: number;
    created_at: string;
}

defineProps<{
    orders: Order[];
    loading: boolean;
}>();

const emit = defineEmits<{
    cancel: [orderId: number];
}>();

function getStatusLabel(status: number): string {
    switch (status) {
        case 1: return 'Open';
        case 2: return 'Filled';
        case 3: return 'Cancelled';
        default: return 'Unknown';
    }
}

function getStatusVariant(status: number): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 1: return 'default';
        case 2: return 'secondary';
        case 3: return 'destructive';
        default: return 'outline';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Order History</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="text-left py-2">Date</th>
                            <th class="text-left py-2">Symbol</th>
                            <th class="text-left py-2">Side</th>
                            <th class="text-right py-2">Price</th>
                            <th class="text-right py-2">Amount</th>
                            <th class="text-right py-2">Total</th>
                            <th class="text-center py-2">Status</th>
                            <th class="text-right py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders"
                            :key="order.id"
                            class="border-b hover:bg-muted/50"
                        >
                            <td class="py-2 text-xs text-muted-foreground">
                                {{ formatDate(order.created_at) }}
                            </td>
                            <td class="py-2 font-medium">{{ order.symbol }}</td>
                            <td class="py-2">
                                <span :class="order.side === 'buy' ? 'text-green-500' : 'text-red-500'">
                                    {{ order.side.toUpperCase() }}
                                </span>
                            </td>
                            <td class="py-2 text-right">${{ parseFloat(order.price).toFixed(2) }}</td>
                            <td class="py-2 text-right">{{ parseFloat(order.amount).toFixed(8) }}</td>
                            <td class="py-2 text-right">
                                ${{ (parseFloat(order.price) * parseFloat(order.amount)).toFixed(2) }}
                            </td>
                            <td class="py-2 text-center">
                                <Badge :variant="getStatusVariant(order.status)">
                                    {{ getStatusLabel(order.status) }}
                                </Badge>
                            </td>
                            <td class="py-2 text-right">
                                <Button
                                    v-if="order.status === 1"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="loading"
                                    @click="emit('cancel', order.id)"
                                >
                                    Cancel
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!orders?.length">
                            <td colspan="8" class="py-8 text-center text-muted-foreground">
                                No orders yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
