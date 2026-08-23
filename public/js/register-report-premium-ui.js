/**
 * Register Report — premium UI helpers only.
 * Reuses #register_report_filter_form, #register_user_id,
 * #register_status, #register_report_date_range,
 * #register_report_table, report.js DataTables + updateRegisterReport(),
 * footer totals, Select2, daterangepicker, export buttons,
 * and .view_register / .btn-modal details.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.rgr-shell')) return;

  var STORAGE_KEY = 'vkpos_rgr_dark_mode';
  var SETTINGS_KEY = 'vkpos_rgr_settings';
  var searchTimer = null;

  var PAY_FIELDS = [
    { key: 'total_card_payment', footer: 'footer_total_card_payment', label: 'Card' },
    { key: 'total_cheque_payment', footer: 'footer_total_cheque_payment', label: 'Cheque' },
    { key: 'total_cash_payment', footer: 'footer_total_cash_payment', label: 'Cash' },
    { key: 'total_bank_transfer_payment', footer: 'footer_total_bank_transfer_payment', label: 'Bank transfer' },
    { key: 'total_advance_payment', footer: 'footer_total_advance_payment', label: 'Advance' },
    { key: 'total_custom_pay_1', footer: 'footer_total_custom_pay_1', label: 'Custom 1' },
    { key: 'total_custom_pay_2', footer: 'footer_total_custom_pay_2', label: 'Custom 2' },
    { key: 'total_custom_pay_3', footer: 'footer_total_custom_pay_3', label: 'Custom 3' },
    { key: 'total_custom_pay_4', footer: 'footer_total_custom_pay_4', label: 'Custom 4' },
    { key: 'total_custom_pay_5', footer: 'footer_total_custom_pay_5', label: 'Custom 5' },
    { key: 'total_custom_pay_6', footer: 'footer_total_custom_pay_6', label: 'Custom 6' },
    { key: 'total_custom_pay_7', footer: 'footer_total_custom_pay_7', label: 'Custom 7' },
    { key: 'total_other_payment', footer: 'footer_total_other_payments', label: 'Other' },
    { key: 'total', footer: 'footer_total', label: 'Total' },
  ];

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('rgr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'rgr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof register_report_table !== 'undefined' && register_report_table) {
        return register_report_table;
      }
    } catch (e) {}
    var $t = $('#register_report_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('rgr-dark-mode', enabled);
    document.body.classList.toggle('rgr-dark-mode', enabled);
    var shell = document.querySelector('.rgr-shell');
    if (shell) shell.classList.toggle('rgr-dark-mode', enabled);
    var icon = document.querySelector('#rgr_dark_mode_toggle i');
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
    var shell = document.querySelector('.rgr-shell');
    if (!shell) return;
    shell.classList.toggle('rgr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('rgr-compact', !!(s && s.compact));
    var k = document.getElementById('rgr_set_hide_kpis');
    var c = document.getElementById('rgr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('rgr_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('rgr_set_compact') || {}).checked,
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
    var v = ($('#register_report_date_range').val() || '').trim();
    return v || '—';
  }

  function setPeriodChip(code) {
    $('#rgr_period_chips .rgr-chip').removeClass('is-active');
    if (code) $('#rgr_period_chips .rgr-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function setStatusChip(val) {
    $('#rgr_status_chips .rgr-chip').removeClass('is-active');
    var $chip = $('#rgr_status_chips .rgr-chip[data-status="' + (val || '') + '"]');
    if ($chip.length) $chip.addClass('is-active');
    else $('#rgr_status_chips .rgr-chip[data-status=""]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#register_report_date_range').val() || '').trim();
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
    var user = selectedText('#register_user_id');
    var status = selectedText('#register_status');
    var range = dateVal();
    $('#rgr_meta_user').text(user);
    $('#rgr_meta_status').text(status);
    $('#rgr_meta_range').text(range);
    $('.rgr-print-user').text(user);
    $('.rgr-print-status').text(status);
    $('.rgr-print-range').text(range);
    setStatusChip($('#register_status').val() || '');
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

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function footerText(cls) {
    return ($('.' + cls).first().text() || '').trim() || '—';
  }

  function headerLabel(index) {
    var t = $('#register_report_table thead th').eq(index).text().replace(/\s+/g, ' ').trim();
    return t || '';
  }

  function reloadReport() {
    if (typeof updateRegisterReport === 'function') {
      updateRegisterReport();
      return;
    }
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var $btn = $('#register_report_table_wrapper').find(cls).first();
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
    var $input = $('#register_report_date_range');
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

  function applyStatus(val) {
    var $s = $('#register_status');
    $s.val(val);
    if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change');
    else $s.trigger('change');
    setStatusChip(val);
    syncMeta();
  }

  function resetFilters() {
    $('#register_user_id, #register_status').each(function () {
      var $s = $(this);
      $s.val('');
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#register_report_date_range').val('');
    $('#rgr_quick_search').val('');
    $('#rgr_search_clear').prop('hidden', true);
    setPeriodChip('all');
    setStatusChip('');
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('rgr_export_menu');
    var btn = document.getElementById('rgr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#rgr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#rgr_drawer_backdrop').prop('hidden', true);
    $('#register_report_table tbody tr').removeClass('rgr-row-active');
  }

  function money(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof __currency_trans_from_en === 'function') {
      return __currency_trans_from_en(parseFloat(value) || 0);
    }
    return String(value);
  }

  function parseDenoms(raw) {
    if (!raw) return null;
    if (typeof raw === 'object') return raw;
    if (typeof raw === 'string') {
      try {
        return JSON.parse(raw);
      } catch (e) {
        return null;
      }
    }
    return null;
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#register_report_table tbody tr').removeClass('rgr-row-active');
    $row.addClass('rgr-row-active');

    var shell = document.getElementById('rgr_shell');
    var openLabel = (shell && shell.getAttribute('data-open')) || 'Open';
    var closeLabel = (shell && shell.getAttribute('data-close')) || 'Closed';
    var isOpen = data.status === 'open';

    $('#rgr_drawer_title').text(textFromHtml(data.location_name));
    $('#rgr_drawer_user, #rgr_drawer_user2').html(htmlOrDash(data.user_name));
    $('#rgr_drawer_open').text(textFromHtml(data.created_at));
    $('#rgr_drawer_close').text(isOpen ? '—' : textFromHtml(data.closed_at));
    $('#rgr_drawer_location').text(textFromHtml(data.location_name));
    $('#rgr_drawer_status').html(
      '<span class="rgr-status ' + (isOpen ? 'is-open' : 'is-close') + '">' +
      (isOpen ? openLabel : closeLabel) +
      '</span>'
    );

    var $pays = $('#rgr_drawer_payments').empty();
    PAY_FIELDS.forEach(function (field, i) {
      var html = data[field.key];
      if (html === null || html === undefined || html === '') return;
      var label = headerLabel(i + 4) || field.label;
      $pays.append(
        '<div><dt>' + $('<div>').text(label).html() + '</dt><dd>' + htmlOrDash(html) + '</dd></div>'
      );
    });

    var $closeSec = $('#rgr_drawer_close_section');
    var hasClosing = !isOpen && data.closing_amount !== undefined && data.closing_amount !== null && data.closing_amount !== '';
    var note = data.closing_note;
    var denoms = parseDenoms(data.denominations);
    var hasNote = !!(note && String(note).replace(/\s+/g, '').length);
    var hasDenoms = !!(denoms && typeof denoms === 'object' && Object.keys(denoms).length);

    if (hasClosing || hasNote || hasDenoms) {
      $closeSec.prop('hidden', false);
      $('#rgr_row_closing').prop('hidden', !hasClosing);
      $('#rgr_drawer_closing').text(hasClosing ? money(data.closing_amount) : '—');
      $('#rgr_row_note').prop('hidden', !hasNote);
      $('#rgr_drawer_note').text(hasNote ? String(note) : '—');
      var $list = $('#rgr_drawer_denoms').empty();
      if (hasDenoms) {
        Object.keys(denoms).forEach(function (k) {
          $list.append($('<li/>').append(
            $('<span/>').text(k),
            $('<span/>').text(String(denoms[k]))
          ));
        });
        $list.prop('hidden', false);
      } else {
        $list.prop('hidden', true);
      }
    } else {
      $closeSec.prop('hidden', true);
    }

    var $actions = $('#rgr_drawer_actions').empty();
    if (data.action) $actions.html(data.action);

    $('#rgr_drawer_backdrop').prop('hidden', false);
    $('#rgr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var openCount = 0;
    var shell = document.getElementById('rgr_shell');
    var openLabel = (shell && shell.getAttribute('data-open')) || 'Open';
    var closeLabel = (shell && shell.getAttribute('data-close')) || 'Closed';

    $('#register_report_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.rgr-status').remove();
      $tr.removeClass('rgr-row-open');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;
      $tr.attr('tabindex', '0');

      var isOpen = data.status === 'open';
      if (isOpen) {
        openCount += 1;
        $tr.addClass('rgr-row-open');
      }

      var $closeTd = $tr.children('td').eq(1);
      if ($closeTd.length && !$closeTd.find('.rgr-status').length) {
        $closeTd.append(
          '<span class="rgr-status ' + (isOpen ? 'is-open' : 'is-close') + '">' +
          (isOpen ? openLabel : closeLabel) +
          '</span>'
        );
      }
    });

    var $strip = $('#rgr_exception_strip').empty();
    if (!openCount) {
      $strip.prop('hidden', true);
      return;
    }
    $strip.prop('hidden', false);
    $strip.append(
      $('<button type="button" class="rgr-chip amber" data-kind="open"/>')
        .text('Open sessions (this page) · ' + openCount)
    );
  }

  function syncPaymix() {
    var $host = $('#rgr_paymix').empty();
    var map = [
      { cls: 'footer_total_card_payment', th: 4 },
      { cls: 'footer_total_cheque_payment', th: 5 },
      { cls: 'footer_total_cash_payment', th: 6 },
      { cls: 'footer_total_bank_transfer_payment', th: 7 },
      { cls: 'footer_total_advance_payment', th: 8 },
      { cls: 'footer_total_custom_pay_1', th: 9 },
      { cls: 'footer_total_custom_pay_2', th: 10 },
      { cls: 'footer_total_custom_pay_3', th: 11 },
      { cls: 'footer_total_custom_pay_4', th: 12 },
      { cls: 'footer_total_custom_pay_5', th: 13 },
      { cls: 'footer_total_custom_pay_6', th: 14 },
      { cls: 'footer_total_custom_pay_7', th: 15 },
      { cls: 'footer_total_other_payments', th: 16 },
      { cls: 'footer_total', th: 17 },
    ];
    map.forEach(function (item) {
      var val = footerText(item.cls);
      var label = headerLabel(item.th) || item.cls;
      $host.append(
        $('<div class="rgr-paymix-item"/>').append(
          $('<span/>').text(label),
          $('<strong/>').text(val)
        )
      );
    });
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    setText('rgr_kpi_sessions', info ? String(info.recordsDisplay) : '—');
    setText('rgr_kpi_total', footerText('footer_total'));
    setText('rgr_kpi_cash', footerText('footer_total_cash_payment'));
    setText('rgr_kpi_card', footerText('footer_total_card_payment'));
    decorateRows();
    syncPaymix();
    syncMeta();
    var empty = info && info.recordsDisplay === 0;
    $('#rgr_empty').prop('hidden', !empty);
    $('.rgr-table-card').toggleClass('is-empty', !!empty);
    if (!empty && dt && dt.columns) {
      try { dt.columns.adjust(); } catch (e) {}
    }
  }

  function showError(show) {
    $('#rgr_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#rgr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('rgr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#rgr_settings_toggle').on('click', function () {
      var panel = document.getElementById('rgr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#rgr_set_hide_kpis, #rgr_set_compact').on('change', saveSettings);

    $('#rgr_fullscreen').on('click', function () {
      var el = document.getElementById('rgr_shell');
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
      var icon = document.querySelector('#rgr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#rgr_refresh, #rgr_retry').on('click', function () {
      showError(false);
      reloadReport();
    });
    $('#rgr_reset_filters, #rgr_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#rgr_focus_search').on('click', function () {
      var input = document.getElementById('rgr_quick_search');
      if (input) input.focus();
    });

    $('#rgr_print, #rgr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#rgr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('rgr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#rgr_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#rgr_export_excel, #rgr_export_excel_alt, #rgr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#rgr_export_csv, #rgr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#rgr_export_pdf, #rgr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#rgr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#rgr_quick_search').on('input', function () {
      var q = this.value;
      $('#rgr_search_clear').prop('hidden', q.length === 0);
      $('#rgr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#rgr_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#rgr_search_clear').on('click', function () {
      $('#rgr_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#rgr_filters_toggle').on('click', function () {
      var $body = $('#rgr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#rgr_period_chips').on('click', '.rgr-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $('#rgr_status_chips').on('click', '.rgr-chip', function () {
      applyStatus($(this).attr('data-status') || '');
    });

    $('#register_report_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#rgr_table_loading').prop('hidden', !processing);
        $('#rgr_search_spinner').prop('hidden', !processing);
        $('.rgr-kpi').toggleClass('is-loading', !!processing);
      })
      .on('draw.dt', function () {
        showError(false);
        $('.rgr-kpi').removeClass('is-loading');
        setTimeout(updateFromTable, 40);
      })
      .on('error.dt', function () {
        showError(true);
        $('.rgr-kpi').removeClass('is-loading');
      });

    $(document).on('change', '#register_user_id, #register_status, #register_report_date_range', function () {
      setTimeout(syncMeta, 40);
    });

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('register-report') !== -1 && xhr.status >= 400) showError(true);
    });

    $(document).on('click', '#register_report_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .btn-modal, .tw-dw-btn, input, select').length) {
        return;
      }
      openDrawer($(this));
    });
    $(document).on('keydown', '#register_report_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#rgr_exception_strip .rgr-chip', function () {
      var $row = $('#register_report_table tbody tr.rgr-row-open').first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('rgr-row-active');
      }
    });

    $('#rgr_drawer_close, #rgr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(updateFromTable, 700);
  });
})();
