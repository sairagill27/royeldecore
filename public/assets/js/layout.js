/* Measures the sticky header and stores its height in --sgh-header-h.
   The sticky sidebar and the calculator result box use it, so they never sit under the header
   or run past the bottom of the screen. */
(function () {
    'use strict';

    var header = document.querySelector('.sgh-header');
    if (!header) { return; }

    function update() {
        var height = Math.round(header.getBoundingClientRect().height);
        document.documentElement.style.setProperty('--sgh-header-h', height + 'px');
    }

    update();
    window.addEventListener('resize', update);
    window.addEventListener('load', update);

    // The header grows when the search bar opens, and shrinks when it closes
    if ('ResizeObserver' in window) {
        new ResizeObserver(update).observe(header);
    }
})();