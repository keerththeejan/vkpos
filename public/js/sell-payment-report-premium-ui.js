/**
 * Sell Payment Report — premium UI helpers only.
 * Reuses #sell_payment_report_form, #sell_payment_report_table,
 * #customer_id, #location_id, #payment_types, #customer_group_filter,
 * #spr_date_filter, report.js DataTables, child-payment expand
 * (details-control), payment.js view_payment, footer total, Select2,
 * daterangepicker, and export buttons.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.spt-shell')) return;

  var STORAGE_KEY = 'vkpos_spt_dark_mode';
  var SETTINGS_KEY = 'vkpos_spt_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('spt_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'spt-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof sell_payment_report !== 'undefined' && sell_payment_report) {
        return sell_payment_report;
      }
    } catch (e) {}
    var $t = $('#sell_payment_report_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('spt-dark-mode', enabled);
    document.body.classList.toggle('spt-dark-mode', enabled);
    var shell = document.querySelector('.spt-shell');
    if (shell) shell.classList.toggle('spt-dark-mode', enabled);
    var icon = document.querySelector('#spt_dark_mode_toggle i');
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
    var shell = document.querySelector('.spt-shell');
    if (!shell) return;
    shell.classList.toggle('spt-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('spt-compact', !!(s && s.compact));
    var k = document.getElementById('spt_set_hide_kpis');
    var c = document.getElementById('spt_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('spt_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('spt_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (err) {}
    applySettings(s);
  }

  function selectedText(sel) {
    var t = ($(sel).find('option:selected').text() || '').trim();
    return t || '—';
  }

  function dateVal(sel) {
    var v = ($(sel).val() || '').trim();
    return v || '—';
  }

  function setPeriodChip(code) {
    $('#spt_period_chips .spt-chip').removeClass('is-active');
    $('#spt_period_chips .spt-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#spr_date_filter').val() || '').trim();
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
    var loc = selectedText('#sell_payment_report_form #location_id');
    var customer = selectedText('#sell_payment_report_form #customer_id');
    var method = selectedText('#sell_payment_report_form #payment_types');
    var group = selectedText('#sell_payment_report_form #customer_group_filter');
    var range = dateVal('#spr_date_filter');
    $('#spt_meta_location').text(loc);
    $('#spt_meta_customer').text(customer);
    $('#spt_meta_method').text(method);
    $('#spt_meta_group').text(group);
    $('#spt_meta_range').text(range);
    $('.spt-print-location').text(loc);
    $('.spt-print-customer').text(customer);
    $('.spt-print-method').text(method);
    $('.spt-print-group').text(group);
    $('.spt-print-range').text(range);
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

  function setHtml(id, value) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = value;
  }

  function showRow(id, show) {
    var el = document.getElementById(id);
    if (el) el.hidden = !show;
  }

  function reloadReport() {
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var $btn = $('#sell_payment_report_table_wrapper').find(cls).first();
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
    var $input = $('#spr_date_filter');
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
    $('#sell_payment_report_form select').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#spr_date_filter').val('');
    $('#spt_quick_search').val('');
    $('#spt_search_clear').prop('hidden', true);
    setPeriodChip('all');
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('spt_export_menu');
    var btn = document.getElementById('spt_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#spt_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#spt_drawer_backdrop').prop('hidden', true);
    $('#sell_payment_report_table tbody tr').removeClass('spt-row-active');
  }

  function methodKind(data) {
    if (hasVal(data.cheque_number)) return 'cheque';
    if (hasVal(data.card_transaction_number)) return 'card';
    if (hasVal(data.bank_account_number)) return 'bank';
    return '';
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.hasClass('child') || $row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#sell_payment_report_table tbody tr').removeClass('spt-row-active');
    $row.addClass('spt-row-active');

    $('#spt_drawer_title').text(textFromHtml(data.payment_ref_no));
    $('#spt_drawer_ref').text(textFromHtml(data.payment_ref_no));
    setHtml('spt_drawer_date', htmlOrDash(data.paid_on));
    setHtml('spt_drawer_customer', htmlOrDash(data.customer));
    setHtml('spt_drawer_contact', htmlOrDash(data.contact_id));
    setHtml('spt_drawer_group', htmlOrDash(data.customer_group));
    setHtml('spt_drawer_invoice', htmlOrDash(data.invoice_no));
    setHtml('spt_drawer_amount', htmlOrDash(data.amount));
    setHtml('spt_drawer_method', htmlOrDash(data.method));

    showRow('spt_row_cheque', hasVal(data.cheque_number));
    setText('spt_drawer_cheque', hasVal(data.cheque_number) ? String(data.cheque_number) : '—');
    showRow('spt_row_card', hasVal(data.card_transaction_number));
    setText('spt_drawer_card', hasVal(data.card_transaction_number) ? String(data.card_transaction_number) : '—');
    showRow('spt_row_bank', hasVal(data.bank_account_number));
    setText('spt_drawer_bank', hasVal(data.bank_account_number) ? String(data.bank_account_number) : '—');
    showRow('spt_row_txn', hasVal(data.transaction_no));
    setText('spt_drawer_txn', hasVal(data.transaction_no) ? String(data.transaction_no) : '—');

    var $actions = $('#spt_drawer_actions').empty();
    var actionHtml = data.action || '';
    if (actionHtml) $actions.html(actionHtml);

    $('#spt_drawer_backdrop').prop('hidden', false);
    $('#spt_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var grouped = 0;
    var cheque = 0;
    var card = 0;
    var bank = 0;
    var returns = 0;

    $('#sell_payment_report_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.spt-method-tag').remove();
      $tr.removeClass('spt-row-child spt-row-return');
      if ($tr.hasClass('child') || $tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;
      $tr.attr('tabindex', '0');

      if (!data.transaction_id) {
        grouped += 1;
        $tr.addClass('spt-row-child');
      }

      var isReturn = data.is_return == 1 || data.is_return === '1' || data.is_return === true;
      if (isReturn) {
        returns += 1;
        $tr.addClass('spt-row-return');
      }

      var kind = methodKind(data);
      var $methodTd = $tr.children('td').eq(7);
      if ($methodTd.length) {
        if (isReturn && !$methodTd.find('.spt-method-tag.is-return').length) {
          $methodTd.append('<span class="spt-method-tag is-return">CHANGE RETURN</span>');
        }
        if (kind && !$methodTd.find('.spt-method-tag.is-' + kind).length) {
          var label = kind === 'cheque' ? 'CHEQUE' : kind === 'card' ? 'CARD' : 'BANK';
          $methodTd.append('<span class="spt-method-tag is-' + kind + '">' + label + '</span>');
        }
      }
      if (kind === 'cheque') cheque += 1;
      if (kind === 'card') card += 1;
      if (kind === 'bank') bank += 1;
    });

    var $strip = $('#spt_exception_strip').empty();
    if (!grouped && !cheque && !card && !bank && !returns) {
      $strip.prop('hidden', true);
      return;
    }
    $strip.prop('hidden', false);
    if (grouped) {
      $strip.append(
        $('<button type="button" class="spt-chip" data-kind="grouped"/>')
          .text('Grouped payments (this page) · ' + grouped)
      );
    }
    if (returns) {
      $strip.append(
        $('<button type="button" class="spt-chip amber" data-kind="return"/>')
          .text('Change return (this page) · ' + returns)
      );
    }
    if (cheque) {
      $strip.append(
        $('<button type="button" class="spt-chip amber" data-kind="cheque"/>')
          .text('Cheque (this page) · ' + cheque)
      );
    }
    if (card) {
      $strip.append(
        $('<button type="button" class="spt-chip" data-kind="card"/>')
          .text('Card (this page) · ' + card)
      );
    }
    if (bank) {
      $strip.append(
        $('<button type="button" class="spt-chip" data-kind="bank"/>')
          .text('Bank transfer (this page) · ' + bank)
      );
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    setText('spt_kpi_lines', info ? String(info.recordsDisplay) : '—');
    setText('spt_kpi_amount', ($('#footer_total_amount').text() || '').trim() || '—');
    decorateRows();
    syncMeta();
    var empty = info && info.recordsDisplay === 0;
    $('#spt_empty').prop('hidden', !empty);
    $('.spt-table-card').toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#spt_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#sell_payment_report_form').on('submit', function (e) {
      e.preventDefault();
      reloadReport();
    });

    $('#spt_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('spt-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#spt_settings_toggle').on('click', function () {
      var panel = document.getElementById('spt_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#spt_set_hide_kpis, #spt_set_compact').on('change', saveSettings);

    $('#spt_fullscreen').on('click', function () {
      var el = document.getElementById('spt_shell');
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
      var icon = document.querySelector('#spt_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#spt_refresh, #spt_apply_filters, #spt_retry').on('click', function () {
      showError(false);
      reloadReport();
    });
    $('#spt_reset_filters, #spt_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#spt_focus_search').on('click', function () {
      var input = document.getElementById('spt_quick_search');
      if (input) input.focus();
    });

    $('#spt_print, #spt_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#spt_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('spt_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#spt_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#spt_export_excel, #spt_export_excel_alt, #spt_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#spt_export_csv, #spt_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#spt_export_pdf, #spt_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#spt_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#spt_quick_search').on('input', function () {
      var q = this.value;
      $('#spt_search_clear').prop('hidden', q.length === 0);
      $('#spt_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#spt_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#spt_search_clear').on('click', function () {
      $('#spt_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#spt_filters_toggle').on('click', function () {
      var $body = $('#spt_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#spt_period_chips').on('click', '.spt-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $('#sell_payment_report_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#spt_table_loading').prop('hidden', !processing);
        $('#spt_search_spinner').prop('hidden', !processing);
        $('.spt-kpi').toggleClass('is-loading', !!processing);
      })
      .on('draw.dt', function () {
        showError(false);
        $('.spt-kpi').removeClass('is-loading');
        setTimeout(updateFromTable, 40);
      })
      .on('error.dt', function () {
        showError(true);
        $('.spt-kpi').removeClass('is-loading');
      });

    $(document).on('change', '#sell_payment_report_form select, #spr_date_filter', function () {
      setTimeout(syncMeta, 40);
    });

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('sell-payment-report') !== -1 && xhr.status >= 400) showError(true);
    });

    $(document).on('click', '#sell_payment_report_table tbody tr', function (e) {
      var $tr = $(this);
      if ($tr.hasClass('child')) return;
      if ($(e.target).closest('td.details-control, a, button, .btn, .view_payment, .btn-modal, .tw-dw-btn, input, select').length) {
        return;
      }
      openDrawer($tr);
    });
    $(document).on('keydown', '#sell_payment_report_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('td.details-control, a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#spt_exception_strip .spt-chip', function () {
      var kind = $(this).data('kind');
      var $row;
      if (kind === 'grouped') {
        $row = $('#sell_payment_report_table tbody tr.spt-row-child').first();
      } else if (kind === 'return') {
        $row = $('#sell_payment_report_table tbody tr.spt-row-return').first();
      } else {
        $row = $('#sell_payment_report_table tbody td .spt-method-tag.is-' + kind).closest('tr').first();
      }
      if ($row && $row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('spt-row-active');
      }
    });

    $('#spt_drawer_close, #spt_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(updateFromTable, 700);
  });
})();
