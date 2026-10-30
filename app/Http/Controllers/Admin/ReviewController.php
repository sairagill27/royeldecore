<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // ---------- LIST ----------
    public function index(Request $request)
    {
        // Only these status values are accepted
        $filter = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status')
            : null;

        $query = ProductReview::with(['product:id,name,slug', 'user:id,name,email'])->latest('id');
        if ($filter) {
            $query->where('status', $filter);
        }

        $reviews = $query->paginate(20)->withQueryString();

        // The numbers on the tabs
        $counts = ProductReview::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.reviews.index', compact('reviews', 'counts', 'filter'));
    }

    // ---------- APPROVE ----------
    public function approve(ProductReview $review)
    {
        $review->status = 'approved';
        $review->save();

        return back()->with('success', 'The review is now visible on the product page.')->with('alert_title', 'Review approved');
    }

    // ---------- REJECT (hides it, but keeps it, so you can approve it later) ----------
    public function reject(ProductReview $review)
    {
        $review->status = 'rejected';
        $review->save();

        return back()->with('success', 'The review is hidden.')->with('alert_title', 'Review rejected');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, ProductReview $review)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $review->delete();

        return back()->with('success', 'The review was deleted.')->with('alert_title', 'Review deleted');
    }
}