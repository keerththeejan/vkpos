@extends('layouts.app')
@section('title', __('lang_v1.sell_payment_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/sell-payment-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $spt_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content spt-shell" id="spt_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.sell_payment_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="spt-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('contact.customer') }}: <span class="spt-print-customer">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.payment_method') }}: <span class="spt-print-method">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.customer_group') }}: <span class="spt-print-group">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="spt-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.sell_payment_report') }}</p>
    </div>

    <div class="spt-header no-print" role="banner">
        <div class="spt-header-left">
            <h1>
                <span class="spt-title-icon" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                {{ __('lang_v1.sell_payment_report') }}
            </h1>
            <p class="spt-subtitle">Customer payments, payment methods and sale references</p>
            <nav class="spt-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.sell_payment_report') }}</span>
            </nav>
        </div>
        <div class="spt-header-actions">
            <div class="spt-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="spt_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} payment, invoice, customer, cheque…" aria-label="Search sell payment report" autocomplete="off">
                <button type="button" class="spt-search-clear" id="spt_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="spt-search-spinner" id="spt_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="spt-btn spt-btn-icon" id="spt_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="spt-btn spt-btn-icon" id="spt_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="spt_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="spt-btn spt-btn-icon" id="spt_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="spt-btn" id="spt_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($spt_has_export)
            <div class="spt-export-wrap">
                <button type="button" class="spt-btn" id="spt_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="spt_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="spt-export-menu" id="spt_export_menu" role="menu" hidden>
                    <button type="button" class="spt-export-item" id="spt_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="spt-export-item" id="spt_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="spt-export-item" id="spt_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="spt-export-item" id="spt_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="spt-btn spt-btn-primary" id="spt_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="spt-header-meta" aria-label="Active filters">
            <div class="spt-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="spt_meta_location">—</strong></div>
            <div class="spt-meta"><span>{{ __('contact.customer') }}</span> <strong id="spt_meta_customer">—</strong></div>
            <div class="spt-meta"><span>{{ __('lang_v1.payment_method') }}</span> <strong id="spt_meta_method">—</strong></div>
            <div class="spt-meta"><span>{{ __('lang_v1.customer_group') }}</span> <strong id="spt_meta_group">—</strong></div>
            <div class="spt-meta"><span>{{ __('report.date_range') }}</span> <strong id="spt_meta_range">—</strong></div>
        </div>
        <div class="spt-settings-panel" id="spt_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="spt_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="spt_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="spt-kpi-grid no-print" aria-label="Sell payment summary">
        <div class="spt-kpi tone-blue">
            <div class="spt-kpi-icon"><i class="bi bi-receipt"></i></div>
            <span class="spt-kpi-label">Matching payments</span>
            <span class="spt-kpi-value" id="spt_kpi_lines">—</span>
            <span class="spt-kpi-hint">Filtered payment lines</span>
        </div>
        <div class="spt-kpi tone-green">
            <div class="spt-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="spt-kpi-label">{{ __('sale.amount') }}</span>
            <span class="spt-kpi-value" id="spt_kpi_amount">—</span>
            <span class="spt-kpi-hint">This page</span>
        </div>
    </div>

    <div class="spt-card spt-filters-card no-print">
        <div class="spt-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Customer, location, payment method, group and date</p>
            </div>
            <button type="button" class="spt-btn spt-btn-ghost" id="spt_filters_toggle" aria-expanded="true" aria-controls="spt_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="spt-card-body" id="spt_filters_body">
            {!! Form::open(['url' => '#', 'method' => 'get', 'id' => 'sell_payment_report_form' ]) !!}
            <div class="spt-filter-grid">
                <div class="form-group">
                    {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2',  'style' => 'width:100%', 'placeholder' => __('messages.all'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',  'style' => 'width:100%', 'placeholder' => __('messages.all'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('payment_types', __('lang_v1.payment_method').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fas fa-money-bill-alt"></i>
                        </span>
                        {!! Form::select('payment_types', $payment_types, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all'), 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('customer_group_filter', __('lang_v1.customer_group').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-users"></i>
                        </span>
                        {!! Form::select('customer_group_filter', $customer_groups, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('spr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'spr_date_filter', 'readonly']); !!}
                </div>
            </div>
            {!! Form::close() !!}

            <div class="spt-period-chips" id="spt_period_chips" role="group" aria-label="Payment period">
                <button type="button" class="spt-chip" data-period="all">All</button>
                <button type="button" class="spt-chip" data-period="today">Today</button>
                <button type="button" class="spt-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="spt-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="spt-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="spt-chip" data-period="this_month">This Month</button>
                <button type="button" class="spt-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="spt-chip" data-period="this_year">This Year</button>
                <button type="button" class="spt-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="spt-filter-actions">
                <button type="button" class="spt-btn spt-btn-primary" id="spt_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="spt-btn" id="spt_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="spt-btn" id="spt_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($spt_has_export)
                <button type="button" class="spt-btn" id="spt_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="spt-exception-strip no-print" id="spt_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="spt-alert spt-alert-error no-print" id="spt_error" hidden role="alert">
        <div>
            <strong>Unable to load sell payment report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="spt-btn spt-btn-primary" id="spt_retry">Retry</button>
    </div>

    <div class="spt-card spt-table-card">
        <div class="spt-card-head no-print">
            <div>
                <h2>{{ __('lang_v1.sell_payment_report') }}</h2>
                <p>Payment lines with method, customer and sale reference</p>
            </div>
        </div>
        <div class="spt-card-body spt-table-body">
            <div class="spt-table-loading no-print" id="spt_table_loading" hidden>
                <div class="spt-skeleton-row"></div>
                <div class="spt-skeleton-row"></div>
                <div class="spt-skeleton-row"></div>
            </div>
            <div class="spt-empty no-print" id="spt_empty" hidden>
                <div class="spt-empty-icon" aria-hidden="true">💳</div>
                <strong>No sell payments found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="spt-btn spt-btn-primary" id="spt_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="sell_payment_report_table">
                    <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('lang_v1.paid_on')</th>
                            <th>@lang('sale.amount')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('lang_v1.contact_id')</th>
                            <th>@lang('lang_v1.customer_group')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('sale.sale')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="display_currency" id="footer_total_amount" data-currency_symbol ="true"></span></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="spt-page-note no-print">Matching payments is the filtered DataTables count. Amount copies the table footer for the current page. Expand the first column on grouped payments to see child lines.</p>

    <div class="spt-export-bar no-print">
        @if($spt_has_export)
        <button type="button" class="spt-btn" id="spt_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="spt-btn" id="spt_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="spt-btn" id="spt_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="spt-btn spt-btn-primary" id="spt_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="spt-drawer" id="spt_drawer" role="dialog" aria-modal="true" aria-labelledby="spt_drawer_title" aria-hidden="true">
    <div class="spt-drawer-head">
        <div>
            <h3 id="spt_drawer_title">Payment</h3>
            <p class="spt-drawer-sku" id="spt_drawer_ref">—</p>
        </div>
        <button type="button" class="spt-btn spt-btn-icon" id="spt_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <details class="spt-drawer-section" open>
        <summary>Overview</summary>
        <dl class="spt-drawer-dl">
            <div><dt>@lang('lang_v1.paid_on')</dt><dd id="spt_drawer_date">—</dd></div>
            <div><dt>@lang('contact.customer')</dt><dd id="spt_drawer_customer">—</dd></div>
            <div><dt>@lang('lang_v1.contact_id')</dt><dd id="spt_drawer_contact">—</dd></div>
            <div><dt>@lang('lang_v1.customer_group')</dt><dd id="spt_drawer_group">—</dd></div>
            <div><dt>@lang('sale.sale')</dt><dd id="spt_drawer_invoice">—</dd></div>
        </dl>
    </details>
    <details class="spt-drawer-section" open>
        <summary>Payment</summary>
        <dl class="spt-drawer-dl">
            <div><dt>@lang('sale.amount')</dt><dd id="spt_drawer_amount">—</dd></div>
            <div><dt>@lang('lang_v1.payment_method')</dt><dd id="spt_drawer_method">—</dd></div>
            <div id="spt_row_cheque"><dt>@lang('lang_v1.cheque_no')</dt><dd id="spt_drawer_cheque">—</dd></div>
            <div id="spt_row_card"><dt>@lang('lang_v1.card_transaction_no')</dt><dd id="spt_drawer_card">—</dd></div>
            <div id="spt_row_bank"><dt>@lang('lang_v1.bank_account_no')</dt><dd id="spt_drawer_bank">—</dd></div>
            <div id="spt_row_txn"><dt>@lang('lang_v1.transaction_no')</dt><dd id="spt_drawer_txn">—</dd></div>
        </dl>
    </details>
    <div class="spt-drawer-actions" id="spt_drawer_actions"></div>
</aside>
<div class="spt-drawer-backdrop" id="spt_drawer_backdrop" hidden></div>
<div class="spt-toast-host" id="spt_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/sell-payment-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
