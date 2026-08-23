/**
 * Units Management — premium UI helpers only.
 * Reuses existing #unit_table DataTable and CRUD handlers from app.js.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_um_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('um-dark-mode', enabled);
    document.body.classList.toggle('um-dark-mode', enabled);
    var icon = document.querySelector('#um_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#unit_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#unit_table')) return null;
    return $('#unit_table').DataTable();
  }

  function updateKpis() {
    var table = getTable();
    if (!table) return;
    try {
      var info = table.page.info();
      $('#um_kpi_total').text(info.recordsTotal);
      $('#um_kpi_filtered').text(info.recordsDisplay);

      var multi = 0;
      table.rows({ page: 'current' }).every(function () {
        var data = this.data();
        var name = data && data.actual_name != null ? String(data.actual_name) : '';
        // Server formats multi-units as: Name (12pc)
        if (/\([^)]+\)\s*$/.test($('<div>').html(name).text())) {
          multi++;
        }
      });
      $('#um_kpi_multi').text(multi);
    } catch (e) {}
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#unit_table tbody tr').removeClass('um-row-active');
    $row.addClass('um-row-active');

    var name = ($row.find('td').eq(0).text() || '').trim();
    var shortName = ($row.find('td').eq(1).text() || '').trim();
    var decimal = ($row.find('td').eq(2).text() || '').trim();

    var conversion = 'Base unit';
    var match = name.match(/\(([^)]+)\)\s*$/);
    if (match) {
      conversion = '1 unit = ' + match[1];
    }

    $('#um_preview_empty').hide();
    $('#um_preview_content').show();
    $('#um_preview_name').text(name.replace(/\s*\([^)]+\)\s*$/, '') || name || '—');
    $('#um_preview_short').text(shortName || '—');
    $('#um_preview_decimal').text(decimal || '—');
    $('#um_preview_conversion').text(conversion);
    $('#um_kpi_selected').text('1');

    var $actions = $row.find('td').eq(3).clone(true, true);
    $('#um_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.um-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#um_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#um_print_page').on('click', function () {
      window.print();
    });

    $('#um_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    var searchTimer = null;
    $('#um_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#um_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      table.column(0).search($('#um_filter_name').val() || '');
      table.column(1).search($('#um_filter_short').val() || '');
      table.draw();
    });

    $('#um_reset_filters').on('click', function () {
      $('#um_filter_name').val('');
      $('#um_filter_short').val('');
      $('#um_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.column(0).search('');
      table.column(1).search('');
      table.draw();
    });

    $('#unit_table').on('draw.dt', function () {
      updateKpis();
    });

    setTimeout(updateKpis, 600);

    $(document).on('click', '#unit_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreview($(this));
    });
  });
})();
