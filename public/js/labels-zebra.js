(function ($) {
    var boot = window.ZL_BOOT || { ready: false, profiles: [], defaults: {}, urls: {}, queue: [] };
    var state = {
        profiles: boot.profiles || [],
        profileId: null,
        product: null,
        printing: false,
        barcodeKey: '',
        qzLoading: null,
        skuEditing: false,
        skuDrafts: {},
        masterLayout: null
    };

    var alignKeys = [
        'col1_x', 'col2_x', 'col3_x',
        'barcode_x', 'barcode_y', 'barcode_width', 'barcode_height',
        'sku_x', 'sku_y', 'sku_font_size', 'sku_font_weight',
        'price_x', 'price_y', 'price_font_size',
        'vertical_x', 'vertical_y', 'vertical_font_size',
        'product_name_x', 'product_name_y', 'product_name_font_size', 'product_name_font_width',
        'product_name_max_width', 'product_name_max_lines'
    ];

    function byId(id) {
        return document.getElementById(id);
    }

    function setStatus(message, kind) {
        var el = byId('zl_status');
        if (!el) {
            return;
        }
        el.textContent = message || '';
        el.classList.toggle('is-on', !!message);
        el.classList.toggle('is-ok', kind === 'ok');
        el.classList.toggle('is-error', kind === 'error');
    }

    function readError(xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }
        if (xhr && (xhr.status === 401 || xhr.status === 419)) {
            return 'Your session has expired. Sign in again.';
        }
        if (xhr && xhr.status === 403) {
            return 'You are not allowed to print labels.';
        }
        return 'Unable to prepare the label. Check the product and layout, then try again.';
    }

    function ask(message) {
        if (typeof swal === 'function') {
            return swal({
                title: 'Please confirm',
                text: message,
                icon: 'warning',
                buttons: ['Cancel', 'Continue'],
                dangerMode: true
            });
        }
        return Promise.resolve(window.confirm(message));
    }

    function num(id) {
        var node = byId(id);
        var n = node ? parseFloat(node.value) : 0;
        return Number.isFinite(n) ? n : 0;
    }

    function text(id) {
        var node = byId(id);
        return node ? node.value : '';
    }

    function setVal(id, value) {
        var node = byId(id);
        if (node && value !== undefined && value !== null) {
            node.value = value;
        }
    }

    function readLayout() {
        return {
            printer_name: text('zl_printer_name').trim(),
            printer_dpi: text('zl_printer_dpi'),
            label_gap_mm: num('zl_label_gap_mm'),
            top_offset_mm: num('zl_top_offset_mm'),
            left_offset_mm: num('zl_left_offset_mm'),
            width: num('zl_width'),
            height: num('zl_height'),
            col1_x: num('zl_col1_x'),
            col2_x: num('zl_col2_x'),
            col3_x: num('zl_col3_x'),
            barcode_x: num('zl_barcode_x'),
            barcode_y: num('zl_barcode_y'),
            barcode_width: num('zl_barcode_width'),
            barcode_height: num('zl_barcode_height'),
            sku_x: num('zl_sku_x'),
            sku_y: num('zl_sku_y'),
            sku_font_size: num('zl_sku_font_size'),
            sku_font_weight: text('zl_sku_font_weight') || 'bold',
            sku_align: text('zl_sku_align') || 'center',
            price_x: num('zl_price_x'),
            price_y: num('zl_price_y'),
            price_font_size: num('zl_price_font_size'),
            price_align: text('zl_price_align') || 'center',
            vertical_text: text('zl_vertical').trim(),
            vertical_x: num('zl_vertical_x'),
            vertical_y: num('zl_vertical_y'),
            vertical_font_size: num('zl_vertical_font_size'),
            product_name_x: num('zl_product_name_x'),
            product_name_y: num('zl_product_name_y'),
            product_name_font_size: num('zl_product_name_font_size'),
            product_name_font_width: num('zl_product_name_font_width'),
            product_name_font_weight: text('zl_product_name_font_weight') || 'bold',
            product_name_max_width: num('zl_product_name_max_width'),
            product_name_max_lines: num('zl_product_name_max_lines'),
            product_name_align: text('zl_product_name_align') || 'center',
            show_product_name: byId('zl_product_name_show') && byId('zl_product_name_show').checked ? 1 : 0,
            show_barcode: !(byId('zl_show_barcode') && !byId('zl_show_barcode').checked) ? 1 : 0,
            show_sku: !(byId('zl_show_sku') && !byId('zl_show_sku').checked) ? 1 : 0,
            show_price: !(byId('zl_show_price') && !byId('zl_show_price').checked) ? 1 : 0,
            product_name_wrap: byId('zl_product_name_wrap') && byId('zl_product_name_wrap').checked ? 1 : 0
        };
    }

    function cloneLayout() {
        return JSON.parse(JSON.stringify(readLayout()));
    }

    function layoutToken(key, value) {
        var textKeys = {
            printer_name: true,
            vertical_text: true,
            sku_font_weight: true,
            sku_align: true,
            price_align: true,
            product_name_font_weight: true,
            product_name_align: true
        };
        if (textKeys[key]) {
            return String(value || '');
        }
        var number = Number(value);
        if (Number.isFinite(number)) {
            return String(Math.round(number * 1000) / 1000);
        }
        return String(value);
    }

    function layoutSignature(layout) {
        return Object.keys(layout || {}).sort().map(function (key) {
            return key + '=' + layoutToken(key, layout[key]);
        }).join('|');
    }

    function writeLayout(layout) {
        if (!layout) {
            return;
        }
        setVal('zl_printer_name', layout.printer_name);
        setVal('zl_printer_dpi', layout.printer_dpi);
        setVal('zl_label_gap_mm', layout.label_gap_mm);
        setVal('zl_top_offset_mm', layout.top_offset_mm);
        setVal('zl_left_offset_mm', layout.left_offset_mm);
        setVal('zl_width', layout.width);
        setVal('zl_height', layout.height);
        alignKeys.forEach(function (key) {
            setVal('zl_' + key, layout[key]);
        });
        setVal('zl_sku_font_weight', layout.sku_font_weight || 'bold');
        setVal('zl_sku_align', layout.sku_align || 'center');
        setVal('zl_price_align', layout.price_align || 'center');
        setVal('zl_product_name_font_weight', layout.product_name_font_weight || 'bold');
        setVal('zl_product_name_align', layout.product_name_align || 'center');
        setChecked('zl_product_name_show', layout.show_product_name);
        setChecked('zl_show_barcode', layout.show_barcode !== 0 && layout.show_barcode !== false);
        setChecked('zl_show_sku', layout.show_sku !== 0 && layout.show_sku !== false);
        setChecked('zl_show_price', layout.show_price !== 0 && layout.show_price !== false);
        setChecked('zl_product_name_wrap', layout.product_name_wrap);
        setVal('zl_vertical', layout.vertical_text || '');
    }

    function syncTemporaryMode() {
        var banner = byId('zl_temporary_mode');
        if (!banner) {
            return;
        }
        var dirty = !!(state.masterLayout && layoutSignature(readLayout()) !== layoutSignature(state.masterLayout));
        banner.hidden = !dirty;
    }

    function captureMaster() {
        state.masterLayout = cloneLayout();
        syncTemporaryMode();
    }

    function discardTemporary() {
        if (!state.masterLayout) {
            return;
        }
        writeLayout(JSON.parse(JSON.stringify(state.masterLayout)));
        state.barcodeKey = '';
        updatePreview();
        syncTemporaryMode();
        setStatus('Temporary edits discarded. The saved label is unchanged.', 'ok');
    }

    function setChecked(id, value) {
        var node = byId(id);
        if (!node) {
            return;
        }
        node.checked = value === true || value === 1 || value === '1';
    }

    function profileValue(profile, key) {
        var value = profile ? profile[key] : undefined;
        if (value === undefined || value === null || value === '') {
            value = (boot.defaults || {})[key];
        }
        return value;
    }

    function applyProfile(profile, keepVertical) {
        if (!profile) {
            return;
        }
        state.profileId = profile.id || null;
        setVal('zl_profile_name', profile.name || '');
        setVal('zl_printer_name', profile.printer_name);
        setVal('zl_printer_dpi', profile.printer_dpi);
        setVal('zl_label_gap_mm', profileValue(profile, 'label_gap_mm'));
        setVal('zl_top_offset_mm', profileValue(profile, 'top_offset_mm'));
        setVal('zl_left_offset_mm', profileValue(profile, 'left_offset_mm'));
        setVal('zl_width', profile.width);
        setVal('zl_height', profile.height);
        alignKeys.forEach(function (key) {
            setVal('zl_' + key, profileValue(profile, key));
        });
        setVal('zl_sku_align', profileValue(profile, 'sku_align') || 'center');
        setVal('zl_price_align', profileValue(profile, 'price_align') || 'center');
        setVal('zl_product_name_font_weight', profileValue(profile, 'product_name_font_weight') || 'bold');
        setVal('zl_product_name_align', profileValue(profile, 'product_name_align') || 'center');
        var showName = profile.show_product_name;
        if (showName === undefined || showName === null) {
            showName = profileValue(boot.defaults || {}, 'show_product_name');
            if (showName === undefined || showName === null) {
                showName = true;
            }
        }
        setChecked('zl_product_name_show', showName);
        setChecked('zl_show_barcode', profileValue(profile, 'show_barcode') !== 0 && profileValue(profile, 'show_barcode') !== false);
        setChecked('zl_show_sku', profileValue(profile, 'show_sku') !== 0 && profileValue(profile, 'show_sku') !== false);
        setChecked('zl_show_price', profileValue(profile, 'show_price') !== 0 && profileValue(profile, 'show_price') !== false);
        setChecked('zl_product_name_wrap', profileValue(profile, 'product_name_wrap'));
        if (isLegacyProfile(profile)) {
            applyMediaStack();
            setVal('zl_barcode_x', 0);
            setVal('zl_barcode_width', (boot.media && boot.media.barcode_module_width) || 1);
        }
        if (!keepVertical && profile.vertical_text) {
            setVal('zl_vertical', profile.vertical_text);
        }
        fillProfiles();
        updateQuantitySummary();
        updatePreview();
        captureMaster();
    }

    function fillProfiles() {
        var sel = byId('zl_profile');
        if (!sel) {
            return;
        }
        var current = state.profileId ? String(state.profileId) : sel.value;
        sel.innerHTML = '';
        state.profiles.forEach(function (profile) {
            var opt = document.createElement('option');
            opt.value = profile.id;
            opt.textContent = profile.name + (profile.is_default ? ' (default)' : '');
            if (String(profile.id) === String(current)) {
                opt.selected = true;
            }
            sel.appendChild(opt);
        });
    }

    function renderQueue() {
        var host = byId('zl_queue');
        if (!host) {
            return;
        }
        host.innerHTML = '';
        (boot.queue || []).forEach(function (item) {
            if (!item.variation_id) {
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = item.name || ('Product ' + item.variation_id);
            btn.addEventListener('click', function () {
                loadProduct(item.variation_id, item);
            });
            host.appendChild(btn);
        });
    }

    function productKey(product) {
        if (!product || product.product_id === undefined || product.product_id === null || product.product_id === '') {
            return '';
        }
        return String(product.product_id);
    }

    function storedDraft(product) {
        var key = productKey(product);
        if (!key || !state.skuDrafts[key]) {
            return '';
        }
        return String(state.skuDrafts[key]).trim();
    }

    function displaySku(product) {
        var draft = storedDraft(product);
        if (draft) {
            return draft;
        }
        return product && product.barcode ? String(product.barcode) : '';
    }

    function activePrintSku() {
        if (state.skuEditing) {
            var input = byId('zl_sku_input');
            var live = input ? input.value.trim() : '';
            if (live) {
                return live;
            }
            return state.product && state.product.barcode ? String(state.product.barcode) : '';
        }
        return displaySku(state.product);
    }

    function closeSkuEditor() {
        state.skuEditing = false;
        var fact = byId('zl_sku_fact');
        var view = byId('zl_sku_view');
        var editor = byId('zl_sku_editor');
        if (fact) {
            fact.classList.remove('is-editing');
        }
        if (view) {
            view.hidden = false;
        }
        if (editor) {
            editor.hidden = true;
        }
    }

    function syncSkuEditButton() {
        var btn = byId('zl_sku_edit');
        if (!btn) {
            return;
        }
        btn.hidden = !(state.product && state.product.variation_id);
    }

    function openSkuEditor() {
        if (!state.product || !state.product.variation_id || state.skuEditing) {
            return;
        }
        var input = byId('zl_sku_input');
        var fact = byId('zl_sku_fact');
        var view = byId('zl_sku_view');
        var editor = byId('zl_sku_editor');
        if (!input || !editor) {
            return;
        }
        input.value = displaySku(state.product);
        state.skuEditing = true;
        if (fact) {
            fact.classList.add('is-editing');
        }
        if (view) {
            view.hidden = true;
        }
        editor.hidden = false;
        input.focus();
        input.select();
        if (typeof input.setSelectionRange === 'function') {
            input.setSelectionRange(0, input.value.length);
        }
    }

    function commitSkuDraft() {
        if (!state.skuEditing || !state.product) {
            return;
        }
        var input = byId('zl_sku_input');
        var value = input ? input.value.trim() : '';
        var key = productKey(state.product);
        var original = state.product.barcode ? String(state.product.barcode).trim() : '';
        if (key) {
            if (value === '' || value === original) {
                delete state.skuDrafts[key];
            } else {
                state.skuDrafts[key] = value;
            }
        }
        state.barcodeKey = '';
        closeSkuEditor();
        renderProduct(false);
        updatePreview();
    }

    function renderProduct(setVertical) {
        if (state.skuEditing) {
            closeSkuEditor();
        }
        var product = state.product;
        var sku = displaySku(product);
        byId('zl_product_name').textContent = product ? product.name : 'No product selected';
        byId('zl_product_sku').textContent = sku || '—';
        syncSkuEditButton();
        byId('zl_product_price').textContent = product && product.price ? product.price : '—';
        var bits = [];
        if (product) {
            bits.push('ID ' + product.product_id);
            if (product.lot_number) {
                bits.push('Batch ' + product.lot_number);
            }
            if (product.exp_date) {
                bits.push('Expiry ' + product.exp_date);
            }
            if (product.purchase_quantity) {
                bits.push('Purchase qty ' + product.purchase_quantity);
            }
        }
        byId('zl_product_meta').textContent = bits.length ? bits.join(' · ') : 'Search to load a product from VKPOS.';
        if (product && setVertical) {
            setVal('zl_vertical', (product.name || '').slice(0, 80));
        }
        byId('zl_product_card').classList.toggle('is-missing', !!(product && !sku));
    }

    function loadProduct(variationId, extras, setVertical) {
        if (!variationId || String(variationId) === '0') {
            setStatus('Select the product variation, not the group heading.', 'error');
            return;
        }
        $.get(boot.urls.product + '/' + encodeURIComponent(variationId), {
            price_group_id: text('zl_price_group')
        }).done(function (res) {
            if (!res || !res.success || !res.product) {
                setStatus((res && res.message) || 'The selected product could not be loaded.', 'error');
                return;
            }
            if (state.skuEditing) {
                commitSkuDraft();
            }
            state.product = res.product;
            state.barcodeKey = '';
            if (extras) {
                state.product.lot_number = extras.lot_number || null;
                state.product.exp_date = extras.exp_date || null;
                state.product.purchase_quantity = extras.quantity || null;
            }
            renderProduct(setVertical !== false);
            updatePreview();
            setStatus(displaySku(state.product) ? 'Product loaded from VKPOS.' : 'Barcode is missing for this product.', displaySku(state.product) ? 'ok' : 'error');
        }).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function updateQuantitySummary() {
        var el = byId('zl_qty_summary');
        var qty = parseInt(text('zl_qty'), 10);
        if (!el) {
            return;
        }
        if (!Number.isInteger(qty) || qty < 1 || qty > 100 || String(text('zl_qty')).indexOf('.') !== -1) {
            el.textContent = 'Enter a whole number of rows from 1 to 100. Each row prints 3 labels.';
            el.classList.add('is-warn');
            return;
        }
        el.classList.remove('is-warn');
        var labels = qty * 3;
        el.textContent = qty + (qty === 1 ? ' row' : ' rows') + ' × 3 labels = ' + labels + ' physical labels';
    }

    function charsOf(value) {
        return Array.from(String(value || ''));
    }

    function displayProductName(name, layout) {
        var list = charsOf(name).slice(0, 120);
        name = list.join('');
        if (layout.product_name_wrap) {
            return name;
        }
        var fontWidth = Math.max(1, layout.product_name_font_width || layout.product_name_font_size || 20);
        var maxChars = Math.max(1, Math.floor((layout.product_name_max_width || 220) / fontWidth));
        if (list.length <= maxChars) {
            return name;
        }
        if (maxChars <= 3) {
            return list.slice(0, maxChars).join('');
        }
        return list.slice(0, maxChars - 3).join('').replace(/\s+$/, '') + '...';
    }

    function namePlacement(base, layout) {
        var size = layout.product_name_font_size || 20;
        var fontWidth = layout.product_name_font_width || size;
        var ratio = size > 0 ? fontWidth / size : 1;
        if (!Number.isFinite(ratio) || ratio <= 0) {
            ratio = 1;
        }
        var maxWidth = layout.product_name_max_width || 220;
        var layoutWidth = maxWidth / ratio;
        var align = layout.product_name_align || 'center';
        var left = base + layout.product_name_x;
        var origin = 'left top';
        if (align === 'center') {
            origin = 'center top';
            left -= (layoutWidth - maxWidth) / 2;
        } else if (align === 'right') {
            origin = 'right top';
            left += maxWidth - layoutWidth;
        }
        return { left: left, width: layoutWidth, origin: origin, ratio: ratio, maxWidth: maxWidth };
    }

    function fieldX(value, labelWidth) {
        var n = Number(value);
        if (!Number.isFinite(n)) {
            n = 4;
        }
        return Math.max(0, Math.min(Math.max(0, labelWidth - 8), n));
    }

    function fieldAlign(value) {
        return value === 'left' || value === 'right' || value === 'center' ? value : 'center';
    }

    function estimateText(value, size) {
        return Math.max(size, String(value || '').length * size * 0.55);
    }

    function estimateBarcodeWidth(value, module) {
        var chars = Math.max(1, String(value || '').length);
        return (11 * chars + 35) * (module || 1);
    }

    function outside(x, y, w, h, col, width, height) {
        var slice = Math.floor(width / 3);
        var left = col * slice;
        var right = col === 2 ? width : (col + 1) * slice;
        return x < left - 1 || y < -1 || (x + w) > right + 1 || (y + h) > height + 1;
    }

    function showWarnings(messages) {
        var box = byId('zl_warnings');
        if (!box) {
            return;
        }
        box.innerHTML = '';
        if (!messages.length) {
            box.hidden = true;
            return;
        }
        messages.forEach(function (message) {
            var p = document.createElement('p');
            p.textContent = message;
            box.appendChild(p);
        });
        var note = document.createElement('p');
        note.textContent = 'You can still print if you are testing this alignment.';
        box.appendChild(note);
        box.hidden = false;
    }

    function mmToDots(mm, dpi) {
        var negative = Number(mm) < 0;
        var usedDpi = dpi || parseInt(text('zl_printer_dpi'), 10) || (boot.media && boot.media.dpi) || 203;
        var dots = Math.round(Math.abs(Number(mm) || 0) * usedDpi / 25.4);
        return negative ? -dots : dots;
    }

    function mediaDots() {
        var media = boot.media || {};
        var dpi = parseInt(text('zl_printer_dpi'), 10) || media.dpi || 203;
        var columns = media.label_columns || 3;
        var labelW = mmToDots(media.label_width_mm || 30, dpi);
        var labelH = mmToDots(media.label_height_mm || 15, dpi);
        var gap = mmToDots(num('zl_label_gap_mm'), dpi);
        var left = mmToDots(num('zl_left_offset_mm'), dpi);
        var top = mmToDots(num('zl_top_offset_mm'), dpi);
        var origins = [];
        var i;
        for (i = 0; i < columns; i++) {
            origins.push(i * (labelW + gap));
        }
        var span = (columns * labelW) + (Math.max(0, columns - 1) * gap);
        return {
            dpi: dpi,
            labelWidth: labelW,
            labelHeight: labelH,
            gap: gap,
            left: left,
            top: top,
            origins: origins,
            printWidth: span + Math.max(0, left),
            labelLength: labelH
        };
    }

    function isLegacyProfile(profile) {
        if (!profile) {
            return false;
        }
        return Number(profile.width) === 800 && Number(profile.height) === 140;
    }

    function applyMediaStack() {
        var media = boot.media || {};
        var stack = media.stack || {};
        setVal('zl_barcode_y', stack.barcode_y);
        setVal('zl_barcode_height', stack.barcode_height || media.barcode_height);
        setVal('zl_sku_y', stack.sku_y);
        setVal('zl_sku_font_size', stack.sku_font_size || media.sku_font_height);
        setVal('zl_price_y', stack.price_y);
        setVal('zl_price_font_size', stack.price_font_size || media.price_font_height);
        setVal('zl_product_name_y', stack.product_name_y);
        setVal('zl_product_name_font_size', stack.product_name_font_size || media.name_font_height);
        setVal('zl_product_name_font_width', stack.product_name_font_width || media.name_font_width);
        setVal('zl_product_name_max_width', stack.product_name_max_width || ((media.label_width || 240) - 8));
        setVal('zl_sku_x', 4);
        setVal('zl_price_x', 4);
        setVal('zl_product_name_x', 4);
    }

    function clampBarcodeHeight(value, barcodeY) {
        var labelH = mediaDots().labelHeight || 120;
        var y = Math.max(0, Number(barcodeY) || 0);
        var maxH = Math.max(10, labelH - y);
        var height = Math.round(parseFloat(value) || 10);
        return Math.max(10, Math.min(maxH, height));
    }

    function syncGeometryFields() {
        var m = mediaDots();
        setVal('zl_width', m.printWidth);
        setVal('zl_height', m.labelLength);
        setVal('zl_col1_x', m.origins[0]);
        setVal('zl_col2_x', m.origins[1]);
        setVal('zl_col3_x', m.origins[2]);
        var summary = byId('zl_geometry_summary');
        if (summary) {
            summary.textContent = '^PW' + m.printWidth + '   ^LL' + m.labelLength
                + '   labels at ' + m.origins.join(', ')
                + '   gap ' + m.gap + ' dots';
        }
        return m;
    }

    function drawGuides(layout) {
        var canvas = byId('zl_canvas');
        var width = layout.width;
        var height = layout.height;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        canvas.classList.toggle('is-coords', byId('zl_show_coords').checked);
        canvas.classList.toggle('is-bounds-off', !byId('zl_show_bounds').checked);

        var grid = document.createElement('div');
        grid.className = 'zl-grid';
        grid.hidden = !byId('zl_show_grid').checked;
        var x;
        for (x = 0; x <= width; x += 50) {
            var v = document.createElement('div');
            v.className = 'zl-grid__v';
            v.style.left = x + 'px';
            var vl = document.createElement('span');
            vl.textContent = String(x);
            v.appendChild(vl);
            grid.appendChild(v);
        }
        var y;
        for (y = 0; y <= height; y += 20) {
            var h = document.createElement('div');
            h.className = 'zl-grid__h';
            h.style.top = y + 'px';
            var hl = document.createElement('span');
            hl.textContent = String(y);
            h.appendChild(hl);
            grid.appendChild(h);
        }

        var bounds = document.createElement('div');
        var slice = Math.floor(width / 3);
        var i;
        for (i = 0; i < 3; i++) {
            var bound = document.createElement('div');
            bound.className = 'zl-bound';
            bound.style.left = (i * slice) + 'px';
            bound.style.width = (i === 2 ? width - (i * slice) : slice) + 'px';
            var tag = document.createElement('div');
            tag.className = 'zl-bound__tag';
            tag.textContent = 'LABEL ' + (i + 1);
            bound.appendChild(tag);
            bounds.appendChild(bound);
        }

        return { grid: grid, bounds: bounds };
    }

    function nameStyle(layout, media, name) {
        var x = fieldX(layout.product_name_x, media.labelWidth);
        var y = Number(layout.product_name_y) || 0;
        var size = Number(layout.product_name_font_size) || 16;
        var width = Number(layout.product_name_font_width) || size;
        var inner = Math.max(8, media.labelWidth - x - 2);
        var length = Math.max(1, Array.from(String(name || '')).length);
        var lines = 1;
        if (width < 8 || size < 14) {
            size = Math.max(size, 16);
            width = Math.max(width, 12);
        }
        if (length * width > inner) {
            var room = Math.max(0, media.labelHeight - y - 2);
            if (room >= 28) {
                lines = 2;
                if (size * 2 > room) {
                    size = Math.max(14, Math.floor(room / 2));
                }
                width = Math.max(8, Math.min(width, Math.max(10, Math.floor(inner / Math.max(8, Math.ceil(length / 2))))));
            } else {
                while (width > 8 && length * width > inner) {
                    width--;
                }
            }
        }
        if (y + (size * lines) > media.labelHeight - 1) {
            y = Math.max(0, media.labelHeight - 1 - (size * lines));
        }
        return { x: x, y: y, size: size, width: width, lines: lines };
    }

    function updatePreview() {
        var roll = byId('zl_roll');
        if (!roll) {
            return;
        }
        var layout = readLayout();
        var media = syncGeometryFields();
        var sku = activePrintSku();
        var price = state.product && state.product.price ? state.product.price : '';
        var productName = state.product && state.product.name ? state.product.name : '';
        var showName = !!layout.show_product_name && productName !== '';
        var showBarcode = layout.show_barcode !== 0 && layout.show_barcode !== false;
        var showSku = layout.show_sku !== 0 && layout.show_sku !== false;
        var showPrice = layout.show_price !== 0 && layout.show_price !== false;
        var fittedName = nameStyle(layout, media, productName);
        var nameLayout = Object.assign({}, layout, {
            product_name_font_width: fittedName.width,
            product_name_max_width: Math.max(40, media.labelWidth - fittedName.x - 2) * fittedName.lines,
            product_name_wrap: fittedName.lines > 1
        });
        var nameText = showName ? displayProductName(productName, nameLayout) : '';
        var nameLines = fittedName.lines;
        var vertical = layout.vertical_text;
        var zoom = 2.6;
        var pxPerMm = 96 / 25.4;
        var labelMmW = (boot.media && boot.media.label_width_mm) || 30;
        var labelMmH = (boot.media && boot.media.label_height_mm) || 15;
        var stickerW = labelMmW * pxPerMm * zoom;
        var stickerH = labelMmH * pxPerMm * zoom;
        var scale = stickerW / media.labelWidth;
        var gapPx = media.gap * scale;
        var requestedModule = Math.max(1, Math.min(10, Math.round(parseFloat(layout.barcode_width) || 1)));
        var module = requestedModule;
        var bcDots = sku ? estimateBarcodeWidth(sku, module) : 0;
        while (module > 1 && bcDots > media.labelWidth) {
            module--;
            bcDots = estimateBarcodeWidth(sku, module);
        }
        var nudge = layout.barcode_x || 0;
        var bcX = sku ? Math.max(0, Math.floor((media.labelWidth - bcDots) / 2) + nudge) : 0;
        var showBounds = byId('zl_show_bounds') && byId('zl_show_bounds').checked;
        var showGrid = byId('zl_show_grid') && byId('zl_show_grid').checked;
        var showCoords = byId('zl_show_coords') && byId('zl_show_coords').checked;

        roll.innerHTML = '';
        roll.style.marginLeft = Math.max(0, media.left) * scale + 'px';
        var qty = parseInt(text('zl_qty'), 10);
        if (!Number.isInteger(qty) || qty < 1) {
            qty = 1;
        }
        var rowsShown = Math.min(qty, 2);
        var rowIndex;
        for (rowIndex = 0; rowIndex < rowsShown; rowIndex++) {
        var block = document.createElement('div');
        block.className = 'zl-row-block';
        var caption = document.createElement('div');
        caption.className = 'zl-row__caption';
        caption.textContent = qty > 2 && rowIndex === 1
            ? ('Row 2 of ' + qty + ' · remaining rows repeat this row')
            : ('Row ' + (rowIndex + 1) + ' · 3 labels');
        block.appendChild(caption);
        var line = document.createElement('div');
        line.className = 'zl-row';
        line.style.gap = gapPx + 'px';

        media.origins.forEach(function (origin, index) {
            var sticker = document.createElement('div');
            sticker.className = 'zl-sticker' + (showBounds ? '' : ' is-bounds-off') + (showCoords ? ' is-coords' : '');
            sticker.style.width = stickerW + 'px';
            sticker.style.height = stickerH + 'px';

            var tag = document.createElement('div');
            tag.className = 'zl-bound__tag';
            tag.textContent = 'LABEL ' + (index + 1);
            sticker.appendChild(tag);

            if (showGrid) {
                var grid = document.createElement('div');
                grid.className = 'zl-grid';
                var x;
                for (x = 0; x <= media.labelWidth; x += 40) {
                    var v = document.createElement('div');
                    v.className = 'zl-grid__v';
                    v.style.left = (x * scale) + 'px';
                    sticker.appendChild(v);
                }
                var y;
                for (y = 0; y <= media.labelHeight; y += 20) {
                    var h = document.createElement('div');
                    h.className = 'zl-grid__h';
                    h.style.top = ((y + media.top) * scale) + 'px';
                    sticker.appendChild(h);
                }
                sticker.appendChild(grid);
            }

            var bc = document.createElement('img');
            bc.className = 'zl-el';
            bc.alt = '';
            bc.style.left = (bcX * scale) + 'px';
            bc.style.top = ((layout.barcode_y + media.top) * scale) + 'px';
            if (sku && typeof JsBarcode === 'function') {
                try {
                    JsBarcode(bc, sku, {
                        format: 'CODE128',
                        width: Math.max(1, module * scale),
                        height: Math.max(8, clampBarcodeHeight(layout.barcode_height, layout.barcode_y) * scale),
                        displayValue: false,
                        margin: 0
                    });
                } catch (e) {
                    bc.removeAttribute('src');
                }
            }
            if (showBarcode) {
                sticker.appendChild(bc);
            }

            var skuX = fieldX(layout.sku_x, media.labelWidth);
            var skuEl = document.createElement('div');
            skuEl.className = 'zl-el zl-sku';
            skuEl.textContent = sku;
            skuEl.style.left = (skuX * scale) + 'px';
            skuEl.style.top = ((layout.sku_y + media.top) * scale) + 'px';
            skuEl.style.width = ((media.labelWidth - skuX - 2) * scale) + 'px';
            skuEl.style.fontSize = (layout.sku_font_size * scale) + 'px';
            skuEl.style.textAlign = fieldAlign(layout.sku_align);
            skuEl.style.fontWeight = layout.sku_font_weight === 'normal' ? '400' : '700';
            if (showSku) {
                sticker.appendChild(skuEl);
            }

            var priceX = fieldX(layout.price_x, media.labelWidth);
            var priceEl = document.createElement('div');
            priceEl.className = 'zl-el';
            priceEl.textContent = price;
            priceEl.style.left = (priceX * scale) + 'px';
            priceEl.style.top = ((layout.price_y + media.top) * scale) + 'px';
            priceEl.style.width = ((media.labelWidth - priceX - 2) * scale) + 'px';
            priceEl.style.fontSize = (layout.price_font_size * scale) + 'px';
            priceEl.style.textAlign = fieldAlign(layout.price_align);
            priceEl.style.fontWeight = '700';
            if (showPrice) {
                sticker.appendChild(priceEl);
            }

            if (showName) {
                var nameX = fittedName.x;
                var nameEl = document.createElement('div');
                nameEl.className = 'zl-el zl-name' + (fittedName.lines > 1 ? ' is-wrap' : '');
                nameEl.textContent = nameText;
                nameEl.style.left = (nameX * scale) + 'px';
                nameEl.style.top = ((fittedName.y + media.top) * scale) + 'px';
                nameEl.style.width = ((media.labelWidth - nameX - 2) * scale) + 'px';
                var nameSizePx = fittedName.size || 16;
                nameEl.style.fontSize = (nameSizePx * scale) + 'px';
                nameEl.style.lineHeight = (nameSizePx * scale) + 'px';
                nameEl.style.maxHeight = (nameSizePx * nameLines * scale) + 'px';
                nameEl.style.textAlign = fieldAlign(layout.product_name_align);
                nameEl.style.fontWeight = layout.product_name_font_weight === 'normal' ? '400' : '700';
                var nameStretch = nameSizePx > 0 ? (Number(fittedName.width) || nameSizePx) / nameSizePx : 1;
                if (!Number.isFinite(nameStretch) || nameStretch <= 0) {
                    nameStretch = 1;
                }
                nameEl.style.transform = 'scaleX(' + nameStretch + ')';
                nameEl.style.transformOrigin = fieldAlign(layout.product_name_align) + ' top';
                nameEl.style.setProperty('--zl-lines', String(nameLines));
                sticker.appendChild(nameEl);
            }

            if (vertical && layout.vertical_font_size >= 6) {
                var vertSize = Math.max(6, layout.vertical_font_size);
                var vertX = Math.max(0, Math.min(media.labelWidth - vertSize, Number(layout.vertical_x) || 0));
                var vertY = Math.max(0, Math.min(media.labelHeight - vertSize, Number(layout.vertical_y) || 0));
                var vertEl = document.createElement('div');
                vertEl.className = 'zl-el zl-vert';
                vertEl.textContent = vertical;
                vertEl.style.left = (vertX * scale) + 'px';
                vertEl.style.top = ((vertY + media.top) * scale) + 'px';
                vertEl.style.fontSize = (vertSize * scale) + 'px';
                sticker.appendChild(vertEl);
            }

            if (showCoords) {
                var coord = document.createElement('span');
                coord.className = 'zl-coord';
                coord.style.left = '2px';
                coord.style.bottom = '1px';
                coord.style.top = 'auto';
                coord.textContent = 'X ' + origin + '  BC ' + bcX + ',' + layout.barcode_y;
                sticker.appendChild(coord);
            }

            line.appendChild(sticker);
        });
        block.appendChild(line);
        roll.appendChild(block);
        }

        var warnings = [];
        var quiet = 10 * module;
        if (sku && module < requestedModule) {
            warnings.push('Barcode width was reduced so the bars fit the 30 mm label.');
        } else if (sku && (bcX + bcDots) > media.labelWidth) {
            warnings.push('Barcode exceeds the 30 mm label.');
        } else if (sku && bcX < quiet) {
            warnings.push('This barcode is long for a 30 mm label. The quiet zone is tighter than 10 modules.');
        }
        if (showName && (fittedName.y + (fittedName.size * nameLines)) > media.labelHeight) {
            warnings.push('Product name exceeds the 15 mm label.');
        }
        if (price && (layout.price_y + layout.price_font_size) > media.labelHeight) {
            warnings.push('Price exceeds the 15 mm label.');
        }
        if (sku && (layout.sku_y + layout.sku_font_size) > media.labelHeight) {
            warnings.push('Barcode number exceeds the 15 mm label.');
        }
        showWarnings(warnings);
        fitPreview();
        syncTemporaryMode();
    }

    function fitPreview() {
        var stage = byId('zl_stage');
        var scaler = byId('zl_scaler');
        var roll = byId('zl_roll');
        if (!stage || !scaler || !roll) {
            return;
        }
        var width = roll.scrollWidth || roll.offsetWidth;
        var height = roll.scrollHeight || roll.offsetHeight;
        var available = Math.max(280, stage.clientWidth - 8);
        var fit = width > available ? available / width : 1;
        roll.style.transform = 'scale(' + fit + ')';
        scaler.style.width = Math.ceil(width * fit) + 'px';
        scaler.style.height = Math.ceil(height * fit) + 'px';
    }

    function openPrintPreview() {
        updatePreview();
        var roll = byId('zl_roll');
        if (!roll) {
            setStatus('The label preview is not ready yet.', 'error');
            return;
        }
        var clone = roll.cloneNode(true);
        clone.querySelectorAll('.zl-grid, .zl-coord').forEach(function (node) {
            node.remove();
        });
        clone.style.transform = 'none';
        clone.classList.remove('is-coords');
        clone.style.border = 'none';
        var layer = byId('zl_print_layer');
        if (!layer) {
            layer = document.createElement('div');
            layer.id = 'zl_print_layer';
            layer.className = 'zl-print-layer';
            layer.hidden = true;
            document.body.appendChild(layer);
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closePrintPreview();
                }
            });
            layer.addEventListener('click', function (event) {
                if (event.target === layer) {
                    closePrintPreview();
                }
            });
        }
        layer.innerHTML = '';
        layer.appendChild(clone);
        layer.hidden = false;
        document.body.classList.add('zl-print-open');
    }

    function closePrintPreview() {
        var layer = byId('zl_print_layer');
        if (!layer || layer.hidden) {
            return;
        }
        layer.hidden = true;
        layer.innerHTML = '';
        document.body.classList.remove('zl-print-open');
    }

    function postProfile(url, data) {
        return $.ajax({ url: url, method: 'POST', data: data });
    }

    function acceptProfiles(res) {
        state.profiles = res.profiles || [];
        if (res.profile) {
            state.profileId = res.profile.id;
            setVal('zl_profile_name', res.profile.name || '');
        }
        fillProfiles();
        setStatus(res.message || 'Saved.', 'ok');
        if (window.toastr) {
            toastr.success(res.message || 'Saved.');
        }
    }

    function profileName() {
        return text('zl_profile_name').trim();
    }

    function saveProfile() {
        var name = profileName();
        if (!name) {
            setStatus('Enter a profile name up to 80 characters.', 'error');
            return;
        }
        var payload = { name: name, layout: readLayout() };
        var url = state.profileId ? (boot.urls.profiles + '/' + state.profileId) : boot.urls.profiles;
        postProfile(url, payload).done(function (res) {
            acceptProfiles(res);
            captureMaster();
        }).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function duplicateProfile() {
        var name = profileName() || 'Label';
        postProfile(boot.urls.profiles, {
            name: name + ' copy',
            auto_name: 1,
            layout: readLayout()
        }).done(function (res) {
            acceptProfiles(res);
            captureMaster();
        }).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function renameProfile() {
        if (!state.profileId) {
            setStatus('Load a profile before renaming it.', 'error');
            return;
        }
        postProfile(boot.urls.profiles + '/' + state.profileId + '/rename', {
            name: profileName()
        }).done(acceptProfiles).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function deleteProfile() {
        if (!state.profileId) {
            return;
        }
        ask('Delete this label profile?').then(function (ok) {
            if (!ok) {
                return;
            }
            $.ajax({ url: boot.urls.profiles + '/' + state.profileId, method: 'DELETE' })
                .done(function (res) {
                    acceptProfiles(res);
                    var next = (res.profiles || []).filter(function (p) {
                        return String(p.id) === String(state.profileId);
                    })[0] || (res.profiles || [])[0];
                    if (next) {
                        applyProfile(next, !!state.product);
                    }
                })
                .fail(function (xhr) {
                    setStatus(readError(xhr), 'error');
                });
        });
    }

    function setDefaultProfile() {
        if (!state.profileId) {
            return;
        }
        postProfile(boot.urls.profiles + '/' + state.profileId + '/default', {})
            .done(acceptProfiles)
            .fail(function (xhr) {
                setStatus(readError(xhr), 'error');
            });
    }

    function loadSelected() {
        var id = text('zl_profile');
        var profile = state.profiles.filter(function (item) {
            return String(item.id) === String(id);
        })[0];
        if (!profile) {
            setStatus('The selected label profile could not be found.', 'error');
            return;
        }
        applyProfile(profile, !!state.product);
        setStatus('Layout loaded.', 'ok');
    }

    function resetAlignment() {
        ask('Restore the default alignment on screen? This does not change the saved label until you press Save label.').then(function (ok) {
            if (!ok) {
                return;
            }
            var defaults = boot.defaults || {};
            alignKeys.forEach(function (key) {
                setVal('zl_' + key, defaults[key]);
            });
            setVal('zl_sku_align', defaults.sku_align || 'center');
            setVal('zl_price_align', defaults.price_align || 'center');
            setVal('zl_product_name_font_weight', defaults.product_name_font_weight || 'bold');
            setVal('zl_product_name_align', defaults.product_name_align || 'center');
            setChecked('zl_product_name_show', defaults.show_product_name !== false && defaults.show_product_name !== 0);
            setChecked('zl_show_barcode', defaults.show_barcode !== false && defaults.show_barcode !== 0);
            setChecked('zl_show_sku', defaults.show_sku !== false && defaults.show_sku !== 0);
            setChecked('zl_show_price', defaults.show_price !== false && defaults.show_price !== 0);
            setChecked('zl_product_name_wrap', defaults.product_name_wrap);
            setVal('zl_label_gap_mm', defaults.label_gap_mm);
            setVal('zl_top_offset_mm', defaults.top_offset_mm);
            setVal('zl_left_offset_mm', defaults.left_offset_mm);
            setVal('zl_printer_dpi', defaults.printer_dpi || 203);
            applyMediaStack();
            state.barcodeKey = '';
            updatePreview();
            setStatus('Default alignment is on screen only. Press Save label to keep it.', 'ok');
        });
    }

    function clientPrintError() {
        if (!state.product || !state.product.variation_id) {
            return 'Select a product before printing.';
        }
        if (!activePrintSku()) {
            return 'Barcode is missing for this product.';
        }
        var rawQty = text('zl_qty').trim();
        var qty = parseInt(rawQty, 10);
        if (!/^\d+$/.test(rawQty) || qty < 1 || qty > 100) {
            return 'Please enter a valid print quantity.';
        }
        if (!text('zl_printer_name').trim()) {
            return 'Select a printer before printing.';
        }
        return '';
    }

    var qzCertificate = [
        '-----BEGIN CERTIFICATE-----',
        'MIIE9TCCAt2gAwIBAgIQNzkyMDI0MTIyMDE5MDI0NDANBgkqhkiG9w0BAQsFADCB',
        'mDELMAkGA1UEBhMCVVMxCzAJBgNVBAgMAk5ZMRswGQYDVQQKDBJRWiBJbmR1c3Ry',
        'aWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMsIExMQzEZMBcGA1UEAwwQ',
        'cXppbmR1c3RyaWVzLmNvbTEnMCUGCSqGSIb3DQEJARYYc3VwcG9ydEBxemluZHVz',
        'dHJpZXMuY29tMB4XDTI0MTIyMDE5MDI0NFoXDTI5MTIyMDE4NTMxOVowga4xFjAU',
        'BgNVBAYMDVVuaXRlZCBTdGF0ZXMxCzAJBgNVBAgMAk5ZMRIwEAYDVQQHDAlDYW5h',
        'c3RvdGExGzAZBgNVBAoMElFaIEluZHVzdHJpZXMsIExMQzEbMBkGA1UECwwSUVog',
        'SW5kdXN0cmllcywgTExDMRswGQYDVQQDDBJRWiBJbmR1c3RyaWVzLCBMTEMxHDAa',
        'BgkqhkiG9w0BCQEMDXN1cHBvcnRAcXouaW8wggEiMA0GCSqGSIb3DQEBAQUAA4IB',
        'DwAwggEKAoIBAQC+j6ewVhtLHbY3uBNgqNB5DSz+QX9Pz5Dm46bI9vt/Q1Q6BL8I',
        'dhaxT2PA1AY0fqQgkzlSrwqNCjWZcrNZRw/e54FGM8zf3azbHrQif6d7Wo1JK5oN',
        'kI3jdB54YVwHIAt6i3BcLIvyOHsPnrKjlpROz72Kx1kK5g0gLDuH5RYVM9KFK+HR',
        'fBc3JSfeg8nUkTqYJVzlT5AGRWPXeDWloqQqSyuB1t8DihNBReWyJHQ7a4yerLOI',
        'J6N0jAlLDx9yt9UznAxnoO+7tKBfxCbNJerGfePMOwRKq0gx+r8M/FTrAoj+yc+T',
        'SOYtuY/VZ79HCTP/vLgm1pGyrta1we24fVezAgMBAAGjIzAhMB8GA1UdIwQYMBaA',
        'FJCmULeE1LnqX/IFhBN4ReipdVRcMA0GCSqGSIb3DQEBCwUAA4ICAQAMvfp931Zt',
        'PgfqGXSrsM+GAVBxcRVm14MyldWfRr+MVaFZ6cH7c+fSs8hUt2qNPwHrnpK9eev5',
        'MPUL27hjfiTPwv1ojLJ180aMO0ZAfPfnKeLO8uTzY7GiPQeGK7Qh39kX9XxEOidG',
        'rMwfllZ6jJReS0ZGaX8LUXhh9RHGSYJhxgyUV7clB/dJch8Bbcd+DOxwc1POUHx1',
        'wWExKkoWzHCCYNvqxLC9p1eO2Elz9J9ynDjXtCBl7lssnoSUKtahBCKgN5tYmZZK',
        'NErKPQpbYk5yTEK1gybxhup8i2sGEJXZ9HRJLAl0UxB+eCu1ExWv7eGbcbIZJbeh',
        'bwRf03fatsqzCQbGboLWtMQfcxHrEu+5MdZwOFx8i+c0c2WYad2MkkzGYHBVHPtY',
        'o+PR61uIwJC2mNkPpX94CIFxSHyZumttyVKF4AhIPm9IMGTHaIr5M39zesQpVc7N',
        'VIgxmMuePBrLyh6vKvuqD7W3S2HWA/8IUX703tdhoXhv5lNo1j0oywSrrUkCvUvJ',
        'FjPS8+VUtVZNl7SVetQTexdcUwoADj6c1UwL9QWItskJ5Myesco3ZY0O+3QbgCuQ',
        'SRqN5D0qdaLNMdEwh1YekUp4i1jm0jzPzia+WvJrW1k1ZafV6ep+YkMBkC1SFYFw',
        '1Mdy+fYGyXlSn/Mvou//SSb0fUMIpXE9NA==',
        '-----END CERTIFICATE-----',
        '--START INTERMEDIATE CERT--',
        '-----BEGIN CERTIFICATE-----',
        'MIIFEjCCA/qgAwIBAgICEAAwDQYJKoZIhvcNAQELBQAwgawxCzAJBgNVBAYTAlVT',
        'MQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYDVQQKDBJRWiBJ',
        'bmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMsIExMQzEZMBcG',
        'A1UEAwwQcXppbmR1c3RyaWVzLmNvbTEnMCUGCSqGSIb3DQEJARYYc3VwcG9ydEBx',
        'emluZHVzdHJpZXMuY29tMB4XDTE1MDMwMjAwNTAxOFoXDTM1MDMwMjAwNTAxOFow',
        'gZgxCzAJBgNVBAYTAlVTMQswCQYDVQQIDAJOWTEbMBkGA1UECgwSUVogSW5kdXN0',
        'cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMxGTAXBgNVBAMM',
        'EHF6aW5kdXN0cmllcy5jb20xJzAlBgkqhkiG9w0BCQEWGHN1cHBvcnRAcXppbmR1',
        'c3RyaWVzLmNvbTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBANTDgNLU',
        'iohl/rQoZ2bTMHVEk1mA020LYhgfWjO0+GsLlbg5SvWVFWkv4ZgffuVRXLHrwz1H',
        'YpMyo+Zh8ksJF9ssJWCwQGO5ciM6dmoryyB0VZHGY1blewdMuxieXP7Kr6XD3GRM',
        'GAhEwTxjUzI3ksuRunX4IcnRXKYkg5pjs4nLEhXtIZWDLiXPUsyUAEq1U1qdL1AH',
        'EtdK/L3zLATnhPB6ZiM+HzNG4aAPynSA38fpeeZ4R0tINMpFThwNgGUsxYKsP9kh',
        '0gxGl8YHL6ZzC7BC8FXIB/0Wteng0+XLAVto56Pyxt7BdxtNVuVNNXgkCi9tMqVX',
        'xOk3oIvODDt0UoQUZ/umUuoMuOLekYUpZVk4utCqXXlB4mVfS5/zWB6nVxFX8Io1',
        '9FOiDLTwZVtBmzmeikzb6o1QLp9F2TAvlf8+DIGDOo0DpPQUtOUyLPCh5hBaDGFE',
        'ZhE56qPCBiQIc4T2klWX/80C5NZnd/tJNxjyUyk7bjdDzhzT10CGRAsqxAnsjvMD',
        '2KcMf3oXN4PNgyfpbfq2ipxJ1u777Gpbzyf0xoKwH9FYigmqfRH2N2pEdiYawKrX',
        '6pyXzGM4cvQ5X1Yxf2x/+xdTLdVaLnZgwrdqwFYmDejGAldXlYDl3jbBHVM1v+uY',
        '5ItGTjk+3vLrxmvGy5XFVG+8fF/xaVfo5TW5AgMBAAGjUDBOMB0GA1UdDgQWBBSQ',
        'plC3hNS56l/yBYQTeEXoqXVUXDAfBgNVHSMEGDAWgBQDRcZNwPqOqQvagw9BpW0S',
        'BkOpXjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQAJIO8SiNr9jpLQ',
        'eUsFUmbueoxyI5L+P5eV92ceVOJ2tAlBA13vzF1NWlpSlrMmQcVUE/K4D01qtr0k',
        'gDs6LUHvj2XXLpyEogitbBgipkQpwCTJVfC9bWYBwEotC7Y8mVjjEV7uXAT71GKT',
        'x8XlB9maf+BTZGgyoulA5pTYJ++7s/xX9gzSWCa+eXGcjguBtYYXaAjjAqFGRAvu',
        'pz1yrDWcA6H94HeErJKUXBakS0Jm/V33JDuVXY+aZ8EQi2kV82aZbNdXll/R6iGw',
        '2ur4rDErnHsiphBgZB71C5FD4cdfSONTsYxmPmyUb5T+KLUouxZ9B0Wh28ucc1Lp',
        'rbO7BnjW',
        '-----END CERTIFICATE-----',
        ''
    ].join('\n');

    function printerMessage(err) {
        var raw = '';
        if (err && err.message) {
            raw = String(err.message);
        } else if (err) {
            raw = String(err);
        }
        var low = raw.toLowerCase();
        if (low.indexOf('blocked') !== -1) {
            return 'QZ Tray blocked this site. Open the QZ Tray icon, choose Advanced, then Site Manager, and remove localhost from Blocked. Print again and click Allow.';
        }
        if (low === 'printer' || low.indexOf('printer') !== -1) {
            return 'ZD220 was not found. Check the printer name, then use Detect ZD220.';
        }
        if (low === 'allow' || low.indexOf('not returned') !== -1) {
            return 'Click Allow on the QZ Tray prompt, then try again.';
        }
        if (low.indexOf('local network') !== -1) {
            return 'Allow local network access for this site in the browser prompt, then try again.';
        }
        if (low.indexOf('has not been established') !== -1) {
            return 'The printer connection dropped before the job was sent. Leave QZ Tray running and try again.';
        }
        if (low.indexOf('unable to establish') !== -1 || low.indexOf('websocket') !== -1 || low.indexOf('unable to create') !== -1 || low === 'service') {
            return 'QZ Tray is not reachable from this browser. Allow the local network prompt if one appears, and leave QZ Tray running.';
        }
        return 'Unable to connect to the selected printer.';
    }

    function withTimeout(promise, ms) {
        return new Promise(function (resolve, reject) {
            var settled = false;
            var timer = setTimeout(function () {
                if (settled) {
                    return;
                }
                settled = true;
                try {
                    if (window.qz && window.qz.websocket && !window.qz.websocket.isActive()) {
                        window.qz.websocket.disconnect().catch(function () {});
                    }
                } catch (ignore) {}
                reject(new Error('allow'));
            }, ms);
            Promise.resolve(promise).then(function (value) {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(timer);
                resolve(value);
            }, function (err) {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(timer);
                reject(err);
            });
        });
    }

    function prepareQz() {
        window.qz.security.setCertificatePromise(function (resolve) {
            resolve(qzCertificate);
        });
        if (window.qz.security.setSignatureAlgorithm) {
            window.qz.security.setSignatureAlgorithm('SHA512');
        }
        window.qz.security.setSignaturePromise(function () {
            return function (resolve) {
                resolve();
            };
        });
    }

    function connectOnce(options) {
        options.port = {
            secure: [8181, 8282, 8383, 8484],
            insecure: [8182, 8283, 8384, 8485],
            portIndex: 0
        };
        return window.qz.websocket.connect(options);
    }

    function releaseSocket() {
        if (!window.qz || !window.qz.websocket || window.qz.websocket.isActive()) {
            return Promise.resolve();
        }
        try {
            return window.qz.websocket.disconnect().catch(function () {
                return null;
            });
        } catch (ignore) {
            return Promise.resolve();
        }
    }

    function connectQz() {
        prepareQz();
        if (window.qz.websocket.isActive()) {
            return Promise.resolve();
        }
        return releaseSocket().then(function () {
            return connectOnce({
                host: ['127.0.0.1', 'localhost'],
                usingSecure: false,
                retries: 0,
                delay: 0
            });
        });
    }

    function ensureConnected() {
        return loadQz().then(function () {
            if (window.qz.websocket.isActive()) {
                return;
            }
            return withTimeout(connectQz(), 45000);
        });
    }

    function choosePrinter(wanted) {
        return window.qz.printers.find().then(function (list) {
            var names = Array.isArray(list) ? list.slice() : (list ? [String(list)] : []);
            var exact = names.filter(function (name) { return name === wanted; })[0];
            if (exact) {
                return exact;
            }
            var lower = wanted.toLowerCase();
            var insensitive = names.filter(function (name) {
                return String(name).toLowerCase() === lower;
            })[0];
            if (insensitive) {
                return insensitive;
            }
            var zd220 = names.filter(function (name) {
                return String(name).toLowerCase().indexOf('zd220') !== -1;
            })[0];
            if (zd220) {
                return zd220;
            }
            var zebra = names.filter(function (name) {
                var value = String(name).toLowerCase();
                return value.indexOf('zdesigner') !== -1 || value.indexOf('zebra') !== -1 || value.indexOf('zd2') !== -1;
            })[0];
            if (zebra) {
                return zebra;
            }
            throw new Error('printer');
        });
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.onload = function () {
                resolve();
            };
            script.onerror = function () {
                reject(new Error('service'));
            };
            document.head.appendChild(script);
        });
    }

    function loadQz() {
        if (window.qz && window.qz.websocket) {
            return Promise.resolve();
        }
        if (state.qzLoading) {
            return state.qzLoading;
        }
        var lna = loadScript(boot.urls.lna);
        state.qzLoading = lna.catch(function () {
            return null;
        }).then(function () {
            return loadScript(boot.urls.qz);
        }).then(function () {
            if (!window.qz || !window.qz.websocket) {
                throw new Error('service');
            }
        }).catch(function (err) {
            state.qzLoading = null;
            throw err;
        });
        return state.qzLoading;
    }


    function quantityProblem() {
        var rawQty = text('zl_qty').trim();
        var qty = parseInt(rawQty, 10);
        if (!/^\d+$/.test(rawQty) || qty < 1 || qty > 100) {
            return 'Please enter a valid print quantity.';
        }
        return '';
    }

    function requestZpl(isTest) {
        if (state.skuEditing) {
            commitSkuDraft();
        }
        var draft = storedDraft(state.product);
        return $.ajax({
            url: boot.urls.temporaryPrint || boot.urls.zpl,
            method: 'POST',
            data: {
                variation_id: isTest ? 0 : (state.product ? state.product.variation_id : 0),
                profile_id: state.profileId || 0,
                quantity: text('zl_qty'),
                price_group_id: text('zl_price_group'),
                test: isTest ? 1 : 0,
                temporary: 1,
                print_sku: isTest ? '' : draft,
                layout: cloneLayout()
            }
        });
    }

    function submitRaw(zpl, printer) {
        var config = window.qz.configs.create(printer, {
            copies: 1,
            jobName: 'VKPOS 30x15 labels'
        });
        return window.qz.print(config, [{
            type: 'raw',
            format: 'plain',
            data: zpl
        }]);
    }

    function sendRaw(zpl, printerName) {
        return ensureConnected()
            .then(function () {
                return choosePrinter(printerName);
            })
            .then(function (printer) {
                setStatus('Sending ZPL to ' + printer + '…');
                var ready = window.qz.websocket.isActive() ? Promise.resolve() : ensureConnected();
                return ready.then(function () {
                    return submitRaw(zpl, printer);
                }).catch(function (err) {
                    var message = err && err.message ? String(err.message).toLowerCase() : '';
                    if (message.indexOf('has not been established') === -1) {
                        throw err;
                    }
                    return ensureConnected().then(function () {
                        return submitRaw(zpl, printer);
                    });
                });
            });
    }

    function lockPrint(locked) {
        ['zl_print', 'zl_test_print', 'zl_view_zpl'].forEach(function (id) {
            var node = byId(id);
            if (node) {
                node.disabled = locked;
            }
        });
    }

    function finishPrint() {
        state.printing = false;
        lockPrint(false);
    }

    function runPrint(isTest) {
        if (state.printing) {
            return;
        }
        var problem = isTest ? quantityProblem() : clientPrintError();
        if (!problem && isTest && !text('zl_printer_name').trim()) {
            problem = 'Select a printer before printing.';
        }
        if (problem) {
            setStatus(problem, 'error');
            return;
        }
        state.printing = true;
        lockPrint(true);
        setStatus(isTest ? 'Preparing test ZPL…' : 'Preparing ZPL…');
        requestZpl(isTest).done(function (res) {
            if (res.warnings && res.warnings.length) {
                showWarnings(res.warnings);
            }
            setStatus('Connecting to QZ Tray. Click Allow if a prompt appears.');
            sendRaw(res.zpl, res.printer_name).then(function () {
                var rows = res.rows || 1;
                var labels = res.labels || (rows * 3);
                var done = 'Submitted ' + rows + (rows === 1 ? ' row' : ' rows') + ' (' + labels + ' physical labels) to ' + (res.printer_name || 'the ZD220') + '. QZ Tray accepted the ZPL job.';
                setStatus(done, 'ok');
                if (window.toastr) {
                    toastr.success(done);
                }
            }).catch(function (err) {
                setStatus(printerMessage(err), 'error');
            }).then(finishPrint);
        }).fail(function (xhr) {
            finishPrint();
            setStatus(readError(xhr), 'error');
        });
    }

    function showZpl(zpl) {
        var modal = byId('zl_zpl_modal');
        var pre = byId('zl_zpl_text');
        if (!modal || !pre) {
            return;
        }
        pre.textContent = zpl || '';
        modal.hidden = false;
    }

    function closeZpl() {
        var modal = byId('zl_zpl_modal');
        if (modal) {
            modal.hidden = true;
        }
    }

    function viewZpl() {
        var problem = clientPrintError();
        if (problem) {
            setStatus(problem, 'error');
            return;
        }
        setStatus('Building ZPL…');
        requestZpl(false).done(function (res) {
            showZpl(res.zpl);
            if (res.warnings && res.warnings.length) {
                showWarnings(res.warnings);
            }
            setStatus('ZPL ready. Nothing was sent to the printer.', 'ok');
        }).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function detectPrinter() {
        setStatus('Looking for the ZD220. Click Allow if QZ Tray asks.');
        loadQz().then(function () {
            return withTimeout(connectQz(), 45000);
        }).then(function () {
            return choosePrinter(text('zl_printer_name').trim());
        }).then(function (name) {
            setVal('zl_printer_name', name);
            setStatus('Printer ready: ' + name + '.', 'ok');
        }).catch(function (err) {
            setStatus(printerMessage(err), 'error');
        });
    }

    function printLabels() {
        runPrint(false);
    }

    function showMode(mode) {
        var zebra = byId('zl_workspace');
        var sheet = byId('ld_sheet_workspace');
        var zebraBtn = byId('zl_mode_zebra');
        var sheetBtn = byId('zl_mode_sheet');
        var showSheet = mode === 'sheet';
        if (zebra) {
            zebra.classList.toggle('zl-is-hidden', showSheet);
        }
        if (sheet) {
            sheet.classList.toggle('zl-is-hidden', !showSheet);
        }
        if (zebraBtn) {
            zebraBtn.classList.toggle('is-active', !showSheet);
        }
        if (sheetBtn) {
            sheetBtn.classList.toggle('is-active', showSheet);
        }
        if (showSheet && typeof window.scheduleLivePreview === 'function') {
            window.scheduleLivePreview();
        }
        if (!showSheet) {
            fitPreview();
        }
    }

    function bindSearch() {
        var input = $('#zl_search');
        if (!input.length || !$.fn.autocomplete || !boot.urls || !boot.urls.search) {
            return;
        }
        input.autocomplete({
            source: boot.urls.search + '?check_enable_stock=false',
            minLength: 2,
            response: function (event, ui) {
                if (ui.content.length === 1) {
                    ui.item = ui.content[0];
                    $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                    $(this).autocomplete('close');
                } else if (ui.content.length === 0 && window.LANG && LANG.no_products_found) {
                    swal(LANG.no_products_found);
                }
            },
            select: function (event, ui) {
                $(this).val('');
                loadProduct(ui.item.variation_id);
                return false;
            }
        }).autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>').append($('<div>').text(item.text)).appendTo(ul);
        };
    }

    function bindNudge() {
        var workspace = byId('zl_workspace');
        if (!workspace) {
            return;
        }
        workspace.addEventListener('click', function (event) {
            var btn = event.target.closest('.zl-nudge');
            if (!btn) {
                return;
            }
            var input = byId(btn.getAttribute('data-target'));
            if (!input) {
                return;
            }
            var step = (event.shiftKey ? 10 : 1) * parseInt(btn.getAttribute('data-step'), 10);
            var next = (parseFloat(input.value) || 0) + step;
            if (input.id === 'zl_barcode_width') {
                next = Math.max(1, Math.min(10, Math.round(next)));
            }
            if (input.id === 'zl_barcode_height') {
                next = clampBarcodeHeight(next, num('zl_barcode_y'));
            }
            input.value = next;
            state.barcodeKey = '';
            updatePreview();
        });
        workspace.addEventListener('keydown', function (event) {
            var el = event.target;
            if (!el || el.tagName !== 'INPUT' || !el.dataset || !el.dataset.axis) {
                return;
            }
            if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].indexOf(event.key) === -1) {
                return;
            }
            event.preventDefault();
            var step = event.shiftKey ? 10 : 1;
            var delta = 0;
            if (el.dataset.axis === 'x') {
                if (event.key === 'ArrowLeft') { delta = -step; }
                if (event.key === 'ArrowRight') { delta = step; }
            }
            if (el.dataset.axis === 'y') {
                if (event.key === 'ArrowUp') { delta = -step; }
                if (event.key === 'ArrowDown') { delta = step; }
            }
            if (!delta) {
                return;
            }
            el.value = (parseFloat(el.value) || 0) + delta;
            updatePreview();
        });
        workspace.addEventListener('input', function (event) {
            var id = event.target.id || '';
            if (id === 'zl_qty') {
                updateQuantitySummary();
                updatePreview();
                return;
            }
            if (id === 'zl_profile_name' || id === 'zl_search' || id === 'zl_sku_input') {
                return;
            }
            if (id === 'zl_barcode_width' || id === 'zl_barcode_height') {
                state.barcodeKey = '';
            }
            updatePreview();
        });
        workspace.addEventListener('change', function (event) {
            if (event.target.id === 'zl_price_group' && state.product) {
                loadProduct(state.product.variation_id, {
                    lot_number: state.product.lot_number,
                    exp_date: state.product.exp_date,
                    quantity: state.product.purchase_quantity
                }, false);
                return;
            }
            if (event.target.id === 'zl_product_name_font_weight') {
                var nameSize = num('zl_product_name_font_size') || 20;
                setVal('zl_product_name_font_width', text('zl_product_name_font_weight') === 'normal' ? Math.max(8, Math.round(nameSize * 0.7)) : nameSize);
            }
            if (event.target.id === 'zl_show_grid' || event.target.id === 'zl_show_bounds' || event.target.id === 'zl_show_coords' || event.target.id === 'zl_sku_font_weight' || event.target.id === 'zl_sku_align' || event.target.id === 'zl_price_align' || event.target.id === 'zl_printer_dpi' || event.target.id === 'zl_product_name_font_weight' || event.target.id === 'zl_product_name_align' || event.target.id === 'zl_product_name_show' || event.target.id === 'zl_product_name_wrap' || event.target.id === 'zl_show_barcode' || event.target.id === 'zl_show_sku' || event.target.id === 'zl_show_price') {
                updatePreview();
            }
        });
    }

    $(function () {
        if (!byId('zl_workspace')) {
            return;
        }
        renderQueue();
        bindSearch();
        bindNudge();
        var defaults = boot.defaults || {};
        var initial = (state.profiles || []).filter(function (p) { return p.is_default; })[0] || state.profiles[0] || defaults;
        applyProfile(initial, true);
        if (boot.product) {
            state.product = boot.product;
            renderProduct(true);
            state.barcodeKey = '';
            updatePreview();
        }
        updateQuantitySummary();
        byId('zl_load').addEventListener('click', loadSelected);
        byId('zl_save').addEventListener('click', saveProfile);
        byId('zl_discard').addEventListener('click', discardTemporary);
        byId('zl_duplicate').addEventListener('click', duplicateProfile);
        byId('zl_rename').addEventListener('click', renameProfile);
        byId('zl_delete').addEventListener('click', deleteProfile);
        byId('zl_default').addEventListener('click', setDefaultProfile);
        byId('zl_reset').addEventListener('click', resetAlignment);
        byId('zl_print').addEventListener('click', printLabels);
        byId('zl_print_preview').addEventListener('click', openPrintPreview);
        byId('zl_test_print').addEventListener('click', function () { runPrint(true); });
        byId('zl_view_zpl').addEventListener('click', viewZpl);
        byId('zl_detect_printer').addEventListener('click', detectPrinter);
        byId('zl_zpl_close').addEventListener('click', closeZpl);
        byId('zl_printer_settings_btn').addEventListener('click', function () {
            var panel = byId('zl_printer_settings');
            if (panel) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        byId('zl_zpl_modal').addEventListener('click', function (event) {
            if (event.target === byId('zl_zpl_modal')) {
                closeZpl();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeZpl();
            }
        });
        byId('zl_sku_edit').addEventListener('click', openSkuEditor);
        byId('zl_sku_input').addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                commitSkuDraft();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                closeSkuEditor();
                renderProduct(false);
                updatePreview();
            }
        });
        byId('zl_sku_input').addEventListener('input', function () {
            if (state.skuEditing) {
                updatePreview();
            }
        });
        byId('zl_sku_input').addEventListener('blur', function () {
            if (state.skuEditing) {
                commitSkuDraft();
            }
        });
        byId('zl_mode_zebra').addEventListener('click', function () { showMode('zebra'); });
        byId('zl_mode_sheet').addEventListener('click', function () { showMode('sheet'); });
        window.addEventListener('resize', fitPreview);
        if (!boot.ready) {
            showMode('sheet');
        }
    });
}(jQuery));
