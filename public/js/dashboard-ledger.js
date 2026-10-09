(function ($) {
    $(function () {
        var $card = $('#dashboard_sales_ledger_card');
        if (!$card.length) {
            return;
        }

        var labels = {};
        try {
            labels = JSON.parse($('#dashboard-ledger-labels').text() || '{}');
        } catch (e) {
            labels = {};
        }

        var state = {
            page: 1,
            sort: 'date',
            direction: 'asc',
            statementUrl: null
        };
        var request = null;
        var optionsLoaded = false;
        var allAccounts = [];
        var allTypes = [];
        var currency = '';
        var can = {};

        function text(value) {
            return value === null || typeof value === 'undefined' ? '' : String(value);
        }

        function setAlert(kind, message) {
            var host = document.getElementById('dl_alert');
            host.textContent = '';
            if (!message) {
                return;
            }
            var box = document.createElement('div');
            box.className = kind === 'error' ? 'alert alert-danger' : 'alert alert-info';
            box.textContent = message;
            host.appendChild(box);
        }

        function stat(label, value) {
            var col = document.createElement('div');
            col.className = 'col-xs-6 col-sm-4 col-md-3';
            var box = document.createElement('div');
            box.className = 'dl-stat';
            var name = document.createElement('span');
            name.className = 'dl-label';
            name.textContent = label;
            var amount = document.createElement('strong');
            amount.textContent = text(value) || '—';
            box.appendChild(name);
            box.appendChild(amount);
            col.appendChild(box);
            return col;
        }

        function fillSelect($select, rows, placeholder) {
            var current = $select.val();
            $select.empty();
            $select.append($('<option>', { value: '', text: placeholder || labels.all || 'All' }));
            $.each(rows, function (_, row) {
                $select.append($('<option>', { value: row.id, text: row.name }));
            });
            if (current) {
                $select.val(current);
            }
        }

        function typeIds(typeId) {
            if (!typeId) {
                return null;
            }
            var ids = [String(typeId)];
            $.each(allTypes, function (_, type) {
                if (String(type.parent_account_type_id) === String(typeId)) {
                    ids.push(String(type.id));
                }
            });
            return ids;
        }

        function renderAccountOptions() {
            var ids = typeIds($('#dl_account_type').val());
            var rows = allAccounts.filter(function (account) {
                return !ids || ids.indexOf(String(account.account_type_id)) !== -1;
            });
            fillSelect($('#dl_account'), rows, labels.all || 'All');
        }

        function renderTypeOptions() {
            var rows = [];
            $.each(allTypes, function (_, type) {
                if (!type.parent_account_type_id) {
                    rows.push({ id: type.id, name: type.name });
                    $.each(allTypes, function (__, child) {
                        if (String(child.parent_account_type_id) === String(type.id)) {
                            rows.push({ id: child.id, name: '— ' + child.name });
                        }
                    });
                }
            });
            fillSelect($('#dl_account_type'), rows, labels.all || 'All');
        }

        function applyModeVisibility(mode) {
            $card.find('[data-ledger-filter]').each(function () {
                var modes = ($(this).attr('data-ledger-filter') || '').split(/\s+/);
                $(this).toggle(modes.indexOf(mode) !== -1);
            });
        }

        function initContactSelect() {
            var $contact = $('#dl_contact');
            if ($contact.hasClass('select2-hidden-accessible')) {
                return;
            }
            $contact.select2({
                ajax: {
                    url: $card.data('contacts-url'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            type: $('#dashboard_card_mode').val() === 'supplier' ? 'supplier' : 'customer'
                        };
                    },
                    processResults: function (data) {
                        return { results: (data && data.results) || [] };
                    }
                },
                placeholder: labels.contact || 'Contact',
                allowClear: true,
                width: '100%'
            });
        }

        function loadOptions(done) {
            if (optionsLoaded) {
                done();
                return;
            }
            $.ajax({
                url: $card.data('options-url'),
                dataType: 'json',
                headers: { Accept: 'application/json' }
            }).done(function (data) {
                optionsLoaded = true;
                allAccounts = data.accounts || [];
                allTypes = data.account_types || [];
                currency = data.currency || '';
                can = data.can || {};
                renderTypeOptions();
                renderAccountOptions();
                fillSelect($('#dl_location'), data.locations || [], labels.all || 'All');
                fillSelect($('#dl_voucher_type'), data.voucher_types || [], labels.all || 'All');
                done();
            }).fail(function (xhr) {
                setAlert('error', errorMessage(xhr));
            });
        }

        function moneyHeader(label) {
            return currency ? label + ' (' + currency + ')' : label;
        }

        function currentParams() {
            var mode = $('#dashboard_card_mode').val();
            var data = {
                mode: mode,
                start_date: $('#dl_start').val(),
                end_date: $('#dl_end').val(),
                page: state.page,
                per_page: 25,
                sort: state.sort,
                direction: state.direction,
                search: $('#dl_search').val() || ''
            };
            if (mode === 'general' || mode === 'account' || mode === 'cash_bank') {
                data.account_type_id = $('#dl_account_type').val() || '';
                data.account_id = $('#dl_account').val() || '';
                data.voucher_type = $('#dl_voucher_type').val() || '';
                data.voucher_no = $('#dl_voucher_no').val() || '';
            }
            if ($('#dl_location').val()) {
                data.location_id = $('#dl_location').val();
            }
            if ((mode === 'customer' || mode === 'supplier') && $('#dl_contact').val()) {
                data.contact_id = $('#dl_contact').val();
            }
            return data;
        }

        function errorMessage(xhr) {
            if (xhr && xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    return xhr.responseJSON.message;
                }
                if (xhr.responseJSON.errors) {
                    var first = null;
                    $.each(xhr.responseJSON.errors, function (_, messages) {
                        first = messages[0];
                        return false;
                    });
                    if (first) {
                        return first;
                    }
                }
            }
            if (xhr && xhr.status === 403) {
                return 'You do not have permission to view this ledger.';
            }
            return 'The ledger could not be loaded.';
        }

        function renderSummary(data) {
            var host = document.getElementById('dl_summary');
            host.textContent = '';
            var summary = data.summary || {};
            var mode = $('#dashboard_card_mode').val();

            if (summary.contact) {
                host.appendChild(stat(labels.contact || 'Contact', [summary.contact.business_name, summary.contact.name, summary.contact.code].filter(Boolean).join(' — ')));
                host.appendChild(stat(labels.openingDebit || 'Opening', summary.contact.opening));
                if (mode === 'supplier') {
                    host.appendChild(stat(labels.purchases || 'Purchases', summary.contact.purchases));
                } else {
                    host.appendChild(stat(labels.invoices || 'Invoices', summary.contact.invoices));
                }
                host.appendChild(stat(labels.paid || 'Paid', summary.contact.paid));
                host.appendChild(stat(labels.discount || 'Discount', summary.contact.discount));
                host.appendChild(stat(labels.outstanding || 'Outstanding', summary.contact.outstanding));
            } else {
                host.appendChild(stat(labels.openingDebit || 'Opening debit', summary.opening_debit));
                host.appendChild(stat(labels.openingCredit || 'Opening credit', summary.opening_credit));
                host.appendChild(stat(moneyHeader(labels.movementDebit || 'Debit'), summary.movement_debit));
                host.appendChild(stat(moneyHeader(labels.movementCredit || 'Credit'), summary.movement_credit));
                host.appendChild(stat((labels.closing || 'Closing') + ' ' + (labels.closingDebit || 'debit'), summary.closing_debit));
                host.appendChild(stat((labels.closing || 'Closing') + ' ' + (labels.closingCredit || 'credit'), summary.closing_credit));
                host.appendChild(stat(labels.count || 'Transactions', summary.transaction_count));
            }

            var cashHost = document.getElementById('dl_cash_wrap');
            cashHost.textContent = '';
            if (mode === 'cash_bank' && summary.accounts && summary.accounts.length) {
                var table = document.createElement('table');
                table.className = 'table table-bordered table-condensed';
                var head = document.createElement('thead');
                var headRow = document.createElement('tr');
                [labels.account || 'Account', labels.openingDebit || 'Opening', labels.receipts || 'Receipts', labels.payments || 'Payments', labels.transferIn || 'Transfers in', labels.transferOut || 'Transfers out', labels.closing || 'Closing'].forEach(function (title) {
                    var th = document.createElement('th');
                    th.textContent = title;
                    headRow.appendChild(th);
                });
                head.appendChild(headRow);
                table.appendChild(head);
                var body = document.createElement('tbody');
                summary.accounts.forEach(function (account) {
                    var tr = document.createElement('tr');
                    [account.name, account.opening, account.receipts, account.payments, account.transfer_in, account.transfer_out, account.closing].forEach(function (value) {
                        var td = document.createElement('td');
                        td.textContent = text(value);
                        tr.appendChild(td);
                    });
                    body.appendChild(tr);
                });
                table.appendChild(body);
                cashHost.appendChild(table);
            }
        }

        function headerCell(title, sortKey) {
            var th = document.createElement('th');
            th.textContent = title;
            if (sortKey) {
                th.className = 'dl-sort';
                th.setAttribute('data-sort', sortKey);
                if (state.sort === sortKey) {
                    th.textContent = title + (state.direction === 'asc' ? ' ▲' : ' ▼');
                }
            }
            return th;
        }

        function renderTable(data) {
            var table = document.getElementById('dl_table');
            table.textContent = '';
            var mode = $('#dashboard_card_mode').val();
            var accountColumn = mode === 'customer' || mode === 'supplier' ? (labels.type || 'Type') : (labels.account || 'Account');
            var rows = (data.rows || []).slice();
            if (data.opening_row) {
                rows.unshift(data.opening_row);
            }

            var thead = document.createElement('thead');
            var headRow = document.createElement('tr');
            headRow.appendChild(headerCell(labels.date || 'Date', 'date'));
            headRow.appendChild(headerCell(labels.voucher || 'Voucher', 'voucher'));
            headRow.appendChild(headerCell(accountColumn, 'account'));
            headRow.appendChild(headerCell(labels.description || 'Description', 'description'));
            headRow.appendChild(headerCell(moneyHeader(labels.debit || 'Debit'), 'debit'));
            headRow.appendChild(headerCell(moneyHeader(labels.credit || 'Credit'), 'credit'));
            headRow.appendChild(headerCell(labels.balance || 'Running Balance', null));
            headRow.appendChild(headerCell(labels.action || 'Action', null));
            thead.appendChild(headRow);
            table.appendChild(thead);

            var tbody = document.createElement('tbody');
            if (!rows.length) {
                var empty = document.createElement('tr');
                var emptyCell = document.createElement('td');
                emptyCell.colSpan = 8;
                emptyCell.className = 'text-center text-muted';
                emptyCell.textContent = data.message || labels.empty || 'No posted transactions for this period.';
                empty.appendChild(emptyCell);
                tbody.appendChild(empty);
            } else {
                rows.forEach(function (row) {
                    var tr = document.createElement('tr');
                    ['date', 'voucher', 'account', 'description', 'debit', 'credit', 'balance'].forEach(function (key) {
                        var td = document.createElement('td');
                        td.textContent = text(row[key]);
                        tr.appendChild(td);
                    });
                    var action = document.createElement('td');
                    if (row.voucher_url) {
                        var link = document.createElement('a');
                        link.href = '#';
                        link.className = 'btn-modal';
                        link.setAttribute('data-href', row.voucher_url);
                        link.setAttribute('data-container', '.view_modal');
                        link.textContent = labels.view || 'View';
                        action.appendChild(link);
                    }
                    tr.appendChild(action);
                    tbody.appendChild(tr);
                });
            }
            table.appendChild(tbody);

            if (data.summary && (mode === 'general' || mode === 'account' || mode === 'cash_bank')) {
                var tfoot = document.createElement('tfoot');
                var totalRow = document.createElement('tr');
                var labelCell = document.createElement('td');
                labelCell.colSpan = 4;
                labelCell.textContent = labels.count || 'Total';
                totalRow.appendChild(labelCell);
                var debitCell = document.createElement('td');
                debitCell.textContent = text(data.summary.movement_debit);
                var creditCell = document.createElement('td');
                creditCell.textContent = text(data.summary.movement_credit);
                totalRow.appendChild(debitCell);
                totalRow.appendChild(creditCell);
                var rest = document.createElement('td');
                rest.colSpan = 2;
                totalRow.appendChild(rest);
                tfoot.appendChild(totalRow);
                table.appendChild(tfoot);
            }
        }

        function renderPager(pagination) {
            var host = document.getElementById('dl_pager');
            host.textContent = '';
            if (!pagination || !pagination.total) {
                return;
            }
            var from = ((pagination.page - 1) * pagination.per_page) + 1;
            var to = Math.min(pagination.total, pagination.page * pagination.per_page);
            var info = document.createElement('span');
            info.textContent = from + '–' + to + ' / ' + pagination.total;
            host.appendChild(info);

            var controls = document.createElement('div');
            function pageButton(page, title, disabled) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-ml-1';
                button.textContent = title;
                button.disabled = disabled;
                if (!disabled) {
                    button.setAttribute('data-page', page);
                }
                return button;
            }
            controls.appendChild(pageButton(pagination.page - 1, '‹', pagination.page <= 1));
            var start = Math.max(1, pagination.page - 2);
            var end = Math.min(pagination.last_page, start + 4);
            for (var page = start; page <= end; page++) {
                var button = pageButton(page, String(page), page === pagination.page);
                if (page === pagination.page) {
                    button.className += ' tw-dw-btn-active';
                }
                controls.appendChild(button);
            }
            controls.appendChild(pageButton(pagination.page + 1, '›', pagination.page >= pagination.last_page));
            host.appendChild(controls);
        }

        function load() {
            var mode = $('#dashboard_card_mode').val();
            if (mode === 'sales') {
                return;
            }
            if (!$('#dl_start').val() || !$('#dl_end').val()) {
                setAlert('error', 'Choose a from date and a to date.');
                return;
            }
            if ((mode === 'general' || mode === 'account' || mode === 'cash_bank') && can.account === false) {
                setAlert('error', 'You do not have permission to view account ledgers.');
                return;
            }
            if (mode === 'customer' && can.customer === false) {
                setAlert('error', 'You do not have permission to view customer ledgers.');
                return;
            }
            if (mode === 'supplier' && can.supplier === false) {
                setAlert('error', 'You do not have permission to view supplier ledgers.');
                return;
            }

            if (request) {
                request.abort();
            }
            setAlert('info', 'Loading…');
            $('#dl_apply, #dl_excel, #dl_print, #dl_pdf').prop('disabled', true);
            request = $.ajax({
                url: $card.data('ledger-url'),
                data: currentParams(),
                dataType: 'json',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (data) {
                state.statementUrl = data.statement_url || null;
                document.getElementById('dl_note').textContent = [data.note, data.filter_note].filter(Boolean).join(' ');
                var kind = data.status === 'error' ? 'error' : (data.status === 'ok' ? null : 'info');
                setAlert(kind, data.status === 'ok' ? '' : (data.message || ''));
                renderSummary(data);
                renderTable(data);
                renderPager(data.pagination);
            }).fail(function (xhr) {
                if (xhr.statusText === 'abort') {
                    return;
                }
                setAlert('error', errorMessage(xhr));
            }).always(function () {
                $('#dl_apply, #dl_excel, #dl_print, #dl_pdf').prop('disabled', false);
            });
        }

        function showLedger() {
            var mode = $('#dashboard_card_mode').val();
            $('#dashboard_sales_card_title').text($('#dashboard_card_mode option:selected').text());
            if (mode === 'sales') {
                $('#dashboard_ledger_panel').addClass('tw-hidden');
                $('#dashboard_sales_chart_panel').removeClass('tw-hidden');
                $(window).trigger('resize');
                return;
            }
            $('#dashboard_sales_chart_panel').addClass('tw-hidden');
            $('#dashboard_ledger_panel').removeClass('tw-hidden');
            applyModeVisibility(mode);
            if (mode === 'customer' || mode === 'supplier') {
                initContactSelect();
            }
            loadOptions(function () {
                state.page = 1;
                state.sort = 'date';
                state.direction = 'asc';
                load();
            });
        }

        $('#dashboard_card_mode').on('change', function () {
            if ($(this).val() === 'customer' || $(this).val() === 'supplier') {
                $('#dl_contact').val(null).trigger('change.select2');
            }
            showLedger();
        });

        $('#dl_apply').on('click', function () {
            state.page = 1;
            load();
        });

        $('#dl_search, #dl_voucher_no').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                state.page = 1;
                load();
            }
        });

        $('#dl_account_type').on('change', renderAccountOptions);

        $('#dl_table').on('click', 'th[data-sort]', function () {
            var sort = $(this).data('sort');
            if (state.sort === sort) {
                state.direction = state.direction === 'asc' ? 'desc' : 'asc';
            } else {
                state.sort = sort;
                state.direction = 'asc';
            }
            state.page = 1;
            load();
        });

        $('#dl_pager').on('click', 'button[data-page]', function () {
            state.page = parseInt($(this).attr('data-page'), 10) || 1;
            load();
        });

        $('#dl_excel').on('click', function () {
            var query = currentParams();
            query.export = 'csv';
            query.page = 1;
            window.open($card.data('ledger-url') + '?' + $.param(query), '_blank', 'noopener');
        });

        $('#dl_pdf').on('click', function () {
            if (!state.statementUrl) {
                setAlert('info', labels.empty || 'Select a contact and load the statement first.');
                return;
            }
            window.open(state.statementUrl, '_blank', 'noopener');
        });

        $('#dl_print').on('click', function () {
            var printWindow = window.open('', '_blank', 'noopener');
            if (!printWindow) {
                return;
            }
            var title = document.getElementById('dashboard_sales_card_title').textContent;
            printWindow.document.write('<!DOCTYPE html><html><head><title>' + $('<div>').text(title).html() + '</title>');
            printWindow.document.write('<style>body{font-family:Arial,sans-serif;font-size:12px;color:#111}h1{font-size:18px}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;vertical-align:top}th{background:#f3f4f6}.dl-stat{display:inline-block;min-width:140px;margin:0 8px 8px 0;padding:6px 8px;border:1px solid #ddd}</style>');
            printWindow.document.write('</head><body><h1></h1><div id="summary"></div><div id="cash"></div><div id="table"></div></body></html>');
            printWindow.document.close();
            printWindow.document.querySelector('h1').textContent = title;
            printWindow.document.getElementById('summary').innerHTML = document.getElementById('dl_summary').innerHTML;
            printWindow.document.getElementById('cash').innerHTML = document.getElementById('dl_cash_wrap').innerHTML;
            printWindow.document.getElementById('table').innerHTML = document.getElementById('dl_table_wrap').innerHTML;
            printWindow.focus();
            printWindow.print();
        });
    });
})(jQuery);
