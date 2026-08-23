/**
 * Thermal label print engine — Zebra ZD230 @ 203 DPI
 * WYSIWYG: screen preview and print output use identical mm dimensions.
 */
(function (window) {
    'use strict';

    var MIN_FONT_PX = 6;
    var BARCODE_WIDTH_RATIO = 0.88;
    var BARCODE_HEIGHT_RATIO = 0.32;
    var SAFE_PAD_MM = 1.2;
    var printInProgress = false;

    function beginPrintJob() {
        if (printInProgress) {
            return false;
        }
        printInProgress = true;
        setTimeout(function () {
            printInProgress = false;
        }, 4000);
        return true;
    }

    function mmToPx(mm) {
        return mm * (96 / 25.4);
    }

    function pxToMm(px) {
        return px * (25.4 / 96);
    }

    function getAssetVersion() {
        var link = document.querySelector('link[href*="labels-print.css"]');
        if (!link || !link.href) {
            return String(Date.now());
        }
        var match = link.href.match(/[?&]v=([^&]+)/);
        return match ? match[1] : String(Date.now());
    }

    function getSafePadMm(wrap) {
        var sheet = wrap ? wrap.closest('.thermal-label-sheet') : null;
        if (sheet) {
            var v = parseFloat(getComputedStyle(sheet).getPropertyValue('--ld-safe-pad-mm'));
            if (v > 0) {
                return v;
            }
        }
        return SAFE_PAD_MM;
    }

    function normalize(root) {
        root = root || document;
        dedupeLabelContent(root);
        var wraps = root.querySelectorAll('.label-sticker-wrap');

        wraps.forEach(function (wrap) {
            applyDimensions(wrap);
            scaleBarcodes(wrap);
            scaleTextElements(wrap);
            centerContent(wrap);
        });

        root.querySelectorAll('.thermal-label-sheet').forEach(function (sheet) {
            applySheetDimensions(sheet);
        });
    }

    function applyDimensions(wrap) {
        var wMm = parseFloat(wrap.getAttribute('data-width-mm')) || parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--label-w-mm'));
        var hMm = parseFloat(wrap.getAttribute('data-height-mm')) || parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--label-h-mm'));
        var bMax = parseFloat(wrap.getAttribute('data-barcode-max-mm')) || hMm * BARCODE_HEIGHT_RATIO;

        if (!wMm || !hMm) {
            return;
        }

        wrap.style.width = wMm + 'mm';
        wrap.style.height = hMm + 'mm';
        wrap.style.maxWidth = wMm + 'mm';
        wrap.style.maxHeight = hMm + 'mm';
        wrap.style.setProperty('--label-w-mm', wMm + 'mm');
        wrap.style.setProperty('--label-h-mm', hMm + 'mm');
        wrap.style.setProperty('--barcode-max-h-mm', bMax + 'mm');
        wrap.style.setProperty('--ld-safe-pad-mm', getSafePadMm(wrap) + 'mm');
        wrap.style.transform = 'none';
        wrap.style.writingMode = 'horizontal-tb';

        var align = wrap.getAttribute('data-text-align') || document.getElementById('ld_text_align_field');
        align = typeof align === 'string' ? align : (align ? align.value : 'center');
        wrap.style.setProperty('--ld-align', align);
        var inner = wrap.querySelector('.label-sticker-inner');
        if (inner) {
            inner.style.textAlign = align;
        }
    }

    function applySheetDimensions(sheet) {
        var pw = sheet.getAttribute('data-paper-w-mm');
        var ph = sheet.getAttribute('data-paper-h-mm');
        if (pw) {
            sheet.style.width = pw + 'mm';
            sheet.style.setProperty('--paper-w-mm', pw + 'mm');
        }
        if (ph) {
            sheet.style.height = ph + 'mm';
            sheet.style.minHeight = ph + 'mm';
            sheet.style.maxHeight = ph + 'mm';
            sheet.style.setProperty('--paper-h-mm', ph + 'mm');
        }
    }

    function scaleBarcodes(wrap) {
        var wMm = parseFloat(wrap.getAttribute('data-width-mm')) || 50.8;
        var hMm = parseFloat(wrap.getAttribute('data-height-mm')) || 25.4;
        var padMm = getSafePadMm(wrap);
        var maxWMm = Math.max(10, wMm - padMm * 2);
        var maxHm = parseFloat(wrap.getAttribute('data-barcode-max-mm')) || hMm * BARCODE_HEIGHT_RATIO;

        wrap.querySelectorAll('.label-sticker-barcode').forEach(function (img) {
            if (img.getAttribute('data-print-locked') === '1') {
                return;
            }

            var factor = parseFloat(img.getAttribute('data-barcode-factor')) || BARCODE_HEIGHT_RATIO;
            var barMaxHm = Math.min(maxHm, hMm * factor);

            img.removeAttribute('width');
            img.removeAttribute('height');
            img.style.width = 'auto';
            img.style.height = 'auto';
            img.style.maxWidth = maxWMm + 'mm';
            img.style.maxHeight = barMaxHm + 'mm';
            img.style.objectFit = 'contain';
            img.style.objectPosition = 'center center';
            img.style.display = 'block';
            img.style.margin = '0 auto';
            img.style.padding = '0';
            img.style.transform = 'none';
            img.style.imageOrientation = 'none';
            img.style.writingMode = 'horizontal-tb';
        });

        wrap.querySelectorAll('.label-sticker-qr').forEach(function (img) {
            var qrMax = mmToPx(Math.min(wMm * 0.35, hMm * 0.45));
            img.style.maxWidth = qrMax + 'px';
            img.style.maxHeight = qrMax + 'px';
            img.style.objectFit = 'contain';
            img.style.display = 'block';
            img.style.margin = '0 auto';
        });
    }

    function scaleTextElements(wrap) {
        var wMm = parseFloat(wrap.getAttribute('data-width-mm')) || 50.8;
        var hMm = parseFloat(wrap.getAttribute('data-height-mm')) || 25.4;
        var padMm = getSafePadMm(wrap);
        var maxWMm = Math.max(8, wMm - padMm * 2);
        var maxWpx = mmToPx(maxWMm);

        wrap.querySelectorAll('.label-sticker-line2__name, .label-sticker-line3__name, .label-sticker-line1__name, [data-base-size]').forEach(function (el) {
            if (el.getAttribute('data-print-locked') === '1') {
                return;
            }
            var base = parseInt(el.getAttribute('data-base-size'), 10) || 12;
            var nameMaxRatio = el.classList.contains('label-sticker-line2__name') ? 0.28 : 0.34;
            if (el.classList.contains('label-sticker-line2__name') || el.hasAttribute('data-wrap-lines')) {
                fitTextMultiline(el, maxWpx, hMm, padMm, 2, MIN_FONT_PX, base, nameMaxRatio);
            } else {
                fitTextToWidth(el, maxWpx, MIN_FONT_PX, base);
            }
        });

        wrap.querySelectorAll('.label-sticker-price').forEach(function (el) {
            if (el.getAttribute('data-print-locked') === '1') {
                return;
            }
            var base = parseInt(window.getComputedStyle(el).fontSize, 10) || 11;
            fitTextToWidth(el, maxWpx, MIN_FONT_PX, base);
        });
    }

    function fitTextMultiline(el, maxWpx, hMm, padMm, maxLines, minPx, startPx, heightRatio) {
        heightRatio = heightRatio || 0.34;
        var lineHeight = 1.1;
        var maxHpx = mmToPx(Math.max(4, hMm * heightRatio - padMm * 0.3));
        el.style.display = 'block';
        el.style.whiteSpace = 'normal';
        el.style.wordBreak = 'break-word';
        el.style.overflow = 'hidden';
        el.style.textOverflow = 'ellipsis';
        el.style.maxWidth = maxWpx + 'px';
        el.style.lineHeight = String(lineHeight);
        el.style.maxHeight = maxHpx + 'px';
        el.style.webkitBoxOrient = 'unset';
        el.style.webkitLineClamp = 'unset';
        el.style.transform = 'none';
        el.style.writingMode = 'horizontal-tb';

        var size = startPx;
        el.style.fontSize = size + 'px';
        var guard = 0;
        while ((el.scrollHeight > maxHpx || el.scrollWidth > maxWpx) && size > minPx && guard < 36) {
            size -= 0.5;
            el.style.fontSize = size + 'px';
            guard++;
        }
    }

    function fitTextToWidth(el, maxWpx, minPx, startPx) {
        var size = startPx;
        el.style.fontSize = size + 'px';
        el.style.whiteSpace = 'nowrap';
        el.style.overflow = 'hidden';
        el.style.textOverflow = 'ellipsis';
        el.style.maxWidth = maxWpx + 'px';
        el.style.transform = 'none';
        el.style.writingMode = 'horizontal-tb';

        var guard = 0;
        while (el.scrollWidth > maxWpx && size > minPx && guard < 40) {
            size -= 0.5;
            el.style.fontSize = size + 'px';
            guard++;
        }
    }

    function resetPrintTransforms(root) {
        root = root || document;
        root.querySelectorAll('.thermal-label-sheet, .thermal-label-cell, .label-sticker-wrap, .label-sticker-inner, .label-sticker-line2--stack, .label-sticker-barcode, .label-sticker-line2__name, .label-sticker-price, .label-sticker-sku').forEach(function (el) {
            el.style.transform = 'none';
            el.style.writingMode = 'horizontal-tb';
            el.style.webkitBoxOrient = 'unset';
            el.style.webkitLineClamp = 'unset';
            el.style.zoom = '1';
        });
    }

    function isLivePreviewContext(root) {
        root = root || document;
        if (root.id === 'preview_box') {
            return true;
        }
        if (root.querySelector && root.querySelector('.ld-preview-chrome')) {
            return true;
        }
        if (root.closest && root.closest('#preview_box')) {
            return true;
        }
        return !!document.getElementById('preview_setting_form') && !document.querySelector('html.thermal-print-root');
    }

    function applyLayoutForContext(root) {
        root = root || document;
        if (isLivePreviewContext(root) && document.getElementById('preview_setting_form')) {
            applyLayoutFromForm(root);
        } else {
            applyLayoutFromQuery(root === document ? root : document);
        }
    }

    function unlockRenderedStyles(root) {
        root = root || document;
        root.removeAttribute('data-styles-locked');
        root.querySelectorAll('[data-print-locked]').forEach(function (el) {
            el.removeAttribute('data-print-locked');
        });
    }

    /** Freeze computed layout so print output matches live preview exactly. */
    function lockRenderedStyles(root) {
        root = root || document;

        root.querySelectorAll('.thermal-label-sheet').forEach(function (sheet) {
            var pw = sheet.getAttribute('data-paper-w-mm');
            var ph = sheet.getAttribute('data-paper-h-mm');
            if (pw) {
                sheet.style.setProperty('width', pw + 'mm', 'important');
                sheet.style.setProperty('--paper-w-mm', pw + 'mm', 'important');
            }
            if (ph) {
                sheet.style.setProperty('height', ph + 'mm', 'important');
                sheet.style.setProperty('min-height', ph + 'mm', 'important');
                sheet.style.setProperty('max-height', ph + 'mm', 'important');
                sheet.style.setProperty('--paper-h-mm', ph + 'mm', 'important');
            }
        });

        root.querySelectorAll('.thermal-label-cell').forEach(function (cell) {
            var wrap = cell.querySelector('.label-sticker-wrap');
            if (!wrap) {
                return;
            }
            var w = wrap.getAttribute('data-width-mm');
            var h = wrap.getAttribute('data-height-mm');
            if (w) {
                cell.style.setProperty('width', w + 'mm', 'important');
                cell.style.setProperty('max-width', w + 'mm', 'important');
                cell.style.setProperty('min-width', w + 'mm', 'important');
            }
            if (h) {
                cell.style.setProperty('height', h + 'mm', 'important');
                cell.style.setProperty('max-height', h + 'mm', 'important');
                cell.style.setProperty('min-height', h + 'mm', 'important');
            }
        });

        root.querySelectorAll('.label-sticker-wrap').forEach(function (wrap) {
            var w = wrap.getAttribute('data-width-mm');
            var h = wrap.getAttribute('data-height-mm');
            if (w) {
                wrap.style.setProperty('width', w + 'mm', 'important');
                wrap.style.setProperty('max-width', w + 'mm', 'important');
            }
            if (h) {
                wrap.style.setProperty('height', h + 'mm', 'important');
                wrap.style.setProperty('max-height', h + 'mm', 'important');
            }
        });

        root.querySelectorAll('.label-sticker-barcode').forEach(function (img) {
            if (img.getAttribute('data-print-locked') === '1') {
                return;
            }
            var wrap = img.closest('.label-sticker-wrap');
            if (wrap) {
                lockBarcodeDimensions(img, wrap);
            }
        });

        root.querySelectorAll(
            '.label-sticker-line2__name, .label-sticker-line3__name, .label-sticker-line1__name, .label-sticker-price, .label-sticker-sku, [data-base-size]'
        ).forEach(function (el) {
            var cs = window.getComputedStyle(el);
            el.style.setProperty('font-size', cs.fontSize, 'important');
            el.style.setProperty('font-weight', cs.fontWeight, 'important');
            if (el.classList.contains('label-sticker-sku')) {
                el.style.setProperty('margin-top', '2mm', 'important');
                el.style.setProperty('line-height', '1.15', 'important');
            }
            el.style.setProperty('max-width', cs.maxWidth, 'important');
            el.style.setProperty('max-height', cs.maxHeight, 'important');
            el.style.setProperty('line-height', cs.lineHeight, 'important');
            el.style.setProperty('transform', 'none', 'important');
            el.style.setProperty('writing-mode', 'horizontal-tb', 'important');
            el.setAttribute('data-print-locked', '1');
        });

        root.setAttribute('data-styles-locked', '1');
    }

    function prepareForPrint(root) {
        root = root || document;
        unlockRenderedStyles(root);
        applyLayoutForContext(root);
        reflowForZebraPrint(root);
        resetPrintTransforms(root);
        normalize(root);
        lockRenderedStyles(root);
        if (root === document || root === document.documentElement) {
            document.documentElement.classList.add('ld-print-zebra');
            document.documentElement.setAttribute('data-print-ready', '1');
        }
    }

    /** Live preview on labels/show — layout from form, never URL query. */
    function refreshPreview(root) {
        root = root || document.getElementById('preview_box');
        if (!root) {
            return;
        }
        unlockRenderedStyles(root);
        applyLayoutFromForm(root);
        resetPrintTransforms(root);
        normalize(root);
        lockRenderedStyles(root);
    }

    function dedupeLabelContent(root) {
        root.querySelectorAll('.thermal-label-cell').forEach(function (cell) {
            ['.label-sticker-barcode', '.label-sticker-line2__name', '.label-sticker-price', '.label-sticker-sku'].forEach(function (sel) {
                var nodes = cell.querySelectorAll(sel);
                for (var i = 1; i < nodes.length; i++) {
                    nodes[i].parentNode.removeChild(nodes[i]);
                }
            });

            var inners = cell.querySelectorAll('.label-sticker-inner');
            for (var j = 1; j < inners.length; j++) {
                inners[j].parentNode.removeChild(inners[j]);
            }

            var stacks = cell.querySelectorAll('.label-sticker-line2--stack');
            for (var k = 1; k < stacks.length; k++) {
                stacks[k].parentNode.removeChild(stacks[k]);
            }
        });
    }

    function centerContent(wrap) {
        var inner = wrap.querySelector('.label-sticker-inner');
        if (!inner) {
            return;
        }

        inner.style.width = '100%';
        inner.style.height = '100%';
        inner.style.overflow = 'hidden';
        inner.style.boxSizing = 'border-box';

        if (/label-sticker-wrap--(?:line|mfg|custom|text_card|warehouse|retail_shelf|mrp_offer)/.test(wrap.className)) {
            return;
        }

        inner.style.display = 'flex';
        inner.style.flexDirection = 'column';
        inner.style.alignItems = 'center';
        inner.style.justifyContent = 'center';
        inner.style.textAlign = 'center';
        inner.style.padding = '0.8mm';
    }

    function setupPrintHints() {
        if (window.matchMedia) {
            var mql = window.matchMedia('print');
            if (mql.addListener) {
                mql.addListener(function (m) {
                    if (m.matches) {
                        lockRenderedStyles(document);
                        resetPrintTransforms(document);
                    }
                });
            }
        }

        window.addEventListener('beforeprint', function () {
            if (!document.documentElement.getAttribute('data-print-ready')) {
                prepareForPrint(document);
            } else {
                lockRenderedStyles(document);
                resetPrintTransforms(document);
            }
        });
    }

    function showPrintError(message) {
        var msg = message || 'Could not open the print window.';
        if (typeof swal !== 'undefined') {
            swal(msg);
        } else if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }

    function getStylesheetHrefs() {
        var hrefs = [];
        ['labels-print.css', 'labels-sticker.css'].forEach(function (name) {
            var link = document.querySelector('link[href*="' + name + '"]');
            if (link && link.href) {
                hrefs.push(link.href);
            }
        });
        if (!hrefs.length) {
            var bp = window.base_path || '';
            var v = getAssetVersion();
            hrefs.push(bp + '/css/labels-print.css?v=' + v);
            hrefs.push(bp + '/css/labels-sticker.css?v=' + v);
        }
        return hrefs;
    }

    function getZebraStylesheetHref() {
        var link = document.querySelector('link[href*="labels-print-zebra.css"]');
        if (link && link.href) {
            return link.href;
        }
        var printLink = document.querySelector('link[href*="labels-print.css"]');
        if (printLink && printLink.href) {
            return printLink.href.replace(/labels-print\.css[^'"]*/, 'labels-print-zebra.css?v=' + getAssetVersion());
        }
        return (window.base_path || '') + '/css/labels-print-zebra.css?v=' + getAssetVersion();
    }

    function getStickerStylesheetHref() {
        var link = document.querySelector('link[href*="labels-sticker.css"]');
        if (link && link.href) {
            return link.href;
        }
        return (window.base_path || '') + '/css/labels-sticker.css?v=' + getAssetVersion();
    }

    function normalizeZebraSingleLabelSheet(sheet, labelW, labelH) {
        var w = cssVarMm(labelW, '50.8');
        var h = cssVarMm(labelH, '25.4');
        var wNum = parseFloat(labelW) || 50.8;
        var hNum = parseFloat(labelH) || 25.4;
        var bMax = cssVarMm(hNum * BARCODE_HEIGHT_RATIO, '8');

        sheet.setAttribute('data-label-w-mm', String(wNum));
        sheet.setAttribute('data-label-h-mm', String(hNum));
        sheet.setAttribute('data-paper-w-mm', String(wNum));
        sheet.setAttribute('data-paper-h-mm', String(hNum));
        sheet.setAttribute('data-stickers-per-row', '1');
        sheet.setAttribute('data-labels-on-page', '1');

        sheet.style.width = w;
        sheet.style.height = h;
        sheet.style.minHeight = h;
        sheet.style.maxHeight = h;
        sheet.style.margin = '0';
        sheet.style.padding = '0';
        sheet.style.overflow = 'hidden';
        sheet.style.setProperty('--label-w-mm', w);
        sheet.style.setProperty('--label-h-mm', h);
        sheet.style.setProperty('--paper-w-mm', w);
        sheet.style.setProperty('--paper-h-mm', h);
        sheet.style.setProperty('--col-gap-mm', '0mm');
        sheet.style.setProperty('--row-gap-mm', '0mm');
        sheet.style.setProperty('--barcode-max-h-mm', bMax);
        sheet.style.setProperty('--ld-safe-pad-mm', '0.5mm');

        sheet.querySelectorAll('.thermal-label-table').forEach(function (table) {
            table.style.width = w;
            table.style.height = h;
            table.style.borderSpacing = '0';
            table.style.margin = '0';
            table.style.padding = '0';
        });

        sheet.querySelectorAll('.thermal-label-cell').forEach(function (cell) {
            cell.style.width = w;
            cell.style.height = h;
            cell.style.maxWidth = w;
            cell.style.maxHeight = h;
            cell.style.minWidth = w;
            cell.style.minHeight = h;
            cell.style.padding = '0';
            cell.style.margin = '0';
            cell.style.overflow = 'hidden';
            cell.style.verticalAlign = 'top';
        });

        sheet.querySelectorAll('.label-sticker-wrap').forEach(function (wrap) {
            wrap.setAttribute('data-width-mm', String(wNum));
            wrap.setAttribute('data-height-mm', String(hNum));
            wrap.style.width = w;
            wrap.style.height = h;
            wrap.style.maxWidth = w;
            wrap.style.maxHeight = h;
            wrap.style.setProperty('--label-w-mm', w);
            wrap.style.setProperty('--label-h-mm', h);
            wrap.style.setProperty('--barcode-max-h-mm', bMax);
        });
    }

    function buildZebraSingleLabelSheetElement(cell, labelW, labelH) {
        var sheet = document.createElement('div');
        sheet.className = 'thermal-label-sheet thermal-label-sheet--zebra thermal-label-sheet--single-col thermal-label-sheet--sheet';

        var table = document.createElement('table');
        table.className = 'thermal-label-table';
        table.setAttribute('cellspacing', '0');
        table.setAttribute('cellpadding', '0');

        var row = document.createElement('tr');
        row.className = 'thermal-label-row';
        row.appendChild(cell);
        table.appendChild(row);
        sheet.appendChild(table);

        normalizeZebraSingleLabelSheet(sheet, labelW, labelH);
        return sheet;
    }

    /**
     * Zebra ZD230 print HTML from live preview.
     * 1-up: one label per page (50.8×25.4 mm) for driver compatibility.
     * 2-up+: keep side-by-side row layout cloned from preview.
     */
    function collectZebraPrintSheetsHtml(previewRoot) {
        var firstSheet = previewRoot.querySelector('.thermal-label-sheet');
        var labelW = firstSheet ? (firstSheet.getAttribute('data-label-w-mm') || '50.8') : '50.8';
        var labelH = firstSheet ? (firstSheet.getAttribute('data-label-h-mm') || '25.4') : '25.4';
        var pageW = firstSheet ? (firstSheet.getAttribute('data-paper-w-mm') || labelW) : labelW;
        var pageH = firstSheet ? (firstSheet.getAttribute('data-paper-h-mm') || labelH) : labelH;
        var cols = firstSheet ? (parseInt(firstSheet.getAttribute('data-stickers-per-row'), 10) || 1) : 1;
        var html = '';

        if (cols > 1) {
            previewRoot.querySelectorAll('.thermal-label-sheet').forEach(function (sheet) {
                var clone = sheet.cloneNode(true);
                normalizePrintSheetElement(clone);
                html += clone.outerHTML;
            });
            return {
                html: html,
                labelW: labelW,
                labelH: labelH,
                pageW: pageW,
                pageH: pageH,
                cols: cols,
            };
        }

        var tmp = document.createElement('div');
        previewRoot.querySelectorAll('.thermal-label-cell').forEach(function (cell) {
            if (!cell.querySelector('.label-sticker-wrap')) {
                return;
            }
            var cellClone = cell.cloneNode(true);
            var sheet = buildZebraSingleLabelSheetElement(cellClone, labelW, labelH);
            tmp.appendChild(sheet);
            html += sheet.outerHTML;
            tmp.removeChild(sheet);
        });

        return {
            html: html,
            labelW: labelW,
            labelH: labelH,
            pageW: labelW,
            pageH: labelH,
            cols: 1,
        };
    }

    /** Reflow server print window — split only for 1-up; keep 2-up row intact. */
    function reflowForZebraPrint(root) {
        root = root || document;
        var body = root.body || root;
        if (!body.querySelectorAll) {
            return;
        }

        var firstSheet = body.querySelector('.thermal-label-sheet');
        if (!firstSheet) {
            return;
        }

        var cols = parseInt(firstSheet.getAttribute('data-stickers-per-row'), 10) || 1;

        if (cols > 1) {
            body.querySelectorAll('.thermal-label-sheet').forEach(function (sheet) {
                normalizePrintSheetElement(sheet);
            });
            return;
        }

        var labelW = firstSheet.getAttribute('data-label-w-mm') || '50.8';
        var labelH = firstSheet.getAttribute('data-label-h-mm') || '25.4';
        var cells = [];

        body.querySelectorAll('.thermal-label-cell').forEach(function (cell) {
            if (cell.querySelector('.label-sticker-wrap')) {
                cells.push(cell);
            }
        });

        if (!cells.length) {
            return;
        }

        body.querySelectorAll('.thermal-label-sheet').forEach(function (s) {
            s.parentNode.removeChild(s);
        });

        cells.forEach(function (cell) {
            var sheet = buildZebraSingleLabelSheetElement(cell, labelW, labelH);
            body.appendChild(sheet);
        });
    }

    function runWithoutPreviewZoom(fn) {
        var vp = document.getElementById('ld_preview_viewport');
        var prev = vp ? vp.style.transform : '';
        if (vp) {
            vp.style.transform = 'none';
        }
        try {
            return fn();
        } finally {
            if (vp) {
                vp.style.transform = prev;
            }
        }
    }

    function cssVarMm(value, fallback) {
        var num = parseFloat(value);
        if (isNaN(num) || num <= 0) {
            num = parseFloat(fallback) || 0;
        }
        return num > 0 ? num + 'mm' : '';
    }

    function normalizePrintSheetElement(sheet) {
        if (!sheet) {
            return;
        }

        var labelW = sheet.getAttribute('data-label-w-mm');
        var labelH = sheet.getAttribute('data-label-h-mm');
        var paperW = sheet.getAttribute('data-paper-w-mm');
        var paperH = sheet.getAttribute('data-paper-h-mm');
        var colGap = sheet.getAttribute('data-col-gap-mm') || '0';
        var rowGap = sheet.getAttribute('data-row-gap-mm') || '0';
        var mTop = sheet.getAttribute('data-margin-top-mm') || '0';
        var mLeft = sheet.getAttribute('data-margin-left-mm') || '0';
        var barcodeMax = sheet.style.getPropertyValue('--barcode-max-h-mm') || '';
        var safePad = sheet.style.getPropertyValue('--ld-safe-pad-mm') || '1.2';

        if (labelW) {
            sheet.style.setProperty('--label-w-mm', cssVarMm(labelW, '50.8'));
        }
        if (labelH) {
            sheet.style.setProperty('--label-h-mm', cssVarMm(labelH, '25.4'));
        }
        if (paperW) {
            sheet.style.setProperty('--paper-w-mm', cssVarMm(paperW, paperW));
            sheet.style.width = cssVarMm(paperW, paperW);
        }
        if (paperH) {
            sheet.style.setProperty('--paper-h-mm', cssVarMm(paperH, paperH));
            sheet.style.height = cssVarMm(paperH, paperH);
            sheet.style.minHeight = cssVarMm(paperH, paperH);
            sheet.style.maxHeight = cssVarMm(paperH, paperH);
        }

        sheet.style.setProperty('--col-gap-mm', cssVarMm(colGap, '0'));
        sheet.style.setProperty('--row-gap-mm', cssVarMm(rowGap, '0'));
        sheet.style.setProperty('--margin-top-mm', cssVarMm(mTop, '0'));
        sheet.style.setProperty('--margin-left-mm', cssVarMm(mLeft, '0'));
        sheet.style.setProperty('--ld-safe-pad-mm', cssVarMm(safePad, '1.2'));
        if (barcodeMax) {
            sheet.style.setProperty('--barcode-max-h-mm', cssVarMm(barcodeMax, barcodeMax));
        } else if (labelH) {
            sheet.style.setProperty('--barcode-max-h-mm', cssVarMm(parseFloat(labelH) * BARCODE_HEIGHT_RATIO, '8'));
        }

        sheet.style.transform = 'none';
        sheet.style.writingMode = 'horizontal-tb';

        sheet.querySelectorAll('.label-sticker-wrap').forEach(function (wrap) {
            var w = wrap.getAttribute('data-width-mm') || labelW;
            var h = wrap.getAttribute('data-height-mm') || labelH;
            var bMax = wrap.getAttribute('data-barcode-max-mm') || (h ? String(parseFloat(h) * BARCODE_HEIGHT_RATIO) : '8');
            if (w) {
                wrap.style.setProperty('--label-w-mm', cssVarMm(w, w));
                wrap.style.width = cssVarMm(w, w);
                wrap.style.maxWidth = cssVarMm(w, w);
            }
            if (h) {
                wrap.style.setProperty('--label-h-mm', cssVarMm(h, h));
                wrap.style.height = cssVarMm(h, h);
                wrap.style.maxHeight = cssVarMm(h, h);
            }
            wrap.style.setProperty('--barcode-max-h-mm', cssVarMm(bMax, bMax));
            wrap.style.setProperty('--ld-safe-pad-mm', cssVarMm(safePad, '1.2'));
            wrap.style.transform = 'none';
            wrap.style.writingMode = 'horizontal-tb';
        });
    }

    function lockBarcodeDimensions(img, wrap) {
        if (!img || !wrap || !img.naturalWidth || !img.naturalHeight) {
            return;
        }

        var wMm = parseFloat(wrap.getAttribute('data-width-mm')) || 50.8;
        var hMm = parseFloat(wrap.getAttribute('data-height-mm')) || 25.4;
        var padMm = getSafePadMm(wrap);
        var maxWMm = Math.max(8, wMm - padMm * 2);
        var factor = parseFloat(img.getAttribute('data-barcode-factor')) || BARCODE_HEIGHT_RATIO;
        var maxHm = Math.min(
            parseFloat(wrap.getAttribute('data-barcode-max-mm')) || hMm * BARCODE_HEIGHT_RATIO,
            hMm * factor
        );
        var aspect = img.naturalWidth / img.naturalHeight;
        var barHm = maxHm;
        var barWm = barHm * aspect;

        if (barWm > maxWMm) {
            barWm = maxWMm;
            barHm = barWm / aspect;
        }

        img.style.setProperty('width', barWm.toFixed(3) + 'mm', 'important');
        img.style.setProperty('height', barHm.toFixed(3) + 'mm', 'important');
        img.style.setProperty('max-width', barWm.toFixed(3) + 'mm', 'important');
        img.style.setProperty('max-height', barHm.toFixed(3) + 'mm', 'important');
        img.style.setProperty('object-fit', 'contain', 'important');
        img.style.setProperty('object-position', 'center center', 'important');
        img.style.setProperty('transform', 'none', 'important');
        img.style.setProperty('writing-mode', 'horizontal-tb', 'important');
        img.style.setProperty('image-orientation', 'none', 'important');
        img.style.setProperty('margin-left', 'auto', 'important');
        img.style.setProperty('margin-right', 'auto', 'important');
        img.style.setProperty('display', 'block', 'important');
        img.setAttribute('data-print-locked', '1');
    }

    function canPrintFromPreview() {
        var previewRoot = document.getElementById('preview_box');
        if (!previewRoot || !previewRoot.querySelector('.thermal-label-sheet')) {
            return false;
        }
        var form = document.getElementById('preview_setting_form');
        var expected = countExpectedLabelsFromForm(form);
        if (expected <= 0) {
            return false;
        }
        var actual = previewRoot.querySelectorAll('.thermal-label-cell .label-sticker-wrap').length;
        return actual >= expected;
    }

    function buildPrintDocumentHtml(sheetsHtml, labelW, labelH, pageW, pageH, cols, showToolbar) {
        var labelWNum = parseFloat(labelW) || 50.8;
        var labelHNum = parseFloat(labelH) || 25.4;
        var colCount = parseInt(cols, 10) || 1;
        var printWNum = colCount > 1 ? (parseFloat(pageW) || labelWNum * colCount) : labelWNum;
        var printHNum = parseFloat(pageH) || labelHNum;
        var wCss = printWNum + 'mm';
        var hCss = printHNum + 'mm';
        var lwCss = labelWNum + 'mm';
        var multiClass = colCount > 1 ? ' ld-print-zebra--multi-col' : '';

        var toolbar = showToolbar
            ? '<div class="ld-print-toolbar" style="padding:10px;text-align:center;background:#f5f5f5;border-bottom:1px solid #ddd;">' +
              '<button type="button" onclick="window.print()" style="padding:8px 20px;font-size:14px;cursor:pointer;">' +
              'Print Labels</button>' +
              '<p style="margin:8px 0 0;font-size:12px;color:#555;">Zebra ZD230: ' + printWNum + '×' + printHNum +
              ' mm · label ' + labelWNum + '×' + labelHNum + ' mm · Scale 100% · Margins None</p></div>'
            : '';

        return '<!DOCTYPE html><html lang="en" class="thermal-print-root ld-print-zebra' + multiClass + '" data-print-ready="1" ' +
            'style="--ld-label-w-mm:' + lwCss + ';--ld-label-h-mm:' + hCss + ';--ld-zebra-page-w:' + wCss + ';--ld-zebra-page-h:' + hCss + ';">' +
            '<head><meta charset="utf-8"><title>Print Labels</title>' +
            '<link rel="stylesheet" href="' + getStickerStylesheetHref() + '">' +
            '<link rel="stylesheet" href="' + getZebraStylesheetHref() + '">' +
            '<style>' +
            '@page { size: ' + wCss + ' ' + hCss + '; margin: 0; }' +
            'html.ld-print-zebra, html.ld-print-zebra body.thermal-print-body {' +
            'width: ' + wCss + '; margin: 0; padding: 0; overflow: hidden; box-sizing: border-box; background: #fff;' +
            '}' +
            '@media print {' +
            '@page { size: ' + wCss + ' ' + hCss + '; margin: 0; }' +
            'html, body { width: ' + wCss + ' !important; margin: 0 !important; padding: 0 !important;' +
            'overflow: hidden !important; box-sizing: border-box !important;' +
            '-webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }' +
            '}' +
            '</style></head>' +
            '<body class="thermal-print-body thermal-print-body--sheet" ' +
            'style="width:' + wCss + ';margin:0;padding:0;overflow:hidden;box-sizing:border-box;">' +
            toolbar +
            sheetsHtml +
            '<script>(function(){function ready(){if(document.fonts&&document.fonts.ready){document.fonts.ready.then(function(){window.__ldZebraReady=1;});}}' +
            'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",ready);}else{ready();}' +
            'window.addEventListener("beforeprint",function(){document.documentElement.style.setProperty("--ld-label-w-mm","' + lwCss + '");' +
            'document.documentElement.style.setProperty("--ld-label-h-mm","' + hCss + '");' +
            'document.documentElement.style.setProperty("--ld-zebra-page-w","' + wCss + '");' +
            'document.documentElement.style.setProperty("--ld-zebra-page-h","' + hCss + '");});})();<\/script>' +
            '</body></html>';
    }

    function waitForPrintDocumentReady(doc, timeoutMs) {
        timeoutMs = timeoutMs || 8000;
        return new Promise(function (resolve) {
            if (!doc) {
                resolve();
                return;
            }

            var settled = false;
            function finish() {
                if (!settled) {
                    settled = true;
                    resolve();
                }
            }

            setTimeout(finish, timeoutMs);

            function waitImages() {
                var images = doc.querySelectorAll('img');
                var pending = Array.from(images).filter(function (img) {
                    return !img.complete;
                });
                var fontsReady = (!doc.fonts || !doc.fonts.ready)
                    ? Promise.resolve()
                    : doc.fonts.ready.catch(function () { return null; });

                fontsReady.then(function () {
                    if (!pending.length) {
                        finish();
                        return;
                    }
                    var left = pending.length;
                    pending.forEach(function (img) {
                        var done = function () {
                            left--;
                            if (left <= 0) {
                                finish();
                            }
                        };
                        img.addEventListener('load', done, { once: true });
                        img.addEventListener('error', done, { once: true });
                    });
                });
            }

            if (doc.readyState === 'complete' || doc.readyState === 'interactive') {
                waitImages();
            } else {
                doc.addEventListener('DOMContentLoaded', waitImages, { once: true });
            }
        });
    }

    function triggerPrintWhenReady(targetWin, autoPrint) {
        if (!autoPrint || !targetWin) {
            return;
        }
        if (targetWin.__ldPrintTriggered) {
            return;
        }
        waitForPrintDocumentReady(targetWin.document).then(function () {
            if (targetWin.closed || targetWin.__ldPrintTriggered) {
                return;
            }
            targetWin.__ldPrintTriggered = true;
            try {
                if (targetWin.document && targetWin.document.documentElement) {
                    targetWin.document.documentElement.setAttribute('data-print-ready', '1');
                    resetPrintTransforms(targetWin.document);
                }
                targetWin.focus();
                targetWin.print();
            } catch (e) {
                showPrintError('Print dialog could not be opened. Use Ctrl+P in the print window.');
            }
        });
    }

    function writePrintDocument(targetWin, docHtml) {
        targetWin.document.open('text/html', 'replace');
        targetWin.document.write(docHtml);
        targetWin.document.close();
    }

    function openPrintViaHiddenIframe(docHtml, autoPrint) {
        var iframe = document.getElementById('ld_print_iframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'ld_print_iframe';
            iframe.setAttribute('title', 'Label print frame');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
            document.body.appendChild(iframe);
        }

        try {
            writePrintDocument(iframe.contentWindow, docHtml);
            triggerPrintWhenReady(iframe.contentWindow, autoPrint);
            return iframe.contentWindow;
        } catch (e) {
            showPrintError('Print failed. Allow pop-ups for this site or try again.');
            return null;
        }
    }

    function openPrintDocument(docHtml, autoPrint) {
        var blobUrl = null;
        var win = null;

        try {
            var blob = new Blob([docHtml], { type: 'text/html;charset=utf-8' });
            blobUrl = URL.createObjectURL(blob);
            win = window.open(blobUrl, '_blank');
        } catch (e) {
            win = null;
        }

        if (!win) {
            if (blobUrl) {
                try {
                    URL.revokeObjectURL(blobUrl);
                } catch (ignore) {}
            }
            win = window.open('about:blank', '_blank');
            if (!win) {
                return openPrintViaHiddenIframe(docHtml, autoPrint);
            }
            try {
                writePrintDocument(win, docHtml);
            } catch (writeErr) {
                try {
                    win.close();
                } catch (ignore) {}
                return openPrintViaHiddenIframe(docHtml, autoPrint);
            }
            triggerPrintWhenReady(win, autoPrint);
            return win;
        }

        var revoked = false;
        function revokeBlob() {
            if (!revoked && blobUrl) {
                revoked = true;
                try {
                    URL.revokeObjectURL(blobUrl);
                } catch (ignore) {}
            }
        }

        win.addEventListener('load', function () {
            revokeBlob();
            triggerPrintWhenReady(win, autoPrint);
        }, { once: true });

        setTimeout(function () {
            revokeBlob();
            if (!win.closed) {
                triggerPrintWhenReady(win, autoPrint);
            }
        }, 2500);

        return win;
    }

    function ensurePreviewReadyForPrint(previewRoot) {
        if (!previewRoot || !previewRoot.querySelector('.thermal-label-sheet')) {
            return false;
        }
        unlockRenderedStyles(previewRoot);
        if (document.getElementById('preview_setting_form')) {
            applyLayoutFromForm(previewRoot);
            normalize(previewRoot);
        }
        lockRenderedStyles(previewRoot);
        return !!previewRoot.querySelector('.thermal-label-sheet .label-sticker-wrap');
    }

    function collectPreviewSheetsHtml(previewRoot) {
        var html = '';
        previewRoot.querySelectorAll('.thermal-label-sheet').forEach(function (sheet) {
            var clone = sheet.cloneNode(true);
            normalizePrintSheetElement(clone);
            html += clone.outerHTML;
        });
        return html;
    }

    function watchServerPrintWindow(win, autoPrint) {
        if (!autoPrint || !win) {
            return;
        }
        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            if (!win || win.closed) {
                clearInterval(timer);
                return;
            }
            try {
                var doc = win.document;
                if (!doc || doc.readyState !== 'complete') {
                    return;
                }
                var sheet = doc.querySelector('.thermal-label-sheet');
                if (!sheet) {
                    return;
                }
                var barcodes = doc.querySelectorAll('.label-sticker-barcode');
                var imagesReady = !barcodes.length || Array.from(barcodes).every(function (img) {
                    return img.complete;
                });
                if (!imagesReady) {
                    return;
                }
                clearInterval(timer);
                if (window.LabelPrintEngine && typeof window.LabelPrintEngine.reflowForZebraPrint === 'function') {
                    window.LabelPrintEngine.reflowForZebraPrint(doc);
                }
                if (window.LabelPrintEngine && typeof window.LabelPrintEngine.lockRenderedStyles === 'function') {
                    window.LabelPrintEngine.lockRenderedStyles(doc);
                }
                setTimeout(function () {
                    if (!win.closed) {
                        win.focus();
                        win.print();
                    }
                }, 300);
            } catch (e) {
                /* ignore until ready */
            }
            if (attempts > 60) {
                clearInterval(timer);
            }
        }, 250);
    }

    function openPrintFromServer(autoPrint, internal) {
        if (!internal && !beginPrintJob()) {
            return null;
        }
        autoPrint = autoPrint !== false;
        var qs = window.serializeLabelPreviewForm ? window.serializeLabelPreviewForm() : '';
        if (!qs) {
            showPrintError('No label data to print. Add products first.');
            return null;
        }
        var url = (window.base_path || '') + '/labels/preview?' + qs;
        var win = window.open(url, 'label_print_' + Date.now());
        if (!win) {
            showPrintError('Pop-up blocked. Allow pop-ups for this site to print labels.');
            return null;
        }
        watchServerPrintWindow(win, autoPrint);
        return win;
    }

    /** Clone live preview DOM — single source of truth for preview and print. */
    function openPrintFromPreview(autoPrint) {
        if (!beginPrintJob()) {
            return null;
        }
        autoPrint = autoPrint !== false;

        return runWithoutPreviewZoom(function () {
            var previewRoot = document.getElementById('preview_box');

            if (!previewRoot || !previewRoot.querySelector('.thermal-label-sheet')) {
                return openPrintFromServer(autoPrint, true);
            }

            if (!ensurePreviewReadyForPrint(previewRoot)) {
                return openPrintFromServer(autoPrint, true);
            }

            var zebraSheets = collectZebraPrintSheetsHtml(previewRoot);
            if (!zebraSheets.html.trim()) {
                showPrintError('Label preview is empty. Wait for the preview to load, then try again.');
                return openPrintFromServer(autoPrint, true);
            }

            var docHtml = buildPrintDocumentHtml(
                zebraSheets.html,
                zebraSheets.labelW,
                zebraSheets.labelH,
                zebraSheets.pageW,
                zebraSheets.pageH,
                zebraSheets.cols,
                !autoPrint
            );

            return openPrintDocument(docHtml, autoPrint);
        });
    }

    function parseQueryParams() {
        var params = {};
        if (!window.location || !window.location.search) {
            return params;
        }
        var search = window.location.search.replace(/^\?/, '');
        if (!search) {
            return params;
        }
        search.split('&').forEach(function (pair) {
            if (!pair) {
                return;
            }
            var idx = pair.indexOf('=');
            var key = idx >= 0 ? pair.slice(0, idx) : pair;
            var val = idx >= 0 ? pair.slice(idx + 1) : '';
            key = decodeURIComponent(key.replace(/\+/g, ' '));
            val = decodeURIComponent(val.replace(/\+/g, ' '));
            if (Object.prototype.hasOwnProperty.call(params, key)) {
                if (!Array.isArray(params[key])) {
                    params[key] = [params[key]];
                }
                params[key].push(val);
            } else {
                params[key] = val;
            }
        });
        return params;
    }

    function countExpectedLabelsFromForm(form) {
        var total = 0;
        if (!form) {
            return total;
        }
        form.querySelectorAll('input[name$="[quantity]"]').forEach(function (inp) {
            total += parseInt(inp.value, 10) || 0;
        });
        return Math.max(0, total);
    }

    function countExpectedLabelsFromQuery() {
        var params = parseQueryParams();
        var total = 0;
        Object.keys(params).forEach(function (key) {
            var match = key.match(/^products\[(\d+)\]\[quantity\]$/);
            if (!match) {
                return;
            }
            var val = params[key];
            if (Array.isArray(val)) {
                val.forEach(function (v) {
                    total += parseInt(v, 10) || 0;
                });
            } else {
                total += parseInt(val, 10) || 0;
            }
        });
        return Math.max(0, total);
    }

    function getColumnsFromForm(form) {
        var el = form ? form.querySelector('#ld_print_columns') : null;
        var cols = el ? parseInt(el.value, 10) : 1;
        if (!cols || cols < 1) {
            cols = 1;
        }
        if (cols > 3) {
            cols = 3;
        }
        return cols;
    }

    function getColumnsFromQuery() {
        var params = parseQueryParams();
        var cols = parseInt(params.ld_print_columns, 10) || 1;
        if (cols < 1) {
            cols = 1;
        }
        if (cols > 3) {
            cols = 3;
        }
        return cols;
    }

    function getStartPositionFromForm(form) {
        var el = form ? form.querySelector('#ld_print_start_pos') : null;
        return el ? Math.max(0, parseInt(el.value, 10) || 0) : 0;
    }

    function getStartPositionFromQuery() {
        var params = parseQueryParams();
        return Math.max(0, parseInt(params.ld_print_start_pos, 10) || 0);
    }

    function readPrintSetting(form, id, fallback) {
        if (!form) {
            return fallback;
        }
        var el = form.querySelector('#' + id);
        if (!el || el.value === '') {
            return fallback;
        }
        var num = parseFloat(el.value);
        return isNaN(num) ? fallback : num;
    }

    function applyPrintSettingsToSheet(sheet, form) {
        if (!sheet || !form) {
            return;
        }
        var w = readPrintSetting(form, 'ld_print_width', parseFloat(sheet.getAttribute('data-label-w-mm')));
        var h = readPrintSetting(form, 'ld_print_height', parseFloat(sheet.getAttribute('data-label-h-mm')));
        var colGap = readPrintSetting(form, 'ld_print_col_gap', parseFloat(sheet.getAttribute('data-col-gap-mm')));
        var rowGap = readPrintSetting(form, 'ld_print_row_gap', parseFloat(sheet.getAttribute('data-row-gap-mm')));
        var mTop = readPrintSetting(form, 'ld_print_margin_top', parseFloat(sheet.getAttribute('data-margin-top-mm')));
        var mLeft = readPrintSetting(form, 'ld_print_margin_left', parseFloat(sheet.getAttribute('data-margin-left-mm')));

        applyPrintSettingsValues(sheet, w, h, colGap, rowGap, mTop, mLeft, getColumnsFromForm(form));
    }

    function applyPrintSettingsFromQuery(sheet) {
        if (!sheet) {
            return;
        }
        var params = parseQueryParams();

        function readParam(name, fallback) {
            if (!Object.prototype.hasOwnProperty.call(params, name)) {
                return fallback;
            }
            var raw = params[name];
            var val = Array.isArray(raw) ? raw[0] : raw;
            if (val === undefined || val === null || val === '') {
                return fallback;
            }
            var num = parseFloat(val);
            return isNaN(num) ? fallback : num;
        }

        var w = readParam('ld_print_width', parseFloat(sheet.getAttribute('data-label-w-mm')));
        var h = readParam('ld_print_height', parseFloat(sheet.getAttribute('data-label-h-mm')));
        var colGap = readParam('ld_print_col_gap', parseFloat(sheet.getAttribute('data-col-gap-mm')));
        var rowGap = readParam('ld_print_row_gap', parseFloat(sheet.getAttribute('data-row-gap-mm')));
        var mTop = readParam('ld_print_margin_top', parseFloat(sheet.getAttribute('data-margin-top-mm')));
        var mLeft = readParam('ld_print_margin_left', parseFloat(sheet.getAttribute('data-margin-left-mm')));

        applyPrintSettingsValues(sheet, w, h, colGap, rowGap, mTop, mLeft, getColumnsFromQuery());
    }

    function applyPrintSettingsValues(sheet, w, h, colGap, rowGap, mTop, mLeft, cols) {
        if (w > 0) {
            sheet.style.setProperty('--label-w-mm', w + 'mm');
            sheet.setAttribute('data-label-w-mm', String(w));
        }
        if (h > 0) {
            sheet.style.setProperty('--label-h-mm', h + 'mm');
            sheet.setAttribute('data-label-h-mm', String(h));
        }
        if (!isNaN(colGap)) {
            sheet.style.setProperty('--col-gap-mm', colGap + 'mm');
            sheet.setAttribute('data-col-gap-mm', String(colGap));
        }
        if (!isNaN(rowGap)) {
            sheet.style.setProperty('--row-gap-mm', rowGap + 'mm');
            sheet.setAttribute('data-row-gap-mm', String(rowGap));
        }
        if (!isNaN(mTop)) {
            sheet.style.setProperty('--margin-top-mm', mTop + 'mm');
            sheet.setAttribute('data-margin-top-mm', String(mTop));
        }
        if (!isNaN(mLeft)) {
            sheet.style.setProperty('--margin-left-mm', mLeft + 'mm');
            sheet.setAttribute('data-margin-left-mm', String(mLeft));
        }

        var colsCount = cols || parseInt(sheet.getAttribute('data-stickers-per-row'), 10) || 1;
        if (w > 0 && h > 0) {
            var pageW = (w * colsCount) + (colGap * Math.max(0, colsCount - 1));
            sheet.style.setProperty('--paper-w-mm', pageW + 'mm');
            sheet.style.setProperty('--paper-h-mm', h + 'mm');
            sheet.setAttribute('data-paper-w-mm', String(pageW));
            sheet.setAttribute('data-paper-h-mm', String(h));
        }

        sheet.querySelectorAll('.label-sticker-wrap').forEach(function (wrap) {
            if (w > 0) {
                wrap.setAttribute('data-width-mm', String(w));
                wrap.style.setProperty('--label-w-mm', w + 'mm');
            }
            if (h > 0) {
                wrap.setAttribute('data-height-mm', String(h));
                wrap.style.setProperty('--label-h-mm', h + 'mm');
                var bMax = h * BARCODE_HEIGHT_RATIO;
                wrap.setAttribute('data-barcode-max-mm', String(bMax));
                wrap.style.setProperty('--barcode-max-h-mm', bMax + 'mm');
            }
        });
    }

    function collectLabelCells(root) {
        return Array.from(root.querySelectorAll('.thermal-label-cell')).filter(function (td) {
            return td.querySelector('.label-sticker-wrap');
        });
    }

    function reflowLabelTable(table, cells, cols, startPos) {
        if (!table) {
            return;
        }

        table.innerHTML = '';
        var row = null;
        var slot = 0;

        function openRow() {
            row = document.createElement('tr');
            row.className = 'thermal-label-row';
            table.appendChild(row);
        }

        for (var skip = 0; skip < startPos; skip++) {
            if (slot % cols === 0) {
                openRow();
            }
            var empty = document.createElement('td');
            empty.className = 'thermal-label-cell thermal-label-cell--empty';
            empty.setAttribute('aria-hidden', 'true');
            row.appendChild(empty);
            slot++;
        }

        cells.forEach(function (cell) {
            if (slot % cols === 0) {
                openRow();
            }
            row.appendChild(cell);
            slot++;
        });
    }

    function updateSheetColumnMode(sheet, cols) {
        sheet.classList.toggle('thermal-label-sheet--multi-col', cols > 1);
        sheet.classList.toggle('thermal-label-sheet--single-col', cols === 1);
        sheet.setAttribute('data-stickers-per-row', String(cols));
    }

    function applyLayout(root, expectedCount, cols, startPos, form) {
        root = root || document;
        dedupeLabelContent(root);

        var cells = collectLabelCells(root);

        if (expectedCount <= 0 && cells.length > 0) {
            expectedCount = cells.length;
        }

        while (cells.length > expectedCount) {
            var extra = cells.pop();
            if (extra && extra.parentNode) {
                extra.parentNode.removeChild(extra);
            }
        }

        var sheets = Array.from(root.querySelectorAll('.thermal-label-sheet'));
        if (!sheets.length) {
            return;
        }

        var isFirstSheet = true;
        sheets.forEach(function (sheet) {
            if (form) {
                applyPrintSettingsToSheet(sheet, form);
            } else {
                applyPrintSettingsFromQuery(sheet);
            }
            updateSheetColumnMode(sheet, cols);

            var table = sheet.querySelector('.thermal-label-table');
            if (!table) {
                return;
            }
            var pageCells = Array.from(table.querySelectorAll('.thermal-label-cell')).filter(function (td) {
                return td.querySelector('.label-sticker-wrap');
            });
            var pageStart = isFirstSheet ? startPos : 0;
            reflowLabelTable(table, pageCells, cols, pageStart);
            isFirstSheet = false;
        });
    }

    function applyLayoutFromForm(root) {
        var form = document.getElementById('preview_setting_form');
        if (!form) {
            return applyLayoutFromQuery(root);
        }
        applyLayout(
            root || document.getElementById('preview_box') || document,
            countExpectedLabelsFromForm(form),
            getColumnsFromForm(form),
            getStartPositionFromForm(form),
            form
        );
    }

    function applyLayoutFromQuery(root) {
        applyLayout(
            root || document,
            countExpectedLabelsFromQuery(),
            getColumnsFromQuery(),
            getStartPositionFromQuery(),
            null
        );
    }

    window.LabelPrintEngine = {
        normalize: normalize,
        setupPrintHints: setupPrintHints,
        mmToPx: mmToPx,
        pxToMm: pxToMm,
        applyLayoutFromForm: applyLayoutFromForm,
        applyLayoutFromQuery: applyLayoutFromQuery,
        countExpectedLabelsFromForm: countExpectedLabelsFromForm,
        prepareForPrint: prepareForPrint,
        refreshPreview: refreshPreview,
        lockRenderedStyles: lockRenderedStyles,
        unlockRenderedStyles: unlockRenderedStyles,
        canPrintFromPreview: canPrintFromPreview,
        openPrintFromPreview: openPrintFromPreview,
        openPrintFromServer: openPrintFromServer,
        reflowForZebraPrint: reflowForZebraPrint,
        collectZebraPrintSheetsHtml: collectZebraPrintSheetsHtml,
        isLivePreviewContext: isLivePreviewContext,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupPrintHints);
    } else {
        setupPrintHints();
    }
})(window);
