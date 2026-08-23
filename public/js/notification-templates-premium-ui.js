/**
 * Notification Templates — premium UI helpers only.
 * Keeps Bootstrap tabs, TinyMCE init, iCheck, and the existing save form.
 */
(function () {
  'use strict';

  if (!document.querySelector('.nt-shell')) return;

  var STORAGE_KEY = 'vkpos_nt_dark_mode';
  var SETTINGS_KEY = 'vkpos_nt_settings';
  var groupFilter = 'all';
  var channelFilter = 'all';
  var previewChannel = 'email';
  var lastFocus = null;
  var hookedEditors = {};

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function toast(type, message) {
    var host = document.getElementById('nt_toast_host');
    if (!host || !message) return;
    var el = document.createElement('div');
    el.className = 'nt-toast ' + (type || 'info');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 2400);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('nt-dark-mode', enabled);
    document.body.classList.toggle('nt-dark-mode', enabled);
    var shell = document.querySelector('.nt-shell');
    if (shell) shell.classList.toggle('nt-dark-mode', enabled);
    var icon = document.querySelector('#nt_dark_mode_toggle i');
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
    var shell = document.querySelector('.nt-shell');
    if (!shell) return;
    shell.classList.toggle('nt-hide-kpis', !!(s && s.hideKpis));
    shell.classList.toggle('nt-hide-preview', !!(s && s.hidePreview));
    var k = document.getElementById('nt_set_hide_kpis');
    var p = document.getElementById('nt_set_hide_preview');
    if (k) k.checked = !!(s && s.hideKpis);
    if (p) p.checked = !!(s && s.hidePreview);
  }

  function saveSettings() {
    var s = {
      hideKpis: !!(document.getElementById('nt_set_hide_kpis') || {}).checked,
      hidePreview: !!(document.getElementById('nt_set_hide_preview') || {}).checked,
    };
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify(s));
    } catch (e) {}
    applySettings(s);
  }

  function searchValue() {
    var a = document.getElementById('nt_quick_search');
    var b = document.getElementById('nt_global_search');
    return ((a && a.value) || (b && b.value) || '').trim().toLowerCase();
  }

  function setSearchValue(val) {
    var a = document.getElementById('nt_quick_search');
    var b = document.getElementById('nt_global_search');
    if (a && a.value !== val) a.value = val;
    if (b && b.value !== val) b.value = val;
    var clears = document.querySelectorAll('.nt-search-clear');
    for (var i = 0; i < clears.length; i++) {
      if (val) clears[i].removeAttribute('hidden');
      else clears[i].setAttribute('hidden', 'hidden');
    }
  }

  function setChipGroup(rootId, attr, val) {
    var root = document.getElementById(rootId);
    if (!root) return;
    var chips = root.querySelectorAll('.nt-chip');
    for (var i = 0; i < chips.length; i++) {
      chips[i].classList.toggle('is-active', chips[i].getAttribute(attr) === val);
    }
  }

  function activePane() {
    return document.querySelector('.nt-shell .tab-pane.active');
  }

  function paneKey(pane) {
    if (!pane || !pane.id) return '';
    return pane.id.replace(/^cn_/, '');
  }

  function fieldVal(id) {
    var el = document.getElementById(id);
    return el ? el.value : '';
  }

  function emailHtml(key) {
    var id = key + '_email_body';
    try {
      if (window.tinymce && tinymce.get(id)) {
        return tinymce.get(id).getContent() || '';
      }
    } catch (e) {}
    return fieldVal(id);
  }

  function updateSmsCounts() {
    var nodes = document.querySelectorAll('.nt-sms-count');
    for (var i = 0; i < nodes.length; i++) {
      var id = nodes[i].getAttribute('data-nt-for');
      var el = id ? document.getElementById(id) : null;
      nodes[i].textContent = el ? (el.value.length + ' chars') : '';
    }
  }

  function setPreviewChannel(ch) {
    previewChannel = ch || 'email';
    setChipGroup('nt_preview_channels', 'data-preview', previewChannel);
    var panels = document.querySelectorAll('[data-nt-preview]');
    for (var i = 0; i < panels.length; i++) {
      var on = panels[i].getAttribute('data-nt-preview') === previewChannel;
      if (on) panels[i].removeAttribute('hidden');
      else panels[i].setAttribute('hidden', 'hidden');
    }
  }

  function updatePreview() {
    var pane = activePane();
    var key = paneKey(pane);
    var subjectEl = document.getElementById('nt_preview_subject');
    var emailEl = document.getElementById('nt_preview_email');
    var smsEl = document.getElementById('nt_preview_sms');
    var waEl = document.getElementById('nt_preview_wa');
    if (subjectEl) subjectEl.textContent = key ? (fieldVal(key + '_subject') || '—') : '—';
    if (emailEl) {
      var html = key ? emailHtml(key) : '';
      emailEl.innerHTML = html || '—';
    }
    if (smsEl) smsEl.textContent = key ? (fieldVal(key + '_sms_body') || '—') : '—';
    if (waEl) waEl.textContent = key ? (fieldVal(key + '_whatsapp_text') || '—') : '—';

    var hasSms = key && key !== 'send_ledger';
    var smsChip = document.getElementById('nt_preview_sms_chip');
    var waChip = document.getElementById('nt_preview_wa_chip');
    if (smsChip) smsChip.hidden = !hasSms;
    if (waChip) waChip.hidden = !hasSms;
    if (!hasSms && (previewChannel === 'sms' || previewChannel === 'whatsapp')) {
      setPreviewChannel('email');
    }
    updateSmsCounts();
  }

  function activateVisibleTabs() {
    if (typeof $ === 'undefined' || !$.fn.tab) return;
    $('.nt-shell .nav-tabs-custom').each(function () {
      var $box = $(this);
      var $active = $box.find('> .nav-tabs > li.active');
      if ($active.length && !$active.hasClass('nt-is-hidden')) return;
      var $next = $box.find('> .nav-tabs > li[data-nt-key]:not(.nt-is-hidden)').first();
      if ($next.length) {
        $next.find('a[data-toggle="tab"]').tab('show');
      }
    });
  }

  function filterTemplates() {
    var q = searchValue();
    var lis = document.querySelectorAll('.nt-shell .nav-tabs > li[data-nt-key]');
    var visible = 0;

    for (var i = 0; i < lis.length; i++) {
      var li = lis[i];
      var group = li.getAttribute('data-nt-group') || '';
      var sms = li.getAttribute('data-nt-sms') === '1';
      var key = li.getAttribute('data-nt-key') || '';
      var hay = (li.getAttribute('data-nt-search') || '').toLowerCase();
      var subj = document.getElementById(key + '_subject');
      if (subj) hay += ' ' + (subj.value || '').toLowerCase();

      var matchQ = !q || hay.indexOf(q) !== -1;
      var matchG = groupFilter === 'all' || group === groupFilter;
      var matchC = true;
      if (channelFilter === 'sms' || channelFilter === 'whatsapp') matchC = sms;

      var show = matchQ && matchG && matchC;
      li.classList.toggle('nt-is-hidden', !show);
      if (show) visible++;
    }

    var sections = document.querySelectorAll('.nt-group[data-nt-section]');
    for (var s = 0; s < sections.length; s++) {
      var sec = sections[s];
      var g = sec.getAttribute('data-nt-section');
      var any = sec.querySelectorAll('.nav-tabs > li[data-nt-key]:not(.nt-is-hidden)').length;
      var showSec = any > 0 && (groupFilter === 'all' || groupFilter === g);
      sec.classList.toggle('nt-is-hidden', !showSec);
    }

    activateVisibleTabs();

    var visEl = document.getElementById('nt_meta_visible');
    if (visEl) visEl.textContent = String(visible);

    var emptySearch = document.getElementById('nt_search_empty');
    var hasAny = lis.length > 0;
    if (emptySearch) emptySearch.hidden = !(hasAny && visible === 0);

    updatePreview();
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        toast('success', 'Copied ' + text);
      }).catch(function () {
        toast('info', text);
      });
      return;
    }
    toast('info', text);
  }

  function insertTargetId(target) {
    if (!target) return '';
    if (typeof target === 'string') return target;
    return target.id || '';
  }

  function canInsertInto(target) {
    return /_(subject|email_body|sms_body|whatsapp_text)$/.test(insertTargetId(target));
  }

  function insertTag(tag) {
    if (!tag || !canInsertInto(lastFocus)) return;
    try {
      if (typeof lastFocus === 'string' && window.tinymce && tinymce.get(lastFocus)) {
        tinymce.get(lastFocus).insertContent(tag);
        updatePreview();
        return;
      }
    } catch (e) {}
    var el = lastFocus && lastFocus.nodeType ? lastFocus : null;
    if (!el || (el.tagName !== 'TEXTAREA' && el.tagName !== 'INPUT')) return;
    if (typeof el.selectionStart === 'number') {
      var start = el.selectionStart;
      var end = el.selectionEnd;
      var val = el.value || '';
      el.value = val.slice(0, start) + tag + val.slice(end);
      el.selectionStart = el.selectionEnd = start + tag.length;
    } else {
      el.value = (el.value || '') + tag;
    }
    if (typeof $ !== 'undefined') $(el).trigger('input');
    updatePreview();
  }

  function hookEditor(ed) {
    if (!ed || !ed.id || hookedEditors[ed.id]) return;
    hookedEditors[ed.id] = true;
    ed.on('change keyup undo redo SetContent', updatePreview);
    ed.on('focus', function () {
      lastFocus = ed.id;
    });
  }

  function hookTinyMce() {
    if (!window.tinymce) return;
    if (tinymce.editors) {
      for (var i = 0; i < tinymce.editors.length; i++) hookEditor(tinymce.editors[i]);
    }
  }

  ready(function () {
    var dark = false;
    try { dark = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
    applyDark(dark);
    applySettings(loadSettings());
    filterTemplates();
    updateSmsCounts();
    updatePreview();

    var darkBtn = document.getElementById('nt_dark_mode_toggle');
    if (darkBtn) {
      darkBtn.addEventListener('click', function () {
        var next = !document.querySelector('.nt-shell').classList.contains('nt-dark-mode');
        applyDark(next);
        try { localStorage.setItem(STORAGE_KEY, next ? '1' : '0'); } catch (e) {}
      });
    }

    var settingsBtn = document.getElementById('nt_settings_toggle');
    var settingsPanel = document.getElementById('nt_settings_panel');
    if (settingsBtn && settingsPanel) {
      settingsBtn.addEventListener('click', function () {
        var open = settingsPanel.hasAttribute('hidden');
        if (open) settingsPanel.removeAttribute('hidden');
        else settingsPanel.setAttribute('hidden', 'hidden');
        settingsBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    var hideK = document.getElementById('nt_set_hide_kpis');
    var hideP = document.getElementById('nt_set_hide_preview');
    if (hideK) hideK.addEventListener('change', saveSettings);
    if (hideP) hideP.addEventListener('change', saveSettings);

    var fsBtn = document.getElementById('nt_fullscreen');
    if (fsBtn) {
      fsBtn.addEventListener('click', function () {
        var shell = document.querySelector('.nt-shell');
        if (!shell) return;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
          if (shell.requestFullscreen) shell.requestFullscreen();
          else if (shell.webkitRequestFullscreen) shell.webkitRequestFullscreen();
        } else if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        }
      });
    }

    var refresh = document.getElementById('nt_refresh');
    if (refresh) {
      refresh.addEventListener('click', function () {
        window.location.reload();
      });
    }

    function onSearchInput(ev) {
      setSearchValue(ev.target.value);
      filterTemplates();
    }

    var q1 = document.getElementById('nt_quick_search');
    var q2 = document.getElementById('nt_global_search');
    if (q1) q1.addEventListener('input', onSearchInput);
    if (q2) q2.addEventListener('input', onSearchInput);

    var clears = document.querySelectorAll('.nt-search-clear');
    for (var c = 0; c < clears.length; c++) {
      clears[c].addEventListener('click', function () {
        setSearchValue('');
        filterTemplates();
      });
    }

    var clearSearchBtn = document.getElementById('nt_clear_search');
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function () {
        setSearchValue('');
        groupFilter = 'all';
        channelFilter = 'all';
        setChipGroup('nt_group_chips', 'data-group', 'all');
        setChipGroup('nt_channel_chips', 'data-channel', 'all');
        filterTemplates();
      });
    }

    var groupChips = document.getElementById('nt_group_chips');
    if (groupChips) {
      groupChips.addEventListener('click', function (e) {
        var chip = e.target.closest('.nt-chip');
        if (!chip) return;
        groupFilter = chip.getAttribute('data-group') || 'all';
        setChipGroup('nt_group_chips', 'data-group', groupFilter);
        filterTemplates();
      });
    }

    var channelChips = document.getElementById('nt_channel_chips');
    if (channelChips) {
      channelChips.addEventListener('click', function (e) {
        var chip = e.target.closest('.nt-chip');
        if (!chip) return;
        channelFilter = chip.getAttribute('data-channel') || 'all';
        setChipGroup('nt_channel_chips', 'data-channel', channelFilter);
        filterTemplates();
      });
    }

    var previewChips = document.getElementById('nt_preview_channels');
    if (previewChips) {
      previewChips.addEventListener('click', function (e) {
        var chip = e.target.closest('.nt-chip');
        if (!chip || chip.hidden) return;
        setPreviewChannel(chip.getAttribute('data-preview') || 'email');
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var t = e.target;
      if (!t) return;
      if (t.id === 'nt_quick_search' || t.id === 'nt_global_search' || (t.classList && t.classList.contains('nt-var-search'))) {
        e.preventDefault();
      }
    });

    document.addEventListener('input', function (e) {
      if (!e.target) return;
      if (e.target.classList && e.target.classList.contains('nt-var-search')) {
        var q = (e.target.value || '').toLowerCase();
        var panel = e.target.closest('.nt-tags-panel');
        if (!panel) return;
        var tags = panel.querySelectorAll('.nt-tag');
        for (var i = 0; i < tags.length; i++) {
          var tag = tags[i].getAttribute('data-nt-tag') || tags[i].textContent || '';
          tags[i].classList.toggle('nt-tag-hidden', q && tag.toLowerCase().indexOf(q) === -1);
        }
        return;
      }
      if (e.target.classList && e.target.classList.contains('nt-sync')) {
        updatePreview();
      }
    });

    document.addEventListener('focusin', function (e) {
      var t = e.target;
      if (!t || !t.closest) return;
      if (!t.closest('.tab-pane')) return;
      if (t.classList && t.classList.contains('nt-var-search')) return;
      if (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA') lastFocus = t;
    });

    document.addEventListener('click', function (e) {
      var tag = e.target.closest && e.target.closest('.nt-shell .nt-tag');
      if (!tag) return;
      e.preventDefault();
      var value = tag.getAttribute('data-nt-tag') || tag.textContent || '';
      insertTag(value);
      copyText(value);
    });

    if (typeof $ !== 'undefined') {
      $(document).on('shown.bs.tab', '.nt-shell a[data-toggle="tab"]', function () {
        updatePreview();
      });
    }

    var form = document.getElementById('nt_templates_form');
    if (form) {
      form.addEventListener('submit', function () {
        try {
          if (window.tinymce && tinymce.triggerSave) tinymce.triggerSave();
        } catch (e) {}
        var btn = document.getElementById('nt_save_btn');
        if (btn && !btn.disabled) {
          btn.disabled = true;
          btn.setAttribute('aria-busy', 'true');
        }
      });
    }

    if (window.tinymce && tinymce.on) {
      tinymce.on('AddEditor', function (evt) {
        if (evt && evt.editor) hookEditor(evt.editor);
      });
    }
    hookTinyMce();
    setTimeout(hookTinyMce, 800);
    setTimeout(updatePreview, 1000);
  });
})();
