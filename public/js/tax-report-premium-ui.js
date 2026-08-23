/**
 * Tax Report — premium UI helpers only.
 * Reuses #tax_report_* filters, .tax_diff, DataTables, updateTaxReport().
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_tx_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('tx-dark-mode', enabled);
    document.body.classList.toggle('tx-dark-mode', enabled);
    var icon = document.querySelector('#tx_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function textOf(sel) {
    var $el = $(sel).first();
    if (!$el.length) return '—';
    if ($el.find('.fa-spin, .fa-sync').length) return '…';
    return ($el.text() || '').trim() || '—';
  }

  function syncMeta() {
    var loc = ($('#tax_report_location_id option:selected').text() || '').trim() || '—';
    var contact = ($('#tax_report_contact_id option:selected').text() || '').trim() || 'All';
    var range = ($('#tax_report_date_range').val() || '').trim() || '—';
    $('#tx_meta_location').text(loc);
    $('#tx_meta_contact').text(contact);
    $('#tx_meta_range').text(range);
  }

  function syncKpis() {
    // Mirror existing rendered values only
    $('#tx_kpi_diff').text(textOf('.tax_diff'));
    $('#tx_kpi_input_total').text(textOf('#sell_total')); // input tab footer (existing ID)
    $('#tx_kpi_output_total').text(textOf('#purchase_total')); // output tab footer (existing ID)
    $('#tx_kpi_expense_total').text(textOf('#expense_total'));
  }

  function refreshReport() {
    try {
      if (typeof updateTaxReport === 'function') updateTaxReport();
    } catch (e) {}

    try {
      if (typeof input_tax_table !== 'undefined' && input_tax_table) input_tax_table.ajax.reload();
    } catch (e) {}
    try {
      if (typeof output_tax_datatable !== 'undefined' && output_tax_datatable) output_tax_datatable.ajax.reload();
    } catch (e) {}
    try {
      if (typeof expense_tax_datatable !== 'undefined' && expense_tax_datatable) expense_tax_datatable.ajax.reload();
    } catch (e) {}
  }

  ready(function () {
    if (!$('.tx-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#tx_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#tx_print_page').on('click', function () {
      window.print();
    });

    $('#tx_refresh_report, #tx_generate_report').on('click', function () {
      refreshReport();
    });

    $(document).on('change', '#tax_report_location_id, #tax_report_contact_id, #tax_report_date_range', function () {
      setTimeout(syncMeta, 50);
    });

    var taxDiff = document.querySelector('.tax_diff');
    if (taxDiff && window.MutationObserver) {
      var mo = new MutationObserver(function () {
        syncKpis();
        syncMeta();
      });
      mo.observe(taxDiff, { childList: true, characterData: true, subtree: true });
    }

    $('#input_tax_table, #output_tax_table, #expense_tax_table').on('draw.dt', function () {
      setTimeout(syncKpis, 80);
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings && settings.url && String(settings.url).indexOf('/reports/tax-report') !== -1) {
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
