/**
 * Update Product Price — premium UI helpers only.
 * Does not alter export/import endpoints, field names, or save logic.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_upp_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('upp-dark-mode', enabled);
    document.body.classList.toggle('upp-dark-mode', enabled);
    var icon = document.querySelector('#upp_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function updateFileUi(file) {
    var $zone = $('#upp_dropzone');
    var $name = $('#upp_file_name');
    var $preview = $('#upp_preview_panel');
    var $previewName = $('#upp_preview_filename');

    if (file) {
      $zone.addClass('has-file');
      $name.text(file.name);
      $previewName.text(file.name);
      $preview.addClass('is-visible');
    } else {
      $zone.removeClass('has-file');
      $name.text('No file selected');
      $previewName.text('—');
      $preview.removeClass('is-visible');
    }
  }

  ready(function () {
    if (!$('.upp-shell').length) return;

    // Dark mode
    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#upp_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#upp_refresh_page').on('click', function () {
      window.location.reload();
    });

    $('#upp_print_page').on('click', function () {
      window.print();
    });

    // File input UI (same #product_group_prices / name=product_group_prices)
    var $input = $('#product_group_prices');
    if ($input.length) {
      $input.on('change', function () {
        var file = this.files && this.files[0] ? this.files[0] : null;
        updateFileUi(file);
      });

      var zone = document.getElementById('upp_dropzone');
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
          // Assign to native input so form submit still works
          try {
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            $input[0].files = dt.files;
            $input.trigger('change');
          } catch (err) {
            // Older browsers: user must click to select
          }
        });
      }
    }

    $('#upp_reset_file').on('click', function () {
      if ($input.length) {
        $input.val('');
        updateFileUi(null);
      }
    });

    // Client-side page search (filters visible cards only)
    $('#upp_page_search').on('input', function () {
      var q = String($(this).val() || '')
        .toLowerCase()
        .trim();
      $('[data-upp-search]').each(function () {
        var hay = String($(this).attr('data-upp-search') || '').toLowerCase();
        var text = $(this).text().toLowerCase();
        var match = !q || hay.indexOf(q) !== -1 || text.indexOf(q) !== -1;
        $(this).toggleClass('is-hidden-by-search', !match);
      });
    });

    // Soft loading state on submit — does not change request
    $('#upp_import_form').on('submit', function () {
      var btn = document.getElementById('upp_submit_import');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';
      }
    });
  });
})();
