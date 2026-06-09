<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Purchase Controller — purchase history per-template.
 */
class PurchaseController extends Controller
{
    /**
     * Tampilkan detail pembelian untuk template tertentu (per user).
     * Route: GET /purchases/{id}
     */
    public function show(Request $request, int $id): Response
    {
        $template = Template::findOrFail($id);

        return Inertia::render('Client/PurchaseDetail', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'price' => $template->price,
            ],
            'purchase' => [
                'orderId' => 'ORD-' . $template->id . '-' . $request->user()->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'completed',
            ],
        ]);
    }
}
