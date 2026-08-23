/**
 * Manage Modules — premium UI helpers only.
 * Reuses existing upload/install/uninstall/delete/regenerate actions.
 * Does not add activation, AJAX, or backend calls.
 */
(function () {
  'use strict';

  if (!document.querySelector('.mm-shell')) return;

  var STORAGE_KEY = 'vkpos_mm_dark_mode';
  var SETTINGS_KEY = 'vkpos_mm_settings';
  var statusFilter = 'all';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('mm_toast_host');
    if (!host || !message) return;
    var el = document.createElement('div');
    el.className = 'mm-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 3200);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('mm-dark-mode', enabled);
    document.body.classList.toggle('mm-dark-mode', enabled);
    var shell = document.querySelector('.mm-shell');
    if (shell) shell.classList.toggle('mm-dark-mode', enabled);
    var icon = document.querySelector('#mm_dark_mode_toggle i');
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
    var shell = document.querySelector('.mm-shell');
    if (!shell) return;
    shell.classList.toggle('mm-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('mm-compact', !!(s && s.compact));
    var k = document.getElementById('mm_set_hide_kpis');
    var c = document.getElementById('mm_set_compact');
    if (k) k.checked = !!(s && s.hideKpis);
    if (c) c.checked = !!(s && s.compact);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('mm_set_hide_kpis') || {}).checked,
      compact: !!(document.getElementById('mm_set_compact') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (e) {}
    applySettings(s);
  }

  function searchValue() {
    var a = document.getElementById('mm_quick_search');
    var b = document.getElementById('mm_global_search');
    return ((a && a.value) || (b && b.value) || '').trim().toLowerCase();
  }

  function setSearchValue(val) {
    var a = document.getElementById('mm_quick_search');
    var b = document.getElementById('mm_global_search');
    if (a && a.value !== val) a.value = val;
    if (b && b.value !== val) b.value = val;
    var clears = document.querySelectorAll('.mm-search-clear');
    for (var i = 0; i < clears.length; i++) {
      if (val) clears[i].removeAttribute('hidden');
      else clears[i].setAttribute('hidden', 'hidden');
    }
  }

  function setStatusChip(val) {
    var chips = document.querySelectorAll('#mm_status_chips .mm-chip');
    for (var i = 0; i < chips.length; i++) {
      chips[i].classList.toggle('is-active', chips[i].getAttribute('data-status') === val);
    }
  }

  function filterCards() {
    var q = searchValue();
    var cards = document.querySelectorAll('.mm-card[data-mm-kind]');
    var visible = 0;

    for (var i = 0; i < cards.length; i++) {
      var card = cards[i];
      var kind = card.getAttribute('data-mm-kind') || '';
      var update = card.getAttribute('data-mm-update') === '1';
      var hay = (card.getAttribute('data-mm-search') || '').toLowerCase();
      var matchSearch = !q || hay.indexOf(q) !== -1;
      var matchStatus = true;
      if (statusFilter === 'installed') matchStatus = kind === 'installed';
      else if (statusFilter === 'not_installed') matchStatus = kind === 'not_installed';
      else if (statusFilter === 'update') matchStatus = update;
      else if (statusFilter === 'catalog') matchStatus = kind === 'catalog';

      var show = matchSearch && matchStatus;
      card.classList.toggle('is-hidden', !show);
      if (show) visible++;
    }

    var sections = document.querySelectorAll('.mm-section[data-mm-section]');
    for (var s = 0; s < sections.length; s++) {
      var section = sections[s];
      var shown = section.querySelectorAll('.mm-card[data-mm-kind]:not(.is-hidden)').length;
      section.classList.toggle('is-empty-section', shown === 0);
    }

    var emptySearch = document.getElementById('mm_search_empty');
    var emptyAll = document.getElementById('mm_empty_all');
    var hasAny = document.querySelectorAll('.mm-card[data-mm-kind]').length > 0;
    if (emptySearch) emptySearch.hidden = !(hasAny && visible === 0);
    if (emptyAll) emptyAll.hidden = hasAny;

    var visEl = document.getElementById('mm_meta_visible');
    if (visEl) visEl.textContent = String(visible);
  }

  function closeDrawer() {
    var drawer = document.getElementById('mm_drawer');
    var backdrop = document.getElementById('mm_drawer_backdrop');
    if (drawer) drawer.classList.remove('open');
    if (backdrop) backdrop.hidden = true;
    document.body.style.overflow = '';
  }

  function openDrawer(card) {
    if (!card) return;
    var drawer = document.getElementById('mm_drawer');
    var backdrop = document.getElementById('mm_drawer_backdrop');
    if (!drawer) return;

    var name = card.getAttribute('data-mm-name') || '—';
    var desc = card.getAttribute('data-mm-desc') || '—';
    var kind = card.getAttribute('data-mm-kind') || '';
    var version = card.getAttribute('data-mm-version') || '';
    var update = card.getAttribute('data-mm-update') === '1';
    var requires = card.getAttribute('data-mm-requires') || '';

    var statusLabel = '—';
    var statusClass = 'mm-badge';
    if (kind === 'installed') {
      statusLabel = 'Installed';
      statusClass = 'mm-badge is-installed';
    } else if (kind === 'not_installed') {
      statusLabel = 'Not installed';
      statusClass = 'mm-badge is-not-installed';
    } else if (kind === 'catalog') {
      statusLabel = 'Available to buy';
      statusClass = 'mm-badge is-catalog';
    }

    drawer.querySelector('[data-mm-d="name"]').textContent = name;
    drawer.querySelector('[data-mm-d="desc"]').textContent = desc;
    var statusEl = drawer.querySelector('[data-mm-d="status"]');
    statusEl.className = statusClass;
    statusEl.textContent = statusLabel;

    var verRow = drawer.querySelector('[data-mm-row="version"]');
    var verVal = drawer.querySelector('[data-mm-d="version"]');
    if (verRow && verVal) {
      verRow.hidden = !version;
      verVal.textContent = version || '—';
    }

    var updRow = drawer.querySelector('[data-mm-row="update"]');
    var updVal = drawer.querySelector('[data-mm-d="update"]');
    if (updRow && updVal) {
      updRow.hidden = kind !== 'installed';
      updVal.textContent = update ? 'Update available' : 'No update flagged';
    }

    var reqRow = drawer.querySelector('[data-mm-row="requires"]');
    var reqVal = drawer.querySelector('[data-mm-d="requires"]');
    if (reqRow && reqVal) {
      reqRow.hidden = !requires;
      reqVal.textContent = requires || '—';
    }

    drawer.classList.add('open');
    if (backdrop) backdrop.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function isInteractive(el) {
    return !!(el && el.closest && el.closest('a, button, form, input, select, textarea, label'));
  }

  ready(function () {
    var dark = false;
    try { dark = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    filterCards();

    var darkBtn = document.getElementById('mm_dark_mode_toggle');
    if (darkBtn) {
      darkBtn.addEventListener('click', function () {
        var next = !document.querySelector('.mm-shell').classList.contains('mm-dark-mode');
        applyDark(next);
        try { localStorage.setItem(STORAGE_KEY, next ? '1' : '0'); } catch (e) {}
      });
    }

    var settingsBtn = document.getElementById('mm_settings_toggle');
    var settingsPanel = document.getElementById('mm_settings_panel');
    if (settingsBtn && settingsPanel) {
      settingsBtn.addEventListener('click', function () {
        var open = settingsPanel.hasAttribute('hidden');
        if (open) settingsPanel.removeAttribute('hidden');
        else settingsPanel.setAttribute('hidden', 'hidden');
        settingsBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    var hideK = document.getElementById('mm_set_hide_kpis');
    var compact = document.getElementById('mm_set_compact');
    if (hideK) hideK.addEventListener('change', saveSettings);
    if (compact) compact.addEventListener('change', saveSettings);

    var fsBtn = document.getElementById('mm_fullscreen');
    if (fsBtn) {
      fsBtn.addEventListener('click', function () {
        var shell = document.querySelector('.mm-shell');
        if (!shell) return;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
          if (shell.requestFullscreen) shell.requestFullscreen();
          else if (shell.webkitRequestFullscreen) shell.webkitRequestFullscreen();
        } else {
          if (document.exitFullscreen) document.exitFullscreen();
          else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
        }
      });
    }

    var refresh = document.getElementById('mm_refresh');
    if (refresh) {
      refresh.addEventListener('click', function () {
        window.location.reload();
      });
    }

    function onSearchInput(ev) {
      setSearchValue(ev.target.value);
      filterCards();
    }

    var q1 = document.getElementById('mm_quick_search');
    var q2 = document.getElementById('mm_global_search');
    if (q1) q1.addEventListener('input', onSearchInput);
    if (q2) q2.addEventListener('input', onSearchInput);

    var clears = document.querySelectorAll('.mm-search-clear');
    for (var c = 0; c < clears.length; c++) {
      clears[c].addEventListener('click', function () {
        setSearchValue('');
        filterCards();
        var g = document.getElementById('mm_global_search') || document.getElementById('mm_quick_search');
        if (g) g.focus();
      });
    }

    var clearSearchBtn = document.getElementById('mm_clear_search');
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function () {
        setSearchValue('');
        statusFilter = 'all';
        setStatusChip('all');
        filterCards();
      });
    }

    var chips = document.getElementById('mm_status_chips');
    if (chips) {
      chips.addEventListener('click', function (e) {
        var chip = e.target.closest('.mm-chip');
        if (!chip) return;
        statusFilter = chip.getAttribute('data-status') || 'all';
        setStatusChip(statusFilter);
        filterCards();
      });
    }

    var attn = document.getElementById('mm_update_chip');
    if (attn) {
      attn.addEventListener('click', function () {
        statusFilter = 'update';
        setStatusChip('update');
        filterCards();
      });
    }

    document.addEventListener('click', function (e) {
      var details = e.target.closest('.mm-details-btn');
      if (details) {
        e.preventDefault();
        openDrawer(details.closest('.mm-card'));
        return;
      }
      var card = e.target.closest('.mm-card[data-mm-kind]');
      if (card && !isInteractive(e.target)) {
        openDrawer(card);
      }
    });

    var closeBtn = document.getElementById('mm_drawer_close');
    var backdrop = document.getElementById('mm_drawer_backdrop');
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });

    var uploadForm = document.getElementById('upload_module_form');
    if (uploadForm) {
      uploadForm.addEventListener('submit', function () {
        var btn = uploadForm.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
          btn.disabled = true;
          btn.setAttribute('aria-busy', 'true');
        }
      });
    }

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || !form.matches) return;
      if (!form.matches('.mm-delete-form')) return;
      var btn = form.querySelector('button[type="submit"], button:not([type])');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
      }
    });
  });
})();
