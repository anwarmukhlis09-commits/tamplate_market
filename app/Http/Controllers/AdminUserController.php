<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Halaman index admin user.
     * Route: GET /admin/users
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->withCount(['orders as orders_count', 'orders as paid_orders_count' => fn($q) => $q->where('status', 'completed')])
            ->with(['disabledByUser:id,name'])
            ->latest('created_at');

        if ($search = trim((string) $request->query('search'))) {
            $q = addcslashes($search, '%_\\');
            $query->where(function ($sql) use ($q) {
                $sql->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($role = $request->query('role')) {
            // Whitelist: admin (is_admin=true), creator (ada order completed), user (lainnya)
            if ($role === 'admin') {
                $query->where('is_admin', true);
            } elseif ($role === 'creator') {
                $query->whereHas('orders', fn($q) => $q->where('status', 'completed'));
            } elseif ($role === 'user') {
                $query->where('is_admin', false)
                    ->whereDoesntHave('orders', fn($q) => $q->where('status', 'completed'));
            }
        }

        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->whereNull('disabled_at');
            } elseif ($status === 'disabled') {
                $query->whereNotNull('disabled_at');
            }
        }

        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'stats' => $this->computeStats(),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Update nama & email user.
     * Route: PATCH /admin/users/{user}
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Self-protection: admin tidak boleh mengubah akun sendiri dari panel
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat mengubah akun Anda sendiri dari panel admin. Gunakan halaman profil.']);
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->update($validated);

        return back()->with('success', "User '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Reset password user (admin set password baru manual).
     * Route: POST /admin/users/{user}/reset-password
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat mereset password akun Anda sendiri dari panel admin.']);
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
            'new_password.min' => 'Password minimal 8 karakter.',
        ]);

        // forceFill + save: bypass mass assignment guard untuk field 'password'.
        // Sekaligus reset remember_token untuk invalidate existing "remember me" cookies.
        $user->forceFill([
            'password' => Hash::make($request->new_password),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', "Password user '{$user->name}' berhasil direset.");
    }

    /**
     * Toggle status aktif/nonaktif user.
     * Route: PATCH /admin/users/{user}/toggle-active
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        if ($user->isDisabled()) {
            // Re-aktifkan — pakai property assignment + save() karena disabled_at
            // tidak di $fillable (di-set manual untuk proteksi mass assignment).
            $user->disabled_at = null;
            $user->disabled_by = null;
            $user->save();
            return back()->with('success', "Akun '{$user->name}' berhasil diaktifkan kembali.");
        }

        // Last-admin protection: jangan izinkan disable admin terakhir yang aktif
        if ($user->is_admin) {
            $activeAdmins = User::where('is_admin', true)->whereNull('disabled_at')->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['error' => 'Tidak dapat menonaktifkan admin terakhir yang masih aktif.']);
            }
        }

        $user->disabled_at = now();
        $user->disabled_by = Auth::id();
        $user->save();

        return back()->with('success', "Akun '{$user->name}' berhasil dinonaktifkan.");
    }

    /**
     * Hapus user (cascade ke orders via FK).
     * Route: DELETE /admin/users/{user}
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        $name = $user->name;
        $ordersCount = $user->orders()->count();

        // Cascade: orders FK sudah cascadeOnDelete (lihat migration orders line 13)
        $user->delete();

        $msg = "Akun '{$name}' berhasil dihapus.";
        if ($ordersCount > 0) {
            $msg .= " {$ordersCount} order terkait ikut terhapus.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Hitung ringkasan untuk 4 stat cards.
     */
    private function computeStats(): array
    {
        $base = User::query();

        return [
            'total_users'    => User::count(),
            'active_users'   => (clone $base)->whereNull('disabled_at')->count(),
            'disabled_users' => (clone $base)->whereNotNull('disabled_at')->count(),
            'admins_count'   => (clone $base)->where('is_admin', true)->whereNull('disabled_at')->count(),
            'creators_count' => User::whereHas('orders', fn($q) => $q->where('status', 'completed'))->count(),
        ];
    }
}