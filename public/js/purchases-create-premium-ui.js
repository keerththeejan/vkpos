/**
 * Purchase Create — premium UI helpers only.
 * Does not alter purchase.js calculations or submit validation.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_pc_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('pc-dark-mode', enabled);
    document.body.classList.toggle('pc-dark-mode', enabled);
    var icon = document.querySelector('#pc_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function textOrDash($el) {
    if (!$el || !$el.length) return '—';
    if ($el.is('select')) {
      var t = ($el.find('option:selected').text() || '').trim();
      return t && t.toLowerCase().indexOf('select') === -1 ? t : '—';
    }
    var v = ($el.val() || $el.text() || '').toString().trim();
    return v || '—';
  }

  function syncHeaderMeta() {
    var ref = ($('#ref_no').val() || '').trim();
    $('#pc_meta_ref').text(ref || 'Auto');
    $('#pc_meta_location').text(textOrDash($('#location_id')));
    $('#pc_meta_supplier').text(textOrDash($('#supplier_id')));
    $('#pc_meta_date').text(textOrDash($('#transaction_date')));
    $('#pc_meta_status').text(textOrDash($('#status')));
  }

  function syncSummary() {
    $('#pc_sum_qty').text($('#total_quantity').text() || '0');
    $('#pc_sum_subtotal').text($('#total_subtotal').text() || '0');
    $('#pc_sum_discount').text($('#discount_calculated_amount').text() || '0');
    $('#pc_sum_tax').text($('#tax_calculated_amount').text() || '0');
    $('#pc_sum_grand').text($('#grand_total').text() || '0');
    $('#pc_sum_due').text($('#payment_due').text() || '0');
  }

  function syncAll() {
    syncSummary();
    syncHeaderMeta();
  }

  function triggerSave() {
    var $btn = $('#submit_purchase_form');
    if ($btn.length) $btn.trigger('click');
  }

  ready(function () {
    if (!$('.pc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#pc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#pc_header_save, #pc_side_save').on('click', function (e) {
      e.preventDefault();
      triggerSave();
    });

    $('#pc_refresh_btn').on('click', function () {
      syncAll();
    });

    // Keep sticky summary + header meta in sync with existing fields
    setInterval(syncAll, 500);
    $(document).on(
      'change keyup blur select2:select select2:clear',
      '#add_purchase_form input, #add_purchase_form select, #supplier_id, #location_id, #status, #ref_no, #transaction_date',
      function () {
        setTimeout(syncAll, 50);
      }
    );
    syncAll();
  });
})();
