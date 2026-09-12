/**
 * Invoice Schemes — UI helpers only.
 * Reuses existing #invoice_table DataTable and CRUD handlers from app.js.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function getTable() {
    if (!$('#invoice_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#invoice_table')) return null;
    return $('#invoice_table').DataTable();
  }

  ready(function () {
    if (!$('.is-shell').length) return;

    var searchTimer = null;
    $('#is_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#is_filter_default, #is_filter_number_type').on('change', function () {
      var table = getTable();
      if (table) table.ajax.reload();
    });

    $('#is_reset_filters').on('click', function () {
      $('#is_quick_search').val('');
      $('#is_filter_default').val('');
      $('#is_filter_number_type').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.ajax.reload();
    });

    $('#is_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });
  });
})();
