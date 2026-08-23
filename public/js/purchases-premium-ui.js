/**
 * Purchases — premium UI helpers only.
 * Reuses existing #purchase_table DataTable (purchase_table from purchase.js).
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_pu_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('pu-dark-mode', enabled);
    document.body.classList.toggle('pu-dark-mode', enabled);
    var icon = document.querySelector('#pu_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from purchase_table.blade.php:
  // 0 action, 1 date, 2 ref, 3 location, 4 supplier, 5 purchase_status,
  // 6 payment_status, 7 grand_total, 8 payment_due, 9 added_by
  var COL = {
    action: 0,
    date: 1,
    ref: 2,
    location: 3,
    supplier: 4,
    status: 5,
    payment: 6,
    total: 7,
    due: 8,
    by: 9
  };

  function updateKpis() {
    try {
      if (typeof purchase_table !== 'undefined' && purchase_table) {
        var info = purchase_table.page.info();
        $('#pu_kpi_total').text(info.recordsTotal);
        $('#pu_kpi_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var total = ($('#purchase_table tfoot .footer_purchase_total').text() || '').trim();
    var due = ($('#purchase_table tfoot .footer_total_due').text() || '').trim();
    var ret = ($('#purchase_table tfoot .footer_total_purchase_return_due').text() || '').trim();

    $('#pu_kpi_page_total').text(total || '—');
    $('#pu_kpi_due').text(due || '—');
    $('#pu_fin_total').text(total || '—');
    $('#pu_fin_due').text(due || '—');
    $('#pu_fin_return').text(ret || '—');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#purchase_table tbody tr').removeClass('pu-row-active');
    $row.addClass('pu-row-active');

    $('#pu_preview_empty').hide();
    $('#pu_preview_content').show();
    $('#pu_preview_ref').text(cellText($row, COL.ref));
    $('#pu_preview_status').text(cellText($row, COL.status));
    $('#pu_preview_date').text(cellText($row, COL.date));
    $('#pu_preview_supplier').text(cellText($row, COL.supplier));
    $('#pu_preview_location').text(cellText($row, COL.location));
    $('#pu_preview_payment').text(cellText($row, COL.payment));
    $('#pu_preview_total').text(cellText($row, COL.total));
    $('#pu_preview_due').text(cellText($row, COL.due));
    $('#pu_preview_by').text(cellText($row, COL.by));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#pu_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.pu-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#pu_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#pu_print_page').on('click', function () {
      window.print();
    });

    $('#pu_refresh_table').on('click', function () {
      try {
        if (typeof purchase_table !== 'undefined') purchase_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#pu_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof purchase_table !== 'undefined') purchase_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#purchase_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#purchase_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .update_status').length) return;
      showPreview($(this));
    });
  });
})();
