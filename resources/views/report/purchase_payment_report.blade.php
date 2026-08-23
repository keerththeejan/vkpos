@extends('layouts.app')
@section('title', __('lang_v1.purchase_payment_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/purchase-payment-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $pmt_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content pmt-shell" id="pmt_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.purchase_payment_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="pmt-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('purchase.supplier') }}: <span class="pmt-print-supplier">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="pmt-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.purchase_payment_report') }}</p>
    </div>

    <div class="pmt-header no-print" role="banner">
        <div class="pmt-header-left">
            <h1>
                <span class="pmt-title-icon" aria-hidden="true"><i class="bi bi-credit-card"></i></span>
                {{ __('lang_v1.purchase_payment_report') }}
            </h1>
            <p class="pmt-subtitle">Supplier payments, payment methods and purchase references</p>
            <nav class="pmt-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.purchase_payment_report') }}</span>
            </nav>
        </div>
        <div class="pmt-header-actions">
            <div class="pmt-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="pmt_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} payment, purchase, supplier, cheque…" aria-label="Search purchase payment report" autocomplete="off">
                <button type="button" class="pmt-search-clear" id="pmt_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="pmt-search-spinner" id="pmt_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="pmt-btn pmt-btn-icon" id="pmt_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="pmt-btn pmt-btn-icon" id="pmt_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="pmt_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="pmt-btn pmt-btn-icon" id="pmt_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="pmt-btn" id="pmt_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($pmt_has_export)
            <div class="pmt-export-wrap">
                <button type="button" class="pmt-btn" id="pmt_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="pmt_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="pmt-export-menu" id="pmt_export_menu" role="menu" hidden>
                    <button type="button" class="pmt-export-item" id="pmt_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="pmt-export-item" id="pmt_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="pmt-export-item" id="pmt_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="pmt-export-item" id="pmt_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="pmt-btn pmt-btn-primary" id="pmt_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="pmt-header-meta" aria-label="Active filters">
            <div class="pmt-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="pmt_meta_location">—</strong></div>
            <div class="pmt-meta"><span>{{ __('purchase.supplier') }}</span> <strong id="pmt_meta_supplier">—</strong></div>
            <div class="pmt-meta"><span>{{ __('report.date_range') }}</span> <strong id="pmt_meta_range">—</strong></div>
        </div>
        <div class="pmt-settings-panel" id="pmt_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="pmt_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="pmt_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="pmt-kpi-grid no-print" aria-label="Purchase payment summary">
        <div class="pmt-kpi tone-blue">
            <div class="pmt-kpi-icon"><i class="bi bi-receipt"></i></div>
            <span class="pmt-kpi-label">Matching payments</span>
            <span class="pmt-kpi-value" id="pmt_kpi_lines">—</span>
            <span class="pmt-kpi-hint">Filtered payment lines</span>
        </div>
        <div class="pmt-kpi tone-green">
            <div class="pmt-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="pmt-kpi-label">{{ __('sale.amount') }}</span>
            <span class="pmt-kpi-value" id="pmt_kpi_amount">—</span>
            <span class="pmt-kpi-hint">This page</span>
        </div>
    </div>

    <div class="pmt-card pmt-filters-card no-print">
        <div class="pmt-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Supplier, location and payment date</p>
            </div>
            <button type="button" class="pmt-btn pmt-btn-ghost" id="pmt_filters_toggle" aria-expanded="true" aria-controls="pmt_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="pmt-card-body" id="pmt_filters_body">
            {!! Form::open(['url' => '#', 'method' => 'get', 'id' => 'purchase_payment_report_form' ]) !!}
            <div class="pmt-filter-grid">
                <div class="form-group">
                    {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ppr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'ppr_date_filter', 'readonly']); !!}
                </div>
            </div>
            {!! Form::close() !!}

            <div class="pmt-period-chips" id="pmt_period_chips" role="group" aria-label="Payment period">
                <button type="button" class="pmt-chip" data-period="all">All</button>
                <button type="button" class="pmt-chip" data-period="today">Today</button>
                <button type="button" class="pmt-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="pmt-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="pmt-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="pmt-chip" data-period="this_month">This Month</button>
                <button type="button" class="pmt-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="pmt-chip" data-period="this_year">This Year</button>
                <button type="button" class="pmt-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="pmt-filter-actions">
                <button type="button" class="pmt-btn pmt-btn-primary" id="pmt_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="pmt-btn" id="pmt_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="pmt-btn" id="pmt_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($pmt_has_export)
                <button type="button" class="pmt-btn" id="pmt_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="pmt-exception-strip no-print" id="pmt_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="pmt-alert pmt-alert-error no-print" id="pmt_error" hidden role="alert">
        <div>
            <strong>Unable to load purchase payment report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="pmt-btn pmt-btn-primary" id="pmt_retry">Retry</button>
    </div>

    <div class="pmt-card pmt-table-card">
        <div class="pmt-card-head no-print">
            <div>
                <h2>{{ __('lang_v1.purchase_payment_report') }}</h2>
                <p>Payment lines with method, supplier and purchase reference</p>
            </div>
        </div>
        <div class="pmt-card-body pmt-table-body">
            <div class="pmt-table-loading no-print" id="pmt_table_loading" hidden>
                <div class="pmt-skeleton-row"></div>
                <div class="pmt-skeleton-row"></div>
                <div class="pmt-skeleton-row"></div>
            </div>
            <div class="pmt-empty no-print" id="pmt_empty" hidden>
                <div class="pmt-empty-icon" aria-hidden="true">💳</div>
                <strong>No purchase payments found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="pmt-btn pmt-btn-primary" id="pmt_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="purchase_payment_report_table">
                    <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('lang_v1.paid_on')</th>
                            <th>@lang('sale.amount')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('lang_v1.purchase')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="display_currency" id="footer_total_amount" data-currency_symbol ="true"></span></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="pmt-page-note no-print">Matching payments is the filtered DataTables count. Amount copies the table footer for the current page. Expand the first column on grouped payments to see child lines.</p>

    <div class="pmt-export-bar no-print">
        @if($pmt_has_export)
        <button type="button" class="pmt-btn" id="pmt_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="pmt-btn" id="pmt_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="pmt-btn" id="pmt_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="pmt-btn pmt-btn-primary" id="pmt_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="pmt-drawer" id="pmt_drawer" role="dialog" aria-modal="true" aria-labelledby="pmt_drawer_title" aria-hidden="true">
    <div class="pmt-drawer-head">
        <div>
            <h3 id="pmt_drawer_title">Payment</h3>
            <p class="pmt-drawer-sku" id="pmt_drawer_ref">—</p>
        </div>
        <button type="button" class="pmt-btn pmt-btn-icon" id="pmt_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <details class="pmt-drawer-section" open>
        <summary>Overview</summary>
        <dl class="pmt-drawer-dl">
            <div><dt>@lang('lang_v1.paid_on')</dt><dd id="pmt_drawer_date">—</dd></div>
            <div><dt>@lang('purchase.supplier')</dt><dd id="pmt_drawer_supplier">—</dd></div>
            <div><dt>@lang('lang_v1.purchase')</dt><dd id="pmt_drawer_purchase">—</dd></div>
        </dl>
    </details>
    <details class="pmt-drawer-section" open>
        <summary>Payment</summary>
        <dl class="pmt-drawer-dl">
            <div><dt>@lang('sale.amount')</dt><dd id="pmt_drawer_amount">—</dd></div>
            <div><dt>@lang('lang_v1.payment_method')</dt><dd id="pmt_drawer_method">—</dd></div>
            <div id="pmt_row_cheque"><dt>@lang('lang_v1.cheque_no')</dt><dd id="pmt_drawer_cheque">—</dd></div>
            <div id="pmt_row_card"><dt>@lang('lang_v1.card_transaction_no')</dt><dd id="pmt_drawer_card">—</dd></div>
            <div id="pmt_row_bank"><dt>@lang('lang_v1.bank_account_no')</dt><dd id="pmt_drawer_bank">—</dd></div>
            <div id="pmt_row_txn"><dt>@lang('lang_v1.transaction_no')</dt><dd id="pmt_drawer_txn">—</dd></div>
        </dl>
    </details>
    <div class="pmt-drawer-actions" id="pmt_drawer_actions"></div>
</aside>
<div class="pmt-drawer-backdrop" id="pmt_drawer_backdrop" hidden></div>
<div class="pmt-toast-host" id="pmt_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/purchase-payment-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
