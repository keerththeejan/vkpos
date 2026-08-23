/**
 * Shipments — premium UI helpers only.
 * Reuses existing #sell_table + sell_table DataTable from sell/shipments.blade.php.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sh_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sh-dark-mode', enabled);
    document.body.classList.toggle('sh-dark-mode', enabled);
    var icon = document.querySelector('#sh_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Fixed columns (custom shipping fields may follow shipping_status):
  // 0 action, 1 date, 2 invoice, 3 customer, 4 mobile, 5 location,
  // 6 delivery_person, 7 shipping_status, ... payment_status near end
  var COL = {
    action: 0,
    date: 1,
    invoice: 2,
    customer: 3,
    mobile: 4,
    location: 5,
    courier: 6,
    status: 7,
  };

  function updateKpis() {
    try {
      if (typeof sell_table !== 'undefined' && sell_table) {
        var info = sell_table.page.info();
        $('#sh_kpi_total').text(info.recordsTotal);
        $('#sh_kpi_filtered').text(info.recordsDisplay);
        $('#sh_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var customers = {};
    var delivered = 0;
    var pending = 0;
    var transit = 0;
    $('#sell_table tbody tr').each(function () {
      var c = cellText($(this), COL.customer);
      if (c && c !== '—') customers[c] = true;
      var st = cellText($(this), COL.status).toLowerCase();
      if (st.indexOf('deliver') !== -1) delivered += 1;
      else if (st.indexOf('transit') !== -1 || st.indexOf('dispatch') !== -1 || st.indexOf('shipped') !== -1) transit += 1;
      else if (st && st !== '—') pending += 1;
    });
    $('#sh_kpi_customers').text(Object.keys(customers).length || '0');
    $('#sh_kpi_delivered').text(delivered);
    $('#sh_kpi_transit').text(transit);
    $('#sh_kpi_pending').text(pending);
    $('#sh_sum_customers').text(Object.keys(customers).length || '0');
    $('#sh_sum_delivered').text(delivered);
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#sell_table tbody tr').removeClass('sh-row-active');
    $row.addClass('sh-row-active');

    $('#sh_preview_empty').hide();
    $('#sh_preview_content').show();
    $('#sh_preview_invoice').text(cellText($row, COL.invoice));
    $('#sh_preview_date').text(cellText($row, COL.date));
    $('#sh_preview_customer').text(cellText($row, COL.customer));
    $('#sh_preview_mobile').text(cellText($row, COL.mobile));
    $('#sh_preview_location').text(cellText($row, COL.location));
    $('#sh_preview_courier').text(cellText($row, COL.courier));
    $('#sh_preview_status').text(cellText($row, COL.status));

    // Payment status is last or second-last column depending on waiter visibility
    var $tds = $row.find('td');
    var payIdx = $tds.length - 2;
    if (payIdx > COL.status) {
      $('#sh_preview_payment').text(($tds.eq(payIdx).text() || '').trim() || '—');
    } else {
      $('#sh_preview_payment').text('—');
    }

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#sh_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.sh-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sh_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sh_print_page').on('click', function () {
      window.print();
    });

    $('#sh_refresh_table').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    $('#sh_apply_filters').on('click', function () {
      try {
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    $('#sh_reset_filters').on('click', function () {
      try {
        [
          '#sell_list_filter_location_id',
          '#sell_list_filter_customer_id',
          '#sell_list_filter_payment_status',
          '#created_by',
          '#shipping_status',
          '#delivery_person',
          '#service_staffs',
        ].forEach(function (sel) {
          if ($(sel).length) $(sel).val('').trigger('change');
        });
        if ($('#sell_list_filter_date_range').length) {
          $('#sell_list_filter_date_range').val('');
        }
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload();
      } catch (e) {}
    });

    var searchTimer = null;
    $('#sh_quick_search').on('input', function () {
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
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .btn-modal').length) {
        return;
      }
      showPreview($(this));
    });
  });
})();
