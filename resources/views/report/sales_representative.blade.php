@extends('layouts.app')
@section('title', __('report.sales_representative'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/sales-representative-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $srr_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content srr-shell" id="srr_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.sales_representative') }}</h2>
        <p>
            {{ __('report.user') }}: <span class="srr-print-user">—</span>
            &nbsp;·&nbsp;
            {{ __('business.business_location') }}: <span class="srr-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="srr-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('report.sales_representative') }}</p>
    </div>

    <div class="srr-header no-print" role="banner">
        <div class="srr-header-left">
            <h1>
                <span class="srr-title-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                {{ __('report.sales_representative') }}
            </h1>
            <p class="srr-subtitle">Sales performance, expenses and commission for a selected user</p>
            <nav class="srr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.sales_representative') }}</span>
            </nav>
        </div>
        <div class="srr-header-actions">
            <div class="srr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="srr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} invoice, customer, reference…" aria-label="Search sales representative report" autocomplete="off">
                <button type="button" class="srr-search-clear" id="srr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="srr-search-spinner" id="srr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="srr-btn srr-btn-icon" id="srr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="srr-btn srr-btn-icon" id="srr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="srr_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="srr-btn srr-btn-icon" id="srr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="srr-btn" id="srr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($srr_has_export)
            <div class="srr-export-wrap">
                <button type="button" class="srr-btn" id="srr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="srr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="srr-export-menu" id="srr_export_menu" role="menu" hidden>
                    <button type="button" class="srr-export-item" id="srr_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="srr-export-item" id="srr_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="srr-export-item" id="srr_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="srr-export-item" id="srr_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="srr-btn srr-btn-primary" id="srr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="srr-header-meta" aria-label="Active filters">
            <div class="srr-meta"><span>{{ __('report.user') }}</span> <strong id="srr_meta_user">—</strong></div>
            <div class="srr-meta"><span>{{ __('business.business_location') }}</span> <strong id="srr_meta_location">—</strong></div>
            <div class="srr-meta"><span>{{ __('report.date_range') }}</span> <strong id="srr_meta_range">—</strong></div>
        </div>
        <div class="srr-settings-panel" id="srr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="srr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="srr_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="srr-kpi-grid no-print" aria-label="Sales representative summary">
        <div class="srr-kpi tone-blue">
            <div class="srr-kpi-icon"><i class="bi bi-receipt"></i></div>
            <span class="srr-kpi-label">{{ __('report.total_sell') }}</span>
            <span class="srr-kpi-value" id="sr_total_sales"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Existing summary</span>
        </div>
        <div class="srr-kpi tone-orange">
            <div class="srr-kpi-icon"><i class="bi bi-arrow-return-left"></i></div>
            <span class="srr-kpi-label">{{ __('lang_v1.total_sales_return') }}</span>
            <span class="srr-kpi-value" id="sr_total_sales_return"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Existing summary</span>
        </div>
        <div class="srr-kpi tone-green">
            <div class="srr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="srr-kpi-label">{{ __('report.total_sell') }} − {{ __('lang_v1.total_sales_return') }}</span>
            <span class="srr-kpi-value" id="sr_total_sales_final"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Existing summary</span>
        </div>
        <div class="srr-kpi tone-slate">
            <div class="srr-kpi-icon"><i class="bi bi-wallet2"></i></div>
            <span class="srr-kpi-label">{{ __('report.total_expense') }}</span>
            <span class="srr-kpi-value" id="sr_total_expenses"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Existing summary</span>
        </div>
        <div class="srr-kpi tone-teal hide" id="total_payment_with_commsn_div">
            <div class="srr-kpi-icon"><i class="bi bi-credit-card"></i></div>
            <span class="srr-kpi-label">{{ __('lang_v1.total_payment_with_commsn') }}</span>
            <span class="srr-kpi-value" id="total_payment_with_commsn"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Shown when a user is selected</span>
        </div>
        <div class="srr-kpi tone-violet hide" id="total_commission_div">
            <div class="srr-kpi-icon"><i class="bi bi-percent"></i></div>
            <span class="srr-kpi-label">{{ __('lang_v1.total_sale_commission') }}</span>
            <span class="srr-kpi-value" id="sr_total_commission"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="srr-kpi-hint">Shown when a user is selected</span>
        </div>
    </div>

    <div class="srr-card srr-filters-card no-print">
        <div class="srr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>User, location and date range update the existing report</p>
            </div>
            <button type="button" class="srr-btn srr-btn-ghost" id="srr_filters_toggle" aria-expanded="true" aria-controls="srr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="srr-card-body" id="srr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'sales_representative_filter_form' ]) !!}
            <div class="srr-filter-grid">
                <div class="form-group">
                    {!! Form::label('sr_id',  __('report.user') . ':') !!}
                    {!! Form::select('sr_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('sr_business_id',  __('business.business_location') . ':') !!}
                    {!! Form::select('sr_business_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('sr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'sr_date_filter', 'readonly']); !!}
                </div>
            </div>
            {!! Form::close() !!}

            <div class="srr-period-chips" id="srr_period_chips" role="group" aria-label="Sales period">
                <button type="button" class="srr-chip" data-period="all">All</button>
                <button type="button" class="srr-chip" data-period="today">Today</button>
                <button type="button" class="srr-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="srr-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="srr-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="srr-chip" data-period="this_month">This Month</button>
                <button type="button" class="srr-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="srr-chip" data-period="this_year">This Year</button>
                <button type="button" class="srr-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="srr-filter-actions">
                <button type="button" class="srr-btn srr-btn-primary" id="srr_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="srr-btn" id="srr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="srr-btn" id="srr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($srr_has_export)
                <button type="button" class="srr-btn" id="srr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="srr-exception-strip no-print" id="srr_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="srr-alert srr-alert-error no-print" id="srr_error" hidden role="alert">
        <div>
            <strong>Unable to load sales representative report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="srr-btn srr-btn-primary" id="srr_retry">Retry</button>
    </div>

    <div class="srr-empty no-print" id="srr_empty" hidden>
        <div class="srr-empty-icon" aria-hidden="true">👤</div>
        <strong>No sales representative data found</strong>
        <p>Try changing your search or filters.</p>
        <button type="button" class="srr-btn srr-btn-primary" id="srr_empty_clear">Clear Filters</button>
    </div>

    <div class="srr-table-loading no-print" id="srr_table_loading" hidden>
        <div class="srr-skeleton-row"></div>
        <div class="srr-skeleton-row"></div>
        <div class="srr-skeleton-row"></div>
    </div>

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#sr_sales_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cog" aria-hidden="true"></i> @lang('lang_v1.sales_added')</a>
            </li>
            <li>
                <a href="#sr_commission_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cog" aria-hidden="true"></i> @lang('lang_v1.sales_with_commission')</a>
            </li>
            <li>
                <a href="#sr_expenses_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cog" aria-hidden="true"></i> @lang('expense.expenses')</a>
            </li>
            @if(!empty($pos_settings['cmmsn_calculation_type']) && $pos_settings['cmmsn_calculation_type'] == 'payment_received')
                <li>
                    <a href="#sr_payments_with_cmmsn_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cog" aria-hidden="true"></i> @lang('lang_v1.payments_with_cmmsn')</a>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="sr_sales_tab">
                @include('report.partials.sales_representative_sales')
            </div>
            <div class="tab-pane" id="sr_commission_tab">
                @include('report.partials.sales_representative_commission')
            </div>
            <div class="tab-pane" id="sr_expenses_tab">
                @include('report.partials.sales_representative_expenses')
            </div>
            @if(!empty($pos_settings['cmmsn_calculation_type']) && $pos_settings['cmmsn_calculation_type'] == 'payment_received')
                <div class="tab-pane" id="sr_payments_with_cmmsn_tab">
                    @include('report.partials.sales_representative_payments_with_cmmsn')
                </div>
            @endif
        </div>
    </div>

    <p class="srr-page-note no-print">Summary totals come from the existing sales-representative AJAX. Tables are sales, commission, expense and payment lines for the selected user — not a ranking of representatives.</p>

    <div class="srr-export-bar no-print">
        @if($srr_has_export)
        <button type="button" class="srr-btn" id="srr_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="srr-btn" id="srr_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="srr-btn" id="srr_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="srr-btn srr-btn-primary" id="srr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="srr-drawer" id="srr_drawer" role="dialog" aria-modal="true" aria-labelledby="srr_drawer_title" aria-hidden="true">
    <div class="srr-drawer-head">
        <div>
            <h3 id="srr_drawer_title">Details</h3>
            <p class="srr-drawer-sku" id="srr_drawer_sub">—</p>
        </div>
        <button type="button" class="srr-btn srr-btn-icon" id="srr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <dl class="srr-drawer-dl" id="srr_drawer_dl"></dl>
    <div class="srr-drawer-actions" id="srr_drawer_actions"></div>
</aside>
<div class="srr-drawer-backdrop" id="srr_drawer_backdrop" hidden></div>
<div class="srr-toast-host" id="srr_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/sales-representative-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
