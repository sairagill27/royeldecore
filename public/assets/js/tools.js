/* Decor tools: live calculator.
   The sums are done by the server (one source of truth). This script only sends the form and shows
   the answer. Without JavaScript, the Calculate button still works and reloads the page. */
(function () {
    'use strict';

    var form = document.querySelector('[data-tool-form]');
    var box  = document.getElementById('toolResults');
    if (!form || !box || !window.fetch || !window.FormData) { return; }

    var labels = {
        m:  { len: 'm',  area: 'm\u00b2',  cov: 'm\u00b2 per litre' },
        ft: { len: 'ft', area: 'ft\u00b2', cov: 'ft\u00b2 per litre' }
    };
    var timer = null;
    var seq   = 0;     // only the newest answer is shown
    var unit  = currentUnit();

    function currentUnit() {
        var checked = form.querySelector('input[name="unit"]:checked');
        return checked && checked.value === 'm' ? 'm' : 'ft';
    }

    // Feet or metres changed: update the unit text and the default coverage
    function applyUnit(previous) {
        var set = labels[unit];
        form.querySelectorAll('[data-u]').forEach(function (node) {
            node.textContent = set[node.getAttribute('data-u')] || '';
        });
        form.querySelectorAll('[data-default-m]').forEach(function (input) {
            var old = input.getAttribute('data-default-' + previous);
            if (input.value === '' || input.value === old) {
                input.value = input.getAttribute('data-default-' + unit);
            }
        });
    }

    function make(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function showMessage(text) {
        box.textContent = '';
        var wrap = make('div', 'sgh-res-empty');
        wrap.appendChild(make('p', null, text));
        box.appendChild(wrap);
    }

    function showResult(data) {
        box.textContent = '';

        (data.results || []).forEach(function (row) {
            var item = make('div', 'sgh-res' + (row.main ? ' is-main' : ''));
            item.appendChild(make('span', 'sgh-res-label', row.label));
            item.appendChild(make('strong', 'sgh-res-value', row.value));
            if (row.note) { item.appendChild(make('small', 'sgh-res-note', row.note)); }
            box.appendChild(item);
        });

        if (data.tips && data.tips.length) {
            var wrap = make('div', 'sgh-res-tips');
            wrap.appendChild(make('strong', null, 'Good to know'));
            var list = make('ul');
            data.tips.forEach(function (tip) { list.appendChild(make('li', null, tip)); });
            wrap.appendChild(list);
            box.appendChild(wrap);
        }
    }

    function clearErrors() {
        form.querySelectorAll('[data-error-for]').forEach(function (node) { node.textContent = ''; });
        form.querySelectorAll('.is-invalid').forEach(function (node) { node.classList.remove('is-invalid'); });
    }

    function showError(name, message) {
        var slot  = form.querySelector('[data-error-for="' + name + '"]');
        var input = form.querySelector('[name="' + name + '"]');
        if (slot)  { slot.textContent = message; }
        if (input) { input.classList.add('is-invalid'); }
    }

    // The boxes marked as required that are still empty
    function missing() {
        return Array.prototype.filter.call(form.querySelectorAll('[data-required]'), function (input) {
            return input.value.trim() === '';
        });
    }

    function run(explicit) {
        var alertBox = document.querySelector('[data-server-alert]');
        if (alertBox) { alertBox.remove(); }
        clearErrors();

        var gaps = missing();
        if (gaps.length) {
            if (explicit) {
                gaps.forEach(function (input) { showError(input.name, 'Please fill this in.'); });
                showMessage('Please fill in the highlighted boxes.');
            } else {
                showMessage('Fill in the measurements to see your result.');
            }
            return;
        }

        var mine = ++seq;

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) { return { status: res.status, data: data }; });
            })
            .then(function (reply) {
                if (mine !== seq) { return; }

                if (reply.status === 200) {
                    showResult(reply.data);
                } else if (reply.status === 422) {
                    var errors = reply.data.errors || {};
                    Object.keys(errors).forEach(function (name) { showError(name, errors[name][0]); });
                    showMessage('Please check the highlighted boxes.');
                } else if (reply.status === 429) {
                    showMessage('Too many calculations. Please wait a moment and try again.');
                } else {
                    showMessage('Something went wrong. Please try again.');
                }
            })
            .catch(function () {
                if (mine === seq) { showMessage('Could not calculate. Please check your connection and try again.'); }
            });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(function () { run(false); }, 400);
    }

    form.addEventListener('input', schedule);

    form.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'unit') {
            var previous = unit;
            unit = currentUnit();
            applyUnit(previous);
        }
        schedule();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearTimeout(timer);
        run(true);
    });

    // A browser may bring the numbers back after a reload: show the answer straight away
    if (!box.querySelector('.sgh-res') && missing().length === 0) { run(false); }
})();