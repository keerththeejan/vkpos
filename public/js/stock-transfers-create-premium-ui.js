/**
 * Stock Transfer Create — premium UI helpers only.
 * Does not replace stock_transfer.js. Preserves all form IDs / AJAX.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_stc_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('stc-dark-mode', enabled);
    document.body.classList.toggle('stc-dark-mode', enabled);
    var icon = document.querySelector('#stc_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function syncMeta() {
    var ref = ($('#ref_no').val() || '').trim() || 'Auto';
    var date = ($('#transaction_date').val() || '').trim() || '—';
    var status = ($('#status option:selected').text() || '').trim() || '—';
    var from = ($('#location_id option:selected').text() || '').trim() || '—';
    var to = ($('#transfer_location_id option:selected').text() || '').trim() || '—';

    $('#stc_meta_ref').text(ref);
    $('#stc_meta_date').text(date);
    $('#stc_meta_status').text(status);
    $('#stc_meta_from').text(from);
    $('#stc_meta_to').text(to);

    $('#stc_live_from').text(from);
    $('#stc_live_to').text(to);
  }

  function syncSummary() {
    var rows = $('table#stock_adjustment_product_table tbody tr.product_row').length;
    if (!rows) {
      rows = $('table#stock_adjustment_product_table tbody tr').length;
    }
    $('#stc_sum_products').text(rows);

    var qty = 0;
    $('table#stock_adjustment_product_table tbody input.product_quantity').each(function () {
      var v = parseFloat(String($(this).val()).replace(/,/g, ''));
      if (!isNaN(v)) qty += v;
    });
    $('#stc_sum_qty').text(qty);

    var ship = ($('#shipping_charges').val() || '0').toString();
    $('#stc_sum_shipping').text(ship);

    var adj = ($('#total_adjustment').text() || '0.00').trim();
    var grand = ($('#final_total_text').text() || '0.00').trim();
    $('#stc_sum_value').text(adj);
    $('#stc_sum_grand').text(grand);
    $('#stc_live_value').text(grand);
    $('#stc_live_products').text(rows);
  }

  /**
   * Prevent autocomplete from being clipped by panels.
   * stock_transfer.js already inits autocomplete — we only fix appendTo.
   */
  function fixAutocomplete() {
    var $input = $('#search_product_for_srock_adjustment');
    if (!$input.length || !$input.data('ui-autocomplete')) return;
    try {
      $input.autocomplete('option', 'appendTo', 'body');
    } catch (e) {}
  }

  function triggerSave() {
    var $btn = $('#save_stock_transfer');
    if ($btn.length) $btn.trigger('click');
  }

  ready(function () {
    if (!$('.stc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#stc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#stc_header_save, #stc_side_save').on('click', function (e) {
      e.preventDefault();
      triggerSave();
    });

    $('#stc_refresh_btn').on('click', function () {
      window.location.reload();
    });

    $(document).on(
      'change keyup input',
      '#ref_no, #transaction_date, #status, #location_id, #transfer_location_id, #shipping_charges, #stock_adjustment_product_table input',
      function () {
        syncMeta();
        syncSummary();
      }
    );

    // Observe product table mutations (rows appended via AJAX)
    var tableBody = document.querySelector('#stock_adjustment_product_table tbody');
    if (tableBody && window.MutationObserver) {
      var mo = new MutationObserver(function () {
        syncSummary();
      });
      mo.observe(tableBody, { childList: true, subtree: true });
    }

    // Periodic light sync for totals updated by stock_transfer.js
    setInterval(function () {
      syncMeta();
      syncSummary();
    }, 800);

    syncMeta();
    syncSummary();

    // Fix autocomplete after stock_transfer.js ready handlers
    setTimeout(fixAutocomplete, 300);
    setTimeout(fixAutocomplete, 1000);

    // Keyboard shortcuts (UI only — reuse existing fields/actions)
    $(document).on('keydown', function (e) {
      if (!e.key) return;
      var tag = (e.target && e.target.tagName) || '';
      var typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || $(e.target).is('[contenteditable]');

      if (e.key === 'Escape') {
        var cancel = document.getElementById('stc_cancel_link');
        if (cancel) window.location.href = cancel.href;
        return;
      }

      // Allow F-keys even while typing in search
      if (e.key === 'F2') {
        e.preventDefault();
        $('#location_id').select2('open');
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
        // Avoid browser refresh conflict only when not typing
        e.preventDefault();
        // Draft not implemented server-side — focus save (same as save)
        triggerSave();
      }
    });
  });
})();
