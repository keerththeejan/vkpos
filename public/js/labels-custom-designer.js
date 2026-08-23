/**
 * Custom sticker layout designer — positions saved in localStorage (no DB).
 */
(function(window, $) {
    'use strict';

    var STORAGE_KEY = 'vkpos_custom_label_layout';
    var DRAGGABLE_FIELDS = [
        { field: 'product_name', label: 'Product Name' },
        { field: 'variation', label: 'Variation' },
        { field: 'selling_price', label: 'Price' },
        { field: 'barcode', label: 'Barcode' },
        { field: 'qr_code', label: 'QR Code' },
        { field: 'batch_number', label: 'Batch No' },
        { field: 'packing_date', label: 'Packing Date' },
        { field: 'expiry_date', label: 'Expiry Date' },
        { field: 'business_name', label: 'Business Name' },
        { field: 'sku', label: 'SKU' },
        { field: 'company_logo', label: 'Logo' },
    ];

    var defaultLayout = function() {
        return {
            elements: [
                { field: 'product_name', x: 2, y: 5, font: 13 },
                { field: 'batch_number', x: 2, y: 35, font: 10 },
                { field: 'selling_price', x: 2, y: 58, font: 11 },
                { field: 'barcode', x: 40, y: 50, font: 10 },
            ],
        };
    };

    function getLayout() {
        try {
            var raw = $('#custom_layout_input').val();
            if (raw) {
                return JSON.parse(raw);
            }
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) { /* ignore */ }
        return defaultLayout();
    }

    function setLayout(layout) {
        var json = JSON.stringify(layout);
        $('#custom_layout_input').val(json);
        localStorage.setItem(STORAGE_KEY, json);
    }

    function isCustomLayout() {
        return $('input[name="sticker_layout"]:checked').val() === 'custom';
    }

    function toggleDesigner() {
        if (isCustomLayout()) {
            $('#custom_layout_designer').removeClass('hide');
            var layout = getLayout();
            setLayout(layout);
        } else {
            $('#custom_layout_designer').addClass('hide');
        }
    }

    function buildPalette() {
        var $palette = $('#custom_layout_palette');
        $palette.empty();
        DRAGGABLE_FIELDS.forEach(function(item) {
            $('<button type="button" class="custom-layout-field-btn">')
                .text(item.label)
                .data('field', item.field)
                .appendTo($palette);
        });
    }

    function addFieldToLayout(field) {
        var layout = getLayout();
        layout.elements = layout.elements || [];
        var exists = layout.elements.some(function(el) { return el.field === field; });
        if (exists) {
            return;
        }
        layout.elements.push({
            field: field,
            x: 5 + layout.elements.length * 3,
            y: 5 + layout.elements.length * 8,
            font: 11,
        });
        setLayout(layout);
        if (typeof scheduleLivePreview === 'function') {
            scheduleLivePreview();
        }
    }

    function syncPositionsFromPreview() {
        var $container = $('#preview_box .label-sticker-custom-layout');
        if (!$container.length) {
            return;
        }
        var layout = getLayout();
        var containerW = $container.outerWidth();
        var containerH = $container.outerHeight();
        if (!containerW || !containerH) {
            return;
        }

        $container.find('.label-sticker-custom-layout__item').each(function() {
            var $item = $(this);
            var field = $item.data('field');
            if (!field) {
                return;
            }
            var left = parseFloat($item.css('left')) || 0;
            var top = parseFloat($item.css('top')) || 0;
            var x = Math.max(0, Math.min(95, (left / containerW) * 100));
            var y = Math.max(0, Math.min(95, (top / containerH) * 100));
            var font = parseInt($item.css('font-size'), 10) || 11;

            layout.elements = (layout.elements || []).map(function(el) {
                if (el.field === field) {
                    el.x = Math.round(x * 10) / 10;
                    el.y = Math.round(y * 10) / 10;
                    el.font = font;
                }
                return el;
            });
        });

        setLayout(layout);
    }

    function initPreviewDrag() {
        if (!isCustomLayout()) {
            return;
        }

        var $container = $('#preview_box .label-sticker-custom-layout');
        if (!$container.length || typeof $.fn.draggable === 'undefined') {
            return;
        }

        $container.find('.label-sticker-custom-layout__item').each(function() {
            var $item = $(this);
            if ($item.data('ui-draggable')) {
                $item.draggable('destroy');
            }
            $item.draggable({
                containment: 'parent',
                scroll: false,
                stop: function() {
                    syncPositionsFromPreview();
                },
            });
        });
    }

    function bindEvents() {
        $('input[name="sticker_layout"]').off('change.labelCustomDesigner').on('change.labelCustomDesigner', toggleDesigner);

        $('#custom_layout_palette').on('click', '.custom-layout-field-btn', function() {
            addFieldToLayout($(this).data('field'));
        });

        $('#custom_layout_save').on('click', function() {
            syncPositionsFromPreview();
            localStorage.setItem(STORAGE_KEY, $('#custom_layout_input').val());
            if (typeof toastr !== 'undefined') {
                toastr.success('Layout saved');
            }
        });

        $('#custom_layout_reset').on('click', function() {
            setLayout(defaultLayout());
            if (typeof scheduleLivePreview === 'function') {
                scheduleLivePreview();
            }
        });
    }

    window.LabelCustomDesigner = {
        init: function() {
            buildPalette();
            bindEvents();
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                $('#custom_layout_input').val(saved);
            }
            toggleDesigner();
        },
        afterPreview: function() {
            initPreviewDrag();
            if (typeof applyAutoFontScale === 'function') {
                applyAutoFontScale();
            }
        },
        getLayout: getLayout,
        setLayout: setLayout,
    };

    $(function() {
        if ($('#custom_layout_designer').length) {
            window.LabelCustomDesigner.init();
        }
    });
})(window, jQuery);
