/**
 * Stock Transfers — premium UI helpers only.
 * Reuses existing #stock_transfer_table + stock_transfer_table (stock_transfer.js).
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_st_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('st-dark-mode', enabled);
    document.body.classList.toggle('st-dark-mode', enabled);
    var icon = document.querySelector('#st_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from stock_transfer.js:
  // 0 date, 1 ref, 2 from, 3 to, 4 status, 5 shipping, 6 total, 7 notes, 8 action
  var COL = {
    date: 0,
    ref: 1,
    from: 2,
    to: 3,
    status: 4,
    shipping: 5,
    total: 6,
    notes: 7,
    action: 8,
  };

  function updateKpis() {
    try {
      if (typeof stock_transfer_table !== 'undefined' && stock_transfer_table) {
        var info = stock_transfer_table.page.info();
        $('#st_kpi_total').text(info.recordsTotal);
        $('#st_kpi_filtered').text(info.recordsDisplay);
        $('#st_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var completed = 0;
    var pending = 0;
    var inTransit = 0;
    var pageTotal = 0;
    $('#stock_transfer_table tbody tr').each(function () {
      if ($(this).hasClass('child')) return;
      var st = cellText($(this), COL.status).toLowerCase();
      if (st.indexOf('complet') !== -1 || st.indexOf('received') !== -1) completed += 1;
      else if (st.indexOf('transit') !== -1 || st.indexOf('ship') !== -1) inTransit += 1;
      else if (st && st !== '—') pending += 1;

      var t = parseFloat(String(cellText($(this), COL.total)).replace(/[^0-9.\-]/g, ''));
      if (!isNaN(t)) pageTotal += t;
    });
    $('#st_kpi_completed').text(completed);
    $('#st_kpi_pending').text(pending);
    $('#st_kpi_transit').text(inTransit);
    $('#st_sum_completed').text(completed);
    $('#st_sum_pending').text(pending);
  }

  function showPreview($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    $('#stock_transfer_table tbody tr').removeClass('st-row-active');
    $row.addClass('st-row-active');

    $('#st_preview_empty').hide();
    $('#st_preview_content').show();
    $('#st_preview_ref').text(cellText($row, COL.ref));
    $('#st_preview_date').text(cellText($row, COL.date));
    $('#st_preview_from').text(cellText($row, COL.from));
    $('#st_preview_to').text(cellText($row, COL.to));
    $('#st_preview_status').text(cellText($row, COL.status));
    $('#st_preview_shipping').text(cellText($row, COL.shipping));
    $('#st_preview_total').text(cellText($row, COL.total));
    $('#st_preview_notes').text(cellText($row, COL.notes));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#st_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.st-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#st_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#st_print_page').on('click', function () {
      window.print();
    });

    $('#st_refresh_table').on('click', function () {
      try {
        if (typeof stock_transfer_table !== 'undefined') stock_transfer_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#st_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof stock_transfer_table !== 'undefined') stock_transfer_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#stock_transfer_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#stock_transfer_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .view_stock_transfer').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      showPreview($(this));
    });
  });
})();
