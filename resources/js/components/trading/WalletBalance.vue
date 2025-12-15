<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

defineProps<{
    balance: string;
    assets: {
        symbol: string;
        amount: string;
        locked_amount: string;
        available: string;
    }[];
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Wallet</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="flex items-center justify-between p-3 rounded-md bg-muted">
                <div>
                    <div class="text-sm text-muted-foreground">USD Balance</div>
                    <div class="text-2xl font-bold">${{ parseFloat(balance || '0').toFixed(2) }}</div>
                </div>
                <Badge variant="secondary">USD</Badge>
            </div>

            <div v-if="assets && assets.length > 0" class="space-y-2">
                <div class="text-sm font-medium text-muted-foreground">Assets</div>
                <div
                    v-for="asset in assets"
                    :key="asset.symbol"
                    class="flex items-center justify-between p-3 rounded-md bg-muted"
                >
                    <div>
                        <div class="font-medium">{{ asset.symbol }}</div>
                        <div class="text-sm text-muted-foreground">
                            Available: {{ parseFloat(asset.available).toFixed(8) }}
                        </div>
                        <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-orange-500">
                            Locked: {{ parseFloat(asset.locked_amount).toFixed(8) }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold">{{ parseFloat(asset.amount).toFixed(8) }}</div>
                        <Badge variant="outline">{{ asset.symbol }}</Badge>
                    </div>
                </div>
            </div>

            <div v-else class="text-sm text-muted-foreground text-center py-4">
                No assets yet
            </div>
        </CardContent>
    </Card>
</template>
