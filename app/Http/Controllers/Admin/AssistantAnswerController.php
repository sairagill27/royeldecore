<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantAnswer;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssistantAnswerController extends Controller
{
    public function index()
    {
        $answers = AssistantAnswer::orderByDesc('is_active')->orderByDesc('hits')->orderBy('id')->paginate(20);

        return view('admin.assistant.index', compact('answers'));
    }

    public function create()
    {
        return view('admin.assistant.create', ['answer' => new AssistantAnswer(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $answer = AssistantAnswer::create($this->checked($request));

        Audit::record('assistant.answer.created', $answer->question);

        return redirect()->route('admin.assistant-answers.index')
            ->with('success', 'The answer was saved.')->with('alert_title', 'Answer saved');
    }

    public function edit(AssistantAnswer $answer)
    {
        return view('admin.assistant.edit', compact('answer'));
    }

    public function update(Request $request, AssistantAnswer $answer)
    {
        $answer->update($this->checked($request, $answer));

        Audit::record('assistant.answer.updated', $answer->question);

        return redirect()->route('admin.assistant-answers.index')
            ->with('success', 'The answer was updated.')->with('alert_title', 'Answer saved');
    }

    // DELETE (admin only)
    public function destroy(Request $request, AssistantAnswer $answer)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $question = $answer->question;
        $answer->delete();

        Audit::record('assistant.answer.deleted', $question);

        return redirect()->route('admin.assistant-answers.index')
            ->with('success', 'The answer was deleted.')->with('alert_title', 'Answer deleted');
    }

    // SECURITY: plain text only (any HTML is removed), and every value is checked on the server
    private function checked(Request $request, ?AssistantAnswer $answer = null): array
    {
        $clean = fn ($value) => is_string($value) ? trim(strip_tags($value)) : null;

        $request->merge([
            'question' => $clean($request->input('question')),
            'keywords' => $clean($request->input('keywords')),
            'answer'   => $clean($request->input('answer')),
        ]);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:80', Rule::unique('assistant_answers', 'question')->ignore($answer)],
            'keywords' => ['required', 'string', 'max:400', function (string $attribute, mixed $value, \Closure $fail) {
                $phrases = collect(explode(',', (string) $value))
                    ->map(fn ($phrase) => AssistantAnswer::normalize($phrase))
                    ->filter(fn ($phrase) => mb_strlen($phrase) >= 2);

                if ($phrases->isEmpty()) {
                    $fail('Please type at least one word or phrase, separated by commas.');
                }
            }],
            'answer'    => ['required', 'string', 'max:800'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'question.unique' => 'You already have an answer with this question.',
            'question.max'    => 'The question can be up to 80 characters, because it is also a button.',
            'answer.max'      => 'The answer can be up to 800 characters.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}