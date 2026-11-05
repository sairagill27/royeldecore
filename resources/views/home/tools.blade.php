{{-- DECOR TOOLS BAND: promotes the free calculators (config/tools.php). Dark green, like the tool pages. --}}
@php $readyTools = collect(config('tools'))->filter(fn ($tool) => ! empty($tool['ready'])); @endphp

@if ($readyTools->isNotEmpty())
<section class="sgh-home-tools">
    @include('tools._leaves')

    <div class="container position-relative">
        <div class="row align-items-center g-4 g-lg-5">

            <div class="col-lg-5">
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Free to use</span>
                <h2 class="sgh-home-tools-title">Plan your room before you buy</h2>
                <p class="sgh-home-tools-text">
                    Work out how much paint, flooring, wallpaper or curtain fabric you need, and keep your spending on track.
                    Results are estimates to help you plan.
                </p>
                <a href="{{ route('tools.index') }}" class="btn btn-light rounded-pill px-4">
                    Explore all tools <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>

            <div class="col-lg-7">
                <div class="row row-cols-1 row-cols-sm-2 g-3">
                    @foreach ($readyTools as $key => $tool)
                        <div class="col">
                            <a href="{{ route('tools.show', $key) }}" class="sgh-home-tool">
                                <span class="sgh-home-tool-icon"><i class="bi {{ $tool['icon'] }}" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $tool['name'] }}</strong>
                                    <small>{{ $tool['short'] }}</small>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif