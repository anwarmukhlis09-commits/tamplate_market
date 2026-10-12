<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const showGreetingBubble = ref(false);
const sessionToken = ref(localStorage.getItem('hotspot_chat_session') || '');
const customerName = ref(localStorage.getItem('hotspot_chat_name') || '');
const messages = ref([]);
const newMessage = ref('');
const isSending = ref(false);
const isLoading = ref(false);
const unreadCount = ref(0);
const messagesContainer = ref(null);

const quickSuggestions = [
    'Cara pasang template di Winbox?',
    'Rekomendasi template untuk Cafe',
    'Bisa edit warna dan logo sendiri?',
    'Bagaimana cara bayar via QRIS?',
];

let pollInterval = null;

async function initChat() {
    isLoading.value = true;
    try {
        const res = await axios.post('/api/chat/init', {
            session_token: sessionToken.value,
            customer_name: customerName.value || null,
        });

        if (res.data?.ok) {
            sessionToken.value = res.data.session.session_token;
            localStorage.setItem('hotspot_chat_session', res.data.session.session_token);
            if (res.data.session.customer_name) {
                customerName.value = res.data.session.customer_name;
                localStorage.setItem('hotspot_chat_name', res.data.session.customer_name);
            }
            messages.value = res.data.messages || [];
            unreadCount.value = 0;
            scrollToBottom();
        }
    } catch (e) {
        console.error('Failed to init chat', e);
    } finally {
        isLoading.value = false;
    }
}

async function fetchMessages(markRead = true) {
    if (!sessionToken.value) return;

    try {
        const res = await axios.get('/api/chat/messages', {
            params: {
                session_token: sessionToken.value,
                mark_read: markRead && isOpen.value ? 1 : 0,
            },
        });

        if (res.data?.ok) {
            const previousLength = messages.value.length;
            messages.value = res.data.messages || [];

            if (!isOpen.value) {
                unreadCount.value = res.data.unread_customer_count || 0;
            } else {
                unreadCount.value = 0;
                if (messages.value.length > previousLength) {
                    scrollToBottom();
                }
            }
        }
    } catch (e) {
        // silent fail during polling
    }
}

async function sendMessage(textToSend = null) {
    const text = (textToSend || newMessage.value).trim();
    if (!text || isSending.value) return;

    // Pastikan session sudah ter-init
    if (!sessionToken.value) {
        await initChat();
    }

    isSending.value = true;
    if (!textToSend) {
        newMessage.value = '';
    }

    // Optimistic UI insert
    const tempId = 'temp_' + Date.now();
    messages.value.push({
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
            session_token: sessionToken.value,
            message: text,
            customer_name: customerName.value || null,
        });

        if (res.data?.ok) {
            // Replace temporary message with server response
            const idx = messages.value.findIndex(m => m.id === tempId);
            if (idx !== -1) {
                messages.value[idx] = res.data.message;
            }
        }
    } catch (e) {
        console.error('Failed to send message', e);
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

function selectSuggestion(chip) {
    sendMessage(chip);
}

function toggleChat() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        showGreetingBubble.value = false;
        unreadCount.value = 0;
        if (!sessionToken.value || messages.value.length === 0) {
            initChat();
        } else {
            fetchMessages(true);
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

watch(isOpen, (opened) => {
    if (opened) {
        scrollToBottom();
        startFastPolling();
    } else {
        startSlowPolling();
    }
});

function startFastPolling() {
    stopPolling();
    pollInterval = setInterval(() => fetchMessages(true), 3500);
}

function startSlowPolling() {
    stopPolling();
    pollInterval = setInterval(() => fetchMessages(false), 20000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

onMounted(() => {
    // Show polite greeting bubble after 4 seconds if chat hasn't been opened yet
    setTimeout(() => {
        if (!isOpen.value && messages.value.length === 0) {
            showGreetingBubble.value = true;
        }
    }, 4000);

    // Initial silent check
    if (sessionToken.value) {
        fetchMessages(false);
    }

    startSlowPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
<div class="fixed bottom-6 right-6 z-50 font-sans select-none print:hidden">

    <!-- ═══ GREETING POPOVER (Muncul awal sebelum dibuka) ═══ -->
    <transition
        enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="opacity-0 translate-y-3 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in transform"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95"
    >
        <div 
            v-if="!isOpen && showGreetingBubble" 
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

    <!-- ═══ CHAT WINDOW (Popup Modern) ═══ -->
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
            class="w-[360px] sm:w-[390px] h-[540px] max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col overflow-hidden mb-3"
        >
            <!-- ═══ HEADER ═══ -->
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

                <div class="flex items-center gap-1">
                    <button 
                        type="button"
                        @click="toggleChat"
                        class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition-colors"
                        title="Tutup Obrolan"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
            </div>

            <!-- ═══ MESSAGES BODY ═══ -->
            <div 
                ref="messagesContainer"
                class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/60 select-text"
            >
                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center py-8">
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span>Menghubungkan ke admin...</span>
                    </div>
                </div>

                <!-- Messages -->
                <template v-else>
                    <div 
                        v-for="msg in messages" 
                        :key="msg.id"
                        class="flex flex-col"
                    >
                        <!-- SYSTEM MESSAGE (Welcome Bot / Info) -->
                        <div v-if="msg.sender_type === 'system'" class="my-2 text-center">
                            <div class="inline-block bg-indigo-50 border border-indigo-100 rounded-2xl px-3.5 py-2 text-xs text-indigo-900 max-w-[90%] shadow-xs text-left">
                                <div class="flex items-center gap-1.5 font-bold text-indigo-700 text-[11px] mb-0.5">
                                    <span>🤖 Support Bot</span>
                                </div>
                                <div class="text-[11px] leading-relaxed">{{ msg.message }}</div>
                            </div>
                        </div>

                        <!-- CUSTOMER MESSAGE (Right - Indigo bubble) -->
                        <div 
                            v-else-if="msg.sender_type === 'customer'"
                            class="flex flex-col items-end"
                        >
                            <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 shadow-sm text-xs leading-relaxed break-words">
                                {{ msg.message }}
                            </div>
                            <div class="flex items-center gap-1 text-[9px] text-slate-400 mt-1 mr-1">
                                <span>{{ msg.time }}</span>
                                <span v-if="msg.is_read" class="text-indigo-600 font-bold" title="Dibaca">✓✓</span>
                                <span v-else class="text-slate-400" title="Terkirim">✓</span>
                            </div>
                        </div>

                        <!-- ADMIN MESSAGE (Left - White bubble) -->
                        <div 
                            v-else-if="msg.sender_type === 'admin'"
                            class="flex items-start gap-2 max-w-[88%]"
                        >
                            <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-xs">
                                CS
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 mb-0.5 ml-1">
                                    {{ msg.sender_name || 'Admin Support' }}
                                </div>
                                <div class="bg-white text-slate-800 border border-slate-200/80 rounded-2xl rounded-tl-xs px-3.5 py-2.5 shadow-sm text-xs leading-relaxed break-words">
                                    {{ msg.message }}
                                </div>
                                <div class="text-[9px] text-slate-400 mt-1 ml-1">
                                    {{ msg.time }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ═══ QUICK PROMPT SUGGESTIONS ═══ -->
            <div class="px-3 py-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
                <button
                    v-for="(chip, idx) in quickSuggestions"
                    :key="idx"
                    type="button"
                    @click="selectSuggestion(chip)"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 rounded-lg text-[10px] font-semibold whitespace-nowrap transition-colors shrink-0 border border-slate-200/60"
                >
                    {{ chip }}
                </button>
            </div>

            <!-- ═══ INPUT FOOTER ═══ -->
            <div class="p-3 bg-white border-t border-slate-200 shrink-0">
                <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                    <input 
                        v-model="newMessage"
                        type="text" 
                        placeholder="Ketik pesan Anda..." 
                        :disabled="isSending"
                        @keydown="handleKeydown"
                        class="flex-1 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all"
                    />
                    <button 
                        type="submit"
                        :disabled="!newMessage.trim() || isSending"
                        class="w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center transition-colors shadow-md shadow-indigo-100 shrink-0"
                        title="Kirim Pesan"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2 px-1">
                    <span>⚡ Balasan realtime</span>
                    <span>Tekan Enter untuk kirim</span>
                </div>
            </div>
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
            <!-- Unread badge count -->
            <span 
                v-if="!isOpen && unreadCount > 0" 
                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-500 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center border-2 border-white animate-bounce shadow-md"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
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
/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
