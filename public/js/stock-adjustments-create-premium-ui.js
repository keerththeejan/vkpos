/**
 * Stock Adjustment Create — premium UI helpers only.
 * Does not replace stock_adjustment.js. Preserves all form IDs / AJAX.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sac_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sac-dark-mode', enabled);
    document.body.classList.toggle('sac-dark-mode', enabled);
    var icon = document.querySelector('#sac_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function syncMeta() {
    var ref = ($('#ref_no').val() || '').trim() || 'Auto';
    var date = ($('#transaction_date').val() || '').trim() || '—';
    var loc = ($('#location_id option:selected').text() || '').trim() || '—';
    var type = ($('#adjustment_type option:selected').text() || '').trim() || '—';
    var typeVal = ($('#adjustment_type').val() || '').trim();

    $('#sac_meta_ref').text(ref);
    $('#sac_meta_date').text(date);
    $('#sac_meta_location').text(loc);
    $('#sac_meta_type').text(type);

    $('#sac_live_location').text(loc);
    $('#sac_live_type').text(type);

    $('.sac-type-card[data-adj-type]').removeClass('active');
    if (typeVal) {
      $('.sac-type-card[data-adj-type="' + typeVal + '"]').addClass('active');
    }
  }

  function syncSummary() {
    var rows = $('table#stock_adjustment_product_table tbody tr.product_row').length;
    if (!rows) {
      rows = $('table#stock_adjustment_product_table tbody tr').length;
    }
    $('#sac_sum_products').text(rows);
    $('#sac_live_products').text(rows);

    var qty = 0;
    $('table#stock_adjustment_product_table tbody input.product_quantity').each(function () {
      var v = parseFloat(String($(this).val()).replace(/,/g, ''));
      if (!isNaN(v)) qty += v;
    });
    $('#sac_sum_qty').text(qty);

    var recovered = ($('#total_amount_recovered').val() || '0').toString();
    $('#sac_sum_recovered').text(recovered);

    var total = ($('#total_adjustment').text() || '0.00').trim();
    $('#sac_sum_value').text(total);
    $('#sac_live_value').text(total);
  }

  function fixAutocomplete() {
    var $input = $('#search_product_for_srock_adjustment');
    if (!$input.length || !$input.data('ui-autocomplete')) return;
    try {
      $input.autocomplete('option', 'appendTo', 'body');
    } catch (e) {}
  }

  function triggerSave() {
    var $btn = $('#save_stock_adjustment');
    if ($btn.length) {
      $btn.trigger('click');
      return;
    }
    var form = document.getElementById('stock_adjustment_form');
    if (form) {
      if (typeof $(form).valid === 'function') {
        if ($(form).valid()) $(form).submit();
      } else {
        form.submit();
      }
    }
  }

  ready(function () {
    if (!$('.sac-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sac_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sac_header_save, #sac_side_save, #sac_sticky_save').on('click', function (e) {
      e.preventDefault();
      triggerSave();
    });

    $('#sac_refresh_btn').on('click', function () {
      window.location.reload();
    });

    // Type cards only for real select options: normal / abnormal
    $(document).on('click', '.sac-type-card[data-adj-type]', function () {
      var val = $(this).data('adj-type');
      if (!val) return;
      var $sel = $('#adjustment_type');
      if (!$sel.length) return;
      $sel.val(val).trigger('change');
      if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.trigger('change.select2');
      }
      syncMeta();
    });

    $(document).on(
      'change keyup input',
      '#ref_no, #transaction_date, #location_id, #adjustment_type, #total_amount_recovered, #additional_notes, #stock_adjustment_product_table input',
      function () {
        syncMeta();
        syncSummary();
      }
    );

    var tableBody = document.querySelector('#stock_adjustment_product_table tbody');
    if (tableBody && window.MutationObserver) {
      var mo = new MutationObserver(function () {
        syncSummary();
      });
      mo.observe(tableBody, { childList: true, subtree: true });
    }

    setInterval(function () {
      syncMeta();
      syncSummary();
    }, 800);

    syncMeta();
    syncSummary();

    setTimeout(fixAutocomplete, 300);
    setTimeout(fixAutocomplete, 1000);

    $(document).on('keydown', function (e) {
      if (!e.key) return;
      var tag = (e.target && e.target.tagName) || '';
      var typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || $(e.target).is('[contenteditable]');

      if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        triggerSave();
        return;
      }

      if (e.key === 'Escape') {
        var cancel = document.getElementById('sac_cancel_link');
        if (cancel) window.location.href = cancel.href;
        return;
      }

      if (e.key === 'F2') {
        e.preventDefault();
        try {
          $('#location_id').select2('open');
        } catch (err) {
          $('#location_id').focus();
        }
      } else if (e.key === 'F3') {
        e.preventDefault();
        $('#search_product_for_srock_adjustment').focus();
      } else if (e.key === 'F6') {
        e.preventDefault();
        triggerSave();
      } else if (e.key === 'F7') {
        e.preventDefault();
        window.print();
      } else if (e.key === 'F8') {
        e.preventDefault();
        $('#search_product_for_srock_adjustment').focus().select();
      } else if (e.key === 'F5' && !typing) {
        e.preventDefault();
        triggerSave();
      }
    });
  });
})();
