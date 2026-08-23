@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/stock-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $sr_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content sr-shell" id="sr_shell" data-has-export="{{ $sr_has_export ? '1' : '0' }}">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.stock_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="sr-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('category.category') }}: <span class="sr-print-category">—</span>
            &nbsp;·&nbsp;
            {{ __('product.brand') }}: <span class="sr-print-brand">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('report.stock_report') }}</p>
    </div>

    {{-- 1. Premium header --}}
    <div class="sr-header no-print" role="banner">
        <div class="sr-header-left">
            <h1>
                <span class="sr-title-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                {{ __('report.stock_report') }}
            </h1>
            <p class="sr-subtitle">Inventory position, availability and valuation</p>
            <nav class="sr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.stock_report') }}</span>
            </nav>
        </div>
        <div class="sr-header-actions">
            <div class="sr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="sr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search_product_placeholder') }}" aria-label="Search stock report" autocomplete="off">
                <button type="button" class="sr-search-clear" id="sr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="sr-search-spinner" id="sr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="sr-btn sr-btn-icon" id="sr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="sr-btn sr-btn-icon" id="sr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="sr_settings_panel">
                <i class="bi bi-sliders"></i>
            </button>
            <button type="button" class="sr-btn sr-btn-icon" id="sr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="sr-btn" id="sr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($sr_has_export)
            <div class="sr-export-wrap">
                <button type="button" class="sr-btn" id="sr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="sr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="sr-export-menu" id="sr_export_menu" role="menu" hidden>
                    <button type="button" class="sr-export-item" id="sr_export_excel" role="menuitem">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button type="button" class="sr-export-item" id="sr_export_csv" role="menuitem">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button type="button" class="sr-export-item" id="sr_export_pdf" role="menuitem">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button type="button" class="sr-export-item" id="sr_colvis" role="menuitem">
                        <i class="bi bi-layout-three-columns"></i> Columns
                    </button>
                </div>
            </div>
            @endif
            <button type="button" class="sr-btn sr-btn-primary" id="sr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="sr-header-meta" aria-label="Active filters">
            <div class="sr-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="sr_meta_location">—</strong></div>
            <div class="sr-meta"><span>{{ __('category.category') }}</span> <strong id="sr_meta_category">—</strong></div>
            <div class="sr-meta"><span>{{ __('product.brand') }}</span> <strong id="sr_meta_brand">—</strong></div>
            <div class="sr-meta"><span>{{ __('product.unit') }}</span> <strong id="sr_meta_unit">—</strong></div>
        </div>
        <div class="sr-settings-panel" id="sr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="sr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="sr_set_hide_exceptions"> Hide attention strip</label>
            <label><input type="checkbox" id="sr_set_compact"> Compact table</label>
        </div>
    </div>

    {{-- 2. KPI summary — only metrics the backend already returns --}}
    <div class="sr-kpi-grid no-print" aria-label="Stock summary">
        <div class="sr-kpi tone-blue">
            <div class="sr-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="sr-kpi-label">Matching lines</span>
            <span class="sr-kpi-value" id="sr_kpi_lines">—</span>
            <span class="sr-kpi-hint">Filtered SKUs / variations</span>
        </div>
        @can('view_product_stock_value')
        <div class="sr-kpi tone-green">
            <div class="sr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="sr-kpi-label">{{ __('report.closing_stock') }}</span>
            <span class="sr-kpi-value" id="closing_stock_by_pp">—</span>
            <span class="sr-kpi-hint">{{ __('lang_v1.by_purchase_price') }}</span>
        </div>
        <div class="sr-kpi tone-teal">
            <div class="sr-kpi-icon"><i class="bi bi-tag"></i></div>
            <span class="sr-kpi-label">{{ __('report.closing_stock') }}</span>
            <span class="sr-kpi-value" id="closing_stock_by_sp">—</span>
            <span class="sr-kpi-hint">{{ __('lang_v1.by_sale_price') }}</span>
        </div>
        <div class="sr-kpi tone-violet">
            <div class="sr-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <span class="sr-kpi-label">{{ __('lang_v1.potential_profit') }}</span>
            <span class="sr-kpi-value" id="potential_profit">—</span>
            <span class="sr-kpi-hint">Sale value − purchase value</span>
        </div>
        <div class="sr-kpi tone-orange">
            <div class="sr-kpi-icon"><i class="bi bi-percent"></i></div>
            <span class="sr-kpi-label">{{ __('lang_v1.profit_margin') }}</span>
            <span class="sr-kpi-value" id="profit_margin">—</span>
            <span class="sr-kpi-hint">From stock valuation</span>
        </div>
        @endcan
        <div class="sr-kpi tone-slate">
            <div class="sr-kpi-icon"><i class="bi bi-stack"></i></div>
            <span class="sr-kpi-label">Qty (this page)</span>
            <span class="sr-kpi-value" id="sr_kpi_page_qty">—</span>
            <span class="sr-kpi-hint">Current table page total</span>
        </div>
    </div>

    {{-- 3. Smart filter bar — original IDs for report.js --}}
    <div class="sr-card sr-filters-card no-print">
        <div class="sr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Location, category, brand and unit drive the existing stock report</p>
            </div>
            <button type="button" class="sr-btn sr-btn-ghost sr-filters-toggle" id="sr_filters_toggle" aria-expanded="true" aria-controls="sr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="sr-card-body" id="sr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'stock_report_filter_form']) !!}
                <div class="sr-filter-grid">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'aria-label' => __('purchase.business_location')]); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('category_id', __('category.category') . ':') !!}
                        {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id', 'aria-label' => __('category.category')]); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id', 'aria-label' => __('product.sub_category')]); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'aria-label' => __('product.brand')]); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('unit', __('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'aria-label' => __('product.unit')]); !!}
                    </div>
                    @if($show_manufacturing_data)
                    <div class="form-group sr-check-group">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('only_mfg', 1, false, ['class' => 'input-icheck', 'id' => 'only_mfg_products']); !!}
                                {{ __('manufacturing::lang.only_mfg_products') }}
                            </label>
                        </div>
                    </div>
                    @endif
                </div>
            {!! Form::close() !!}

            <div class="sr-filter-actions">
                <button type="button" class="sr-btn sr-btn-primary" id="sr_apply_filters">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
                <button type="button" class="sr-btn" id="sr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="sr-btn" id="sr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($sr_has_export)
                <button type="button" class="sr-btn" id="sr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
                <button type="button" class="sr-btn sr-btn-muted" id="sr_save_filter" title="Not available in this version">
                    <i class="bi bi-bookmark"></i> Save Filter
                </button>
            </div>
        </div>
    </div>

    {{-- 4–5. Attention strip — populated from current page rows only --}}
    <div class="sr-exception-strip no-print" id="sr_exception_strip" hidden role="status" aria-live="polite"></div>

    {{-- Error state --}}
    <div class="sr-alert sr-alert-error no-print" id="sr_error" hidden role="alert">
        <div>
            <strong>Unable to load stock report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="sr-btn sr-btn-primary" id="sr_retry">Retry</button>
    </div>

    {{-- 6. Main stock report table --}}
    <div class="sr-card sr-table-card">
        <div class="sr-card-head no-print">
            <div>
                <h2>{{ __('report.stock_report') }}</h2>
                <p>Server-side table · existing columns, sorting, pagination and export</p>
            </div>
            <div class="sr-table-legend" aria-label="Stock status legend">
                <span class="sr-legend"><i class="sr-dot tone-amber"></i> Alert qty</span>
                <span class="sr-legend"><i class="sr-dot tone-red"></i> Negative</span>
                <span class="sr-legend"><i class="sr-dot tone-gray"></i> Zero</span>
            </div>
        </div>
        <div class="sr-card-body sr-table-body">
            <div class="sr-table-loading no-print" id="sr_table_loading" hidden>
                <div class="sr-skeleton-row"></div>
                <div class="sr-skeleton-row"></div>
                <div class="sr-skeleton-row"></div>
                <div class="sr-skeleton-row"></div>
            </div>
            <div class="sr-empty no-print" id="sr_empty" hidden>
                <div class="sr-empty-icon" aria-hidden="true">📦</div>
                <strong>No stock records found</strong>
                <p>Try changing your filters or search criteria.</p>
                <button type="button" class="sr-btn sr-btn-primary" id="sr_empty_clear">Clear Filters</button>
            </div>
            @include('report.partials.stock_report_table')
        </div>
    </div>

    {{-- Page totals (mirrors existing DataTables footer — current page only) --}}
    <div class="sr-page-totals no-print" aria-label="This page totals">
        <div class="sr-total-item">
            <span>{{ __('report.current_stock') }}</span>
            <strong id="sr_sum_stock">—</strong>
        </div>
        @can('view_product_stock_value')
        <div class="sr-total-item">
            <span>{{ __('lang_v1.by_purchase_price') }}</span>
            <strong id="sr_sum_pp">—</strong>
        </div>
        <div class="sr-total-item">
            <span>{{ __('lang_v1.by_sale_price') }}</span>
            <strong id="sr_sum_sp">—</strong>
        </div>
        <div class="sr-total-item">
            <span>{{ __('lang_v1.potential_profit') }}</span>
            <strong id="sr_sum_profit">—</strong>
        </div>
        @endcan
        <div class="sr-total-item">
            <span>{{ __('report.total_unit_sold') }}</span>
            <strong id="sr_sum_sold">—</strong>
        </div>
        <div class="sr-total-item">
            <span>{{ __('lang_v1.total_unit_transfered') }}</span>
            <strong id="sr_sum_transfered">—</strong>
        </div>
        <div class="sr-total-item">
            <span>{{ __('lang_v1.total_unit_adjusted') }}</span>
            <strong id="sr_sum_adjusted">—</strong>
        </div>
        <p class="sr-page-totals-note">Totals above match the table footer for the current page.</p>
    </div>

    {{-- 13. Attention list — current page only, collapsible --}}
    <details class="sr-card sr-attention-card no-print" id="sr_attention_card" hidden>
        <summary>
            <h2>Needs attention <small>(this page)</small></h2>
        </summary>
        <div class="sr-card-body">
            <ul class="sr-attention-list" id="sr_attention_list"></ul>
        </div>
    </details>

    {{-- 19. Export actions --}}
    <div class="sr-export-bar no-print">
        @if($sr_has_export)
        <button type="button" class="sr-btn" id="sr_export_excel_foot">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </button>
        <button type="button" class="sr-btn" id="sr_export_csv_foot">
            <i class="bi bi-filetype-csv"></i> CSV
        </button>
        <button type="button" class="sr-btn" id="sr_export_pdf_foot">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
        @endif
        <button type="button" class="sr-btn sr-btn-primary" id="sr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

{{-- 10. Product detail drawer — values from the clicked row only --}}
<div class="sr-drawer-backdrop" id="sr_drawer_backdrop" hidden></div>
<aside class="sr-drawer" id="sr_drawer" role="dialog" aria-modal="true" aria-labelledby="sr_drawer_title" aria-hidden="true">
    <div class="sr-drawer-head">
        <div>
            <h3 id="sr_drawer_title">Product</h3>
            <p class="sr-drawer-sku" id="sr_drawer_sku">—</p>
        </div>
        <button type="button" class="sr-btn sr-btn-icon" id="sr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sr-drawer-status" id="sr_drawer_status"></div>
    <dl class="sr-drawer-dl">
        <div><dt>{{ __('lang_v1.variation') }}</dt><dd id="sr_drawer_variation">—</dd></div>
        <div><dt>{{ __('product.category') }}</dt><dd id="sr_drawer_category">—</dd></div>
        <div><dt>{{ __('sale.location') }}</dt><dd id="sr_drawer_location">—</dd></div>
        <div><dt>{{ __('report.current_stock') }}</dt><dd id="sr_drawer_stock">—</dd></div>
        <div><dt>{{ __('purchase.unit_selling_price') }}</dt><dd id="sr_drawer_price">—</dd></div>
        @can('view_product_stock_value')
        <div><dt>{{ __('lang_v1.by_purchase_price') }}</dt><dd id="sr_drawer_pp">—</dd></div>
        <div><dt>{{ __('lang_v1.by_sale_price') }}</dt><dd id="sr_drawer_sp">—</dd></div>
        <div><dt>{{ __('lang_v1.potential_profit') }}</dt><dd id="sr_drawer_profit">—</dd></div>
        @endcan
        <div><dt>{{ __('report.total_unit_sold') }}</dt><dd id="sr_drawer_sold">—</dd></div>
        <div><dt>{{ __('lang_v1.total_unit_transfered') }}</dt><dd id="sr_drawer_transfered">—</dd></div>
        <div><dt>{{ __('lang_v1.total_unit_adjusted') }}</dt><dd id="sr_drawer_adjusted">—</dd></div>
    </dl>
    <div class="sr-drawer-actions">
        <a class="sr-btn sr-btn-primary" id="sr_drawer_history" href="#" hidden>
            <i class="bi bi-clock-history"></i> {{ __('lang_v1.product_stock_history') }}
        </a>
    </div>
</aside>

<div class="sr-toast-host" id="sr_toast_host" aria-live="polite" aria-atomic="true"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/stock-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
