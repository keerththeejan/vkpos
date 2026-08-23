/**
 * Purchase IMEI / Serial Receiving — full workflow UI
 * Works with purchases[N][serial_numbers][] submitted to PurchaseController.
 */
(function ($) {
  'use strict';

  var checkTimer = {};
  var purchaseId = $('#purchase_id').val() || '';

  function rowIndexFromPanel($panel) {
    var idx = $panel.data('row-index');
    if (idx === undefined || idx === null || idx === '') {
      var $row = $panel.closest('tr');
      var name = $row.find('.purchase_quantity').attr('name') || '';
      var m = name.match(/purchases\[(\d+)\]/);
      idx = m ? m[1] : 0;
    }
    return idx;
  }

  function readQty($row) {
    var $q = $row.find('.purchase_quantity');
    if (typeof __read_number === 'function') {
      return Math.round(__read_number($q, true) || 0);
    }
    return Math.round(parseFloat(($q.val() || '0').toString().replace(/,/g, '')) || 0);
  }

  function toast(type, msg) {
    if (window.toastr) {
      toastr[type](msg);
    } else {
      alert(msg);
    }
  }

  function renumber($panel) {
    $panel.find('.pir-tbody .pir-row').each(function (i) {
      $(this).find('.pir-no').text(i + 1);
    });
  }

  function updateCounters($panel) {
    var $row = $panel.closest('tr');
    var required = readQty($row);
    var entered = 0;
    var values = [];
    $panel.find('.pir-input').each(function () {
      var v = $.trim($(this).val() || '');
      if (v) {
        entered++;
        values.push(v.toLowerCase());
      }
    });
    var remaining = Math.max(required - entered, 0);
    var pct = required > 0 ? Math.min(100, Math.round((entered / required) * 100)) : 0;
    $panel.find('.pir-required').text(required);
    $panel.find('.pir-entered').text(entered);
    $panel.find('.pir-remaining').text(remaining);
    $panel.find('.pir-progress-bar').css('width', pct + '%').text(pct + '%');
    $panel.toggleClass('pir-complete', required > 0 && entered === required);
    $panel.toggleClass('pir-incomplete', required > 0 && entered !== required);

    // Sync sticky summary if present
    var imeiProducts = 0;
    var imeiOk = 0;
    $('#purchase_entry_table tbody tr.purchase_product_row .pir-panel').each(function () {
      imeiProducts++;
      if ($(this).hasClass('pir-complete')) imeiOk++;
    });
    if ($('#pc_imei_required').length) {
      var totReq = 0,
        totEnt = 0;
      $('#purchase_entry_table tbody tr .pir-panel').each(function () {
        totReq += parseInt($(this).find('.pir-required').text(), 10) || 0;
        totEnt += parseInt($(this).find('.pir-entered').text(), 10) || 0;
      });
      $('#pc_imei_required').text(totReq);
      $('#pc_imei_entered').text(totEnt);
      $('#pc_imei_remaining').text(Math.max(totReq - totEnt, 0));
    }
  }

  function setStatus($input, state, remark) {
    var $tr = $input.closest('tr');
    var $st = $tr.find('.pir-status');
    var $rm = $tr.find('.pir-remark');
    $st.removeClass('label-success label-danger label-warning label-default label-info');
    if (state === 'valid') {
      $st.addClass('label label-success').text('Valid');
    } else if (state === 'duplicate') {
      $st.addClass('label label-danger').text('Duplicate');
    } else if (state === 'exists') {
      $st.addClass('label label-warning').text('Already Exists');
    } else if (state === 'empty') {
      $st.addClass('label label-default').text('Empty');
    } else {
      $st.addClass('label label-default').text('…');
    }
    $rm.text(remark || '');
  }

  function isDuplicateInPurchase(value, $exceptInput) {
    var key = value.toLowerCase();
    var found = false;
    $('#purchase_entry_table .pir-input').each(function () {
      if ($exceptInput && this === $exceptInput[0]) return;
      if ($.trim($(this).val() || '').toLowerCase() === key && key !== '') {
        found = true;
        return false;
      }
    });
    return found;
  }

  function validateInput($input) {
    var val = $.trim($input.val() || '');
    if (!val) {
      setStatus($input, 'empty', '');
      updateCounters($input.closest('.pir-panel'));
      return;
    }
    if (isDuplicateInPurchase(val, $input)) {
      setStatus($input, 'duplicate', 'Duplicate in this purchase');
      updateCounters($input.closest('.pir-panel'));
      return;
    }

    var key = 't' + Math.random();
    clearTimeout(checkTimer[key]);
    var timerKey = $input.data('timer-key') || key;
    $input.data('timer-key', timerKey);
    clearTimeout(checkTimer[timerKey]);
    checkTimer[timerKey] = setTimeout(function () {
      $.ajax({
        method: 'POST',
        url: '/product-serial-numbers/check',
        dataType: 'json',
        data: {
          serial_number: val,
          purchase_id: purchaseId || '',
          product_id: $input.closest('tr.purchase_product_row, tr').closest('tr.purchase_product_row').data('product_id') ||
            $input.closest('.pir-panel').closest('tr').data('product_id') || '',
        },
        success: function (res) {
          if (!res.ok) {
            if (res.status === 'exists') {
              setStatus($input, 'exists', res.message || 'Already in stock');
            } else {
              setStatus($input, 'empty', res.message || '');
            }
          } else {
            setStatus($input, 'valid', '');
          }
          updateCounters($input.closest('.pir-panel'));
        },
        error: function () {
          setStatus($input, 'valid', '');
          updateCounters($input.closest('.pir-panel'));
        },
      });
    }, 280);
    updateCounters($input.closest('.pir-panel'));
  }

  function addRow($panel, value) {
    var idx = rowIndexFromPanel($panel);
    var $tbody = $panel.find('.pir-tbody');
    var $tr = $(
      '<tr class="pir-row">' +
        '<td class="pir-no"></td>' +
        '<td><input type="text" class="form-control input-sm pir-input" name="purchases[' +
        idx +
        '][serial_numbers][]" value="" maxlength="100" autocomplete="off"></td>' +
        '<td><span class="pir-status label label-default">…</span></td>' +
        '<td class="pir-remark text-muted"></td>' +
        '<td><i class="fa fa-times text-danger pir-del" style="cursor:pointer"></i></td>' +
        '</tr>'
    );
    $tbody.append($tr);
    renumber($panel);
    var $input = $tr.find('.pir-input');
    if (value) {
      $input.val(value);
      validateInput($input);
    }
    updateCounters($panel);
    return $input;
  }

  function ensureRowCount($panel) {
    var $row = $panel.closest('tr');
    var required = readQty($row);
    var current = $panel.find('.pir-row').length;
    while (current < required) {
      addRow($panel, '');
      current++;
    }
    // Do not auto-delete excess filled rows; only trim empty extras
    while (current > required) {
      var $last = $panel.find('.pir-row').last();
      if ($last.length && !$.trim($last.find('.pir-input').val() || '')) {
        $last.remove();
        current--;
      } else {
        break;
      }
    }
    renumber($panel);
    updateCounters($panel);
  }

  function focusNextEmpty($panel, $from) {
    var $inputs = $panel.find('.pir-input');
    var start = $from ? $inputs.index($from) + 1 : 0;
    for (var i = start; i < $inputs.length; i++) {
      if (!$.trim($($inputs[i]).val() || '')) {
        $($inputs[i]).focus().select();
        return;
      }
    }
    // need another row?
    var $row = $panel.closest('tr');
    if ($inputs.length < readQty($row)) {
      addRow($panel, '').focus();
    }
  }

  function pasteList($panel, text) {
    var lines = (text || '')
      .split(/[\r\n,;\t]+/)
      .map(function (s) {
        return $.trim(s);
      })
      .filter(function (s) {
        return s && !/^(serial|imei|s\/?n)/i.test(s);
      });
    if (!lines.length) return;
    lines.forEach(function (line) {
      var $empty = null;
      $panel.find('.pir-input').each(function () {
        if (!$empty && !$.trim($(this).val() || '')) $empty = $(this);
      });
      if ($empty) {
        $empty.val(line);
        validateInput($empty);
      } else {
        addRow($panel, line);
      }
    });
    ensureRowCount($panel);
    toast('success', lines.length + ' IMEI(s) pasted');
  }

  function importFile($panel, file) {
    if (!file) return;
    var name = (file.name || '').toLowerCase();
    var reader = new FileReader();
    reader.onload = function (e) {
      var text = e.target.result || '';
      // xlsx binary is not parsed without SheetJS — ask CSV/TXT; still try to extract printable lines
      if (/\.xlsx?$/.test(name) && text.indexOf(',') === -1 && text.indexOf('\n') === -1) {
        toast('warning', 'Please export Excel as CSV/TXT for import');
        return;
      }
      pasteList($panel, text);
    };
    reader.readAsText(file);
  }

  function initPanel($panel) {
    if ($panel.data('pir-ready')) {
      updateCounters($panel);
      return;
    }
    $panel.data('pir-ready', 1);
    ensureRowCount($panel);
    $panel.find('.pir-input').each(function () {
      validateInput($(this));
    });
    updateCounters($panel);
  }

  function scanMode($panel) {
    toast('info', 'Scan mode: focus an empty IMEI field and scan. Enter moves to next row.');
    focusNextEmpty($panel, null);
  }

  // Events
  $(document).on('click', '.pir-add', function (e) {
    e.preventDefault();
    var $panel = $(this).closest('.pir-panel');
    addRow($panel, '').focus();
  });

  $(document).on('click', '.pir-clear', function (e) {
    e.preventDefault();
    var $panel = $(this).closest('.pir-panel');
    $panel.find('.pir-tbody').empty();
    ensureRowCount($panel);
  });

  $(document).on('click', '.pir-del', function () {
    var $panel = $(this).closest('.pir-panel');
    $(this).closest('tr').remove();
    renumber($panel);
    ensureRowCount($panel);
  });

  $(document).on('click', '.pir-paste', function (e) {
    e.preventDefault();
    var $panel = $(this).closest('.pir-panel');
    var text = window.prompt('Paste IMEI / Serial list (one per line):', '');
    if (text) pasteList($panel, text);
  });

  $(document).on('click', '.pir-import', function (e) {
    e.preventDefault();
    $(this).closest('.pir-panel').find('.pir-file').trigger('click');
  });

  $(document).on('change', '.pir-file', function () {
    var $panel = $(this).closest('.pir-panel');
    importFile($panel, this.files && this.files[0]);
    $(this).val('');
  });

  $(document).on('click', '.pir-scan', function (e) {
    e.preventDefault();
    scanMode($(this).closest('.pir-panel'));
  });

  $(document).on('input change', '.pir-input', function () {
    validateInput($(this));
  });

  // USB scanners typically send Enter after barcode
  $(document).on('keydown', '.pir-input', function (e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault();
      var $panel = $(this).closest('.pir-panel');
      validateInput($(this));
      focusNextEmpty($panel, $(this));
    }
  });

  $(document).on('change', '.purchase_quantity', function () {
    var $panel = $(this).closest('tr').find('.pir-panel');
    if ($panel.length) ensureRowCount($panel);
  });

  // After product row appended via AJAX
  var _append = window.append_purchase_lines;
  if (typeof _append === 'function') {
    window.append_purchase_lines = function () {
      var result = _append.apply(this, arguments);
      setTimeout(function () {
        $('#purchase_entry_table tbody tr .pir-panel').each(function () {
          initPanel($(this));
        });
      }, 50);
      return result;
    };
  }

  // Block save until IMEI counts match
  $(document).on('submit', 'form#add_purchase_form', function (e) {
    var invalid = false;
    var messages = [];
    $('#purchase_entry_table tbody tr').each(function () {
      var $row = $(this);
      var $panel = $row.find('.pir-panel');
      if (!$panel.length) return;
      var enable = $row.find('.enable_sr_no').val();
      if (String(enable) !== '1') return;
      var required = readQty($row);
      var entered = 0;
      var hasBad = false;
      $panel.find('.pir-input').each(function () {
        var v = $.trim($(this).val() || '');
        if (v) entered++;
        var $st = $(this).closest('tr').find('.pir-status');
        if ($st.hasClass('label-danger') || $st.hasClass('label-warning')) hasBad = true;
      });
      if (entered !== required) {
        invalid = true;
        var name = $row.find('.purchase_row_product_name').val() || 'Product';
        messages.push(name + ': need ' + required + ' IMEI(s), entered ' + entered);
      }
      if (hasBad) {
        invalid = true;
        messages.push('Fix duplicate / existing IMEI values before saving.');
      }
    });
    if (invalid) {
      e.preventDefault();
      toast('error', messages[0] || 'IMEI validation failed');
      return false;
    }
  });

  // Init on page load (edit)
  $(function () {
    $('#purchase_entry_table tbody tr .pir-panel').each(function () {
      initPanel($(this));
    });
  });
})(jQuery);
