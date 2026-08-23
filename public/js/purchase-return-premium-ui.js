/**
 * Purchase Return — premium UI helpers only.
 * Reuses existing #purchase_return_datatable + purchase_return_table.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_pr_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('pr-dark-mode', enabled);
    document.body.classList.toggle('pr-dark-mode', enabled);
    var icon = document.querySelector('#pr_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from purchase_return_list.blade.php / DataTable:
  // 0 date, 1 ref_no, 2 parent_purchase, 3 location, 4 supplier,
  // 5 payment_status, 6 final_total, 7 payment_due, 8 action
  var COL = {
    date: 0,
    ref: 1,
    parent: 2,
    location: 3,
    supplier: 4,
    payment: 5,
    total: 6,
    due: 7,
    action: 8,
  };

  function updateKpis() {
    try {
      if (typeof purchase_return_table !== 'undefined' && purchase_return_table) {
        var info = purchase_return_table.page.info();
        $('#pr_kpi_total').text(info.recordsTotal);
        $('#pr_kpi_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var total = ($('#footer_purchase_return_total').text() || '').trim();
    var due = ($('#footer_total_due').text() || '').trim();
    var statusHtml = ($('#footer_payment_status_count').text() || '').trim();

    $('#pr_kpi_page_total').text(total || '—');
    $('#pr_kpi_due').text(due || '—');
    $('#pr_fin_total').text(total || '—');
    $('#pr_fin_due').text(due || '—');
    $('#pr_fin_status').text(statusHtml || '—');

    // Unique suppliers visible on current page (UI estimate)
    var suppliers = {};
    $('#purchase_return_datatable tbody tr').each(function () {
      var s = cellText($(this), COL.supplier);
      if (s && s !== '—') suppliers[s] = true;
    });
    $('#pr_kpi_suppliers').text(Object.keys(suppliers).length || '0');
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#purchase_return_datatable tbody tr').removeClass('pr-row-active');
    $row.addClass('pr-row-active');

    $('#pr_preview_empty').hide();
    $('#pr_preview_content').show();
    $('#pr_preview_ref').text(cellText($row, COL.ref));
    $('#pr_preview_parent').text(cellText($row, COL.parent));
    $('#pr_preview_date').text(cellText($row, COL.date));
    $('#pr_preview_supplier').text(cellText($row, COL.supplier));
    $('#pr_preview_location').text(cellText($row, COL.location));
    $('#pr_preview_payment').text(cellText($row, COL.payment));
    $('#pr_preview_total').text(cellText($row, COL.total));
    $('#pr_preview_due').text(cellText($row, COL.due));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#pr_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.pr-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#pr_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#pr_print_page').on('click', function () {
      window.print();
    });

    $('#pr_refresh_table').on('click', function () {
      try {
        if (typeof purchase_return_table !== 'undefined') {
          purchase_return_table.ajax.reload(null, false);
        } else {
          window.location.reload();
        }
      } catch (e) {
        window.location.reload();
      }
    });

    $('#pr_apply_filters').on('click', function () {
      try {
        if (typeof purchase_return_table !== 'undefined') {
          purchase_return_table.ajax.reload();
        }
      } catch (e) {}
    });

    $('#pr_reset_filters').on('click', function () {
      try {
        if ($('#purchase_list_filter_location_id').length) {
          $('#purchase_list_filter_location_id').val('').trigger('change');
        }
        if ($('#purchase_list_filter_date_range').length) {
          $('#purchase_list_filter_date_range').val('');
          var drp = $('#purchase_list_filter_date_range').data('daterangepicker');
          if (drp) {
            // leave dates cleared; existing cancel handler also reloads
          }
        }
        if (typeof purchase_return_table !== 'undefined') {
          purchase_return_table.ajax.reload();
        }
      } catch (e) {}
    });

    var searchTimer = null;
    $('#pr_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof purchase_return_table !== 'undefined') {
            purchase_return_table.search(q).draw();
          }
        } catch (e) {}
      }, 250);
    });

    $('#pr_toggle_adv').on('click', function () {
      $('#pr_adv_filters').slideToggle(180);
    });

    $('#purchase_return_datatable').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#purchase_return_datatable tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      showPreview($(this));
    });
  });
})();
