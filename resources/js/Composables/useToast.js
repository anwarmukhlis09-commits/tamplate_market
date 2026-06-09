// Shared toast store — module-level singleton so AdminLayout and any page
// component share the same toast list, even when AdminLayout unmounts on
// page navigation.
import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

// Dedup: avoid showing two identical toasts within a short window.
// This prevents the double-toast scenario where the form's onSuccess
// AND the AdminLayout's flash watcher both fire for the same action.
const recentKeys = new Map(); // key -> expiresAt
const DEDUP_WINDOW_MS = 1500;

function isDuplicate(type, title, message) {
    const key = `${type}|${title}|${message}`;
    const now = Date.now();
    // Lazy GC
    for (const [k, exp] of recentKeys) {
        if (exp < now) recentKeys.delete(k);
    }
    if (recentKeys.has(key)) return true;
    recentKeys.set(key, now + DEDUP_WINDOW_MS);
    return false;
}

function push({ title = '', message = '', type = 'success', duration = 4000 } = {}) {
    if (!title && !message) return null;
    if (isDuplicate(type, title, message)) return null;

    const id = ++toastId;
    toasts.value.push({ id, title, message, type });

    if (duration > 0) {
        setTimeout(() => dismiss(id), duration);
    }
    return id;
}

function dismiss(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

function success(title, message, duration = 4000) {
    return push({ title, message, type: 'success', duration });
}
function error(title, message, duration = 4500) {
    return push({ title, message, type: 'error', duration });
}
function info(title, message, duration = 4000) {
    return push({ title, message, type: 'info', duration });
}

export function useToast() {
    return { toasts, push, dismiss, success, error, info };
}
