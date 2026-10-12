<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const isAdmin = computed(() => !!page.props?.auth?.isAdmin || !!page.props?.auth?.user?.is_admin);

// ── Widget state ──
const isOpen = ref(false);
const messagesContainer = ref(null);

// ═════════════════════════════════════════════════════════════
// ADMIN MODE STATES (WhatsApp Style Inbox)
// ═════════════════════════════════════════════════════════════
const adminView = ref('list'); // 'list' | 'room'
const adminSessions = ref([]);
const adminSearch = ref('');
const adminActiveSessionId = ref(null);
const adminActiveSession = ref(null);
const adminMessages = ref([]);
const adminNewMessage = ref('');
const adminIsSending = ref(false);
const adminTotalUnread = ref(0);
const adminLoadingMessages = ref(false);

const adminQuickReplies = [
    'Halo kak! Ada yang bisa kami bantu?',
    'Untuk pasang di MikroTik, cukup ekstrak ZIP lalu drag folder ke menu Files di Winbox ya.',
    'Bisa tolong infokan nomor Order ID pesanannya kak?',
    'Tampilan di HP belum berubah? Coba logout di http://192.168.88.1/logout ya.',
    'Baik kak, terima kasih sudah menghubungi tim support kami!',
];

const filteredAdminSessions = computed(() => {
    if (!adminSearch.value.trim()) return adminSessions.value;
    const q = adminSearch.value.toLowerCase();
    return adminSessions.value.filter(s => {
        const nameMatch = (s.customer_name || '').toLowerCase().includes(q);
        const msgMatch = (s.last_message || '').toLowerCase().includes(q);
        return nameMatch || msgMatch;
    });
});

async function fetchAdminSessions() {
    if (!isAdmin.value) return;
    try {
        const res = await axios.get('/admin/chats/api/sessions');
        if (res.data?.ok) {
            adminSessions.value = res.data.sessions || [];
            adminTotalUnread.value = res.data.totalUnread || 0;
        }
    } catch (e) {}
}

async function selectAdminSession(session) {
    adminActiveSessionId.value = session.id;
    adminActiveSession.value = session;
    adminView.value = 'room';
    adminLoadingMessages.value = true;

    try {
        const res = await axios.get(`/admin/chats/api/${session.id}/messages`);
        if (res.data?.ok) {
            adminMessages.value = res.data.messages || [];
            adminActiveSession.value = res.data.session;

            // Update unread local count
            const idx = adminSessions.value.findIndex(s => s.id === session.id);
            if (idx !== -1) {
                adminSessions.value[idx].unread_admin_count = 0;
            }
            scrollToBottom();
        }
    } catch (e) {
        console.error('Failed to load admin session messages', e);
    } finally {
        adminLoadingMessages.value = false;
    }
}

async function fetchAdminActiveMessages() {
    if (!adminActiveSessionId.value || adminView.value !== 'room') return;
    try {
        const res = await axios.get(`/admin/chats/api/${adminActiveSessionId.value}/messages`);
        if (res.data?.ok) {
            const prevLen = adminMessages.value.length;
            adminMessages.value = res.data.messages || [];
            if (adminMessages.value.length > prevLen) {
                scrollToBottom();
            }
        }
    } catch (e) {}
}

async function sendAdminMessage(textToSend = null) {
    const text = (textToSend || adminNewMessage.value).trim();
    if (!text || !adminActiveSessionId.value || adminIsSending.value) return;

    adminIsSending.value = true;
    if (!textToSend) {
        adminNewMessage.value = '';
    }

    // Optimistic UI insert
    const tempId = 'temp_' + Date.now();
    adminMessages.value.push({
        id: tempId,
        sender_type: 'admin',
        sender_name: 'Admin',
        message: text,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        is_read: false,
    });
    scrollToBottom();

    try {
        const res = await axios.post(`/admin/chats/api/${adminActiveSessionId.value}/send`, {
            message: text,
        });
        if (res.data?.ok) {
            const idx = adminMessages.value.findIndex(m => m.id === tempId);
            if (idx !== -1) {
                adminMessages.value[idx] = res.data.message;
            }
            fetchAdminSessions();
        }
    } catch (e) {
        console.error('Failed to send admin message', e);
    } finally {
        adminIsSending.value = false;
        scrollToBottom();
    }
}

async function toggleAdminSessionStatus() {
    if (!adminActiveSessionId.value) return;
    try {
        const res = await axios.post(`/admin/chats/api/${adminActiveSessionId.value}/toggle-status`);
        if (res.data?.ok) {
            adminActiveSession.value.status = res.data.status;
            fetchAdminActiveMessages();
            fetchAdminSessions();
        }
    } catch (e) {}
}

function backToAdminList() {
    adminView.value = 'list';
    adminActiveSessionId.value = null;
    adminActiveSession.value = null;
    fetchAdminSessions();
}

// ═════════════════════════════════════════════════════════════
// CUSTOMER MODE STATES (1-on-1 Support Chat)
// ═════════════════════════════════════════════════════════════
const showGreetingBubble = ref(false);
const customerSessionToken = ref(localStorage.getItem('hotspot_chat_session') || '');
const customerName = ref(localStorage.getItem('hotspot_chat_name') || '');
const customerMessages = ref([]);
const customerNewMessage = ref('');
const customerIsSending = ref(false);
const customerIsLoading = ref(false);
const customerUnreadCount = ref(0);

const customerQuickSuggestions = [
    'Cara pasang template di Winbox?',
    'Rekomendasi template untuk Cafe',
    'Bisa edit warna dan logo sendiri?',
    'Bagaimana cara bayar via QRIS?',
];

async function initCustomerChat() {
    customerIsLoading.value = true;
    try {
        const res = await axios.post('/api/chat/init', {
            session_token: customerSessionToken.value,
            customer_name: customerName.value || null,
        });

        if (res.data?.ok) {
            customerSessionToken.value = res.data.session.session_token;
            localStorage.setItem('hotspot_chat_session', res.data.session.session_token);
            if (res.data.session.customer_name) {
                customerName.value = res.data.session.customer_name;
                localStorage.setItem('hotspot_chat_name', res.data.session.customer_name);
            }
            customerMessages.value = res.data.messages || [];
            customerUnreadCount.value = 0;
            scrollToBottom();
        }
    } catch (e) {
        console.error('Failed to init customer chat', e);
    } finally {
        customerIsLoading.value = false;
    }
}

async function fetchCustomerMessages(markRead = true) {
    if (!customerSessionToken.value) return;

    try {
        const res = await axios.get('/api/chat/messages', {
            params: {
                session_token: customerSessionToken.value,
                mark_read: markRead && isOpen.value ? 1 : 0,
            },
        });

        if (res.data?.ok) {
            const prevLen = customerMessages.value.length;
            customerMessages.value = res.data.messages || [];

            if (!isOpen.value) {
                customerUnreadCount.value = res.data.unread_customer_count || 0;
            } else {
                customerUnreadCount.value = 0;
                if (customerMessages.value.length > prevLen) {
                    scrollToBottom();
                }
            }
        }
    } catch (e) {}
}

async function sendCustomerMessage(textToSend = null) {
    const text = (textToSend || customerNewMessage.value).trim();
    if (!text || customerIsSending.value) return;

    if (!customerSessionToken.value) {
        await initCustomerChat();
    }

    customerIsSending.value = true;
    if (!textToSend) {
        customerNewMessage.value = '';
    }

    const tempId = 'temp_' + Date.now();
    customerMessages.value.push({
        id: tempId,
        sender_type: 'customer',
        sender_name: customerName.value || 'Anda',
        message: text,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        is_read: false,
    });
    scrollToBottom();

    try {
        const res = await axios.post('/api/chat/send', {
            session_token: customerSessionToken.value,
            message: text,
            customer_name: customerName.value || null,
        });

        if (res.data?.ok) {
            const idx = customerMessages.value.findIndex(m => m.id === tempId);
            if (idx !== -1) {
                customerMessages.value[idx] = res.data.message;
            }
        }
    } catch (e) {
        console.error('Failed to send customer message', e);
    } finally {
        customerIsSending.value = false;
        scrollToBottom();
    }
}

// ═════════════════════════════════════════════════════════════
// SHARED WIDGET CONTROLS
// ═════════════════════════════════════════════════════════════
let pollInterval = null;

function toggleChat() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        showGreetingBubble.value = false;
        if (isAdmin.value) {
            fetchAdminSessions();
        } else {
            customerUnreadCount.value = 0;
            if (!customerSessionToken.value || customerMessages.value.length === 0) {
                initCustomerChat();
            } else {
                fetchCustomerMessages(true);
            }
        }
        scrollToBottom();
    }
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

function handleAdminKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendAdminMessage();
    }
}

function handleCustomerKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendCustomerMessage();
    }
}

watch(isOpen, (opened) => {
    if (opened) {
        startFastPolling();
    } else {
        startSlowPolling();
    }
});

function startFastPolling() {
    stopPolling();
    pollInterval = setInterval(() => {
        if (isAdmin.value) {
            fetchAdminSessions();
            if (adminView.value === 'room') {
                fetchAdminActiveMessages();
            }
        } else {
            fetchCustomerMessages(true);
        }
    }, 3500);
}

function startSlowPolling() {
    stopPolling();
    pollInterval = setInterval(() => {
        if (isAdmin.value) {
            fetchAdminSessions();
        } else {
            fetchCustomerMessages(false);
        }
    }, 15000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

onMounted(() => {
    if (isAdmin.value) {
        fetchAdminSessions();
    } else {
        // Customer greeting bubble
        setTimeout(() => {
            if (!isOpen.value && customerMessages.value.length === 0) {
                showGreetingBubble.value = true;
            }
        }, 4000);

        if (customerSessionToken.value) {
            fetchCustomerMessages(false);
        }
    }

    startSlowPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
<div class="fixed bottom-6 right-6 z-50 font-sans select-none print:hidden">

    <!-- ═══ GREETING POPOVER (Khusus Customer sebelum widget dibuka) ═══ -->
    <transition
        enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="opacity-0 translate-y-3 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in transform"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95"
    >
        <div 
            v-if="!isAdmin && !isOpen && showGreetingBubble" 
            @click="toggleChat"
            class="mb-3 max-w-xs bg-white rounded-2xl p-3.5 shadow-2xl border border-slate-200/80 cursor-pointer hover:border-indigo-300 transition-all group relative"
        >
            <button 
                type="button" 
                @click.stop="showGreetingBubble = false" 
                class="absolute -top-2 -right-2 w-5 h-5 bg-slate-200 hover:bg-slate-300 text-slate-600 rounded-full flex items-center justify-center text-[10px] font-bold"
            >
                ✕
            </button>
            <div class="flex items-start gap-2.5">
                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                    💬
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <span>Customer Support</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                        Halo! Ada yang bisa kami bantu seputar template MikroTik? Chat kami di sini! 👋
                    </p>
                </div>
            </div>
        </div>
    </transition>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- CHAT POPUP WINDOW CONTAINER -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <transition
        enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="opacity-0 translate-y-6 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in transform"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-6 scale-95"
    >
        <div 
            v-show="isOpen"
            class="w-[370px] sm:w-[410px] h-[560px] max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/90 flex flex-col overflow-hidden mb-3"
        >

            <!-- ═════════════════════════════════════════════════════ -->
            <!-- 👑 JIKA LOGIN SEBAGAI ADMIN (MODE WHATSAPP INBOX) -->
            <!-- ═════════════════════════════════════════════════════ -->
            <template v-if="isAdmin">

                <!-- ── VIEW 1: DAFTAR CHAT SEMUA USER (SEPERTI WHATSAPP) ── -->
                <div v-show="adminView === 'list'" class="flex-1 flex flex-col h-full bg-slate-50">
                    <!-- WhatsApp Style Header -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-4 text-white shrink-0 flex items-center justify-between border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-sm">
                                💬
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-white flex items-center gap-1.5">
                                    Inbox Chat Pelanggan
                                    <span v-if="adminTotalUnread > 0" class="px-2 py-0.2 bg-rose-500 text-white text-[10px] font-black rounded-full animate-pulse">
                                        {{ adminTotalUnread }} baru
                                    </span>
                                </h3>
                                <p class="text-[10px] text-slate-300">Pilih pelanggan untuk membalas</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <Link href="/admin/chats" class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-slate-200 text-[10px] font-bold transition-colors" title="Buka Console Penuh">
                                Full View ↗
                            </Link>
                            <button 
                                type="button"
                                @click="toggleChat"
                                class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition-colors"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Search bar -->
                    <div class="p-3 bg-white border-b border-slate-200/80 shrink-0">
                        <div class="relative">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input 
                                v-model="adminSearch"
                                type="text"
                                placeholder="Cari nama atau pesan..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-100 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none"
                            />
                        </div>
                    </div>

                    <!-- List of Conversations -->
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-100 bg-white">
                        <div v-if="filteredAdminSessions.length === 0" class="p-8 text-center text-xs text-slate-400">
                            Belum ada pesan yang masuk.
                        </div>

                        <button
                            v-for="s in filteredAdminSessions"
                            :key="s.id"
                            type="button"
                            @click="selectAdminSession(s)"
                            class="w-full p-3.5 text-left flex items-start gap-3 hover:bg-slate-50 transition-colors relative group"
                        >
                            <!-- Avatar -->
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold text-sm flex items-center justify-center shadow-xs">
                                    {{ s.customer_name ? s.customer_name.charAt(0).toUpperCase() : 'P' }}
                                </div>
                                <span 
                                    class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                    :class="s.status === 'active' ? 'bg-emerald-500' : 'bg-slate-300'"
                                ></span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">
                                        {{ s.customer_name }}
                                    </h4>
                                    <span class="text-[10px] text-slate-400 shrink-0">
                                        {{ s.last_message_time }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 mb-1">
                                    <span v-if="s.is_member" class="px-1 py-0.1 bg-indigo-50 text-indigo-700 text-[8px] font-bold rounded">
                                        Member
                                    </span>
                                    <span v-else class="px-1 py-0.1 bg-slate-100 text-slate-600 text-[8px] font-semibold rounded">
                                        Tamu
                                    </span>
                                </div>

                                <p class="text-[11px] text-slate-500 truncate leading-snug">
                                    {{ s.last_message || 'Belum ada pesan' }}
                                </p>
                            </div>

                            <!-- Unread badge -->
                            <span 
                                v-if="s.unread_admin_count > 0" 
                                class="px-2 py-0.5 text-[9px] font-black bg-rose-500 text-white rounded-full shrink-0 animate-pulse shadow-sm"
                            >
                                {{ s.unread_admin_count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- ── VIEW 2: RUANG BALAS CHAT USER TERTENTU ── -->
                <div v-show="adminView === 'room'" class="flex-1 flex flex-col h-full bg-slate-50">
                    <!-- WhatsApp Style Room Header with Back Button -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-3.5 text-white shrink-0 flex items-center justify-between border-b border-slate-800">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <!-- Back Button -->
                            <button
                                type="button"
                                @click="backToAdminList"
                                class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors shrink-0"
                                title="Kembali ke Daftar Chat"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <!-- User Info -->
                            <div class="relative shrink-0">
                                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-extrabold flex items-center justify-center text-xs shadow-xs">
                                    {{ adminActiveSession?.customer_name?.charAt(0).toUpperCase() || 'P' }}
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border border-slate-900"></span>
                            </div>

                            <div class="min-w-0">
                                <h3 class="text-xs font-extrabold text-white truncate flex items-center gap-1.5">
                                    {{ adminActiveSession?.customer_name }}
                                </h3>
                                <p class="text-[10px] text-slate-300 truncate">
                                    {{ adminActiveSession?.status === 'active' ? 'Aktif' : 'Selesai' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <button 
                                type="button"
                                @click="toggleAdminSessionStatus"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors"
                                :class="adminActiveSession?.status === 'active' ? 'bg-white/10 text-slate-200 border-white/20 hover:bg-white/20' : 'bg-emerald-500 text-white border-transparent'"
                            >
                                {{ adminActiveSession?.status === 'active' ? 'Selesai' : 'Aktifkan' }}
                            </button>
                            <button 
                                type="button"
                                @click="toggleChat"
                                class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition-colors"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Messages scroll area -->
                    <div 
                        ref="messagesContainer"
                        class="flex-1 p-3.5 overflow-y-auto space-y-3 bg-slate-50 select-text"
                    >
                        <div v-if="adminLoadingMessages" class="flex justify-center py-8">
                            <span class="text-xs text-slate-400">Memuat pesan...</span>
                        </div>

                        <template v-else>
                            <div v-for="m in adminMessages" :key="m.id" class="flex flex-col">
                                <!-- System Message -->
                                <div v-if="m.sender_type === 'system'" class="my-1.5 text-center">
                                    <span class="inline-block px-3 py-1 bg-slate-200 text-slate-600 rounded-full text-[10px] font-medium max-w-[85%]">
                                        {{ m.message }}
                                    </span>
                                </div>

                                <!-- Customer Message (Left) -->
                                <div v-else-if="m.sender_type === 'customer'" class="flex flex-col items-start max-w-[85%]">
                                    <div class="bg-white text-slate-800 border border-slate-200/90 rounded-2xl rounded-tl-xs px-3 py-2 shadow-xs text-xs leading-relaxed break-words">
                                        {{ m.message }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 mt-0.5 ml-1">{{ m.time }}</div>
                                </div>

                                <!-- Admin Message (Right) -->
                                <div v-else-if="m.sender_type === 'admin'" class="flex flex-col items-end">
                                    <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-tr-xs px-3 py-2 shadow-xs text-xs leading-relaxed break-words">
                                        {{ m.message }}
                                    </div>
                                    <div class="flex items-center gap-1 text-[9px] text-slate-400 mt-0.5 mr-1">
                                        <span>{{ m.time }}</span>
                                        <span v-if="m.is_read" class="text-indigo-600 font-bold">✓✓</span>
                                        <span v-else class="text-slate-400">✓</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Quick Replies -->
                    <div class="px-3 py-1.5 bg-white border-t border-slate-100 flex items-center gap-1 overflow-x-auto no-scrollbar shrink-0">
                        <button
                            v-for="(qr, idx) in adminQuickReplies"
                            :key="idx"
                            type="button"
                            @click="sendAdminMessage(qr)"
                            class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 rounded-lg text-[9px] font-semibold whitespace-nowrap transition-colors border border-slate-200/60 shrink-0"
                        >
                            {{ qr }}
                        </button>
                    </div>

                    <!-- Admin Input -->
                    <div class="p-3 bg-white border-t border-slate-200 shrink-0">
                        <form @submit.prevent="sendAdminMessage()" class="flex items-center gap-2">
                            <input 
                                v-model="adminNewMessage"
                                type="text"
                                placeholder="Balas pelanggan... (Enter)"
                                :disabled="adminIsSending"
                                @keydown="handleAdminKeydown"
                                class="flex-1 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none"
                            />
                            <button 
                                type="submit"
                                :disabled="!adminNewMessage.trim() || adminIsSending"
                                class="w-9 h-9 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white flex items-center justify-center transition-colors shadow-md shadow-indigo-100 shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

            </template>

            <!-- ═════════════════════════════════════════════════════ -->
            <!-- 👤 JIKA PENGUNJUNG / CUSTOMER BIASA (1-ON-1 CHAT) -->
            <!-- ═════════════════════════════════════════════════════ -->
            <template v-else>
                <!-- Customer Header -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-4 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-base font-extrabold shadow-md">
                                CS
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-slate-900"></span>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-white flex items-center gap-1.5">
                                Customer Support
                                <span class="text-[9px] px-1.5 py-0.2 bg-emerald-500/20 text-emerald-300 rounded font-semibold">Online</span>
                            </h3>
                            <p class="text-[11px] text-slate-300">Respon dalam beberapa menit</p>
                        </div>
                    </div>

                    <button 
                        type="button"
                        @click="toggleChat"
                        class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition-colors"
                    >
                        ✕
                    </button>
                </div>

                <!-- Messages area -->
                <div 
                    ref="messagesContainer"
                    class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/60 select-text"
                >
                    <div v-if="customerIsLoading" class="flex justify-center py-8">
                        <span class="text-xs text-slate-400">Menghubungkan ke admin...</span>
                    </div>

                    <template v-else>
                        <div v-for="msg in customerMessages" :key="msg.id" class="flex flex-col">
                            <!-- System message -->
                            <div v-if="msg.sender_type === 'system'" class="my-2 text-center">
                                <div class="inline-block bg-indigo-50 border border-indigo-100 rounded-2xl px-3.5 py-2 text-xs text-indigo-900 max-w-[90%] shadow-xs text-left">
                                    <div class="font-bold text-indigo-700 text-[11px] mb-0.5">🤖 Support Bot</div>
                                    <div class="text-[11px] leading-relaxed">{{ msg.message }}</div>
                                </div>
                            </div>

                            <!-- Customer message (right) -->
                            <div v-else-if="msg.sender_type === 'customer'" class="flex flex-col items-end">
                                <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 shadow-sm text-xs leading-relaxed break-words">
                                    {{ msg.message }}
                                </div>
                                <div class="text-[9px] text-slate-400 mt-1 mr-1">{{ msg.time }}</div>
                            </div>

                            <!-- Admin message (left) -->
                            <div v-else-if="msg.sender_type === 'admin'" class="flex items-start gap-2 max-w-[88%]">
                                <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">
                                    CS
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 mb-0.5 ml-1">{{ msg.sender_name || 'Admin Support' }}</div>
                                    <div class="bg-white text-slate-800 border border-slate-200/80 rounded-2xl rounded-tl-xs px-3.5 py-2.5 shadow-sm text-xs leading-relaxed break-words">
                                        {{ msg.message }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 mt-1 ml-1">{{ msg.time }}</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Suggestions -->
                <div class="px-3 py-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
                    <button
                        v-for="(chip, idx) in customerQuickSuggestions"
                        :key="idx"
                        type="button"
                        @click="sendCustomerMessage(chip)"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 rounded-lg text-[10px] font-semibold whitespace-nowrap transition-colors border border-slate-200/60 shrink-0"
                    >
                        {{ chip }}
                    </button>
                </div>

                <!-- Input footer -->
                <div class="p-3 bg-white border-t border-slate-200 shrink-0">
                    <form @submit.prevent="sendCustomerMessage()" class="flex items-center gap-2">
                        <input 
                            v-model="customerNewMessage"
                            type="text" 
                            placeholder="Ketik pesan Anda..." 
                            :disabled="customerIsSending"
                            @keydown="handleCustomerKeydown"
                            class="flex-1 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 outline-none"
                        />
                        <button 
                            type="submit"
                            :disabled="!customerNewMessage.trim() || customerIsSending"
                            class="w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white flex items-center justify-center transition-colors shadow-md shadow-indigo-100 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </div>
            </template>

        </div>
    </transition>

    <!-- ═══ FLOATING LAUNCHER BUTTON ═══ -->
    <div class="flex items-center justify-end">
        <button
            type="button"
            @click="toggleChat"
            class="group relative w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 hover:from-indigo-500 hover:to-violet-600 text-white shadow-xl shadow-indigo-500/30 flex items-center justify-center transition-all duration-300 hover:scale-105 active:scale-95"
            aria-label="Buka Live Chat"
        >
            <!-- Badge count -->
            <span 
                v-if="!isOpen && (isAdmin ? adminTotalUnread > 0 : customerUnreadCount > 0)" 
                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-500 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center border-2 border-white animate-bounce shadow-md"
            >
                {{ (isAdmin ? adminTotalUnread : customerUnreadCount) > 9 ? '9+' : (isAdmin ? adminTotalUnread : customerUnreadCount) }}
            </span>

            <!-- Close icon when open -->
            <svg 
                v-if="isOpen" 
                class="w-6 h-6 transition-transform duration-200" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>

            <!-- Chat icon when closed -->
            <svg 
                v-else 
                class="w-6 h-6 transition-transform duration-200 group-hover:scale-110" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </button>
    </div>

</div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
