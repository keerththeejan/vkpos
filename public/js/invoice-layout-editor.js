/**
 * Invoice Layout Editor helpers.
 * Preview uses the existing receipt renderer via POST /invoice-layouts/{id}/preview.
 * Does not change invoice calculations or numbering.
 */
(function () {
  'use strict';

  var paperMap = {
    classic: 'A4 / normal',
    elegant: 'A4 / normal',
    detailed: 'A4 / normal',
    'columnize-taxes': 'A4 / normal',
    slim: '80mm thermal',
    slim2: '58mm thermal'
  };

  var previewTimer = null;
  var previewXhr = null;
  var saving = false;

  function shell() {
    return document.querySelector('.ile-shell');
  }

  function formEl() {
    return document.getElementById('add_invoice_layout_form');
  }

  function previewUrl() {
    var el = shell();
    return el ? el.getAttribute('data-preview-url') : '';
  }

  function vendorCss() {
    var el = shell();
    return el ? (el.getAttribute('data-vendor-css') || '') : '';
  }

  function wrapHtml(html) {
    var trimmed = String(html || '').trim();
    if (/^<!DOCTYPE/i.test(trimmed) || /^<html/i.test(trimmed)) {
      return trimmed;
    }
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
      '<link rel="stylesheet" href="' + vendorCss() + '">' +
      '<style>body{background:#fff;padding:12px;color:#111;max-width:210mm;margin:0 auto} .row{margin-left:0;margin-right:0}</style></head><body>' +
      trimmed + '</body></html>';
  }

  function previewFormData() {
    var form = formEl();
    var data = new FormData(form);
    data.delete('_method');
    data.delete('logo');
    data.delete('letter_head');
    return data;
  }

  function setPaperClass(design) {
    var wrap = document.getElementById('ile_preview_wrap');
    if (!wrap) return;
    wrap.classList.remove('paper-slim', 'paper-slim2', 'paper-normal');
    if (design === 'slim') wrap.classList.add('paper-slim');
    else if (design === 'slim2') wrap.classList.add('paper-slim2');
    else wrap.classList.add('paper-normal');
  }

  function syncChips(design) {
    $('.ile-chip').removeClass('active');
    $('.ile-chip[data-design="' + design + '"]').addClass('active');
    var badge = document.getElementById('ile_paper_badge');
    if (badge) badge.textContent = paperMap[design] || design;
  }

  function loadPreview() {
    var form = formEl();
    var url = previewUrl();
    if (!form || !url) return;

    var wrap = $('#ile_preview_wrap');
    wrap.addClass('is-loading');

    if (previewXhr && previewXhr.readyState !== 4) {
      previewXhr.abort();
    }

    previewXhr = $.ajax({
      method: 'POST',
      url: url,
      data: previewFormData(),
      processData: false,
      contentType: false,
      dataType: 'json',
      headers: { 'Accept': 'application/json' },
      success: function (result) {
        if (!result || !result.success) {
          $('#ile_preview_empty').text((result && result.msg) || (window.LANG && LANG.something_went_wrong) || 'Preview unavailable');
          wrap.removeClass('is-loaded is-loading');
          return;
        }
        var design = result.design || $('#design').val();
        setPaperClass(design);
        var iframe = document.getElementById('ile_preview_frame');
        if (iframe) {
          iframe.srcdoc = wrapHtml(result.html);
        }
        wrap.addClass('is-loaded').removeClass('is-loading');
        $('#ile_preview_meta').text(result.invoice_no ? ('#' + result.invoice_no) : '');
      },
      error: function (xhr, status) {
        if (status === 'abort') return;
        var msg = (window.LANG && LANG.something_went_wrong) || 'Preview unavailable';
        if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
        $('#ile_preview_empty').text(msg);
        wrap.removeClass('is-loaded is-loading');
      }
    });
  }

  function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(loadPreview, 450);
  }

  function savingLabel() {
    var el = shell();
    return (el && el.getAttribute('data-saving-text')) || (window.LANG && LANG.saving) || 'Saving...';
  }

  function setSaving(on) {
    saving = on;
    var label = savingLabel();
    $('#ile_save_btn, #ile_save_btn_bottom').each(function () {
      var $btn = $(this);
      if (on) {
        if (!$btn.data('orig')) $btn.data('orig', $.trim($btn.text()));
        $btn.prop('disabled', true).text(label);
      } else {
        $btn.prop('disabled', false).text($btn.data('orig') || $btn.text());
      }
    });
  }

  function bindLeaveWarning() {
    if (typeof __page_leave_confirmation !== 'function') return;
    window.onbeforeunload = null;
    __page_leave_confirmation('#add_invoice_layout_form');
    var inner = window.onbeforeunload;
    var el = shell();
    var msg = (el && el.getAttribute('data-unsaved-text')) || 'You have unsaved changes.';
    window.onbeforeunload = function () {
      if (typeof inner === 'function' && inner()) {
        return msg;
      }
    };
  }

  function printPreview() {
    var iframe = document.getElementById('ile_preview_frame');
    if (!iframe || !$('#ile_preview_wrap').hasClass('is-loaded')) {
      loadPreview();
      return;
    }
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      toastr.error((window.LANG && LANG.something_went_wrong) || 'Print unavailable');
    }
  }

  $(function () {
    if (!shell() || !formEl()) return;

    $(document).on('click', '.ile-chip', function () {
      var design = $(this).data('design');
      $('#design').val(design).trigger('change');
    });

    $('#design').on('change', function () {
      syncChips($(this).val());
    });

    $(document).on('change keyup ifChanged', '#add_invoice_layout_form :input', function () {
      if ($(this).is(':file') || $(this).attr('name') === '_token' || $(this).attr('name') === '_method') {
        return;
      }
      schedulePreview();
    });

    $('#ile_preview_btn').on('click', function () {
      loadPreview();
    });

    $('#ile_print_preview_btn').on('click', function () {
      printPreview();
    });

    $('#add_invoice_layout_form').on('submit', function (e) {
      e.preventDefault();
      if (saving) return false;
      var form = formEl();
      var $form = $(form);
      if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        if (typeof form.reportValidity === 'function') form.reportValidity();
        return false;
      }
      setSaving(true);
      $.ajax({
        method: 'POST',
        url: $form.attr('action'),
        data: new FormData(form),
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: { 'Accept': 'application/json' },
        success: function (result) {
          if (result && (result.success === 1 || result.success === true)) {
            toastr.success(result.msg);
            bindLeaveWarning();
            loadPreview();
          } else {
            toastr.error((result && result.msg) || (window.LANG && LANG.something_went_wrong));
          }
        },
        error: function (xhr) {
          var msg = window.LANG && LANG.something_went_wrong;
          if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
          toastr.error(msg);
        },
        complete: function () {
          setSaving(false);
        }
      });
      return false;
    });

    syncChips($('#design').val());
    bindLeaveWarning();
    loadPreview();
  });
})();
