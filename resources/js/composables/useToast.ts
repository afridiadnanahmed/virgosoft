import { ref } from 'vue';

export interface Toast {
    id: number;
    type: 'success' | 'error' | 'info' | 'warning';
    title: string;
    message?: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let toastId = 0;

export function useToast() {
    function addToast(toast: Omit<Toast, 'id'>) {
        const id = ++toastId;
        const newToast: Toast = {
            ...toast,
            id,
            duration: toast.duration ?? 5000,
        };
        toasts.value.push(newToast);

        if (newToast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, newToast.duration);
        }

        return id;
    }

    function removeToast(id: number) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function success(title: string, message?: string) {
        return addToast({ type: 'success', title, message });
    }

    function error(title: string, message?: string) {
        return addToast({ type: 'error', title, message });
    }

    function info(title: string, message?: string) {
        return addToast({ type: 'info', title, message });
    }

    function warning(title: string, message?: string) {
        return addToast({ type: 'warning', title, message });
    }

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
        warning,
    };
}
