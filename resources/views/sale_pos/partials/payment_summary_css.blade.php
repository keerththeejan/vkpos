.payment-summary,
.pos-pay-summary-card.payment-summary {
    background: #ffffff;
    border: 1px solid #d9dde5;
    border-radius: 22px;
    padding: 28px 30px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    height: auto;
    min-height: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: #111827;
    overflow-x: hidden;
    overflow-y: visible;
}

.payment-summary .payment-summary-title {
    margin: 0 0 24px;
    font-size: 21px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #111827;
    line-height: 1.2;
}

.payment-summary .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    width: 100%;
    margin: 0 0 22px;
    min-width: 0;
}

.payment-summary .summary-row:last-child {
    margin-bottom: 0;
}

.payment-summary .summary-label {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
}

.payment-summary .summary-value {
    flex: 0 0 auto;
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
}

.payment-summary .summary-value span {
    font: inherit;
    color: inherit;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.payment-summary .summary-row-items .summary-label,
.payment-summary .summary-row-items .summary-value {
    font-size: 20px;
    font-weight: 700;
}

.payment-summary .summary-sep {
    display: block;
    width: 100%;
    height: 0;
    border: 0;
    border-top: 1px solid #d9dde5;
    margin: 18px 0;
}

.payment-summary .summary-sep-change {
    margin: 26px 0;
}

.payment-summary .summary-row-total {
    margin-bottom: 0;
    align-items: baseline;
}

.payment-summary .summary-row-total .summary-label {
    font-size: 22px;
    font-weight: 800;
}

.payment-summary .summary-total-value,
.payment-summary .summary-row-total .summary-value,
.payment-summary .summary-total-value span,
.payment-summary .summary-row-total .summary-value span,
.payment-summary .summary-total-value.lead,
.payment-summary .summary-row-total .lead {
    font-size: 40px !important;
    font-weight: 800 !important;
    letter-spacing: -0.03em;
    white-space: nowrap;
    color: #111827 !important;
}

.payment-summary .summary-row-paid {
    margin-top: 26px;
    margin-bottom: 0;
    align-items: baseline;
}

.payment-summary .summary-row-paid .summary-label {
    font-size: 20px;
    font-weight: 700;
}

.payment-summary .summary-paid-value,
.payment-summary .summary-row-paid .summary-value,
.payment-summary .summary-paid-value span,
.payment-summary .summary-row-paid .summary-value span,
.payment-summary .summary-paid-value.lead,
.payment-summary .summary-row-paid .lead {
    font-size: 30px !important;
    font-weight: 800 !important;
    white-space: nowrap;
    color: #111827 !important;
}

.payment-summary .summary-row-change {
    margin-bottom: 0;
    align-items: baseline;
}

.payment-summary .summary-row-change .summary-label {
    font-size: 21px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.payment-summary .summary-change-value,
.payment-summary .summary-row-change .summary-value,
.payment-summary .summary-change-value span,
.payment-summary .summary-row-change .summary-value span,
.payment-summary .summary-change-value.lead,
.payment-summary .summary-row-change .lead {
    font-size: 40px !important;
    font-weight: 800 !important;
    letter-spacing: -0.03em;
    white-space: nowrap;
    color: #111827 !important;
}

.payment-summary .summary-row-due {
    display: none;
    margin-top: 18px;
    margin-bottom: 0;
}

.payment-summary.is-balance-due .summary-row-due {
    display: flex;
}

.payment-summary .summary-row-due .summary-label,
.payment-summary .summary-row-due .summary-value,
.payment-summary .summary-row-due .summary-value span {
    font-size: 18px;
    font-weight: 700;
}

.payment-summary .summary-row-extra {
    margin-bottom: 16px;
}

.payment-summary .summary-row-extra .summary-label,
.payment-summary .summary-row-extra .summary-value {
    font-size: 16px;
    font-weight: 600;
}

.payment-summary .summary-edit {
    margin-left: 6px;
    font-size: 13px;
    color: #6b7280;
    cursor: pointer;
}

.payment-summary .summary-edit:hover,
.payment-summary .summary-edit:focus {
    color: #111827;
}

.payment-summary .text-success,
.payment-summary .text-danger {
    color: #111827 !important;
}

.payment-summary .box,
.payment-summary .box-body {
    background: transparent;
    box-shadow: none;
    border: 0;
    padding: 0;
    margin: 0;
}

.pos_form_totals {
    margin-left: 0;
    margin-right: 0;
}

.pos_form_totals > [class*="col-"] {
    padding-left: 0;
    padding-right: 0;
}

#modal_payment .premium-payment-summary.box {
    background: transparent;
    border: 0;
    box-shadow: none;
    margin: 0;
}

/* Beat older POS terminal/premium wrappers without changing business CSS files */
body.hold-transition.lockscreen .pos_form_totals,
.premium-pos-shell .pos_form_totals {
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-width: none;
    width: 100%;
    margin-top: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

body.hold-transition.lockscreen .pos_form_totals .payment-summary,
.premium-pos-shell .pos_form_totals .payment-summary,
#modal_payment .payment-summary.pos-pay-summary-card,
#modal_payment .payment-summary.premium-payment-summary {
    background: #ffffff !important;
    border: 1px solid #d9dde5 !important;
    border-radius: 22px !important;
    padding: 28px 30px !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06) !important;
    height: auto !important;
    min-height: 0 !important;
    max-width: 100%;
}

#modal_payment .payment-summary .payment-summary-title {
    font-size: 21px !important;
    font-weight: 800 !important;
    letter-spacing: 0.08em !important;
    margin: 0 0 24px !important;
    color: #111827 !important;
}

#modal_payment .payment-summary .pos-pay-row,
#modal_payment .payment-summary .summary-row {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center;
    flex-wrap: nowrap !important;
    margin: 0 0 22px !important;
    width: 100%;
}

#modal_payment .payment-summary .summary-row-total,
#modal_payment .payment-summary .summary-row-paid,
#modal_payment .payment-summary .summary-row-change {
    align-items: baseline;
    margin-bottom: 0 !important;
}

#modal_payment .payment-summary .summary-row-paid {
    margin-top: 26px !important;
}

#modal_payment .payment-summary .summary-label,
#modal_payment .payment-summary .pos-pay-label,
#modal_payment .payment-summary.pos-pay-summary-card strong,
#modal_payment .payment-summary.premium-payment-summary strong {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #111827 !important;
    text-transform: none;
    letter-spacing: 0;
}

#modal_payment .payment-summary .summary-row-items .summary-label,
#modal_payment .payment-summary .summary-row-items .summary-value,
#modal_payment .payment-summary .summary-row-items .pos-pay-value {
    font-size: 20px !important;
    font-weight: 700 !important;
}

#modal_payment .payment-summary .summary-row-total .summary-label,
#modal_payment .payment-summary .summary-row-total .pos-pay-label {
    font-size: 22px !important;
    font-weight: 800 !important;
}

#modal_payment .payment-summary .summary-row-paid .summary-label,
#modal_payment .payment-summary .summary-row-paid .pos-pay-label {
    font-size: 20px !important;
    font-weight: 700 !important;
}

#modal_payment .payment-summary .summary-row-change .summary-label,
#modal_payment .payment-summary .summary-row-change .pos-pay-label {
    font-size: 21px !important;
    font-weight: 800 !important;
    letter-spacing: 0.06em !important;
    text-transform: uppercase !important;
}

#modal_payment .payment-summary .summary-row-total .total_payable_span,
#modal_payment .payment-summary .summary-row-total .pos-pay-value,
#modal_payment .payment-summary .summary-total-value,
#modal_payment .payment-summary .summary-total-value span {
    font-size: 40px !important;
    font-weight: 800 !important;
    color: #111827 !important;
    white-space: nowrap !important;
}

#modal_payment .payment-summary .summary-row-paid .total_paying,
#modal_payment .payment-summary .summary-row-paid .pos-pay-value,
#modal_payment .payment-summary .summary-paid-value,
#modal_payment .payment-summary .summary-paid-value span {
    font-size: 30px !important;
    font-weight: 800 !important;
    color: #111827 !important;
    white-space: nowrap !important;
}

#modal_payment .payment-summary .summary-row-change .change_return_span,
#modal_payment .payment-summary .summary-row-change .pos-pay-value,
#modal_payment .payment-summary .summary-change-value,
#modal_payment .payment-summary .summary-change-value span {
    font-size: 40px !important;
    font-weight: 800 !important;
    color: #111827 !important;
    white-space: nowrap !important;
}

#modal_payment .payment-summary .pos-pay-divider,
#modal_payment .payment-summary .summary-sep {
    height: 0 !important;
    background: transparent !important;
    border: 0;
    border-top: 1px solid #d9dde5 !important;
    margin: 18px 0 !important;
}

#modal_payment .payment-summary .summary-sep-change {
    margin: 26px 0 !important;
}

#modal_payment .payment-summary .pos-pay-change-wrap,
#modal_payment .payment-summary .summary-row-change {
    display: flex !important;
}

#modal_payment .payment-summary .pos-pay-due-wrap,
#modal_payment .payment-summary .summary-row-due {
    display: none !important;
}

#modal_payment .payment-summary.is-balance-due .pos-pay-due-wrap,
#modal_payment .payment-summary.is-balance-due .summary-row-due {
    display: flex !important;
}

@media (max-width: 991px) {
    body.hold-transition.lockscreen .pos_form_totals,
    .premium-pos-shell .pos_form_totals {
        max-width: 100% !important;
        margin-left: 0 !important;
    }

    .payment-summary,
    .pos-pay-summary-card.payment-summary,
    #modal_payment .payment-summary.pos-pay-summary-card,
    #modal_payment .payment-summary.premium-payment-summary {
        width: 100%;
        padding: 22px 20px !important;
        border-radius: 18px !important;
    }

    .payment-summary .summary-total-value,
    .payment-summary .summary-change-value,
    .payment-summary .summary-row-total .summary-value,
    .payment-summary .summary-row-change .summary-value,
    .payment-summary .summary-total-value span,
    .payment-summary .summary-change-value span,
    .payment-summary .summary-row-total .summary-value span,
    .payment-summary .summary-row-change .summary-value span,
    .payment-summary .summary-row-total .lead,
    .payment-summary .summary-row-change .lead {
        font-size: 34px !important;
    }
}

@media (max-width: 480px) {
    .payment-summary,
    .pos-pay-summary-card.payment-summary,
    #modal_payment .payment-summary.pos-pay-summary-card,
    #modal_payment .payment-summary.premium-payment-summary {
        padding: 18px 16px !important;
        border-radius: 16px !important;
    }

    .payment-summary .payment-summary-title {
        font-size: 18px;
        margin-bottom: 18px;
    }

    .payment-summary .summary-row {
        margin-bottom: 16px;
    }

    .payment-summary .summary-label,
    .payment-summary .summary-value,
    .payment-summary .summary-row-items .summary-label,
    .payment-summary .summary-row-items .summary-value {
        font-size: 16px;
    }

    .payment-summary .summary-total-value,
    .payment-summary .summary-change-value,
    .payment-summary .summary-row-total .summary-value,
    .payment-summary .summary-row-change .summary-value,
    .payment-summary .summary-total-value span,
    .payment-summary .summary-change-value span,
    .payment-summary .summary-row-total .summary-value span,
    .payment-summary .summary-row-change .summary-value span,
    .payment-summary .summary-row-total .lead,
    .payment-summary .summary-row-change .lead {
        font-size: 30px !important;
    }

    .payment-summary .summary-paid-value,
    .payment-summary .summary-row-paid .summary-value,
    .payment-summary .summary-paid-value span,
    .payment-summary .summary-row-paid .summary-value span,
    .payment-summary .summary-row-paid .lead {
        font-size: 24px !important;
    }
}
