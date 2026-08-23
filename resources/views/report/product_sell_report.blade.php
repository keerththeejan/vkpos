@extends('layouts.app')
@section('title', __('lang_v1.product_sell_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/product-sell-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $ssr_has_export = auth()->user()->can('view_export_buttons');
    $startDay = Carbon::now()->startOfDay();
    $endDay   = $startDay->copy()->endOfDay();
@endphp

@section('content')

<section class="content ssr-shell" id="ssr_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.product_sell_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="ssr-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('contact.customer') }}: <span class="ssr-print-customer">—</span>
            &nbsp;·&nbsp;
            {{ __('product.category') }}: <span class="ssr-print-category">—</span>
            &nbsp;·&nbsp;
            {{ __('product.brand') }}: <span class="ssr-print-brand">—</span>
        </p>
        <p>
            {{ __('report.date_range') }}: <span class="ssr-print-range">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.time_range') }}: <span class="ssr-print-time">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.product_sell_report') }}</p>
    </div>

    <div class="ssr-header no-print" role="banner">
        <div class="ssr-header-left">
            <h1>
                <span class="ssr-title-icon" aria-hidden="true"><i class="bi bi-graph-up"></i></span>
                {{ __('lang_v1.product_sell_report') }}
            </h1>
            <p class="ssr-subtitle">Product sales, revenue and sales performance</p>
            <nav class="ssr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.product_sell_report') }}</span>
            </nav>
        </div>
        <div class="ssr-header-actions">
            <div class="ssr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="ssr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} product, SKU, customer, invoice…" aria-label="Search product sell report" autocomplete="off">
                <button type="button" class="ssr-search-clear" id="ssr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="ssr-search-spinner" id="ssr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="ssr-btn ssr-btn-icon" id="ssr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="ssr-btn ssr-btn-icon" id="ssr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="ssr_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="ssr-btn ssr-btn-icon" id="ssr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="ssr-btn" id="ssr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($ssr_has_export)
            <div class="ssr-export-wrap">
                <button type="button" class="ssr-btn" id="ssr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="ssr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="ssr-export-menu" id="ssr_export_menu" role="menu" hidden>
                    <button type="button" class="ssr-export-item" id="ssr_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="ssr-export-item" id="ssr_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="ssr-export-item" id="ssr_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="ssr-export-item" id="ssr_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="ssr-btn ssr-btn-primary" id="ssr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="ssr-header-meta" aria-label="Active filters">
            <div class="ssr-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="ssr_meta_location">—</strong></div>
            <div class="ssr-meta"><span>{{ __('contact.customer') }}</span> <strong id="ssr_meta_customer">—</strong></div>
            <div class="ssr-meta"><span>{{ __('product.category') }}</span> <strong id="ssr_meta_category">—</strong></div>
            <div class="ssr-meta"><span>{{ __('product.brand') }}</span> <strong id="ssr_meta_brand">—</strong></div>
            <div class="ssr-meta"><span>{{ __('report.date_range') }}</span> <strong id="ssr_meta_range">—</strong></div>
        </div>
        <div class="ssr-settings-panel" id="ssr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="ssr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="ssr_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="ssr-kpi-grid no-print" aria-label="Product sell summary">
        <div class="ssr-kpi tone-blue" id="ssr_kpi_a">
            <div class="ssr-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="ssr-kpi-label" id="ssr_kpi_a_label">Matching lines</span>
            <span class="ssr-kpi-value" id="ssr_kpi_a_value">—</span>
            <span class="ssr-kpi-hint" id="ssr_kpi_a_hint">Filtered rows</span>
        </div>
        <div class="ssr-kpi tone-teal" id="ssr_kpi_b">
            <div class="ssr-kpi-icon"><i class="bi bi-stack"></i></div>
            <span class="ssr-kpi-label" id="ssr_kpi_b_label">{{ __('sale.qty') }}</span>
            <span class="ssr-kpi-value" id="ssr_kpi_b_value">—</span>
            <span class="ssr-kpi-hint" id="ssr_kpi_b_hint">This page</span>
        </div>
        <div class="ssr-kpi tone-orange" id="ssr_kpi_c">
            <div class="ssr-kpi-icon"><i class="bi bi-percent"></i></div>
            <span class="ssr-kpi-label" id="ssr_kpi_c_label">{{ __('sale.tax') }}</span>
            <span class="ssr-kpi-value" id="ssr_kpi_c_value">—</span>
            <span class="ssr-kpi-hint" id="ssr_kpi_c_hint">This page</span>
        </div>
        <div class="ssr-kpi tone-green" id="ssr_kpi_d">
            <div class="ssr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="ssr-kpi-label" id="ssr_kpi_d_label">{{ __('sale.total') }}</span>
            <span class="ssr-kpi-value" id="ssr_kpi_d_value">—</span>
            <span class="ssr-kpi-hint" id="ssr_kpi_d_hint">This page</span>
        </div>
    </div>

    <div class="ssr-card ssr-filters-card no-print">
        <div class="ssr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Product, customer, location, category, brand and date</p>
            </div>
            <button type="button" class="ssr-btn ssr-btn-ghost" id="ssr_filters_toggle" aria-expanded="true" aria-controls="ssr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="ssr-card-body" id="ssr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'product_sell_report_form' ]) !!}
            <div class="ssr-filter-grid">
                <div class="form-group">
                    {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="hidden" value="" id="variation_id">
                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('psr_customer_group_id', __( 'lang_v1.customer_group_name' ) . ':') !!}
                    {!! Form::select('psr_customer_group_id', $customer_group, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'psr_customer_group_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('category_id', __('product.category') . ':') !!}
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'psr_filter_category_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('brand_id', __('product.brand') . ':') !!}
                    {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'psr_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('product_sr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_sr_date_filter', 'readonly']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('product_sr_start_time', __('lang_v1.time_range') . ':') !!}
                    <div class="ssr-time-pair">
                        {!! Form::text('start_time', @format_time($startDay), ['style' => __('lang_v1.select_a_date_range'), 'class' => 'form-control width-50 f-left', 'id' => 'product_sr_start_time']); !!}
                        {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50 f-left', 'id' => 'product_sr_end_time']); !!}
                    </div>
                </div>
            </div>
            {!! Form::close() !!}

            <div class="ssr-period-chips" id="ssr_period_chips" role="group" aria-label="Sales period">
                <button type="button" class="ssr-chip" data-period="all">All</button>
                <button type="button" class="ssr-chip" data-period="today">Today</button>
                <button type="button" class="ssr-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="ssr-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="ssr-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="ssr-chip" data-period="this_month">This Month</button>
                <button type="button" class="ssr-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="ssr-chip" data-period="this_year">This Year</button>
                <button type="button" class="ssr-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="ssr-filter-actions">
                <button type="button" class="ssr-btn ssr-btn-primary" id="ssr_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="ssr-btn" id="ssr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="ssr-btn" id="ssr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($ssr_has_export)
                <button type="button" class="ssr-btn" id="ssr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="ssr-exception-strip no-print" id="ssr_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="ssr-alert ssr-alert-error no-print" id="ssr_error" hidden role="alert">
        <div>
            <strong>Unable to load product sell report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="ssr-btn ssr-btn-primary" id="ssr_retry">Retry</button>
    </div>

    <div class="ssr-empty no-print" id="ssr_empty" hidden>
        <div class="ssr-empty-icon" aria-hidden="true">📊</div>
        <strong>No product sales found</strong>
        <p>Try changing your search or filters.</p>
        <button type="button" class="ssr-btn ssr-btn-primary" id="ssr_empty_clear">Clear Filters</button>
    </div>

    <div class="ssr-table-loading no-print" id="ssr_table_loading" hidden>
        <div class="ssr-skeleton-row"></div>
        <div class="ssr-skeleton-row"></div>
        <div class="ssr-skeleton-row"></div>
    </div>

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#psr_detailed_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list" aria-hidden="true"></i> @lang('lang_v1.detailed')</a>
            </li>
            <li>
                <a href="#psr_detailed_with_purchase_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list" aria-hidden="true"></i> @lang('lang_v1.detailed_with_purchase')</a>
            </li>
            <li>
                <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-bars" aria-hidden="true"></i> @lang('lang_v1.grouped')</a>
            </li>
            <li>
                <a href="#psr_by_cat_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-bars" aria-hidden="true"></i> @lang('lang_v1.by_category')</a>
            </li>
            <li>
                <a href="#psr_by_brand_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-bars" aria-hidden="true"></i> @lang('lang_v1.by_brand')</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="psr_detailed_tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped"
                    id="product_sell_report_table">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th id="psr_product_custom_field1">{{$product_custom_field1}}</th>
                                <th id="psr_product_custom_field2">{{$product_custom_field2}}</th>
                                <th>@lang('sale.customer_name')</th>
                                <th>@lang('lang_v1.contact_id')</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('sale.unit_price')</th>
                                <th>@lang('sale.discount')</th>
                                <th>@lang('sale.tax')</th>
                                <th>@lang('sale.price_inc_tax')</th>
                                <th>@lang('sale.total')</th>
                                <th>@lang('lang_v1.payment_method')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total_sold"></td>
                                <td></td>
                                <td></td>
                                <td id="footer_tax"></td>
                                <td></td>
                                <td><span class="display_currency" id="footer_subtotal" data-currency_symbol ="true"></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="tab-pane" id="psr_detailed_with_purchase_tab">
                <div class="table-responsive">
                    @if(session('business.enable_lot_number'))
                        <input type="hidden" id="lot_enabled">
                    @endif
                    <table class="table table-bordered table-striped"
                    id="product_sell_report_with_purchase_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('sale.customer_name')</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('lang_v1.purchase_ref_no')</th>
                                <th>@lang('lang_v1.lot_number')</th>
                                <th>@lang('lang_v1.supplier_name')</th>
                                <th>@lang('sale.qty')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane" id="psr_grouped_tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped"
                    id="product_sell_grouped_report_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('report.total_unit_sold')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total_grouped_sold"></td>
                                <td><span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol ="true"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @include('report.partials.product_sell_report_by_category')

            @include('report.partials.product_sell_report_by_brand')
        </div>
    </div>

    <p class="ssr-page-note no-print">Summary cards copy the active tab’s filtered row count and table footer totals for the current page. Quantity sold is already net of returns.</p>

    <div class="ssr-export-bar no-print">
        @if($ssr_has_export)
        <button type="button" class="ssr-btn" id="ssr_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="ssr-btn" id="ssr_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="ssr-btn" id="ssr_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="ssr-btn ssr-btn-primary" id="ssr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="ssr-drawer" id="ssr_drawer" role="dialog" aria-modal="true" aria-labelledby="ssr_drawer_title" aria-hidden="true">
    <div class="ssr-drawer-head">
        <div>
            <h3 id="ssr_drawer_title">Product</h3>
            <p class="ssr-drawer-sku" id="ssr_drawer_sku">—</p>
        </div>
        <button type="button" class="ssr-btn ssr-btn-icon" id="ssr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <details class="ssr-drawer-section" id="ssr_sec_overview" open>
        <summary>Overview</summary>
        <dl class="ssr-drawer-dl">
            <div id="ssr_row_cf1"><dt id="ssr_dt_cf1">Field 1</dt><dd id="ssr_drawer_cf1">—</dd></div>
            <div id="ssr_row_cf2"><dt id="ssr_dt_cf2">Field 2</dt><dd id="ssr_drawer_cf2">—</dd></div>
            <div id="ssr_row_date"><dt>@lang('messages.date')</dt><dd id="ssr_drawer_date">—</dd></div>
            <div id="ssr_row_invoice"><dt>@lang('sale.invoice_no')</dt><dd id="ssr_drawer_invoice">—</dd></div>
            <div id="ssr_row_pref"><dt>@lang('lang_v1.purchase_ref_no')</dt><dd id="ssr_drawer_pref">—</dd></div>
            <div id="ssr_row_lot"><dt>@lang('lang_v1.lot_number')</dt><dd id="ssr_drawer_lot">—</dd></div>
            <div id="ssr_row_category"><dt>@lang('category.category')</dt><dd id="ssr_drawer_category">—</dd></div>
            <div id="ssr_row_brand"><dt>@lang('product.brand')</dt><dd id="ssr_drawer_brand">—</dd></div>
        </dl>
    </details>
    <details class="ssr-drawer-section" id="ssr_sec_party" open>
        <summary>Customer / Supplier</summary>
        <dl class="ssr-drawer-dl">
            <div id="ssr_row_customer"><dt>@lang('sale.customer_name')</dt><dd id="ssr_drawer_customer">—</dd></div>
            <div id="ssr_row_contact"><dt>@lang('lang_v1.contact_id')</dt><dd id="ssr_drawer_contact">—</dd></div>
            <div id="ssr_row_supplier"><dt>@lang('lang_v1.supplier_name')</dt><dd id="ssr_drawer_supplier">—</dd></div>
        </dl>
    </details>
    <details class="ssr-drawer-section" id="ssr_sec_qty" open>
        <summary>Quantity</summary>
        <dl class="ssr-drawer-dl">
            <div id="ssr_row_qty"><dt>@lang('sale.qty')</dt><dd id="ssr_drawer_qty">—</dd></div>
            <div id="ssr_row_stock"><dt>@lang('report.current_stock')</dt><dd id="ssr_drawer_stock">—</dd></div>
        </dl>
    </details>
    <details class="ssr-drawer-section" id="ssr_sec_value" open>
        <summary>Value</summary>
        <dl class="ssr-drawer-dl">
            <div id="ssr_row_price"><dt>@lang('sale.unit_price')</dt><dd id="ssr_drawer_price">—</dd></div>
            <div id="ssr_row_discount"><dt>@lang('sale.discount')</dt><dd id="ssr_drawer_discount">—</dd></div>
            <div id="ssr_row_tax"><dt>@lang('sale.tax')</dt><dd id="ssr_drawer_tax">—</dd></div>
            <div id="ssr_row_inc"><dt>@lang('sale.price_inc_tax')</dt><dd id="ssr_drawer_inc">—</dd></div>
            <div id="ssr_row_subtotal"><dt>@lang('sale.total')</dt><dd id="ssr_drawer_subtotal">—</dd></div>
            <div id="ssr_row_pay"><dt>@lang('lang_v1.payment_method')</dt><dd id="ssr_drawer_pay">—</dd></div>
        </dl>
    </details>
</aside>
<div class="ssr-drawer-backdrop" id="ssr_drawer_backdrop" hidden></div>
<div class="ssr-toast-host" id="ssr_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product-sell-report-premium-ui.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(
        '#product_sell_report_form #location_id, #product_sell_report_form #customer_id, #psr_filter_brand_id, #psr_filter_category_id, #psr_customer_group_id'
    ).change(function() {
        $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
    });
        $(document).ready( function() {
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr('href');
                if ( target == '#psr_by_cat_tab') {
                    if(typeof product_sell_report_by_category_datatable == 'undefined') {
                        product_sell_report_by_category_datatable = $('table#product_sell_report_by_category').DataTable({
                                processing: true,
                                serverSide: true,
                                fixedHeader:false,
                                ajax: {
                                    url: '/reports/product-sell-grouped-by',
                                    data: function(d) {
                                        var start = '';
                                        var end = '';
                                        var start_time = $('#product_sr_start_time').val();
                                        var end_time = $('#product_sr_end_time').val();
                                        if ($('#product_sr_date_filter').val()) {
                                            start = $('input#product_sr_date_filter')
                                                .data('daterangepicker')
                                                .startDate.format('YYYY-MM-DD');
                                            end = $('input#product_sr_date_filter')
                                                .data('daterangepicker')
                                                .endDate.format('YYYY-MM-DD');

                                            start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                                            end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                                        }
                                        d.start_date = start;
                                        d.end_date = end;
                                        d.group_by = 'category';
                                        d.category_id = $('select#psr_filter_category_id').val();
                                        d.brand_id = $('select#psr_filter_brand_id').val();
                                        d.customer_id = $('select#customer_id').val();
                                        d.location_id = $('select#location_id').val();
                                        d.customer_group_id = $('#psr_customer_group_id').val();
                                    },
                                },
                                columns: [
                                    { data: 'category_name', name: 'cat.name' },
                                    { data: 'current_stock', name: 'current_stock', searchable: false, orderable: false },
                                    { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
                                    { data: 'subtotal', name: 'subtotal', searchable: false },
                                ],
                                fnDrawCallback: function(oSettings) {
                                    $('#footer_psr_by_cat_total_sell').text(
                                        sum_table_col($('#product_sell_report_by_category'), 'row_subtotal')
                                    );
                                    $('#footer_psr_by_cat_total_sold').html(
                                        __sum_stock($('#product_sell_report_by_category'), 'sell_qty')
                                    );

                                    $('#footer_psr_by_cat_total_stock').html(
                                        __sum_stock($('#product_sell_report_by_category'), 'current_stock')
                                    );
                                    __currency_convert_recursively($('#product_sell_report_by_category'));
                                },
                            });
                        } else {
                            product_sell_report_by_category_datatable.ajax.reload();
                        }
                    } else if ( target == '#psr_by_brand_tab') {
                    if(typeof product_sell_report_by_brand_datatable == 'undefined') {
                        product_sell_report_by_brand_datatable = $('table#product_sell_report_by_brand').DataTable({
                                processing: true,
                                serverSide: true,
                                fixedHeader:false,
                                ajax: {
                                    url: '/reports/product-sell-grouped-by',
                                    data: function(d) {
                                        var start = '';
                                        var end = '';
                                        var start_time = $('#product_sr_start_time').val();
                                        var end_time = $('#product_sr_end_time').val();
                                        if ($('#product_sr_date_filter').val()) {
                                            start = $('input#product_sr_date_filter')
                                                .data('daterangepicker')
                                                .startDate.format('YYYY-MM-DD');
                                            end = $('input#product_sr_date_filter')
                                                .data('daterangepicker')
                                                .endDate.format('YYYY-MM-DD');

                                            start = moment(start + " " + start_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                                            end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format).format('YYYY-MM-DD HH:mm');
                                        }
                                        d.start_date = start;
                                        d.end_date = end;
                                        d.group_by = 'brand';
                                        d.category_id = $('select#psr_filter_category_id').val();
                                        d.brand_id = $('select#psr_filter_brand_id').val();
                                        d.customer_id = $('select#customer_id').val();
                                        d.location_id = $('select#location_id').val();
                                        d.customer_group_id = $('#psr_customer_group_id').val();
                                    },
                                },
                                columns: [
                                    { data: 'brand_name', name: 'b.name' },
                                    { data: 'current_stock', name: 'current_stock', searchable: false, orderable: false },
                                    { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
                                    { data: 'subtotal', name: 'subtotal', searchable: false },
                                ],
                                fnDrawCallback: function(oSettings) {
                                    $('#footer_psr_by_brand_total_sell').text(
                                        sum_table_col($('#product_sell_report_by_brand'), 'row_subtotal')
                                    );
                                    $('#footer_psr_by_brand_total_sold').html(
                                        __sum_stock($('#product_sell_report_by_brand'), 'sell_qty')
                                    );

                                    $('#footer_psr_by_cat_total_stock').html(
                                        __sum_stock($('#product_sell_report_by_brand'), 'current_stock')
                                    );
                                    __currency_convert_recursively($('#product_sell_report_by_brand'));
                                },
                            });
                        } else {
                            product_sell_report_by_brand_datatable.ajax.reload();
                        }
                    }
                });
            });
    </script>
@endsection
