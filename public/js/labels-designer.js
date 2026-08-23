/**
 * VKPOS Professional Label Designer — Zebra ZD230 @ 203 DPI
 * 8 ready templates · design_template blade routing · no backend changes
 */
(function (window, $) {
    'use strict';

    var STORAGE_PREFIX = 'vkpos_label_template_';

    var TEMPLATES = {
        t1_standard: {
            num: 1,
            name: 'Standard Label',
            desc: 'Product Name · Barcode · Price',
            icon: 'fa-tag',
            design: '',
            layout: 'line2',
            align: 'center',
            weight: 'bold',
            fields: {
                name: 1, price: 1, show_barcode: 1, show_sku: 1,
                business_name: 0, variations: 0, packing_date: 0, lot_number: 0,
            },
        },
        t2_sku: {
            num: 2,
            name: 'SKU Label',
            desc: 'Product Name · SKU · Barcode',
            icon: 'fa-hashtag',
            design: '',
            layout: 'line2',
            align: 'center',
            fields: { name: 1, price: 0, show_barcode: 1, show_sku: 1 },
        },
        t3_mrp_offer: {
            num: 3,
            name: 'MRP / Offer',
            desc: 'Product Name · Barcode · MRP · Offer Price',
            icon: 'fa-tags',
            design: 'mrp_offer',
            layout: 'line2',
            align: 'center',
            fields: { name: 1, price: 1, show_mrp: 1, show_barcode: 1, show_sku: 1 },
        },
        t4_text_card: {
            num: 4,
            name: 'Text Only Card',
            desc: 'Header · Name · Part No · Batch · Date · Price',
            icon: 'fa-file-text-o',
            design: 'text_card',
            layout: 'default',
            align: 'center',
            weight: 'bold',
            textLines: 2,
            fields: {
                business_name: 1, name: 1, lot_number: 1, packing_date: 1, price: 1,
                show_barcode: 0, show_qr_code: 0, show_sku: 1, variations: 0,
            },
        },
        t5_warehouse: {
            num: 5,
            name: 'Warehouse Label',
            desc: 'SKU · Name · Batch · Barcode',
            icon: 'fa-industry',
            design: 'warehouse',
            layout: 'mfg_line2',
            align: 'left',
            fields: { name: 1, show_sku: 1, show_barcode: 1, lot_number: 1, packing_date: 1, price: 0 },
        },
        t6_retail_shelf: {
            num: 6,
            name: 'Retail Shelf Label',
            desc: 'Store · Name · Price · Barcode',
            icon: 'fa-shopping-cart',
            design: 'retail_shelf',
            layout: 'line3',
            align: 'center',
            fields: { business_name: 1, name: 1, price: 1, show_barcode: 1, show_sku: 1 },
            price_type: 'inclusive',
        },
        t7_inventory: {
            num: 7,
            name: 'Inventory Label',
            desc: 'Name · SKU · Lot · Expiry · Barcode',
            icon: 'fa-cubes',
            design: '',
            layout: 'default',
            align: 'left',
            fields: { name: 1, variations: 1, lot_number: 1, exp_date: 1, show_sku: 1, show_barcode: 1, price: 0 },
        },
        t8_qr: {
            num: 8,
            name: 'QR Code Label',
            desc: 'Product Name · QR Code',
            icon: 'fa-qrcode',
            design: '',
            layout: 'line2',
            align: 'center',
            fields: { name: 1, price: 0, show_barcode: 0, show_qr_code: 1 },
        },
    };

    var DISPLAY_IDS = [
        't1_standard', 't2_sku', 't3_mrp_offer', 't4_text_card',
        't5_warehouse', 't6_retail_shelf', 't7_inventory', 't8_qr',
    ];

    var activeTemplateId = 't1_standard';
    var suppressPrintSync = false;

    function getMeta() { return window.LD_BARCODE_META || {}; }
    function getMetaById(id) { var m = getMeta(); return m[id] || m[String(id)] || null; }

    function init() {
        if (!$('#preview_setting_form').length) return;

        buildTemplateGallery();
        buildSizeChips();
        bindTabs();
        bindZoom();
        bindActions();
        bindSidebarToggles();
        bindPrintSettings();
        bindFontPriceOptions();

        $('#barcode_setting').on('change', function () {
            syncPrintSettingsFromBarcode();
            bindBarcodeSettingInfo();
            scheduleLivePreviewSafe();
        });

        bindLayoutPicker();
        bindBarcodeSettingInfo();
        applyZebra5025(false);
        applyTemplate('t1_standard');
        applyPreviewZoom(100);
    }

    function bindLayoutPicker() {
        $(document).on('click', '[data-set-layout]', function () {
            var layout = $(this).data('set-layout');
            if (!layout) return;
            $('input[name="sticker_layout"][value="' + layout + '"]').prop('checked', true).trigger('change');
            if (layout === 'custom' && window.LabelCustomDesigner && typeof window.LabelCustomDesigner.init === 'function') {
                $('#custom_layout_designer').removeClass('hide');
            }
            scheduleLivePreviewSafe();
        });
    }

    function scheduleLivePreviewSafe() {
        if (typeof scheduleLivePreview === 'function') scheduleLivePreview();
    }

    function bindTabs() {
        $(document).on('click', '.ld-tab', function () {
            var target = $(this).data('tab');
            $('.ld-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.ld-tab-pane').removeClass('is-active');
            $('#' + target).addClass('is-active');
        });
    }

    function buildTemplateGallery() {
        var $list = $('#ld_template_grid');
        if (!$list.length) return;
        $list.empty();

        DISPLAY_IDS.forEach(function (id) {
            var t = TEMPLATES[id];
            if (!t) return;
            var $item = $('<div class="ld-template-pro" role="button" tabindex="0">')
                .attr('data-template-id', id)
                .append('<div class="ld-template-pro__num">Template ' + t.num + '</div>')
                .append('<div class="ld-template-pro__head"><i class="fa ' + t.icon + '"></i><strong>' + escapeHtml(t.name) + '</strong></div>')
                .append('<div class="ld-template-pro__desc">' + escapeHtml(t.desc) + '</div>')
                .append('<div class="ld-template-pro__sketch">' + buildSketch(t) + '</div>');
            $list.append($item);
        });

        $list.on('click keypress', '.ld-template-pro', function (e) {
            if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) return;
            applyTemplate($(this).data('template-id'));
        });
    }

    function buildSketch(t) {
        if (t.design === 'text_card') {
            return '<span class="sk-line sk-line--hdr"></span><span class="sk-line"></span><span class="sk-line sk-line--sm"></span><span class="sk-line sk-line--sm"></span><span class="sk-line sk-line--price"></span>';
        }
        if (t.design === 'warehouse') {
            return '<span class="sk-line sk-line--sku"></span><span class="sk-line"></span><span class="sk-barcode"></span>';
        }
        if (t.design === 'retail_shelf') {
            return '<span class="sk-line sk-line--sm"></span><span class="sk-line sk-line--hdr"></span><span class="sk-line sk-line--price"></span><span class="sk-barcode sk-barcode--sm"></span>';
        }
        if (t.num === 8) {
            return '<span class="sk-line"></span><span class="sk-qr"></span>';
        }
        return '<span class="sk-line"></span><span class="sk-barcode"></span><span class="sk-line sk-line--price"></span>';
    }

    function buildSizeChips() {
        var sizes = [
            { w: 38, h: 25 }, { w: 50, h: 25 }, { w: 50, h: 30 },
            { w: 58, h: 40 }, { w: 75, h: 50 }, { w: 100, h: 50 },
        ];
        var $wrap = $('#ld_size_chips');
        if (!$wrap.length) return;
        $wrap.empty();
        sizes.forEach(function (s) {
            $wrap.append($('<button type="button" class="ld-size-chip">').attr('data-w', s.w).attr('data-h', s.h).text(s.w + ' × ' + s.h + ' mm'));
        });
        $wrap.on('click', '.ld-size-chip', function () {
            $('#ld_print_width').val($(this).data('w'));
            $('#ld_print_height').val($(this).data('h'));
            syncBarcodeFromPrintSettings();
        });
    }

    function applyTemplate(templateId) {
        var t = TEMPLATES[templateId];
        if (!t) return;

        activeTemplateId = templateId;
        $('.ld-template-pro').removeClass('is-selected');
        $('.ld-template-pro[data-template-id="' + templateId + '"]').addClass('is-selected');

        $('#design_template').val(t.design || '');
        $('input[name="sticker_layout"][value="' + t.layout + '"]').prop('checked', true).trigger('change');

        if (t.align) {
            $('#ld_text_align_select').val(t.align);
            $('#ld_text_align_field').val(t.align);
        }
        if (t.weight) {
            $('#ld_text_weight_select').val(t.weight);
            $('#ld_text_weight_field').val(t.weight);
        }
        if (t.textLines) {
            $('#ld_text_lines').val(t.textLines);
            $('#ld_text_lines_select').val(String(t.textLines));
        }

        var fieldKeys = Object.keys(t.fields);
        $('#preview_setting_form input[type="checkbox"][name^="print["]').each(function () {
            var name = this.name.replace(/^print\[|\]$/g, '');
            if (fieldKeys.indexOf(name) >= 0) {
                $(this).prop('checked', !!t.fields[name]);
            }
        });

        if (t.price_type) {
            $('select[name="print[price_type]"]').val(t.price_type);
            $('#ld_price_display').val(t.price_type);
        }

        togglePriceTypeDiv();
        syncAdvancedToggles();
        scheduleLivePreviewSafe();
    }

    function bindPrintSettings() {
        $('#ld_apply_zebra_5025').on('click', function () { applyZebra5025(true); });
        $('#ld_btn_print_test').on('click', printTest);

        var sel = '#ld_print_width,#ld_print_height,#ld_print_columns,#ld_print_col_gap,#ld_print_row_gap,#ld_print_margin_top,#ld_print_margin_left,#ld_print_start_pos';
        $(document).on('change input', sel, function () {
            if (!suppressPrintSync) syncBarcodeFromPrintSettings();
            updateDotsCalc();
            scheduleLivePreviewSafe();
        });

        $('#ld_print_copies').on('change input', function () {
            var qty = parseInt($(this).val(), 10) || 1;
            var $f = $('table#product_table tbody input[name$="[quantity]"]').first();
            if ($f.length) $f.val(qty);
            updateCopiesCount();
            scheduleLivePreviewSafe();
        });
    }

    function printTest() {
        if (!$('table#product_table tbody tr').length) {
            swal(LANG.label_no_product_error);
            return;
        }
        window.open(base_path + '/labels/preview?' + (window.serializeLabelPreviewForm ? window.serializeLabelPreviewForm() : $('#preview_setting_form').serialize()), 'label_test');
    }

    function bindFontPriceOptions() {
        $('#ld_text_lines_select').on('change', function () {
            $('#ld_text_lines').val($(this).val());
            scheduleLivePreviewSafe();
        });
        $('#ld_text_align_select').on('change', function () {
            $('#ld_text_align_field').val($(this).val());
            scheduleLivePreviewSafe();
        });
        $('#ld_text_weight_select').on('change', function () {
            $('#ld_text_weight_field').val($(this).val());
            scheduleLivePreviewSafe();
        });
        $('#ld_price_display').on('change', function () {
            $('select[name="print[price_type]"]').val($(this).val()).trigger('change');
        });
        $('select[name="print[price_type]"]').on('change', function () {
            $('#ld_price_display').val($(this).val());
        });
    }

    function applyZebra5025(forceTemplate) {
        var target = window.LD_ZEBRA_TARGET || { width_mm: 50, height_mm: 25, columns: 2, col_gap_mm: 2, row_gap_mm: 2 };
        suppressPrintSync = true;
        $('#ld_print_width').val(target.width_mm);
        $('#ld_print_height').val(target.height_mm);
        $('#ld_print_columns').val(String(target.columns));
        $('#ld_print_col_gap').val(target.col_gap_mm);
        $('#ld_print_row_gap').val(target.row_gap_mm);
        suppressPrintSync = false;
        syncBarcodeFromPrintSettings();
        updateDotsCalc();
        $('#ld_preview_rounded').prop('checked', true);
        if (forceTemplate) applyTemplate('t1_standard');
    }

    function syncBarcodeFromPrintSettings() {
        var preset = findClosestPreset({
            w: parseFloat($('#ld_print_width').val()) || 50,
            h: parseFloat($('#ld_print_height').val()) || 25,
            cols: parseInt($('#ld_print_columns').val(), 10) || 2,
            col_gap: parseFloat($('#ld_print_col_gap').val()) || 0,
            row_gap: parseFloat($('#ld_print_row_gap').val()) || 0,
        });
        if (preset && String($('#barcode_setting').val()) !== String(preset.id)) {
            $('#barcode_setting').val(preset.id);
        }
        syncPrintSettingsFromBarcode();
        bindBarcodeSettingInfo();
    }

    function findClosestPreset(target) {
        var best = null, bestScore = Infinity;
        Object.keys(getMeta()).forEach(function (key) {
            var b = getMeta()[key];
            var score = Math.abs(b.width_mm - target.w) * 2 + Math.abs(b.height_mm - target.h) * 2 +
                (b.stickers_in_one_row !== target.cols ? 10 : 0) +
                Math.abs(b.col_gap_mm - target.col_gap) * 0.5;
            if (score < bestScore) { bestScore = score; best = b; }
        });
        return best;
    }

    function syncPrintSettingsFromBarcode() {
        var m = getMetaById($('#barcode_setting').val());
        if (!m) return;
        suppressPrintSync = true;
        $('#ld_print_width').val(m.width_mm);
        $('#ld_print_height').val(m.height_mm);
        $('#ld_print_columns').val(String(m.stickers_in_one_row || 1));
        $('#ld_print_col_gap').val(m.col_gap_mm);
        $('#ld_print_row_gap').val(m.row_gap_mm);
        $('#ld_print_margin_top').val(m.margin_top_mm);
        $('#ld_print_margin_left').val(m.margin_left_mm);
        suppressPrintSync = false;
        $('#ld_feed_type').html('<i class="fa fa-scroll"></i> ' + (m.is_continuous ? 'Continuous Roll' : 'Gap Labels'));
        updateDotsCalc();
    }

    function updateDotsCalc() {
        var w = parseFloat($('#ld_print_width').val()) || 50;
        var h = parseFloat($('#ld_print_height').val()) || 25;
        var cols = parseInt($('#ld_print_columns').val(), 10) || 1;
        var dots = Math.round(w * 8) + ' × ' + Math.round(h * 8) + ' dots';
        if (cols > 1) dots += ' · ' + cols + '-up';
        $('#ld_dots_calc').text(dots);
        highlightSizeChip(w, h);
    }

    function bindZoom() {
        $(document).on('click', '.ld-zoom-btn', function () {
            applyPreviewZoom(parseInt($(this).data('zoom'), 10));
        });
    }

    function applyPreviewZoom(pct) {
        $('.ld-zoom-btn').removeClass('is-active');
        $('.ld-zoom-btn[data-zoom="' + pct + '"]').addClass('is-active');
        $('#ld_preview_viewport').css('transform', 'scale(' + pct / 100 + ')');
        $('#ld_zoom_label').text(pct + '%');
    }

    function bindActions() {
        $('#ld_btn_print').on('click', function () { $('#labels_preview').trigger('click'); });
        $('#ld_btn_print_all').on('click', function () {
            if (!$('table#product_table tbody tr').length) { swal(LANG.label_no_product_error); return; }
            if (window.LabelPrintEngine && typeof window.LabelPrintEngine.canPrintFromPreview === 'function' &&
                window.LabelPrintEngine.canPrintFromPreview()) {
                window.LabelPrintEngine.openPrintFromPreview(true);
            } else if (window.LabelPrintEngine && typeof window.LabelPrintEngine.openPrintFromServer === 'function') {
                window.LabelPrintEngine.openPrintFromServer(true);
            } else {
                window.open(base_path + '/labels/preview?' + (window.serializeLabelPreviewForm ? window.serializeLabelPreviewForm() : $('#preview_setting_form').serialize()), 'label_print_all');
            }
        });
        $('#ld_btn_preview_refresh').on('click', function () {
            if (typeof refreshLivePreview === 'function') refreshLivePreview();
        });
        $('#ld_btn_reset').on('click', function () {
            if (confirm('Reset all label options?')) resetFormDefaults();
        });
        $('#ld_btn_save_template').on('click', saveTemplate);
        $('#ld_btn_load_template').on('click', loadTemplate);
        $('#ld_btn_duplicate').on('click', duplicateFirstProductRow);
    }

    function resetFormDefaults() {
        applyZebra5025(false);
        applyTemplate('t1_standard');
        scheduleLivePreviewSafe();
    }

    function saveTemplate() {
        var name = prompt('Template name:');
        if (!name) return;
        try {
            localStorage.setItem(STORAGE_PREFIX + name, JSON.stringify({
                serialized: window.serializeLabelPreviewForm ? window.serializeLabelPreviewForm() : $('#preview_setting_form').serialize(),
                templateId: activeTemplateId,
                savedAt: new Date().toISOString(),
            }));
            if (typeof toastr !== 'undefined') toastr.success('Saved: ' + name);
        } catch (e) { alert('Save failed'); }
    }

    function loadTemplate() {
        var names = [];
        for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);
            if (k && k.indexOf(STORAGE_PREFIX) === 0) names.push(k.replace(STORAGE_PREFIX, ''));
        }
        if (!names.length) { alert('No saved templates'); return; }
        var name = prompt('Load:\n' + names.join('\n'));
        if (!name) return;
        var raw = localStorage.getItem(STORAGE_PREFIX + name);
        if (!raw) return;
        try {
            var data = JSON.parse(raw);
            if (data.templateId) applyTemplate(data.templateId);
            if (data.serialized) {
                $('#preview_setting_form input[type="checkbox"][name^="print["]').prop('checked', false);
                new URLSearchParams(data.serialized).forEach(function (val, key) {
                    var $el = $('[name="' + key.replace(/"/g, '\\"') + '"]');
                    if ($el.attr('type') === 'checkbox') {
                        $el.prop('checked', val === '1' || val === 'on');
                    } else if ($el.attr('type') === 'radio') {
                        $el.filter('[value="' + val + '"]').prop('checked', true);
                    } else if ($el.length) {
                        $el.val(val);
                    }
                });
            }
            syncAdvancedToggles();
            scheduleLivePreviewSafe();
        } catch (e) { alert('Invalid template'); }
    }

    function duplicateFirstProductRow() {
        var $first = $('table#product_table tbody tr').first();
        if (!$first.length) return;
        var n = $('table#product_table tbody tr').length;
        var $c = $first.clone();
        $c.find('[name]').each(function () {
            this.name = this.name.replace(/\[\d+\]/, '[' + n + ']');
        });
        $c.find('.label-date-picker').removeClass('hasDatepicker').removeAttr('id');
        $('table#product_table tbody').append($c);
        $c.find('.label-date-picker').datepicker({ autoclose: true });
        updateCopiesCount();
        scheduleLivePreviewSafe();
    }

    function validateProductQuantities() {
        $('table#product_table tbody input[name$="[quantity]"]').each(function () {
            var v = parseInt($(this).val(), 10);
            if (isNaN(v) || v < 1) {
                $(this).val(1);
            }
        });
    }

    function bindBarcodeSettingInfo() {
        var m = getMetaById($('#barcode_setting').val());
        var text = $('#barcode_setting option:selected').text() || '';
        $('#ld_barcode_setting_name').text(text.trim() || '—');
        if (m) {
            $('#ld_preview_meta').text(m.width_mm + '×' + m.height_mm + ' mm · ' + (m.stickers_in_one_row || 1) + '-up · gap ' + m.col_gap_mm + 'mm');
            $('#ld_label_dims').text(m.width_mm + ' × ' + m.height_mm + ' mm');
        }
        updateCopiesCount();
        updateDotsCalc();
    }

    function highlightSizeChip(w, h) {
        $('.ld-size-chip').removeClass('is-match').each(function () {
            if (Math.abs($(this).data('w') - w) < 1.5 && Math.abs($(this).data('h') - h) < 1.5) {
                $(this).addClass('is-match');
            }
        });
    }

    function updateCopiesCount() {
        var total = 0;
        $('table#product_table tbody input[name$="[quantity]"]').each(function () {
            total += parseInt($(this).val(), 10) || 0;
        });
        $('#ld_total_copies').text(total || 0);
        if (!$('#ld_print_copies').is(':focus')) {
            var f = parseInt($('table#product_table tbody input[name$="[quantity]"]').first().val(), 10) || 1;
            $('#ld_print_copies').val(f);
        }
    }

    function bindSidebarToggles() {
        var map = {
            ld_adv_logo: 'print[show_company_logo]', ld_adv_product_code: 'print[show_product_code]',
            ld_adv_sku: 'print[show_sku]', ld_adv_expiry: 'print[exp_date]', ld_adv_batch: 'print[show_batch_number]',
            ld_adv_qr: 'print[show_qr_code]', ld_adv_barcode: 'print[show_barcode]',
            ld_adv_name: 'print[name]', ld_adv_price: 'print[price]',
        };
        $(document).on('change', '#ld_adv_logo,#ld_adv_product_code,#ld_adv_sku,#ld_adv_expiry,#ld_adv_batch,#ld_adv_qr,#ld_adv_barcode,#ld_adv_name,#ld_adv_price', function () {
            if (map[this.id]) $('input[name="' + map[this.id] + '"]').prop('checked', this.checked).trigger('change');
        });
        $(document).on('change', '#ld_show_barcode_text', function () {
            $('input[name="print[show_sku]"]').prop('checked', this.checked).trigger('change');
        });
        $(document).on('change', '#ld_preview_border,#ld_preview_rounded', applyPreviewChrome);
        $('table#product_table').on('change input', 'input[name$="[quantity]"]', function () {
            validateProductQuantities();
            updateCopiesCount();
        });
    }

    function syncAdvancedToggles() {
        var map = {
            ld_adv_logo: 'print[show_company_logo]', ld_adv_product_code: 'print[show_product_code]',
            ld_adv_sku: 'print[show_sku]', ld_adv_expiry: 'print[exp_date]', ld_adv_batch: 'print[show_batch_number]',
            ld_adv_qr: 'print[show_qr_code]', ld_adv_barcode: 'print[show_barcode]',
            ld_adv_name: 'print[name]', ld_adv_price: 'print[price]',
        };
        Object.keys(map).forEach(function (id) {
            var $s = $('input[name="' + map[id] + '"]');
            if ($s.length) $('#' + id).prop('checked', $s.is(':checked'));
        });
        $('#ld_show_barcode_text').prop('checked', $('input[name="print[show_sku]"]').is(':checked'));
        togglePriceTypeDiv();
    }

    function togglePriceTypeDiv() { $('#price_type_div').toggle($('input#is_show_price').is(':checked')); }

    function applyPreviewChrome() {
        var border = $('#ld_preview_border').is(':checked');
        var rounded = $('#ld_preview_rounded').is(':checked');
        $('#preview_box .label-sticker-wrap').toggleClass('label-sticker-wrap--border', border);
        $('#preview_box .label-sticker-wrap').toggleClass('label-sticker-wrap--rounded', rounded);
    }

    function wrapPreviewHtml(html) {
        var $tmp = $('<div>').html(html);
        $tmp.find('link, style').remove();
        var cleanHtml = $tmp.html();
        var sheet = $tmp.find('.thermal-label-sheet').first();
        var w = sheet.attr('data-label-w-mm') || $('#ld_print_width').val() || '50';
        var h = sheet.attr('data-label-h-mm') || $('#ld_print_height').val() || '25';
        var cols = $('#ld_print_columns').val() || sheet.attr('data-stickers-per-row') || '1';
        var colGap = sheet.attr('data-col-gap-mm') || $('#ld_print_col_gap').val() || '0';
        var labelCount = (window.LabelPrintEngine && typeof window.LabelPrintEngine.countExpectedLabelsFromForm === 'function')
            ? window.LabelPrintEngine.countExpectedLabelsFromForm(document.getElementById('preview_setting_form'))
            : $tmp.find('.thermal-label-cell .label-sticker-wrap').length;
        var m = getMetaById($('#barcode_setting').val());
        var feed = m && m.is_continuous ? 'Continuous' : 'Gap labels';

        return '<div class="ld-preview-chrome">' +
            '<div class="ld-preview-chrome__dims">' + w + ' × ' + h + ' mm · ' + cols + '-col · ' + labelCount + ' label' + (labelCount === 1 ? '' : 's') + ' · 203 DPI</div>' +
            '<div class="ld-preview-chrome__feed">' + feed + ' · gap ' + colGap + ' mm</div>' +
            '<div class="ld-preview-chrome__paper">' +
            '<div class="ld-preview-chrome__outline"></div>' +
            '<div class="ld-preview-chrome__safe" title="Safe margin 1mm"></div>' +
            cleanHtml + '</div>' +
            '<div class="ld-preview-chrome__dpi">' + Math.round(parseFloat(w) * 8) + '×' + Math.round(parseFloat(h) * 8) + ' dots · Zebra ZD230</div></div>';
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    window.LabelDesigner = {
        init: init, applyTemplate: applyTemplate, applyPreviewZoom: applyPreviewZoom,
        wrapPreviewHtml: wrapPreviewHtml, applyPreviewChrome: applyPreviewChrome,
        updateCopiesCount: updateCopiesCount, bindBarcodeSettingInfo: bindBarcodeSettingInfo,
        syncAdvancedToggles: syncAdvancedToggles, scheduleLivePreviewSafe: scheduleLivePreviewSafe,
        TEMPLATES: TEMPLATES,
    };

    $(document).ready(init);
})(window, jQuery);
