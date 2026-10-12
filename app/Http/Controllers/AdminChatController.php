<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminChatController extends Controller
{
    /**
     * Tampilkan halaman live chat console di admin dashboard.
     */
    public function index(Request $request)
    {
        $sessions = ChatSession::with(['latestMessage', 'user:id,name,email'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'session_token' => $s->session_token,
                'customer_name' => $s->customer_name ?: ($s->user?->name ?? 'Guest #' . $s->id),
                'customer_email' => $s->customer_email ?: $s->user?->email,
                'customer_ip' => $s->customer_ip,
                'is_member' => ! is_null($s->user_id),
                'status' => $s->status,
                'unread_admin_count' => $s->unread_admin_count,
                'last_message' => $s->latestMessage?->message,
                'last_message_time' => $s->last_message_at ? $s->last_message_at->diffForHumans() : null,
                'created_at' => $s->created_at->format('d M Y H:i'),
            ]);

        $totalUnread = ChatSession::sum('unread_admin_count');

        return Inertia::render('Admin/Chats', [
            'initialSessions' => $sessions,
            'totalUnread' => $totalUnread,
        ]);
    }

    /**
     * Ambil data sesi ter-update (polling realtime list sesi untuk admin).
     */
    public function getSessions(Request $request)
    {
        $sessions = ChatSession::with(['latestMessage', 'user:id,name,email'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'session_token' => $s->session_token,
                'customer_name' => $s->customer_name ?: ($s->user?->name ?? 'Guest #' . $s->id),
                'customer_email' => $s->customer_email ?: $s->user?->email,
                'customer_ip' => $s->customer_ip,
                'is_member' => ! is_null($s->user_id),
                'status' => $s->status,
                'unread_admin_count' => $s->unread_admin_count,
                'last_message' => $s->latestMessage?->message,
                'last_message_time' => $s->last_message_at ? $s->last_message_at->diffForHumans() : null,
                'created_at' => $s->created_at->format('d M Y H:i'),
            ]);

        $totalUnread = ChatSession::sum('unread_admin_count');

        return response()->json([
            'ok' => true,
            'sessions' => $sessions,
            'totalUnread' => $totalUnread,
        ]);
    }

    /**
     * Ambil riwayat percakapan untuk sesi tertentu & tandai sudah dibaca admin.
     */
    public function getMessages(Request $request, int $id)
    {
        $session = ChatSession::with('user:id,name,email')->findOrFail($id);

        // Tandai semua pesan customer sebagai sudah dibaca oleh admin
        ChatMessage::where('chat_session_id', $session->id)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $session->update(['unread_admin_count' => 0]);

        $messages = $session->messages()->get()->map(fn($m) => [
            'id' => $m->id,
            'sender_type' => $m->sender_type,
            'sender_name' => $m->sender_name,
            'message' => $m->message,
            'is_read' => $m->is_read,
            'time' => $m->created_at->format('H:i'),
            'date' => $m->created_at->format('d M'),
        ]);

        return response()->json([
            'ok' => true,
            'session' => [
                'id' => $session->id,
                'customer_name' => $session->customer_name ?: ($session->user?->name ?? 'Guest #' . $session->id),
                'customer_email' => $session->customer_email ?: $session->user?->email,
                'customer_ip' => $session->customer_ip,
                'status' => $session->status,
                'is_member' => ! is_null($session->user_id),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim balasan pesan dari Admin.
     */
    public function sendMessage(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:1500',
        ]);

        $session = ChatSession::findOrFail($id);
        $admin = $request->user();

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'admin',
            'sender_id' => $admin?->id,
            'sender_name' => $admin?->name ?? 'Admin Support',
            'message' => trim($request->message),
            'is_read' => false,
        ]);

        $session->update([
            'last_message_at' => now(),
            'unread_customer_count' => $session->unread_customer_count + 1,
            'status' => 'active',
        ]);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'is_read' => $message->is_read,
                'time' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('d M'),
            ],
        ]);
    }

    /**
     * Ubah status percakapan (active / closed).
     */
    public function toggleStatus(Request $request, int $id)
    {
        $session = ChatSession::findOrFail($id);
        $newStatus = $session->status === 'closed' ? 'active' : 'closed';
        $session->update(['status' => $newStatus]);

        if ($newStatus === 'closed') {
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'system',
                'sender_name' => 'System',
                'message' => 'Percakapan telah ditandai selesai oleh admin. Terima kasih telah menghubungi kami!',
                'is_read' => true,
            ]);
        }

        return response()->json([
            'ok' => true,
            'status' => $newStatus,
        ]);
    }

    /**
     * Ambil jumlah pesan yang belum dibaca admin untuk badge sidebar.
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => (int) ChatSession::sum('unread_admin_count'),
        ]);
    }
}
