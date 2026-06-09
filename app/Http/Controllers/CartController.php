<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cart Controller — keranjang template (in-memory, scoped per-user).
 * Data disimpan di session untuk hindari bentrok antar user.
 */
class CartController extends Controller
{
    /**
     * Tambah template ke keranjang.
     * Route: POST /cart/{id}
     */
    public function add(Request $request, int $id): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        if (!in_array($id, $cart)) {
            $cart[] = $id;
        }
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Template ditambahkan ke keranjang.');
    }

    /**
     * Hapus template dari keranjang.
     * Route: DELETE /cart/{id}
     */
    public function remove(Request $request, int $id): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        $cart = array_values(array_filter($cart, fn($t) => $t !== $id));
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Template dihapus dari keranjang.');
    }
}
