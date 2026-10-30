/* Room budget planner: add and remove item rows.
   The numbers are added up by the server. This script only manages the list of rows. */
(function () {
    'use strict';

    var list = document.getElementById('budgetItems');
    var add  = document.getElementById('addItem');
    var note = document.getElementById('itemLimit');
    if (!list || !add) { return; }

    var MAX = 30;
    var currency = list.getAttribute('data-currency') || '';

    function rows() { return list.querySelectorAll('[data-item]'); }

    // Number the rows 0, 1, 2... so the form always sends items[0], items[1]...
    function renumber() {
        rows().forEach(function (row, i) {
            var name = row.querySelector('input[type="text"]');
            var cost = row.querySelector('input[type="number"]');
            var labels = row.querySelectorAll('label');
            name.name = 'items[' + i + '][name]'; name.id = 'item-name-' + i;
            cost.name = 'items[' + i + '][cost]'; cost.id = 'item-cost-' + i;
            if (labels[0]) { labels[0].setAttribute('for', name.id); }
            if (labels[1]) { labels[1].setAttribute('for', cost.id); }
        });
        add.disabled = rows().length >= MAX;
        note.textContent = rows().length >= MAX ? 'You can list up to ' + MAX + ' items.' : '';
    }

    function make(tag, className) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        return node;
    }

    function newRow() {
        var row = make('div', 'sgh-item-row'); row.setAttribute('data-item', '');

        var nameBox = make('div', 'sgh-item-name');
        var nameLabel = make('label', 'visually-hidden'); nameLabel.textContent = 'Item name';
        var name = make('input', 'form-control'); name.type = 'text'; name.maxLength = 60; name.placeholder = 'Item, for example Sofa';
        nameBox.appendChild(nameLabel); nameBox.appendChild(name);

        var costBox = make('div', 'sgh-item-cost');
        var costLabel = make('label', 'visually-hidden'); costLabel.textContent = 'Cost';
        var group = make('div', 'input-group');
        var cost = make('input', 'form-control'); cost.type = 'number'; cost.step = 'any'; cost.min = '0'; cost.placeholder = 'Cost';
        cost.setAttribute('inputmode', 'decimal');
        var unit = make('span', 'input-group-text'); unit.textContent = currency;
        group.appendChild(cost); group.appendChild(unit);
        costBox.appendChild(costLabel); costBox.appendChild(group);

        var remove = make('button', 'sgh-item-remove'); remove.type = 'button';
        remove.setAttribute('data-remove', ''); remove.setAttribute('aria-label', 'Remove this item');
        var x = make('i', 'bi bi-x-lg'); x.setAttribute('aria-hidden', 'true');
        remove.appendChild(x);

        row.appendChild(nameBox); row.appendChild(costBox); row.appendChild(remove);
        return row;
    }

    add.addEventListener('click', function () {
        if (rows().length >= MAX) { return; }
        var row = newRow();
        list.appendChild(row);
        renumber();
        row.querySelector('input').focus();
    });

    list.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove]');
        if (!button) { return; }

        // Always keep one row on the page
        if (rows().length <= 1) {
            rows()[0].querySelectorAll('input').forEach(function (input) { input.value = ''; });
        } else {
            button.closest('[data-item]').remove();
        }
        renumber();

        // Tell the calculator page the numbers changed
        list.dispatchEvent(new Event('input', { bubbles: true }));
    });

    renumber();
})();