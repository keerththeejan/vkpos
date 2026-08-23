/**
 * Warranties — premium UI helpers only.
 * Reuses existing #warranty_table DataTable and #warranty_form handlers.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_wm_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('wm-dark-mode', enabled);
    document.body.classList.toggle('wm-dark-mode', enabled);
    var icon = document.querySelector('#wm_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#warranty_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#warranty_table')) return null;
    return $('#warranty_table').DataTable();
  }

  function updateKpis() {
    var table = getTable();
    if (!table) return;
    try {
      var info = table.page.info();
      $('#wm_kpi_total').text(info.recordsTotal);
      $('#wm_kpi_filtered').text(info.recordsDisplay);
    } catch (e) {}
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#warranty_table tbody tr').removeClass('wm-row-active');
    $row.addClass('wm-row-active');

    var name = ($row.find('td').eq(0).text() || '').trim();
    var desc = ($row.find('td').eq(1).text() || '').trim();
    var duration = ($row.find('td').eq(2).text() || '').trim();

    $('#wm_preview_empty').hide();
    $('#wm_preview_content').show();
    $('#wm_preview_name').text(name || '—');
    $('#wm_preview_desc').text(desc || 'No description');
    $('#wm_preview_duration').html('<i class="fas fa-clock"></i> ' + (duration || '—'));
    $('#wm_kpi_selected').text('1');

    var $actions = $row.find('td').eq(3).clone(true, true);
    $('#wm_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.wm-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#wm_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#wm_print_page').on('click', function () {
      window.print();
    });

    $('#wm_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    var searchTimer = null;
    $('#wm_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#wm_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      table.column(0).search($('#wm_filter_name').val() || '');
      table.column(1).search($('#wm_filter_desc').val() || '');
      table.column(2).search($('#wm_filter_duration').val() || '');
      table.draw();
    });

    $('#wm_reset_filters').on('click', function () {
      $('#wm_filter_name').val('');
      $('#wm_filter_desc').val('');
      $('#wm_filter_duration').val('');
      $('#wm_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.column(0).search('');
      table.column(1).search('');
      table.column(2).search('');
      table.draw();
    });

    $('#warranty_table').on('draw.dt', function () {
      updateKpis();
    });

    setTimeout(updateKpis, 600);

    $(document).on('click', '#warranty_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreview($(this));
    });
  });
})();
