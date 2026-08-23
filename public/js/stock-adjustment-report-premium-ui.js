/**
 * Stock Adjustment Report — premium UI helpers only.
 * Reuses #stock_adjustment_location_filter, #stock_adjustment_date_filter,
 * #stock_adjustment_table, stock_adjustment.js, report.js,
 * and updateStockAdjustmentReport(). No calculation changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.sar-shell')) return;

  var STORAGE_KEY = 'vkpos_sar_dark_mode';
  var SETTINGS_KEY = 'vkpos_sar_settings';
  var TYPE_COL = 4;
  var searchTimer = null;
  var activeType = '';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('sar_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'sar-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof stock_adjustment_table !== 'undefined' && stock_adjustment_table) {
        return stock_adjustment_table;
      }
    } catch (e) {}
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sar-dark-mode', enabled);
    document.body.classList.toggle('sar-dark-mode', enabled);
    var shell = document.querySelector('.sar-shell');
    if (shell) shell.classList.toggle('sar-dark-mode', enabled);
    var icon = document.querySelector('#sar_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  function loadSettings() {
    try {
      return JSON.parse(localStorage.getItem(SETTINGS_KEY) || '{}') || {};
    } catch (e) {
      return {};
    }
  }

  function applySettings(s) {
    var shell = document.querySelector('.sar-shell');
    if (!shell) return;
    shell.classList.toggle('sar-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('sar-hide-exceptions', !!(s && s.hideExceptions));
    shell.classList.toggle('sar-compact', !!(s && s.compact));
    var k = document.getElementById('sar_set_hide_kpis');
    var e = document.getElementById('sar_set_hide_exceptions');
    var c = document.getElementById('sar_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (e) e.checked = !!(s && s.hideExceptions);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('sar_set_hide_kpis') || {}).checked,
      hideExceptions: !!(document.getElementById('sar_set_hide_exceptions') || {}).checked,
      compact: !!(document.getElementById('sar_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (err) {}
    applySettings(s);
  }

  function dateRangeText() {
    var $btn = $('#stock_adjustment_date_filter');
    var t = ($btn.find('span').text() || $btn.text() || '').replace(/\s+/g, ' ').trim();
    return t || '—';
  }

  function syncMeta() {
    var loc = ($('#stock_adjustment_location_filter option:selected').text() || '').trim() || '—';
    var range = dateRangeText();
    $('#sar_meta_location').text(loc);
    $('#sar_meta_range').text(range);
    $('.sar-print-location').text(loc);
    $('.sar-print-range').text(range);
  }

  function textFromHtml(html) {
    if (html === null || html === undefined || html === '') return '—';
    var t = $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
    return t || '—';
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function typeLabels() {
    var shell = document.querySelector('.sar-shell');
    return {
      normal: (shell && shell.getAttribute('data-type-normal')) || 'Normal',
      abnormal: (shell && shell.getAttribute('data-type-abnormal')) || 'Abnormal',
    };
  }

  function isAbnormalType(text) {
    var labels = typeLabels();
    var t = (text || '').toString().toLowerCase();
    return t === String(labels.abnormal).toLowerCase() || t.indexOf('abnormal') !== -1;
  }

  function reloadReport() {
    if (typeof updateStockAdjustmentReport === 'function') {
      updateStockAdjustmentReport();
    } else {
      var dt = tableApi();
      if (dt) dt.ajax.reload(null, false);
    }
  }

  function clickDtButton(cls) {
    var $btn = $('#stock_adjustment_table_wrapper').find(cls).first();
    if ($btn.length) {
      $btn.trigger('click');
      return true;
    }
    return false;
  }

  function exportKind(kind) {
    var map = {
      excel: '.buttons-excel, .buttons-excelHtml5',
      csv: '.buttons-csv, .buttons-csvHtml5',
      pdf: '.buttons-pdf, .buttons-pdfHtml5',
      print: '.buttons-print',
      colvis: '.buttons-colvis',
    };
    if (!clickDtButton(map[kind])) {
      if (kind === 'print') {
        window.print();
        return;
      }
      toast('warning', 'This export is not available for your user.');
    }
  }

  function applyTypeFilter(type) {
    activeType = type || '';
    $('.sar-type-chips .sar-chip').removeClass('is-active');
    $('.sar-type-chips .sar-chip[data-type="' + activeType + '"]').addClass('is-active');
    var dt = tableApi();
    if (!dt) return;
    try {
      dt.column(TYPE_COL).search(activeType).draw();
    } catch (e) {}
  }

  function resetFilters() {
    var $loc = $('#stock_adjustment_location_filter');
    var first = $loc.find('option:first').val();
    $loc.val(first);
    if ($loc.hasClass('select2-hidden-accessible')) $loc.trigger('change.select2');

    try {
      var picker = $('#stock_adjustment_date_filter').data('daterangepicker');
      if (picker && typeof dateRangeSettings !== 'undefined') {
        picker.setStartDate(dateRangeSettings.startDate);
        picker.setEndDate(dateRangeSettings.endDate);
        $('#stock_adjustment_date_filter span').html(
          dateRangeSettings.startDate.format(moment_date_format) +
            ' ~ ' +
            dateRangeSettings.endDate.format(moment_date_format)
        );
      }
    } catch (e) {}

    $('#sar_quick_search').val('');
    $('#sar_search_clear').prop('hidden', true);
    var dt = tableApi();
    if (dt) {
      try {
        dt.search('');
        dt.column(TYPE_COL).search('');
      } catch (err) {}
    }
    activeType = '';
    $('.sar-type-chips .sar-chip').removeClass('is-active');
    $('#sar_type_all').addClass('is-active');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('sar_export_menu');
    var btn = document.getElementById('sar_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#sar_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#sar_drawer_backdrop').prop('hidden', true);
    $('#stock_adjustment_table tbody tr').removeClass('sar-row-active');
    $('#sar_drawer_full').empty().prop('hidden', true);
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#stock_adjustment_table tbody tr').removeClass('sar-row-active');
    $row.addClass('sar-row-active');

    setText('sar_drawer_title', textFromHtml(data.ref_no));
    setText('sar_drawer_ref', textFromHtml(data.ref_no));
    setText('sar_drawer_date', textFromHtml(data.transaction_date));
    setText('sar_drawer_location', textFromHtml(data.location_name));
    setText('sar_drawer_type', textFromHtml(data.adjustment_type));
    setText('sar_drawer_amount', textFromHtml(data.final_total));
    setText('sar_drawer_recovered', textFromHtml(data.total_amount_recovered));
    setText('sar_drawer_reason', textFromHtml(data.additional_notes));
    setText('sar_drawer_user', textFromHtml(data.added_by));

    var typeText = textFromHtml(data.adjustment_type);
    var abnormal = isAbnormalType(typeText);
    $('#sar_drawer_status').html(
      '<span class="sar-badge ' +
        (abnormal ? 'sar-badge-abnormal' : 'sar-badge-normal') +
        '">' +
        $('<div>').text(typeText).html() +
        '</span>'
    );

    var href = $row.attr('data-href') || $row.find('.btn-modal').data('href');
    var $view = $('#sar_drawer_view');
    if (href) {
      $view.data('href', href).prop('hidden', false);
      $('#sar_drawer_full').html('<p class="sar-kpi-hint">Loading details…</p>').prop('hidden', false);
      $.ajax({
        url: href,
        dataType: 'html',
        success: function (html) {
          var $html = $('<div>').html(html);
          var $body = $html.find('.modal-body');
          $('#sar_drawer_full').html($body.length ? $body.html() : html).prop('hidden', false);
          if (typeof __currency_convert_recursively === 'function') {
            __currency_convert_recursively($('#sar_drawer_full'));
          }
        },
        error: function () {
          $('#sar_drawer_full').html('<p>Unable to load full details.</p>');
        },
      });
    } else {
      $view.prop('hidden', true);
      $('#sar_drawer_full').empty().prop('hidden', true);
    }

    $('#sar_drawer_backdrop').prop('hidden', false);
    $('#sar_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var labels = typeLabels();
    var abnormal = 0;

    $('#stock_adjustment_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.sar-badge').remove();
      $tr.removeClass('sar-row-abnormal sar-row-normal');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      var typeText = data ? textFromHtml(data.adjustment_type) : ($tr.children('td').eq(TYPE_COL).text() || '').trim();
      var $typeTd = $tr.children('td').eq(TYPE_COL);
      if (isAbnormalType(typeText)) {
        abnormal += 1;
        $tr.addClass('sar-row-abnormal');
        $typeTd.html('<span class="sar-badge sar-badge-abnormal"></span>');
        $typeTd.find('.sar-badge').text(typeText);
      } else if (typeText && typeText !== '—') {
        $tr.addClass('sar-row-normal');
        $typeTd.html('<span class="sar-badge sar-badge-normal"></span>');
        $typeTd.find('.sar-badge').text(typeText);
      }
    });

    var $strip = $('#sar_exception_strip').empty();
    if (!abnormal) {
      $strip.prop('hidden', true);
    } else {
      $strip.prop('hidden', false);
      $strip.append(
        $('<button type="button" class="sar-chip amber" data-kind="abnormal"/>').text(
          labels.abnormal + ' (this page) · ' + abnormal
        )
      );
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var lines = info ? info.recordsDisplay : 0;
    setText('sar_kpi_lines', String(lines));
    decorateRows();
    syncMeta();

    var empty = info && info.recordsDisplay === 0;
    $('#sar_empty').prop('hidden', !empty);
    $('.sar-table-card').toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#sar_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#sar_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('sar-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#sar_settings_toggle').on('click', function () {
      var panel = document.getElementById('sar_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#sar_set_hide_kpis, #sar_set_hide_exceptions, #sar_set_compact').on('change', saveSettings);

    $('#sar_fullscreen').on('click', function () {
      var el = document.getElementById('sar_shell');
      if (!el) return;
      if (!document.fullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
      } else if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    });
    document.addEventListener('fullscreenchange', function () {
      var icon = document.querySelector('#sar_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#sar_refresh, #sar_apply_filters, #sar_retry').on('click', function () {
      showError(false);
      reloadReport();
    });
    $('#sar_reset_filters, #sar_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#sar_focus_search').on('click', function () {
      var input = document.getElementById('sar_quick_search');
      if (input) input.focus();
    });

    $('#sar_print, #sar_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#sar_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('sar_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', closeExport);
    $('#sar_export_menu').on('click', function (e) {
      e.stopPropagation();
    });

    $('#sar_export_excel, #sar_export_excel_alt, #sar_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#sar_export_csv, #sar_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#sar_export_pdf, #sar_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#sar_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#sar_quick_search').on('input', function () {
      var q = this.value;
      $('#sar_search_clear').prop('hidden', q.length === 0);
      $('#sar_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#sar_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#sar_search_clear').on('click', function () {
      $('#sar_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#sar_filters_toggle').on('click', function () {
      var $body = $('#sar_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(
        open
          ? '<i class="bi bi-chevron-down"></i> Filters'
          : '<i class="bi bi-chevron-up"></i> Hide'
      );
    });

    $('.sar-type-chips .sar-chip').on('click', function () {
      applyTypeFilter($(this).data('type') || '');
    });

    $('#stock_adjustment_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#sar_table_loading').prop('hidden', !processing);
        $('#sar_search_spinner').prop('hidden', !processing);
      })
      .on('draw.dt', function () {
        showError(false);
        setTimeout(updateFromTable, 40);
      })
      .on('error.dt', function () {
        showError(true);
      });

    $(document).on('change', '#stock_adjustment_location_filter', syncMeta);
    $('#stock_adjustment_date_filter').on('apply.daterangepicker cancel.daterangepicker', function () {
      setTimeout(syncMeta, 30);
    });

    $(document).ajaxSend(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('stock-adjustment-report') !== -1) {
        $('.sar-kpi.tone-green, .sar-kpi.tone-orange, .sar-kpi.tone-violet, .sar-kpi.tone-teal').addClass('is-loading');
      }
    });
    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('stock-adjustment-report') !== -1) {
        $('.sar-kpi').removeClass('is-loading');
        if (xhr.status >= 400) showError(true);
        else showError(false);
      }
      if (url.indexOf('stock-adjustments') !== -1 && xhr.status >= 400) {
        showError(true);
      }
    });

    $(document).on('click', '#stock_adjustment_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      openDrawer($(this));
    });

    $(document).on('click', '#sar_exception_strip .sar-chip', function () {
      applyTypeFilter('abnormal');
    });

    $('#sar_drawer_view').on('click', function () {
      var href = $(this).data('href');
      if (!href) return;
      var $btn = $('#stock_adjustment_table tbody tr.sar-row-active .btn-modal').first();
      if ($btn.length) {
        closeDrawer();
        $btn.trigger('click');
      }
    });

    $('#sar_drawer_close, #sar_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(function () {
      updateFromTable();
      syncMeta();
    }, 800);
  });
})();
