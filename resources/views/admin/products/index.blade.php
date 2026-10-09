@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Products')

@section('content')

@php $currency = config('shop.currency'); @endphp

<div class="sgh-panel">

    {{-- ============ SEARCH + FILTERS + ADD BUTTON ============ --}}
    <div class="sgh-admin-toolbar">

        {{-- GET form: the filters stay in the web address, so the page can be shared --}}
        <form action="{{ route('admin.products.index') }}" method="GET" class="sgh-admin-filters" role="search">
            <label for="q" class="visually-hidden">Search products</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}"
                class="form-control form-control-sm" placeholder="Search name or SKU…">

            <label for="status" class="visually-hidden">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status')==='active' )>Active</option>
                <option value="draft" @selected(request('status')==='draft' )>Draft</option>
            </select>

            <label for="category" class="visually-hidden">Category</label>
            <select name="category" id="category" class="form-select form-select-sm">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) request('category')===(string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>

            <label for="stock" class="visually-hidden">Stock</label>
            <select name="stock" id="stock" class="form-select form-select-sm">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock')==='low' )>Low (5 or fewer)</option>
                <option value="out" @selected(request('stock')==='out' )>Out of stock</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-link btn-sm">Reset</a>
        </form>

        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 ms-lg-auto">
            <i class="bi bi-plus-lg me-1"></i> Add product
        </a>
    </div>

    <p class="small text-secondary mb-3">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</p>

    @if ($products->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-box-seam"></i> No products found. Try changing the filters.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                <tr>
                    {{-- Photo + name + SKU --}}
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="sgh-admin-thumb">
                                @if ($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt=""
                                    width="44" height="44" loading="lazy">
                                @else
                                <i class="bi bi-image" aria-hidden="true"></i>
                                @endif
                            </span>
                            <div>
                                {{-- {{ }} escapes the text (safe) --}}
                                <strong class="d-block">
                                    {{ $product->name }}
                                    @if ($product->is_featured)
                                    <i class="bi bi-star-fill text-warning small" title="Featured"></i>
                                    @endif
                                </strong>
                                <small class="text-secondary">{{ $product->sku ? 'SKU: ' . $product->sku : 'No SKU' }}</small>
                            </div>
                        </div>
                    </td>

                    <td>{{ $product->category?->name ?? '—' }}</td>

                    <td class="text-end fw-semibold">{{ $currency }} {{ number_format($product->price) }}</td>

                    <td class="text-center">
                        @if ($product->stock === 0)
                        <span class="badge sgh-badge-out">Sold out</span>
                        @elseif ($product->stock <= 5)
                            <span class="badge sgh-badge-low">{{ $product->stock }} left</span>
                            @else
                            <span class="badge sgh-badge-ok">{{ $product->stock }}</span>
                            @endif
                    </td>

                    <td>
                        <span class="badge {{ $product->status === 'active' ? 'sgh-badge-ok' : 'sgh-badge-draft' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>

                    {{-- Action buttons --}}
                    <td>
                        <div class="sgh-row-actions">

                            {{-- View on the shop (only active products with a slug) --}}
                            @if ($product->status === 'active' && $product->slug)
                            <a href="{{ url('/products/' . $product->slug) }}" target="_blank" rel="noopener"
                                class="sgh-icon-action" title="View on website" aria-label="View {{ $product->name }} on the website">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            @endif

                            <a href="{{ route('admin.products.edit', $product) }}"
                                class="sgh-icon-action" title="Edit" aria-label="Edit {{ $product->name }}">
                                <i class="bi bi-pencil"></i>
                            </a>

                            {{-- Delete: admins only. The form needs @csrf and @method('DELETE'). --}}
                            @if (auth()->user()->isAdmin())
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sgh-icon-action is-danger"
                                    title="Delete" aria-label="Delete {{ $product->name }}">
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

    <div class="mt-4 d-flex justify-content-center">
        {{ $products->links() }}
    </div>
    @endif
</div>

@endsection