/**
 * Premium POS UI helpers only.
 * Does NOT alter cart/payment/search/barcode/IMEI business logic in pos.js.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDarkMode(enabled) {
    document.documentElement.classList.toggle('pos-dark-mode', enabled);
    document.body.classList.toggle('pos-dark-mode', enabled);
    var icon = document.querySelector('#pos_dark_mode_toggle i');
    if (icon) {
      icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
    }
  }

  /**
   * Keep UltimatePOS autocomplete; only prevent menu clipping by premium panels /
   * #scrollable-container overflow.
   */
  function repairProductSearchUi() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var $sp = $('#search_product');
    if (!$sp.length) return;

    if ($('input#location_id').val()) {
      $sp.prop('disabled', false);
    }

    function attach() {
      if (!$sp.data('ui-autocomplete')) return false;
      try {
        $sp.autocomplete('option', 'appendTo', 'body');
        var $menu = $sp.autocomplete('widget');
        if ($menu && $menu.length) $menu.css('z-index', 10050);
      } catch (e) {}
      return true;
    }

    if (!attach()) {
      var tries = 0;
      var t = setInterval(function () {
        tries += 1;
        if (attach() || tries > 40) clearInterval(t);
      }, 50);
    }
  }

  function parseMoney(text) {
    if (text == null) return 0;
    var n = parseFloat(
      String(text)
        .replace(/,/g, '')
        .replace(/[^0-9.\-]/g, '')
    );
    return isNaN(n) ? 0 : n;
  }

  function syncKpis() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var items = $('#pos_table tbody tr.product_row').length || $('#pos_table tbody tr').length || 0;
    var qty = $('.total_quantity').first().text() || '0';
    var sub = $('.price_total').first().text() || '0';
    var pay = $('#total_payable').first().text() || $('.total_payable_span').first().text() || '0';
    var elItems = document.getElementById('pos_kpi_items');
    var elQty = document.getElementById('pos_kpi_qty');
    var elSub = document.getElementById('pos_kpi_subtotal');
    var elPay = document.getElementById('pos_kpi_payable');
    if (elItems) elItems.textContent = items;
    if (elQty) elQty.textContent = qty;
    if (elSub) elSub.textContent = sub;
    if (elPay) elPay.textContent = pay;
    var elExcelTotal = document.getElementById('pos_excel_total_display');
    if (elExcelTotal) elExcelTotal.textContent = pay;
    var elExcelPayable = document.getElementById('pos_excel_payable_display');
    if (elExcelPayable) elExcelPayable.textContent = pay;

    var due = $('.contact_due_text span').first().text();
    var dueEl = document.getElementById('pos_cust_due');
    if (dueEl) dueEl.textContent = due && !$('.contact_due_text').hasClass('hide') ? due : '—';
  }

  function syncPaymentSummary() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    var sub = ($('.price_total').first().text() || '0').toString().trim();
    var disc = ($('#total_discount').first().text() || '0').toString().trim();
    $('.pos-pay-subtotal-display').text(sub);
    $('.pos-pay-discount-display').text(disc ? '- ' + disc.replace(/^\-\s*/, '') : '0');

    var paid = parseMoney($('span.total_paying').first().text()) || parseMoney($('#total_paying_input').val());
    var total = parseMoney($('span.total_payable_span').first().text()) || parseMoney($('#final_total_input').val());
    var change = parseMoney($('span.change_return_span').first().text()) || parseMoney($('#change_return').val());
    var bal = parseMoney($('#in_balance_due').val());

    var $card = $('.pos-pay-summary-card');
    if (!$card.length) return;

    $card.removeClass('is-change is-balance-due is-settled');
    if (paid + 0.0001 < total || bal > 0.004) {
      $card.addClass('is-balance-due');
    } else if (change > 0.004 || paid > total + 0.004) {
      $card.addClass('is-change');
    } else {
      $card.addClass('is-settled');
    }
  }

  function focusPaidAmount() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var $amt = $('#modal_payment .payment-amount').filter(':visible').first();
    if (!$amt.length) $amt = $('#amount_0');
    if ($amt.length) {
      $amt.trigger('focus');
      try {
        $amt[0].select();
      } catch (e) {}
    }
  }

  function tickClock() {
    var el = document.getElementById('pos_live_clock');
    if (!el) return;
    try {
      var now = new Date();
      el.textContent = now.toLocaleString();
    } catch (e) {}
  }

  /**
   * Cash quick buttons → write into existing .payment-amount and trigger change
   * so pos.js recalculates totals. No new payment APIs.
   */
  function wireCashQuick() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    $(document).on('click', '#pos_cash_quick_amounts [data-cash-quick]', function (e) {
      e.preventDefault();
      var key = $(this).data('cash-quick');
      var $amt = $('#modal_payment .payment-amount').filter(':visible').first();
      if (!$amt.length) $amt = $('#amount_0');
      if (!$amt.length) return;

      var val;
      if (key === 'exact') {
        var payable =
          ($('#modal_payment .total_payable_span').text() || $('#total_payable').first().text() || '0')
            .toString()
            .replace(/[^0-9.\-]/g, '');
        val = payable || '0';
      } else {
        val = String(key);
      }

      $amt.val(val).trigger('change').trigger('keyup');
      focusPaidAmount();
    });
  }

  /**
   * Hidden buying-price popover. Uses existing cost values already
   * rendered on .pos-view-cost (no new price calculation).
   */
  function wireBuyingPricePopover() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var hideTimer = null;

    function hidePop() {
      $('#pos_cost_popover').removeClass('is-open');
      if (hideTimer) {
        clearTimeout(hideTimer);
        hideTimer = null;
      }
    }

    function ensurePop() {
      var $pop = $('#pos_cost_popover');
      if (!$pop.length) {
        $pop = $(
          '<div id="pos_cost_popover" class="pos-cost-pop" role="tooltip">' +
            '<div class="pos-cost-pop-label">Buying Price</div>' +
            '<div class="pos-cost-pop-value"></div>' +
          '</div>'
        );
        $('body').append($pop);
      }
      return $pop;
    }

    $(document).on('click', '.pos-view-cost', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $btn = $(this);
      var $pop = ensurePop();
      $pop.find('.pos-cost-pop-value').text($btn.attr('data-cost-label') || '—');
      var rect = this.getBoundingClientRect();
      $pop.css({
        top: rect.bottom + 8 + (window.scrollY || window.pageYOffset || 0) + 'px',
        left: Math.max(8, rect.left + (window.scrollX || window.pageXOffset || 0)) + 'px',
      }).addClass('is-open');
      if (hideTimer) clearTimeout(hideTimer);
      hideTimer = setTimeout(hidePop, 4000);
    });

    $(document).on('click', function (e) {
      if ($(e.target).closest('.pos-view-cost, #pos_cost_popover').length) return;
      hidePop();
    });

    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') hidePop();
    });
  }

  ready(function () {
    document.body.classList.add('premium-pos-body');

    // Always use the light, high-contrast POS theme.
    applyDarkMode(false);
    localStorage.setItem('vkpos_pos_dark_mode', '0');

    var toggle = document.getElementById('pos_dark_mode_toggle');
    if (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var next = !document.body.classList.contains('pos-dark-mode');
        localStorage.setItem('vkpos_pos_dark_mode', next ? '1' : '0');
        applyDarkMode(next);
      });
    }

    // Category filter (UI only)
    var catSearch = document.getElementById('pos_category_filter');
    if (catSearch) {
      catSearch.addEventListener('input', function () {
        var q = (this.value || '').toLowerCase().trim();
        document
          .querySelectorAll(
            '.premium-pos-left .pos-cat-card[data-name], .premium-pos-left .main-category-div[data-name], .premium-pos-left .product_category, .premium-pos-left .product_brand'
          )
          .forEach(function (el) {
            var name = (el.getAttribute('data-name') || el.textContent || '').toLowerCase();
            el.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
          });
      });
    }

    // Active category highlight (visual only)
    document.addEventListener('click', function (e) {
      var card = e.target.closest('.main-category, .product_category, .product_brand, .main-category-div.main-category');
      if (!card || !card.closest('.premium-pos-left')) return;

      var parentCard = card.classList.contains('pos-cat-card') ? card : card.closest('.pos-cat-card');
      document.querySelectorAll('.premium-pos-left .pos-cat-card.is-active').forEach(function (el) {
        el.classList.remove('is-active');
      });
      if (parentCard) parentCard.classList.add('is-active');
      else card.classList.add('is-active');
    });

    // Ripple on product cards (visual only; does not stop existing handlers)
    document.addEventListener('click', function (e) {
      var box = e.target.closest('.product_box');
      if (!box) return;
      var ripple = document.createElement('span');
      ripple.className = 'pos-ripple';
      var rect = box.getBoundingClientRect();
      var size = Math.max(rect.width, rect.height);
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
      ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
      box.appendChild(ripple);
      setTimeout(function () {
        ripple.remove();
      }, 500);
    });

    // Network status indicator
    var net = document.getElementById('pos_network_status');
    function syncNet() {
      if (!net) return;
      var online = navigator.onLine;
      net.textContent = online ? 'Online' : 'Offline';
      var dot = document.querySelector('#pos_network_dot');
      if (dot) dot.style.background = online ? 'var(--pos-accent)' : 'var(--pos-danger)';
    }
    window.addEventListener('online', syncNet);
    window.addEventListener('offline', syncNet);
    syncNet();

    repairProductSearchUi();
    if (typeof jQuery !== 'undefined') {
      var $ = jQuery;
      $(document).on('change', '#select_location_id', function () {
        setTimeout(repairProductSearchUi, 100);
      });

      $('#modal_payment').on('shown.bs.modal', function () {
        syncPaymentSummary();
        setTimeout(focusPaidAmount, 50);
      });

      $(document).on('click', '#modal_payment .pos-pay-summary-card, #modal_payment .pos-paid-amount-heading', function (e) {
        if ($(e.target).is('input, select, textarea, button, a')) return;
        focusPaidAmount();
      });

      $(document).on('change keyup', '#modal_payment .payment-amount', function () {
        setTimeout(syncPaymentSummary, 30);
      });
    }

    wireCashQuick();
    wireBuyingPricePopover();
    tickClock();
    setInterval(tickClock, 30000);
    syncKpis();
    syncPaymentSummary();
    setInterval(function () {
      syncKpis();
      syncPaymentSummary();
    }, 400);

    var receipt = document.getElementById('receipt_section');
    if (receipt) {
      receipt.classList.remove('thermal-preview');
      if (window.MutationObserver) {
        new MutationObserver(function () {
          receipt.classList.remove('thermal-preview');
        }).observe(receipt, { attributes: true, attributeFilter: ['class'] });
      }
      window.addEventListener('afterprint', function () {
        receipt.classList.remove('thermal-preview');
        if (document.body.classList.contains('lockscreen') || document.body.classList.contains('premium-pos-body')) {
          receipt.innerHTML = '';
        }
      });
    }
  });
})();
