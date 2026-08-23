/**
 * Profit & Loss — premium UI helpers only.
 * Reuses #profit_loss_date_filter, #profit_loss_location_filter, #pl_data_div, updateProfitLoss().
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_pl_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('pl-dark-mode', enabled);
    document.body.classList.toggle('pl-dark-mode', enabled);
    var icon = document.querySelector('#pl_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function syncMeta() {
    var loc = ($('#profit_loss_location_filter option:selected').text() || '').trim() || '—';
    var range = ($('#profit_loss_date_filter span').text() || '').trim() || '—';
    $('#pl_meta_location').text(loc);
    $('#pl_meta_range').text(range);
  }

  /**
   * Soft-read key figures from AJAX-loaded #pl_data_div (read-only UI).
   * Does not recalculate — mirrors rendered labels.
   */
  function syncKpisFromPlData() {
    var $root = $('#pl_data_div');
    if (!$root.length) return;

    var found = { cogs: null, gross: null, net: null };

    $root.find('h3').each(function () {
      var txt = ($(this).text() || '').toLowerCase();
      var val = ($(this).find('.display_currency').first().text() || $(this).text() || '').replace(/^[^0-9\-\.]*/, '').trim();
      // Prefer currency span full text
      var $cur = $(this).find('.display_currency').first();
      if ($cur.length) val = ($cur.text() || '').trim();

      if (txt.indexOf('cogs') !== -1 || txt.indexOf('cost of') !== -1) found.cogs = val || found.cogs;
      if (txt.indexOf('gross') !== -1) found.gross = val || found.gross;
      if (txt.indexOf('net') !== -1) found.net = val || found.net;
    });

    // Fallback: table rows for expense / purchase / opening
    var expense = null;
    var purchase = null;
    var opening = null;
    $root.find('table tr').each(function () {
      var label = ($(this).find('th').first().text() || '').toLowerCase();
      var val = ($(this).find('.display_currency').first().text() || '').trim();
      if (!val) return;
      if (label.indexOf('expense') !== -1 && !expense) expense = val;
      if (label.indexOf('purchase') !== -1 && label.indexOf('shipping') === -1 && !purchase) purchase = val;
      if (label.indexOf('opening') !== -1 && !opening) opening = val;
    });

    if (found.gross) $('#pl_kpi_gross').text(found.gross);
    if (found.net) $('#pl_kpi_net').text(found.net);
    if (found.cogs) $('#pl_kpi_cogs').text(found.cogs);
    if (expense) $('#pl_kpi_expense').text(expense);
    if (purchase) $('#pl_kpi_purchase').text(purchase);
    if (opening) $('#pl_kpi_opening').text(opening);

    $('#pl_sum_gross').text(found.gross || '—');
    $('#pl_sum_net').text(found.net || '—');
    $('#pl_sum_cogs').text(found.cogs || '—');
  }

  function refreshReport() {
    try {
      if (typeof updateProfitLoss === 'function') updateProfitLoss();
      else window.location.reload();
    } catch (e) {
      window.location.reload();
    }
  }

  ready(function () {
    if (!$('.pl-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#pl_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#pl_print_page').on('click', function () {
      window.print();
    });

    $('#pl_refresh_report, #pl_generate_report').on('click', function () {
      refreshReport();
    });

    $(document).on('change', '#profit_loss_location_filter', function () {
      setTimeout(syncMeta, 50);
    });

    // Observe P&L HTML injection
    var plDiv = document.getElementById('pl_data_div');
    if (plDiv && window.MutationObserver) {
      var mo = new MutationObserver(function () {
        syncMeta();
        syncKpisFromPlData();
      });
      mo.observe(plDiv, { childList: true, subtree: true });
    }

    // Also after AJAX completes
    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings && settings.url && String(settings.url).indexOf('/reports/profit-loss') !== -1) {
        setTimeout(function () {
          syncMeta();
          syncKpisFromPlData();
        }, 100);
      }
    });

    setInterval(syncMeta, 1000);
    setTimeout(syncKpisFromPlData, 1200);
    syncMeta();
  });
})();
