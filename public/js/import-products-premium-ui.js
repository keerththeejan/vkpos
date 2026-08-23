/**
 * Import Products — premium UI helpers only.
 * Preserves form POST, field name products_csv, and submit behavior.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_ip_dark_mode';
  var currentStep = 1;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('ip-dark-mode', enabled);
    document.body.classList.toggle('ip-dark-mode', enabled);
    var icon = document.querySelector('#ip_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
  }

  function isAllowedFile(file) {
    if (!file) return false;
    var name = String(file.name || '').toLowerCase();
    return (
      name.endsWith('.xls') ||
      name.endsWith('.xlsx') ||
      name.endsWith('.csv')
    );
  }

  function updateFileUi(file) {
    var $zone = $('#ip_dropzone');
    var $name = $('#ip_file_name');
    var $meta = $('#ip_file_meta');
    var $continue = $('#ip_goto_step2');

    if (file && isAllowedFile(file)) {
      $zone.addClass('has-file');
      $name.text(file.name);
      $meta.text(formatBytes(file.size));
      $('#ip_kpi_file_status').text('Ready');
      $('#ip_kpi_file_hint').text(file.name);
      $('#ip_preview_filename, #ip_final_filename').text(file.name);
      $('#ip_preview_size').text(formatBytes(file.size));
      $('#ip_preview_type').text((file.name.split('.').pop() || '').toUpperCase());
      $continue.prop('disabled', false);
    } else {
      $zone.removeClass('has-file');
      $name.text('No file selected');
      $meta.text('');
      $('#ip_kpi_file_status').text('Waiting');
      $('#ip_kpi_file_hint').text('No file selected');
      $('#ip_preview_filename, #ip_final_filename').text('—');
      $('#ip_preview_size').text('—');
      $('#ip_preview_type').text('—');
      $continue.prop('disabled', true);
      if (file && !isAllowedFile(file)) {
        if (typeof toastr !== 'undefined') {
          toastr.error('Please select a .xls, .xlsx, or .csv file');
        }
      }
    }
  }

  function goToStep(step) {
    step = parseInt(step, 10);
    if (step < 1 || step > 4) return;

    // Require file before leaving step 1
    var input = document.getElementById('products_csv');
    var hasFile = input && input.files && input.files.length > 0 && isAllowedFile(input.files[0]);
    if (step > 1 && !hasFile) {
      if (typeof toastr !== 'undefined') {
        toastr.warning('Please choose an import file first');
      }
      step = 1;
    }

    currentStep = step;

    $('.ip-panel').hide().removeClass('is-active');
    $('#ip_panel_' + step)
      .show()
      .addClass('is-active');

    $('.ip-step').each(function () {
      var s = parseInt($(this).attr('data-step'), 10);
      $(this).toggleClass('is-active', s === step);
      $(this).toggleClass('is-done', s < step);
    });

    if (step >= 3) {
      var file = input && input.files && input.files[0] ? input.files[0] : null;
      updateFileUi(file);
    }
  }

  ready(function () {
    if (!$('.ip-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#ip_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#ip_refresh_page').on('click', function () {
      window.location.reload();
    });

    $('#ip_help_toggle, #ip_toggle_instructions').on('click', function () {
      var $card = $('#ip_instructions_card');
      $card.toggleClass('is-collapsed');
      var collapsed = $card.hasClass('is-collapsed');
      $('#ip_toggle_instructions i').attr(
        'class',
        collapsed ? 'fas fa-chevron-down' : 'fas fa-chevron-up'
      );
      if (!collapsed) {
        $('html, body').animate(
          { scrollTop: $card.offset().top - 80 },
          300
        );
      }
    });

    var $input = $('#products_csv');
    $input.on('change', function () {
      var file = this.files && this.files[0] ? this.files[0] : null;
      if (file && !isAllowedFile(file)) {
        this.value = '';
        updateFileUi(null);
        return;
      }
      updateFileUi(file);
    });

    var zone = document.getElementById('ip_dropzone');
    if (zone) {
      ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          zone.classList.add('is-dragover');
        });
      });
      ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          zone.classList.remove('is-dragover');
        });
      });
      zone.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || !files.length) return;
        if (!isAllowedFile(files[0])) {
          if (typeof toastr !== 'undefined') {
            toastr.error('Please select a .xls, .xlsx, or .csv file');
          }
          return;
        }
        try {
          var dt = new DataTransfer();
          dt.items.add(files[0]);
          $input[0].files = dt.files;
          $input.trigger('change');
        } catch (err) {
          // Fallback: user must click to browse
        }
      });
    }

    $('#ip_remove_file').on('click', function () {
      if ($input.length) {
        $input.val('');
        updateFileUi(null);
      }
      goToStep(1);
    });

    $('#ip_goto_step2').on('click', function () {
      goToStep(2);
    });

    $(document).on('click', '[data-ip-goto]', function () {
      goToStep($(this).attr('data-ip-goto'));
    });

    // Soft progress UI only — does not change request
    $('#ip_import_form').on('submit', function () {
      var input = document.getElementById('products_csv');
      if (!input || !input.files || !input.files.length) {
        goToStep(1);
        return false;
      }
      $('#ip_progress_panel').show();
      var btn = document.getElementById('ip_submit_import');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing…';
      }
    });
  });
})();
