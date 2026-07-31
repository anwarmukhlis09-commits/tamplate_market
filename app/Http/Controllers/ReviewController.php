<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Template;
use App\Models\Order;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $template = Template::findOrFail($id);
        $user = $request->user();



        // Create or update review
        Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'template_id' => $template->id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        // Recalculate average rating for the template
        $avgRating = Review::where('template_id', $template->id)->avg('rating');
        $template->update([
            'rating' => round($avgRating, 1),
        ]);

        return back()->with('info', 'Ulasan Anda berhasil disimpan!');
    }
}
