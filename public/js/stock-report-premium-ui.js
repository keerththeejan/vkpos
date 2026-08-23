/**
 * Stock Report — premium UI helpers only.
 * Reuses #stock_report_filter_form, #stock_report_table, report.js,
 * get_stock_value(), and existing DataTables AJAX. No calculation changes.
 */
(function () {
  'use strict';

  if (!document.querySelector('.sr-shell')) return;

  var STORAGE_KEY = 'vkpos_sr_dark_mode';
  var SETTINGS_KEY = 'vkpos_sr_settings';
  var searchTimer = null;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('sr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'sr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function tableApi() {
    try {
      if (typeof stock_report_table !== 'undefined' && stock_report_table) {
        return stock_report_table;
      }
    } catch (e) {}
    return null;
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('sr-dark-mode', enabled);
    document.body.classList.toggle('sr-dark-mode', enabled);
    var shell = document.querySelector('.sr-shell');
    if (shell) shell.classList.toggle('sr-dark-mode', enabled);
    var icon = document.querySelector('#sr_dark_mode_toggle i');
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
    var shell = document.querySelector('.sr-shell');
    if (!shell) return;
    shell.classList.toggle('sr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('sr-hide-exceptions', !!(s && s.hideExceptions));
    shell.classList.toggle('sr-compact', !!(s && s.compact));
    var k = document.getElementById('sr_set_hide_kpis');
    var e = document.getElementById('sr_set_hide_exceptions');
    var c = document.getElementById('sr_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (e) e.checked = !!(s && s.hideExceptions);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('sr_set_hide_kpis') || {}).checked,
      hideExceptions: !!(document.getElementById('sr_set_hide_exceptions') || {}).checked,
      compact: !!(document.getElementById('sr_set_compact') || {}).checked,
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

  function syncMeta() {
    var loc = selectedText('#location_id');
    var cat = selectedText('#category_id');
    var brand = selectedText('#brand');
    var unit = selectedText('#unit');
    $('#sr_meta_location').text(loc);
    $('#sr_meta_category').text(cat);
    $('#sr_meta_brand').text(brand);
    $('#sr_meta_unit').text(unit);
    $('.sr-print-location').text(loc);
    $('.sr-print-category').text(cat);
    $('.sr-print-brand').text(brand);
  }

  function textFromHtml(html) {
    if (html === null || html === undefined || html === '') return '—';
    var t = $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
    return t || '—';
  }

  function stockQty(html) {
    var $wrap = $('<div>').html(html || '');
    var $el = $wrap.find('.current_stock');
    if (!$el.length) return null;
    var n = parseFloat($el.attr('data-orig-value'));
    return isNaN(n) ? null : n;
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function copyFooter() {
    var map = [
      ['.footer_total_stock', 'sr_sum_stock', 'sr_kpi_page_qty'],
      ['.footer_total_stock_price', 'sr_sum_pp'],
      ['.footer_stock_value_by_sale_price', 'sr_sum_sp'],
      ['.footer_potential_profit', 'sr_sum_profit'],
      ['.footer_total_sold', 'sr_sum_sold'],
      ['.footer_total_transfered', 'sr_sum_transfered'],
      ['.footer_total_adjusted', 'sr_sum_adjusted'],
    ];
    map.forEach(function (item) {
      var t = ($(item[0]).first().text() || '').trim() || '—';
      setText(item[1], t);
      if (item[2]) setText(item[2], t);
    });
  }

  function reloadReport() {
    var dt = tableApi();
    if (dt) dt.ajax.reload(null, false);
    if (typeof get_stock_value === 'function' && document.getElementById('closing_stock_by_pp')) {
      get_stock_value();
    }
  }

  function clickDtButton(cls) {
    var $btn = $('#stock_report_table_wrapper').find(cls).first();
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
    $('#stock_report_filter_form select').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) {
        $s.trigger('change.select2');
      }
    });
    var $mfg = $('#only_mfg_products');
    if ($mfg.length) {
      if ($.fn.iCheck) $mfg.iCheck('uncheck');
      else $mfg.prop('checked', false);
    }
    $('#sr_quick_search').val('');
    var dt = tableApi();
    if (dt) dt.search('');
    $('#location_id').trigger('change');
    syncMeta();
  }

  function closeExport() {
    var menu = document.getElementById('sr_export_menu');
    var btn = document.getElementById('sr_export_toggle');
    if (menu) menu.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function closeDrawer() {
    $('#sr_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#sr_drawer_backdrop').prop('hidden', true);
    $('#stock_report_table tbody tr').removeClass('sr-row-active');
  }

  function openDrawer($row) {
    var dt = tableApi();
    if (!dt || !$row || !$row.length) return;
    if ($row.find('td.dataTables_empty').length) return;
    var data = dt.row($row).data();
    if (!data) return;

    $('#stock_report_table tbody tr').removeClass('sr-row-active');
    $row.addClass('sr-row-active');

    var name = textFromHtml(data.product);
    $('#sr_drawer_title').text(name);
    $('#sr_drawer_sku').text(textFromHtml(data.sku));
    $('#sr_drawer_variation').text(textFromHtml(data.variation));
    $('#sr_drawer_category').text(textFromHtml(data.category_name));
    $('#sr_drawer_location').text(textFromHtml(data.location_name));
    $('#sr_drawer_stock').text(textFromHtml(data.stock));
    $('#sr_drawer_price').text(textFromHtml(data.unit_price));
    $('#sr_drawer_pp').text(textFromHtml(data.stock_price));
    $('#sr_drawer_sp').text(textFromHtml(data.stock_value_by_sale_price));
    $('#sr_drawer_profit').text(textFromHtml(data.potential_profit));
    $('#sr_drawer_sold').text(textFromHtml(data.total_sold));
    $('#sr_drawer_transfered').text(textFromHtml(data.total_transfered));
    $('#sr_drawer_adjusted').text(textFromHtml(data.total_adjusted));

    var qty = stockQty(data.stock);
    var statusHtml = '';
    if (qty === null) {
      statusHtml = '<span class="sr-badge sr-badge-out">NOT TRACKED</span>';
    } else if (qty < 0) {
      statusHtml = '<span class="sr-badge sr-badge-neg">NEGATIVE</span>';
    } else if (qty === 0) {
      statusHtml = '<span class="sr-badge sr-badge-out">OUT OF STOCK</span>';
    } else if ($row.hasClass('bg-danger') || $row.hasClass('sr-row-low')) {
      statusHtml = '<span class="sr-badge sr-badge-low">LOW STOCK</span>';
    }
    $('#sr_drawer_status').html(statusHtml);

    var href = $('<div>').html(data.action || '').find('a').attr('href');
    var $hist = $('#sr_drawer_history');
    if (href) {
      $hist.attr('href', href).prop('hidden', false);
    } else {
      $hist.prop('hidden', true);
    }

    $('#sr_drawer_backdrop').prop('hidden', false);
    $('#sr_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  function decorateRows() {
    var dt = tableApi();
    var low = 0;
    var out = 0;
    var neg = 0;
    var attention = [];

    $('#stock_report_table tbody tr').each(function () {
      var $tr = $(this);
      $tr.find('.sr-badge').remove();
      $tr.removeClass('sr-row-low sr-row-zero sr-row-negative');
      if ($tr.find('td.dataTables_empty').length) return;

      var data = dt ? dt.row($tr).data() : null;
      var qty = data ? stockQty(data.stock) : null;
      var $stockTd = $tr.find('.current_stock').closest('td');
      if (!$stockTd.length) $stockTd = $tr.children('td').eq(7);
      var name = data ? textFromHtml(data.product) : '';
      var sku = data ? textFromHtml(data.sku) : '';

      if (qty === null) return;

      if (qty < 0) {
        neg += 1;
        $tr.addClass('sr-row-negative');
        $stockTd.append('<span class="sr-badge sr-badge-neg">NEGATIVE</span>');
        attention.push({ name: name, sku: sku, label: 'Negative', qty: qty, $tr: $tr });
      } else if (qty === 0) {
        out += 1;
        $tr.addClass('sr-row-zero');
        $stockTd.append('<span class="sr-badge sr-badge-out">OUT OF STOCK</span>');
        attention.push({ name: name, sku: sku, label: 'Out of stock', qty: qty, $tr: $tr });
      } else if ($tr.hasClass('bg-danger')) {
        low += 1;
        $tr.addClass('sr-row-low');
        $stockTd.append('<span class="sr-badge sr-badge-low">LOW STOCK</span>');
        attention.push({ name: name, sku: sku, label: 'Low stock', qty: qty, $tr: $tr });
      }
    });

    var $strip = $('#sr_exception_strip').empty();
    if (low + out + neg === 0) {
      $strip.prop('hidden', true);
    } else {
      $strip.prop('hidden', false);
      if (low) {
        $strip.append(
          $('<button type="button" class="sr-chip amber" data-kind="low"/>')
            .text('Low stock (this page) · ' + low)
        );
      }
      if (out) {
        $strip.append(
          $('<button type="button" class="sr-chip gray" data-kind="zero"/>')
            .text('Out of stock (this page) · ' + out)
        );
      }
      if (neg) {
        $strip.append(
          $('<button type="button" class="sr-chip red" data-kind="neg"/>')
            .text('Negative stock (this page) · ' + neg)
        );
      }
    }

    var $card = $('#sr_attention_card');
    var $list = $('#sr_attention_list').empty();
    if (!attention.length) {
      $card.prop('hidden', true);
    } else {
      $card.prop('hidden', false);
      attention.forEach(function (item) {
        var $li = $('<li/>');
        var $btn = $('<button type="button"/>');
        $btn.append($('<strong/>').text(item.name + (item.sku && item.sku !== '—' ? ' · ' + item.sku : '')));
        $btn.append($('<div/>').css({ fontSize: '12px', color: 'var(--sr-muted)', fontWeight: '500' }).text(item.label));
        $btn.on('click', function () {
          openDrawer(item.$tr);
        });
        $li.append($btn);
        $li.append($('<span class="sr-badge"/>').addClass(
          item.label === 'Negative' ? 'sr-badge-neg' : item.label === 'Out of stock' ? 'sr-badge-out' : 'sr-badge-low'
        ).text(String(item.qty)));
        $list.append($li);
      });
    }
  }

  function updateFromTable() {
    var dt = tableApi();
    var info = dt ? dt.page.info() : null;
    var lines = info ? info.recordsDisplay : 0;
    setText('sr_kpi_lines', String(lines));
    copyFooter();
    decorateRows();
    syncMeta();

    var $card = $('.sr-table-card');
    var empty = info && info.recordsDisplay === 0;
    $('#sr_empty').prop('hidden', !empty);
    $card.toggleClass('is-empty', !!empty);
  }

  function showError(show) {
    $('#sr_error').prop('hidden', !show);
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#stock_report_filter_form').on('submit', function (e) {
      e.preventDefault();
      reloadReport();
    });

    $('#sr_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('sr-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#sr_settings_toggle').on('click', function () {
      var panel = document.getElementById('sr_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#sr_set_hide_kpis, #sr_set_hide_exceptions, #sr_set_compact').on('change', saveSettings);

    $('#sr_fullscreen').on('click', function () {
      var el = document.getElementById('sr_shell');
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
      var icon = document.querySelector('#sr_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#sr_refresh, #sr_apply_filters, #sr_retry').on('click', function () {
      showError(false);
      reloadReport();
    });

    $('#sr_reset_filters, #sr_empty_clear').on('click', function () {
      showError(false);
      resetFilters();
    });

    $('#sr_save_filter').on('click', function () {
      toast('info', 'Save Filter is a placeholder — no backend change.');
    });

    $('#sr_focus_search').on('click', function () {
      var input = document.getElementById('sr_quick_search');
      if (input) input.focus();
    });

    $('#sr_print, #sr_print_foot').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });

    $('#sr_export_toggle').on('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('sr_export_menu');
      if (!menu) return;
      var open = menu.hidden;
      menu.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () {
      closeExport();
    });
    $('#sr_export_menu').on('click', function (e) {
      e.stopPropagation();
    });

    $('#sr_export_excel, #sr_export_excel_alt, #sr_export_excel_foot').on('click', function () {
      closeExport();
      exportKind('excel');
    });
    $('#sr_export_csv, #sr_export_csv_foot').on('click', function () {
      closeExport();
      exportKind('csv');
    });
    $('#sr_export_pdf, #sr_export_pdf_foot').on('click', function () {
      closeExport();
      exportKind('pdf');
    });
    $('#sr_colvis').on('click', function () {
      closeExport();
      exportKind('colvis');
    });

    $('#sr_quick_search').on('input', function () {
      var q = this.value;
      $('#sr_search_clear').prop('hidden', q.length === 0);
      $('#sr_search_spinner').prop('hidden', false);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var dt = tableApi();
        if (dt) dt.search(q).draw();
        $('#sr_search_spinner').prop('hidden', true);
      }, 300);
    });
    $('#sr_search_clear').on('click', function () {
      $('#sr_quick_search').val('');
      $(this).prop('hidden', true);
      var dt = tableApi();
      if (dt) dt.search('').draw();
    });

    $('#sr_filters_toggle').on('click', function () {
      var $body = $('#sr_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#stock_report_table')
      .on('processing.dt', function (e, settings, processing) {
        $('#sr_table_loading').prop('hidden', !processing);
        $('#sr_search_spinner').prop('hidden', !processing);
      })
      .on('draw.dt', function () {
        showError(false);
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
      });

    $(document).on('change', '#stock_report_filter_form select', function () {
      setTimeout(syncMeta, 40);
    });

    $(document).ajaxSend(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('get-stock-value') !== -1) {
        $('.sr-kpi.tone-green, .sr-kpi.tone-teal, .sr-kpi.tone-violet, .sr-kpi.tone-orange').addClass('is-loading');
      }
    });
    $(document).ajaxComplete(function (e, xhr, settings) {
      var url = settings.url || '';
      if (url.indexOf('get-stock-value') !== -1) {
        $('.sr-kpi').removeClass('is-loading');
        if (xhr.status >= 400) showError(true);
      }
      if (url.indexOf('stock-report') !== -1 && xhr.status >= 400) {
        showError(true);
      }
    });

    $(document).on('click', '#stock_report_table tbody tr', function (e) {
      if ($(e.target).closest('a, button, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      openDrawer($(this));
    });

    $(document).on('click', '#sr_exception_strip .sr-chip', function () {
      var kind = $(this).data('kind');
      var cls = kind === 'neg' ? '.sr-row-negative' : kind === 'zero' ? '.sr-row-zero' : '.sr-row-low';
      var $row = $('#stock_report_table tbody tr' + cls).first();
      if ($row.length) {
        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('sr-row-active');
      }
    });

    $('#sr_drawer_close, #sr_drawer_backdrop').on('click', closeDrawer);
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
