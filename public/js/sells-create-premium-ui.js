/**
 * Sales Create — premium UI helpers only.
 * Does not alter pos.js calculations, product search source, or submit validation.
 *
 * Also repairs autocomplete visibility after the premium shell:
 * overflow / sticky / #scrollable-container can clip jQuery UI menus.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sc_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sc-dark-mode', enabled);
    document.body.classList.toggle('sc-dark-mode', enabled);
    var icon = document.querySelector('#sc_dark_mode_toggle i');
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
    var inv = ($('#invoice_no').val() || '').trim();
    $('#sc_meta_invoice').text(inv || 'Auto');
    $('#sc_meta_location').text(textOrDash($('#select_location_id').length ? $('#select_location_id') : $('#location_id')));
    $('#sc_meta_customer').text(textOrDash($('#customer_id')));
    $('#sc_meta_date').text(textOrDash($('#transaction_date')));
    $('#sc_meta_status').text(textOrDash($('#status')));
  }

  function syncSummary() {
    $('#sc_sum_qty').text($('.total_quantity').first().text() || '0');
    $('#sc_sum_items').text($('#pos_table tbody tr.product_row').length || $('#pos_table tbody tr').length || '0');
    $('#sc_sum_subtotal').text($('.price_total').first().text() || '0');
    $('#sc_sum_discount').text($('#total_discount').text() || '0');
    $('#sc_sum_tax').text($('#order_tax').text() || '0');
    $('#sc_sum_grand').text($('#total_payable').text() || '0');
    $('#sc_sum_balance').text($('.balance_due').first().text() || '0');
    $('#sc_kpi_grand').text($('#total_payable').text() || '0');
    $('#sc_kpi_qty').text($('.total_quantity').first().text() || '0');
  }

  function syncAll() {
    syncSummary();
    syncHeaderMeta();
  }

  /**
   * Keep UltimatePOS autocomplete behavior; only fix menu attachment/clipping.
   * Does not change /products/list, select handlers, or barcode auto-add.
   */
  function repairProductSearchUi() {
    var $sp = $('#search_product');
    if (!$sp.length) return;

    // Match original enable rule from pos.js reset_pos_form
    if ($('input#location_id').val()) {
      $sp.prop('disabled', false);
    }

    function attachMenuToBody() {
      if (!$sp.data('ui-autocomplete')) return false;
      try {
        $sp.autocomplete('option', 'appendTo', 'body');
        // Ensure menu stacks above sticky header / summary
        var $menu = $sp.autocomplete('widget');
        if ($menu && $menu.length) {
          $menu.css('z-index', 10050);
        }
      } catch (e) {}
      return true;
    }

    if (!attachMenuToBody()) {
      // pos.js may init on document.ready after or before us — retry briefly
      var tries = 0;
      var timer = setInterval(function () {
        tries += 1;
        if (attachMenuToBody() || tries > 40) clearInterval(timer);
      }, 50);
    }
  }

  ready(function () {
    if (!$('.sc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sc_header_save, #sc_side_save').on('click', function (e) {
      e.preventDefault();
      var $btn = $('#submit-sell');
      if ($btn.length) $btn.trigger('click');
    });

    $('#sc_header_save_print, #sc_side_print').on('click', function (e) {
      e.preventDefault();
      var $btn = $('#save-and-print');
      if ($btn.length) $btn.trigger('click');
    });

    $('#sc_refresh_btn').on('click', function () {
      syncAll();
    });

    // After location change, pos.js enables search — re-attach menu if needed
    $(document).on('change', '#select_location_id', function () {
      setTimeout(repairProductSearchUi, 100);
    });

    repairProductSearchUi();

    setInterval(syncAll, 500);
    $(document).on(
      'change keyup blur select2:select select2:clear',
      '#add_sell_form input, #add_sell_form select, #customer_id, #select_location_id, #status, #invoice_no, #transaction_date',
      function () {
        setTimeout(syncAll, 50);
      }
    );
    syncAll();
  });
})();
