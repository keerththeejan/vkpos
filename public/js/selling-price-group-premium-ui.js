/**
 * Selling Price Groups — premium UI helpers only.
 * Reuses existing #selling_price_group_table DataTable and CRUD handlers.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_spg_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('spg-dark-mode', enabled);
    document.body.classList.toggle('spg-dark-mode', enabled);
    var icon = document.querySelector('#spg_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#selling_price_group_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#selling_price_group_table')) return null;
    return $('#selling_price_group_table').DataTable();
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function updateKpis() {
    var table = getTable();
    if (!table) return;
    try {
      var info = table.page.info();
      $('#spg_kpi_total').text(info.recordsTotal);
      $('#spg_kpi_filtered').text(info.recordsDisplay);
    } catch (e) {}
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#selling_price_group_table tbody tr').removeClass('spg-row-active');
    $row.addClass('spg-row-active');

    var name = ($row.find('td').eq(0).text() || '').trim();
    var desc = ($row.find('td').eq(1).text() || '').trim();

    $('#spg_preview_empty').hide();
    $('#spg_preview_content').show();
    $('#spg_preview_name').text(name || '—');
    $('#spg_preview_desc').text(desc || 'No description');
    $('#spg_kpi_selected').text('1');

    var $actions = $row.find('td').eq(2).clone(true, true);
    $('#spg_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.spg-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#spg_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#spg_print_page').on('click', function () {
      window.print();
    });

    $('#spg_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    var searchTimer = null;
    $('#spg_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#spg_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      table.column(0).search($('#spg_filter_name').val() || '');
      table.column(1).search($('#spg_filter_desc').val() || '');
      table.draw();
    });

    $('#spg_reset_filters').on('click', function () {
      $('#spg_filter_name').val('');
      $('#spg_filter_desc').val('');
      $('#spg_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.column(0).search('');
      table.column(1).search('');
      table.draw();
    });

    $('#selling_price_group_table').on('draw.dt', function () {
      updateKpis();
    });

    setTimeout(updateKpis, 600);

    $(document).on('click', '#selling_price_group_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreview($(this));
    });
  });
})();
