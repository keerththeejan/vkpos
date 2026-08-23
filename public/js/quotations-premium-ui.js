/**
 * Sales Quotations — premium UI helpers only.
 * Reuses existing #sell_table + sell_table DataTable from quotations.blade.php.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_qt_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('qt-dark-mode', enabled);
    document.body.classList.toggle('qt-dark-mode', enabled);
    var icon = document.querySelector('#qt_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from sale_pos/quotations.blade.php:
  // 0 date, 1 invoice_no, 2 customer, 3 mobile, 4 location, 5 items, 6 added_by, 7 action
  var COL = {
    date: 0,
    invoice: 1,
    customer: 2,
    mobile: 3,
    location: 4,
    items: 5,
    by: 6,
    action: 7,
  };

  function updateKpis() {
    try {
      if (typeof sell_table !== 'undefined' && sell_table) {
        var info = sell_table.page.info();
        $('#qt_kpi_total').text(info.recordsTotal);
        $('#qt_kpi_filtered').text(info.recordsDisplay);
        $('#qt_kpi_page').text(info.end - info.start);
        $('#qt_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var customers = {};
    var items = 0;
    $('#sell_table tbody tr').each(function () {
      var c = cellText($(this), COL.customer);
      if (c && c !== '—') customers[c] = true;
      var n = parseFloat(String(cellText($(this), COL.items)).replace(/[^0-9.\-]/g, ''));
      if (!isNaN(n)) items += n;
    });
    $('#qt_kpi_customers').text(Object.keys(customers).length || '0');
    $('#qt_kpi_items').text(items || '0');
    $('#qt_sum_customers').text(Object.keys(customers).length || '0');
    $('#qt_sum_items').text(items || '0');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#sell_table tbody tr').removeClass('qt-row-active');
    $row.addClass('qt-row-active');

    $('#qt_preview_empty').hide();
    $('#qt_preview_content').show();
    $('#qt_preview_invoice').text(cellText($row, COL.invoice));
    $('#qt_preview_date').text(cellText($row, COL.date));
    $('#qt_preview_customer').text(cellText($row, COL.customer));
    $('#qt_preview_mobile').text(cellText($row, COL.mobile));
    $('#qt_preview_location').text(cellText($row, COL.location));
    $('#qt_preview_items').text(cellText($row, COL.items));
    $('#qt_preview_by').text(cellText($row, COL.by));
    $('#qt_preview_status').text('Quotation');

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#qt_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.qt-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#qt_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#qt_print_page').on('click', function () {
      window.print();
    });

    $('#qt_refresh_table').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    $('#qt_apply_filters').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    $('#qt_reset_filters').on('click', function () {
      try {
        ['#sell_list_filter_location_id', '#sell_list_filter_customer_id', '#created_by'].forEach(function (sel) {
          if ($(sel).length) $(sel).val('').trigger('change');
        });
        if ($('#sell_list_filter_date_range').length) {
          $('#sell_list_filter_date_range').val('');
        }
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    var searchTimer = null;
    $('#qt_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof sell_table !== 'undefined') sell_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#sell_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#sell_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      showPreview($(this));
    });
  });
})();
