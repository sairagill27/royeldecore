@extends('layouts.admin')

@section('title', 'Assistant answers')
@section('heading', 'Assistant answers')

@section('content')

<div class="sgh-panel">
    <div class="sgh-admin-toolbar">
        <p class="small text-secondary mb-0 me-lg-3">
            Answers you write yourself. The assistant uses them first: they are instant, free, and never use the AI or the daily limits.
        </p>
        <div class="d-flex flex-wrap gap-2 ms-lg-auto">
            <a href="{{ route('admin.assistant-usage') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-graph-up-arrow me-1"></i> Usage
            </a>
            <a href="{{ route('admin.assistant-answers.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i> Add an answer
            </a>
        </div>
    </div>

    @if ($answers->isEmpty())
        <p class="sgh-panel-empty"><i class="bi bi-chat-heart"></i> No answers yet. Click "Add an answer", or ask your developer to run <code>php artisan assistant:seed-answers</code> for a good starter set.</p>
    @else
        <div class="table-responsive">
            <table class="table sgh-table align-middle mb-0">
                <thead>
                    <tr><th>Question</th><th>Words that trigger it</th><th class="text-center">Used</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($answers as $answer)
                        <tr>
                            {{-- {{ }} escapes the text (safe) --}}
                            <td>
                                <a href="{{ route('admin.assistant-answers.edit', $answer) }}" class="fw-semibold text-decoration-none">{{ $answer->question }}</a>
                                <small class="d-block text-secondary">{{ \Illuminate\Support\Str::limit($answer->answer, 70) }}</small>
                            </td>
                            <td class="small text-secondary">{{ \Illuminate\Support\Str::limit($answer->keywords, 60) }}</td>
                            <td class="text-center">{{ number_format($answer->hits) }}</td>
                            <td>
                                @if ($answer->is_active)
                                    <span class="badge sgh-badge-ok">Active</span>
                                @else
                                    <span class="badge sgh-badge-draft">Hidden</span>
                                @endif
                            </td>
                            <td>
                                <div class="sgh-row-actions">
                                    <a href="{{ route('admin.assistant-answers.edit', $answer) }}" class="sgh-icon-action"
                                       title="Edit" aria-label="Edit {{ $answer->question }}"><i class="bi bi-pencil"></i></a>

                                    {{-- Delete: admins only --}}
                                    @if (auth()->user()->isAdmin())
                                        <form action="{{ route('admin.assistant-answers.destroy', $answer) }}" method="POST"
                                              onsubmit="return confirm('Delete this answer? Hiding it instead keeps it for later.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sgh-icon-action is-danger"
                                                    title="Delete" aria-label="Delete {{ $answer->question }}"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $answers->links() }}</div>
    @endif
</div>

@endsection