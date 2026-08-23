/**
 * Stock Adjustments — premium UI helpers only.
 * Reuses existing #stock_adjustment_table + stock_adjustment_table (stock_adjustment.js).
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_sa_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sa-dark-mode', enabled);
    document.body.classList.toggle('sa-dark-mode', enabled);
    var icon = document.querySelector('#sa_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from stock_adjustment.js:
  // 0 action, 1 date, 2 ref, 3 location, 4 type, 5 total, 6 recovered, 7 notes, 8 added_by
  var COL = {
    action: 0,
    date: 1,
    ref: 2,
    location: 3,
    type: 4,
    total: 5,
    recovered: 6,
    notes: 7,
    added_by: 8,
  };

  function updateKpis() {
    try {
      if (typeof stock_adjustment_table !== 'undefined' && stock_adjustment_table) {
        var info = stock_adjustment_table.page.info();
        $('#sa_kpi_total').text(info.recordsTotal);
        $('#sa_kpi_filtered').text(info.recordsDisplay);
        $('#sa_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var normal = 0;
    var abnormal = 0;
    var pageValue = 0;
    $('#stock_adjustment_table tbody tr').each(function () {
      if ($(this).hasClass('child')) return;
      var type = cellText($(this), COL.type).toLowerCase();
      if (type.indexOf('normal') !== -1 || type.indexOf('increase') !== -1) normal += 1;
      else if (type.indexOf('abnormal') !== -1 || type.indexOf('decrease') !== -1) abnormal += 1;

      var t = parseFloat(String(cellText($(this), COL.total)).replace(/[^0-9.\-]/g, ''));
      if (!isNaN(t)) pageValue += t;
    });
    $('#sa_kpi_normal').text(normal);
    $('#sa_kpi_abnormal').text(abnormal);
    $('#sa_sum_normal').text(normal);
    $('#sa_sum_abnormal').text(abnormal);
    $('#sa_kpi_value').text(pageValue ? pageValue.toFixed(2) : '—');
    $('#sa_sum_value').text(pageValue ? pageValue.toFixed(2) : '—');
  }

  function showPreview($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    $('#stock_adjustment_table tbody tr').removeClass('sa-row-active');
    $row.addClass('sa-row-active');

    $('#sa_preview_empty').hide();
    $('#sa_preview_content').show();
    $('#sa_preview_ref').text(cellText($row, COL.ref));
    $('#sa_preview_date').text(cellText($row, COL.date));
    $('#sa_preview_location').text(cellText($row, COL.location));
    $('#sa_preview_type').text(cellText($row, COL.type));
    $('#sa_preview_total').text(cellText($row, COL.total));
    $('#sa_preview_recovered').text(cellText($row, COL.recovered));
    $('#sa_preview_notes').text(cellText($row, COL.notes));
    $('#sa_preview_by').text(cellText($row, COL.added_by));

    var typeLower = cellText($row, COL.type).toLowerCase();
    var $badge = $('#sa_preview_type_badge');
    $badge.removeClass('normal abnormal');
    if (typeLower.indexOf('abnormal') !== -1) $badge.addClass('abnormal');
    else if (typeLower.indexOf('normal') !== -1) $badge.addClass('normal');

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#sa_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.sa-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#sa_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#sa_print_page').on('click', function () {
      window.print();
    });

    $('#sa_refresh_table').on('click', function () {
      try {
        if (typeof stock_adjustment_table !== 'undefined') stock_adjustment_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#sa_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof stock_adjustment_table !== 'undefined') stock_adjustment_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#stock_adjustment_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#stock_adjustment_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      showPreview($(this));
    });
  });
})();
