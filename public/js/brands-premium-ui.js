/**
 * Brands — premium UI helpers only.
 * Reuses existing #brands_table DataTable and CRUD handlers from app.js.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_br_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('br-dark-mode', enabled);
    document.body.classList.toggle('br-dark-mode', enabled);
    var icon = document.querySelector('#br_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#brands_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#brands_table')) return null;
    return $('#brands_table').DataTable();
  }

  function updateKpis() {
    var table = getTable();
    if (!table) return;
    try {
      var info = table.page.info();
      $('#br_kpi_total').text(info.recordsTotal);
      $('#br_kpi_filtered').text(info.recordsDisplay);
    } catch (e) {}
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#brands_table tbody tr').removeClass('br-row-active');
    $row.addClass('br-row-active');

    var name = ($row.find('td').eq(0).text() || '').trim();
    var desc = ($row.find('td').eq(1).text() || '').trim();

    $('#br_preview_empty').hide();
    $('#br_preview_content').show();
    $('#br_preview_name').text(name || '—');
    $('#br_preview_desc').text(desc || 'No description');
    $('#br_kpi_selected').text('1');

    var $actions = $row.find('td').eq(2).clone(true, true);
    $('#br_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.br-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#br_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#br_print_page').on('click', function () {
      window.print();
    });

    $('#br_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    var searchTimer = null;
    $('#br_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#br_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      table.column(0).search($('#br_filter_name').val() || '');
      table.column(1).search($('#br_filter_note').val() || '');
      table.draw();
    });

    $('#br_reset_filters').on('click', function () {
      $('#br_filter_name').val('');
      $('#br_filter_note').val('');
      $('#br_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.column(0).search('');
      table.column(1).search('');
      table.draw();
    });

    $('#brands_table').on('draw.dt', function () {
      updateKpis();
    });

    setTimeout(updateKpis, 600);

    $(document).on('click', '#brands_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreview($(this));
    });
  });
})();
