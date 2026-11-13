/* Decor Assistant chat page.
   Everything the server sends is written with textContent, so no text can run as code.
   The conversation is kept only in this browser tab (sessionStorage) and is never saved on the server. */
(function () {
    'use strict';

    var root = document.getElementById('chat');
    if (!root) { return; }

    var log      = document.getElementById('chatLog');
    var form     = document.getElementById('chatForm');
    var input    = document.getElementById('chatInput');
    var send     = document.getElementById('chatSend');
    var counter  = document.getElementById('chatCount');
    var clearBtn = document.getElementById('chatClear');

    var url  = root.getAttribute('data-ask-url');
    var csrf = root.getAttribute('data-csrf');
    var max  = parseInt(root.getAttribute('data-max'), 10) || 500;
    var KEY  = 'sgh-chat-v1';

    var history = [];   // [{ role: 'user' | 'assistant', text: '...' }]
    var busy = false;

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) { node.className = cls; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    // Only http and https addresses are used for links and pictures
    function safe(u) {
        try {
            var x = new URL(u, window.location.href);
            return (x.protocol === 'http:' || x.protocol === 'https:') ? x.href : '';
        } catch (e) { return ''; }
    }

    function scrollDown() { log.scrollTop = log.scrollHeight; }

    function save() {
        try { sessionStorage.setItem(KEY, JSON.stringify(history.slice(-24))); } catch (e) { /* private mode: fine */ }
    }

    // One message bubble. Returns the bubble, so cards can be added inside it.
    function bubble(role, text) {
        var wrap = el('div', 'sgh-msg sgh-msg--' + role);
        if (role === 'bot') {
            var avatar = el('span', 'sgh-avatar');
            avatar.setAttribute('aria-hidden', 'true');
            avatar.appendChild(el('i', 'bi bi-stars'));
            wrap.appendChild(avatar);
        }
        var b = el('div', 'sgh-bubble sgh-bubble--' + role);
        String(text).split(/\n+/).forEach(function (line) {
            if (line.trim() !== '') { b.appendChild(el('p', 'mb-1', line)); }
        });
        wrap.appendChild(b);
        log.appendChild(wrap);
        return b;
    }

    function typing() {
        var wrap = el('div', 'sgh-msg sgh-msg--bot');
        var avatar = el('span', 'sgh-avatar');
        avatar.setAttribute('aria-hidden', 'true');
        avatar.appendChild(el('i', 'bi bi-stars'));
        wrap.appendChild(avatar);
        var b = el('div', 'sgh-bubble sgh-bubble--bot sgh-typing');
        b.setAttribute('aria-label', 'The assistant is typing');
        for (var i = 0; i < 3; i++) { b.appendChild(el('span')); }
        wrap.appendChild(b);
        log.appendChild(wrap);
        scrollDown();
        return wrap;
    }

    // Product cards: the photo, name and price all come from the shop's database
    function cards(products, parent) {
        products.forEach(function (p) {
            var link = el('a', 'd-flex gap-2 align-items-center p-2 mt-2 border rounded-3 bg-white text-decoration-none text-body sgh-chat-card');
            link.href = safe(p.url) || '#';

            var picture = safe(p.image);
            if (picture) {
                var img = el('img', 'rounded-2 object-fit-cover');
                img.src = picture; img.alt = ''; img.width = 56; img.height = 56; img.loading = 'lazy';
                link.appendChild(img);
            } else {
                var ph = el('span', 'd-inline-flex align-items-center justify-content-center rounded-2 bg-success-subtle text-success');
                ph.style.width = '56px'; ph.style.height = '56px';
                ph.appendChild(el('i', 'bi bi-image'));
                link.appendChild(ph);
            }

            var body = el('span', 'small lh-sm flex-grow-1');
            body.appendChild(el('strong', 'd-block', p.name));
            body.appendChild(el('span', 'text-secondary', p.price));
            link.appendChild(body);
            link.appendChild(el('span', 'btn btn-sm sgh-btn-dark rounded-pill', 'View'));
            parent.appendChild(link);
        });
    }

    function removeChips() {
        log.querySelectorAll('.sgh-chips, #chatStarters').forEach(function (n) { n.remove(); });
    }

    function chips(list) {
        if (!list || !list.length) { return; }
        var box = el('div', 'sgh-chips d-flex flex-wrap gap-2 ms-5 mb-2');
        list.forEach(function (q) {
            var b = el('button', 'btn btn-outline-primary btn-sm rounded-pill', q);
            b.type = 'button';
            b.setAttribute('data-ask', q);
            box.appendChild(b);
        });
        log.appendChild(box);
    }

    function setBusy(flag) { busy = flag; send.disabled = flag; }

    function showAnswer(data) {
        var b = bubble('bot', data.reply);
        if (Array.isArray(data.products) && data.products.length) { cards(data.products, b); }
        if (data.mode === 'simple') { b.appendChild(el('div', 'sgh-bubble-note', 'Simple mode: matched by keywords.')); }
        history.push({ role: 'assistant', text: data.reply });
        save();
        chips(Array.isArray(data.followups) ? data.followups : []);
        scrollDown();
    }

    // Something went wrong: say so in the chat, and take the failed question out of the history
    function problem(message, retryText) {
        history.pop();
        var b = bubble('bot', message);
        if (retryText) {
            var again = el('button', 'btn btn-outline-primary btn-sm rounded-pill mt-1', 'Try again');
            again.type = 'button';
            again.addEventListener('click', function () { again.closest('.sgh-msg').remove(); ask(retryText); });
            b.appendChild(again);
        }
        scrollDown();
    }

    function ask(text) {
        text = String(text).replace(/\s+/g, ' ').trim();
        if (!text || busy) { return; }
        if (text.length > max) { problem('Please keep your question under ' + max + ' characters.', null); return; }

        removeChips();
        bubble('me', text);
        history.push({ role: 'user', text: text });
        setBusy(true);
        var dots = typing();

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message: text, history: history.slice(0, -1).slice(-12) })
        })
            .then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) { return { status: res.status, data: data }; });
            })
            .then(function (r) {
                dots.remove();
                if (r.status === 200 && typeof r.data.reply === 'string') {
                    showAnswer(r.data);
                } else if (r.status === 419) {
                    problem('This page has expired. Please refresh it and try again.', null);
                } else if (r.data && typeof r.data.error === 'string') {
                    problem(r.data.error, null);
                } else {
                    problem('Something went wrong. Please try again.', text);
                }
            })
            .catch(function () {
                dots.remove();
                problem('I could not connect. Please check your internet and try again.', text);
            })
            .then(function () { setBusy(false); input.focus(); });
    }

    // ----- the box: size, counter, Enter to send -----
    function updateBox() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        counter.textContent = input.value.length + ' / ' + max;
    }
    input.addEventListener('input', updateBox);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value;
        input.value = '';
        updateBox();
        ask(text);
    });

    // Clicking a starter or follow-up chip sends it
    log.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-ask]');
        if (chip) { ask(chip.getAttribute('data-ask')); }
    });

    // New chat: remove everything except the greeting
    clearBtn.addEventListener('click', function () {
        Array.prototype.slice.call(log.children).forEach(function (child) {
            if (!child.classList.contains('sgh-keep')) { child.remove(); }
        });
        history = [];
        try { sessionStorage.removeItem(KEY); } catch (e) { /* fine */ }
        input.focus();
    });

    // A reload keeps the conversation (as plain messages) for this tab
    try {
        var saved = JSON.parse(sessionStorage.getItem(KEY) || '[]');
        if (Array.isArray(saved) && saved.length) {
            var starters = document.getElementById('chatStarters');
            if (starters) { starters.remove(); }
            saved.forEach(function (m) {
                if (m && (m.role === 'user' || m.role === 'assistant') && typeof m.text === 'string') {
                    bubble(m.role === 'user' ? 'me' : 'bot', m.text);
                    history.push({ role: m.role, text: m.text });
                }
            });
            scrollDown();
        }
    } catch (e) { /* ignore a damaged saved chat */ }

    updateBox();
})();