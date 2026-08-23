/**
 * Sales Representative Report — premium UI helpers only.
 * Reuses #sales_representative_filter_form, #sr_id, #sr_business_id,
 * #sr_date_filter, summary spans, Bootstrap tabs, report.js DataTables
 * + updateSalesRepresentativeReport(), payment.js, and export buttons.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.srr-shell')) return;

  var STORAGE_KEY = 'vkpos_srr_dark_mode';
  var SETTINGS_KEY = 'vkpos_srr_settings';
  var searchTimer = null;

  var TAB_TABLES = {
    '#sr_sales_tab': { id: 'sr_sales_report', name: 'sr_sales_report', kind: 'sales' },
    '#sr_commission_tab': { id: 'sr_sales_with_commission_table', name: 'sr_sales_commission_report', kind: 'sales' },
    '#sr_expenses_tab': { id: 'sr_expenses_report', name: 'sr_expenses_report', kind: 'expense' },
    '#sr_payments_with_cmmsn_tab': { id: 'sr_payments_with_commission_table', name: 'sr_payments_with_commission_report', kind: 'payment' },
  };

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('srr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'srr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function dtByName(name, selector) {
    try {
      if (typeof window[name] !== 'undefined' && window[name]) return window[name];
    } catch (e) {}
    var $t = $(selector);
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function activeHref() {
    return ($('.srr-shell .nav-tabs li.active a[data-toggle="tab"]').attr('href') || '#sr_sales_tab');
  }

  function activeMeta() {
    return TAB_TABLES[activeHref()] || TAB_TABLES['#sr_sales_tab'];
  }

  function tableApi() {
    var meta = activeMeta();
    return dtByName(meta.name, '#' + meta.id);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('srr-dark-mode', enabled);
    document.body.classList.toggle('srr-dark-mode', enabled);
    var shell = document.querySelector('.srr-shell');
    if (shell) shell.classList.toggle('srr-dark-mode', enabled);
    var icon = document.querySelector('#srr_dark_mode_toggle i');
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
    var shell = document.querySelector('.srr-shell');
    if (!shell) return;
    shell.classList.toggle('srr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('srr-compact', !!(s && s.compact));
    var k = document.getElementById('srr_set_hide_kpis');
    var c = document.getElementById('srr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('srr_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('srr_set_compact') || {}).checked,
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
    var v = ($('#sr_date_filter').val() || '').trim();
    return v || '—';
  }

  function setPeriodChip(code) {
    $('#srr_period_chips .srr-chip').removeClass('is-active');
    if (code) $('#srr_period_chips .srr-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#sr_date_filter').val() || '').trim();
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
    var user = selectedText('#sr_id');
    var loc = selectedText('#sr_business_id');
    var range = dateVal();
    $('#srr_meta_user').text(user);
    $('#srr_meta_location').text(loc);
    $('#srr_meta_range').text(range);
    $('.srr-print-user').text(user);
    $('.srr-print-location').text(loc);
    $('.srr-print-range').text(range);
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

  function reloadReport() {
    if (typeof updateSalesRepresentativeReport === 'function') {
      updateSalesRepresentativeReport();
      return;
    }
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var meta = activeMeta();
    var $btn = $('#' + meta.id + '_wrapper').find(cls).first();
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
    var $input = $('#sr_date_filter');
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

  function resetFilters() {
    $('#sr_id, #sr_business_id').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#sr_date_filter').val('');
    $('#srr_quick_search').val('');
    $('#srr_search_clear').prop('hidden', true);
    setPeriodChip('all');
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('srr_export_menu');
    var btn = document.getElementById('srr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#srr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#srr_drawer_backdrop').prop('hidden', true);
    $('.srr-shell .tab-content table tbody tr').removeClass('srr-row-active');
  }

  function addDl($dl, label, value, asHtml) {
    if (value === null || value === undefined || value === '') return;
    var $dd = $('<dd/>');
    if (asHtml) $dd.html(htmlOrDash(value));
    else $dd.text(textFromHtml(value));
    if ($dd.text() === '—' && !asHtml) return;
    $dl.append($('<div/>').append($('<dt/>').text(label), $dd));
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;
    var meta = activeMeta();

    $('.srr-shell .tab-content table tbody tr').removeClass('srr-row-active');
    $row.addClass('srr-row-active');

    var $dl = $('#srr_drawer_dl').empty();
    $('#srr_drawer_actions').empty();

    if (meta.kind === 'sales') {
      $('#srr_drawer_title').text(textFromHtml(data.invoice_no));
      $('#srr_drawer_sub').html(htmlOrDash(data.conatct_name || data.contact_name));
      addDl($dl, 'Date', data.transaction_date, true);
      addDl($dl, 'Invoice', data.invoice_no, true);
      addDl($dl, 'Customer', data.conatct_name || data.contact_name, true);
      addDl($dl, 'Location', data.business_location, true);
      addDl($dl, 'Payment status', data.payment_status, true);
      addDl($dl, 'Total', data.final_total, true);
      addDl($dl, 'Paid', data.total_paid, true);
      addDl($dl, 'Remaining', data.total_remaining, true);
    } else if (meta.kind === 'expense') {
      $('#srr_drawer_title').text(textFromHtml(data.ref_no));
      $('#srr_drawer_sub').text(textFromHtml(data.category));
      addDl($dl, 'Date', data.transaction_date, true);
      addDl($dl, 'Reference', data.ref_no, true);
      addDl($dl, 'Category', data.category, true);
      addDl($dl, 'Location', data.location_name, true);
      addDl($dl, 'Payment status', data.payment_status, true);
      addDl($dl, 'Amount', data.final_total, true);
      addDl($dl, 'Expense for', data.expense_for, true);
      addDl($dl, 'Note', data.additional_notes, true);
    } else {
      $('#srr_drawer_title').text(textFromHtml(data.payment_ref_no));
      $('#srr_drawer_sub').html(htmlOrDash(data.customer));
      addDl($dl, 'Payment ref', data.payment_ref_no, true);
      addDl($dl, 'Paid on', data.paid_on, true);
      addDl($dl, 'Amount', data.amount, true);
      addDl($dl, 'Customer', data.customer, true);
      addDl($dl, 'Method', data.method, true);
      addDl($dl, 'Sale', data.invoice_no, true);
      if (data.action) $('#srr_drawer_actions').html(data.action);
    }

    $('#srr_drawer_backdrop').prop('hidden', false);
    $('#srr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var meta = activeMeta();
    var due = 0;
    $('#' + meta.id + ' tbody tr').each(function () {
      var $tr = $(this);
      $tr.removeClass('srr-row-due');
      if ($tr.find('td.dataTables_empty').length) return;
      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;
      $tr.attr('tabindex', '0');
      var status = (data.payment_status || '').toString().toLowerCase();
      if (status.indexOf('due') !== -1 || status.indexOf('partial') !== -1) {
        due += 1;
        $tr.addClass('srr-row-due');
      }
    });

    var $strip = $('#srr_exception_strip').empty();
    if (!due || meta.kind === 'payment') {
      $strip.prop('hidden', true);
      return;
    }
    $strip.prop('hidden', false);
    $strip.append(
      $('<button type="button" class="srr-chip amber" data-kind="due"/>')
        .text('Due / partial (this page) · ' + due)
    );
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    decorateRows();
    syncMeta();
    var empty = info && info.recordsDisplay === 0;
    $('#srr_empty').prop('hidden', !empty);
    if (dt && dt.columns) {
      try { dt.columns.adjust(); } catch (e) {}
    }
  }

  function showError(show) {
    $('#srr_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#sales_representative_filter_form').on('submit', function (e) {
      e.preventDefault();
      reloadReport();
    });

    $('#srr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('srr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#srr_settings_toggle').on('click', function () {
      var panel = document.getElementById('srr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#srr_set_hide_kpis, #srr_set_compact').on('change', saveSettings);

    $('#srr_fullscreen').on('click', function () {
      var el = document.getElementById('srr_shell');
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
      var icon = document.querySelector('#srr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#srr_refresh, #srr_apply_filters, #srr_retry').on('click', function () {
      showError(false);
      reloadReport();
    });
    $('#srr_reset_filters, #srr_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#srr_focus_search').on('click', function () {
      var input = document.getElementById('srr_quick_search');
      if (input) input.focus();
    });

    $('#srr_print, #srr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#srr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('srr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#srr_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#srr_export_excel, #srr_export_excel_alt, #srr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#srr_export_csv, #srr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#srr_export_pdf, #srr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#srr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#srr_quick_search').on('input', function () {
      var q = this.value;
      $('#srr_search_clear').prop('hidden', q.length === 0);
      $('#srr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#srr_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#srr_search_clear').on('click', function () {
      $('#srr_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#srr_filters_toggle').on('click', function () {
      var $body = $('#srr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#srr_period_chips').on('click', '.srr-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $(document).on('change', '#sr_id, #sr_business_id, #sr_date_filter', function () {
      setTimeout(syncMeta, 40);
    });

    $('.srr-shell a[data-toggle="tab"]').on('shown.bs.tab', function () {
      setTimeout(updateFromTable, 40);
    });

    Object.keys(TAB_TABLES).forEach(function (href) {
      var meta = TAB_TABLES[href];
      $('#' + meta.id)
        .on('processing.dt', function (e, settings, processing) {
          $('#srr_table_loading').prop('hidden', !processing);
          $('#srr_search_spinner').prop('hidden', !processing);
          $('.srr-kpi').toggleClass('is-loading', !!processing);
        })
        .on('draw.dt', function () {
          showError(false);
          $('.srr-kpi').removeClass('is-loading');
          setTimeout(updateFromTable, 40);
        })
        .on('error.dt', function () {
          showError(true);
          $('.srr-kpi').removeClass('is-loading');
        });
    });

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('sales-representative') !== -1 && xhr.status >= 400) showError(true);
    });

    $(document).on('click', '.srr-shell .tab-content table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .btn-modal, .view_payment, .tw-dw-btn, input, select').length) {
        return;
      }
      openDrawer($(this));
    });
    $(document).on('keydown', '.srr-shell .tab-content table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#srr_exception_strip .srr-chip', function () {
      var $row = $('.srr-shell .tab-pane.active table tbody tr.srr-row-due').first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('srr-row-active');
      }
    });

    $('#srr_drawer_close, #srr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(updateFromTable, 700);
  });
})();
