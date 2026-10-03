(function ($) {
    var boot = window.ZL_BOOT || { ready: false, profiles: [], defaults: {}, urls: {}, queue: [] };
    var state = {
        profiles: boot.profiles || [],
        profileId: null,
        product: null,
        printing: false,
        barcodeKey: '',
        qzLoading: null
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
            price_x: num('zl_price_x'),
            price_y: num('zl_price_y'),
            price_font_size: num('zl_price_font_size'),
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
            product_name_wrap: byId('zl_product_name_wrap') && byId('zl_product_name_wrap').checked ? 1 : 0
        };
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
        setVal('zl_width', profile.width);
        setVal('zl_height', profile.height);
        alignKeys.forEach(function (key) {
            setVal('zl_' + key, profileValue(profile, key));
        });
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
        setChecked('zl_product_name_wrap', profileValue(profile, 'product_name_wrap'));
        if (!keepVertical && profile.vertical_text) {
            setVal('zl_vertical', profile.vertical_text);
        }
        fillProfiles();
        updateQuantitySummary();
        updatePreview();
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

    function renderProduct(setVertical) {
        var product = state.product;
        byId('zl_product_name').textContent = product ? product.name : 'No product selected';
        byId('zl_product_sku').textContent = product && product.barcode ? product.barcode : '—';
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
        byId('zl_product_card').classList.toggle('is-missing', !!(product && product.barcode_missing));
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
            state.product = res.product;
            state.barcodeKey = '';
            if (extras) {
                state.product.lot_number = extras.lot_number || null;
                state.product.exp_date = extras.exp_date || null;
                state.product.purchase_quantity = extras.quantity || null;
            }
            renderProduct(setVertical !== false);
            updatePreview();
            setStatus(state.product.barcode_missing ? 'Barcode is missing for this product.' : 'Product loaded from VKPOS.', state.product.barcode_missing ? 'error' : 'ok');
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

    function updatePreview() {
        var canvas = byId('zl_canvas');
        if (!canvas) {
            return;
        }
        var layout = readLayout();
        var sku = state.product && state.product.barcode ? state.product.barcode : '';
        var price = state.product && state.product.price ? state.product.price : '';
        var productName = state.product && state.product.name ? state.product.name : '';
        var showName = !!layout.show_product_name && productName !== '';
        var nameText = showName ? displayProductName(productName, layout) : '';
        var nameLines = layout.product_name_wrap ? Math.max(1, layout.product_name_max_lines || 1) : 1;
        var vertical = layout.vertical_text;
        var guides = drawGuides(layout);
        var elements = document.createElement('div');
        var bases = [layout.col1_x, layout.col2_x, layout.col3_x];
        var barcodeKey = sku + '|' + layout.barcode_width + '|' + layout.barcode_height;
        var redrawBarcode = barcodeKey !== state.barcodeKey;
        state.barcodeKey = barcodeKey;

        bases.forEach(function (base, index) {
            var bc = document.createElement('img');
            bc.className = 'zl-el';
            bc.alt = '';
            bc.style.left = (base + layout.barcode_x) + 'px';
            bc.style.top = layout.barcode_y + 'px';
            var previous = canvas.querySelector('[data-bc="' + index + '"]');
            if (!redrawBarcode && previous && previous.getAttribute('src')) {
                bc.src = previous.getAttribute('src');
            } else if (sku && typeof JsBarcode === 'function') {
                try {
                    JsBarcode(bc, sku, {
                        format: 'CODE128',
                        width: (parseFloat(layout.barcode_width) || 1.5) + 0.1,
                        height: layout.barcode_height,
                        displayValue: false,
                        margin: 0
                    });
                } catch (e) {
                    bc.removeAttribute('src');
                }
            }
            bc.setAttribute('data-bc', String(index));

            var nameEl = document.createElement('div');
            nameEl.className = 'zl-el zl-name p-element p-product-name' + (layout.product_name_wrap ? ' is-wrap' : '');
            nameEl.id = 'prev-product-name-' + index;
            if (showName) {
                var place = namePlacement(base, layout);
                nameEl.textContent = nameText;
                nameEl.style.left = place.left + 'px';
                nameEl.style.top = layout.product_name_y + 'px';
                nameEl.style.width = place.width + 'px';
                nameEl.style.maxHeight = (layout.product_name_font_size * nameLines) + 'px';
                nameEl.style.fontSize = layout.product_name_font_size + 'px';
                nameEl.style.lineHeight = layout.product_name_font_size + 'px';
                nameEl.style.fontWeight = layout.product_name_font_weight === 'normal' ? '400' : '700';
                nameEl.style.textAlign = layout.product_name_align || 'center';
                nameEl.style.setProperty('--zl-lines', String(nameLines));
                if (place.ratio !== 1) {
                    nameEl.style.transform = 'scaleX(' + place.ratio + ')';
                    nameEl.style.transformOrigin = place.origin;
                }
            }

            var skuEl = document.createElement('div');
            skuEl.className = 'zl-el';
            skuEl.textContent = sku;
            skuEl.style.left = (base + layout.sku_x) + 'px';
            skuEl.style.top = layout.sku_y + 'px';
            skuEl.style.fontSize = layout.sku_font_size + 'px';
            skuEl.style.fontWeight = layout.sku_font_weight === 'normal' ? '400' : '700';

            var priceEl = document.createElement('div');
            priceEl.className = 'zl-el';
            priceEl.textContent = price;
            priceEl.style.left = (base + layout.price_x) + 'px';
            priceEl.style.top = layout.price_y + 'px';
            priceEl.style.fontSize = layout.price_font_size + 'px';
            priceEl.style.fontWeight = '700';

            var vertEl = document.createElement('div');
            vertEl.className = 'zl-el zl-vert';
            vertEl.textContent = vertical;
            vertEl.style.left = (base + layout.vertical_x) + 'px';
            vertEl.style.top = layout.vertical_y + 'px';
            vertEl.style.fontSize = layout.vertical_font_size + 'px';

            var coord = document.createElement('span');
            coord.className = 'zl-coord';
            coord.style.left = (base + layout.barcode_x) + 'px';
            coord.style.top = Math.max(0, layout.barcode_y - 10) + 'px';
            coord.textContent = 'BC ' + (base + layout.barcode_x) + ',' + layout.barcode_y
                + '  NAME ' + (base + layout.product_name_x) + ',' + layout.product_name_y
                + '  SKU ' + (base + layout.sku_x) + ',' + layout.sku_y
                + '  PRICE ' + (base + layout.price_x) + ',' + layout.price_y;

            elements.appendChild(bc);
            if (showName) {
                elements.appendChild(nameEl);
            }
            elements.appendChild(skuEl);
            elements.appendChild(priceEl);
            elements.appendChild(vertEl);
            elements.appendChild(coord);
        });

        canvas.innerHTML = '';
        canvas.appendChild(guides.grid);
        canvas.appendChild(guides.bounds);
        canvas.appendChild(elements);

        var warnings = [];
        var bcW = estimateBarcodeWidth(sku, layout.barcode_width);
        var bcBad = false;
        var nameBad = false;
        var skuBad = false;
        var priceBad = false;
        var vertBad = false;
        bases.forEach(function (base, index) {
            if (sku && outside(base + layout.barcode_x, layout.barcode_y, bcW, layout.barcode_height, index, layout.width, layout.height)) {
                bcBad = true;
            }
            if (showName && outside(base + layout.product_name_x, layout.product_name_y, layout.product_name_max_width, layout.product_name_font_size * nameLines, index, layout.width, layout.height)) {
                nameBad = true;
            }
            if (sku && outside(base + layout.sku_x, layout.sku_y, estimateText(sku, layout.sku_font_size), layout.sku_font_size, index, layout.width, layout.height)) {
                skuBad = true;
            }
            if (price && outside(base + layout.price_x, layout.price_y, estimateText(price, layout.price_font_size), layout.price_font_size, index, layout.width, layout.height)) {
                priceBad = true;
            }
            if (vertical && outside(base + layout.vertical_x, layout.vertical_y, layout.vertical_font_size, estimateText(vertical, layout.vertical_font_size), index, layout.width, layout.height)) {
                vertBad = true;
            }
        });
        if (bcBad) {
            warnings.push('Barcode exceeds label boundary.');
        }
        if (nameBad) {
            warnings.push('Product name exceeds label boundary.');
        }
        if (skuBad) {
            warnings.push('SKU exceeds label boundary.');
        }
        if (priceBad) {
            warnings.push('Price exceeds label boundary.');
        }
        if (vertBad) {
            warnings.push('Vertical text exceeds label boundary.');
        }
        showWarnings(warnings);
        fitPreview();
    }

    function fitPreview() {
        var stage = byId('zl_stage');
        var scaler = byId('zl_scaler');
        var canvas = byId('zl_canvas');
        if (!stage || !scaler || !canvas) {
            return;
        }
        var width = Math.max(200, num('zl_width') || 800);
        var height = Math.max(40, num('zl_height') || 140);
        var scale = Math.min(1, Math.max(280, stage.clientWidth - 8) / width);
        canvas.style.transform = 'scale(' + scale + ')';
        scaler.style.width = Math.ceil(width * scale) + 'px';
        scaler.style.height = Math.ceil(height * scale) + 'px';
    }

    function openPrintPreview() {
        updatePreview();
        var canvas = byId('zl_canvas');
        if (!canvas) {
            setStatus('The label preview is not ready yet.', 'error');
            return;
        }
        var clone = canvas.cloneNode(true);
        clone.querySelectorAll('.zl-grid, .zl-bound, .zl-coord').forEach(function (node) {
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
        postProfile(url, payload).done(acceptProfiles).fail(function (xhr) {
            setStatus(readError(xhr), 'error');
        });
    }

    function duplicateProfile() {
        var name = profileName() || 'Label';
        postProfile(boot.urls.profiles, {
            name: name + ' copy',
            auto_name: 1,
            layout: readLayout()
        }).done(acceptProfiles).fail(function (xhr) {
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
        ask('Restore the default alignment? Column positions, barcode, product name, SKU, price, and vertical text offsets return to the Zebra defaults. This is not saved until you press Save layout.').then(function (ok) {
            if (!ok) {
                return;
            }
            var defaults = boot.defaults || {};
            alignKeys.forEach(function (key) {
                setVal('zl_' + key, defaults[key]);
            });
            setVal('zl_product_name_font_weight', defaults.product_name_font_weight || 'bold');
            setVal('zl_product_name_align', defaults.product_name_align || 'center');
            setChecked('zl_product_name_show', defaults.show_product_name !== false && defaults.show_product_name !== 0);
            setChecked('zl_product_name_wrap', defaults.product_name_wrap);
            state.barcodeKey = '';
            updatePreview();
            setStatus('Default alignment restored. Press Save layout to keep it.', 'ok');
        });
    }

    function clientPrintError() {
        if (!state.product || !state.product.variation_id) {
            return 'Select a product before printing.';
        }
        if (!state.product.barcode) {
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

    function printerMessage(err) {
        var raw = '';
        if (err && err.message) {
            raw = String(err.message);
        } else if (err) {
            raw = String(err);
        }
        var low = raw.toLowerCase();
        if (low === 'printer' || low.indexOf('printer') !== -1) {
            return 'Unable to connect to the selected printer.';
        }
        if (low.indexOf('websocket') !== -1 || low.indexOf('connection') !== -1 || low.indexOf('qz') !== -1 || low.indexOf('service') !== -1 || low.indexOf('establish') !== -1) {
            return 'Printer connection required. Please start the configured printing service.';
        }
        return 'Unable to connect to the selected printer.';
    }

    function withTimeout(promise, ms) {
        return new Promise(function (resolve, reject) {
            var timer = setTimeout(function () {
                reject(new Error('service'));
            }, ms);
            Promise.resolve(promise).then(function (value) {
                clearTimeout(timer);
                resolve(value);
            }, function (err) {
                clearTimeout(timer);
                reject(err);
            });
        });
    }

    function prepareQz() {
        window.qz.security.setCertificatePromise(function (resolve) {
            resolve();
        });
        window.qz.security.setSignaturePromise(function () {
            return function (resolve) {
                resolve();
            };
        });
    }

    function connectQz() {
        prepareQz();
        if (window.qz.websocket.isActive()) {
            return Promise.resolve();
        }
        return window.qz.websocket.connect({
            host: ['localhost'],
            usingSecure: false,
            retries: 0,
            delay: 0
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

    function loadQz() {
        if (window.qz && window.qz.websocket) {
            return Promise.resolve();
        }
        if (state.qzLoading) {
            return state.qzLoading;
        }
        state.qzLoading = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = boot.urls.qz;
            script.onload = function () {
                if (window.qz && window.qz.websocket) {
                    resolve();
                } else {
                    reject(new Error('service'));
                }
            };
            script.onerror = function () {
                reject(new Error('service'));
            };
            document.head.appendChild(script);
        }).catch(function (err) {
            state.qzLoading = null;
            throw err;
        });
        return state.qzLoading;
    }

    function zebraStylesheetHref() {
        var link = document.querySelector('link[href*="labels-zebra.css"]');
        return link ? link.href : 'css/labels-zebra.css';
    }

    function buildZebraPrintHtml(rows) {
        updatePreview();
        var canvas = byId('zl_canvas');
        if (!canvas) {
            return '';
        }
        var layout = readLayout();
        var width = Math.max(200, layout.width || 800);
        var height = Math.max(40, layout.height || 140);
        var dpi = parseInt(layout.printer_dpi, 10) || 203;
        var widthMm = (width / dpi * 25.4);
        var heightMm = (height / dpi * 25.4);
        var scale = (widthMm * 96 / 25.4) / width;
        var pages = '';
        var row;
        for (row = 0; row < rows; row++) {
            var clone = canvas.cloneNode(true);
            clone.querySelectorAll('.zl-grid, .zl-bound, .zl-coord').forEach(function (node) {
                node.remove();
            });
            clone.style.transform = 'none';
            clone.style.border = 'none';
            clone.style.width = width + 'px';
            clone.style.height = height + 'px';
            clone.classList.remove('is-coords');
            pages += '<div class="zl-sheet-page" style="width:' + widthMm.toFixed(2) + 'mm;height:' + heightMm.toFixed(2) + 'mm;overflow:hidden;page-break-after:always;break-after:page;">' +
                '<div style="width:' + width + 'px;height:' + height + 'px;transform:scale(' + scale + ');transform-origin:top left;">' +
                clone.outerHTML +
                '</div></div>';
        }
        var wCss = widthMm.toFixed(2) + 'mm';
        var hCss = heightMm.toFixed(2) + 'mm';
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Print Labels</title>' +
            '<link rel="stylesheet" href="' + zebraStylesheetHref() + '">' +
            '<style>' +
            '@page { size: ' + wCss + ' ' + hCss + '; margin: 0; }' +
            'html, body { margin: 0; padding: 0; background: #fff; }' +
            '@media print { html, body { margin: 0 !important; padding: 0 !important; } .zl-sheet-page:last-child { page-break-after: auto; break-after: auto; } }' +
            '</style></head><body>' + pages + '</body></html>';
    }

    function printLabels() {
        if (state.printing) {
            return;
        }
        var problem = clientPrintError();
        if (problem) {
            setStatus(problem, 'error');
            return;
        }
        if (!window.LabelPrintEngine || typeof window.LabelPrintEngine.openPrintDocument !== 'function') {
            setStatus('The label print function is not ready yet. Reload the page and try again.', 'error');
            return;
        }
        var rows = parseInt(text('zl_qty'), 10);
        var docHtml = buildZebraPrintHtml(rows);
        if (!docHtml) {
            setStatus('The label preview is not ready yet.', 'error');
            return;
        }
        var button = byId('zl_print');
        state.printing = true;
        button.disabled = true;
        var original = button.innerHTML;
        button.textContent = 'Printing…';
        var opened = window.LabelPrintEngine.openPrintDocument(docHtml, true);
        state.printing = false;
        button.disabled = false;
        button.innerHTML = original;
        if (!opened) {
            setStatus('Pop-up blocked. Allow pop-ups for this site to print labels.', 'error');
            return;
        }
        var labels = rows * 3;
        var done = 'Print dialog opened for ' + rows + (rows === 1 ? ' row' : ' rows') + ' (' + labels + ' labels). Choose the Zebra printer, scale 100%, margins none.';
        setStatus(done, 'ok');
        if (window.toastr) {
            toastr.success(done);
        }
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
            input.value = (parseFloat(input.value) || 0) + step;
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
                return;
            }
            if (id === 'zl_profile_name' || id === 'zl_search') {
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
            if (event.target.id === 'zl_show_grid' || event.target.id === 'zl_show_bounds' || event.target.id === 'zl_show_coords' || event.target.id === 'zl_sku_font_weight' || event.target.id === 'zl_printer_dpi' || event.target.id === 'zl_product_name_font_weight' || event.target.id === 'zl_product_name_align' || event.target.id === 'zl_product_name_show' || event.target.id === 'zl_product_name_wrap') {
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
        byId('zl_duplicate').addEventListener('click', duplicateProfile);
        byId('zl_rename').addEventListener('click', renameProfile);
        byId('zl_delete').addEventListener('click', deleteProfile);
        byId('zl_default').addEventListener('click', setDefaultProfile);
        byId('zl_reset').addEventListener('click', resetAlignment);
        byId('zl_print').addEventListener('click', printLabels);
        byId('zl_print_preview').addEventListener('click', openPrintPreview);
        byId('zl_mode_zebra').addEventListener('click', function () { showMode('zebra'); });
        byId('zl_mode_sheet').addEventListener('click', function () { showMode('sheet'); });
        window.addEventListener('resize', fitPreview);
        if (!boot.ready) {
            showMode('sheet');
        }
    });
}(jQuery));
