/**
 * Repair Dashboard — premium UI helpers only.
 * Does not alter chart scripts or backend data.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_rd_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('rd-dark-mode', enabled);
    document.body.classList.toggle('rd-dark-mode', enabled);
    var icon = document.querySelector('#rd_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-count'), 10);
    if (isNaN(target)) return;
    var duration = 700;
    var start = 0;
    var startTime = null;

    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(start + (target - start) * eased);
      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  ready(function () {
    if (!$('.rd-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#rd_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#rd_print_page').on('click', function () {
      window.print();
    });

    $('#rd_refresh_page').on('click', function () {
      window.location.reload();
    });

    $('.rd-counter').each(function () {
      animateCounter(this);
    });

    var searchTimer = null;
    $('#rd_quick_search').on('input', function () {
      var q = ($(this).val() || '').toLowerCase().trim();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        $('#rd_status_board .rd-status-card').each(function () {
          var name = ($(this).attr('data-status-name') || '').toLowerCase();
          var visible = !q || name.indexOf(q) !== -1;
          $(this).toggle(visible);
        });
      }, 150);
    });
  });
})();
