/**
 * Expense Report — premium UI helpers only.
 * Reuses GET #expense_report_form, #location_id, #category_id,
 * #trending_product_date_range, #expense_report_table, report.js
 * DataTables + daterangepicker, and the existing Highcharts chart.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.exr-shell')) return;

  var STORAGE_KEY = 'vkpos_exr_dark_mode';
  var SETTINGS_KEY = 'vkpos_exr_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('exr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'exr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof expense_report_table !== 'undefined' && expense_report_table) {
        return expense_report_table;
      }
    } catch (e) {}
    var $t = $('#expense_report_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('exr-dark-mode', enabled);
    document.body.classList.toggle('exr-dark-mode', enabled);
    var shell = document.querySelector('.exr-shell');
    if (shell) shell.classList.toggle('exr-dark-mode', enabled);
    var icon = document.querySelector('#exr_dark_mode_toggle i');
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
    var shell = document.querySelector('.exr-shell');
    if (!shell) return;
    shell.classList.toggle('exr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('exr-hide-chart', !!(s && s.hideChart));
    shell.classList.toggle('exr-compact', !!(s && s.compact));
    var k = document.getElementById('exr_set_hide_kpis');
    var c = document.getElementById('exr_set_hide_chart');
    var p = document.getElementById('exr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.hideChart);
    if (p) p.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('exr_set_hide_kpis') || {}).checked,
      hideChart: !!(document.getElementById('exr_set_hide_chart') || {}).checked,
      compact: !!(document.getElementById('exr_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (e) {}
    applySettings(s);
  }

  function selectedText(sel) {
    var t = ($(sel).find('option:selected').text() || '').trim();
    return t || '—';
  }

  function dateVal() {
    var v = ($('#trending_product_date_range').val() || '').trim();
    return v || '—';
  }

  function setPeriodChip(code) {
    $('#exr_period_chips .exr-chip').removeClass('is-active');
    $('#exr_period_chips .exr-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#trending_product_date_range').val() || '').trim();
    if (!val) {
      setPeriodChip('');
      return;
    }
    if (typeof ranges === 'undefined' || typeof LANG === 'undefined' || typeof moment_date_format === 'undefined') {
      setPeriodChip('custom');
      return;
    }
    var keyMap = {
      today: LANG.today,
      yesterday: LANG.yesterday,
      last_7_days: LANG.last_7_days,
      last_30_days: LANG.last_30_days,
      this_month: LANG.this_month,
      last_month: LANG.last_month,
      this_year: LANG.this_year,
    };
    var matched = '';
    Object.keys(keyMap).forEach(function (code) {
      var span = ranges[keyMap[code]];
      if (!span || !span[0] || !span[1]) return;
      var text = span[0].format(moment_date_format) + ' ~ ' + span[1].format(moment_date_format);
      if (text === val) matched = code;
    });
    setPeriodChip(matched || 'custom');
  }

  function syncMeta() {
    var loc = selectedText('#expense_report_form #location_id');
    var cat = selectedText('#expense_report_form #category_id');
    var range = dateVal();
    $('#exr_meta_location').text(loc);
    $('#exr_meta_category').text(cat);
    $('#exr_meta_range').text(range);
    $('.exr-print-location').text(loc);
    $('.exr-print-category').text(cat);
    $('.exr-print-range').text(range);
    syncPeriodChip();
  }

  function submitForm() {
    var form = document.getElementById('expense_report_form');
    if (form) form.submit();
  }

  function resetFilters() {
    $('#expense_report_form select').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#trending_product_date_range').val('');
    submitForm();
  }

  function applyPeriod(code) {
    var $input = $('#trending_product_date_range');
    if (code === 'custom') {
      setPeriodChip('custom');
      $input.trigger('click').focus();
      return;
    }
    if (typeof ranges === 'undefined' || typeof LANG === 'undefined' || typeof moment_date_format === 'undefined') {
      toast('warning', 'Date ranges are not available.');
      return;
    }
    var keyMap = {
      today: LANG.today,
      yesterday: LANG.yesterday,
      last_7_days: LANG.last_7_days,
      last_30_days: LANG.last_30_days,
      this_month: LANG.this_month,
      last_month: LANG.last_month,
      this_year: LANG.this_year,
    };
    var span = ranges[keyMap[code]];
    if (!span || !span[0] || !span[1]) {
      toast('warning', 'That period is not available.');
      return;
    }
    var text = span[0].format(moment_date_format) + ' ~ ' + span[1].format(moment_date_format);
    var picker = $input.data('daterangepicker');
    if (picker) {
      picker.setStartDate(span[0]);
      picker.setEndDate(span[1]);
    }
    $input.val(text);
    setPeriodChip(code);
    submitForm();
  }

  function clickDtButton(cls) {
    var $btn = $('#expense_report_table_wrapper').find(cls).first();
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

  function closeExport() {
    var menu = document.getElementById('exr_export_menu');
    var btn = document.getElementById('exr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#exr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#exr_drawer_backdrop').prop('hidden', true);
    $('#expense_report_table tbody tr').removeClass('exr-row-active');
  }

  function openDrawer($row) {
    if (!$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    $('#expense_report_table tbody tr').removeClass('exr-row-active');
    $row.addClass('exr-row-active');
    var cat = $row.attr('data-category') || $row.children('td').eq(0).text().replace(/\s+/g, ' ').trim();
    var totalHtml = $row.children('td').eq(1).html() || '—';
    $('#exr_drawer_title').text(cat || 'Category');
    $('#exr_drawer_category').text(cat || '—');
    $('#exr_drawer_total').html(totalHtml);
    $('#exr_drawer_backdrop').prop('hidden', false);
    $('#exr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function runSearch(q) {
    q = q || '';
    $('#exr_search_clear').prop('hidden', q.length === 0);
    var dt = tableApi();
    if (dt) {
      dt.search(q).draw();
      return;
    }
    q = q.toLowerCase().trim();
    $('#expense_report_table tbody tr').each(function () {
      var hay = ($(this).text() || '').toLowerCase();
      $(this).toggle(!q || hay.indexOf(q) !== -1);
    });
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#exr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('exr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#exr_settings_toggle').on('click', function () {
      var panel = document.getElementById('exr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#exr_set_hide_kpis, #exr_set_hide_chart, #exr_set_compact').on('change', saveSettings);

    $('#exr_fullscreen').on('click', function () {
      var el = document.getElementById('exr_shell');
      if (!el) return;
      if (!document.fullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
        $(this).find('i').attr('class', 'bi bi-fullscreen-exit');
      } else if (document.exitFullscreen) {
        document.exitFullscreen();
        $(this).find('i').attr('class', 'bi bi-fullscreen');
      }
    });
    document.addEventListener('fullscreenchange', function () {
      var icon = document.querySelector('#exr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#exr_refresh').on('click', function () {
      submitForm();
    });
    $('#exr_reset_filters, #exr_empty_clear').on('click', resetFilters);
    $('#exr_focus_search').on('click', function () {
      var input = document.getElementById('exr_quick_search');
      if (input) input.focus();
    });

    $('#exr_print, #exr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#exr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('exr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#exr_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#exr_export_excel, #exr_export_excel_alt, #exr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#exr_export_csv, #exr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#exr_export_pdf, #exr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#exr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#exr_quick_search').on('input', function () {
      var q = this.value;
      $('#exr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        runSearch(q);
        $('#exr_search_spinner').prop('hidden', true);
      }, 200);
    });
    $('#exr_search_clear').on('click', function () {
      $('#exr_quick_search').val('');
      runSearch('');
    });

    $('#exr_filters_toggle').on('click', function () {
      var $body = $('#exr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#exr_period_chips').on('click', '.exr-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $(document).on('change', '#expense_report_form select, #trending_product_date_range', syncMeta);
    $('#trending_product_date_range').on('apply.daterangepicker cancel.daterangepicker', function () {
      setTimeout(syncMeta, 30);
    });

    $(document).on('click', '#expense_report_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, input, select').length) return;
      openDrawer($(this));
    });
    $(document).on('keydown', '#expense_report_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $('#exr_drawer_close, #exr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });
  });
})();
