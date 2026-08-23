/**
 * Business Settings — premium UI helpers only.
 * Does not replace pos-tab clicks, #search_settings Select2,
 * test email/SMS AJAX, or __page_leave_confirmation.
 */
(function () {
  'use strict';

  if (!document.querySelector('.bs-shell')) return;

  var STORAGE_KEY = 'vkpos_bs_dark_mode';
  var orig = '';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function formEl() {
    return document.getElementById('bussiness_edit_form');
  }

  function snapshot() {
    var form = formEl();
    if (!form || typeof $ === 'undefined') return '';
    try {
      return $(form).serialize();
    } catch (e) {
      return '';
    }
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('bs-dark-mode', enabled);
    document.body.classList.toggle('bs-dark-mode', enabled);
    var shell = document.querySelector('.bs-shell');
    if (shell) shell.classList.toggle('bs-dark-mode', enabled);
    var icon = document.querySelector('#bs_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  function setDirty(dirty) {
    var meta = document.getElementById('bs_dirty_meta');
    var bar = document.querySelector('.bs-save-bar');
    var hint = document.getElementById('bs_save_hint');
    if (meta) {
      if (dirty) meta.removeAttribute('hidden');
      else meta.setAttribute('hidden', 'hidden');
    }
    if (bar) bar.classList.toggle('is-dirty', !!dirty);
    if (hint) {
      var def = hint.getAttribute('data-default') || hint.textContent;
      hint.textContent = dirty ? 'Unsaved changes' : def;
    }
  }

  function checkDirty() {
    if (!orig) return;
    setDirty(snapshot() !== orig);
  }

  function setSearchPlaceholder() {
    var el = document.getElementById('search_settings');
    if (!el || typeof $ === 'undefined') return;
    var $el = $(el);
    try {
      var $c = $el.data('select2') && $el.data('select2').$container;
      if (!$c) return;
      var $rendered = $c.find('.select2-selection__rendered');
      if ($rendered.length && (!$el.val() || $el.val() === '')) {
        $rendered.text('Search settings...');
        $rendered.addClass('select2-selection__placeholder');
      }
    } catch (e) {}
  }

  ready(function () {
    var dark = false;
    try { dark = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
    applyDark(dark);

    setTimeout(function () {
      orig = snapshot();
      setDirty(false);
      setSearchPlaceholder();
    }, 1100);

    var darkBtn = document.getElementById('bs_dark_mode_toggle');
    if (darkBtn) {
      darkBtn.addEventListener('click', function () {
        var next = !document.querySelector('.bs-shell').classList.contains('bs-dark-mode');
        applyDark(next);
        try { localStorage.setItem(STORAGE_KEY, next ? '1' : '0'); } catch (e) {}
      });
    }

    var headerSave = document.getElementById('bs_header_save');
    var saveBtn = document.getElementById('bs_save_btn');
    if (headerSave && saveBtn) {
      headerSave.addEventListener('click', function () {
        if (saveBtn.disabled) return;
        saveBtn.click();
      });
    }

    var cancelBtn = document.getElementById('bs_cancel_btn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        window.location.reload();
      });
    }

    var mobileNav = document.getElementById('bs_mobile_nav');
    if (mobileNav) {
      mobileNav.addEventListener('change', function () {
        var links = document.querySelectorAll('div.pos-tab-menu > div.list-group > a');
        var idx = parseInt(mobileNav.value, 10);
        if (links[idx]) links[idx].click();
      });
    }

    if (typeof $ !== 'undefined') {
      $(document).on('click', 'div.pos-tab-menu > div.list-group > a', function () {
        if (!mobileNav) return;
        mobileNav.value = String($(this).index());
      });
    }

    var form = formEl();
    if (form) {
      form.addEventListener('submit', function () {
        if (saveBtn && !saveBtn.disabled) {
          saveBtn.disabled = true;
          saveBtn.classList.add('is-saving');
          saveBtn.setAttribute('aria-busy', 'true');
          var label = saveBtn.querySelector('.bs-save-label');
          if (label) {
            if (!label.getAttribute('data-orig')) label.setAttribute('data-orig', label.textContent.trim());
            label.textContent = 'Saving...';
          }
        }
        if (headerSave) headerSave.disabled = true;
      });
      form.addEventListener('input', checkDirty);
      form.addEventListener('change', function (e) {
        if (e.target && e.target.type === 'file' && e.target.files && e.target.files.length) {
          setDirty(true);
          return;
        }
        checkDirty();
      });
    }

    if (typeof $ !== 'undefined') {
      $(document).on('ifToggled', '#bussiness_edit_form .input-icheck', checkDirty);
      $(document).on('change', '#bussiness_edit_form select', checkDirty);
      $('#search_settings').on('select2:open', function () {
        var field = document.querySelector('.select2-search__field');
        if (field && !field.getAttribute('placeholder')) {
          field.setAttribute('placeholder', 'Search settings...');
        }
      });
    }
  });
})();
