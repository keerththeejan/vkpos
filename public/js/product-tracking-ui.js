/**
 * Product tracking panel UI only.
 * Syncs visual switch/preview with existing #enable_sr_no checkbox (incl. iCheck).
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function syncUi(checked) {
    var card = document.getElementById('pt_tracking_card');
    var label = document.getElementById('pt_switch_state_label');
    var control = document.getElementById('pt_switch_control');
    if (card) card.classList.toggle('is-enabled', !!checked);
    if (label) label.textContent = checked ? 'Enabled' : 'Disabled';
    if (control) control.setAttribute('aria-checked', checked ? 'true' : 'false');
  }

  function isChecked($input) {
    if (!$input.length) return false;
    return $input.prop('checked') === true;
  }

  function toggleCheckbox($input) {
    if (!$input.length) return;
    if (typeof $input.iCheck === 'function') {
      if (isChecked($input)) {
        $input.iCheck('uncheck');
      } else {
        $input.iCheck('check');
      }
    } else {
      $input.prop('checked', !isChecked($input)).trigger('change');
      syncUi(isChecked($input));
    }
  }

  ready(function () {
    var $input = $('#enable_sr_no');
    if (!$input.length) return;

    syncUi(isChecked($input));

    // Click anywhere on the custom switch control
    $(document).on('click', '#pt_switch_control', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleCheckbox($('#enable_sr_no'));
    });

    // Keyboard support
    $(document).on('keydown', '#pt_switch_control', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleCheckbox($('#enable_sr_no'));
      }
    });

    // iCheck events (UltimatePOS uses these)
    $(document).on('ifChecked', '#enable_sr_no', function () {
      syncUi(true);
    });
    $(document).on('ifUnchecked', '#enable_sr_no', function () {
      syncUi(false);
    });

    // Fallback native change
    $(document).on('change', '#enable_sr_no', function () {
      syncUi(this.checked);
    });

    // Description character counter (UI only)
    var $desc = $('#product_description');
    var $counter = $('#pt_description_counter');
    if ($desc.length && $counter.length) {
      var updateCount = function () {
        var len = ($desc.val() || '').length;
        $counter.text(len + ' characters');
      };
      $desc.on('input keyup change', updateCount);
      updateCount();
    }
  });
})();
