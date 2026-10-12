<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    initialSessions: { type: Array, default: () => [] },
    totalUnread: { type: Number, default: 0 },
});

const sessions = ref([...props.initialSessions]);
const activeSessionId = ref(null);
const activeSession = ref(null);
const messages = ref([]);
const newMessage = ref('');
const isSending = ref(false);
const isLoadingMessages = ref(false);
const searchQuery = ref('');
const activeFilter = ref('all'); // 'all' | 'unread' | 'active' | 'closed'
const messagesContainer = ref(null);

const quickReplies = [
    'Halo kak! Ada yang bisa kami bantu?',
    'Untuk pasang di MikroTik, cukup ekstrak ZIP lalu drag folder ke menu Files di Winbox ya.',
    'Bisa tolong infokan nomor Order ID pesanannya kak?',
    'Tampilan login di HP belum berubah? Coba ketik http://192.168.88.1/logout atau lupakan jaringan Wi-Fi ya kak.',
    'Baik kak, terima kasih sudah menghubungi tim support kami!',
];

const filteredSessions = computed(() => {
    return sessions.value.filter(s => {
        // Filter tabs
        if (activeFilter.value === 'unread' && s.unread_admin_count === 0) return false;
        if (activeFilter.value === 'active' && s.status !== 'active') return false;
        if (activeFilter.value === 'closed' && s.status !== 'closed') return false;

        // Search query
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            const nameMatch = (s.customer_name || '').toLowerCase().includes(q);
            const emailMatch = (s.customer_email || '').toLowerCase().includes(q);
            const msgMatch = (s.last_message || '').toLowerCase().includes(q);
            return nameMatch || emailMatch || msgMatch;
        }

        return true;
    });
});

async function selectSession(session) {
    activeSessionId.value = session.id;
    activeSession.value = session;
    isLoadingMessages.value = true;

    try {
        const res = await axios.get(`/admin/chats/api/${session.id}/messages`);
        if (res.data?.ok) {
            messages.value = res.data.messages || [];
            activeSession.value = res.data.session;
            
            // Update unread count in local list
            const idx = sessions.value.findIndex(s => s.id === session.id);
            if (idx !== -1) {
                sessions.value[idx].unread_admin_count = 0;
            }
            scrollToBottom();
        }
    } catch (e) {
        console.error('Failed to load session messages', e);
    } finally {
        isLoadingMessages.value = false;
    }
}

async function fetchActiveMessages() {
    if (!activeSessionId.value) return;

    try {
        const res = await axios.get(`/admin/chats/api/${activeSessionId.value}/messages`);
        if (res.data?.ok) {
            const prevLen = messages.value.length;
            messages.value = res.data.messages || [];
            if (messages.value.length > prevLen) {
                scrollToBottom();
            }
        }
    } catch (e) {}
}

async function fetchSessionsList() {
    try {
        const res = await axios.get('/admin/chats/api/sessions');
        if (res.data?.ok) {
            sessions.value = res.data.sessions || [];
        }
    } catch (e) {}
}

async function sendMessage(textToSend = null) {
    const text = (textToSend || newMessage.value).trim();
    if (!text || !activeSessionId.value || isSending.value) return;

    isSending.value = true;
    if (!textToSend) {
        newMessage.value = '';
    }

    // Optimistic UI
    const tempId = 'temp_' + Date.now();
    messages.value.push({
        id: tempId,
        sender_type: 'admin',
        sender_name: 'Admin',
        message: text,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        is_read: false,
    });
    scrollToBottom();

    try {
        const res = await axios.post(`/admin/chats/api/${activeSessionId.value}/send`, {
            message: text,
        });

        if (res.data?.ok) {
            const idx = messages.value.findIndex(m => m.id === tempId);
            if (idx !== -1) {
                messages.value[idx] = res.data.message;
            }
            fetchSessionsList();
        }
    } catch (e) {
        console.error('Failed to send admin message', e);
    } finally {
        isSending.value = false;
        scrollToBottom();
    }
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

async function toggleSessionStatus() {
    if (!activeSessionId.value) return;

    try {
        const res = await axios.post(`/admin/chats/api/${activeSessionId.value}/toggle-status`);
        if (res.data?.ok) {
            activeSession.value.status = res.data.status;
            const idx = sessions.value.findIndex(s => s.id === activeSessionId.value);
            if (idx !== -1) {
                sessions.value[idx].status = res.data.status;
            }
            fetchActiveMessages();
        }
    } catch (e) {
        console.error('Failed to toggle status', e);
    }
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

let pollTimer = null;
onMounted(() => {
    // If sessions exist, auto-select the first one with unread or most recent
    if (sessions.value.length > 0) {
        const firstUnread = sessions.value.find(s => s.unread_admin_count > 0) || sessions.value[0];
        selectSession(firstUnread);
    }

    // Setup polling every 3.5 seconds
    pollTimer = setInterval(() => {
        fetchSessionsList();
        if (activeSessionId.value) {
            fetchActiveMessages();
        }
    }, 3500);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
<Head title="Live Chat Support — Admin Panel" />

<AdminLayout>
    <div class="space-y-6">
        <!-- ═══ PAGE HEADER ═══ -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                    <span>Live Chat Support</span>
                    <span class="text-xs px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold">Realtime</span>
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Pantau dan balas pertanyaan pelanggan langsung dari dashboard.
                </p>
            </div>

            <!-- Quick Stats -->
            <div class="flex items-center gap-2">
                <div class="px-3.5 py-2 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2 text-xs">
                    <span class="text-slate-500">Total Percakapan:</span>
                    <span class="font-extrabold text-slate-900">{{ sessions.length }}</span>
                </div>
                <div class="px-3.5 py-2 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2 text-xs">
                    <span class="text-slate-500">Belum Dibalas:</span>
                    <span class="font-extrabold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">
                        {{ sessions.filter(s => s.unread_admin_count > 0).length }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══ MAIN MESSENGER CARD ═══ -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px] max-h-[calc(100vh-210px)]">

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- LEFT PANEL: CONVERSATIONS LIST -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="lg:col-span-4 border-r border-slate-200 flex flex-col bg-slate-50/50">
                <!-- Search & Filter Header -->
                <div class="p-4 border-b border-slate-200 space-y-3 bg-white">
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari pelanggan atau pesan..."
                            class="w-full pl-9 pr-3.5 py-2 text-xs bg-slate-100 border border-slate-200/80 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all"
                        />
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex items-center gap-1 overflow-x-auto text-[11px] font-semibold text-slate-500 pb-0.5">
                        <button
                            type="button"
                            @click="activeFilter = 'all'"
                            class="px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap"
                            :class="activeFilter === 'all' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100'"
                        >
                            Semua ({{ sessions.length }})
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'unread'"
                            class="px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1"
                            :class="activeFilter === 'unread' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100'"
                        >
                            <span>Belum Dibalas</span>
                            <span v-if="sessions.filter(s => s.unread_admin_count > 0).length" class="w-2 h-2 rounded-full bg-rose-500"></span>
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'active'"
                            class="px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap"
                            :class="activeFilter === 'active' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100'"
                        >
                            Aktif
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'closed'"
                            class="px-2.5 py-1 rounded-lg transition-colors whitespace-nowrap"
                            :class="activeFilter === 'closed' ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100'"
                        >
                            Selesai
                        </button>
                    </div>
                </div>

                <!-- Session Items List -->
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <div v-if="filteredSessions.length === 0" class="p-8 text-center text-xs text-slate-400">
                        Tidak ada percakapan yang cocok.
                    </div>

                    <button
                        v-for="s in filteredSessions"
                        :key="s.id"
                        type="button"
                        @click="selectSession(s)"
                        class="w-full p-4 text-left flex items-start gap-3 transition-colors relative hover:bg-slate-100/70"
                        :class="activeSessionId === s.id ? 'bg-white shadow-xs border-l-4 border-indigo-600' : ''"
                    >
                        <!-- Avatar -->
                        <div class="relative shrink-0">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold text-sm flex items-center justify-center shadow-xs">
                                {{ s.customer_name ? s.customer_name.charAt(0).toUpperCase() : 'G' }}
                            </div>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                :class="s.status === 'active' ? 'bg-emerald-500' : 'bg-slate-300'"
                            ></span>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <h4 class="text-xs font-bold text-slate-900 truncate">
                                    {{ s.customer_name }}
                                </h4>
                                <span class="text-[10px] text-slate-400 shrink-0 font-medium">
                                    {{ s.last_message_time }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5 mb-1">
                                <span v-if="s.is_member" class="px-1.5 py-0.2 bg-indigo-50 text-indigo-700 text-[9px] font-bold rounded">
                                    Member
                                </span>
                                <span v-else class="px-1.5 py-0.2 bg-slate-100 text-slate-600 text-[9px] font-semibold rounded">
                                    Tamu
                                </span>
                                <span v-if="s.status === 'closed'" class="px-1.5 py-0.2 bg-slate-200 text-slate-600 text-[9px] font-semibold rounded">
                                    Selesai
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-500 truncate leading-snug">
                                {{ s.last_message || 'Belum ada pesan' }}
                            </p>
                        </div>

                        <!-- Unread badge -->
                        <span
                            v-if="s.unread_admin_count > 0"
                            class="px-2 py-0.5 text-[10px] font-black bg-rose-500 text-white rounded-full shrink-0 animate-pulse shadow-sm"
                        >
                            {{ s.unread_admin_count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- RIGHT PANEL: ACTIVE CHAT ROOM -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="lg:col-span-8 flex flex-col bg-white">

                <!-- EMPTY STATE: If no session is selected -->
                <div v-if="!activeSessionId" class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-400">
                    <div class="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mb-4 shadow-sm">
                        💬
                    </div>
                    <h3 class="text-base font-bold text-slate-700">Pilih Percakapan</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm">
                        Klik salah satu percakapan di sebelah kiri untuk melihat riwayat pesan dan membalas pertanyaan pelanggan.
                    </p>
                </div>

                <!-- ACTIVE ROOM -->
                <template v-else>
                    <!-- Chat Room Header -->
                    <div class="p-4 border-b border-slate-200 flex items-center justify-between gap-4 bg-white shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-extrabold flex items-center justify-center text-sm shadow-xs shrink-0">
                                {{ activeSession?.customer_name?.charAt(0).toUpperCase() || 'P' }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-slate-900 truncate flex items-center gap-2">
                                    <span>{{ activeSession?.customer_name }}</span>
                                    <span 
                                        class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider"
                                        :class="activeSession?.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ activeSession?.status === 'active' ? 'Aktif' : 'Selesai' }}
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400 truncate">
                                    {{ activeSession?.customer_email || 'Email belum diisi' }}
                                    <span v-if="activeSession?.customer_ip" class="ml-2 text-slate-300">• IP: {{ activeSession.customer_ip }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Status Action Button -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                @click="toggleSessionStatus"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl border transition-colors shadow-xs"
                                :class="activeSession?.status === 'active' 
                                    ? 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' 
                                    : 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100'"
                            >
                                {{ activeSession?.status === 'active' ? '✓ Tandai Selesai' : '↺ Buka Kembali' }}
                            </button>
                        </div>
                    </div>

                    <!-- Messages Scroll Area -->
                    <div 
                        ref="messagesContainer"
                        class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50/70"
                    >
                        <div v-if="isLoadingMessages" class="flex justify-center py-10">
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span>Memuat pesan...</span>
                            </div>
                        </div>

                        <template v-else>
                            <div 
                                v-for="m in messages" 
                                :key="m.id"
                                class="flex flex-col"
                            >
                                <!-- System Message -->
                                <div v-if="m.sender_type === 'system'" class="my-3 text-center">
                                    <span class="inline-block px-3 py-1 bg-slate-200 text-slate-600 rounded-full text-[10px] font-medium max-w-md">
                                        {{ m.message }}
                                    </span>
                                </div>

                                <!-- Customer Message (Left side for Admin view) -->
                                <div 
                                    v-else-if="m.sender_type === 'customer'"
                                    class="flex items-start gap-2.5 max-w-[80%]"
                                >
                                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                        {{ m.sender_name?.charAt(0).toUpperCase() || 'P' }}
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-500 mb-0.5 ml-1">
                                            {{ m.sender_name || 'Pelanggan' }}
                                        </div>
                                        <div class="bg-white text-slate-800 border border-slate-200 rounded-2xl rounded-tl-xs px-4 py-2.5 shadow-xs text-xs leading-relaxed break-words">
                                            {{ m.message }}
                                        </div>
                                        <div class="text-[9px] text-slate-400 mt-1 ml-1">
                                            {{ m.time }} • {{ m.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Message (Right side for Admin view) -->
                                <div 
                                    v-else-if="m.sender_type === 'admin'"
                                    class="flex flex-col items-end"
                                >
                                    <div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-tr-xs px-4 py-2.5 shadow-sm text-xs leading-relaxed break-words">
                                        {{ m.message }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[9px] text-slate-400 mt-1 mr-1">
                                        <span>{{ m.sender_name || 'Admin' }}</span>
                                        <span>•</span>
                                        <span>{{ m.time }}</span>
                                        <span v-if="m.is_read" class="text-indigo-600 font-bold" title="Sudah dibaca pelanggan">✓✓</span>
                                        <span v-else class="text-slate-400" title="Terkirim">✓</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Quick Replies Toolbar -->
                    <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0 mr-1">Template:</span>
                        <button
                            v-for="(qr, idx) in quickReplies"
                            :key="idx"
                            type="button"
                            @click="sendMessage(qr)"
                            class="px-2.5 py-1 bg-white hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 border border-slate-200 rounded-lg text-[10px] font-semibold whitespace-nowrap transition-colors shadow-xs shrink-0"
                        >
                            {{ qr }}
                        </button>
                    </div>

                    <!-- Input Reply Footer -->
                    <div class="p-4 border-t border-slate-200 bg-white shrink-0">
                        <form @submit.prevent="sendMessage()" class="flex items-end gap-3">
                            <textarea
                                v-model="newMessage"
                                rows="2"
                                placeholder="Tulis balasan pesan untuk pelanggan... (Tekan Enter untuk kirim)"
                                @keydown="handleKeydown"
                                :disabled="isSending"
                                class="flex-1 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-2xl p-3 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all resize-none"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="!newMessage.trim() || isSending"
                                class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold transition-all shadow-md shadow-indigo-100 shrink-0 flex items-center gap-2"
                            >
                                <span>Kirim Balasan</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                </template>

            </div>
        </div>
    </div>
</AdminLayout>
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
