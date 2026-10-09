<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    // These orders do not count as money spent
    private const NOT_COUNTED = ['cancelled', 'refunded'];

    // ---------- LIST ----------
    public function index(Request $request)
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum(['orders as spent' => fn($q) => $q->whereNotIn('status', self::NOT_COUNTED)], 'total')
            ->latest('id');

        // Search by name or email
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    // ---------- ONE CUSTOMER ----------
    public function show(User $user)
    {
        // This page is for customers only (admins and staff are not listed here)
        abort_unless($user->role === 'customer', 404);

        $orders = $user->orders()->withCount('items')->latest()->get();
        $spent  = $orders->whereNotIn('status', self::NOT_COUNTED)->sum('total');

        return view('admin.customers.show', ['customer' => $user, 'orders' => $orders, 'spent' => $spent]);
    }

    // ---------- BLOCK / UNBLOCK (admin only) ----------
    public function toggle(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->role === 'customer', 404);

        $user->is_active = ! $user->is_active;
        $user->save();

        // Blocked: end their login sessions right now (sessions are stored in the database)
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        // Log who did what. Only ids: no personal details.
        Log::info('Customer account ' . ($user->is_active ? 'unblocked' : 'blocked'), [
            'customer' => $user->id,
            'by_user'  => $request->user()->id,
        ]);

        return back()->with('success', $user->is_active
            ? 'The account was unblocked.'
            : 'The account was blocked and logged out.');
    }
}
