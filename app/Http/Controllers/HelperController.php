<?php

namespace App\Http\Controllers;

use App\Services\DecorHelper;
use Illuminate\Http\Request;

class HelperController extends Controller
{
    public function index(Request $request)
    {
        $rooms   = config('helper.rooms');
        $styles  = config('helper.styles');
        $budgets = config('helper.budgets');

        // SECURITY: only values from our own lists are accepted. Anything else is ignored.
        $pick = function (string $key, array $allowed) use ($request): ?string {
            $value = $request->query($key);

            return (is_string($value) && isset($allowed[$value])) ? $value : null;
        };

        $room   = $pick('room', $rooms);
        $style  = $pick('style', $styles);
        $budget = $pick('budget', $budgets) ?? 'any';

        $result = ($room && $style) ? DecorHelper::suggest($room, $style, $budget) : null;

        // The form sends go=1, so a shortcut link that only names a room does not show an error
        $error = ($request->has('go') && ! $result) ? 'Please choose a room and a style.' : null;

        return view('helper.index', compact('rooms', 'styles', 'budgets', 'room', 'style', 'budget', 'result', 'error'));
    }
}