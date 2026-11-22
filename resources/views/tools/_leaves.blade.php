{{-- Warm shade (always) and an optional photo (only when a picture exists in public/assets/images/headers/) --}}
@php
    $headKey = match (true) {
        request()->is('shop', 'shop/*')                        => 'shop',
        request()->is('blog', 'blog/category/*', 'blog/tag/*') => 'blog',
        request()->is('tools', 'tools/*')                      => 'tools',
        request()->is('helper')                                => 'helper',
        request()->is('assistant')                             => 'assistant',
        request()->is('recommended')                           => 'recommended',
        default                                                => null,
    };

    $headPhoto = null;
    if ($headKey) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (file_exists(public_path('assets/images/headers/' . $headKey . '.' . $ext))) {
                $headPhoto = 'assets/images/headers/' . $headKey . '.' . $ext;
                break;
            }
        }
    }
@endphp
<div class="sgh-head-glow" aria-hidden="true"></div>
@if ($headPhoto)
    <div class="sgh-head-photo" aria-hidden="true">
        <img src="{{ asset($headPhoto) }}" alt="" width="1600" height="700" loading="eager" decoding="async">
    </div>
@endif
{{-- Decorative leaf branches for the dark green tool header. Pure SVG, no image files. --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <g id="tl-leaf">
            <path d="M0 0C8 -10 28 -12 44 0C28 12 8 10 0 0Z"/>
            <path d="M3 0L38 0" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="1"/>
        </g>
        <g id="tl-branch">
            <path d="M30 0C60 80 10 160 50 260" fill="none" stroke="#a9c4ae" stroke-width="2" stroke-linecap="round"/>
            <use href="#tl-leaf" transform="translate(39 36) rotate(35) scale(.85)"/>
            <use href="#tl-leaf" transform="translate(40 72) rotate(148)"/>
            <use href="#tl-leaf" transform="translate(37 110) rotate(30) scale(1.1)"/>
            <use href="#tl-leaf" transform="translate(34 148) rotate(152) scale(1.05)"/>
            <use href="#tl-leaf" transform="translate(34 188) rotate(28)"/>
            <use href="#tl-leaf" transform="translate(40 230) rotate(155) scale(.9)"/>
            <use href="#tl-leaf" transform="translate(50 260) rotate(85) scale(.8)"/>
        </g>
    </defs>
</svg>
<svg class="sgh-tool-leaves sgh-tool-leaves--a" viewBox="-30 0 170 280" aria-hidden="true" focusable="false"><use href="#tl-branch"/></svg>
<svg class="sgh-tool-leaves sgh-tool-leaves--b" viewBox="-30 0 170 280" aria-hidden="true" focusable="false"><use href="#tl-branch"/></svg>