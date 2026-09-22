.vkpos-thermal,
.vkpos-thermal * {
    box-sizing: border-box;
}

.receipt-container.vkpos-thermal {
    width: 100%;
    display: flex;
    justify-content: center;
    background: transparent;
    color: #000;
    height: auto;
    min-height: 0;
}

.vkpos-thermal .receipt {
    width: 100%;
    max-width: 72mm;
    margin: 0 auto;
    padding: 2mm 1.5mm 3mm;
    height: auto !important;
    min-height: 0 !important;
    overflow-x: hidden;
    overflow-y: visible;
    color: #000;
    background: #fff;
    font-family: "Noto Sans Tamil", "Noto Sans", "Nirmala UI", Latha, Arial, Helvetica, sans-serif;
    font-size: 11px;
    font-size: clamp(9px, 2.5vw, 11px);
    font-weight: 600;
    line-height: 1.35;
    text-align: left;
}

.vkpos-thermal.paper-58 .receipt {
    max-width: 48mm;
    padding: 1.5mm 1mm 2.5mm;
    font-size: 9px;
    font-size: clamp(8px, 3.2vw, 10px);
}

.vkpos-thermal.paper-80 .receipt {
    max-width: 72mm;
}

.vkpos-thermal .business-name,
.vkpos-thermal .shop-name,
.vkpos-thermal .shop-name *,
.vkpos-thermal .receipt-title {
    font-family: "Noto Sans Tamil", "Noto Sans", "Nirmala UI", Latha, Arial, Helvetica, sans-serif;
    text-transform: none;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
    text-align: center;
}

.vkpos-thermal .business-name,
.vkpos-thermal .shop-name,
.vkpos-thermal .shop-name * {
    font-size: 13px;
    font-size: clamp(12px, 4vw, 16px);
    font-weight: 800;
    line-height: 1.25;
    margin: 0;
    color: #000;
}

.vkpos-thermal.paper-58 .business-name,
.vkpos-thermal.paper-58 .shop-name,
.vkpos-thermal.paper-58 .shop-name * {
    font-size: 12px;
    font-size: clamp(11px, 4.2vw, 14px);
}

.vkpos-thermal .receipt-title {
    display: block;
    margin: 3px 0 2px;
    font-size: 12px;
    font-size: clamp(11px, 3vw, 14px);
    font-weight: 800;
    letter-spacing: 0.04em;
}

.vkpos-thermal .shop-meta,
.vkpos-thermal .shop-meta * {
    font-family: inherit;
    font-size: inherit;
    font-weight: 600;
    text-align: center;
    white-space: normal;
    overflow-wrap: break-word;
    word-break: normal;
    line-height: 1.35;
}

.vkpos-thermal .header,
.vkpos-thermal .centered,
.vkpos-thermal .footer {
    text-align: center;
}

.vkpos-thermal .logo {
    display: block;
    max-height: 36px;
    max-width: 100%;
    width: auto;
    height: auto;
    margin: 0 auto 4px;
}

.vkpos-thermal .letterhead {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
    margin: 0 0 4px;
}

.vkpos-thermal .receipt-section {
    margin-block: 4px;
}

.vkpos-thermal .sep {
    border: 0;
    border-bottom: 1px dashed #000;
    margin: 5px 0;
    height: 0;
}

.vkpos-thermal .info-row,
.vkpos-thermal .payment-row,
.vkpos-thermal .total-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 4px;
    width: 100%;
    margin: 1px 0;
}

.vkpos-thermal .info-label,
.vkpos-thermal .payment-label,
.vkpos-thermal .total-label {
    flex: 1 1 auto;
    min-width: 0;
    text-align: left;
    overflow-wrap: break-word;
    word-break: normal;
}

.vkpos-thermal .info-value,
.vkpos-thermal .payment-value,
.vkpos-thermal .total-value {
    flex: 0 0 auto;
    white-space: nowrap;
    text-align: right;
    font-variant-numeric: tabular-nums;
    word-break: keep-all;
    overflow-wrap: normal;
}

.vkpos-thermal .customer-block {
    text-align: left;
    overflow-wrap: break-word;
    word-break: normal;
    margin: 0 0 3px;
}

.vkpos-thermal .receipt-table {
    width: 100%;
    max-width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 2px 0 4px;
    background: transparent;
}

.vkpos-thermal .receipt-table th,
.vkpos-thermal .receipt-table td {
    padding: 2px 1px;
    vertical-align: top;
    border: 0;
    float: none;
    background: transparent;
    color: #000;
    min-width: 0;
}

.vkpos-thermal .receipt-table thead th {
    font-weight: 800;
    text-align: left;
    border-bottom: 1px dashed #000;
    white-space: nowrap !important;
    word-break: keep-all !important;
    overflow-wrap: normal !important;
    hyphens: none;
    font-size: 9px;
}

.vkpos-thermal.paper-58 .receipt-table thead th {
    font-size: 8px;
}

.vkpos-thermal.paper-58 .col-qty {
    width: 22%;
}

.vkpos-thermal.paper-58 .col-price,
.vkpos-thermal.paper-58 .receipt-table thead th.col-price,
.vkpos-thermal.paper-58 .receipt-table td.col-price,
.vkpos-thermal.paper-58 .col-extra,
.vkpos-thermal.paper-58 .receipt-table thead th.col-extra,
.vkpos-thermal.paper-58 .receipt-table td.col-extra {
    display: none;
}

.vkpos-thermal.paper-58 .col-amt {
    width: 32%;
}

.vkpos-thermal.paper-58 .receipt-table td.col-amt {
    font-size: 0.82em;
}

.vkpos-thermal .receipt-table thead th.col-qty,
.vkpos-thermal .receipt-table thead th.col-price,
.vkpos-thermal .receipt-table thead th.col-amt,
.vkpos-thermal .receipt-table thead th.col-extra {
    text-align: right;
}

.vkpos-thermal .col-no {
    width: 1.7em;
    text-align: left;
    white-space: nowrap;
}

.vkpos-thermal .col-item {
    width: auto;
    text-align: left;
    white-space: normal;
    overflow-wrap: break-word;
    word-break: normal;
    hyphens: none;
}

.vkpos-thermal .col-qty {
    width: 16%;
    min-width: 3.2em;
    text-align: right;
    white-space: normal !important;
}

.vkpos-thermal .col-price {
    width: 23%;
    text-align: right;
}

.vkpos-thermal .col-amt {
    width: 25%;
    text-align: right;
}

.vkpos-thermal .col-extra {
    width: 16%;
    text-align: right;
}

.vkpos-thermal .receipt-table td.col-price,
.vkpos-thermal .receipt-table td.col-amt,
.vkpos-thermal .receipt-table td.col-extra {
    white-space: nowrap !important;
    word-break: keep-all !important;
    overflow-wrap: normal !important;
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-size: 0.86em;
}

.vkpos-thermal .qty-num,
.vkpos-thermal .qty-unit {
    display: block;
    white-space: nowrap;
    word-break: keep-all;
    text-align: right;
}

.vkpos-thermal.paper-58 .qty-num,
.vkpos-thermal.paper-58 .qty-unit {
    font-size: 0.9em;
}

.vkpos-thermal .unit-price-hint {
    display: none;
}

.vkpos-thermal.paper-58 .unit-price-hint {
    display: block;
}

.vkpos-thermal .receipt-table.cols-6 .col-price,
.vkpos-thermal .receipt-table.cols-6 .col-amt,
.vkpos-thermal .receipt-table.cols-6 .col-extra {
    width: 15%;
}

.vkpos-thermal .receipt-table.cols-7 .col-qty {
    width: 10%;
}

.vkpos-thermal .receipt-table.cols-7 .col-price,
.vkpos-thermal .receipt-table.cols-7 .col-amt,
.vkpos-thermal .receipt-table.cols-7 .col-extra {
    width: 13%;
}

.vkpos-thermal .total-row.em,
.vkpos-thermal .total-row.em .total-label,
.vkpos-thermal .total-row.em .total-value {
    font-weight: 800;
    font-size: 12px;
    font-size: clamp(11px, 3vw, 13px);
}

.vkpos-thermal .money {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.vkpos-thermal .footer {
    margin: 6px 0 2px;
    font-weight: 600;
    line-height: 1.35;
    overflow-wrap: break-word;
}

.vkpos-thermal .barcode {
    display: block;
    margin: 6px auto 0;
    max-width: 100%;
    height: auto;
}

.vkpos-thermal img {
    max-width: 100%;
    height: auto;
}

.vkpos-thermal .shop-name p,
.vkpos-thermal .shop-name span,
.vkpos-thermal .shop-name strong,
.vkpos-thermal .footer p {
    margin: 0;
    padding: 0;
}

@media screen {
    .receipt-preview,
    .receipt-container.vkpos-thermal {
        width: min(100%, 80mm);
        margin: 0 auto;
    }

    .receipt-container.vkpos-thermal.paper-58 {
        width: min(100%, 58mm);
    }

    .vkpos-thermal .receipt {
        border: 1px dashed #cbd5e1;
    }
}

@media (max-width: 500px) {
    .receipt-preview,
    .receipt-container.vkpos-thermal,
    .receipt-container.vkpos-thermal.paper-58 {
        width: 100%;
    }
}

@media print {
    .vkpos-thermal,
    .vkpos-thermal * {
        color: #000 !important;
        background: transparent !important;
        box-shadow: none !important;
        text-shadow: none !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .receipt-container.vkpos-thermal,
    .receipt-preview {
        width: 100% !important;
        max-width: none !important;
        display: block !important;
        margin: 0 auto !important;
        height: auto !important;
        min-height: 0 !important;
    }

    .vkpos-thermal .receipt {
        width: 100% !important;
        max-width: 72mm;
        margin: 0 auto !important;
        padding: 1mm 1.5mm 2mm !important;
        height: auto !important;
        min-height: 0 !important;
        border: 0 !important;
        overflow-x: hidden !important;
        overflow-y: visible !important;
    }

    .vkpos-thermal.paper-58 .receipt {
        max-width: 48mm;
        padding: 1mm !important;
    }

    .vkpos-thermal .receipt-table thead th {
        white-space: nowrap !important;
        word-break: keep-all !important;
    }

    .vkpos-thermal .receipt-table td.col-price,
    .vkpos-thermal .receipt-table td.col-amt,
    .vkpos-thermal .receipt-table td.col-extra,
    .vkpos-thermal .info-value,
    .vkpos-thermal .payment-value,
    .vkpos-thermal .total-value,
    .vkpos-thermal .money {
        white-space: nowrap !important;
        word-break: keep-all !important;
    }

    .hidden-print,
    .hidden-print * {
        display: none !important;
    }
}

body.vkpos-printing-receipt .main-header,
body.vkpos-printing-receipt .main-sidebar,
body.vkpos-printing-receipt .main-footer,
body.vkpos-printing-receipt .no-print,
body.vkpos-printing-receipt nav,
body.vkpos-printing-receipt aside,
body.vkpos-printing-receipt .modal,
body.vkpos-printing-receipt .scrolltop,
body.vkpos-printing-receipt .pos-header,
body.vkpos-printing-receipt .pos-form-actions,
body.vkpos-printing-receipt .content.no-print {
    display: none !important;
}

@media print {
    body.vkpos-printing-receipt .main-header,
    body.vkpos-printing-receipt .main-sidebar,
    body.vkpos-printing-receipt .main-footer,
    body.vkpos-printing-receipt .no-print,
    body.vkpos-printing-receipt nav,
    body.vkpos-printing-receipt aside,
    body.vkpos-printing-receipt .modal,
    body.vkpos-printing-receipt .scrolltop,
    body.vkpos-printing-receipt .pos-header,
    body.vkpos-printing-receipt .pos-form-actions,
    body.vkpos-printing-receipt .content.no-print {
        display: none !important;
    }

    body.vkpos-printing-receipt #receipt_section,
    body.vkpos-printing-receipt .print_section {
        display: block !important;
        visibility: visible !important;
        position: static !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        background: #fff !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
