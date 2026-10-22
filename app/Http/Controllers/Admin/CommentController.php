<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // ---------- LIST ----------
    public function index(Request $request)
    {
        // Only these status values are accepted
        $filter = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status')
            : null;

        $query = Comment::with('post:id,title,slug')->latest('id');
        if ($filter) {
            $query->where('status', $filter);
        }

        $comments = $query->paginate(20)->withQueryString();

        // The numbers on the tabs
        $counts = Comment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.comments.index', compact('comments', 'counts', 'filter'));
    }

    // ---------- APPROVE ----------
    public function approve(Comment $comment)
    {
        $comment->status = 'approved';
        $comment->save();

        return back()->with('success', 'The comment is now visible on the article.')->with('alert_title', 'Comment approved');
    }

    // ---------- REJECT (hides it, but keeps it, so you can approve it later) ----------
    public function reject(Comment $comment)
    {
        $comment->status = 'rejected';
        $comment->save();

        return back()->with('success', 'The comment is hidden.')->with('alert_title', 'Comment rejected');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, Comment $comment)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $comment->delete();

        return back()->with('success', 'The comment was deleted.')->with('alert_title', 'Comment deleted');
    }
}
