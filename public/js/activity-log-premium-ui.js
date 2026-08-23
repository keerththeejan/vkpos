/**
 * Activity Log — premium UI helpers only.
 * Reuses #al_users_filter, #subject_type, #al_date_filter,
 * #activity_log_table and the existing inline DataTables init.
 * No calculation, audit, or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.alg-shell')) return;

  var STORAGE_KEY = 'vkpos_alg_dark_mode';
  var SETTINGS_KEY = 'vkpos_alg_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('alg_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'alg-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof activity_log_table !== 'undefined' && activity_log_table) {
        return activity_log_table;
      }
    } catch (e) {}
    var $t = $('#activity_log_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('alg-dark-mode', enabled);
    document.body.classList.toggle('alg-dark-mode', enabled);
    var shell = document.querySelector('.alg-shell');
    if (shell) shell.classList.toggle('alg-dark-mode', enabled);
    var icon = document.querySelector('#alg_dark_mode_toggle i');
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
    var shell = document.querySelector('.alg-shell');
    if (!shell) return;
    shell.classList.toggle('alg-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('alg-compact', !!(s && s.compact));
    var k = document.getElementById('alg_set_hide_kpis');
    var c = document.getElementById('alg_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('alg_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('alg_set_compact') || {}).checked,
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
    var v = ($('#al_date_filter').val() || '').trim();
    return v || '—';
  }

  function setPeriodChip(code) {
    $('#alg_period_chips .alg-chip').removeClass('is-active');
    if (code) $('#alg_period_chips .alg-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function setSubjectChip(val) {
    $('#alg_subject_chips .alg-chip').removeClass('is-active');
    var $chip = $('#alg_subject_chips .alg-chip[data-subject="' + (val || '') + '"]');
    if ($chip.length) $chip.addClass('is-active');
    else $('#alg_subject_chips .alg-chip[data-subject=""]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#al_date_filter').val() || '').trim();
    if (!val) {
      setPeriodChip('all');
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
    var user = selectedText('#al_users_filter');
    var subject = selectedText('#subject_type');
    var range = dateVal();
    $('#alg_meta_user').text(user);
    $('#alg_meta_subject').text(subject);
    $('#alg_meta_range').text(range);
    $('.alg-print-user').text(user);
    $('.alg-print-subject').text(subject);
    $('.alg-print-range').text(range);
    setSubjectChip($('#subject_type').val() || '');
    syncPeriodChip();
  }

  function textFromHtml(html) {
    if (html === null || html === undefined || html === '') return '—';
    var t = $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
    return t || '—';
  }

  function htmlOrDash(html) {
    if (html === null || html === undefined || html === '') return '—';
    var t = $('<div>').html(html);
    if (!t.text().replace(/\s+/g, ' ').trim()) return '—';
    return html;
  }

  function hasVal(v) {
    if (v === null || v === undefined) return false;
    return String(v).replace(/\s+/g, '').length > 0;
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function reloadReport() {
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var $btn = $('#activity_log_table_wrapper').find(cls).first();
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

  function applyPeriod(code) {
    var $input = $('#al_date_filter');
    if (code === 'custom') {
      setPeriodChip('custom');
      $input.trigger('click').focus();
      return;
    }
    if (code === 'all') {
      $input.val('');
      setPeriodChip('all');
      reloadReport();
      syncMeta();
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
    reloadReport();
    syncMeta();
  }

  function applySubject(val) {
    var $s = $('#subject_type');
    $s.val(val);
    $s.trigger('change');
    setSubjectChip(val);
    syncMeta();
  }

  function resetFilters() {
    $('#al_users_filter, #subject_type').each(function () {
      var $s = $(this);
      $s.val('');
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#al_date_filter').val('');
    $('#alg_quick_search').val('');
    $('#alg_search_clear').prop('hidden', true);
    setPeriodChip('all');
    setSubjectChip('');
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('alg_export_menu');
    var btn = document.getElementById('alg_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#alg_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#alg_drawer_backdrop').prop('hidden', true);
    $('#activity_log_table tbody tr').removeClass('alg-row-active');
  }

  function actionKind(text) {
    var t = (text || '').toString().toLowerCase();
    if (t.indexOf('delet') !== -1) return 'delete';
    if (t.indexOf('updat') !== -1 || t.indexOf('edit') !== -1) return 'update';
    if (t.indexOf('add') !== -1 || t.indexOf('creat') !== -1) return 'create';
    return 'other';
  }

  function addDl($dl, label, value, asHtml) {
    if (!hasVal(value) && value !== 0) return;
    var $dd = $('<dd/>');
    if (asHtml) $dd.html(htmlOrDash(value));
    else $dd.text(textFromHtml(value));
    $dl.append($('<div/>').append($('<dt/>').text(label), $dd));
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#activity_log_table tbody tr').removeClass('alg-row-active');
    $row.addClass('alg-row-active');

    var action = textFromHtml(data.description);
    var kind = actionKind(action);
    $('#alg_drawer_title').text(action);
    $('#alg_drawer_sub').text(textFromHtml(data.created_at));
    $('#alg_drawer_status').html(
      '<span class="alg-action is-' + kind + '">' + $('<div>').text(action).html() + '</span>'
    );

    var $dl = $('#alg_drawer_dl').empty();
    addDl($dl, 'Date', data.created_at, false);
    addDl($dl, 'Subject', data.subject_type, false);
    addDl($dl, 'Action', data.description, false);
    addDl($dl, 'By', data.created_by, false);
    addDl($dl, 'Note', data.note, true);
    if (hasVal(data.subject_id)) addDl($dl, 'Record ID', data.subject_id, false);
    if (hasVal(data.event)) addDl($dl, 'Event', data.event, false);
    if (hasVal(data.log_name)) addDl($dl, 'Log', data.log_name, false);

    $('#alg_drawer_backdrop').prop('hidden', false);
    $('#alg_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var created = 0;
    var updated = 0;
    var deleted = 0;

    $('#activity_log_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.alg-action').remove();
      $tr.removeClass('alg-row-delete alg-row-update');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;
      $tr.attr('tabindex', '0');

      var action = textFromHtml(data.description);
      var kind = actionKind(action);
      if (kind === 'create') created += 1;
      if (kind === 'update') {
        updated += 1;
        $tr.addClass('alg-row-update');
      }
      if (kind === 'delete') {
        deleted += 1;
        $tr.addClass('alg-row-delete');
      }

      var $actionTd = $tr.children('td').eq(2);
      if ($actionTd.length && !$actionTd.find('.alg-action').length) {
        $actionTd.html(
          '<span class="alg-action is-' + kind + '">' + $('<div>').text(action).html() + '</span>'
        );
      }
    });

    var $strip = $('#alg_exception_strip').empty();
    if (!created && !updated && !deleted) {
      $strip.prop('hidden', true);
      return;
    }
    $strip.prop('hidden', false);
    if (created) {
      $strip.append(
        $('<button type="button" class="alg-chip" data-kind="create"/>')
          .text('Created (this page) · ' + created)
      );
    }
    if (updated) {
      $strip.append(
        $('<button type="button" class="alg-chip amber" data-kind="update"/>')
          .text('Updated (this page) · ' + updated)
      );
    }
    if (deleted) {
      $strip.append(
        $('<button type="button" class="alg-chip amber" data-kind="delete"/>')
          .text('Deleted (this page) · ' + deleted)
      );
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    setText('alg_kpi_total', info ? String(info.recordsDisplay) : '—');
    var pageCount = 0;
    if (info && info.recordsDisplay) {
      pageCount = Math.max(0, info.end - info.start);
    }
    setText('alg_kpi_page', pageCount ? String(pageCount) : (info && info.recordsDisplay === 0 ? '0' : '—'));
    decorateRows();
    syncMeta();
    var empty = info && info.recordsDisplay === 0;
    $('#alg_empty').prop('hidden', !empty);
    $('.alg-table-card').toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#alg_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#alg_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('alg-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#alg_settings_toggle').on('click', function () {
      var panel = document.getElementById('alg_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#alg_set_hide_kpis, #alg_set_compact').on('change', saveSettings);

    $('#alg_fullscreen').on('click', function () {
      var el = document.getElementById('alg_shell');
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
      var icon = document.querySelector('#alg_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#alg_refresh, #alg_apply_filters, #alg_retry').on('click', function () {
      showError(false);
      reloadReport();
    });
    $('#alg_reset_filters, #alg_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#alg_focus_search').on('click', function () {
      var input = document.getElementById('alg_quick_search');
      if (input) input.focus();
    });

    $('#alg_print, #alg_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#alg_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('alg_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#alg_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#alg_export_excel, #alg_export_excel_alt, #alg_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#alg_export_csv, #alg_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#alg_export_pdf, #alg_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#alg_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#alg_quick_search').on('input', function () {
      var q = this.value;
      $('#alg_search_clear').prop('hidden', q.length === 0);
      $('#alg_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#alg_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#alg_search_clear').on('click', function () {
      $('#alg_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#alg_filters_toggle').on('click', function () {
      var $body = $('#alg_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#alg_period_chips').on('click', '.alg-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $('#alg_subject_chips').on('click', '.alg-chip', function () {
      applySubject($(this).attr('data-subject') || '');
    });

    $('#activity_log_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#alg_table_loading').prop('hidden', !processing);
        $('#alg_search_spinner').prop('hidden', !processing);
        $('.alg-kpi').toggleClass('is-loading', !!processing);
      })
      .on('draw.dt', function () {
        showError(false);
        $('.alg-kpi').removeClass('is-loading');
        setTimeout(updateFromTable, 40);
      })
      .on('error.dt', function () {
        showError(true);
        $('.alg-kpi').removeClass('is-loading');
      });

    $(document).on('change', '#al_users_filter, #subject_type, #al_date_filter', function () {
      setTimeout(syncMeta, 40);
    });

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('activity-log') !== -1 && xhr.status >= 400) showError(true);
    });

    $(document).on('click', '#activity_log_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, input, select').length) return;
      openDrawer($(this));
    });
    $(document).on('keydown', '#activity_log_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#alg_exception_strip .alg-chip', function () {
      var kind = $(this).data('kind');
      var cls = kind === 'delete' ? 'alg-row-delete' : kind === 'update' ? 'alg-row-update' : '';
      var $row = cls
        ? $('#activity_log_table tbody tr.' + cls).first()
        : $('#activity_log_table tbody td .alg-action.is-create').closest('tr').first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('alg-row-active');
      }
    });

    $('#alg_drawer_close, #alg_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(updateFromTable, 700);
  });
})();
