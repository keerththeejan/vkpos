(function ($) {
    var labels = {};
    var request = null;
    var charts = {};

    function text(value) {
        return value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function money(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return '—';
        }
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(value, true);
        }
        return text(value);
    }

    function qty(value) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(value, false);
        }
        return text(value);
    }

    function card(title, value, href, tip) {
        var link = document.createElement('a');
        link.href = href || '#';
        link.className = 'tw-block tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200 tw-p-4 tw-no-underline';
        var name = document.createElement('div');
        name.className = 'tw-text-sm tw-font-medium tw-text-gray-500';
        name.textContent = title;
        if (tip) {
            name.title = tip;
        }
        var amount = document.createElement('div');
        amount.className = 'tw-mt-1 tw-text-xl tw-font-semibold tw-text-gray-900 tw-font-mono';
        amount.textContent = value;
        link.appendChild(name);
        link.appendChild(amount);
        return link;
    }

    function renderCards(data) {
        var host = document.getElementById('da_cards');
        var links = data.links || {};
        var stock = data.stock || {};
        var sales = data.sales || {};
        var profit = data.profit || {};
        var purchases = data.purchases || {};
        var finance = data.finance || {};
        var currency = data.currency || labels.currency || '';
        host.textContent = '';
        var items = [
            [labels.stockQty, qty(stock.quantity), links.stock, labels.stockQty],
            [labels.stockCost + (currency ? ' (' + currency + ')' : ''), stock.can_value ? money(stock.cost) : '—', links.stock, labels.stockCost],
            [labels.potential + (currency ? ' (' + currency + ')' : ''), stock.can_value ? money(stock.potential_sales) : '—', links.stock, labels.potentialTip],
            [labels.lowStock, text(stock.low_stock), links.low_stock, labels.lowStock],
            [labels.outOfStock, text(stock.out_of_stock), links.stock, labels.outOfStock],
            [labels.negative, text(stock.negative), links.stock, labels.negative],
            [labels.todaySales, money(sales.today), links.sells, labels.todaySales],
            [labels.monthSales, money(sales.month), links.sells, labels.monthSales],
            [labels.periodSales, money(sales.period_net), links.sells, labels.periodSales],
            [labels.saleCount, text(sales.count), links.sells, labels.saleCount],
            [labels.avgSale, money(sales.average), links.sells, labels.avgSale],
            [labels.grossProfit, money(profit.gross_profit), links.profit, labels.profitTip],
            [labels.margin, profit.margin === null ? '—' : text(profit.margin) + '%', links.profit, labels.profitTip],
            [labels.purchasesToday, money(purchases.today), links.purchases, labels.purchasesToday],
            [labels.purchases, money(purchases.period), links.purchases, labels.purchases],
            [labels.purchaseReturns, money(purchases.returns), links.purchases, labels.purchaseReturns],
            [labels.cash, money(finance.cash), links.accounts, labels.cashTip],
            [labels.bank, money(finance.bank), links.accounts, labels.bankTip],
            [labels.customerDue, money(finance.customer_due), links.sells, labels.dueTip],
            [labels.supplierDue, money(finance.supplier_due), links.purchases, labels.dueTip],
            [labels.expenses, money(finance.expenses), links.expenses, labels.expenses]
        ];
        items.forEach(function (item) {
            host.appendChild(card(item[0], item[1], item[2], item[3]));
        });
    }

    function table(headers, rows) {
        var wrap = document.createElement('div');
        wrap.className = 'table-responsive tw-mb-3';
        var el = document.createElement('table');
        el.className = 'table table-condensed table-bordered';
        var head = document.createElement('thead');
        var hr = document.createElement('tr');
        headers.forEach(function (header) {
            var th = document.createElement('th');
            th.textContent = header;
            hr.appendChild(th);
        });
        head.appendChild(hr);
        el.appendChild(head);
        var body = document.createElement('tbody');
        if (!rows.length) {
            var empty = document.createElement('tr');
            var cell = document.createElement('td');
            cell.colSpan = headers.length;
            cell.textContent = '—';
            empty.appendChild(cell);
            body.appendChild(empty);
        }
        rows.forEach(function (row) {
            var tr = document.createElement('tr');
            row.forEach(function (value) {
                var td = document.createElement('td');
                td.textContent = value;
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });
        el.appendChild(body);
        wrap.appendChild(el);
        return wrap;
    }

    function renderLists(data) {
        var stock = data.stock || {};
        var branchHost = document.getElementById('da_branch_table');
        branchHost.textContent = '';
        branchHost.appendChild(table(
            [labels.branch || 'Branch', labels.stockCost || 'Cost'],
            (stock.branches || []).map(function (row) {
                return [row.name, money(row.cost)];
            })
        ));

        var lists = document.getElementById('da_lists');
        lists.textContent = '';
        var topTitle = document.createElement('div');
        topTitle.className = 'tw-text-xs tw-font-semibold tw-text-gray-500 tw-mb-1';
        topTitle.textContent = labels.sales || 'Sales';
        lists.appendChild(topTitle);
        lists.appendChild(table(
            ['', labels.qty || 'Qty', labels.sales || 'Sales'],
            (data.top_products || []).map(function (row) {
                return [row.name, qty(row.qty), money(row.revenue)];
            })
        ));
        lists.appendChild(table(
            [labels.receivables || 'Receivables', ''],
            (data.receivables || []).map(function (row) {
                return [row.name, money(row.due)];
            })
        ));
        lists.appendChild(table(
            [labels.payables || 'Payables', ''],
            (data.payables || []).map(function (row) {
                return [row.name, money(row.due)];
            })
        ));
        if ((stock.low_stock_rows || []).length) {
            lists.appendChild(table(
                [labels.lowStock || 'Low stock', labels.qty || 'Qty'],
                stock.low_stock_rows.map(function (row) {
                    return [row.name + (row.location ? ' — ' + row.location : ''), qty(row.stock) + (row.unit ? ' ' + row.unit : '')];
                })
            ));
        }
    }

    function draw(id, type, labelsAxis, datasets) {
        var canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }
        if (charts[id]) {
            charts[id].destroy();
        }
        try {
            charts[id] = new Chart(canvas.getContext('2d'), {
                type: type,
                data: { labels: labelsAxis, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: true },
                    scales: {
                        yAxes: [{ ticks: { beginAtZero: true } }],
                        xAxes: [{ ticks: { maxTicksLimit: 8 } }]
                    }
                }
            });
        } catch (e) {
            return;
        }
    }

    function renderCharts(data) {
        var chart = data.charts || {};
        var axis = chart.labels || [];
        draw('da_chart_compare', 'line', axis, [
            { label: labels.sales || 'Sales', data: chart.sales || [], borderColor: '#0284c7', backgroundColor: 'rgba(2,132,199,0.08)', fill: false },
            { label: labels.purchase || 'Purchases', data: chart.purchases || [], borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,0.08)', fill: false }
        ]);
        draw('da_chart_profit', 'bar', axis, [
            { label: labels.grossProfit || 'Gross profit', data: chart.profit || [], backgroundColor: '#059669' }
        ]);
        var categories = (data.stock && data.stock.categories) || [];
        draw('da_chart_category', 'bar', categories.map(function (row) { return row.name; }), [
            { label: labels.stockCost || 'Cost', data: categories.map(function (row) { return row.cost; }), backgroundColor: '#0369a1' }
        ]);
    }

    function setAlert(message) {
        var host = document.getElementById('da_alert');
        host.textContent = '';
        if (!message) {
            return;
        }
        var box = document.createElement('div');
        box.className = 'alert alert-danger';
        box.textContent = message;
        host.appendChild(box);
    }

    window.loadDashboardAnalytics = function (start, end) {
        var root = document.getElementById('dashboard_analytics');
        if (!root || !start || !end) {
            return;
        }
        if (request) {
            request.abort();
        }
        setAlert('');
        var params = {
            start: start,
            end: end,
            category_id: $('#da_category').val() || ''
        };
        if ($('#dashboard_location').val()) {
            params.location_id = $('#dashboard_location').val();
        }
        request = $.ajax({
            url: root.getAttribute('data-url'),
            data: params,
            dataType: 'json',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (data) {
            var scope = document.getElementById('da_scope');
            if (scope) {
                scope.textContent = data.category_note || '';
            }
            renderCards(data);
            renderLists(data);
            renderCharts(data);
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') {
                return;
            }
            var message = (xhr.responseJSON && xhr.responseJSON.message) || labels.error || 'The analytics could not be loaded.';
            setAlert(message);
        });
    };

    $(function () {
        var root = document.getElementById('dashboard_analytics');
        if (!root) {
            return;
        }
        try {
            labels = JSON.parse($('#da-labels').text() || '{}');
        } catch (e) {
            labels = {};
        }
        $('#da_category').on('change', function () {
            var picker = $('#dashboard_date_filter').data('daterangepicker');
            if (picker) {
                window.loadDashboardAnalytics(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
                return;
            }
            window.loadDashboardAnalytics(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
        });
        $('.da-preset').on('click', function () {
            var preset = $(this).data('preset');
            var start = moment();
            var end = moment();
            if (preset === 'yesterday') {
                start = moment().subtract(1, 'day');
                end = moment().subtract(1, 'day');
            } else if (preset === 'week') {
                start = moment().startOf('week');
            } else if (preset === 'month') {
                start = moment().startOf('month');
            } else if (preset === '30') {
                start = moment().subtract(29, 'day');
            }
            var picker = $('#dashboard_date_filter').data('daterangepicker');
            if (picker) {
                picker.setStartDate(start);
                picker.setEndDate(end);
                $('#dashboard_date_filter span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                if (typeof update_statistics === 'function') {
                    update_statistics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
                    return;
                }
            }
            window.loadDashboardAnalytics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });
        if (!$('#dashboard_date_filter').length) {
            window.loadDashboardAnalytics(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
        }
    });
})(jQuery);
