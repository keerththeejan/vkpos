/**
 * Product Sell Report — premium UI helpers only.
 * Reuses #product_sell_report_form, all five tab tables, report.js
 * DataTables AJAX, the inline category/brand tab script, Select2,
 * daterangepicker, datetimepicker, autocomplete, and export buttons.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.ssr-shell')) return;

  var STORAGE_KEY = 'vkpos_ssr_dark_mode';
  var SETTINGS_KEY = 'vkpos_ssr_settings';
  var searchTimer = null;
  var initialStartTime = '';
  var initialEndTime = '';

  var TAB_TABLES = {
    '#psr_detailed_tab': { id: 'product_sell_report_table', name: 'product_sell_report' },
    '#psr_detailed_with_purchase_tab': { id: 'product_sell_report_with_purchase_table', name: 'product_sell_report_with_purchase_table' },
    '#psr_grouped_tab': { id: 'product_sell_grouped_report_table', name: 'product_sell_grouped_report' },
    '#psr_by_cat_tab': { id: 'product_sell_report_by_category', name: 'product_sell_report_by_category_datatable' },
    '#psr_by_brand_tab': { id: 'product_sell_report_by_brand', name: 'product_sell_report_by_brand_datatable' },
  };

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('ssr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'ssr-toast ' + (type || 'info');
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
    return ($('.ssr-shell .nav-tabs li.active a[data-toggle="tab"]').attr('href') || '#psr_detailed_tab');
  }

  function activeMeta() {
    return TAB_TABLES[activeHref()] || TAB_TABLES['#psr_detailed_tab'];
  }

  function tableApi() {
    var meta = activeMeta();
    return dtByName(meta.name, '#' + meta.id);
  }

  function eachMainTable(fn) {
    [
      ['product_sell_report', '#product_sell_report_table'],
      ['product_sell_grouped_report', '#product_sell_grouped_report_table'],
      ['product_sell_report_with_purchase_table', '#product_sell_report_with_purchase_table'],
      ['product_sell_report_by_category_datatable', '#product_sell_report_by_category'],
      ['product_sell_report_by_brand_datatable', '#product_sell_report_by_brand'],
    ].forEach(function (item) {
      var dt = dtByName(item[0], item[1]);
      if (dt) fn(dt, item[1]);
    });
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ssr-dark-mode', enabled);
    document.body.classList.toggle('ssr-dark-mode', enabled);
    var shell = document.querySelector('.ssr-shell');
    if (shell) shell.classList.toggle('ssr-dark-mode', enabled);
    var icon = document.querySelector('#ssr_dark_mode_toggle i');
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
    var shell = document.querySelector('.ssr-shell');
    if (!shell) return;
    shell.classList.toggle('ssr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('ssr-compact', !!(s && s.compact));
    var k = document.getElementById('ssr_set_hide_kpis');
    var c = document.getElementById('ssr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('ssr_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('ssr_set_compact') || {}).checked,
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
    $('#ssr_period_chips .ssr-chip').removeClass('is-active');
    $('#ssr_period_chips .ssr-chip[data-period="' + code + '"]').addClass('is-active');
  }

  function syncPeriodChip() {
    var val = ($('#product_sr_date_filter').val() || '').trim();
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
    var loc = selectedText('#location_id');
    var customer = selectedText('#customer_id');
    var cat = selectedText('#psr_filter_category_id');
    var brand = selectedText('#psr_filter_brand_id');
    var range = dateVal('#product_sr_date_filter');
    var time = (($('#product_sr_start_time').val() || '') + ' – ' + ($('#product_sr_end_time').val() || '')).trim();
    $('#ssr_meta_location').text(loc);
    $('#ssr_meta_customer').text(customer);
    $('#ssr_meta_category').text(cat);
    $('#ssr_meta_brand').text(brand);
    $('#ssr_meta_range').text(range);
    $('.ssr-print-location').text(loc);
    $('.ssr-print-customer').text(customer);
    $('.ssr-print-category').text(cat);
    $('.ssr-print-brand').text(brand);
    $('.ssr-print-range').text(range);
    $('.ssr-print-time').text(time || '—');
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

  function setHtml(id, value) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = value;
  }

  function showRow(id, show) {
    var el = document.getElementById(id);
    if (el) el.hidden = !show;
  }

  function footerText(sel) {
    return ($(sel).text() || '').trim() || '—';
  }

  function setKpi(slot, visible, label, value, hint) {
    var card = document.getElementById('ssr_kpi_' + slot);
    if (!card) return;
    card.hidden = !visible;
    if (!visible) return;
    setText('ssr_kpi_' + slot + '_label', label);
    setText('ssr_kpi_' + slot + '_value', value || '—');
    setText('ssr_kpi_' + slot + '_hint', hint || 'This page');
  }

  function reloadAll() {
    try { if (typeof product_sell_report !== 'undefined' && product_sell_report) product_sell_report.ajax.reload(null, false); } catch (e) {}
    try { if (typeof product_sell_grouped_report !== 'undefined' && product_sell_grouped_report) product_sell_grouped_report.ajax.reload(null, false); } catch (e2) {}
    try { if (typeof product_sell_report_with_purchase_table !== 'undefined' && product_sell_report_with_purchase_table) product_sell_report_with_purchase_table.ajax.reload(null, false); } catch (e3) {}
    $('.ssr-shell .nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
  }

  function clickDtButton(cls) {
    var id = activeMeta().id;
    var $btn = $('#' + id + '_wrapper').find(cls).first();
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
    var $input = $('#product_sr_date_filter');
    if (code === 'custom') {
      setPeriodChip('custom');
      $input.trigger('click').focus();
      return;
    }
    if (code === 'all') {
      $input.val('');
      setPeriodChip('all');
      reloadAll();
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
    reloadAll();
    syncMeta();
  }

  function resetFilters() {
    $('#product_sell_report_form select, #psr_filter_category_id, #psr_filter_brand_id, #psr_customer_group_id').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#search_product').val('');
    $('#variation_id').val('');
    $('#product_sr_date_filter').val('');
    $('#product_sr_start_time').val(initialStartTime);
    $('#product_sr_end_time').val(initialEndTime);
    $('#ssr_quick_search').val('');
    $('#ssr_search_clear').prop('hidden', true);
    setPeriodChip('all');
    eachMainTable(function (dt) {
      try { dt.search(''); } catch (e) {}
    });
    reloadAll();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('ssr_export_menu');
    var btn = document.getElementById('ssr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#ssr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#ssr_drawer_backdrop').prop('hidden', true);
    $('.ssr-shell .tab-content table tbody tr').removeClass('ssr-row-active');
  }

  function wrapProductCell($tr, data, skuKey) {
    var $nameTd = $tr.children('td').eq(0);
    if (!$nameTd.length || $nameTd.find('.ssr-item-cell').length) return;
    var sku = textFromHtml(data[skuKey]);
    $nameTd.html(
      '<div class="ssr-item-cell"><span class="ssr-item-name">' +
        $nameTd.html() +
        '</span>' +
        (sku && sku !== '—' ? '<span class="ssr-item-sku">' + $('<div>').text(sku).html() + '</span>' : '') +
        '</div>'
    );
  }

  function fillDrawer(data, href) {
    var title = textFromHtml(data.product_name || data.category_name || data.brand_name);
    $('#ssr_drawer_title').text(title);
    $('#ssr_drawer_sku').text(textFromHtml(data.sub_sku));

    var cf1Label = ($('#psr_product_custom_field1').text() || '').trim();
    var cf2Label = ($('#psr_product_custom_field2').text() || '').trim();
    showRow('ssr_row_cf1', !!cf1Label && href === '#psr_detailed_tab');
    showRow('ssr_row_cf2', !!cf2Label && href === '#psr_detailed_tab');
    if (cf1Label) {
      setText('ssr_dt_cf1', cf1Label);
      setHtml('ssr_drawer_cf1', htmlOrDash(data.product_custom_field1));
    }
    if (cf2Label) {
      setText('ssr_dt_cf2', cf2Label);
      setHtml('ssr_drawer_cf2', htmlOrDash(data.product_custom_field2));
    }

    showRow('ssr_row_date', !!(data.transaction_date));
    setHtml('ssr_drawer_date', htmlOrDash(data.transaction_date));
    showRow('ssr_row_invoice', !!(data.invoice_no));
    setHtml('ssr_drawer_invoice', htmlOrDash(data.invoice_no));
    showRow('ssr_row_pref', !!(data.ref_no));
    setHtml('ssr_drawer_pref', htmlOrDash(data.ref_no));
    showRow('ssr_row_lot', href === '#psr_detailed_with_purchase_tab');
    setHtml('ssr_drawer_lot', htmlOrDash(data.lot_number));
    showRow('ssr_row_category', href === '#psr_by_cat_tab');
    setHtml('ssr_drawer_category', htmlOrDash(data.category_name));
    showRow('ssr_row_brand', href === '#psr_by_brand_tab');
    setHtml('ssr_drawer_brand', htmlOrDash(data.brand_name));

    showRow('ssr_row_customer', !!(data.customer));
    setHtml('ssr_drawer_customer', htmlOrDash(data.customer));
    showRow('ssr_row_contact', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_contact', htmlOrDash(data.contact_id));
    showRow('ssr_row_supplier', href === '#psr_detailed_with_purchase_tab');
    setHtml('ssr_drawer_supplier', htmlOrDash(data.supplier_name));
    $('#ssr_sec_party').prop('hidden', !(data.customer || data.supplier_name || (href === '#psr_detailed_tab' && data.contact_id)));

    var qty = data.sell_qty || data.total_qty_sold || data.purchase_quantity;
    showRow('ssr_row_qty', !!qty);
    setHtml('ssr_drawer_qty', htmlOrDash(qty));
    showRow('ssr_row_stock', data.current_stock !== undefined && data.current_stock !== null);
    setHtml('ssr_drawer_stock', htmlOrDash(data.current_stock));

    var hasValue = href === '#psr_detailed_tab' || href === '#psr_grouped_tab' || href === '#psr_by_cat_tab' || href === '#psr_by_brand_tab';
    $('#ssr_sec_value').prop('hidden', !hasValue || href === '#psr_detailed_with_purchase_tab');
    showRow('ssr_row_price', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_price', htmlOrDash(data.unit_price));
    showRow('ssr_row_discount', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_discount', htmlOrDash(data.discount_amount));
    showRow('ssr_row_tax', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_tax', htmlOrDash(data.tax));
    showRow('ssr_row_inc', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_inc', htmlOrDash(data.unit_sale_price));
    showRow('ssr_row_subtotal', !!data.subtotal);
    setHtml('ssr_drawer_subtotal', htmlOrDash(data.subtotal));
    showRow('ssr_row_pay', href === '#psr_detailed_tab');
    setHtml('ssr_drawer_pay', htmlOrDash(data.payment_methods));
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('.ssr-shell .tab-content table tbody tr').removeClass('ssr-row-active');
    $row.addClass('ssr-row-active');
    fillDrawer(data, activeHref());
    $('#ssr_drawer_backdrop').prop('hidden', false);
    $('#ssr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var href = activeHref();
    var dt = tableApi();
    var discounted = 0;
    var $table = $('#' + activeMeta().id);

    $table.find('tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.ssr-badge').remove();
      $tr.removeClass('ssr-row-discount');
      if ($tr.find('td.dataTables_empty').length) return;
      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;
      $tr.attr('tabindex', '0');

      if (data.product_name) wrapProductCell($tr, data, 'sub_sku');

      if (href === '#psr_detailed_tab') {
        var disc = textFromHtml(data.discount_amount);
        if (disc && disc !== '—') {
          discounted += 1;
          $tr.addClass('ssr-row-discount');
        }
      }
    });

    var $strip = $('#ssr_exception_strip').empty();
    if (href === '#psr_detailed_tab' && discounted) {
      $strip.prop('hidden', false);
      $strip.append(
        $('<button type="button" class="ssr-chip amber" data-kind="discount"/>')
          .text('Discounted lines (this page) · ' + discounted)
      );
    } else {
      $strip.prop('hidden', true);
    }
  }

  function updateKpis() {
    var href = activeHref();
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var lines = info ? String(info.recordsDisplay) : '—';
    var pageHint = 'This page';

    if (href === '#psr_detailed_tab') {
      setKpi('a', true, 'Matching lines', lines, 'Filtered sell lines');
      setKpi('b', true, 'Quantity', footerText('#footer_total_sold'), pageHint);
      setKpi('c', true, 'Tax', footerText('#footer_tax'), pageHint);
      setKpi('d', true, 'Total', footerText('#footer_subtotal'), pageHint);
    } else if (href === '#psr_detailed_with_purchase_tab') {
      setKpi('a', true, 'Matching lines', lines, 'Sell lines with purchase');
      setKpi('b', false);
      setKpi('c', false);
      setKpi('d', false);
    } else if (href === '#psr_grouped_tab') {
      setKpi('a', true, 'Matching lines', lines, 'Grouped products');
      setKpi('b', true, 'Units sold', footerText('#footer_total_grouped_sold'), pageHint);
      setKpi('c', false);
      setKpi('d', true, 'Total', footerText('#footer_grouped_subtotal'), pageHint);
    } else if (href === '#psr_by_cat_tab') {
      setKpi('a', true, 'Matching lines', lines, 'Categories');
      setKpi('b', true, 'Units sold', footerText('#footer_psr_by_cat_total_sold'), pageHint);
      setKpi('c', true, 'Current stock', footerText('#footer_psr_by_cat_total_stock'), pageHint);
      setKpi('d', true, 'Total', footerText('#footer_psr_by_cat_total_sell'), pageHint);
    } else if (href === '#psr_by_brand_tab') {
      setKpi('a', true, 'Matching lines', lines, 'Brands');
      setKpi('b', true, 'Units sold', footerText('#footer_psr_by_brand_total_sold'), pageHint);
      setKpi('c', false);
      setKpi('d', true, 'Total', footerText('#footer_psr_by_brand_total_sell'), pageHint);
    }
  }

  function updateFromTable() {
    updateKpis();
    decorateRows();
    syncMeta();
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var empty = info && info.recordsDisplay === 0;
    $('#ssr_empty').prop('hidden', !empty);
  }

  function showError(show) {
    $('#ssr_error').prop('hidden', !show);
  }

  ready(function () {
    initialStartTime = $('#product_sr_start_time').val() || '';
    initialEndTime = $('#product_sr_end_time').val() || '';

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#product_sell_report_form').on('submit', function (e) {
      e.preventDefault();
      reloadAll();
    });

    $('#ssr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('ssr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#ssr_settings_toggle').on('click', function () {
      var panel = document.getElementById('ssr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#ssr_set_hide_kpis, #ssr_set_compact').on('change', saveSettings);

    $('#ssr_fullscreen').on('click', function () {
      var el = document.getElementById('ssr_shell');
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
      var icon = document.querySelector('#ssr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#ssr_refresh, #ssr_apply_filters, #ssr_retry').on('click', function () {
      showError(false);
      reloadAll();
    });
    $('#ssr_reset_filters, #ssr_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });
    $('#ssr_focus_search').on('click', function () {
      var input = document.getElementById('search_product');
      if (input) input.focus();
    });

    $('#ssr_print, #ssr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#ssr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('ssr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () { closeExport(); });
    $('#ssr_export_menu').on('click', function (e) { e.stopPropagation(); });
    $('#ssr_export_excel, #ssr_export_excel_alt, #ssr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#ssr_export_csv, #ssr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#ssr_export_pdf, #ssr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#ssr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#ssr_quick_search').on('input', function () {
      var q = this.value;
      $('#ssr_search_clear').prop('hidden', q.length === 0);
      $('#ssr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        eachMainTable(function (dt) {
          try { dt.search(q).draw(); } catch (e) {}
        });
        $('#ssr_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#ssr_search_clear').on('click', function () {
      $('#ssr_quick_search').val('');
      $(this).prop('hidden', true);
      eachMainTable(function (dt) {
        try { dt.search('').draw(); } catch (e) {}
      });
    });

    $('#ssr_filters_toggle').on('click', function () {
      var $body = $('#ssr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#ssr_period_chips').on('click', '.ssr-chip', function () {
      applyPeriod($(this).attr('data-period'));
    });

    $(document).on(
      'processing.dt',
      '#product_sell_report_table, #product_sell_report_with_purchase_table, #product_sell_grouped_report_table, #product_sell_report_by_category, #product_sell_report_by_brand',
      function (e, settings, processing) {
        $('#ssr_table_loading').prop('hidden', !processing);
        $('#ssr_search_spinner').prop('hidden', !processing);
        $('.ssr-kpi').toggleClass('is-loading', !!processing);
      }
    );
    $(document).on(
      'draw.dt',
      '#product_sell_report_table, #product_sell_report_with_purchase_table, #product_sell_grouped_report_table, #product_sell_report_by_category, #product_sell_report_by_brand',
      function () {
        showError(false);
        $('.ssr-kpi').removeClass('is-loading');
        setTimeout(updateFromTable, 40);
      }
    );
    $(document).on(
      'error.dt',
      '#product_sell_report_table, #product_sell_report_with_purchase_table, #product_sell_grouped_report_table, #product_sell_report_by_category, #product_sell_report_by_brand',
      function () {
        showError(true);
        $('.ssr-kpi').removeClass('is-loading');
      }
    );

    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
      setTimeout(updateFromTable, 80);
      var dt = tableApi();
      if (dt && dt.columns) {
        try { dt.columns.adjust(); } catch (err) {}
      }
    });

    $(document).on(
      'change',
      '#product_sell_report_form select, #psr_filter_category_id, #psr_filter_brand_id, #psr_customer_group_id, #product_sr_date_filter, #search_product',
      function () {
        setTimeout(syncMeta, 40);
      }
    );

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('product-sell-report') !== -1 && xhr.status >= 400) showError(true);
      if (url.indexOf('product-sell-grouped') !== -1 && xhr.status >= 400) showError(true);
    });

    $(document).on('click', '.ssr-shell .tab-content table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .btn-modal, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      openDrawer($(this));
    });
    $(document).on('keydown', '.ssr-shell .tab-content table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#ssr_exception_strip .ssr-chip', function () {
      var $row = $('#product_sell_report_table tbody tr.ssr-row-discount').first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('ssr-row-active');
      }
    });

    $('#ssr_drawer_close, #ssr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDrawer();
        closeExport();
      }
    });

    setTimeout(updateFromTable, 700);
  });
})();
