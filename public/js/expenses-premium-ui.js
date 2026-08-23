/**
 * Expenses — premium UI helpers only.
 * Reuses existing #expense_table + expense_table (app.js) + payment.js.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_ex_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ex-dark-mode', enabled);
    document.body.classList.toggle('ex-dark-mode', enabled);
    var icon = document.querySelector('#ex_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from app.js expense_table:
  // 0 action, 1 date, 2 ref, 3 recur, 4 category, 5 sub, 6 location,
  // 7 payment_status, 8 tax, 9 total, 10 due, 11 expense_for, 12 contact, 13 notes, 14 added_by
  var COL = {
    action: 0,
    date: 1,
    ref: 2,
    recur: 3,
    category: 4,
    sub: 5,
    location: 6,
    status: 7,
    tax: 8,
    total: 9,
    due: 10,
    expense_for: 11,
    contact: 12,
    notes: 13,
    added_by: 14,
  };

  function updateKpis() {
    try {
      if (typeof expense_table !== 'undefined' && expense_table) {
        var info = expense_table.page.info();
        $('#ex_kpi_total').text(info.recordsTotal);
        $('#ex_kpi_filtered').text(info.recordsDisplay);
        $('#ex_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var paid = 0;
    var due = 0;
    var partial = 0;
    $('#expense_table tbody tr').each(function () {
      if ($(this).hasClass('child')) return;
      var st = cellText($(this), COL.status).toLowerCase();
      if (st.indexOf('paid') !== -1 && st.indexOf('partial') === -1) paid += 1;
      else if (st.indexOf('partial') !== -1) partial += 1;
      else if (st.indexOf('due') !== -1) due += 1;
    });
    $('#ex_kpi_paid').text(paid);
    $('#ex_kpi_due').text(due);
    $('#ex_kpi_partial').text(partial);
    $('#ex_sum_paid').text(paid);
    $('#ex_sum_due').text(due);

    var footerTotal = ($('.footer_expense_total').first().text() || '').trim() || '—';
    var footerDue = ($('.footer_total_due').first().text() || '').trim() || '—';
    $('#ex_kpi_amount').text(footerTotal);
    $('#ex_sum_amount').text(footerTotal);
    $('#ex_sum_due_amt').text(footerDue);
  }

  function showPreview($row) {
    if (!$row || !$row.length || $row.hasClass('child')) return;
    $('#expense_table tbody tr').removeClass('ex-row-active');
    $row.addClass('ex-row-active');

    $('#ex_preview_empty').hide();
    $('#ex_preview_content').show();
    $('#ex_preview_ref').text(cellText($row, COL.ref));
    $('#ex_preview_date').text(cellText($row, COL.date));
    $('#ex_preview_category').text(cellText($row, COL.category));
    $('#ex_preview_sub').text(cellText($row, COL.sub));
    $('#ex_preview_location').text(cellText($row, COL.location));
    $('#ex_preview_status').text(cellText($row, COL.status));
    $('#ex_preview_tax').text(cellText($row, COL.tax));
    $('#ex_preview_total').text(cellText($row, COL.total));
    $('#ex_preview_due').text(cellText($row, COL.due));
    $('#ex_preview_for').text(cellText($row, COL.expense_for));
    $('#ex_preview_contact').text(cellText($row, COL.contact));
    $('#ex_preview_notes').text(cellText($row, COL.notes));
    $('#ex_preview_by').text(cellText($row, COL.added_by));

    var st = cellText($row, COL.status).toLowerCase();
    var $badge = $('#ex_preview_status_badge');
    $badge.removeClass('paid due partial');
    if (st.indexOf('partial') !== -1) $badge.addClass('partial');
    else if (st.indexOf('paid') !== -1) $badge.addClass('paid');
    else if (st.indexOf('due') !== -1) $badge.addClass('due');

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#ex_preview_actions').empty().append($actions.contents());
  }

  function reloadTable() {
    try {
      if (typeof expense_table !== 'undefined') expense_table.ajax.reload(null, false);
    } catch (e) {}
  }

  ready(function () {
    if (!$('.ex-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#ex_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#ex_print_page').on('click', function () {
      window.print();
    });

    $('#ex_refresh_table, #ex_apply_filters').on('click', function () {
      reloadTable();
    });

    $('#ex_reset_filters').on('click', function () {
      $('#location_id, #expense_for, #expense_contact_filter, #expense_category_id, #expense_sub_category_id_filter, #expense_payment_status')
        .val('')
        .trigger('change');
      try {
        var $dr = $('#expense_date_range');
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
    });

    var searchTimer = null;
    $('#ex_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof expense_table !== 'undefined') expense_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#expense_table').on('draw.dt', function () {
      setTimeout(updateKpis, 80);
    });

    setTimeout(updateKpis, 1000);

    $(document).on('click', '#expense_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .clickable_td').length) {
        return;
      }
      if ($(this).hasClass('child')) return;
      showPreview($(this));
    });
  });
})();
