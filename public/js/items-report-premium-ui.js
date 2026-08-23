/**
 * Items Report — premium UI helpers only.
 * Reuses #items_report_table, #ir_* filters, report.js DataTables AJAX,
 * footer totals, Select2, daterangepicker, and export buttons.
 * No calculation or backend changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.irp-shell')) return;

  var STORAGE_KEY = 'vkpos_irp_dark_mode';
  var SETTINGS_KEY = 'vkpos_irp_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('irp_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'irp-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof items_report_table !== 'undefined' && items_report_table) {
        return items_report_table;
      }
    } catch (e) {}
    var $t = $('#items_report_table');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
      return $t.DataTable();
    }
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('irp-dark-mode', enabled);
    document.body.classList.toggle('irp-dark-mode', enabled);
    var shell = document.querySelector('.irp-shell');
    if (shell) shell.classList.toggle('irp-dark-mode', enabled);
    var icon = document.querySelector('#irp_dark_mode_toggle i');
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
    var shell = document.querySelector('.irp-shell');
    if (!shell) return;
    shell.classList.toggle('irp-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('irp-compact', !!(s && s.compact));
    var k = document.getElementById('irp_set_hide_kpis');
    var c = document.getElementById('irp_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('irp_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('irp_set_compact') || {}).checked,
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
    var loc = selectedText('#ir_location_id');
    var supplier = selectedText('#ir_supplier_id');
    var customer = selectedText('#ir_customer_id');
    var purchase = dateVal('#ir_purchase_date_filter');
    var sale = dateVal('#ir_sale_date_filter');
    $('#irp_meta_location').text(loc);
    $('#irp_meta_supplier').text(supplier);
    $('#irp_meta_customer').text(customer);
    $('#irp_meta_purchase').text(purchase);
    $('#irp_meta_sale').text(sale);
    $('.irp-print-location').text(loc);
    $('.irp-print-supplier').text(supplier);
    $('.irp-print-customer').text(customer);
    $('.irp-print-purchase').text(purchase);
    $('.irp-print-sale').text(sale);
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

  function copyFooter() {
    setText('irp_kpi_pp', ($('#footer_total_pp').text() || '').trim() || '—');
    setText('irp_kpi_qty', ($('#footer_total_qty').text() || '').trim() || '—');
    setText('irp_kpi_sp', ($('#footer_total_sp').text() || '').trim() || '—');
    setText('irp_kpi_subtotal', ($('#footer_total_subtotal').text() || '').trim() || '—');
  }

  function reloadReport() {
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
  }

  function clickDtButton(cls) {
    var $btn = $('#items_report_table_wrapper').find(cls).first();
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

  function resetFilters() {
    $('#ir_supplier_id, #ir_customer_id, #ir_location_id').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) {
        $s.trigger('change.select2');
      }
    });
    $('#ir_purchase_date_filter, #ir_sale_date_filter').val('');
    var $mfg = $('#only_mfg_products');
    if ($mfg.length) {
      if ($.fn.iCheck) $mfg.iCheck('uncheck');
      else $mfg.prop('checked', false);
    }
    $('#irp_quick_search').val('');
    $('#irp_search_clear').prop('hidden', true);
    var dt = tableApi();
    if (dt) dt.search('');
    reloadReport();
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('irp_export_menu');
    var btn = document.getElementById('irp_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#irp_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#irp_drawer_backdrop').prop('hidden', true);
    $('#items_report_table tbody tr').removeClass('irp-row-active');
  }

  function isAdjustmentRow(data) {
    if (!data) return false;
    if (Object.prototype.hasOwnProperty.call(data, 'sell_line_id')) {
      return !data.sell_line_id;
    }
    return /<small/i.test(data.sale_invoice_no || '') || /<small/i.test(data.quantity || '');
  }

  function hasReturnedQty(data) {
    if (!data) return false;
    if (Object.prototype.hasOwnProperty.call(data, 'qty_returned')) {
      var n = parseFloat(data.qty_returned);
      return !isNaN(n) && n > 0;
    }
    return textFromHtml(data.quantity).toLowerCase().indexOf('returned') !== -1;
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#items_report_table tbody tr').removeClass('irp-row-active');
    $row.addClass('irp-row-active');

    $('#irp_drawer_title').text(textFromHtml(data.product_name));
    $('#irp_drawer_sku').text(textFromHtml(data.sku));
    setHtml('irp_drawer_note', htmlOrDash(data.sell_line_note));
    setHtml('irp_drawer_pdate', htmlOrDash(data.purchase_date));
    setHtml('irp_drawer_pref', htmlOrDash(data.purchase_ref_no));
    setHtml('irp_drawer_lot', htmlOrDash(data.lot_number));
    setHtml('irp_drawer_supplier', htmlOrDash(data.supplier));
    setHtml('irp_drawer_pp', htmlOrDash(data.purchase_price));
    setHtml('irp_drawer_sdate', htmlOrDash(data.sell_date));
    setHtml('irp_drawer_sref', htmlOrDash(data.sale_invoice_no));
    setHtml('irp_drawer_customer', htmlOrDash(data.customer));
    setHtml('irp_drawer_location', htmlOrDash(data.location));
    setHtml('irp_drawer_qty', htmlOrDash(data.quantity));
    setHtml('irp_drawer_sp', htmlOrDash(data.selling_price));
    setHtml('irp_drawer_subtotal', htmlOrDash(data.subtotal));

    var adj = isAdjustmentRow(data);
    $('#irp_drawer_kind')
      .text(adj ? 'Stock adjustment' : 'Sale')
      .toggleClass('is-adj', adj)
      .prop('hidden', false);

    $('#irp_drawer_backdrop').prop('hidden', false);
    $('#irp_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var adj = 0;
    var returned = 0;

    $('#items_report_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.irp-badge').remove();
      $tr.removeClass('irp-row-adj irp-row-returned');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      if (!data) return;

      $tr.attr('tabindex', '0');

      var $nameTd = $tr.children('td').eq(0);
      if ($nameTd.length && !$nameTd.find('.irp-item-cell').length) {
        var nameHtml = $nameTd.html();
        var sku = textFromHtml(data.sku);
        $nameTd.html(
          '<div class="irp-item-cell"><span class="irp-item-name">' +
            nameHtml +
            '</span>' +
            (sku && sku !== '—' ? '<span class="irp-item-sku">' + $('<div>').text(sku).html() + '</span>' : '') +
            '</div>'
        );
      }

      if (isAdjustmentRow(data)) {
        adj += 1;
        $tr.addClass('irp-row-adj');
        var $saleTd = $tr.children('td').eq(9);
        if ($saleTd.length && !$saleTd.find('.irp-badge').length) {
          $saleTd.append('<span class="irp-badge irp-badge-out">ADJ</span>');
        }
      }
      if (hasReturnedQty(data)) {
        returned += 1;
        $tr.addClass('irp-row-returned');
      }
    });

    var $strip = $('#irp_exception_strip').empty();
    if (!adj && !returned) {
      $strip.prop('hidden', true);
    } else {
      $strip.prop('hidden', false);
      if (adj) {
        $strip.append(
          $('<button type="button" class="irp-chip gray" data-kind="adj"/>')
            .text('Stock adjustments (this page) · ' + adj)
        );
      }
      if (returned) {
        $strip.append(
          $('<button type="button" class="irp-chip amber" data-kind="returned"/>')
            .text('Returned qty (this page) · ' + returned)
        );
      }
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var lines = info ? info.recordsDisplay : 0;
    setText('irp_kpi_lines', String(lines));
    copyFooter();
    decorateRows();
    syncMeta();

    var empty = info && info.recordsDisplay === 0;
    $('#irp_empty').prop('hidden', !empty);
    $('.irp-table-card').toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#irp_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#irp_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('irp-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#irp_settings_toggle').on('click', function () {
      var panel = document.getElementById('irp_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#irp_set_hide_kpis, #irp_set_compact').on('change', saveSettings);

    $('#irp_fullscreen').on('click', function () {
      var el = document.getElementById('irp_shell');
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
      var icon = document.querySelector('#irp_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#irp_refresh, #irp_apply_filters, #irp_retry').on('click', function () {
      showError(false);
      reloadReport();
    });

    $('#irp_reset_filters, #irp_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });

    $('#irp_focus_search').on('click', function () {
      var input = document.getElementById('irp_quick_search');
      if (input) input.focus();
    });

    $('#irp_print, #irp_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#irp_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('irp_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () {
      closeExport();
    });
    $('#irp_export_menu').on('click', function (e) {
      e.stopPropagation();
    });

    $('#irp_export_excel, #irp_export_excel_alt, #irp_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#irp_export_csv, #irp_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#irp_export_pdf, #irp_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#irp_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#irp_quick_search').on('input', function () {
      var q = this.value;
      $('#irp_search_clear').prop('hidden', q.length === 0);
      $('#irp_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#irp_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#irp_search_clear').on('click', function () {
      $('#irp_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#irp_filters_toggle').on('click', function () {
      var $body = $('#irp_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#items_report_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#irp_table_loading').prop('hidden', !processing);
        $('#irp_search_spinner').prop('hidden', !processing);
        $('.irp-kpi').toggleClass('is-loading', !!processing);
      })
      .on('draw.dt', function () {
        showError(false);
        $('.irp-kpi').removeClass('is-loading');
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
        $('.irp-kpi').removeClass('is-loading');
      });

    $(document).on('change', '#ir_supplier_id, #ir_customer_id, #ir_location_id, #ir_purchase_date_filter, #ir_sale_date_filter', function () {
      setTimeout(syncMeta, 40);
    });

    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('items-report') !== -1 && xhr.status >= 400) {
        showError(true);
      }
    });

    $(document).on('click', '#items_report_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .btn-modal, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      openDrawer($(this));
    });
    $(document).on('keydown', '#items_report_table tbody tr', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        if ($(e.target).closest('a, button, input, select').length) return;
        e.preventDefault();
        openDrawer($(this));
      }
    });

    $(document).on('click', '#irp_exception_strip .irp-chip', function () {
      var kind = $(this).data('kind');
      var cls = kind === 'returned' ? '.irp-row-returned' : '.irp-row-adj';
      var $row = $('#items_report_table tbody tr' + cls).first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('irp-row-active');
      }
    });

    $('#irp_drawer_close, #irp_drawer_backdrop').on('click', closeDrawer);
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
