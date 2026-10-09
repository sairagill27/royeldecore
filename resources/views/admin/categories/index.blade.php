@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')

<div class="sgh-panel">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="small text-secondary mb-0">{{ $categories->count() }} {{ \Illuminate\Support\Str::plural('category', $categories->count()) }}</p>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
            <i class="bi bi-plus-lg me-1"></i> Add category
        </a>
    </div>

    @if ($categories->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-tags"></i> No categories yet.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-center">Products</th>
                    <th class="text-center">Order</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="sgh-admin-thumb">
                                @if ($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="" width="44" height="44" loading="lazy">
                                @else
                                <i class="bi bi-image" aria-hidden="true"></i>
                                @endif
                            </span>
                            <div>
                                <strong class="d-block">{{ $category->name }}</strong>
                                <small class="text-secondary">/shop/{{ $category->slug }}</small>
                            </div>
                        </div>
                    </td>

                    <td class="text-center">{{ $category->products_count }}</td>
                    <td class="text-center">{{ $category->sort_order }}</td>

                    <td>
                        <span class="badge {{ $category->is_active ? 'sgh-badge-ok' : 'sgh-badge-draft' }}">
                            {{ $category->is_active ? 'Visible' : 'Hidden' }}
                        </span>
                    </td>

                    <td>
                        <div class="sgh-row-actions">
                            @if ($category->is_active)
                            <a href="{{ url('/shop/' . $category->slug) }}" target="_blank" rel="noopener"
                                class="sgh-icon-action" title="View on website" aria-label="View {{ $category->name }} on the website">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            @endif

                            <a href="{{ route('admin.categories.edit', $category) }}"
                                class="sgh-icon-action" title="Edit" aria-label="Edit {{ $category->name }}">
                                <i class="bi bi-pencil"></i>
                            </a>

                            {{-- Delete: admins only. A category with products is refused by the server. --}}
                            @if (auth()->user()->isAdmin())
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                onsubmit="return confirm('Delete this category? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sgh-icon-action is-danger"
                                    title="Delete" aria-label="Delete {{ $category->name }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection