/**
 * Customer Group Report — premium UI helpers only.
 * Reuses #cg_report_table, cg_report_table, #cg_location_id,
 * #cg_customer_group_id, #cg_date_range (original inline DataTable).
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_cgr_dark_mode';
  var FILTER_KEY = 'vkpos_cgr_saved_filters';
  var SETTINGS_KEY = 'vkpos_cgr_settings';
  var charts = { bar: null, doughnut: null, hbar: null };
  var chartJsLoading = false;
  var chartJsReady = typeof window.Chart !== 'undefined';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('cgr_toast_host');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'cgr-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('cgr-dark-mode', enabled);
    document.body.classList.toggle('cgr-dark-mode', enabled);
    var icon = document.querySelector('#cgr_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  function applySettings(s) {
    var shell = document.querySelector('.cgr-shell');
    if (!shell) return;
    shell.classList.toggle('cgr-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('cgr-hide-charts', !!(s && s.hideCharts));
    shell.classList.toggle('cgr-hide-future', !!(s && s.hideFuture));
    var k = document.getElementById('cgr_set_hide_kpis');
    var c = document.getElementById('cgr_set_hide_charts');
    var f = document.getElementById('cgr_set_hide_future');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.hideCharts);
    if (f) f.checked = !!(s && s.hideFuture);
  }

  function loadSettings() {
    try {
      return JSON.parse(localStorage.getItem(SETTINGS_KEY) || '{}') || {};
    } catch (e) {
      return {};
    }
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('cgr_set_hide_kpis') || {}).checked,
      hideCharts: !!(document.getElementById('cgr_set_hide_charts') || {}).checked,
      hideFuture: !!(document.getElementById('cgr_set_hide_future') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (e) {}
    applySettings(s);
  }

  function money(n) {
    if (typeof n !== 'number' || isNaN(n)) return '—';
    try {
      if (typeof __currency_trans_from_en === 'function') {
        return __currency_trans_from_en(n, true);
      }
    } catch (e) {}
    return n.toLocaleString();
  }

  function parseSellCell($td) {
    var $cur = $td.find('.display_currency');
    var raw = $cur.data('orig-value');
    if (raw === undefined || raw === null || raw === '') {
      raw = ($cur.text() || $td.text() || '').replace(/[^\d.\-]/g, '');
    }
    var n = parseFloat(raw);
    return isNaN(n) ? 0 : n;
  }

  function pageRows() {
    var rows = [];
    $('#cg_report_table tbody tr').each(function () {
      var $tr = $(this);
      if ($tr.find('td.dataTables_empty').length) return;
      var $tds = $tr.find('td');
      if ($tds.length < 2) return;
      var name = ($tds.eq(0).text() || '').trim() || '—';
      var sell = parseSellCell($tds.eq(1));
      rows.push({
        name: name,
        sell: sell,
        sellText: ($tds.eq(1).text() || '').trim(),
      });
    });
    return rows;
  }

  function infoSafe() {
    try {
      if (typeof cg_report_table !== 'undefined' && cg_report_table) {
        return cg_report_table.page.info();
      }
    } catch (e) {}
    return null;
  }

  function syncMeta() {
    var loc = ($('#cg_location_id option:selected').text() || '').trim() || '—';
    var grp = ($('#cg_customer_group_id option:selected').text() || '').trim() || '—';
    var range = ($('#cg_date_range').val() || '').trim() || '—';
    $('#cgr_meta_location').text(loc);
    $('#cgr_meta_group').text(grp);
    $('#cgr_meta_range').text(range);
    $('#cgr_filter_location_label').text(loc);
  }

  function fillDash(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function updateFromTable() {
    var rows = pageRows();
    var info = infoSafe();
    var groupsFiltered = info ? info.recordsDisplay : rows.length;
    var groupsTotal = info ? info.recordsTotal : rows.length;
    var pageCount = rows.length;
    var sum = 0;
    var top = null;
    for (var i = 0; i < rows.length; i++) {
      sum += rows[i].sell;
      if (!top || rows[i].sell > top.sell) top = rows[i];
    }
    var avg = pageCount ? sum / pageCount : 0;
    var master = parseInt($('.cgr-shell').attr('data-group-master') || '0', 10) || 0;
    var locs = parseInt($('.cgr-shell').attr('data-location-count') || '0', 10) || 0;

    fillDash('cgr_kpi_groups', String(groupsFiltered));
    fillDash('cgr_kpi_groups_master', String(master || '—'));
    fillDash('cgr_kpi_revenue', money(sum));
    fillDash('cgr_kpi_avg_group', money(avg));
    fillDash('cgr_kpi_top', top ? top.name : '—');
    fillDash('cgr_kpi_sales', money(sum));
    fillDash('cgr_kpi_locations', String(locs || '—'));
    fillDash('cgr_kpi_page_groups', String(pageCount));

    fillDash('cgr_sum_groups', String(groupsFiltered));
    fillDash('cgr_sum_groups_total', String(groupsTotal));
    fillDash('cgr_sum_revenue', money(sum));
    fillDash('cgr_sum_avg', money(avg));
    fillDash('cgr_sum_top', top ? top.name : '—');
    fillDash('cgr_sum_page', String(pageCount));
    fillDash('cgr_sum_master', String(master || '—'));
    fillDash('cgr_sum_locations', String(locs || '—'));

    fillDash('cgr_cmp_groups', String(groupsFiltered));
    fillDash('cgr_cmp_sales', money(sum));
    fillDash('cgr_cmp_avg', money(avg));
    fillDash('cgr_cmp_location', ($('#cg_location_id option:selected').text() || '').trim() || '—');

    renderRankList(rows);
    updateCharts(rows);
    syncMeta();
  }

  function renderRankList(rows) {
    var sorted = rows.slice().sort(function (a, b) {
      return b.sell - a.sell;
    }).slice(0, 8);
    var html = '';
    if (!sorted.length) {
      html = '<li class="cgr-preview-empty">No group sales on this page.</li>';
    } else {
      sorted.forEach(function (r, i) {
        html +=
          '<li><span><span class="cgr-rank">' +
          (i + 1) +
          '</span><span class="cgr-rank-name">' +
          $('<div>').text(r.name).html() +
          '</span></span><span class="cgr-rank-val">' +
          money(r.sell) +
          '</span></li>';
      });
    }
    $('#cgr_top_groups_list, #cgr_high_revenue_list').html(html);
  }

  function ensureChartJs(cb) {
    if (chartJsReady) {
      cb();
      return;
    }
    if (chartJsLoading) return;
    chartJsLoading = true;
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    s.onload = function () {
      chartJsReady = true;
      cb();
    };
    s.onerror = function () {
      chartJsLoading = false;
    };
    document.head.appendChild(s);
  }

  function destroyChart(key) {
    if (charts[key]) {
      try {
        charts[key].destroy();
      } catch (e) {}
      charts[key] = null;
    }
  }

  function chartColors() {
    return ['#4F46E5', '#2563EB', '#10B981', '#F59E0B', '#EF4444', '#06B6D4', '#8B5CF6', '#64748B', '#EC4899', '#14B8A6'];
  }

  function updateCharts(rows) {
    var data = rows.slice().sort(function (a, b) {
      return b.sell - a.sell;
    }).slice(0, 10);
    if (!data.length) {
      destroyChart('bar');
      destroyChart('doughnut');
      destroyChart('hbar');
      return;
    }
    ensureChartJs(function () {
      if (typeof Chart === 'undefined') return;
      var labels = data.map(function (r) {
        return r.name;
      });
      var values = data.map(function (r) {
        return r.sell;
      });
      var colors = chartColors();
      var isDark = document.body.classList.contains('cgr-dark-mode');
      var tick = isDark ? '#E2E8F0' : '#334155';
      var grid = isDark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';

      function make(id, key, cfg) {
        var canvas = document.getElementById(id);
        if (!canvas) return;
        destroyChart(key);
        charts[key] = new Chart(canvas, cfg);
      }

      make('cgr_chart_sales_by_group', 'bar', {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{ label: 'Sales', data: values, backgroundColor: '#4F46E5', borderRadius: 8, maxBarThickness: 28 }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { color: tick, maxRotation: 45, minRotation: 0 }, grid: { display: false } },
            y: { ticks: { color: tick }, grid: { color: grid } },
          },
        },
      });

      make('cgr_chart_distribution', 'doughnut', {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { color: tick, boxWidth: 10 } } },
        },
      });

      make('cgr_chart_top10', 'hbar', {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{ label: 'Sales', data: values, backgroundColor: '#10B981', borderRadius: 8, maxBarThickness: 18 }],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { color: tick }, grid: { color: grid } },
            y: { ticks: { color: tick }, grid: { display: false } },
          },
        },
      });
    });
  }

  function reloadTable() {
    try {
      if (typeof cg_report_table !== 'undefined') cg_report_table.ajax.reload(null, false);
    } catch (e) {}
  }

  function clickDtButton(cls) {
    var $btn = $('#cg_report_table_wrapper').find(cls).first();
    if ($btn.length) {
      $btn.trigger('click');
      return true;
    }
    return false;
  }

  function openDrawer($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    if ($row.find('td.dataTables_empty').length) return;
    $('#cg_report_table tbody tr').removeClass('cgr-row-active');
    $row.addClass('cgr-row-active');
    var name = ($row.find('td').eq(0).text() || '').trim() || '—';
    var sellText = ($row.find('td').eq(1).text() || '').trim() || '—';
    $('#cgr_drawer_name, #cgr_drawer_name_dup').text(name);
    $('#cgr_drawer_sales').text(sellText);
    $('#cgr_drawer_location').text(($('#cg_location_id option:selected').text() || '').trim() || '—');
    $('#cgr_drawer_range').text(($('#cg_date_range').val() || '').trim() || '—');
    $('#cgr_drawer, #cgr_drawer_backdrop').addClass('open');
    $('#cgr_drawer').attr('aria-hidden', 'false');
  }

  function closeDrawer() {
    $('#cgr_drawer, #cgr_drawer_backdrop').removeClass('open');
    $('#cgr_drawer').attr('aria-hidden', 'true');
  }

  function downloadCharts() {
    var downloaded = 0;
    ['cgr_chart_sales_by_group', 'cgr_chart_distribution', 'cgr_chart_top10'].forEach(function (id) {
      var canvas = document.getElementById(id);
      if (!canvas) return;
      try {
        var a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = id + '.png';
        a.click();
        downloaded++;
      } catch (e) {}
    });
    if (downloaded) toast('success', 'Chart image(s) downloaded.');
    else toast('warning', 'No chart images available on this page.');
  }

  ready(function () {
    if (!$('.cgr-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());

    $('#cgr_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
      updateFromTable();
    });

    $('#cgr_print_page').on('click', function () {
      window.print();
    });

    $('#cgr_refresh, #cgr_generate, #cgr_apply, #cgr_generate_alt, #cgr_generate_footer, #cgr_refresh_alt').on('click', function () {
      reloadTable();
      toast('success', 'Report refreshed with current filters.');
    });

    $('#cgr_reset').on('click', function () {
      $('#cg_customer_group_id, #cg_location_id').val('').trigger('change');
      try {
        var $dr = $('#cg_date_range');
        if ($dr.data('daterangepicker')) {
          $dr.data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
          $dr.data('daterangepicker').setEndDate(moment());
          $dr.val(
            $dr.data('daterangepicker').startDate.format(moment_date_format) +
              ' ~ ' +
              $dr.data('daterangepicker').endDate.format(moment_date_format)
          );
        }
      } catch (e) {}
      reloadTable();
      toast('info', 'Filters reset.');
    });

    $('#cgr_save_filter').on('click', function () {
      var payload = {
        location: $('#cg_location_id').val(),
        group: $('#cg_customer_group_id').val(),
        range: $('#cg_date_range').val(),
      };
      try {
        localStorage.setItem(FILTER_KEY, JSON.stringify(payload));
        toast('success', 'Filter saved in this browser.');
      } catch (e) {
        toast('danger', 'Unable to save filter.');
      }
    });

    $('#cgr_export_excel, #cgr_export_excel_alt').on('click', function () {
      if (!clickDtButton('.buttons-excel, .buttons-excelHtml5')) {
        toast('warning', 'Excel export is not available for this user.');
      }
    });
    $('#cgr_export_pdf, #cgr_export_pdf_alt').on('click', function () {
      if (!clickDtButton('.buttons-pdf, .buttons-pdfHtml5')) {
        toast('warning', 'PDF export is not available for this user.');
      }
    });
    $('#cgr_export_csv').on('click', function () {
      if (!clickDtButton('.buttons-csv, .buttons-csvHtml5')) {
        toast('warning', 'CSV export is not available for this user.');
      }
    });
    $('#cgr_export_print').on('click', function () {
      if (!clickDtButton('.buttons-print')) window.print();
    });
    $('#cgr_download_charts').on('click', downloadCharts);

    $('#cgr_email_report, #cgr_email_report_alt, #cgr_share, #cgr_schedule').on('click', function () {
      toast('info', 'UI placeholder — no backend scheduler or email send.');
    });

    $('#cgr_open_groups').on('click', function () {
      window.location.href = $(this).data('url') || '/customer-group';
    });

    $('#cgr_settings_toggle').on('click', function () {
      $('#cgr_settings_panel').toggleClass('open');
    });
    $('#cgr_set_hide_kpis, #cgr_set_hide_charts, #cgr_set_hide_future').on('change', saveSettings);

    var searchTimer = null;
    $('#cgr_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof cg_report_table !== 'undefined') cg_report_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#cg_report_table').on('draw.dt', function () {
      setTimeout(updateFromTable, 80);
    });

    $(document).on('change', '#cg_location_id, #cg_customer_group_id, #cg_date_range', function () {
      setTimeout(syncMeta, 50);
    });

    $(document).on('click', '#cg_report_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      openDrawer($(this));
    });

    $('#cgr_drawer_close, #cgr_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });

    setTimeout(updateFromTable, 900);
    syncMeta();
  });
})();
