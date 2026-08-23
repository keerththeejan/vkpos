/**
 * Customer & Supplier Report — premium UI helpers only.
 * Reuses #supplier_report_tbl + supplier_report_tbl (report.js) + filter IDs.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_cs_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('cs-dark-mode', enabled);
    document.body.classList.toggle('cs-dark-mode', enabled);
    var icon = document.querySelector('#cs_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  function footerText(id) {
    var t = ($('#' + id).text() || '').trim();
    return t || '—';
  }

  // Columns: 0 name, 1 purchase, 2 purchase_return, 3 sell, 4 sell_return, 5 opening_due, 6 due
  var COL = {
    name: 0,
    purchase: 1,
    purchase_return: 2,
    sell: 3,
    sell_return: 4,
    opening: 5,
    due: 6,
  };

  function updateKpis() {
    try {
      if (typeof supplier_report_tbl !== 'undefined' && supplier_report_tbl) {
        var info = supplier_report_tbl.page.info();
        $('#cs_kpi_contacts').text(info.recordsTotal);
        $('#cs_kpi_filtered').text(info.recordsDisplay);
        $('#cs_sum_contacts').text(info.recordsDisplay);
      }
    } catch (e) {}

    $('#cs_kpi_purchase').text(footerText('footer_total_purchase'));
    $('#cs_kpi_sell').text(footerText('footer_total_sell'));
    $('#cs_kpi_due').text(footerText('footer_total_due'));
    $('#cs_kpi_opening').text(footerText('footer_total_opening_bal_due'));
    $('#cs_kpi_purchase_return').text(footerText('footer_total_purchase_return'));
    $('#cs_kpi_sell_return').text(footerText('footer_total_sell_return'));

    $('#cs_sum_purchase').text(footerText('footer_total_purchase'));
    $('#cs_sum_sell').text(footerText('footer_total_sell'));
    $('#cs_sum_due').text(footerText('footer_total_due'));
  }

  function syncMeta() {
    var loc = ($('#cs_report_location_id option:selected').text() || '').trim() || '—';
    var type = ($('#contact_type option:selected').text() || '').trim() || '—';
    var range = ($('#scr_date_filter').val() || '').trim() || '—';
    $('#cs_meta_location').text(loc);
    $('#cs_meta_type').text(type);
    $('#cs_meta_range').text(range);
  }

  function showPreview($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    if ($row.find('td.dataTables_empty').length) return;

    $('#supplier_report_tbl tbody tr').removeClass('cs-row-active');
    $row.addClass('cs-row-active');

    $('#cs_preview_empty').hide();
    $('#cs_preview_content').show();
    $('#cs_preview_name').text(cellText($row, COL.name));
    $('#cs_preview_purchase').text(cellText($row, COL.purchase));
    $('#cs_preview_purchase_return').text(cellText($row, COL.purchase_return));
    $('#cs_preview_sell').text(cellText($row, COL.sell));
    $('#cs_preview_sell_return').text(cellText($row, COL.sell_return));
    $('#cs_preview_opening').text(cellText($row, COL.opening));
    $('#cs_preview_due').text(cellText($row, COL.due));
  }

  function reloadTable() {
    try {
      if (typeof supplier_report_tbl !== 'undefined') supplier_report_tbl.ajax.reload(null, false);
    } catch (e) {}
  }

  ready(function () {
    if (!$('.cs-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#cs_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#cs_print_page').on('click', function () {
      window.print();
    });

    $('#cs_refresh_table, #cs_apply_filters, #cs_generate_report').on('click', function () {
      reloadTable();
      setTimeout(function () {
        updateKpis();
        syncMeta();
      }, 200);
    });

    $('#cs_reset_filters').on('click', function () {
      $('#cnt_customer_group_id, #contact_type, #cs_report_location_id, #scr_contact_id')
        .val('')
        .trigger('change');
      try {
        var $dr = $('#scr_date_filter');
        if ($dr.data('daterangepicker')) {
          $dr.data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
          $dr.data('daterangepicker').setEndDate(moment());
          $dr.val(
            $dr.data('daterangepicker').startDate.format(moment_date_format) +
              ' ~ ' +
              $dr.data('daterangepicker').endDate.format(moment_date_format)
          );
        }
      } catch (e) {}
      reloadTable();
    });

    var searchTimer = null;
    $('#cs_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof supplier_report_tbl !== 'undefined') supplier_report_tbl.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#supplier_report_tbl').on('draw.dt', function () {
      setTimeout(function () {
        updateKpis();
        syncMeta();
      }, 80);
    });

    $(document).on('change', '#cnt_customer_group_id, #contact_type, #cs_report_location_id, #scr_contact_id, #scr_date_filter', function () {
      setTimeout(syncMeta, 50);
    });

    setTimeout(updateKpis, 1000);
    syncMeta();

    $(document).on('click', '#supplier_report_tbl tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      showPreview($(this));
    });
  });
})();
