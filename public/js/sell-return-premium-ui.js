/**
 * Sales Return — premium UI helpers only.
 * Reuses existing #sell_return_table + sell_return_table DataTable.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sr_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sr-dark-mode', enabled);
    document.body.classList.toggle('sr-dark-mode', enabled);
    var icon = document.querySelector('#sr_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from sell_return/index.blade.php:
  // 0 date, 1 invoice_no, 2 parent_sale, 3 customer, 4 location,
  // 5 payment_status, 6 final_total, 7 payment_due, 8 action
  var COL = {
    date: 0,
    invoice: 1,
    parent: 2,
    customer: 3,
    location: 4,
    payment: 5,
    total: 6,
    due: 7,
    action: 8,
  };

  function updateKpis() {
    try {
      if (typeof sell_return_table !== 'undefined' && sell_return_table) {
        var info = sell_return_table.page.info();
        $('#sr_kpi_total').text(info.recordsTotal);
        $('#sr_kpi_filtered').text(info.recordsDisplay);
        $('#sr_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var total = ($('#footer_sell_return_total').text() || '').trim();
    var due = ($('#footer_total_due_sr').text() || '').trim();
    $('#sr_kpi_refund').text(total || '—');
    $('#sr_kpi_due').text(due || '—');
    $('#sr_sum_refund').text(total || '—');
    $('#sr_sum_due').text(due || '—');

    var customers = {};
    $('#sell_return_table tbody tr').each(function () {
      var c = cellText($(this), COL.customer);
      if (c && c !== '—') customers[c] = true;
    });
    $('#sr_kpi_customers').text(Object.keys(customers).length || '0');
    $('#sr_sum_customers').text(Object.keys(customers).length || '0');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#sell_return_table tbody tr').removeClass('sr-row-active');
    $row.addClass('sr-row-active');

    $('#sr_preview_empty').hide();
    $('#sr_preview_content').show();
    $('#sr_preview_invoice').text(cellText($row, COL.invoice));
    $('#sr_preview_parent').text(cellText($row, COL.parent));
    $('#sr_preview_date').text(cellText($row, COL.date));
    $('#sr_preview_customer').text(cellText($row, COL.customer));
    $('#sr_preview_location').text(cellText($row, COL.location));
    $('#sr_preview_payment').text(cellText($row, COL.payment));
    $('#sr_preview_total').text(cellText($row, COL.total));
    $('#sr_preview_due').text(cellText($row, COL.due));
    $('#sr_preview_status').text('Return');

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#sr_preview_actions').empty().append($actions.contents());
  }

  /** Same endpoint as POS header return lookup — no new backend. */
  function submitInvoiceReturn() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var invoice_no = ($('#sr_return_invoice_no').val() || '').trim();
    if (!invoice_no) {
      if (typeof toastr !== 'undefined') toastr.warning('Enter invoice number');
      return;
    }
    $.ajax({
      method: 'get',
      url: '/validate-invoice-to-return/' + encodeURI(invoice_no),
      dataType: 'json',
      success: function (result) {
        if (result.success == true) {
          window.location = result.redirect_url;
        } else if (typeof toastr !== 'undefined') {
          toastr.error(result.msg);
        }
      },
    });
  }

  ready(function () {
    if (!$('.sr-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sr_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sr_print_page').on('click', function () {
      window.print();
    });

    $('#sr_refresh_table').on('click', function () {
      try {
        if (typeof sell_return_table !== 'undefined') sell_return_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    $('#sr_apply_filters').on('click', function () {
      try {
        if (typeof sell_return_table !== 'undefined') sell_return_table.ajax.reload();
      } catch (e) {}
    });

    $('#sr_reset_filters').on('click', function () {
      try {
        ['#sell_list_filter_location_id', '#sell_list_filter_customer_id', '#created_by'].forEach(function (sel) {
          if ($(sel).length) $(sel).val('').trigger('change');
        });
        if ($('#sell_list_filter_date_range').length) {
          $('#sell_list_filter_date_range').val('');
        }
        if (typeof sell_return_table !== 'undefined') sell_return_table.ajax.reload();
      } catch (e) {}
    });

    var searchTimer = null;
    $('#sr_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof sell_return_table !== 'undefined') sell_return_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#sr_send_return').on('click', submitInvoiceReturn);
    $('#sr_return_invoice_no').on('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitInvoiceReturn();
      }
    });

    $('#sell_return_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#sell_return_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      showPreview($(this));
    });
  });
})();
