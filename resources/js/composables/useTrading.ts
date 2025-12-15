import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface Asset {
    symbol: string;
    amount: string;
    locked_amount: string;
    available: string;
}

export interface Profile {
    id: number;
    name: string;
    email: string;
    balance: string;
    assets: Asset[];
}

export interface Order {
    id: number;
    user_id: number;
    symbol: string;
    side: 'buy' | 'sell';
    price: string;
    amount: string;
    status: number;
    created_at: string;
}

export interface Trade {
    id: number;
    symbol: string;
    price: string;
    amount: string;
    total: string;
    commission: string;
    side: 'buy' | 'sell';
    created_at: string;
}

export interface OrderbookData {
    symbol: string;
    buy_orders: Order[];
    sell_orders: Order[];
}

export function useTrading(onOrderMatched?: (trade: Trade) => void) {
    const profile = ref<Profile | null>(null);
    const userOrders = ref<Order[]>([]);
    const orderbook = ref<OrderbookData | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const selectedSymbol = ref('BTC');

    const page = usePage();
    const userId = (page.props.auth as { user: { id: number } })?.user?.id;

    async function fetchProfile() {
        try {
            const response = await fetch('/api/profile', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            if (response.ok) {
                profile.value = await response.json();
            }
        } catch (e) {
            console.error('Failed to fetch profile:', e);
        }
    }

    async function fetchUserOrders() {
        try {
            const response = await fetch('/api/user/orders', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            if (response.ok) {
                const data = await response.json();
                userOrders.value = data.orders;
            }
        } catch (e) {
            console.error('Failed to fetch user orders:', e);
        }
    }

    async function fetchOrderbook(symbol: string) {
        try {
            const response = await fetch(`/api/orders?symbol=${symbol}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            if (response.ok) {
                orderbook.value = await response.json();
            }
        } catch (e) {
            console.error('Failed to fetch orderbook:', e);
        }
    }

    async function placeOrder(data: {
        symbol: string;
        side: 'buy' | 'sell';
        price: string;
        amount: string;
    }) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'include',
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (!response.ok) {
                error.value = result.message || 'Failed to place order';
                return null;
            }
            // Refresh data after placing order
            await Promise.all([
                fetchProfile(),
                fetchUserOrders(),
                fetchOrderbook(data.symbol),
            ]);
            return result;
        } catch (e) {
            error.value = 'Failed to place order';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function cancelOrder(orderId: number) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'include',
            });
            const result = await response.json();
            if (!response.ok) {
                error.value = result.message || 'Failed to cancel order';
                return false;
            }
            // Refresh data after canceling order
            await Promise.all([
                fetchProfile(),
                fetchUserOrders(),
                fetchOrderbook(selectedSymbol.value),
            ]);
            return true;
        } catch (e) {
            error.value = 'Failed to cancel order';
            return false;
        } finally {
            loading.value = false;
        }
    }

    function setupEchoListeners() {
        if (!userId || !window.Echo) return;

        window.Echo.private(`private-user.${userId}`)
            .listen('.order.matched', (data: { trade: Trade; order_id: number }) => {
                console.log('Order matched:', data);
                // Refresh all data when an order is matched
                fetchProfile();
                fetchUserOrders();
                fetchOrderbook(selectedSymbol.value);
                // Call the callback if provided
                if (onOrderMatched) {
                    onOrderMatched(data.trade);
                }
            });
    }

    function cleanupEchoListeners() {
        if (!userId || !window.Echo) return;
        window.Echo.leave(`private-user.${userId}`);
    }

    onMounted(() => {
        setupEchoListeners();
    });

    onUnmounted(() => {
        cleanupEchoListeners();
    });

    return {
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
    };
}
