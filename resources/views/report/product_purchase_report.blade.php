@extends('layouts.app')
@section('title', __('lang_v1.product_purchase_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/product-purchase-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $ppr_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content ppr-shell" id="ppr_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.product_purchase_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="ppr-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('purchase.supplier') }}: <span class="ppr-print-supplier">—</span>
            &nbsp;·&nbsp;
            {{ __('product.brand') }}: <span class="ppr-print-brand">—</span>
        </p>
        <p>
            {{ __('report.date_range') }}: <span class="ppr-print-range">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.search_product') }}: <span class="ppr-print-product">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.product_purchase_report') }}</p>
    </div>

    <div class="ppr-header no-print" role="banner">
        <div class="ppr-header-left">
            <h1>
                <span class="ppr-title-icon" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
                {{ __('lang_v1.product_purchase_report') }}
            </h1>
            <p class="ppr-subtitle">Product purchasing, supplier activity and procurement performance</p>
            <nav class="ppr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.product_purchase_report') }}</span>
            </nav>
        </div>
        <div class="ppr-header-actions">
            <div class="ppr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="ppr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} product, SKU, supplier, reference…" aria-label="Search product purchase report" autocomplete="off">
                <button type="button" class="ppr-search-clear" id="ppr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="ppr-search-spinner" id="ppr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="ppr-btn ppr-btn-icon" id="ppr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="ppr-btn ppr-btn-icon" id="ppr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="ppr_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="ppr-btn ppr-btn-icon" id="ppr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="ppr-btn" id="ppr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($ppr_has_export)
            <div class="ppr-export-wrap">
                <button type="button" class="ppr-btn" id="ppr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="ppr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="ppr-export-menu" id="ppr_export_menu" role="menu" hidden>
                    <button type="button" class="ppr-export-item" id="ppr_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="ppr-export-item" id="ppr_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="ppr-export-item" id="ppr_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="ppr-export-item" id="ppr_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="ppr-btn ppr-btn-primary" id="ppr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="ppr-header-meta" aria-label="Active filters">
            <div class="ppr-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="ppr_meta_location">—</strong></div>
            <div class="ppr-meta"><span>{{ __('purchase.supplier') }}</span> <strong id="ppr_meta_supplier">—</strong></div>
            <div class="ppr-meta"><span>{{ __('product.brand') }}</span> <strong id="ppr_meta_brand">—</strong></div>
            <div class="ppr-meta"><span>{{ __('report.date_range') }}</span> <strong id="ppr_meta_range">—</strong></div>
            <div class="ppr-meta"><span>{{ __('sale.product') }}</span> <strong id="ppr_meta_product">—</strong></div>
        </div>
        <div class="ppr-settings-panel" id="ppr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="ppr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="ppr_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="ppr-kpi-grid no-print" aria-label="Product purchase summary">
        <div class="ppr-kpi tone-blue">
            <div class="ppr-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="ppr-kpi-label">Matching lines</span>
            <span class="ppr-kpi-value" id="ppr_kpi_lines">—</span>
            <span class="ppr-kpi-hint">Filtered purchase lines</span>
        </div>
        <div class="ppr-kpi tone-teal">
            <div class="ppr-kpi-icon"><i class="bi bi-stack"></i></div>
            <span class="ppr-kpi-label">{{ __('sale.qty') }}</span>
            <span class="ppr-kpi-value" id="ppr_kpi_qty">—</span>
            <span class="ppr-kpi-hint">This page</span>
        </div>
        <div class="ppr-kpi tone-orange">
            <div class="ppr-kpi-icon"><i class="bi bi-sliders"></i></div>
            <span class="ppr-kpi-label">{{ __('lang_v1.total_unit_adjusted') }}</span>
            <span class="ppr-kpi-value" id="ppr_kpi_adjusted">—</span>
            <span class="ppr-kpi-hint">This page</span>
        </div>
        <div class="ppr-kpi tone-green">
            <div class="ppr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="ppr-kpi-label">{{ __('sale.subtotal') }}</span>
            <span class="ppr-kpi-value" id="ppr_kpi_subtotal">—</span>
            <span class="ppr-kpi-hint">This page</span>
        </div>
    </div>

    <div class="ppr-card ppr-filters-card no-print">
        <div class="ppr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Product, supplier, location, brand and date</p>
            </div>
            <button type="button" class="ppr-btn ppr-btn-ghost" id="ppr_filters_toggle" aria-expanded="true" aria-controls="ppr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="ppr-card-body" id="ppr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'product_purchase_report_form' ]) !!}
            <div class="ppr-filter-grid">
                <div class="form-group">
                    {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="hidden" value="" id="variation_id">
                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('product_pr_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_pr_date_filter', 'readonly']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('ppr_brand_id', __('product.brand').':') !!}
                    {!! Form::select('ppr_brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            {!! Form::close() !!}

            <div class="ppr-period-chips" id="ppr_period_chips" role="group" aria-label="Purchase period">
                <button type="button" class="ppr-chip" data-period="all">All</button>
                <button type="button" class="ppr-chip" data-period="today">Today</button>
                <button type="button" class="ppr-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="ppr-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="ppr-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="ppr-chip" data-period="this_month">This Month</button>
                <button type="button" class="ppr-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="ppr-chip" data-period="this_year">This Year</button>
                <button type="button" class="ppr-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="ppr-filter-actions">
                <button type="button" class="ppr-btn ppr-btn-primary" id="ppr_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="ppr-btn" id="ppr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="ppr-btn" id="ppr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($ppr_has_export)
                <button type="button" class="ppr-btn" id="ppr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="ppr-exception-strip no-print" id="ppr_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="ppr-alert ppr-alert-error no-print" id="ppr_error" hidden role="alert">
        <div>
            <strong>Unable to load product purchase report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="ppr-btn ppr-btn-primary" id="ppr_retry">Retry</button>
    </div>

    <div class="ppr-card ppr-table-card">
        <div class="ppr-card-head no-print">
            <div>
                <h2>{{ __('lang_v1.product_purchase_report') }}</h2>
                <p>Purchase lines with quantity, adjustments and value</p>
            </div>
        </div>
        <div class="ppr-card-body ppr-table-body">
            <div class="ppr-table-loading no-print" id="ppr_table_loading" hidden>
                <div class="ppr-skeleton-row"></div>
                <div class="ppr-skeleton-row"></div>
                <div class="ppr-skeleton-row"></div>
            </div>
            <div class="ppr-empty no-print" id="ppr_empty" hidden>
                <div class="ppr-empty-icon" aria-hidden="true">📦</div>
                <strong>No purchase records found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="ppr-btn ppr-btn-primary" id="ppr_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="product_purchase_report_table">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('product.sku')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.qty')</th>
                            <th>@lang('lang_v1.total_unit_adjusted')</th>
                            <th>@lang('lang_v1.unit_perchase_price')</th>
                            <th>@lang('sale.subtotal')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_total_purchase"></td>
                            <td id="footer_total_adjusted"></td>
                            <td></td>
                            <td><span class="display_currency" id="footer_subtotal" data-currency_symbol ="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="ppr-page-note no-print">Matching lines is the filtered DataTables count. Quantity, adjusted quantity and subtotal copy the table footer for the current page.</p>

    <div class="ppr-export-bar no-print">
        @if($ppr_has_export)
        <button type="button" class="ppr-btn" id="ppr_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="ppr-btn" id="ppr_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="ppr-btn" id="ppr_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="ppr-btn ppr-btn-primary" id="ppr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="ppr-drawer" id="ppr_drawer" role="dialog" aria-modal="true" aria-labelledby="ppr_drawer_title" aria-hidden="true">
    <div class="ppr-drawer-head">
        <div>
            <h3 id="ppr_drawer_title">Product</h3>
            <p class="ppr-drawer-sku" id="ppr_drawer_sku">—</p>
        </div>
        <button type="button" class="ppr-btn ppr-btn-icon" id="ppr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <details class="ppr-drawer-section" open>
        <summary>Overview</summary>
        <dl class="ppr-drawer-dl">
            <div><dt>@lang('purchase.supplier')</dt><dd id="ppr_drawer_supplier">—</dd></div>
            <div><dt>@lang('purchase.ref_no')</dt><dd id="ppr_drawer_ref">—</dd></div>
            <div><dt>@lang('messages.date')</dt><dd id="ppr_drawer_date">—</dd></div>
        </dl>
    </details>
    <details class="ppr-drawer-section" open>
        <summary>Quantity</summary>
        <dl class="ppr-drawer-dl">
            <div><dt>@lang('sale.qty')</dt><dd id="ppr_drawer_qty">—</dd></div>
            <div><dt>@lang('lang_v1.total_unit_adjusted')</dt><dd id="ppr_drawer_adjusted">—</dd></div>
        </dl>
    </details>
    <details class="ppr-drawer-section" open>
        <summary>Value</summary>
        <dl class="ppr-drawer-dl">
            <div><dt>@lang('lang_v1.unit_perchase_price')</dt><dd id="ppr_drawer_price">—</dd></div>
            <div><dt>@lang('sale.subtotal')</dt><dd id="ppr_drawer_subtotal">—</dd></div>
        </dl>
    </details>
</aside>
<div class="ppr-drawer-backdrop" id="ppr_drawer_backdrop" hidden></div>
<div class="ppr-toast-host" id="ppr_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product-purchase-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
