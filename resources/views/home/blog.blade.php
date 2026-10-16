{{-- LATEST FROM THE BLOG: the 3 newest live posts ($latestPosts comes from HomeController).
     The section does not appear until you have published a post. --}}
@if ($latestPosts->isNotEmpty())
<section class="sgh-section">
    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <p class="sgh-eyebrow mb-2">From the journal</p>
                <h2 class="sgh-section-title mb-0">Latest décor ideas</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="fw-semibold">Read all articles <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
            @foreach ($latestPosts as $post)
            <div class="col"><x-blog-card :post="$post" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif