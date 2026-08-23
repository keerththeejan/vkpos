/**
 * Expense Create — premium UI helpers only.
 * Does not replace inline create scripts or payment row logic.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_exc_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('exc-dark-mode', enabled);
    document.body.classList.toggle('exc-dark-mode', enabled);
    var icon = document.querySelector('#exc_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function syncMeta() {
    var ref = ($('#ref_no').val() || '').trim() || 'Auto';
    var date = ($('#expense_transaction_date').val() || '').trim() || '—';
    var loc = ($('#location_id option:selected').text() || '').trim() || '—';
    var cat = ($('#expense_category_id option:selected').text() || '').trim() || '—';
    var payee = ($('#expense_for option:selected').text() || '').trim() || '—';
    var contact = ($('#contact_id option:selected').text() || '').trim() || '—';

    $('#exc_meta_ref').text(ref);
    $('#exc_meta_date').text(date);
    $('#exc_meta_location').text(loc);
    $('#exc_meta_category').text(cat);

    $('#exc_live_location').text(loc);
    $('#exc_live_category').text(cat);
    $('#exc_live_payee').text(payee !== '—' ? payee : contact);
  }

  function syncSummary() {
    var total = ($('#final_total').val() || '0').toString();
    var paid = '0';
    try {
      if (typeof __read_number === 'function') {
        paid = String(__read_number($('input.payment-amount')) || 0);
      } else {
        paid = ($('input.payment-amount').first().val() || '0').toString();
      }
    } catch (e) {
      paid = ($('input.payment-amount').first().val() || '0').toString();
    }
    var due = ($('#payment_due').text() || '—').trim();
    var tax = ($('#tax_id option:selected').text() || '').trim() || '—';

    $('#exc_sum_total').text(total);
    $('#exc_sum_paid').text(paid);
    $('#exc_sum_due').text(due);
    $('#exc_sum_tax').text(tax);
    $('#exc_live_total').text(total);
  }

  function triggerSave() {
    var $btn = $('#save_expense_btn');
    if ($btn.length) {
      $btn.trigger('click');
      return;
    }
    var form = document.getElementById('add_expense_form');
    if (form) form.submit();
  }

  ready(function () {
    if (!$('.exc-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#exc_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#exc_header_save, #exc_side_save, #exc_sticky_save').on('click', function (e) {
      e.preventDefault();
      triggerSave();
    });

    $('#exc_refresh_btn').on('click', function () {
      window.location.reload();
    });

    $(document).on(
      'change keyup input',
      '#ref_no, #expense_transaction_date, #location_id, #expense_category_id, #expense_sub_category_id, #expense_for, #contact_id, #final_total, #tax_id, input.payment-amount, .payment_types_dropdown',
      function () {
        syncMeta();
        syncSummary();
      }
    );

    setInterval(function () {
      syncMeta();
      syncSummary();
    }, 800);

    syncMeta();
    syncSummary();

    $(document).on('keydown', function (e) {
      if (!e.key) return;
      var tag = (e.target && e.target.tagName) || '';
      var typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || $(e.target).is('[contenteditable]');

      if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        triggerSave();
        return;
      }

      if (e.key === 'Escape') {
        var cancel = document.getElementById('exc_cancel_link');
        if (cancel) window.location.href = cancel.href;
        return;
      }

      if (e.key === 'F2') {
        e.preventDefault();
        try {
          $('#expense_category_id').select2('open');
        } catch (err) {
          $('#expense_category_id').focus();
        }
      } else if (e.key === 'F3') {
        e.preventDefault();
        try {
          $('#contact_id').select2('open');
        } catch (err) {
          $('#expense_for').focus();
        }
      } else if (e.key === 'F4') {
        e.preventDefault();
        try {
          $('.payment_types_dropdown').first().select2('open');
        } catch (err) {
          $('.payment_types_dropdown').first().focus();
        }
      } else if (e.key === 'F6') {
        e.preventDefault();
        triggerSave();
      } else if (e.key === 'F7') {
        e.preventDefault();
        window.print();
      } else if (e.key === 'F8') {
        e.preventDefault();
        $('#upload_document').trigger('click');
      } else if (e.key === 'F5' && !typing) {
        e.preventDefault();
        triggerSave();
      }
    });
  });
})();
