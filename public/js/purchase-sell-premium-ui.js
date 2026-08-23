/**
 * Purchase & Sales Report — premium UI helpers only.
 * Reuses #purchase_sell_date_filter, #purchase_sell_location_filter, updatePurchaseSell().
 * Mirrors existing .total_purchase / .total_sell / etc. class values into KPI cards.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_ps_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ps-dark-mode', enabled);
    document.body.classList.toggle('ps-dark-mode', enabled);
    var icon = document.querySelector('#ps_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function textOf(sel) {
    var $el = $(sel).first();
    if (!$el.length) return '—';
    if ($el.find('.fa-spin, .fa-sync').length) return '…';
    return ($el.text() || '').trim() || '—';
  }

  function syncMeta() {
    var loc = ($('#purchase_sell_location_filter option:selected').text() || '').trim() || '—';
    var range = ($('#purchase_sell_date_filter span').text() || '').trim() || '—';
    $('#ps_meta_location').text(loc);
    $('#ps_meta_range').text(range);
  }

  function syncKpis() {
    // Mirror existing report.js target classes — do not recalculate
    $('#ps_kpi_purchase').text(textOf('.total_purchase'));
    $('#ps_kpi_purchase_tax').text(textOf('.purchase_inc_tax'));
    $('#ps_kpi_purchase_return').text(textOf('.purchase_return_inc_tax'));
    $('#ps_kpi_purchase_due').text(textOf('.purchase_due'));
    $('#ps_kpi_sell').text(textOf('.total_sell'));
    $('#ps_kpi_sell_tax').text(textOf('.sell_inc_tax'));
    $('#ps_kpi_sell_return').text(textOf('.total_sell_return'));
    $('#ps_kpi_sell_due').text(textOf('.sell_due'));
    $('#ps_kpi_diff').text(textOf('.sell_minus_purchase'));
    $('#ps_kpi_diff_due').text(textOf('.difference_due'));
  }

  function refreshReport() {
    try {
      if (typeof updatePurchaseSell === 'function') updatePurchaseSell();
      else window.location.reload();
    } catch (e) {
      window.location.reload();
    }
  }

  ready(function () {
    if (!$('.ps-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#ps_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#ps_print_page').on('click', function () {
      window.print();
    });

    $('#ps_refresh_report, #ps_generate_report').on('click', function () {
      refreshReport();
    });

    $(document).on('change', '#purchase_sell_location_filter', function () {
      setTimeout(syncMeta, 50);
    });

    // Observe value spans updated by updatePurchaseSell
    var targets = document.querySelectorAll(
      '.total_purchase, .purchase_inc_tax, .purchase_return_inc_tax, .purchase_due, .total_sell, .sell_inc_tax, .total_sell_return, .sell_due, .sell_minus_purchase, .difference_due'
    );
    if (window.MutationObserver && targets.length) {
      var mo = new MutationObserver(function () {
        syncKpis();
        syncMeta();
      });
      targets.forEach(function (el) {
        mo.observe(el, { childList: true, characterData: true, subtree: true });
      });
    }

    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings && settings.url && String(settings.url).indexOf('/reports/purchase-sell') !== -1) {
        setTimeout(function () {
          syncKpis();
          syncMeta();
        }, 80);
      }
    });

    setInterval(function () {
      syncMeta();
      syncKpis();
    }, 1200);

    syncMeta();
    syncKpis();
  });
})();
