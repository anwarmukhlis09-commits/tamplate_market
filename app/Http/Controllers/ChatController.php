<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Inisialisasi atau load sesi chat untuk customer (guest maupun member).
     */
    public function init(Request $request)
    {
        $token = $request->input('session_token');
        $user = $request->user();

        $session = null;
        if ($token) {
            $session = ChatSession::where('session_token', $token)->first();
        }

        // Kalau belum ada sesi atau token invalid, buat baru
        if (! $session) {
            $token = Str::random(40);
            $customerName = $user ? $user->name : 'Tamu #' . substr($token, 0, 4);
            $customerEmail = $user ? $user->email : null;

            $session = ChatSession::create([
                'session_token' => $token,
                'user_id' => $user?->id,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_ip' => $request->ip(),
                'status' => 'active',
                'unread_admin_count' => 0,
                'unread_customer_count' => 0,
                'last_message_at' => now(),
            ]);

            // Tambahkan pesan bot sambutan otomatis
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'system',
                'sender_name' => 'Admin Support',
                'message' => 'Halo! Selamat datang di Template Hotspot MikroTik. 👋 Ada yang bisa kami bantu seputar pemilihan template, custom desain, atau panduan pasang ke router Anda?',
                'is_read' => true,
            ]);
        } else {
            // Update nama jika user baru saja login
            if ($user && ! $session->user_id) {
                $session->update([
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                ]);
            }
        }

        // Tandai pesan admin sebagai sudah dibaca oleh customer
        ChatMessage::where('chat_session_id', $session->id)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $session->update(['unread_customer_count' => 0]);

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
                'session_token' => $session->session_token,
                'customer_name' => $session->customer_name,
                'status' => $session->status,
                'unread_count' => 0,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Ambil pesan terbaru (polling realtime untuk customer).
     */
    public function getMessages(Request $request)
    {
        $token = $request->query('session_token');
        if (! $token) {
            return response()->json(['ok' => false, 'error' => 'Missing session token'], 400);
        }

        $session = ChatSession::where('session_token', $token)->first();
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        // Tandai pesan admin sebagai sudah dibaca jika window chat sedang aktif/terbuka
        if ($request->boolean('mark_read', true)) {
            ChatMessage::where('chat_session_id', $session->id)
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $session->update(['unread_customer_count' => 0]);
        }

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
            'session_status' => $session->status,
            'unread_customer_count' => $session->unread_customer_count,
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim pesan dari customer.
     */
    public function sendMessage(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'session_token' => 'required|string',
            'message' => 'required|string|max:1500',
            'customer_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)->first();
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Sesi chat tidak ditemukan'], 404);
        }

        if ($request->filled('customer_name') && ! $session->user_id) {
            $session->customer_name = $request->customer_name;
        }

        $senderName = $session->customer_name ?: ($request->user()?->name ?? 'Pelanggan');

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'customer',
            'sender_id' => $request->user()?->id,
            'sender_name' => $senderName,
            'message' => trim($request->message),
            'is_read' => false,
        ]);

        $session->update([
            'status' => 'active',
            'last_message_at' => now(),
            'unread_admin_count' => $session->unread_admin_count + 1,
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
}
