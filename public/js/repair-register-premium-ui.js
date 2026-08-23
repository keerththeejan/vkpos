/**
 * Repair Cash Register / Service POS gateway — UI helpers only.
 * Does not alter #add_cash_register_form submit or field names.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_rr_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('rr-dark-mode', enabled);
    document.body.classList.toggle('rr-dark-mode', enabled);
    var icon = document.querySelector('#rr_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  ready(function () {
    // Open-register page helpers
    if ($('.rr-shell').length) {
      var dark = false;
      try {
        dark = localStorage.getItem(STORAGE_KEY) === '1';
      } catch (e) {}
      applyDark(dark);

      $('#rr_dark_mode_toggle').on('click', function () {
        dark = !dark;
        applyDark(dark);
        try {
          localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
        } catch (e) {}
      });

      $('.rr-quick-btn').on('click', function () {
        var amount = $(this).data('amount');
        $('.rr-quick-btn').removeClass('is-active');
        $(this).addClass('is-active');
        var $amount = $('#amount');
        if (!$amount.length) return;
        if (amount === '' || amount === null || typeof amount === 'undefined') {
          $amount.val('').trigger('change').focus();
        } else {
          $amount.val(String(amount)).trigger('change').focus();
        }
      });
    }

    // Service POS: mark shell when repair sub_type is present
    var $sub = $('input[name="sub_type"]');
    if ($sub.length && String($sub.val() || '').toLowerCase() === 'repair') {
      $('.premium-pos-shell').addClass('is-repair-service');
      if (!$('.rr-pos-banner').length && $('.premium-pos-shell').length) {
        var banner =
          '<div class="rr-pos-banner" role="status">' +
          '<div><strong><i class="fas fa-wrench"></i> Repair Service Checkout</strong>' +
          '<br><span>Device · IMEI · Warranty · Status fields use existing repair logic</span></div>' +
          '<span class="rr-pos-badge"><i class="fas fa-cash-register"></i> Service POS</span>' +
          '</div>';
        $('.premium-pos-shell').prepend(banner);
      }

      // Wrap repair module field block visually if present
      var $status = $('#repair_status_id');
      if ($status.length && !$('.rr-repair-section').length) {
        var $blockStart = $status.closest('.row');
        // Find the first repair row (due date) by looking upward for repair_due_date
        var $due = $('#repair_due_date').closest('.row');
        if ($due.length) {
          $due.addClass('rr-repair-anchor');
        }
      }
    }
  });
})();
