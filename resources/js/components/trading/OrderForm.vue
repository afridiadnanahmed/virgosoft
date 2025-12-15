<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const COMMISSION_RATE = 0.015; // 1.5%

const props = defineProps<{
    loading: boolean;
    balance: string;
    assets: { symbol: string; available: string }[];
}>();

const emit = defineEmits<{
    submit: [data: { symbol: string; side: 'buy' | 'sell'; price: string; amount: string }];
}>();

const symbol = ref('BTC');
const side = ref<'buy' | 'sell'>('buy');
const price = ref('');
const amount = ref('');

const symbols = ['BTC', 'ETH'];

const totalValue = computed(() => {
    const p = parseFloat(price.value) || 0;
    const a = parseFloat(amount.value) || 0;
    return p * a;
});

const commission = computed(() => {
    return totalValue.value * COMMISSION_RATE;
});

const netValue = computed(() => {
    if (side.value === 'buy') {
        // Buyer pays total (commission deducted from seller)
        return totalValue.value;
    } else {
        // Seller receives total minus commission
        return totalValue.value - commission.value;
    }
});

const availableBalance = computed(() => {
    if (side.value === 'buy') {
        return parseFloat(props.balance || '0');
    }
    const asset = props.assets?.find(a => a.symbol === symbol.value);
    return parseFloat(asset?.available || '0');
});

const availableBalanceFormatted = computed(() => {
    if (side.value === 'buy') {
        return `$${availableBalance.value.toFixed(2)} USD`;
    }
    return `${availableBalance.value.toFixed(8)} ${symbol.value}`;
});

const hasSufficientBalance = computed(() => {
    if (!price.value || !amount.value) return true;
    if (side.value === 'buy') {
        return availableBalance.value >= totalValue.value;
    }
    return availableBalance.value >= parseFloat(amount.value);
});

function handleSubmit() {
    if (!price.value || !amount.value) return;
    emit('submit', {
        symbol: symbol.value,
        side: side.value,
        price: price.value,
        amount: amount.value,
    });
    // Reset form
    price.value = '';
    amount.value = '';
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Place Order</CardTitle>
            <CardDescription>Create a new limit order</CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div class="space-y-2">
                    <Label>Symbol</Label>
                    <select
                        v-model="symbol"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option v-for="s in symbols" :key="s" :value="s">
                            {{ s }}
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label>Side</Label>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            :variant="side === 'buy' ? 'default' : 'outline'"
                            class="flex-1"
                            :class="side === 'buy' ? 'bg-green-600 hover:bg-green-700' : ''"
                            @click="side = 'buy'"
                        >
                            Buy
                        </Button>
                        <Button
                            type="button"
                            :variant="side === 'sell' ? 'default' : 'outline'"
                            class="flex-1"
                            :class="side === 'sell' ? 'bg-red-600 hover:bg-red-700' : ''"
                            @click="side = 'sell'"
                        >
                            Sell
                        </Button>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="price">Price (USD)</Label>
                    <Input
                        id="price"
                        v-model="price"
                        type="number"
                        step="0.00000001"
                        min="0"
                        placeholder="0.00"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="amount">Amount ({{ symbol }})</Label>
                    <Input
                        id="amount"
                        v-model="amount"
                        type="number"
                        step="0.00000001"
                        min="0"
                        placeholder="0.00000000"
                        required
                    />
                </div>

                <!-- Volume Calculation Preview -->
                <div class="rounded-md bg-muted p-3 text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Volume:</span>
                        <span class="font-medium">${{ totalValue.toFixed(2) }} USD</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Commission (1.5%):</span>
                        <span class="font-medium text-orange-500">${{ commission.toFixed(2) }} USD</span>
                    </div>
                    <div class="flex justify-between border-t border-border pt-2">
                        <span class="text-muted-foreground">
                            {{ side === 'buy' ? 'You Pay:' : 'You Receive:' }}
                        </span>
                        <span class="font-bold">
                            ${{ side === 'buy' ? totalValue.toFixed(2) : netValue.toFixed(2) }} USD
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Available:</span>
                        <span
                            class="font-medium"
                            :class="hasSufficientBalance ? 'text-green-500' : 'text-red-500'"
                        >
                            {{ availableBalanceFormatted }}
                        </span>
                    </div>
                    <div v-if="!hasSufficientBalance && (price && amount)" class="text-xs text-red-500 mt-1">
                        Insufficient {{ side === 'buy' ? 'USD balance' : `${symbol} balance` }}
                    </div>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :class="side === 'buy' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                    :disabled="loading || !price || !amount || !hasSufficientBalance"
                >
                    {{ loading ? 'Processing...' : `${side === 'buy' ? 'Buy' : 'Sell'} ${symbol}` }}
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
