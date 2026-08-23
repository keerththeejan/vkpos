/**
 * Sales Import — premium UI helpers only.
 * Does not alter preview/import endpoints or validation rules.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_is_dark_mode';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('is-dark-mode', enabled);
    document.body.classList.toggle('is-dark-mode', enabled);
    var icon = document.querySelector('#is_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function updateFileMeta(input) {
    var meta = document.getElementById('is_file_meta');
    if (!meta || !input || !input.files || !input.files[0]) {
      if (meta) meta.textContent = '';
      return;
    }
    var f = input.files[0];
    var mb = (f.size / (1024 * 1024)).toFixed(2);
    meta.textContent = f.name + ' · ' + mb + ' MB';
  }

  ready(function () {
    if (!$('.is-shell').length) return;

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#is_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#is_refresh_page').on('click', function () {
      window.location.reload();
    });

    var $file = $('input[name="sales"]');
    var $zone = $('#is_upload_zone');

    $file.on('change', function () {
      updateFileMeta(this);
    });

    if ($zone.length && $file.length) {
      $zone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $zone.addClass('is-dragover');
      });
      $zone.on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $zone.removeClass('is-dragover');
      });
      $zone.on('drop', function (e) {
        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length) {
          try {
            $file[0].files = dt.files;
          } catch (err) {}
          updateFileMeta($file[0]);
        }
      });
      $('#is_browse_file').on('click', function (e) {
        e.preventDefault();
        $file.trigger('click');
      });
    }

    // KPI: count history batches / invoices already rendered
    var batches = $('#is_import_history_body tr').length;
    var invoices = 0;
    $('#is_import_history_body tr').each(function () {
      var t = $(this).find('small').text() || '';
      var m = t.match(/(\d+)/);
      if (m) invoices += parseInt(m[1], 10) || 0;
    });
    $('#is_kpi_batches').text(batches || '0');
    $('#is_kpi_invoices').text(invoices || '0');
  });
})();
