/**
 * IMEI / Serial Number Management (product create & edit)
 * Submits values as product_serial_numbers[] — additive field only.
 */
(function () {
  'use strict';

  var MAX_LEN = 100;
  var state = {
    items: [], // { id, value, status, locked }
    filter: '',
    nextId: 1,
  };

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeValue(raw) {
    return String(raw || '').trim().replace(/\s+/g, '');
  }

  /** Valid: 1–100 chars alphanumeric + common serial separators */
  function validateSerial(value) {
    if (!value) return { ok: false, reason: 'empty' };
    if (value.length > MAX_LEN) return { ok: false, reason: 'length' };
    if (!/^[A-Za-z0-9\-_./]+$/.test(value)) return { ok: false, reason: 'chars' };
    // Digits-only that look like IMEI attempt but wrong length
    if (/^\d+$/.test(value) && value.length !== 15 && (value.length === 14 || value.length === 16 || value.length === 17)) {
      return { ok: false, reason: 'imei_length', warn: true };
    }
    return { ok: true };
  }

  function isImeiLike(value) {
    return /^\d{15}$/.test(value);
  }

  function showAlert(type, message) {
    var $el = $('#psn_alert');
    if (!$el.length) return;
    $el
      .removeClass('psn-alert-success psn-alert-warning psn-alert-danger')
      .addClass('psn-alert-' + type)
      .html(message)
      .show();
    clearTimeout(showAlert._t);
    showAlert._t = setTimeout(function () {
      $el.fadeOut(200);
    }, 5000);
  }

  function loadInitial() {
    var el = document.getElementById('psn_initial_data');
    if (!el) return;
    try {
      var data = JSON.parse(el.textContent || '[]');
      if (!Array.isArray(data)) return;
      data.forEach(function (row) {
        var value = normalizeValue(row.value);
        if (!value) return;
        state.items.push({
          id: state.nextId++,
          value: value,
          status: row.status || 'available',
          locked: !!row.locked,
        });
      });
    } catch (e) {
      /* ignore */
    }
  }

  function findDuplicates() {
    var seen = {};
    var dups = {};
    state.items.forEach(function (item) {
      var key = item.value.toLowerCase();
      if (!key) return;
      if (seen[key]) dups[key] = true;
      else seen[key] = true;
    });
    return dups;
  }

  function updateCounters() {
    var dups = findDuplicates();
    var total = 0;
    var available = 0;
    var locked = 0;
    var invalid = 0;

    state.items.forEach(function (item) {
      var v = item.value;
      if (!v) return;
      total++;
      if (item.locked) locked++;
      else available++;
      var val = validateSerial(v);
      if (!val.ok || dups[v.toLowerCase()]) invalid++;
    });

    $('#psn_total_count').text(total);
    $('#psn_available_count').text(available);
    $('#psn_locked_count').text(locked);
    $('#psn_invalid_count').text(invalid);

    var $progress = $('#psn_progress_label');
    if (invalid > 0) {
      $progress.text(invalid + ' issue(s)').addClass('is-warn');
    } else if (total > 0) {
      $progress.text(total + ' ready').removeClass('is-warn').addClass('is-ok');
    } else {
      $progress.text('Ready').removeClass('is-warn is-ok');
    }
  }

  function renderList() {
    var $list = $('#psn_list');
    if (!$list.length) return;

    var filter = (state.filter || '').toLowerCase();
    var dups = findDuplicates();
    var frag = document.createDocumentFragment();
    var visible = 0;

    state.items.forEach(function (item, index) {
      if (filter && item.value.toLowerCase().indexOf(filter) === -1) return;
      visible++;

      var val = validateSerial(item.value);
      var isDup = item.value && dups[item.value.toLowerCase()];
      var classes = ['psn-row'];
      if (item.locked) classes.push('is-locked');
      if (!val.ok && item.value) classes.push('is-invalid');
      if (isDup) classes.push('is-duplicate');
      if (isImeiLike(item.value)) classes.push('is-imei');

      var row = document.createElement('div');
      row.className = classes.join(' ');
      row.setAttribute('data-id', item.id);
      row.setAttribute('role', 'listitem');

      var badge = item.locked
        ? '<span class="psn-status-badge status-' + escapeHtml(item.status) + '">' + escapeHtml(item.status) + '</span>'
        : '<span class="psn-status-badge status-available">available</span>';

      var label = isImeiLike(item.value) ? 'IMEI' : 'Serial';
      var hint = '';
      if (isDup) hint = '<span class="psn-row-hint">Duplicate</span>';
      else if (!val.ok && item.value && val.reason === 'imei_length') hint = '<span class="psn-row-hint">IMEI should be 15 digits</span>';
      else if (!val.ok && item.value && val.reason === 'chars') hint = '<span class="psn-row-hint">Invalid characters</span>';
      else if (!val.ok && item.value && val.reason === 'length') hint = '<span class="psn-row-hint">Max 100 characters</span>';

      row.innerHTML =
        '<div class="psn-row-index">' +
        (index + 1) +
        '</div>' +
        '<div class="psn-row-body">' +
        '<label class="psn-row-label">' +
        label +
        ' ' +
        (index + 1) +
        '</label>' +
        '<input type="text" class="form-control psn-input" name="product_serial_numbers[]" maxlength="' +
        MAX_LEN +
        '" value="' +
        escapeHtml(item.value) +
        '" ' +
        (item.locked ? 'readonly' : '') +
        ' autocomplete="off" spellcheck="false">' +
        hint +
        '</div>' +
        '<div class="psn-row-meta">' +
        badge +
        (item.locked
          ? ''
          : '<button type="button" class="btn btn-link btn-xs psn-remove" title="Remove"><i class="fas fa-times"></i></button>') +
        '</div>';

      frag.appendChild(row);
    });

    $list.empty().append(frag);

    if (state.items.length === 0) {
      $list.html(
        '<div class="psn-empty">' +
          '<i class="fas fa-fingerprint"></i>' +
          '<p>No serial numbers yet. Add manually, paste a list, or import from Excel/CSV.</p>' +
          '</div>'
      );
    } else if (visible === 0) {
      $list.html('<div class="psn-empty"><p>No matches for your search.</p></div>');
    }

    updateCounters();
  }

  function addItems(values, opts) {
    opts = opts || {};
    var added = 0;
    var skippedDup = 0;
    var skippedInvalid = 0;
    var existing = {};
    state.items.forEach(function (item) {
      if (item.value) existing[item.value.toLowerCase()] = true;
    });

    values.forEach(function (raw) {
      var value = normalizeValue(raw);
      if (!value) return;
      if (value.length > MAX_LEN) value = value.substring(0, MAX_LEN);
      var val = validateSerial(value);
      if (!val.ok && val.reason !== 'imei_length') {
        // Still allow imei_length warn items; reject empty/chars
        if (val.reason === 'chars') {
          skippedInvalid++;
          return;
        }
      }
      var key = value.toLowerCase();
      if (existing[key]) {
        skippedDup++;
        return;
      }
      existing[key] = true;
      state.items.push({
        id: state.nextId++,
        value: value,
        status: 'available',
        locked: false,
      });
      added++;
    });

    renderList();

    if (opts.silent) return { added: added, skippedDup: skippedDup, skippedInvalid: skippedInvalid };

    var parts = [];
    if (added) parts.push('<strong>' + added + '</strong> added');
    if (skippedDup) parts.push('<strong>' + skippedDup + '</strong> duplicate(s) skipped');
    if (skippedInvalid) parts.push('<strong>' + skippedInvalid + '</strong> invalid skipped');
    if (parts.length) {
      showAlert(skippedDup || skippedInvalid ? 'warning' : 'success', parts.join(' · '));
    }
    return { added: added, skippedDup: skippedDup, skippedInvalid: skippedInvalid };
  }

  function addEmptyRow() {
    state.items.push({
      id: state.nextId++,
      value: '',
      status: 'available',
      locked: false,
    });
    state.filter = '';
    $('#psn_search').val('');
    renderList();
    var $last = $('#psn_list .psn-input').last();
    if ($last.length) $last.focus();
  }

  function parseLines(text) {
    return String(text || '')
      .replace(/\r\n/g, '\n')
      .replace(/\r/g, '\n')
      .split('\n')
      .map(function (l) {
        // also support comma/tab separated first column
        var line = l.trim();
        if (!line) return '';
        if (line.indexOf('\t') !== -1) line = line.split('\t')[0].trim();
        else if (line.indexOf(',') !== -1 && !/^[A-Za-z0-9\-_./]+$/.test(line)) {
          line = line.split(',')[0].trim().replace(/^"|"$/g, '');
        }
        return line;
      })
      .filter(Boolean);
  }

  function parseCsvText(text) {
    var lines = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
    var out = [];
    var start = 0;
    if (lines.length && /serial|imei|s\/?n/i.test(lines[0])) start = 1;
    for (var i = start; i < lines.length; i++) {
      var line = lines[i].trim();
      if (!line) continue;
      var first = line.split(/[,;\t]/)[0].trim().replace(/^"|"$/g, '');
      if (first) out.push(first);
    }
    return out;
  }

  function loadSheetJs(cb) {
    if (window.XLSX) {
      cb(null);
      return;
    }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    s.onload = function () {
      cb(null);
    };
    s.onerror = function () {
      cb(new Error('Failed to load Excel parser'));
    };
    document.head.appendChild(s);
  }

  function handleImportFile(file) {
    if (!file) return;
    var name = (file.name || '').toLowerCase();
    $('#psn_progress_label').text('Importing...').removeClass('is-ok').addClass('is-warn');

    if (name.endsWith('.csv') || name.endsWith('.txt')) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var values = parseCsvText(e.target.result);
        addItems(values);
      };
      reader.readAsText(file);
      return;
    }

    if (name.endsWith('.xlsx') || name.endsWith('.xls')) {
      loadSheetJs(function (err) {
        if (err) {
          showAlert('danger', 'Could not load Excel importer. Please use CSV or Paste Multiple.');
          updateCounters();
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          try {
            var data = new Uint8Array(e.target.result);
            var wb = XLSX.read(data, { type: 'array' });
            var sheet = wb.Sheets[wb.SheetNames[0]];
            var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
            var values = [];
            var start = 0;
            if (rows.length && rows[0] && /serial|imei|s\/?n/i.test(String(rows[0][0] || ''))) start = 1;
            for (var i = start; i < rows.length; i++) {
              var cell = rows[i] && rows[i][0] != null ? String(rows[i][0]).trim() : '';
              if (cell) values.push(cell);
            }
            addItems(values);
          } catch (ex) {
            showAlert('danger', 'Could not read Excel file. Try CSV format.');
            updateCounters();
          }
        };
        reader.readAsArrayBuffer(file);
      });
      return;
    }

    showAlert('warning', 'Unsupported file type. Use .csv, .xlsx, .xls or .txt');
    updateCounters();
  }

  function setVisible(show) {
    var $card = $('#psn_management_card');
    if (!$card.length) return;
    if (show) {
      $card.stop(true, true).slideDown(200).attr('aria-hidden', 'false');
      if (state.items.length === 0) {
        // ensure at least one empty row for quick entry
        addEmptyRow();
      } else {
        renderList();
      }
    } else {
      $card.stop(true, true).slideUp(150).attr('aria-hidden', 'true');
    }
  }

  // Expose for product-tracking-ui.js
  window.PSNManagement = {
    setVisible: setVisible,
    refresh: renderList,
  };

  ready(function () {
    if (!$('#psn_management_card').length) return;

    loadInitial();

    var $enable = $('#enable_sr_no');
    var enabled = $enable.length && $enable.prop('checked');
    if (enabled) {
      $('#psn_management_card').show().attr('aria-hidden', 'false');
      renderList();
      if (state.items.length === 0) addEmptyRow();
    }

    $(document).on('ifChecked', '#enable_sr_no', function () {
      setVisible(true);
    });
    $(document).on('ifUnchecked', '#enable_sr_no', function () {
      setVisible(false);
    });
    $(document).on('change', '#enable_sr_no', function () {
      setVisible(this.checked);
    });

    $('#psn_add_row').on('click', function (e) {
      e.preventDefault();
      addEmptyRow();
    });

    $('#psn_clear_available').on('click', function (e) {
      e.preventDefault();
      if (!confirm('Remove all available (unlocked) serial numbers from this list?')) return;
      state.items = state.items.filter(function (item) {
        return item.locked;
      });
      renderList();
      showAlert('success', 'Available serials cleared.');
    });

    $('#psn_search').on('input', function () {
      state.filter = $(this).val() || '';
      renderList();
    });

    $('#psn_list').on('input', '.psn-input', function () {
      var id = parseInt($(this).closest('.psn-row').attr('data-id'), 10);
      var value = normalizeValue($(this).val());
      // keep typing spaces until blur for UX — use raw for display while typing
      var raw = $(this).val();
      for (var i = 0; i < state.items.length; i++) {
        if (state.items[i].id === id) {
          state.items[i].value = String(raw || '').trim();
          break;
        }
      }
      updateCounters();
      // soft re-validate classes without full re-render (keeps focus)
      var $row = $(this).closest('.psn-row');
      var v = validateSerial(normalizeValue(raw));
      var dups = findDuplicates();
      var key = normalizeValue(raw).toLowerCase();
      $row.toggleClass('is-invalid', !!(raw && !v.ok));
      $row.toggleClass('is-duplicate', !!(key && dups[key]));
      $row.toggleClass('is-imei', isImeiLike(normalizeValue(raw)));
    });

    $('#psn_list').on('blur', '.psn-input', function () {
      var id = parseInt($(this).closest('.psn-row').attr('data-id'), 10);
      var value = normalizeValue($(this).val());
      $(this).val(value);
      for (var i = 0; i < state.items.length; i++) {
        if (state.items[i].id === id) {
          state.items[i].value = value;
          break;
        }
      }
      renderList();
    });

    $('#psn_list').on('click', '.psn-remove', function (e) {
      e.preventDefault();
      var id = parseInt($(this).closest('.psn-row').attr('data-id'), 10);
      state.items = state.items.filter(function (item) {
        return item.id !== id;
      });
      renderList();
    });

    $('#psn_paste_apply').on('click', function () {
      var values = parseLines($('#psn_paste_textarea').val());
      addItems(values);
      $('#psn_paste_textarea').val('');
      $('#psn_paste_modal').modal('hide');
    });

    $('#psn_import_btn').on('click', function (e) {
      e.preventDefault();
      $('#psn_import_file').val('').trigger('click');
    });

    $('#psn_import_file').on('change', function () {
      var file = this.files && this.files[0];
      handleImportFile(file);
      $(this).val('');
    });

    // Before submit: pack JSON for bulk save + strip empty array inputs
    $('#product_add_form').on('submit', function () {
      if (!$('#enable_sr_no').prop('checked')) {
        $('#psn_list input[name="product_serial_numbers[]"]').prop('disabled', true);
        $('#product_serial_numbers_json').val('');
        return;
      }

      // Sync DOM → state first
      $('#psn_list .psn-input').each(function () {
        var id = parseInt($(this).closest('.psn-row').attr('data-id'), 10);
        var value = normalizeValue($(this).val());
        for (var i = 0; i < state.items.length; i++) {
          if (state.items[i].id === id) {
            state.items[i].value = value;
            break;
          }
        }
      });

      var payload = [];
      var seen = {};
      state.items.forEach(function (item) {
        var v = normalizeValue(item.value);
        if (!v) return;
        var k = v.toLowerCase();
        if (seen[k]) return;
        seen[k] = true;
        payload.push(v);
      });

      $('#product_serial_numbers_json').val(JSON.stringify(payload));
      // Disable individual inputs so only JSON is used (prevents max_input_vars blowups)
      $('#psn_list input[name="product_serial_numbers[]"]').prop('disabled', true);
    });
  });
})();
