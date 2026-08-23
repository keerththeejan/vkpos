/**
 * Taxonomy / Product Categories — premium UI helpers only.
 * Reuses existing #category_table DataTable and taxonomies_js handlers.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'vkpos_tax_dark_mode';
  var codeEnabled = false;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('tax-dark-mode', enabled);
    document.body.classList.toggle('tax-dark-mode', enabled);
    var icon = document.querySelector('#tax_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function getTable() {
    if (!$('#category_table').length) return null;
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#category_table')) return null;
    return $('#category_table').DataTable();
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function stripChildPrefix(name) {
    return String(name || '').replace(/^--+/, '').trim();
  }

  function isChildName(name) {
    return /^--/.test(String(name || '').trim());
  }

  function colIndexes() {
    // name, [code], description, action
    if (codeEnabled) {
      return { name: 0, code: 1, desc: 2, action: 3 };
    }
    return { name: 0, code: -1, desc: 1, action: 2 };
  }

  function updateKpis() {
    var table = getTable();
    if (!table) return;
    try {
      var info = table.page.info();
      $('#tax_kpi_total').text(info.recordsTotal);
      $('#tax_kpi_filtered').text(info.recordsDisplay);

      var parents = 0;
      var children = 0;
      var idx = colIndexes();
      table.rows({ page: 'current' }).every(function () {
        var data = this.data();
        var name = data && data.name != null ? String(data.name) : '';
        // data.name may be HTML-escaped text from server
        var text = $('<div>').html(name).text();
        if (isChildName(text)) children++;
        else parents++;
      });
      $('#tax_kpi_parents').text(parents);
      $('#tax_kpi_children').text(children);
    } catch (e) {}
  }

  function buildTree() {
    var $tree = $('#tax_tree');
    if (!$tree.length) return;
    var table = getTable();
    if (!table) {
      $('#tax_tree_empty').text('No categories yet.');
      return;
    }

    var groups = [];
    var current = null;
    var rowIndex = 0;

    table.rows({ page: 'current' }).every(function () {
      var node = this.node();
      var data = this.data();
      var nameRaw = data && data.name != null ? String(data.name) : '';
      var name = $('<div>').html(nameRaw).text().trim();
      var code =
        codeEnabled && data && data.short_code != null
          ? $('<div>').html(String(data.short_code)).text().trim()
          : '';
      var desc =
        data && data.description != null
          ? $('<div>').html(String(data.description)).text().trim()
          : '';

      if (!isChildName(name)) {
        current = {
          name: name,
          code: code,
          desc: desc,
          row: rowIndex,
          node: node,
          children: [],
        };
        groups.push(current);
      } else if (current) {
        current.children.push({
          name: stripChildPrefix(name),
          code: code,
          desc: desc,
          row: rowIndex,
          node: node,
        });
      } else {
        // orphan child on page start — treat as parent
        groups.push({
          name: stripChildPrefix(name),
          code: code,
          desc: desc,
          row: rowIndex,
          node: node,
          children: [],
        });
      }
      rowIndex++;
    });

    if (!groups.length) {
      $tree.html('<div class="tax-tree-empty">No categories on this page.</div>');
      return;
    }

    var html = '';
    groups.forEach(function (g, gi) {
      var hasChildren = g.children.length > 0;
      html +=
        '<div class="tax-tree-group tax-tree-item" data-group="' +
        gi +
        '">' +
        '<button type="button" class="tax-tree-parent" data-row="' +
        g.row +
        '" role="treeitem">' +
        '<span class="tax-tree-toggle">' +
        (hasChildren ? '<i class="fas fa-caret-down"></i>' : '<i class="fas fa-folder"></i>') +
        '</span>' +
        '<span>' +
        escapeHtml(g.name) +
        '</span>' +
        '</button>';
      if (hasChildren) {
        html += '<div class="tax-tree-children">';
        g.children.forEach(function (c) {
          html +=
            '<button type="button" class="tax-tree-child tax-tree-item" data-row="' +
            c.row +
            '" role="treeitem">' +
            '<i class="fas fa-angle-right" style="margin-right:6px;opacity:.5"></i>' +
            escapeHtml(c.name) +
            '</button>';
        });
        html += '</div>';
      }
      html += '</div>';
    });

    $tree.html(html);
  }

  function showPreviewFromRow($row) {
    if (!$row || !$row.length) return;
    var idx = colIndexes();
    $('#category_table tbody tr').removeClass('tax-row-active');
    $row.addClass('tax-row-active');

    var name = ($row.find('td').eq(idx.name).text() || '').trim();
    var isChild = isChildName(name);
    var cleanName = stripChildPrefix(name);
    var code = idx.code >= 0 ? ($row.find('td').eq(idx.code).text() || '').trim() : '';
    var desc = ($row.find('td').eq(idx.desc).text() || '').trim();

    $('#tax_preview_empty').hide();
    $('#tax_preview_content').show();
    $('#tax_preview_name').text(cleanName || '—');
    $('#tax_preview_type')
      .text(isChild ? 'Subcategory' : 'Parent')
      .toggleClass('is-child', isChild);
    if ($('#tax_preview_code').length) $('#tax_preview_code').text(code || '—');
    $('#tax_preview_desc').text(desc || 'No description');
    $('#tax_kpi_selected').text('1');

    var $actions = $row.find('td').eq(idx.action).clone(true, true);
    $('#tax_preview_actions').empty().append($actions.contents());

    // Sync tree highlight
    var rowIdx = $row.index();
    $('.tax-tree-parent, .tax-tree-child').removeClass('is-active');
    $('.tax-tree-parent[data-row="' + rowIdx + '"], .tax-tree-child[data-row="' + rowIdx + '"]').addClass(
      'is-active'
    );
  }

  ready(function () {
    if (!$('.tax-shell').length) return;

    codeEnabled = String($('.tax-shell').attr('data-cat-code-enabled') || '0') === '1';

    var dark = false;
    try {
      dark = localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {}
    applyDark(dark);

    $('#tax_dark_mode_toggle').on('click', function () {
      dark = !dark;
      applyDark(dark);
      try {
        localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
      } catch (e) {}
    });

    $('#tax_print_page').on('click', function () {
      window.print();
    });

    $('#tax_refresh_table').on('click', function () {
      var table = getTable();
      if (table) table.ajax.reload(null, false);
      else window.location.reload();
    });

    var searchTimer = null;
    $('#tax_quick_search').on('input', function () {
      var q = $(this).val();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        var table = getTable();
        if (table) table.search(q).draw();
      }, 250);
    });

    $('#tax_apply_filters').on('click', function () {
      var table = getTable();
      if (!table) return;
      var idx = colIndexes();
      table.column(idx.name).search($('#tax_filter_name').val() || '');
      if (idx.code >= 0) {
        table.column(idx.code).search($('#tax_filter_code').val() || '');
      }
      table.draw();
    });

    $('#tax_reset_filters').on('click', function () {
      $('#tax_filter_name').val('');
      $('#tax_filter_code').val('');
      $('#tax_quick_search').val('');
      var table = getTable();
      if (!table) return;
      table.search('');
      table.columns().search('');
      table.draw();
    });

    $('#tax_tree_expand').on('click', function () {
      $('.tax-tree-group').removeClass('is-collapsed');
      $('.tax-tree-toggle i.fa-caret-right')
        .removeClass('fa-caret-right')
        .addClass('fa-caret-down');
    });

    $('#tax_tree_collapse').on('click', function () {
      $('.tax-tree-group').addClass('is-collapsed');
      $('.tax-tree-group .tax-tree-toggle i.fa-caret-down')
        .removeClass('fa-caret-down')
        .addClass('fa-caret-right');
    });

    $('#tax_tree_search').on('input', function () {
      var q = String($(this).val() || '')
        .toLowerCase()
        .trim();
      $('.tax-tree-group').each(function () {
        var $group = $(this);
        var parentText = $group.find('.tax-tree-parent').text().toLowerCase();
        var childMatch = false;
        $group.find('.tax-tree-child').each(function () {
          var t = $(this).text().toLowerCase();
          var show = !q || t.indexOf(q) !== -1;
          $(this).toggleClass('is-hidden', !show);
          if (show && q) childMatch = true;
        });
        var showGroup = !q || parentText.indexOf(q) !== -1 || childMatch;
        $group.toggleClass('is-hidden', !showGroup);
        if (childMatch && q) $group.removeClass('is-collapsed');
      });
    });

    $(document).on('click', '.tax-tree-parent', function (e) {
      var $btn = $(this);
      // Toggle if caret clicked or has children
      if ($(e.target).closest('.tax-tree-toggle').length || $btn.siblings('.tax-tree-children').length) {
        var $group = $btn.closest('.tax-tree-group');
        if ($group.find('.tax-tree-children').length) {
          $group.toggleClass('is-collapsed');
          var $icon = $btn.find('.tax-tree-toggle i');
          if ($icon.hasClass('fa-caret-down') || $icon.hasClass('fa-caret-right')) {
            $icon.toggleClass('fa-caret-down fa-caret-right');
          }
        }
      }
      var row = parseInt($btn.attr('data-row'), 10);
      var $tr = $('#category_table tbody tr').eq(row);
      if ($tr.length) showPreviewFromRow($tr);
    });

    $(document).on('click', '.tax-tree-child', function () {
      var row = parseInt($(this).attr('data-row'), 10);
      var $tr = $('#category_table tbody tr').eq(row);
      if ($tr.length) showPreviewFromRow($tr);
    });

    $('#category_table').on('draw.dt', function () {
      updateKpis();
      buildTree();
    });

    setTimeout(function () {
      updateKpis();
      buildTree();
    }, 700);

    $(document).on('click', '#category_table tbody tr', function (e) {
      if ($(e.target).closest('button, a, .btn, .tw-dw-btn').length) return;
      showPreviewFromRow($(this));
    });
  });
})();
