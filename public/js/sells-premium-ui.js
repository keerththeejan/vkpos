/**
 * Sales Management — premium UI helpers only.
 * Reuses existing #sell_table + sell_table DataTable.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sl_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sl-dark-mode', enabled);
    document.body.classList.toggle('sl-dark-mode', enabled);
    var icon = document.querySelector('#sl_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from sell/index.blade.php:
  // 0 action, 1 date, 2 invoice, 3 customer, 4 mobile, 5 location,
  // 6 payment_status, 7 payment_method, 8 total, 9 paid, 10 due,
  // 11 return_due, 12 shipping, 13 items, ...
  var COL = {
    action: 0,
    date: 1,
    invoice: 2,
    customer: 3,
    mobile: 4,
    location: 5,
    payment: 6,
    method: 7,
    total: 8,
    paid: 9,
    due: 10,
    returnDue: 11,
    shipping: 12,
    items: 13,
    by: 20,
  };

  function updateKpis() {
    try {
      if (typeof sell_table !== 'undefined' && sell_table) {
        var info = sell_table.page.info();
        $('#sl_kpi_total').text(info.recordsTotal);
        $('#sl_kpi_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var total = ($('#sell_table tfoot .footer_sale_total').text() || '').trim();
    var paid = ($('#sell_table tfoot .footer_total_paid').text() || '').trim();
    var due = ($('#sell_table tfoot .footer_total_remaining').text() || '').trim();
    var ret = ($('#sell_table tfoot .footer_total_sell_return_due').text() || '').trim();

    $('#sl_kpi_page_total').text(total || '—');
    $('#sl_kpi_paid').text(paid || '—');
    $('#sl_kpi_due').text(due || '—');
    $('#sl_fin_total').text(total || '—');
    $('#sl_fin_paid').text(paid || '—');
    $('#sl_fin_due').text(due || '—');
    $('#sl_fin_return').text(ret || '—');

    var customers = {};
    $('#sell_table tbody tr').each(function () {
      var c = cellText($(this), COL.customer);
      if (c && c !== '—') customers[c] = true;
    });
    $('#sl_kpi_customers').text(Object.keys(customers).length || '0');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#sell_table tbody tr').removeClass('sl-row-active');
    $row.addClass('sl-row-active');

    $('#sl_preview_empty').hide();
    $('#sl_preview_content').show();
    $('#sl_preview_invoice').text(cellText($row, COL.invoice));
    $('#sl_preview_payment').text(cellText($row, COL.payment));
    $('#sl_preview_date').text(cellText($row, COL.date));
    $('#sl_preview_customer').text(cellText($row, COL.customer));
    $('#sl_preview_mobile').text(cellText($row, COL.mobile));
    $('#sl_preview_location').text(cellText($row, COL.location));
    $('#sl_preview_method').text(cellText($row, COL.method));
    $('#sl_preview_total').text(cellText($row, COL.total));
    $('#sl_preview_paid').text(cellText($row, COL.paid));
    $('#sl_preview_due').text(cellText($row, COL.due));
    $('#sl_preview_shipping').text(cellText($row, COL.shipping));
    $('#sl_preview_items').text(cellText($row, COL.items));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#sl_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.sl-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sl_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sl_print_page').on('click', function () {
      window.print();
    });

    $('#sl_refresh_table').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    $('#sl_apply_filters').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    $('#sl_reset_filters').on('click', function () {
      try {
        var ids = [
          '#sell_list_filter_location_id',
          '#sell_list_filter_customer_id',
          '#sell_list_filter_payment_status',
          '#created_by',
          '#sales_cmsn_agnt',
          '#service_staffs',
          '#shipping_status',
          '#sell_list_filter_source',
          '#payment_method',
        ];
        ids.forEach(function (sel) {
          if ($(sel).length) $(sel).val('').trigger('change');
        });
        if ($('#sell_list_filter_date_range').length) {
          $('#sell_list_filter_date_range').val('');
        }
        if ($('#only_subscriptions').length) {
          $('#only_subscriptions').iCheck('uncheck');
        }
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    var searchTimer = null;
    $('#sl_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof sell_table !== 'undefined') sell_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#sl_toggle_adv').on('click', function () {
      $('#sl_adv_filters').slideToggle(180);
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
