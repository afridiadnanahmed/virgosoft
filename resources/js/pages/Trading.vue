<script setup lang="ts">
import { onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { useTrading } from '@/composables/useTrading';
import OrderForm from '@/components/trading/OrderForm.vue';
import WalletBalance from '@/components/trading/WalletBalance.vue';
import Orderbook from '@/components/trading/Orderbook.vue';
import OrderHistory from '@/components/trading/OrderHistory.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Trading',
        href: '/trading',
    },
];

const {
    profile,
    userOrders,
    orderbook,
    loading,
    error,
    selectedSymbol,
    fetchProfile,
    fetchUserOrders,
    fetchOrderbook,
    placeOrder,
    cancelOrder,
} = useTrading();

async function handlePlaceOrder(data: {
    symbol: string;
    side: 'buy' | 'sell';
    price: string;
    amount: string;
}) {
    await placeOrder(data);
}

async function handleCancelOrder(orderId: number) {
    await cancelOrder(orderId);
}

// Fetch initial data
onMounted(async () => {
    await Promise.all([
        fetchProfile(),
        fetchUserOrders(),
        fetchOrderbook(selectedSymbol.value),
    ]);
});

// Refetch orderbook when symbol changes
watch(selectedSymbol, (newSymbol) => {
    fetchOrderbook(newSymbol);
});
</script>

<template>
    <Head title="Trading" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Error Alert -->
            <Alert v-if="error" variant="destructive">
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <div class="grid gap-4 lg:grid-cols-12">
                <!-- Left Column: Order Form & Wallet -->
                <div class="lg:col-span-4 space-y-4">
                    <OrderForm
                        :loading="loading"
                        :balance="profile?.balance || '0'"
                        :assets="profile?.assets || []"
                        @submit="handlePlaceOrder"
                    />
                    <WalletBalance
                        :balance="profile?.balance || '0'"
                        :assets="profile?.assets || []"
                    />
                </div>

                <!-- Right Column: Orderbook -->
                <div class="lg:col-span-8">
                    <div class="space-y-4">
                        <!-- Symbol Selector -->
                        <div class="flex gap-2">
                            <button
                                v-for="symbol in ['BTC', 'ETH']"
                                :key="symbol"
                                class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                :class="selectedSymbol === symbol
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted hover:bg-muted/80'"
                                @click="selectedSymbol = symbol"
                            >
                                {{ symbol }}/USD
                            </button>
                        </div>

                        <Orderbook
                            :symbol="selectedSymbol"
                            :buy-orders="orderbook?.buy_orders || []"
                            :sell-orders="orderbook?.sell_orders || []"
                        />
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <OrderHistory
                :orders="userOrders"
                :loading="loading"
                @cancel="handleCancelOrder"
            />
        </div>
    </AppLayout>
</template>
