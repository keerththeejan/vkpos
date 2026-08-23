$(document).ready(function() {
    $('table#product_table tbody').find('.label-date-picker').each(function() {
        $(this).datepicker({
            autoclose: true,
        });
    });

    if ($('#search_product_for_label').length > 0) {
        $('#search_product_for_label')
            .autocomplete({
                source: (typeof base_path !== 'undefined' ? base_path : '') + '/purchases/get_products?check_enable_stock=false',
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                select: function(event, ui) {
                    $(this).val(null);
                    get_label_product_row(ui.item.product_id, ui.item.variation_id);
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                return $('<li>')
                    .append('<div>' + item.text + '</div>')
                    .appendTo(ul);
            };
    }

    $('input#is_show_price').change(function() {
        if ($(this).is(':checked')) {
            $('div#price_type_div').show();
        } else {
            $('div#price_type_div').hide();
        }
        scheduleLivePreview();
    });

    $('button#labels_preview').click(function() {
        if ($('form#preview_setting_form table#product_table tbody tr').length > 0) {
            if (window.LabelPrintEngine && typeof window.LabelPrintEngine.openPrintFromPreview === 'function') {
                window.LabelPrintEngine.openPrintFromPreview(true);
            } else {
                var url = base_path + '/labels/preview?' + serializeLabelPreviewForm();
                window.open(url, 'newwindow');
            }
        } else {
            swal(LANG.label_no_product_error).then(function() {
                $('#search_product_for_label').focus();
            });
        }
    });

    $(document).on('click', 'button#print_label', function() {
        window.print();
    });

    $('form#preview_setting_form').on(
        'change input',
        'input, select, textarea',
        function() {
            if (window.LabelDesigner && typeof window.LabelDesigner.syncAdvancedToggles === 'function') {
                if (this.id !== 'ld_print_copies') {
                    window.LabelDesigner.syncAdvancedToggles();
                }
            }
            scheduleLivePreview();
        }
    );

    $('input[name="sticker_layout"]').on('change', function() {
        scheduleLivePreview();
        if (window.LabelCustomDesigner) {
            var layout = $('input[name="sticker_layout"]:checked').val();
            if (layout === 'custom') {
                var current = window.LabelCustomDesigner.getLayout();
                window.LabelCustomDesigner.setLayout(current);
            }
        }
    });

    if ($('form#preview_setting_form table#product_table tbody tr').length > 0) {
        scheduleLivePreview();
    }
});

function serializeLabelPreviewForm() {
    var $form = $('form#preview_setting_form');
    $form.find('input.ld-print-explicit-off').remove();
    $form.find('input[type="checkbox"][name^="print["]').each(function() {
        if (!this.checked) {
            $('<input>', { type: 'hidden', 'class': 'ld-print-explicit-off', name: this.name, value: '0' }).appendTo($form);
        }
    });
    return $form.serialize();
}

window.serializeLabelPreviewForm = serializeLabelPreviewForm;

function scheduleLivePreview() {
    if ($('form#preview_setting_form table#product_table tbody tr').length === 0) {
        showPreviewEmpty('Add a product to see the live preview.');
        return;
    }

    clearTimeout(window.labelLivePreviewTimer);
    window.labelLivePreviewTimer = setTimeout(refreshLivePreview, 350);
}

function showPreviewEmpty(message) {
    $('#preview_box').html(
        '<div class="ld-preview-empty">' +
        '<i class="fa fa-tag"></i>' +
        '<p>' + (message || 'No preview available.') + '</p>' +
        '</div>'
    );
}

function showPreviewError(message) {
    $('#preview_box').html(
        '<div class="ld-preview-empty ld-preview-empty--error">' +
        '<i class="fa fa-exclamation-triangle"></i>' +
        '<p>' + (message || 'Could not load label preview.') + '</p>' +
        '<button type="button" class="ld-btn ld-btn--outline ld-btn--sm" onclick="refreshLivePreview()">' +
        '<i class="fa fa-refresh"></i> Retry</button>' +
        '</div>'
    );
}

function refreshLivePreview() {
    if ($('form#preview_setting_form table#product_table tbody tr').length === 0) {
        showPreviewEmpty('Add a product to see the live preview.');
        return;
    }

    $('#label_live_preview_loading').removeClass('hide');

    $.ajax({
        method: 'GET',
        url: base_path + '/labels/preview',
        data: serializeLabelPreviewForm() + '&live_preview=1',
        dataType: 'json',
        success: function(result) {
            if (!result || !result.success) {
                showPreviewError((result && result.msg) ? result.msg : 'Preview could not be generated. Check product and label settings.');
                return;
            }

            if (!result.html || !$.trim(result.html).length) {
                showPreviewError('Preview returned empty content. Verify the product has a barcode/SKU.');
                return;
            }

            var $frag = $('<div>').html(result.html);
            $frag.find('link, style').remove();

            if (!$frag.find('.thermal-label-sheet').length) {
                showPreviewError('Label layout did not render. Try changing the template or barcode setting.');
                return;
            }

            var html = $frag.html();

            if (window.LabelDesigner && typeof window.LabelDesigner.wrapPreviewHtml === 'function') {
                html = window.LabelDesigner.wrapPreviewHtml(html);
            }

            $('#preview_box').html(html);

            var previewRoot = document.getElementById('preview_box');
            if (window.LabelPrintEngine && typeof window.LabelPrintEngine.refreshPreview === 'function') {
                window.LabelPrintEngine.refreshPreview(previewRoot);
            } else if (window.LabelPrintEngine) {
                window.LabelPrintEngine.applyLayoutFromForm(previewRoot);
                window.LabelPrintEngine.normalize(previewRoot);
            }

            var barcodes = previewRoot.querySelectorAll('.label-sticker-barcode');
            var hasContent = previewRoot.querySelector(
                '.label-sticker-line2__name, .label-sticker-line1__name, .label-sticker-line3__name, .label-sticker-price, .mfg-field'
            );

            if (!barcodes.length && !previewRoot.querySelector('.label-sticker-qr') && !hasContent) {
                showPreviewError('Label content did not render. Check field toggles in the Fields tab.');
                return;
            }

            barcodes.forEach(function(img) {
                img.addEventListener('error', function onBarcodeError() {
                    img.removeEventListener('error', onBarcodeError);
                    var err = previewRoot.querySelector('.ld-preview-barcode-error');
                    if (!err) {
                        var note = document.createElement('p');
                        note.className = 'ld-preview-barcode-error';
                        note.textContent = 'Barcode image could not be loaded for this SKU.';
                        previewRoot.querySelector('.ld-preview-chrome__paper') &&
                            previewRoot.querySelector('.ld-preview-chrome__paper').appendChild(note);
                    }
                }, { once: true });

                if (!img.complete) {
                    img.addEventListener('load', function() {
                        if (window.LabelPrintEngine && typeof window.LabelPrintEngine.refreshPreview === 'function') {
                            window.LabelPrintEngine.refreshPreview(previewRoot);
                        }
                    }, { once: true });
                }
            });

            applyAutoFontScale();

            if (window.LabelPrintEngine && typeof window.LabelPrintEngine.lockRenderedStyles === 'function') {
                window.LabelPrintEngine.lockRenderedStyles(previewRoot);
            }

            if (window.LabelDesigner && typeof window.LabelDesigner.applyPreviewChrome === 'function') {
                window.LabelDesigner.applyPreviewChrome();
            }
            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('#preview_box'));
            }
            if (window.LabelCustomDesigner && typeof window.LabelCustomDesigner.afterPreview === 'function') {
                window.LabelCustomDesigner.afterPreview();
            }
        },
        error: function(xhr) {
            var msg = 'Could not load preview.';
            if (xhr.status === 401 || xhr.status === 419) {
                msg = 'Session expired. Please refresh the page and log in again.';
            } else if (xhr.status === 404) {
                msg = 'Preview endpoint not found. Check your installation URL.';
            } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            showPreviewError(msg);
        },
        complete: function() {
            $('#label_live_preview_loading').addClass('hide');
        },
    });
}

function applyAutoFontScale() {
    var $box = $('#preview_box');
    if (window.LabelPrintEngine) {
        return;
    }
    $box.find('[data-base-size]').each(function() {
        var $el = $(this);
        var base = parseInt($el.data('base-size'), 10) || 15;
        var text = $el.text().trim();
        var len = text.length;
        var budget = 28;
        var min = 6;
        var size = base;
        if (len > budget) {
            size = Math.max(min, Math.floor(base * budget / len));
        }
        $el.css('font-size', size + 'px');
    });
}

function get_label_product_row(product_id, variation_id) {
    if (product_id) {
        var row_count = $('table#product_table tbody tr').length;
        $.ajax({
            method: 'GET',
            url: base_path + '/labels/add-product-row',
            dataType: 'html',
            data: { product_id: product_id, row_count: row_count, variation_id: variation_id },
            success: function(result) {
                $('table#product_table tbody').append(result);

                $('table#product_table tbody').find('.label-date-picker').each(function() {
                    $(this).datepicker({
                        autoclose: true,
                    });
                });

                scheduleLivePreview();
            },
            error: function() {
                swal('Could not add product row.');
            },
        });
    }
}
