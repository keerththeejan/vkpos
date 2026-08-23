/**
 * Job Sheets — premium UI helpers only.
 * Reuses existing DataTables: pending_job_sheets_datatable / completed_job_sheets_datatable
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_js_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('js-dark-mode', enabled);
    document.body.classList.toggle('js-dark-mode', enabled);
    var icon = document.querySelector('#js_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function hasServiceStaffCol() {
    return $('#pending_job_sheets_table thead th').length > 15 ||
      ($('#pending_job_sheets_table thead th').filter(function () {
        return $(this).text().toLowerCase().indexOf('technician') !== -1 ||
          $(this).text().toLowerCase().indexOf('tech') !== -1;
      }).length > 0);
  }

  function colMap() {
    // Base columns without technician:
    // 0 action, 1 service, 2 due, 3 job_no, 4 invoice, 5 status,
    // 6 customer, 7 location, 8 brand, 9 device, 10 model, 11 serial, 12 cost
    var tech = false;
    try {
      var $ths = $('#pending_job_sheets_table thead th');
      $ths.each(function (i) {
        var t = ($(this).text() || '').toLowerCase();
        if (t.indexOf('technician') !== -1 || t.indexOf('technecian') !== -1) tech = true;
      });
    } catch (e) {}

    if (tech) {
      return {
        action: 0, due: 2, jobNo: 3, status: 5, tech: 6,
        customer: 7, brand: 9, device: 10, model: 11, serial: 12, cost: 13
      };
    }
    return {
      action: 0, due: 2, jobNo: 3, status: 5, tech: -1,
      customer: 6, brand: 8, device: 9, model: 10, serial: 11, cost: 12
    };
  }

  function updateKpis() {
    try {
      if (typeof pending_job_sheets_datatable !== 'undefined' && pending_job_sheets_datatable) {
        var p = pending_job_sheets_datatable.page.info();
        $('#js_kpi_pending').text(p.recordsTotal);
        $('#js_kpi_filtered_pending').text(p.recordsDisplay);
      }
    } catch (e) {}
    try {
      if (typeof completed_job_sheets_datatable !== 'undefined' && completed_job_sheets_datatable) {
        var c = completed_job_sheets_datatable.page.info();
        $('#js_kpi_completed').text(c.recordsTotal);
        $('#js_kpi_filtered_completed').text(c.recordsDisplay);
      }
    } catch (e) {}
  }

  function cellText($row, idx) {
    if (idx < 0) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#pending_job_sheets_table tbody tr, #completed_job_sheets_table tbody tr').removeClass('js-row-active');
    $row.addClass('js-row-active');

    var m = colMap();
    var device = [cellText($row, m.brand), cellText($row, m.device), cellText($row, m.model)]
      .filter(function (x) { return x && x !== '—'; })
      .join(' · ') || '—';

    $('#js_preview_empty').hide();
    $('#js_preview_content').show();
    $('#js_preview_no').text(cellText($row, m.jobNo));
    $('#js_preview_status').text(cellText($row, m.status));
    $('#js_preview_customer').text(cellText($row, m.customer));
    $('#js_preview_device').text(device);
    $('#js_preview_serial').text(cellText($row, m.serial));
    $('#js_preview_tech').text(cellText($row, m.tech));
    $('#js_preview_due').text(cellText($row, m.due));
    $('#js_preview_cost').text(cellText($row, m.cost));

    var $actions = $row.find('td').eq(m.action).clone(true, true);
    $('#js_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.js-shell').length && !$('.js-form-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#js_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#js_print_page').on('click', function () {
      window.print();
    });

    $('#js_refresh_tables').on('click', function () {
      try {
        if (typeof pending_job_sheets_datatable !== 'undefined') pending_job_sheets_datatable.ajax.reload(null, false);
        if (typeof completed_job_sheets_datatable !== 'undefined') completed_job_sheets_datatable.ajax.reload(null, false);
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#js_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof pending_job_sheets_datatable !== 'undefined') pending_job_sheets_datatable.search(q).draw();
          if (typeof completed_job_sheets_datatable !== 'undefined') completed_job_sheets_datatable.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#pending_job_sheets_table, #completed_job_sheets_table').on('draw.dt', function () {
      updateKpis();
    });

    setTimeout(updateKpis, 800);

    $(document).on('click', '#pending_job_sheets_table tbody tr, #completed_job_sheets_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select').length) return;
      showPreview($(this));
    });

    // Form page: sticky header save triggers existing submit buttons
    $('#js_header_save').on('click', function (e) {
      e.preventDefault();
      var $btn = $('#save');
      if ($btn.length) $btn.trigger('click');
    });

    $('#js_header_save_parts').on('click', function (e) {
      e.preventDefault();
      var $btn = $('#save_and_add_parts');
      if ($btn.length) $btn.trigger('click');
    });
  });
})();
