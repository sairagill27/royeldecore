<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // ---------- LIST ----------
    public function index(Request $request)
    {
        $query = ContactMessage::latest();

        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $messages    = $query->paginate(15)->withQueryString();
        $unreadCount = ContactMessage::whereNull('read_at')->count();
        $totalCount  = ContactMessage::count();

        return view('admin.messages.index', compact('messages', 'filter', 'unreadCount', 'totalCount'));
    }

    // ---------- READ ONE (marks it as read) ----------
    public function show(ContactMessage $message)
    {
        if ($message->isUnread()) {
            $message->read_at = now();
            $message->save();
        }

        return view('admin.messages.show', compact('message'));
    }

    // ---------- MARK AS UNREAD ----------
    public function unread(ContactMessage $message)
    {
        $message->read_at = null;
        $message->save();

        return redirect()->route('admin.messages.index')->with('success', 'Marked as unread.');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, ContactMessage $message)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $message->delete();

        return redirect()->route('admin.messages.index')
            ->with('success', 'The message was deleted.')
            ->with('alert_title', 'Message deleted');
    }
}
