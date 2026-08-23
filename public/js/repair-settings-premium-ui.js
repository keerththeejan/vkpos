/**
 * Repair Settings — premium UI helpers only.
 * Reuses existing Bootstrap tabs + forms; does not alter save logic.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_rs_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('rs-dark-mode', enabled);
    document.body.classList.toggle('rs-dark-mode', enabled);
    var icon = document.querySelector('#rs_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function setActiveNav(href) {
    $('.rs-nav-item[data-rs-tab]').removeClass('active');
    $('.rs-nav-item[data-rs-tab="' + href + '"]').addClass('active');
    var label = $('.rs-nav-item[data-rs-tab="' + href + '"]').text().trim() || href;
    $('#rs_preview_tab').text(label);
  }

  ready(function () {
    if (!$('.rs-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#rs_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    // Side nav triggers existing Bootstrap tabs
    $(document).on('click', '.rs-nav-item[data-rs-tab]', function (e) {
      e.preventDefault();
      var href = $(this).attr('data-rs-tab');
      $('a[href="' + href + '"][data-toggle="tab"]').tab('show');
      setActiveNav(href);
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      var href = $(e.target).attr('href');
      setActiveNav(href);
    });

    // Save button submits the visible settings form (if any)
    $('#rs_save_active').on('click', function () {
      var $pane = $('.tab-pane.active');
      var $form = $pane.find('form').first();
      if ($form.length) {
        var $btn = $form.find('button[type="submit"], input[type="submit"]').first();
        if ($btn.length) $btn.trigger('click');
        else $form.trigger('submit');
      } else {
        if (typeof toastr !== 'undefined') {
          toastr.info('Open Repair Settings or Job Sheet PDF tab to save.');
        }
      }
    });

    // Search highlights / filters section blocks in settings tabs
    var searchTimer = null;
    $('#rs_quick_search').on('input', function () {
      var q = ($(this).val() || '').toLowerCase().trim();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        $('.rs-section-block').each(function () {
          var hay = (($(this).attr('data-rs-search') || '') + ' ' + $(this).text()).toLowerCase();
          var match = !q || hay.indexOf(q) !== -1;
          $(this).toggleClass('rs-search-hide', !match);
          $(this).toggleClass('rs-search-hit', !!q && match);
        });

        // Also filter nav items loosely
        if (q) {
          // Prefer switching to settings tab when searching form content
          if ($('.rs-section-block.rs-search-hit').length) {
            var $hit = $('.rs-section-block.rs-search-hit').first();
            var inRepair = $hit.closest('#repair_settings_tab').length;
            var inJob = $hit.closest('#jobsheet_settings_tab').length;
            if (inRepair) $('a[href="#repair_settings_tab"][data-toggle="tab"]').tab('show');
            else if (inJob) $('a[href="#jobsheet_settings_tab"][data-toggle="tab"]').tab('show');
          }
        } else {
          $('.rs-section-block').removeClass('rs-search-hide rs-search-hit');
        }
      }, 180);
    });
  });
})();
