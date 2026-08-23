/**
 * Variation Templates — premium UI helpers only.
 * Reuses existing DataTable (#variation_table), modal (.variation_modal),
 * and CRUD handlers from app.js without modifying them.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_vt_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('vt-dark-mode', enabled);
    document.body.classList.toggle('vt-dark-mode', enabled);
    var icon = document.querySelector('#vt_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#variation_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#variation_table')) return null;
    return $('#variation_table').DataTable();
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
      $('#vt_kpi_total').text(info.recordsTotal);
      $('#vt_kpi_filtered').text(info.recordsDisplay);

      var valueCount = 0;
      table.rows({ page: 'current' }).every(function () {
        var data = this.data();
        var valuesCell = data && data[1] != null ? String(data[1]) : '';
        // Strip HTML if present
        var tmp = document.createElement('div');
        tmp.innerHTML = valuesCell;
        var text = (tmp.textContent || tmp.innerText || '').trim();
        if (!text) return;
        valueCount += text.split(',').filter(function (p) {
          return p.trim() !== '';
        }).length;
      });
      $('#vt_kpi_values').text(valueCount);
    } catch (e) {}
  }

  function enhanceValuesColumn() {
    var table = getTable();
    if (!table) return;
    table.rows({ page: 'current' }).every(function () {
      var $row = $(this.node());
      var $cell = $row.find('td').eq(1);
      if (!$cell.length || $cell.data('vt-enhanced')) return;
      var text = ($cell.text() || '').trim();
      if (!text) {
        $cell.data('vt-enhanced', 1);
        return;
      }
      var parts = text.split(',').map(function (p) {
        return p.trim();
      }).filter(Boolean);
      if (!parts.length) {
        $cell.data('vt-enhanced', 1);
        return;
      }
      var html = parts
        .map(function (p) {
          return '<span class="vt-value-chip">' + escapeHtml(p) + '</span>';
        })
        .join('');
      $cell.html(html).data('vt-enhanced', 1);
    });
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#variation_table tbody tr').removeClass('vt-row-active');
    $row.addClass('vt-row-active');

    var name = ($row.find('td').eq(0).text() || '').trim();
    var valuesText = '';
    $row.find('td').eq(1).find('.vt-value-chip').each(function () {
      valuesText += (valuesText ? ', ' : '') + $(this).text();
    });
    if (!valuesText) valuesText = ($row.find('td').eq(1).text() || '').trim();

    var parts = valuesText.split(',').map(function (p) {
      return p.trim();
    }).filter(Boolean);

    $('#vt_preview_empty').hide();
    $('#vt_preview_content').show();
    $('#vt_preview_name').text(name || '—');
    $('#vt_preview_count').text(parts.length);
    $('#vt_kpi_selected').text('1');

    var chips = parts
      .map(function (p) {
        return '<li><i class="fas fa-check"></i> ' + escapeHtml(p) + '</li>';
      })
      .join('');
    $('#vt_preview_chips').html(chips || '<li>No values</li>');

    // Clone action buttons into preview (keeps existing classes/handlers)
    var $actions = $row.find('td').eq(2).clone(true, true);
    $('#vt_preview_actions').empty().append($actions.contents());
  }

  function appendVariationValue(value) {
    var html =
      '<div class="form-group"><div class="col-sm-9 col-sm-offset-3"><input type="text" name="variation_values[]" class="form-control" required value="' +
      escapeHtml(value) +
      '"></div><div class="col-sm-2"><button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm delete_variation_value">-</button></div></div>';
    $('#variation_values').append(html);
  }

  ready(function () {
    if (!$('.vt-shell').length) return;

    // Dark mode
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#vt_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#vt_print_page').on('click', function () {
      window.print();
    });

    $('#vt_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    // Quick search → DataTables global search (existing server-side search)
    var searchTimer = null;
    $('#vt_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    // Column filters reuse DataTables column search
    $('#vt_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      table.column(0).search($('#vt_filter_name').val() || '');
      table.column(1).search($('#vt_filter_values').val() || '');
      table.draw();
    });

    $('#vt_reset_filters').on('click', function () {
      $('#vt_filter_name').val('');
      $('#vt_filter_values').val('');
      $('#vt_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.column(0).search('');
      table.column(1).search('');
      table.draw();
    });

    // DataTable draw hooks
    $('#variation_table').on('draw.dt', function () {
      enhanceValuesColumn();
      updateKpis();
    });

    // In case DT already drew before this script
    setTimeout(function () {
      enhanceValuesColumn();
      updateKpis();
    }, 600);

    // Row preview
    $(document).on('click', '#variation_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreview($(this));
    });

    // Open paste modal from create/edit (delegated)
    $(document).on('click', '#vt_open_paste_modal', function (e) {
      e.preventDefault();
      $('#vt_paste_textarea').val('');
      $('#vt_paste_modal').modal('show');
    });

    $('#vt_paste_apply').on('click', function () {
      var lines = String($('#vt_paste_textarea').val() || '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
        .split('\n')
        .map(function (l) {
          return l.trim();
        })
        .filter(Boolean);

      if (!lines.length) {
        $('#vt_paste_modal').modal('hide');
        return;
      }

      // Fill first empty variation_values[] if present
      var $firstEmpty = $('.variation_modal input[name="variation_values[]"]').filter(function () {
        return !$(this).val();
      }).first();

      var start = 0;
      if ($firstEmpty.length) {
        $firstEmpty.val(lines[0]);
        start = 1;
      }

      for (var i = start; i < lines.length; i++) {
        appendVariationValue(lines[i]);
      }

      $('#vt_paste_modal').modal('hide');
      if (typeof toastr !== 'undefined') {
        toastr.success(lines.length + ' value(s) added');
      }
    });
  });
})();
