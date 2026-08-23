/**
 * Repair Orders — premium UI helpers only.
 * Reuses existing DataTables: pending_repair_table / sell_table
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_ro_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ro-dark-mode', enabled);
    document.body.classList.toggle('ro-dark-mode', enabled);
    var icon = document.querySelector('#ro_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function colMap($table) {
    // Detect technician column from header labels
    var tech = false;
    $table.find('thead th').each(function () {
      var t = ($(this).text() || '').toLowerCase();
      if (t.indexOf('technician') !== -1) tech = true;
    });

    // Without technician:
    // 0 action, 1 date, 2 due, 3 job, 4 invoice, 5 added_by, 6 customer,
    // 7 brand, 8 model, 9 serial, 10 status, 11 location, 12 warranty,
    // 13 payment, 14 total, 15 remaining
    if (tech) {
      return {
        action: 0, invoice: 4, tech: 5, customer: 7, brand: 8, model: 9,
        serial: 10, status: 11, warranty: 13, payment: 14, total: 15, due: 16
      };
    }
    return {
      action: 0, invoice: 4, tech: -1, customer: 6, brand: 7, model: 8,
      serial: 9, status: 10, warranty: 12, payment: 13, total: 14, due: 15
    };
  }

  function cellText($row, idx) {
    if (idx < 0) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  function updateKpis() {
    try {
      if (typeof pending_repair_table !== 'undefined' && pending_repair_table) {
        var p = pending_repair_table.page.info();
        $('#ro_kpi_pending').text(p.recordsTotal);
        $('#ro_kpi_filtered_pending').text(p.recordsDisplay);
      }
    } catch (e) {}
    try {
      if (typeof sell_table !== 'undefined' && sell_table) {
        var c = sell_table.page.info();
        $('#ro_kpi_completed').text(c.recordsTotal);
        $('#ro_kpi_filtered_completed').text(c.recordsDisplay);
      }
    } catch (e) {}

    $('#ro_fin_pending_total').text($('#pending_repair_footer_total').text() || '—');
    $('#ro_fin_pending_due').text($('#pending_repair_footer_total_remaining').text() || '—');
    $('#ro_fin_completed_total').text($('#footer_sale_total').text() || '—');
    $('#ro_fin_completed_due').text($('#footer_total_remaining').text() || '—');
  }

  function showPreview($row, $table) {
    if (!$row || !$row.length) return;
    $('#pending_repair_table tbody tr, #sell_table tbody tr').removeClass('ro-row-active');
    $row.addClass('ro-row-active');

    var m = colMap($table);
    var device = [cellText($row, m.brand), cellText($row, m.model)]
      .filter(function (x) { return x && x !== '—'; })
      .join(' · ') || '—';

    $('#ro_preview_empty').hide();
    $('#ro_preview_content').show();
    $('#ro_preview_invoice').text(cellText($row, m.invoice));
    $('#ro_preview_status').text(cellText($row, m.status));
    $('#ro_preview_customer').text(cellText($row, m.customer));
    $('#ro_preview_device').text(device);
    $('#ro_preview_serial').text(cellText($row, m.serial));
    $('#ro_preview_tech').text(cellText($row, m.tech));
    $('#ro_preview_warranty').text(cellText($row, m.warranty));
    $('#ro_preview_payment').text(cellText($row, m.payment));
    $('#ro_preview_total').text(cellText($row, m.total));
    $('#ro_preview_due').text(cellText($row, m.due));

    var $actions = $row.find('td').eq(m.action).clone(true, true);
    $('#ro_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.ro-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#ro_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#ro_print_page').on('click', function () {
      window.print();
    });

    $('#ro_refresh_tables').on('click', function () {
      try {
        if (typeof pending_repair_table !== 'undefined') pending_repair_table.ajax.reload(null, false);
        if (typeof sell_table !== 'undefined') sell_table.ajax.reload(null, false);
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#ro_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof pending_repair_table !== 'undefined') pending_repair_table.search(q).draw();
          if (typeof sell_table !== 'undefined') sell_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#pending_repair_table, #sell_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#pending_repair_table tbody tr, #sell_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .edit_repair_status').length) return;
      var $table = $(this).closest('table');
      showPreview($(this), $table);
    });
  });
})();
