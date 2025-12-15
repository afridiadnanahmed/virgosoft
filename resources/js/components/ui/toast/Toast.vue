<script setup lang="ts">
import { computed } from 'vue';
import { X, CheckCircle, XCircle, AlertCircle, Info } from 'lucide-vue-next';

const props = defineProps<{
    type: 'success' | 'error' | 'info' | 'warning';
    title: string;
    message?: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const icon = computed(() => {
    switch (props.type) {
        case 'success': return CheckCircle;
        case 'error': return XCircle;
        case 'warning': return AlertCircle;
        case 'info': return Info;
    }
});

const colorClasses = computed(() => {
    switch (props.type) {
        case 'success': return 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950 dark:border-green-800 dark:text-green-200';
        case 'error': return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950 dark:border-red-800 dark:text-red-200';
        case 'warning': return 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-800 dark:text-yellow-200';
        case 'info': return 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-200';
    }
});

const iconColorClass = computed(() => {
    switch (props.type) {
        case 'success': return 'text-green-500';
        case 'error': return 'text-red-500';
        case 'warning': return 'text-yellow-500';
        case 'info': return 'text-blue-500';
    }
});
</script>

<template>
    <div
        class="pointer-events-auto w-full max-w-sm rounded-lg border shadow-lg transition-all"
        :class="colorClasses"
    >
        <div class="flex items-start gap-3 p-4">
            <component :is="icon" class="h-5 w-5 shrink-0 mt-0.5" :class="iconColorClass" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium">{{ title }}</p>
                <p v-if="message" class="mt-1 text-sm opacity-80">{{ message }}</p>
            </div>
            <button
                class="shrink-0 rounded-md p-1 opacity-70 hover:opacity-100 transition-opacity"
                @click="emit('close')"
            >
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
