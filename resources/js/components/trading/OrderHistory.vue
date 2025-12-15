<script setup lang="ts">
import { ref, computed } from 'vue';
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

const props = defineProps<{
    orders: Order[];
    loading: boolean;
}>();

const emit = defineEmits<{
    cancel: [orderId: number];
}>();

// Filters
const filterSymbol = ref<string>('all');
const filterSide = ref<string>('all');
const filterStatus = ref<string>('all');

const filteredOrders = computed(() => {
    return props.orders.filter(order => {
        if (filterSymbol.value !== 'all' && order.symbol !== filterSymbol.value) {
            return false;
        }
        if (filterSide.value !== 'all' && order.side !== filterSide.value) {
            return false;
        }
        if (filterStatus.value !== 'all' && order.status !== parseInt(filterStatus.value)) {
            return false;
        }
        return true;
    });
});

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

function clearFilters() {
    filterSymbol.value = 'all';
    filterSide.value = 'all';
    filterStatus.value = 'all';
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <CardTitle>Order History</CardTitle>
            <Button
                v-if="filterSymbol !== 'all' || filterSide !== 'all' || filterStatus !== 'all'"
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                Clear Filters
            </Button>
        </CardHeader>
        <CardContent>
            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-4 p-3 bg-muted/50 rounded-lg">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground">Symbol:</label>
                    <select
                        v-model="filterSymbol"
                        class="h-8 rounded-md border border-input bg-background px-2 text-sm"
                    >
                        <option value="all">All</option>
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground">Side:</label>
                    <select
                        v-model="filterSide"
                        class="h-8 rounded-md border border-input bg-background px-2 text-sm"
                    >
                        <option value="all">All</option>
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground">Status:</label>
                    <select
                        v-model="filterStatus"
                        class="h-8 rounded-md border border-input bg-background px-2 text-sm"
                    >
                        <option value="all">All</option>
                        <option value="1">Open</option>
                        <option value="2">Filled</option>
                        <option value="3">Cancelled</option>
                    </select>
                </div>
                <div class="flex items-center text-sm text-muted-foreground ml-auto">
                    Showing {{ filteredOrders.length }} of {{ orders.length }} orders
                </div>
            </div>

            <!-- Orders Table -->
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
                            v-for="order in filteredOrders"
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
                        <tr v-if="!filteredOrders?.length">
                            <td colspan="8" class="py-8 text-center text-muted-foreground">
                                {{ orders.length ? 'No orders match the filters' : 'No orders yet' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
