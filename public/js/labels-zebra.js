/**
 * VK POS Zebra label module.
 * Preview positions and ZPL both use layout.col* + element offsets.
 * The printer receives server-built ZPL so price and barcode come from the product record.
 */
(function ($) {
    var PRINT_WIDTH = 800;
    var PRINT_LENGTH = 140;
    var cfg = window.ZB_CONFIG || {};
    var settingsKey = cfg.settingsKey || 'vkposBarcodeAlignmentV1';
    var defaults = cfg.defaults || {};
    var state = {
        product: null,
        searchTimer: null,
        searchXhr: null,
        connectPromise: null,
        results: []
    };

    function initialize() {
        renderQueue();
        loadSettings();
        bindEvents();
        updatePreview();
        fitPreview();
        if (cfg.initialVariationId) {
            selectProduct(cfg.initialVariationId);
        }
        connectQzTray().then(getPrinters).catch(function () {
            setStatus('QZ Tray is not running. Please start QZ Tray before printing.', 'off');
        });
    }

    function bindEvents() {
        $('#zb-search').on('input', function () {
            clearTimeout(state.searchTimer);
            var term = $(this).val();
            state.searchTimer = setTimeout(function () {
                loadProducts(term);
            }, 250);
        });

        $('#zb-search').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (state.results.length) {
                    selectProduct(state.results[0].variation_id);
                    $('#zb-results').hide();
                }
            }
        });

        $(document).on('click', '#zb-results button', function () {
            selectProduct($(this).data('id'));
            $('#zb-results').hide();
            $('#zb-search').val('');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.zb-search').length) {
                $('#zb-results').hide();
            }
        });

        $(document).on('click', '#zb-queue button', function () {
            selectProduct($(this).data('id'), {
                quantity: $(this).data('qty'),
                mode: 'individual'
            });
        });

        $('#zb-vertical, #zb-qty, #zb-copies, #zb-mode, #zb-preview-row').on('input change', updatePreview);
        $('.zb-layout, .zb-checks input').on('input change', updatePreview);
        $('#zb-save').on('click', saveSettings);
        $('#zb-reset').on('click', function () {
            applyLayout(defaults);
            updatePreview();
            showMessage('Layout reset to the starting alignment. Save layout to keep it.', 'success');
        });
        $('#zb-printer').on('change', function () {
            rememberPrinter($(this).val());
        });
        $('#zb-detect').on('click', function () {
            connectQzTray().then(getPrinters).catch(function (err) {
                console.error(err);
                setStatus('QZ Tray is not running. Please start QZ Tray before printing.', 'off');
                showMessage('QZ Tray is not running. Please start QZ Tray before printing.', 'danger');
            });
        });
        $('#zb-print').on('click', printLabels);
        $('#zb-test').on('click', testPrint);
        $('#zb-show-zpl').on('click', function () {
            var useTest = !state.product || !state.product.printable;
            fetchZpl(useTest).done(function (res) {
                $('#zb-zpl').val(res.zpl || '');
            });
        });
        $(window).on('resize', fitPreview);
    }

    function renderQueue() {
        var $list = $('#zb-queue');
        if (!$list.length || !cfg.queue) {
            return;
        }
        $list.empty();
        cfg.queue.forEach(function (item) {
            $('<li>').append(
                $('<button type="button" class="btn btn-default btn-sm">')
                    .text(item.name + ' · qty ' + item.quantity)
                    .attr('data-id', item.variation_id)
                    .attr('data-qty', item.quantity)
            ).appendTo($list);
        });
    }

    function loadProducts(term) {
        term = $.trim(term || '');
        if (term.length < 1) {
            $('#zb-results').hide().empty();
            state.results = [];
            return;
        }
        if (state.searchXhr) {
            state.searchXhr.abort();
        }
        state.searchXhr = $.ajax({
            url: cfg.urls.search,
            data: { term: term },
            dataType: 'json'
        }).done(function (rows) {
            state.results = rows || [];
            var $box = $('#zb-results').empty();
            if (!state.results.length) {
                $box.hide();
                return;
            }
            state.results.forEach(function (row) {
                var code = row.product_code && row.product_code !== row.sku ? ' · ' + row.product_code : '';
                $('<button type="button">')
                    .attr('data-id', row.variation_id)
                    .html('<strong></strong><small></small>')
                    .find('strong').text(row.name).end()
                    .find('small').text((row.sku || 'No barcode') + code).end()
                    .appendTo($box);
            });
            $box.show();
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') {
                return;
            }
            showMessage('Product search failed. Try again.', 'danger');
        });
    }

    function selectProduct(variationId, options) {
        options = options || {};
        if (!variationId) {
            return;
        }
        $.ajax({
            url: cfg.urls.product + '/' + variationId,
            dataType: 'json'
        }).done(function (res) {
            if (!res || !res.success || !res.product) {
                showMessage('Product not found.', 'danger');
                return;
            }
            state.product = res.product;
            $('#zb-name').val(res.product.name || '');
            $('#zb-sku').val(res.product.sku || '');
            $('#zb-barcode').val(res.product.barcode || '');
            $('#zb-code').val(res.product.product_code || '');
            $('#zb-price').val(res.product.price_formatted || '');
            $('#zb-vertical').val(res.product.name || '');
            if (options.quantity) {
                $('#zb-qty').val(options.quantity);
            }
            if (options.mode) {
                $('#zb-mode').val(options.mode);
            }
            var $err = $('#zb-product-error');
            if (!res.product.printable) {
                $err.text(res.product.error || 'Selected product does not have a valid barcode.').show();
                $('#zb-print').prop('disabled', true);
            } else {
                $err.hide().text('');
                $('#zb-print').prop('disabled', false);
            }
            updatePreview();
        }).fail(function (xhr) {
            showMessage(ajaxMessage(xhr, 'Could not load that product.'), 'danger');
        });
    }

    function clampInt(value, fallback, min, max) {
        var n = parseFloat(value);
        if (isNaN(n)) {
            n = fallback;
        }
        n = Math.round(n);
        return Math.max(min, Math.min(max, n));
    }

    function clampFloat(value, fallback, min, max) {
        var n = parseFloat(value);
        if (isNaN(n)) {
            n = fallback;
        }
        n = Math.max(min, Math.min(max, n));
        return Math.round(n * 100) / 100;
    }

    function readLayout() {
        var d = defaults;
        return {
            col1X: clampInt($('#zb-col1').val(), d.col1X, 0, 790),
            col2X: clampInt($('#zb-col2').val(), d.col2X, 0, 790),
            col3X: clampInt($('#zb-col3').val(), d.col3X, 0, 790),
            barcode: {
                x: clampInt($('#zb-bc-x').val(), d.barcode.x, -50, 400),
                y: clampInt($('#zb-bc-y').val(), d.barcode.y, 0, 130),
                width: clampFloat($('#zb-bc-w').val(), d.barcode.width, 1, 10),
                height: clampInt($('#zb-bc-h').val(), d.barcode.height, 10, 120)
            },
            sku: {
                x: clampInt($('#zb-sku-x').val(), d.sku.x, -50, 400),
                y: clampInt($('#zb-sku-y').val(), d.sku.y, 0, 130)
            },
            price: {
                x: clampInt($('#zb-price-x').val(), d.price.x, -50, 400),
                y: clampInt($('#zb-price-y').val(), d.price.y, 0, 130)
            },
            vertical: {
                x: clampInt($('#zb-vert-x').val(), d.vertical.x, -20, 400),
                y: clampInt($('#zb-vert-y').val(), d.vertical.y, 0, 130)
            }
        };
    }

    function applyLayout(layout) {
        layout = layout || defaults;
        $('#zb-col1').val(layout.col1X);
        $('#zb-col2').val(layout.col2X);
        $('#zb-col3').val(layout.col3X);
        $('#zb-bc-x').val(layout.barcode.x);
        $('#zb-bc-y').val(layout.barcode.y);
        $('#zb-bc-w').val(layout.barcode.width);
        $('#zb-bc-h').val(layout.barcode.height);
        $('#zb-sku-x').val(layout.sku.x);
        $('#zb-sku-y').val(layout.sku.y);
        $('#zb-price-x').val(layout.price.x);
        $('#zb-price-y').val(layout.price.y);
        $('#zb-vert-x').val(layout.vertical.x);
        $('#zb-vert-y').val(layout.vertical.y);
    }

    function loadSettings() {
        var saved = null;
        try {
            saved = JSON.parse(localStorage.getItem(settingsKey) || 'null');
        } catch (e) {
            saved = null;
        }
        if (!saved || saved.version !== 1 || !saved.layout) {
            applyLayout(defaults);
            return;
        }
        applyLayout(saved.layout);
        if (saved.printMode) {
            $('#zb-mode').val(saved.printMode);
        }
        if (saved.printer) {
            $('#zb-printer').data('preferred', saved.printer);
        }
        $('#zb-price-prefix').prop('checked', !!saved.pricePrefix);
        $('#zb-show-barcode').prop('checked', saved.showBarcode !== false);
        $('#zb-show-sku').prop('checked', saved.showSku !== false);
        $('#zb-show-price').prop('checked', saved.showPrice !== false);
        $('#zb-show-vertical').prop('checked', saved.showVertical !== false);
    }

    function rememberPrinter(name) {
        var saved = null;
        try {
            saved = JSON.parse(localStorage.getItem(settingsKey) || 'null');
        } catch (e) {
            saved = null;
        }
        if (!saved || saved.version !== 1) {
            saved = {
                version: 1,
                layout: readLayout(),
                printMode: $('#zb-mode').val()
            };
        }
        saved.printer = name || '';
        try {
            localStorage.setItem(settingsKey, JSON.stringify(saved));
        } catch (e) {
            console.error(e);
        }
    }

    function saveSettings() {
        var payload = {
            version: 1,
            printer: $('#zb-printer').val() || $('#zb-printer').data('preferred') || '',
            printMode: $('#zb-mode').val(),
            pricePrefix: $('#zb-price-prefix').is(':checked'),
            showBarcode: $('#zb-show-barcode').is(':checked'),
            showSku: $('#zb-show-sku').is(':checked'),
            showPrice: $('#zb-show-price').is(':checked'),
            showVertical: $('#zb-show-vertical').is(':checked'),
            layout: readLayout()
        };
        try {
            localStorage.setItem(settingsKey, JSON.stringify(payload));
            showMessage('Layout saved on this browser.', 'success');
        } catch (e) {
            console.error(e);
            showMessage('Could not save the layout in this browser.', 'danger');
        }
    }

    function currentLabel() {
        var prefix = $('#zb-price-prefix').is(':checked');
        if (state.product) {
            var price = state.product.price_formatted || '';
            return {
                barcode: state.product.barcode || '',
                sku: state.product.sku || '',
                price_text: prefix && price ? ('Price ' + price) : price,
                vertical_text: $('#zb-vertical').val() || '',
                show_barcode: $('#zb-show-barcode').is(':checked'),
                show_sku: $('#zb-show-sku').is(':checked'),
                show_price: $('#zb-show-price').is(':checked'),
                show_vertical: $('#zb-show-vertical').is(':checked'),
                sample: false
            };
        }
        return {
            barcode: 'TEST123456',
            sku: 'TEST123456',
            price_text: 'Price ' + formatMoney(1000),
            vertical_text: $('#zb-vertical').val() || 'TEST LABEL',
            show_barcode: $('#zb-show-barcode').is(':checked'),
            show_sku: $('#zb-show-sku').is(':checked'),
            show_price: $('#zb-show-price').is(':checked'),
            show_vertical: $('#zb-show-vertical').is(':checked'),
            sample: true
        };
    }

    function validateLabelData(label) {
        var barcode = $.trim(label.barcode || '');
        if (!barcode) {
            return 'Selected product does not have a valid barcode.';
        }
        if (barcode.length > 40) {
            return 'Barcode is too long for this label. Use 40 characters or fewer.';
        }
        if (/[\u0000-\u001F\u007F]/.test(barcode) || !/^[\x20-\x7E]+$/.test(barcode)) {
            return 'Barcode contains characters that CODE128 cannot print.';
        }
        if (label.show_price && !$.trim(label.price_text || '')) {
            return 'Selected product does not have a selling price.';
        }
        return null;
    }

    function updatePreview() {
        var layout = readLayout();
        var label = currentLabel();
        var bases = [Number(layout.col1X), Number(layout.col2X), Number(layout.col3X)];
        var slots = previewSlots(label);
        var error = state.product ? validateLabelData(label) : null;
        var notes = [];

        if (!state.product) {
            notes.push('Sample layout. Select a product to preview its barcode and price.');
        }
        if (error) {
            notes.push(error);
        }
        if (label.vertical_text && /[^\x20-\x7E]/.test(label.vertical_text)) {
            notes.push('Vertical text has characters the Zebra built-in font may not print.');
        }
        if (Math.abs(layout.barcode.width - Math.round(layout.barcode.width)) > 0.001) {
            notes.push('Barcode width is sent to Zebra as the ^BY module width. Whole numbers from 1 to 10 are the most reliable.');
        }
        $('#zb-layout-note').text(notes.join(' '));
        $('#zb-job').text(jobSummary());
        $('#zb-preview-caption').text(
            (label.sample ? 'Sample · ' : (state.product.name + ' · ')) + '800 × 140 dots · 203 DPI'
        );

        var showRowPicker = $('#zb-mode').val() === 'individual' && readInt('zb-qty', 1) > 3;
        $('#zb-preview-row-wrap').toggle(showRowPicker);

        if (cfg.debug && $('#zb-zpl').length) {
            $('#zb-zpl').val(generateZpl(expandJob(label), layout));
        }

        $('#zb-stage .zb-sticker').each(function (index) {
            var $sticker = $(this);
            var next = index < 2 ? bases[index + 1] : PRINT_WIDTH;
            var width = Math.max(40, next - bases[index]);
            $sticker.css({ left: bases[index] + 'px', width: width + 'px' });
            var slot = slots[index];
            $sticker.toggleClass('is-empty', !slot);
            paintSticker($sticker, slot, layout, error);
        });

        fitPreview();
    }

    function paintSticker($sticker, slot, layout, error) {
        var $bc = $sticker.find('.zb-bc');
        var $sku = $sticker.find('.zb-sku');
        var $price = $sticker.find('.zb-price');
        var $vert = $sticker.find('.zb-vert');
        $bc.hide().empty();
        $sku.hide().text('');
        $price.hide().text('');
        $vert.hide().text('');
        if (!slot) {
            return;
        }
        if (slot.show_barcode && slot.barcode && !error) {
            try {
                $bc.css({ left: layout.barcode.x + 'px', top: layout.barcode.y + 'px', display: 'block' });
                JsBarcode($bc.get(0), slot.barcode, {
                    format: 'CODE128',
                    width: Number(layout.barcode.width),
                    height: Number(layout.barcode.height),
                    displayValue: false,
                    margin: 0
                });
            } catch (e) {
                console.error(e);
                $bc.hide();
            }
        }
        if (slot.show_sku && slot.barcode) {
            $sku.text(slot.sku || slot.barcode).css({
                left: layout.sku.x + 'px',
                top: layout.sku.y + 'px'
            }).show();
        }
        if (slot.show_price && slot.price_text) {
            $price.text(slot.price_text).css({
                left: layout.price.x + 'px',
                top: layout.price.y + 'px'
            }).show();
        }
        if (slot.show_vertical && slot.vertical_text) {
            $vert.text(slot.vertical_text).css({
                left: layout.vertical.x + 'px',
                top: layout.vertical.y + 'px'
            }).show();
        }
    }

    function previewSlots(label) {
        var qty = readInt('zb-qty', 1);
        var mode = $('#zb-mode').val();
        if (mode !== 'individual') {
            return [label, label, label];
        }
        var count = Math.min(3, qty);
        if ($('#zb-preview-row').val() === 'last' && qty > 3) {
            count = qty % 3 === 0 ? 3 : qty % 3;
        }
        var slots = [null, null, null];
        for (var i = 0; i < count; i++) {
            slots[i] = label;
        }
        return slots;
    }

    function jobSummary() {
        var qty = readInt('zb-qty', 1);
        var copies = readInt('zb-copies', 1);
        var mode = $('#zb-mode').val();
        var labels = (mode === 'individual' ? qty : qty * 3) * copies;
        var rows = (mode === 'individual' ? Math.ceil(qty / 3) : qty) * copies;
        var text = labels + ' label' + (labels === 1 ? '' : 's') + ' · ' + rows + ' row' + (rows === 1 ? '' : 's');
        if (mode === 'individual' && qty % 3 !== 0) {
            text += ' · last row prints ' + (qty % 3) + ' label' + (qty % 3 === 1 ? '' : 's');
        } else if (mode === 'row') {
            text += ' · 3 labels on every row';
        }
        return text;
    }

    function fitPreview() {
        var $scroll = $('.zb-preview-scroll');
        var $stage = $('#zb-stage');
        var $host = $('#zb-scale-host');
        if (!$stage.length || !$scroll.length) {
            return;
        }
        var scale = Math.min(1, Math.max(0.35, ($scroll.innerWidth() - 24) / PRINT_WIDTH));
        $stage.css('transform', 'scale(' + scale + ')');
        $host.css({ width: Math.ceil(PRINT_WIDTH * scale) + 'px', height: Math.ceil(PRINT_LENGTH * scale) + 'px' });
    }

    function connectQzTray() {
        if (typeof qz === 'undefined') {
            return Promise.reject(new Error('QZ Tray library failed to load.'));
        }
        if (qz.websocket.isActive()) {
            setStatus('QZ Tray connected', 'ok');
            return Promise.resolve(true);
        }
        if (state.connectPromise) {
            return state.connectPromise;
        }
        setStatus('Connecting to QZ Tray…', 'warn');
        state.connectPromise = qz.websocket.connect().then(function () {
            state.connectPromise = null;
            setStatus('QZ Tray connected', 'ok');
            return true;
        }).catch(function (err) {
            state.connectPromise = null;
            setStatus('QZ Tray is not running. Please start QZ Tray before printing.', 'off');
            throw err;
        });
        return state.connectPromise;
    }

    function getPrinters() {
        return connectQzTray().then(function () {
            return qz.printers.find();
        }).then(function (list) {
            var names = Array.isArray(list) ? list : (list ? [list] : []);
            var preferred = $('#zb-printer').data('preferred') || $('#zb-printer').val();
            var $select = $('#zb-printer').empty();
            $('<option value="">Select a printer</option>').appendTo($select);
            names.forEach(function (name) {
                $('<option>').val(name).text(name).appendTo($select);
            });
            var match = preferred;
            if (!match || names.indexOf(match) === -1) {
                match = names.filter(function (name) {
                    return /ZD230|ZDesigner/i.test(name);
                })[0] || '';
            }
            if (match) {
                $select.val(match);
                rememberPrinter(match);
            }
            if (!names.length) {
                showMessage('No printers were reported by QZ Tray.', 'warning');
            }
            return names;
        });
    }

    function printLabels() {
        if (!state.product || !state.product.printable) {
            showMessage(state.product && state.product.error ? state.product.error : 'Select a product with a valid barcode.', 'danger');
            return;
        }
        var error = validateLabelData(currentLabel());
        if (error) {
            showMessage(error, 'danger');
            return;
        }
        if (!$('#zb-printer').val()) {
            showMessage('Select a printer before printing.', 'danger');
            return;
        }
        var $btn = $('#zb-print').prop('disabled', true);
        fetchZpl(false).done(function (res) {
            sendToPrinter(res.zpl).then(function () {
                showMessage('Labels sent to the printer.', 'success');
            }).catch(function (err) {
                showMessage(friendlyPrintError(err), 'danger');
            }).then(function () {
                $btn.prop('disabled', false);
            });
        }).fail(function () {
            $btn.prop('disabled', false);
        });
    }

    function testPrint() {
        if (!$('#zb-printer').val()) {
            showMessage('Select a printer before printing.', 'danger');
            return;
        }
        var $btn = $('#zb-test').prop('disabled', true);
        fetchZpl(true).done(function (res) {
            sendToPrinter(res.zpl).then(function () {
                showMessage('Test label sent to the printer.', 'success');
            }).catch(function (err) {
                showMessage(friendlyPrintError(err), 'danger');
            }).then(function () {
                $btn.prop('disabled', false);
            });
        }).fail(function () {
            $btn.prop('disabled', false);
        });
    }

    function fetchZpl(test) {
        var label = currentLabel();
        if (!test) {
            var error = validateLabelData(label);
            if (error) {
                showMessage(error, 'danger');
                return $.Deferred().reject().promise();
            }
        }
        return $.ajax({
            url: test ? cfg.urls.test : cfg.urls.print,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                variation_id: state.product ? state.product.variation_id : null,
                quantity: readInt('zb-qty', 1),
                copies: readInt('zb-copies', 1),
                print_mode: $('#zb-mode').val() === 'individual' ? 'individual' : 'row',
                vertical_text: $('#zb-vertical').val() || '',
                show_barcode: $('#zb-show-barcode').is(':checked'),
                show_sku: $('#zb-show-sku').is(':checked'),
                show_price: $('#zb-show-price').is(':checked'),
                show_vertical: $('#zb-show-vertical').is(':checked'),
                price_prefix: $('#zb-price-prefix').is(':checked'),
                layout: readLayout()
            })
        }).fail(function (xhr) {
            showMessage(ajaxMessage(xhr, 'Could not build the label.'), 'danger');
        });
    }

    function sendToPrinter(zpl) {
        var printer = $('#zb-printer').val();
        if (!printer) {
            return Promise.reject(new Error('Printer not selected'));
        }
        if (!zpl) {
            return Promise.reject(new Error('Empty ZPL'));
        }
        return connectQzTray().then(function () {
            var config = qz.configs.create(printer);
            return qz.print(config, [{
                type: 'raw',
                format: 'command',
                flavor: 'plain',
                data: zpl,
                options: { language: 'ZPL' }
            }]);
        });
    }

    function friendlyPrintError(err) {
        console.error(err);
        var raw = err && err.message ? err.message : '';
        if (/websocket|establish|connection|not running/i.test(raw)) {
            setStatus('QZ Tray is not running. Please start QZ Tray before printing.', 'off');
            return 'QZ Tray is not running. Please start QZ Tray before printing.';
        }
        if (/printer/i.test(raw)) {
            return 'Printer is not available. Select a printer and try again.';
        }
        return 'Could not print the labels. Check QZ Tray and the selected printer.';
    }

    function expandJob(label) {
        var qty = readInt('zb-qty', 1);
        var copies = readInt('zb-copies', 1);
        var mode = $('#zb-mode').val() === 'individual' ? 'individual' : 'row';
        var rows = [];
        if (mode === 'row') {
            for (var r = 0; r < qty; r++) {
                rows.push([label, label, label]);
            }
        } else {
            var remaining = qty;
            while (remaining > 0) {
                var row = [null, null, null];
                for (var c = 0; c < 3 && remaining > 0; c++) {
                    row[c] = label;
                    remaining--;
                }
                rows.push(row);
            }
        }
        var job = [];
        for (var copy = 0; copy < copies; copy++) {
            rows.forEach(function (row) {
                job.push(row);
            });
        }
        return job;
    }

    function generateZpl(rows, layout) {
        var blocks = ['~SD25'];
        rows.forEach(function (slots) {
            blocks.push([
                '^XA',
                '^PW800',
                '^LL140',
                '^LH0,0',
                generateRow(slots, layout),
                '^XZ'
            ].join('\n'));
        });
        return blocks.join('\n') + '\n';
    }

    function generateRow(slots, layout) {
        var bases = [layout.col1X, layout.col2X, layout.col3X];
        var parts = [];
        for (var i = 0; i < 3; i++) {
            if (!slots[i]) {
                continue;
            }
            parts.push(generateLabel(bases[i], slots[i], layout));
        }
        return parts.join('\n');
    }

    function generateLabel(baseX, label, layout) {
        var lines = [];
        var barcode = sanitizeText(label.barcode || label.sku || '', 40);
        baseX = Math.round(Number(baseX) || 0);
        if (label.show_barcode && barcode) {
            var height = Math.round(layout.barcode.height);
            lines.push('^FO' + (baseX + Math.round(layout.barcode.x)) + ',' + Math.round(layout.barcode.y));
            lines.push('^BY' + formatModule(layout.barcode.width) + ',2,' + height);
            lines.push('^BCN,' + height + ',N,N,N');
            lines.push(fieldData(barcode));
        }
        if (label.show_sku && barcode) {
            lines.push('^FO' + (baseX + Math.round(layout.sku.x)) + ',' + Math.round(layout.sku.y));
            lines.push('^A0N,18,14');
            lines.push(fieldData(barcode));
        }
        var price = sanitizeText(label.price_text || '', 40);
        if (label.show_price && price) {
            lines.push('^FO' + (baseX + Math.round(layout.price.x)) + ',' + Math.round(layout.price.y));
            lines.push('^A0N,22,16');
            lines.push(fieldData(price));
        }
        var vertical = sanitizeText(label.vertical_text || '', 80);
        if (label.show_vertical && vertical) {
            lines.push('^FO' + (baseX + Math.round(layout.vertical.x)) + ',' + Math.round(layout.vertical.y));
            lines.push('^A0B,18,14');
            lines.push(fieldData(vertical));
        }
        return lines.join('\n');
    }

    function fieldData(text) {
        var needsHex = false;
        var encoded = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            var ord = text.charCodeAt(i);
            if (ch === '^' || ch === '~' || ch === '\\' || ord < 32 || ord > 126) {
                needsHex = true;
                encoded += '\\' + ord.toString(16).toUpperCase().padStart(2, '0');
            } else {
                encoded += ch;
            }
        }
        if (needsHex) {
            return '^FH\\\n^FD' + encoded + '^FS';
        }
        return '^FD' + encoded + '^FS';
    }

    function formatModule(width) {
        width = Math.max(1, Math.min(10, Number(width) || 1));
        var rounded = Math.round(width);
        if (Math.abs(width - rounded) < 0.001) {
            return String(rounded);
        }
        return String(Math.round(width * 100) / 100);
    }

    function sanitizeText(text, max) {
        text = String(text || '').replace(/[\r\n\t]/g, ' ').replace(/[\u0000-\u001F\u007F]/g, '');
        text = text.replace(/\s+/g, ' ').trim();
        return text.length > max ? text.substring(0, max) : text;
    }

    function formatMoney(amount) {
        var precision = parseInt($('#__precision').val(), 10);
        if (isNaN(precision)) {
            precision = 2;
        }
        var thousand = $('#__thousand').val();
        if (thousand === undefined || thousand === null) {
            thousand = ',';
        }
        var decimal = $('#__decimal').val() || '.';
        var symbol = $('#__symbol').val() || '';
        var placement = $('#__symbol_placement').val();
        var fixed = Math.abs(Number(amount) || 0).toFixed(precision);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
        var number = parts.join(decimal);
        if (!symbol) {
            return printerText(number);
        }
        var formatted = placement === 'after' ? (number + ' ' + symbol) : (symbol + ' ' + number);
        return printerText(formatted);
    }

    function printerText(text) {
        return String(text || '')
            .replace(/₨/g, 'Rs.')
            .replace(/₹/g, 'Rs.')
            .replace(/€/g, 'EUR ')
            .replace(/£/g, 'GBP ')
            .replace(/¥/g, 'JPY ')
            .replace(/[^\x20-\x7E]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function readInt(id, fallback) {
        var value = parseInt($('#' + id).val(), 10);
        if (isNaN(value) || value < 1) {
            return fallback;
        }
        return value;
    }

    function setStatus(text, mode) {
        var $el = $('#zb-status');
        $el.removeClass('is-ok is-warn').addClass(mode === 'ok' ? 'is-ok' : (mode === 'warn' ? 'is-warn' : ''));
        $el.find('span').text(text);
    }

    function showMessage(text, kind) {
        var $alert = $('#zb-alert');
        $alert.removeClass('alert-success alert-danger alert-warning')
            .addClass(kind === 'success' ? 'alert-success' : (kind === 'warning' ? 'alert-warning' : 'alert-danger'))
            .text(text)
            .show();
    }

    function ajaxMessage(xhr, fallback) {
        var body = xhr.responseJSON || {};
        if (body.msg) {
            return body.msg;
        }
        if (body.message && xhr.status === 422 && body.errors) {
            var key = Object.keys(body.errors)[0];
            if (key && body.errors[key] && body.errors[key][0]) {
                return body.errors[key][0];
            }
        }
        console.error(xhr.responseText || xhr.statusText);
        return fallback;
    }

    window.VkBarcodeLabels = {
        initialize: initialize,
        loadSettings: loadSettings,
        saveSettings: saveSettings,
        loadProducts: loadProducts,
        selectProduct: selectProduct,
        updatePreview: updatePreview,
        generateZpl: generateZpl,
        connectQzTray: connectQzTray,
        getPrinters: getPrinters,
        printLabels: printLabels,
        testPrint: testPrint,
        validateLabelData: validateLabelData
    };

    $(initialize);
})(jQuery);
