/**
 * Discount Management — premium UI helpers only.
 * Reuses existing #discounts_table + discounts_table (initialized in app.js).
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_dc_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('dc-dark-mode', enabled);
    document.body.classList.toggle('dc-dark-mode', enabled);
    var icon = document.querySelector('#dc_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function cellText($row, idx) {
    if (idx < 0 || !$row || !$row.length) return '—';
    return ($row.find('td').eq(idx).text() || '').trim() || '—';
  }

  // Columns from app.js discounts_table:
  // 0 select, 1 name, 2 starts, 3 ends, 4 amount, 5 priority,
  // 6 brand, 7 category, 8 products, 9 location, 10 action
  var COL = {
    select: 0,
    name: 1,
    starts: 2,
    ends: 3,
    amount: 4,
    priority: 5,
    brand: 6,
    category: 7,
    products: 8,
    location: 9,
    action: 10,
  };

  function updateKpis() {
    try {
      if (typeof discounts_table !== 'undefined' && discounts_table) {
        var info = discounts_table.page.info();
        $('#dc_kpi_total').text(info.recordsTotal);
        $('#dc_kpi_filtered').text(info.recordsDisplay);
        $('#dc_sum_filtered').text(info.recordsDisplay);
      }
    } catch (e) {}

    var inactive = 0;
    var active = 0;
    $('#discounts_table tbody tr').each(function () {
      var name = cellText($(this), COL.name).toLowerCase();
      if (name.indexOf('inactive') !== -1) inactive += 1;
      else active += 1;
    });
    $('#dc_kpi_active').text(active);
    $('#dc_kpi_inactive').text(inactive);
    $('#dc_sum_active').text(active);
  }

  function showPreview($row) {
    if (!$row || !$row.length) return;
    $('#discounts_table tbody tr').removeClass('dc-row-active');
    $row.addClass('dc-row-active');

    $('#dc_preview_empty').hide();
    $('#dc_preview_content').show();

    var name = cellText($row, COL.name);
    var inactive = name.toLowerCase().indexOf('inactive') !== -1;
    $('#dc_preview_name').text(name.replace(/\s*inactive\s*/i, '').trim() || name);
    $('#dc_preview_status')
      .text(inactive ? 'Inactive' : 'Active')
      .toggleClass('is-inactive', inactive);
    $('#dc_preview_starts').text(cellText($row, COL.starts));
    $('#dc_preview_ends').text(cellText($row, COL.ends));
    $('#dc_preview_amount').text(cellText($row, COL.amount));
    $('#dc_preview_priority').text(cellText($row, COL.priority));
    $('#dc_preview_brand').text(cellText($row, COL.brand));
    $('#dc_preview_category').text(cellText($row, COL.category));
    $('#dc_preview_products').text(cellText($row, COL.products));
    $('#dc_preview_location').text(cellText($row, COL.location));

    var $actions = $row.find('td').eq(COL.action).clone(true, true);
    $('#dc_preview_actions').empty().append($actions.contents());
  }

  ready(function () {
    if (!$('.dc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#dc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#dc_print_page').on('click', function () {
      window.print();
    });

    $('#dc_refresh_table').on('click', function () {
      try {
        if (typeof discounts_table !== 'undefined') discounts_table.ajax.reload(null, false);
        else window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    });

    var searchTimer = null;
    $('#dc_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        try {
          if (typeof discounts_table !== 'undefined') discounts_table.search(q).draw();
        } catch (e) {}
      }, 250);
    });

    $('#discounts_table').on('draw.dt', function () {
      setTimeout(updateKpis, 50);
    });

    setTimeout(updateKpis, 900);

    $(document).on('click', '#discounts_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, .dropdown-menu, input, select, .btn-modal').length) {
        return;
      }
      showPreview($(this));
    });
  });
})();
