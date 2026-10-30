<?php

namespace App\Http\Controllers;

use App\Services\DecorCalculator;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    // /tools: the list of calculators
    public function index()
    {
        return view('tools.index', ['tools' => config('tools')]);
    }

    // /tools/{tool}: one calculator (or a "coming soon" page)
    public function show(string $tool)
    {
        $info = config('tools.' . $tool);
        abort_if(! is_array($info), 404);

        if (! $info['ready']) {
            return response()->view('tools.soon', ['info' => $info, 'slug' => $tool, 'tools' => config('tools')]);
        }

        return view('tools.' . $info['view'], [
            'info'   => $info,
            'slug'   => $tool,
            'tools'  => config('tools'),
            'result' => session('result'),   // set when the form was sent without JavaScript
        ]);
    }

    // POST /tools/{tool}: the page sends the form here, and gets the answer back
    public function calculate(Request $request, string $tool)
    {
        $info = config('tools.' . $tool);
        abort_if(! is_array($info) || ! $info['ready'], 404);

        // SECURITY: only numbers within sensible limits are accepted
        $unit = $request->input('unit') === 'm' ? 'm' : 'ft';
        $data = $request->validate(
            DecorCalculator::rules($info['view'], $unit),
            [],
            DecorCalculator::attributes()
        );

        $result = DecorCalculator::calculate($info['view'], $data);

        // The live page asks for JSON. Without JavaScript the page is shown again with the answer.
        if ($request->expectsJson()) {
            return response()->json($result);
        }

        // The budget list is sent back too, but only as plain text the page escapes when it prints it
        return redirect(route('tools.show', $tool) . '#result')->withInput()->with('result', $result);
    }
}