/**
 * Expense Categories — premium UI helpers only.
 * Reuses #expense_category_table (app.js expense_cat_table) + .expense_category_modal.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_ec_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ec-dark-mode', enabled);
    document.body.classList.toggle('ec-dark-mode', enabled);
    var icon = document.querySelector('#ec_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    try {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#expense_category_table')) {
        return $('#expense_category_table').DataTable();
      }
    } catch (e) {}
    return null;
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns: 0 name, 1 code, 2 action
  var COL = { name: 0, code: 1, action: 2 };

  function updateKpis() {
    var table = getTable();
    try {
      if (table) {
        var info = table.page.info();
        $('#ec_kpi_total').text(info.recordsTotal);
        $('#ec_kpi_filtered').text(info.recordsDisplay);
        $('#ec_sum_total').text(info.recordsTotal);
        $('#ec_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var pageCount = 0;
    $('#expense_category_table tbody tr').each(function () {
      if ($(this).hasClass('child') || $(this).find('td.dataTables_empty').length) return;
      pageCount += 1;
    });
    $('#ec_kpi_page').text(pageCount);
    $('#ec_sum_page').text(pageCount);
  }

  function showPreview($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    if ($row.find('td.dataTables_empty').length) return;

    $('#expense_category_table tbody tr').removeClass('ec-row-active');
    $row.addClass('ec-row-active');

    $('#ec_preview_empty').hide();
    $('#ec_preview_content').show();
    $('#ec_preview_name').text(cellText($row, COL.name));
    $('#ec_preview_code').text(cellText($row, COL.code));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#ec_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.ec-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#ec_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#ec_print_page').on('click', function () {
      window.print();
    });

    $('#ec_refresh_table').on('click', function () {
      var table = getTable();
      try {
        if (table) table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#ec_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        try {
          if (table) table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#expense_category_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#expense_category_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      showPreview($(this));
    });
  });
})();
