/**
 * Business Locations — premium UI helpers only.
 * Reuses #business_location_table, app.js DataTable, .btn-modal,
 * .location_add_modal / .location_edit_modal, #business_location_add_form,
 * and button.activate-deactivate-location. No new CRUD or AJAX.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_bloc_dark_mode';
  var SETTINGS_KEY = 'vkpos_bloc_settings';
  var VIEW_KEY = 'vkpos_bloc_view';
  var statusFilter = 'all';
  var searchTimer = null;
  var cardView = false;

  function ready(fn) {
    if (typeof jQuery === 'undefined') return;
    jQuery(fn);
  }

  function getTable() {
    if (typeof jQuery === 'undefined') return null;
    if (!$('#business_location_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#business_location_table')) return null;
    try {
      if (typeof business_locations !== 'undefined' && business_locations) {
        return business_locations;
      }
    } catch (e) {}
    return $('#business_location_table').DataTable();
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function cellText($td) {
    if (!$td || !$td.length) return '';
    var $clone = $td.clone();
    $clone.find('.bloc-status-badge').remove();
    return ($clone.text() || '').replace(/\s+/g, ' ').trim();
  }

  function dash(val) {
    return val ? val : '—';
  }

  function isRowActive($row) {
    return $row.find('button.activate-deactivate-location.tw-dw-btn-error').length > 0;
  }

  function dataRows() {
    return $('#business_location_table tbody tr').filter(function () {
      var $tr = $(this);
      if ($tr.hasClass('dataTables_empty')) return false;
      return $tr.find('td').length > 1;
    });
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('bloc-dark-mode', enabled);
    document.body.classList.toggle('bloc-dark-mode', enabled);
    var shell = document.querySelector('.bloc-shell');
    if (shell) shell.classList.toggle('bloc-dark-mode', enabled);
    var icon = document.querySelector('#bloc_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function loadSettings() {
    try {
      return JSON.parse(localStorage.getItem(SETTINGS_KEY) || '{}') || {};
    } catch (e) {
      return {};
    }
  }

  function applySettings(s) {
    var shell = document.querySelector('.bloc-shell');
    if (!shell) return;
    shell.classList.toggle('bloc-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('bloc-compact', !!(s && s.compact));
    var k = document.getElementById('bloc_set_hide_kpis');
    var c = document.getElementById('bloc_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('bloc_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('bloc_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (e) {}
    applySettings(s);
  }

  function setLoading(on) {
    var shell = document.querySelector('.bloc-shell');
    if (shell) shell.classList.toggle('is-loading', !!on);
    $('.bloc-kpi').toggleClass('is-loading', !!on);
    var spin = document.getElementById('bloc_search_spinner');
    if (spin) spin.hidden = !on;
  }

  function decorateRows() {
    dataRows().each(function () {
      var $tr = $(this);
      var active = isRowActive($tr);
      $tr.toggleClass('bloc-is-active', active).toggleClass('bloc-is-inactive', !active);
      var $name = $tr.find('td').eq(0);
      $name.find('.bloc-status-badge').remove();
      var $badge = $('<span class="bloc-status-badge"></span>')
        .addClass(active ? 'is-active' : 'is-inactive')
        .text(active ? 'Active' : 'Inactive');
      $name.prepend($badge);
    });
  }

  function applyStatusFilter() {
    dataRows().each(function () {
      var $tr = $(this);
      var active = $tr.hasClass('bloc-is-active');
      var show = statusFilter === 'all' || (statusFilter === 'active' && active) || (statusFilter === 'inactive' && !active);
      $tr.toggle(show);
    });
  }

  function updateKpis() {
    var table = getTable();
    var total = '—';
    if (table) {
      try {
        total = table.page.info().recordsTotal;
      } catch (e) {}
    }
    var $all = dataRows();
    var active = 0;
    var inactive = 0;
    var showing = 0;
    $all.each(function () {
      var $tr = $(this);
      if ($tr.hasClass('bloc-is-active')) active += 1;
      else inactive += 1;
      if ($tr.is(':visible')) showing += 1;
    });
    $('#bloc_kpi_total').text(total);
    $('#bloc_kpi_active').text($all.length ? active : (total === 0 ? 0 : '—'));
    $('#bloc_kpi_inactive').text($all.length ? inactive : (total === 0 ? 0 : '—'));
    $('#bloc_kpi_showing').text($all.length ? showing : (total === 0 ? 0 : '—'));
    $('.bloc-kpi').removeClass('is-loading');

    var $empty = $('#bloc_empty');
    var $none = $('#bloc_no_result');
    var $err = $('#bloc_error');
    var hasError = !$err.prop('hidden');
    if (hasError) {
      $empty.prop('hidden', true);
      $none.prop('hidden', true);
      return;
    }
    if (total === 0) {
      $empty.prop('hidden', false);
      $none.prop('hidden', true);
    } else if (showing === 0) {
      $empty.prop('hidden', true);
      $none.prop('hidden', false);
    } else {
      $empty.prop('hidden', true);
      $none.prop('hidden', true);
    }
  }

  function buildCards() {
    var $grid = $('#bloc_card_grid');
    if (!$grid.length) return;
    $grid.empty();
    dataRows().each(function () {
      var $tr = $(this);
      if (!$tr.is(':visible')) return;
      var tds = $tr.find('td');
      var name = cellText(tds.eq(0));
      var code = cellText(tds.eq(1));
      var landmark = cellText(tds.eq(2));
      var city = cellText(tds.eq(3));
      var scheme = cellText(tds.eq(8));
      var layout = cellText(tds.eq(9));
      var price = cellText(tds.eq(7));
      var active = $tr.hasClass('bloc-is-active');
      var locLine = [city, landmark].filter(Boolean).join(' · ');
      var $card = $('<article class="bloc-loc-card"></article>');
      $card.append(
        '<div class="bloc-loc-card-head">' +
          '<span class="bloc-loc-icon" aria-hidden="true"><i class="fas fa-store"></i></span>' +
          '<div style="flex:1;min-width:0;">' +
            '<h4>' + escapeHtml(dash(name)) + '</h4>' +
            '<span class="bloc-loc-code">' + escapeHtml(dash(code)) + '</span>' +
          '</div>' +
          '<span class="bloc-status-badge ' + (active ? 'is-active' : 'is-inactive') + '">' +
            (active ? 'Active' : 'Inactive') +
          '</span>' +
        '</div>'
      );
      $card.append('<div class="bloc-loc-meta">' + escapeHtml(dash(locLine)) + '</div>');
      $card.append(
        '<div class="bloc-loc-kv">' +
          '<div><span>Invoice scheme</span><strong>' + escapeHtml(dash(scheme)) + '</strong></div>' +
          '<div><span>POS layout</span><strong>' + escapeHtml(dash(layout)) + '</strong></div>' +
          '<div><span>Price group</span><strong>' + escapeHtml(dash(price)) + '</strong></div>' +
        '</div>'
      );
      var $actions = $('<div class="bloc-loc-actions"></div>');
      var $view = $('<button type="button" class="bloc-btn"></button>').text('View');
      $view.on('click', function (e) {
        e.stopPropagation();
        openDrawer($tr);
      });
      $actions.append($view);
      $actions.append(tds.eq(11).children().clone());
      $card.append($actions);
      $card.on('click', function (e) {
        if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
        openDrawer($tr);
      });
      $grid.append($card);
    });
  }

  function afterDraw() {
    decorateRows();
    applyStatusFilter();
    updateKpis();
    buildCards();
    setLoading(false);
    $('#bloc_error').prop('hidden', true);
  }

  function setView(cards) {
    cardView = !!cards;
    try {
      localStorage.setItem(VIEW_KEY, cardView ? 'cards' : 'table');
    } catch (e) {}
    var mobile = window.matchMedia('(max-width: 767px)').matches;
    var showCards = cardView || mobile;
    $('#bloc_card_grid').prop('hidden', !showCards);
    if (!mobile) {
      $('#bloc_table_wrap').toggle(!showCards);
      if (!showCards) {
        var table = getTable();
        if (table && table.columns) {
          try { table.columns.adjust(); } catch (e) {}
        }
      }
    }
    $('#bloc_view_table').toggleClass('is-on', !cardView).attr('aria-pressed', !cardView);
    $('#bloc_view_cards').toggleClass('is-on', cardView).attr('aria-pressed', cardView);
    if (showCards) buildCards();
  }

  function openDrawer($row) {
    if (!$row || !$row.length) return;
    $('#business_location_table tbody tr').removeClass('bloc-row-active-sel');
    $row.addClass('bloc-row-active-sel');

    var tds = $row.find('td');
    var labels = [
      'Location name',
      'Location ID',
      'Landmark',
      'City',
      'Zip code',
      'State',
      'Country',
      'Selling price group',
      'Invoice scheme',
      'Invoice layout (POS)',
      'Invoice layout (sale)',
    ];
    var name = cellText(tds.eq(0));
    var code = cellText(tds.eq(1));
    var active = isRowActive($row);

    $('#bloc_drawer_title').text(name || 'Location details');
    $('#bloc_drawer_code').text(code ? 'ID ' + code : '');
    $('#bloc_drawer_status').html(
      '<span class="bloc-status-badge ' + (active ? 'is-active' : 'is-inactive') + '">' +
        (active ? 'Active' : 'Inactive') +
      '</span>'
    );

    var $dl = $('#bloc_drawer_dl').empty();
    labels.forEach(function (label, i) {
      var val = cellText(tds.eq(i));
      $dl.append(
        '<div><dt>' + escapeHtml(label) + '</dt><dd>' + escapeHtml(dash(val)) + '</dd></div>'
      );
    });

    var $actions = $('#bloc_drawer_actions').empty();
    $actions.append(tds.eq(11).children().clone());

    var drawer = document.getElementById('bloc_drawer');
    var backdrop = document.getElementById('bloc_drawer_backdrop');
    if (drawer) {
      drawer.hidden = false;
      requestAnimationFrame(function () {
        drawer.classList.add('open');
      });
    }
    if (backdrop) backdrop.hidden = false;
  }

  function closeDrawer() {
    var drawer = document.getElementById('bloc_drawer');
    var backdrop = document.getElementById('bloc_drawer_backdrop');
    if (drawer) {
      drawer.classList.remove('open');
      setTimeout(function () {
        drawer.hidden = true;
      }, 220);
    }
    if (backdrop) backdrop.hidden = true;
  }

  ready(function () {
    if (!$('.bloc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());

    try {
      cardView = localStorage.getItem(VIEW_KEY) === 'cards';
    } catch (e) {}
    setView(cardView);

    $('#bloc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#bloc_refresh_table').on('click', function () {
      var table = getTable();
      setLoading(true);
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    $('#bloc_fullscreen').on('click', function () {
      var el = document.getElementById('bloc_shell');
      if (!el) return;
      if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
      } else if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    });

    $('#bloc_settings_toggle').on('click', function () {
      var panel = document.getElementById('bloc_settings_panel');
      if (!panel) return;
      panel.hidden = !panel.hidden;
      this.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    });

    $('#bloc_set_hide_kpis, #bloc_set_compact').on('change', saveSettings);

    $('#bloc_quick_search').on('input', function () {
      var q = $(this).val();
      $('#bloc_search_clear').prop('hidden', !q);
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (!table) return;
        setLoading(true);
        table.search(q).draw();
      }, 250);
    });

    $('#bloc_search_clear').on('click', function () {
      $('#bloc_quick_search').val('');
      $(this).prop('hidden', true);
      var table = getTable();
      if (table) table.search('').draw();
    });

    $('[data-bloc-status]').on('click', function () {
      statusFilter = $(this).data('bloc-status') || 'all';
      $('[data-bloc-status]').removeClass('is-on');
      $(this).addClass('is-on');
      applyStatusFilter();
      updateKpis();
      buildCards();
    });

    $('#bloc_view_table').on('click', function () { setView(false); });
    $('#bloc_view_cards').on('click', function () { setView(true); });

    $('#bloc_empty_add').on('click', function () {
      $('#bloc_add_btn').trigger('click');
    });

    $('#bloc_reset_filters').on('click', function () {
      statusFilter = 'all';
      $('[data-bloc-status]').removeClass('is-on');
      $('#bloc_filter_all').addClass('is-on');
      $('#bloc_quick_search').val('');
      $('#bloc_search_clear').prop('hidden', true);
      var table = getTable();
      if (table) table.search('').draw();
      else {
        applyStatusFilter();
        updateKpis();
        buildCards();
      }
    });

    $('#bloc_retry').on('click', function () {
      $('#bloc_error').prop('hidden', true);
      setLoading(true);
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    $('#bloc_drawer_close, #bloc_drawer_backdrop').on('click', closeDrawer);
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });
    $(document).on('click', '.bloc-drawer .btn-modal, .bloc-card-grid .btn-modal', function () {
      closeDrawer();
    });

    $('#business_location_table').on('click', 'tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn, input').length) return;
      if ($(this).hasClass('dataTables_empty')) return;
      openDrawer($(this));
    });

    $('#business_location_table')
      .on('preXhr.dt', function () {
        setLoading(true);
        $('#bloc_error').prop('hidden', true);
      })
      .on('xhr.dt', function (e, settings, json) {
        if (json == null) {
          setLoading(false);
          $('#bloc_error').prop('hidden', false);
          $('#bloc_empty').prop('hidden', true);
          $('#bloc_no_result').prop('hidden', true);
        }
      })
      .on('error.dt', function () {
        setLoading(false);
        $('#bloc_error').prop('hidden', false);
        $('#bloc_empty').prop('hidden', true);
        $('#bloc_no_result').prop('hidden', true);
      })
      .on('draw.dt', afterDraw);

    $(window).on('resize', function () {
      setView(cardView);
    });

    setLoading(true);
    if (getTable()) {
      afterDraw();
    } else {
      var tries = 0;
      var timer = setInterval(function () {
        tries += 1;
        if (getTable() || tries > 40) {
          clearInterval(timer);
          afterDraw();
        }
      }, 50);
    }
  });
})();
