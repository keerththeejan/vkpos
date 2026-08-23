/**
 * Products page UI helpers only.
 * Does not alter DataTables AJAX, CRUD, or filter business logic.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function applyDark(enabled) {
    document.documentElement.classList.toggle('pm-dark-mode', enabled);
    document.body.classList.toggle('pm-dark-mode', enabled);
    var icon = document.querySelector('#pm_dark_mode_toggle i');
    if (icon) icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
  }

  function updateKpisFromTable() {
    if (typeof window.product_table === 'undefined' || !window.product_table) return;
    try {
      var info = window.product_table.page.info();
      var el = document.getElementById('pm_kpi_total_products');
      if (el && typeof info.recordsTotal !== 'undefined') {
        el.textContent = info.recordsTotal;
      }
      var filtered = document.getElementById('pm_kpi_filtered_products');
      if (filtered && typeof info.recordsDisplay !== 'undefined') {
        filtered.textContent = info.recordsDisplay;
      }
    } catch (e) {}
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildCardView() {
    var grid = document.getElementById('pm_products_card_view');
    if (!grid || typeof window.product_table === 'undefined' || !window.product_table) return;

    var rows = window.product_table.rows({ page: 'current' }).nodes();
    var html = '';

    $(rows).each(function () {
      var $tr = $(this);
      var $img = $tr.find('td').eq(1).find('img').first();
      var imgSrc = $img.attr('src') || '/img/default.png';
      var productHtml = $tr.find('td').eq(3).html() || '';
      var sku = ($tr.find('td').eq(-8).text() || '').trim(); // fragile; better parse from product cell
      // Prefer SKU column by header index if available
      var skuText = '';
      var stockText = '';
      var categoryText = '';
      var brandText = '';

      $tr.find('td').each(function (idx) {
        var header = $('#product_table thead th').eq(idx).text().trim().toLowerCase();
        var text = $(this).text().trim();
        if (header.indexOf('sku') !== -1) skuText = text;
        if (header.indexOf('stock') !== -1) stockText = text;
        if (header.indexOf('category') !== -1) categoryText = text;
        if (header.indexOf('brand') !== -1) brandText = text;
      });

      var actions = $tr.find('td').eq(2).html() || '';

      html +=
        '<div class="pm-product-card">' +
        '<div class="pm-card-media" style="background-image:url(\'' + escapeHtml(imgSrc) + '\')"></div>' +
        '<div class="pm-card-body">' +
        '<h4 class="pm-card-title">' + productHtml + '</h4>' +
        (skuText ? '<div class="pm-card-meta"><strong>SKU:</strong> ' + escapeHtml(skuText) + '</div>' : '') +
        (categoryText ? '<div class="pm-card-meta"><strong>Category:</strong> ' + escapeHtml(categoryText) + '</div>' : '') +
        (brandText ? '<div class="pm-card-meta"><strong>Brand:</strong> ' + escapeHtml(brandText) + '</div>' : '') +
        (stockText ? '<div class="pm-card-meta"><strong>Stock:</strong> ' + escapeHtml(stockText) + '</div>' : '') +
        '<div class="pm-card-actions">' + actions + '</div>' +
        '</div></div>';
    });

    if (!html) {
      html = '<div class="text-muted" style="grid-column:1/-1;padding:24px;text-align:center;">No products on this page</div>';
    }
    grid.innerHTML = html;
  }

  function setViewMode(mode) {
    var shell = document.querySelector('.pm-shell');
    var grid = document.getElementById('pm_products_card_view');
    var tableBtn = document.getElementById('pm_view_table');
    var cardBtn = document.getElementById('pm_view_cards');
    if (!shell) return;

    if (mode === 'cards') {
      shell.classList.add('pm-card-mode');
      if (grid) grid.classList.add('is-visible');
      if (tableBtn) tableBtn.classList.remove('is-active');
      if (cardBtn) cardBtn.classList.add('is-active');
      buildCardView();
    } else {
      shell.classList.remove('pm-card-mode');
      if (grid) grid.classList.remove('is-visible');
      if (cardBtn) cardBtn.classList.remove('is-active');
      if (tableBtn) tableBtn.classList.add('is-active');
    }
    localStorage.setItem('vkpos_products_view_mode', mode);
  }

  ready(function () {
    var storedDark = localStorage.getItem('vkpos_pm_dark_mode');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyDark(storedDark === null ? prefersDark : storedDark === '1');

    var toggle = document.getElementById('pm_dark_mode_toggle');
    if (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var next = !document.body.classList.contains('pm-dark-mode');
        localStorage.setItem('vkpos_pm_dark_mode', next ? '1' : '0');
        applyDark(next);
      });
    }

    var tableBtn = document.getElementById('pm_view_table');
    var cardBtn = document.getElementById('pm_view_cards');
    if (tableBtn) tableBtn.addEventListener('click', function () { setViewMode('table'); });
    if (cardBtn) cardBtn.addEventListener('click', function () { setViewMode('cards'); });

    var refreshBtn = document.getElementById('pm_refresh_products');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (typeof window.product_table !== 'undefined' && window.product_table) {
          window.product_table.ajax.reload(null, false);
        }
      });
    }

    // Hook into DataTables draw without replacing existing callbacks
    $(document).on('draw.dt', '#product_table', function () {
      updateKpisFromTable();
      if (document.querySelector('.pm-shell.pm-card-mode')) {
        buildCardView();
      }
    });

    // Initial mode
    var mode = localStorage.getItem('vkpos_products_view_mode') || 'table';
    // Delay until DataTable exists
    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      if (typeof window.product_table !== 'undefined' && window.product_table) {
        clearInterval(timer);
        updateKpisFromTable();
        if (mode === 'cards') setViewMode('cards');
      }
      if (tries > 40) clearInterval(timer);
    }, 250);
  });
})();
