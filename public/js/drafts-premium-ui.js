/**
 * Draft Sales — premium UI helpers only.
 * Reuses existing #sell_table + sell_table DataTable from draft.blade.php.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_df_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('df-dark-mode', enabled);
    document.body.classList.toggle('df-dark-mode', enabled);
    var icon = document.querySelector('#df_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from sale_pos/draft.blade.php:
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
        $('#df_kpi_total').text(info.recordsTotal);
        $('#df_kpi_filtered').text(info.recordsDisplay);
        $('#df_kpi_page').text(info.end - info.start);
        $('#df_sum_filtered').text(info.recordsDisplay);
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
    $('#df_kpi_customers').text(Object.keys(customers).length || '0');
    $('#df_kpi_items').text(items || '0');
    $('#df_sum_customers').text(Object.keys(customers).length || '0');
    $('#df_sum_items').text(items || '0');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#sell_table tbody tr').removeClass('df-row-active');
    $row.addClass('df-row-active');

    $('#df_preview_empty').hide();
    $('#df_preview_content').show();
    $('#df_preview_invoice').text(cellText($row, COL.invoice));
    $('#df_preview_date').text(cellText($row, COL.date));
    $('#df_preview_customer').text(cellText($row, COL.customer));
    $('#df_preview_mobile').text(cellText($row, COL.mobile));
    $('#df_preview_location').text(cellText($row, COL.location));
    $('#df_preview_items').text(cellText($row, COL.items));
    $('#df_preview_by').text(cellText($row, COL.by));
    $('#df_preview_status').text('Draft');

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#df_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.df-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#df_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#df_print_page').on('click', function () {
      window.print();
    });

    $('#df_refresh_table').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    $('#df_apply_filters').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    $('#df_reset_filters').on('click', function () {
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
    $('#df_quick_search').on('input', function () {
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
