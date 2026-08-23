/**
 * Trending Products — premium UI helpers only.
 * Reuses #trending_product_date_range, #category_id, filter GET form,
 * report.js daterangepicker, and the existing Highcharts chart.
 */
(function () {
  'use strict';

  if (!document.querySelector('.tp-shell')) return;

  var STORAGE_KEY = 'vkpos_tp_dark_mode';
  var SETTINGS_KEY = 'vkpos_tp_settings';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(message) {
    var host = document.getElementById('tp_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'tp-toast info';
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 2800);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('tp-dark-mode', enabled);
    document.body.classList.toggle('tp-dark-mode', enabled);
    var shell = document.querySelector('.tp-shell');
    if (shell) shell.classList.toggle('tp-dark-mode', enabled);
    var icon = document.querySelector('#tp_dark_mode_toggle i');
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
    var shell = document.querySelector('.tp-shell');
    if (!shell) return;
    shell.classList.toggle('tp-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('tp-hide-chart', !!(s && s.hideChart));
    shell.classList.toggle('tp-compact', !!(s && s.compact));
    var k = document.getElementById('tp_set_hide_kpis');
    var c = document.getElementById('tp_set_hide_chart');
    var p = document.getElementById('tp_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.hideChart);
    if (p) p.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('tp_set_hide_kpis') || {}).checked,
      hideChart: !!(document.getElementById('tp_set_hide_chart') || {}).checked,
      compact: !!(document.getElementById('tp_set_compact') || {}).checked,
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

  function syncMeta() {
    var loc = selectedText('#location_id');
    var range = ($('#trending_product_date_range').val() || '').trim() || '—';
    $('#tp_meta_location').text(loc);
    $('#tp_meta_range').text(range);
    $('.tp-print-location').text(loc);
    $('.tp-print-range').text(range);
  }

  function submitForm() {
    var form = document.getElementById('trending_products_filter_form');
    if (form) form.submit();
  }

  function resetFilters() {
    $('#trending_products_filter_form select').each(function () {
      var $s = $(this);
      var empty = $s.find('option[value=""]').length ? '' : $s.find('option:first').val();
      $s.val(empty);
      if ($s.hasClass('select2-hidden-accessible')) $s.trigger('change.select2');
    });
    $('#trending_product_date_range').val('');
    $('#limit').val(5);
    submitForm();
  }

  function applyPeriod(code) {
    if (code === 'custom') {
      $('#trending_product_date_range').trigger('click').focus();
      return;
    }
    if (typeof ranges === 'undefined' || typeof LANG === 'undefined') {
      toast('Date ranges are not available.');
      return;
    }
    var keyMap = {
      today: LANG.today,
      yesterday: LANG.yesterday,
      last_7_days: LANG.last_7_days,
      last_30_days: LANG.last_30_days,
      this_month: LANG.this_month,
      this_year: LANG.this_year,
    };
    var label = keyMap[code];
    var span = label ? ranges[label] : null;
    if (!span || !span[0] || !span[1] || typeof moment_date_format === 'undefined') {
      toast('That period is not available.');
      return;
    }
    var text = span[0].format(moment_date_format) + ' ~ ' + span[1].format(moment_date_format);
    $('#trending_product_date_range').val(text);
    var picker = $('#trending_product_date_range').data('daterangepicker');
    if (picker) {
      picker.setStartDate(span[0]);
      picker.setEndDate(span[1]);
    }
    submitForm();
  }

  function filterRows(q) {
    q = (q || '').toLowerCase().trim();
    var visible = 0;
    $('#trending_products_table tbody tr').each(function () {
      var hay = ($(this).attr('data-search') || $(this).text()).toLowerCase();
      var show = !q || hay.indexOf(q) !== -1;
      $(this).toggle(show);
      if (show) visible += 1;
    });
    $('#tp_search_clear').prop('hidden', q.length === 0);
    var $empty = $('#tp_empty_search');
    if ($empty.length) $empty.prop('hidden', visible !== 0 || !$('#trending_products_table').length);
  }

  function closeDrawer() {
    $('#tp_drawer').removeClass('open').attr('aria-hidden', 'true');
    $('#tp_drawer_backdrop').prop('hidden', true);
    $('#trending_products_table tbody tr').removeClass('tp-row-active');
  }

  function openDrawer($row) {
    if (!$row || !$row.length) return;
    $('#trending_products_table tbody tr').removeClass('tp-row-active');
    $row.addClass('tp-row-active');
    $('#tp_drawer_title').text($row.data('product') || '—');
    $('#tp_drawer_sku, #tp_drawer_sku2').text($row.data('sku') || '—');
    $('#tp_drawer_unit').text($row.data('unit') || '—');
    $('#tp_drawer_sold').text($row.data('sold') || '—');
    $('#tp_drawer_rank').text('#' + ($row.data('rank') || '—'));
    var rank = parseInt($row.data('rank'), 10) || 0;
    $('#tp_drawer_status').html(
      '<span class="tp-rank ' + (rank <= 3 ? 'top-' + rank : '') + '">#' + rank + '</span>'
    );
    $('#tp_drawer_backdrop').prop('hidden', false);
    $('#tp_drawer').addClass('open').attr('aria-hidden', 'false');
  }

  ready(function () {
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    syncMeta();

    $('#tp_dark_mode_toggle').on('click', function () {
      var next = !document.body.classList.contains('tp-dark-mode');
      applyDark(next);
      try {
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
      } catch (e) {}
    });

    $('#tp_settings_toggle').on('click', function () {
      var panel = document.getElementById('tp_settings_panel');
      if (!panel) return;
      var open = panel.hidden;
      panel.hidden = !open;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $('#tp_set_hide_kpis, #tp_set_hide_chart, #tp_set_compact').on('change', saveSettings);

    $('#tp_fullscreen').on('click', function () {
      var el = document.getElementById('tp_shell');
      if (!el) return;
      if (!document.fullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
      } else if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    });
    document.addEventListener('fullscreenchange', function () {
      var icon = document.querySelector('#tp_fullscreen i');
      if (icon) icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    $('#tp_refresh').on('click', function () {
      submitForm();
    });
    $('#tp_reset_filters, #tp_empty_clear').on('click', resetFilters);
    $('#tp_print, #tp_print_foot').on('click', function () {
      window.print();
    });
    $('#tp_focus_search').on('click', function () {
      var el = document.getElementById('tp_quick_search');
      if (el) el.focus();
    });

    $('#tp_filters_toggle').on('click', function () {
      var $body = $('#tp_filters_body');
      var open = $body.is(':visible');
      $body.slideToggle(160);
      $(this).attr('aria-expanded', open ? 'false' : 'true');
      $(this).html(open
        ? '<i class="bi bi-chevron-down"></i> Filters'
        : '<i class="bi bi-chevron-up"></i> Hide');
    });

    $('#tp_period_chips .tp-chip').on('click', function () {
      $('#tp_period_chips .tp-chip').removeClass('is-active');
      $(this).addClass('is-active');
      applyPeriod($(this).data('range'));
    });

    var searchTimer = null;
    $('#tp_quick_search').on('input', function () {
      var q = this.value;
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        filterRows(q);
      }, 200);
    });
    $('#tp_search_clear').on('click', function () {
      $('#tp_quick_search').val('');
      filterRows('');
    });

    $(document).on('change', '#location_id, #trending_product_date_range', syncMeta);
    $('#trending_product_date_range').on('apply.daterangepicker cancel.daterangepicker', function () {
      setTimeout(syncMeta, 30);
    });

    $(document).on('click', '#trending_products_table tbody tr', function () {
      openDrawer($(this));
    });
    $('#tp_drawer_close, #tp_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });
  });
})();
