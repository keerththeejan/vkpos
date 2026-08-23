@extends('layouts.app')
@section('title', __('report.stock_adjustment_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/stock-adjustment-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $sar_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content sar-shell" id="sar_shell"
    data-type-normal="{{ __('stock_adjustment.normal') }}"
    data-type-abnormal="{{ __('stock_adjustment.abnormal') }}">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.stock_adjustment_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="sar-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="sar-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('report.stock_adjustment_report') }}</p>
    </div>

    {{-- 1. Header --}}
    <div class="sar-header no-print" role="banner">
        <div class="sar-header-left">
            <h1>
                <span class="sar-title-icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                {{ __('report.stock_adjustment_report') }}
            </h1>
            <p class="sar-subtitle">Inventory corrections, stock movements and adjustment audit</p>
            <nav class="sar-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.stock_adjustment_report') }}</span>
            </nav>
        </div>
        <div class="sar-header-actions">
            <div class="sar-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="sar_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} ref, location, reason, user…" aria-label="Search stock adjustment report" autocomplete="off">
                <button type="button" class="sar-search-clear" id="sar_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="sar-search-spinner" id="sar_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="sar-btn sar-btn-icon" id="sar_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="sar-btn sar-btn-icon" id="sar_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="sar_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="sar-btn sar-btn-icon" id="sar_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="sar-btn" id="sar_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($sar_has_export)
            <div class="sar-export-wrap">
                <button type="button" class="sar-btn" id="sar_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="sar_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="sar-export-menu" id="sar_export_menu" role="menu" hidden>
                    <button type="button" class="sar-export-item" id="sar_export_excel" role="menuitem">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                    <button type="button" class="sar-export-item" id="sar_export_csv" role="menuitem">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button type="button" class="sar-export-item" id="sar_export_pdf" role="menuitem">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button type="button" class="sar-export-item" id="sar_colvis" role="menuitem">
                        <i class="bi bi-layout-three-columns"></i> Columns
                    </button>
                </div>
            </div>
            @endif
            <button type="button" class="sar-btn sar-btn-primary" id="sar_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="sar-header-meta" aria-label="Active filters">
            <div class="sar-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="sar_meta_location">—</strong></div>
            <div class="sar-meta"><span>{{ __('report.date_range') }}</span> <strong id="sar_meta_range">—</strong></div>
        </div>
        <div class="sar-settings-panel" id="sar_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="sar_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="sar_set_hide_exceptions"> Hide attention strip</label>
            <label><input type="checkbox" id="sar_set_compact"> Compact table</label>
        </div>
    </div>

    {{-- 2. KPI summary — classes preserved for updateStockAdjustmentReport() --}}
    <div class="sar-kpi-grid no-print" aria-label="Stock adjustment summary">
        <div class="sar-kpi tone-blue">
            <div class="sar-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="sar-kpi-label">Matching records</span>
            <span class="sar-kpi-value" id="sar_kpi_lines">—</span>
            <span class="sar-kpi-hint">Filtered adjustments</span>
        </div>
        <div class="sar-kpi tone-green">
            <div class="sar-kpi-icon"><i class="bi bi-check-circle"></i></div>
            <span class="sar-kpi-label">{{ __('report.total_normal') }}</span>
            <span class="sar-kpi-value total_normal"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="sar-kpi-hint">{{ __('stock_adjustment.normal') }} value</span>
        </div>
        <div class="sar-kpi tone-orange">
            <div class="sar-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <span class="sar-kpi-label">{{ __('report.total_abnormal') }}</span>
            <span class="sar-kpi-value total_abnormal"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="sar-kpi-hint">{{ __('stock_adjustment.abnormal') }} value</span>
        </div>
        <div class="sar-kpi tone-violet">
            <div class="sar-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="sar-kpi-label">{{ __('report.total_stock_adjustment') }}</span>
            <span class="sar-kpi-value total_amount"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="sar-kpi-hint">{{ __('stock_adjustment.total_amount') }}</span>
        </div>
        <div class="sar-kpi tone-teal">
            <div class="sar-kpi-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <span class="sar-kpi-label">{{ __('report.total_recovered') }}</span>
            <span class="sar-kpi-value total_recovered"><i class="fas fa-sync fa-spin fa-fw"></i></span>
            <span class="sar-kpi-hint">{{ __('stock_adjustment.total_amount_recovered') }}</span>
        </div>
    </div>

    {{-- 3. Filters — original IDs for report.js --}}
    <div class="sar-card sar-filters-card no-print">
        <div class="sar-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Location and date range drive the existing adjustment AJAX and table</p>
            </div>
            <button type="button" class="sar-btn sar-btn-ghost sar-filters-toggle" id="sar_filters_toggle" aria-expanded="true" aria-controls="sar_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="sar-card-body" id="sar_filters_body">
            <div class="sar-filter-grid">
                <div class="form-group">
                    <label for="stock_adjustment_location_filter">{{ __('purchase.business_location') }}</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                        <select class="form-control select2" id="stock_adjustment_location_filter" aria-label="{{ __('purchase.business_location') }}" style="width:100%">
                            @foreach($business_locations as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.filter_by_date') }}</label>
                    <div class="input-group" style="width:100%;">
                        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="stock_adjustment_date_filter" aria-label="{{ __('messages.filter_by_date') }}">
                            <span>
                                <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                            </span>
                            <i class="fa fa-caret-down"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 4. Type chips — only types that exist (normal / abnormal) --}}
            <div class="sar-type-chips" role="group" aria-label="Adjustment type">
                <button type="button" class="sar-chip is-active" data-type="" id="sar_type_all">All</button>
                <button type="button" class="sar-chip tone-orange" data-type="abnormal" id="sar_type_abnormal">
                    {{ __('stock_adjustment.abnormal') }}
                </button>
            </div>
            <p class="sar-page-note" style="margin-top:8px;margin-bottom:0;">Existing types: {{ __('stock_adjustment.normal') }} and {{ __('stock_adjustment.abnormal') }}.</p>

            <div class="sar-filter-actions">
                <button type="button" class="sar-btn sar-btn-primary" id="sar_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="sar-btn" id="sar_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="sar-btn" id="sar_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($sar_has_export)
                <button type="button" class="sar-btn" id="sar_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 5. Attention strip — current page only --}}
    <div class="sar-exception-strip no-print" id="sar_exception_strip" hidden role="status" aria-live="polite"></div>

    <div class="sar-alert sar-alert-error no-print" id="sar_error" hidden role="alert">
        <div>
            <strong>Unable to load stock adjustment report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="sar-btn sar-btn-primary" id="sar_retry">Retry</button>
    </div>

    {{-- 6. Main table --}}
    <div class="sar-card sar-table-card">
        <div class="sar-card-head no-print">
            <div>
                <h2>{{ __('stock_adjustment.stock_adjustments') }}</h2>
                <p>Existing columns, sorting, pagination and export</p>
            </div>
            <div class="sar-table-legend" aria-label="Adjustment type legend">
                <span class="sar-legend"><i class="sar-dot tone-green"></i> {{ __('stock_adjustment.normal') }}</span>
                <span class="sar-legend"><i class="sar-dot tone-orange"></i> {{ __('stock_adjustment.abnormal') }}</span>
            </div>
        </div>
        <div class="sar-card-body sar-table-body">
            <div class="sar-table-loading no-print" id="sar_table_loading" hidden>
                <div class="sar-skeleton-row"></div>
                <div class="sar-skeleton-row"></div>
                <div class="sar-skeleton-row"></div>
                <div class="sar-skeleton-row"></div>
            </div>
            <div class="sar-empty no-print" id="sar_empty" hidden>
                <div class="sar-empty-icon" aria-hidden="true">📦</div>
                <strong>No stock adjustments found</strong>
                <p>Try changing your filters or date range.</p>
                <button type="button" class="sar-btn sar-btn-primary" id="sar_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="stock_adjustment_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('stock_adjustment.adjustment_type')</th>
                            <th>@lang('stock_adjustment.total_amount')</th>
                            <th>@lang('stock_adjustment.total_amount_recovered')</th>
                            <th>@lang('stock_adjustment.reason_for_stock_adjustment')</th>
                            <th>@lang('lang_v1.added_by')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <p class="sar-page-note no-print">Value totals in the summary cards come from the existing report AJAX for the selected location and date range. Matching records come from the table.</p>

    {{-- 22. Export --}}
    <div class="sar-export-bar no-print">
        @if($sar_has_export)
        <button type="button" class="sar-btn" id="sar_export_excel_foot">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </button>
        <button type="button" class="sar-btn" id="sar_export_csv_foot">
            <i class="bi bi-filetype-csv"></i> CSV
        </button>
        <button type="button" class="sar-btn" id="sar_export_pdf_foot">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </button>
        @endif
        <button type="button" class="sar-btn sar-btn-primary" id="sar_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="sar-drawer" id="sar_drawer" role="dialog" aria-modal="true" aria-labelledby="sar_drawer_title" aria-hidden="true">
    <div class="sar-drawer-head">
        <div>
            <h3 id="sar_drawer_title">{{ __('lang_v1.stock_adjustment_details') }}</h3>
            <p class="sar-drawer-sku" id="sar_drawer_ref">—</p>
        </div>
        <button type="button" class="sar-btn sar-btn-icon" id="sar_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sar-drawer-status" id="sar_drawer_status"></div>
    <dl class="sar-drawer-dl">
        <div><dt>@lang('messages.date')</dt><dd id="sar_drawer_date">—</dd></div>
        <div><dt>@lang('business.location')</dt><dd id="sar_drawer_location">—</dd></div>
        <div><dt>@lang('stock_adjustment.adjustment_type')</dt><dd id="sar_drawer_type">—</dd></div>
        <div><dt>@lang('stock_adjustment.total_amount')</dt><dd id="sar_drawer_amount">—</dd></div>
        <div><dt>@lang('stock_adjustment.total_amount_recovered')</dt><dd id="sar_drawer_recovered">—</dd></div>
        <div><dt>@lang('stock_adjustment.reason_for_stock_adjustment')</dt><dd id="sar_drawer_reason">—</dd></div>
        <div><dt>@lang('lang_v1.added_by')</dt><dd id="sar_drawer_user">—</dd></div>
    </dl>
    <div class="sar-drawer-full" id="sar_drawer_full" hidden></div>
    <div class="sar-drawer-actions">
        <button type="button" class="sar-btn sar-btn-primary" id="sar_drawer_view" hidden>
            <i class="bi bi-eye"></i> {{ __('messages.view') }}
        </button>
    </div>
</aside>
<div class="sar-drawer-backdrop" id="sar_drawer_backdrop" hidden></div>

<div class="sar-toast-host" id="sar_toast_host" aria-live="polite" aria-atomic="true"></div>

@endsection

@section('javascript')
<script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/stock-adjustment-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
