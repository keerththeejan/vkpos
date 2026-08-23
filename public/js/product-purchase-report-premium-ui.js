/**
 * Product Purchase Report — premium UI helpers only.
 * Reuses #product_purchase_report_form, #product_purchase_report_table,
 * #search_product / #variation_id autocomplete, #supplier_id, #location_id,
 * #product_pr_date_filter, #ppr_brand_id, report.js DataTables AJAX,
 * footer totals, Select2, daterangepicker, and export buttons.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.ppr-shell')) return;

  var STORAGE_KEY = 'vkpos_ppr_dark_mode';
  var SETTINGS_KEY = 'vkpos_ppr_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('ppr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'ppr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof product_purchase_report !== 'undefined' && product_purchase_report) {
        return product_purchase_report;
      }
    } catch (e) {}
    var $t = $('#product_purchase_report_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ppr-dark-mode', enabled);
    document.body.classList.toggle('ppr-dark-mode', enabled);
    var shell = document.querySelector('.ppr-shell');
    if (shell) shell.classList.toggle('ppr-dark-mode', enabled);
    var icon = document.querySelector('#ppr_dark_mode_toggle i');
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
    var shell = document.querySelector('.ppr-shell');
    if (!shell) return;
    shell.classList.toggle('ppr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('ppr-compact', !!(s && s.compact));
    var k = document.getElementById('ppr_set_hide_kpis');
    var c = document.getElementById('ppr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('ppr_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('ppr_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (err) {}
    applySettings(s);
  }

  function selectedText(sel) {
    var $el = $(sel);
    if (!$el.length) return '—';
    var t = ($el.find('option:selected').text() || '').trim();
    return t || '—';
  }

  function dateVal(sel) {
    var v = ($(sel).val() || '').trim();
    return v || '—';
  }

  function syncMeta() {
    var loc = selectedText('#location_id');
    var supplier = selectedText('#supplier_id');
    var brand = selectedText('#ppr_brand_id');
    var range = dateVal('#product_pr_date_filter');
    var product = ($('#search_product').val() || '').trim() || '—';
    $('#ppr_meta_location').text(loc);
    $('#ppr_meta_supplier').text(supplier);
    $('#ppr_meta_brand').text(brand);
    $('#ppr_meta_range').text(range);
    $('#ppr_meta_product').text(product);
    $('.ppr-print-location').text(loc);
    $('.ppr-print-supplier').text(supplier);
    $('.ppr-print-brand').text(brand);
    $('.ppr-print-range').text(range);
    $('.ppr-print-product').text(product);
    syncPeriodChip();
  }

  function setPeriodChip(code) {
    $('#ppr_period_chips .ppr-chip').removeClass('is-active');
    $('#ppr_period_chips .ppr-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#product_pr_date_filter').val() || '').trim();
    if (!val) {
      setPeriodChip('all');
      return;
    }
    if (typeof ranges === 'undefined' || typeof LANG === 'undefined' || typeof moment_date_format === 'undefined') {
      setPeriodChip('custom');
      return;
    }
    var codes = ['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year'];
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
    codes.forEach(function (code) {
      var span = ranges[keyMap[code]];
      if (!span || !span[0] || !span[1]) return;
      var text = span[0].format(moment_date_format) + ' ~ ' + span[1].format(moment_date_format);
      if (text === val) matched = code;
    });
    setPeriodChip(matched || 'custom');
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

  function setHtml(id, value) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = value;
  }

  function origQty(html, cls) {
    var $el = $('<div>').html(html || '').find('.' + cls);
    if (!$el.length) return 0;
    var n = parseFloat($el.attr('data-orig-value'));
    return isNaN(n) ? 0 : n;
  }

  function copyFooter() {
    setText('ppr_kpi_qty', ($('#footer_total_purchase').text() || '').trim() || '—');
    setText('ppr_kpi_adjusted', ($('#footer_total_adjusted').text() || '').trim() || '—');
    setText('ppr_kpi_subtotal', ($('#footer_subtotal').text() || '').trim() || '—');
  }

  function reloadReport() {
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var $btn = $('#product_purchase_report_table_wrapper').find(cls).first();
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
    var $input = $('#product_pr_date_filter');
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
    var label = keyMap[code];
    var span = label ? ranges[label] : null;
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
    $('#product_purchase_report_form select, #ppr_brand_id').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) {
        $s.trigger('change.select2');
      }
    });
    $('#search_product').val('');
    $('#variation_id').val('');
    $('#product_pr_date_filter').val('');
    $('#ppr_quick_search').val('');
    $('#ppr_search_clear').prop('hidden', true);
    setPeriodChip('all');
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('ppr_export_menu');
    var btn = document.getElementById('ppr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#ppr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#ppr_drawer_backdrop').prop('hidden', true);
    $('#product_purchase_report_table tbody tr').removeClass('ppr-row-active');
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#product_purchase_report_table tbody tr').removeClass('ppr-row-active');
    $row.addClass('ppr-row-active');

    $('#ppr_drawer_title').text(textFromHtml(data.product_name));
    $('#ppr_drawer_sku').text(textFromHtml(data.sub_sku));
    setHtml('ppr_drawer_supplier', htmlOrDash(data.supplier));
    setHtml('ppr_drawer_ref', htmlOrDash(data.ref_no));
    setHtml('ppr_drawer_date', htmlOrDash(data.transaction_date));
    setHtml('ppr_drawer_qty', htmlOrDash(data.purchase_qty));
    setHtml('ppr_drawer_adjusted', htmlOrDash(data.quantity_adjusted));
    setHtml('ppr_drawer_price', htmlOrDash(data.unit_purchase_price));
    setHtml('ppr_drawer_subtotal', htmlOrDash(data.subtotal));

    $('#ppr_drawer_backdrop').prop('hidden', false);
    $('#ppr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var adjusted = 0;

    $('#product_purchase_report_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.ppr-badge').remove();
      $tr.removeClass('ppr-row-adjusted');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;

      $tr.attr('tabindex', '0');

      var $nameTd = $tr.children('td').eq(0);
      if ($nameTd.length && !$nameTd.find('.ppr-item-cell').length) {
        var nameHtml = $nameTd.html();
        var sku = textFromHtml(data.sub_sku);
        $nameTd.html(
          '<div class="ppr-item-cell"><span class="ppr-item-name">' +
            nameHtml +
            '</span>' +
            (sku && sku !== '—' ? '<span class="ppr-item-sku">' + $('<div>').text(sku).html() + '</span>' : '') +
            '</div>'
        );
      }

      if (origQty(data.quantity_adjusted, 'quantity_adjusted') > 0) {
        adjusted += 1;
        $tr.addClass('ppr-row-adjusted');
        var $adjTd = $tr.children('td').eq(6);
        if ($adjTd.length && !$adjTd.find('.ppr-badge').length) {
          $adjTd.append('<span class="ppr-badge ppr-badge-low">ADJ</span>');
        }
      }
    });

    var $strip = $('#ppr_exception_strip').empty();
    if (!adjusted) {
      $strip.prop('hidden', true);
    } else {
      $strip.prop('hidden', false);
      $strip.append(
        $('<button type="button" class="ppr-chip amber" data-kind="adjusted"/>')
          .text('Adjusted quantity (this page) · ' + adjusted)
      );
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var lines = info ? info.recordsDisplay : 0;
    setText('ppr_kpi_lines', String(lines));
    copyFooter();
    decorateRows();
    syncMeta();

    var empty = info && info.recordsDisplay === 0;
    $('#ppr_empty').prop('hidden', !empty);
    $('.ppr-table-card').toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#ppr_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#product_purchase_report_form').on('submit', function (e) {
      e.preventDefault();
      reloadReport();
    });

    $('#ppr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('ppr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#ppr_settings_toggle').on('click', function () {
      var panel = document.getElementById('ppr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#ppr_set_hide_kpis, #ppr_set_compact').on('change', saveSettings);

    $('#ppr_fullscreen').on('click', function () {
      var el = document.getElementById('ppr_shell');
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
      var icon = document.querySelector('#ppr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#ppr_refresh, #ppr_apply_filters, #ppr_retry').on('click', function () {
      showError(false);
      reloadReport();
    });

    $('#ppr_reset_filters, #ppr_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });

    $('#ppr_focus_search').on('click', function () {
      var input = document.getElementById('search_product');
      if (input) input.focus();
    });

    $('#ppr_print, #ppr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#ppr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('ppr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () {
      closeExport();
    });
    $('#ppr_export_menu').on('click', function (e) {
      e.stopPropagation();
    });

    $('#ppr_export_excel, #ppr_export_excel_alt, #ppr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#ppr_export_csv, #ppr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#ppr_export_pdf, #ppr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#ppr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#ppr_quick_search').on('input', function () {
      var q = this.value;
      $('#ppr_search_clear').prop('hidden', q.length === 0);
      $('#ppr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#ppr_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#ppr_search_clear').on('click', function () {
      $('#ppr_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#ppr_filters_toggle').on('click', function () {
      var $body = $('#ppr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#ppr_period_chips').on('click', '.ppr-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $('#product_purchase_report_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#ppr_table_loading').prop('hidden', !processing);
        $('#ppr_search_spinner').prop('hidden', !processing);
        $('.ppr-kpi').toggleClass('is-loading', !!processing);
      })
      .on('draw.dt', function () {
        showError(false);
        $('.ppr-kpi').removeClass('is-loading');
        setTimeout(updateFromTable, 40);
        var dt = tableApi();
        if (dt && dt.columns) {
          try {
            dt.columns.adjust();
          } catch (err) {}
        }
      })
      .on('error.dt', function () {
        showError(true);
        $('.ppr-kpi').removeClass('is-loading');
      });

    $(document).on(
      'change',
      '#product_purchase_report_form #variation_id, #product_purchase_report_form #location_id, #product_purchase_report_form #supplier_id, #product_pr_date_filter, #ppr_brand_id, #search_product',
      function () {
        setTimeout(syncMeta, 40);
      }
    );

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('product-purchase-report') !== -1 && xhr.status >= 400) {
        showError(true);
      }
    });

    $(document).on('click', '#product_purchase_report_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .btn-modal, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      openDrawer($(this));
    });
    $(document).on('keydown', '#product_purchase_report_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#ppr_exception_strip .ppr-chip', function () {
      var $row = $('#product_purchase_report_table tbody tr.ppr-row-adjusted').first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('ppr-row-active');
      }
    });

    $('#ppr_drawer_close, #ppr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(function () {
      updateFromTable();
      var dt = tableApi();
      if (dt && dt.columns) {
        try {
          dt.columns.adjust();
        } catch (err) {}
      }
    }, 700);
  });
})();
