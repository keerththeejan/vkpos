@extends('layouts.app')
@section('title', __('stock_adjustment.stock_adjustments'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/stock-adjustments-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content sa-shell">
    {{-- Sticky Header --}}
    <div class="sa-header" role="banner">
        <div class="sa-header-left">
            <h1>@lang('stock_adjustment.stock_adjustments')</h1>
            <p class="sa-subtitle">Enterprise inventory adjustment &amp; stock control</p>
            <div class="sa-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Inventory</span>
                <span>/</span>
                <span>@lang('stock_adjustment.stock_adjustments')</span>
            </div>
        </div>
        <div class="sa-header-actions">
            <div class="sa-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="sa_quick_search" class="form-control" placeholder="Search adjustments…" aria-label="Search adjustments" autocomplete="off">
            </div>
            <button type="button" class="sa-btn sa-btn-ghost" id="sa_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="sa-btn sa-btn-ghost" id="sa_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="sa-btn sa-btn-ghost" id="sa_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @if(auth()->user()->can('purchase.create'))
                <a class="sa-btn sa-btn-primary" href="{{action([\App\Http\Controllers\StockAdjustmentController::class, 'create'])}}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endif
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="sa-kpi-grid" aria-label="Stock adjustment KPIs">
        <div class="sa-kpi tone-blue">
            <div class="sa-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
            <span class="sa-kpi-label">Total Adjustments</span>
            <span class="sa-kpi-value" id="sa_kpi_total">—</span>
            <span class="sa-kpi-hint">All adjustment records</span>
        </div>
        <div class="sa-kpi tone-teal">
            <div class="sa-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="sa-kpi-label">Filtered</span>
            <span class="sa-kpi-value" id="sa_kpi_filtered">—</span>
            <span class="sa-kpi-hint">Matching search</span>
        </div>
        <div class="sa-kpi tone-green">
            <div class="sa-kpi-icon"><i class="fas fa-plus-circle"></i></div>
            <span class="sa-kpi-label">Normal (page)</span>
            <span class="sa-kpi-value" id="sa_kpi_normal">—</span>
            <span class="sa-kpi-hint">From type column</span>
        </div>
        <div class="sa-kpi tone-red">
            <div class="sa-kpi-icon"><i class="fas fa-minus-circle"></i></div>
            <span class="sa-kpi-label">Abnormal (page)</span>
            <span class="sa-kpi-value" id="sa_kpi_abnormal">—</span>
            <span class="sa-kpi-hint">From type column</span>
        </div>
        <div class="sa-kpi tone-orange">
            <div class="sa-kpi-icon"><i class="fas fa-coins"></i></div>
            <span class="sa-kpi-label">Value (page)</span>
            <span class="sa-kpi-value" id="sa_kpi_value">—</span>
            <span class="sa-kpi-hint">Sum of totals on page</span>
        </div>
        <div class="sa-kpi tone-violet">
            <div class="sa-kpi-icon"><i class="fas fa-warehouse"></i></div>
            <span class="sa-kpi-label">Warehouses</span>
            <span class="sa-kpi-value">—</span>
            <span class="sa-kpi-hint">Location column</span>
        </div>
        <div class="sa-kpi tone-blue">
            <div class="sa-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="sa-kpi-label">IMEI Adjustments</span>
            <span class="sa-kpi-value">—</span>
            <span class="sa-kpi-hint">On create when enabled</span>
        </div>
        <div class="sa-kpi tone-slate">
            <div class="sa-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <span class="sa-kpi-label">Pending Reviews</span>
            <span class="sa-kpi-value">—</span>
            <span class="sa-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="sa-future-strip" aria-label="Coming soon">
        <span class="sa-chip"><i class="fas fa-sync"></i> Cycle Counting</span>
        <span class="sa-chip"><i class="fas fa-barcode"></i> Barcode Count</span>
        <span class="sa-chip"><i class="fas fa-check-double"></i> Approval Workflow</span>
        <span class="sa-chip"><i class="fas fa-robot"></i> AI Difference Detect</span>
        <span class="sa-chip"><i class="fas fa-history"></i> Audit Trail</span>
        <span class="sa-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="sa-main-grid">
        <div class="sa-main-col">
            <div class="sa-card">
                <div class="sa-card-head">
                    <div>
                        <h3>@lang('stock_adjustment.all_stock_adjustments')</h3>
                        <p>Server-side DataTable · view · delete · print</p>
                    </div>
                    @if(auth()->user()->can('purchase.create'))
                        <a class="sa-btn sa-btn-primary" href="{{action([\App\Http\Controllers\StockAdjustmentController::class, 'create'])}}">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endif
                </div>
                <div class="sa-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped ajax_view" id="stock_adjustment_table">
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
        </div>

        <aside class="sa-side" aria-label="Adjustment details">
            <div class="sa-preview-card">
                <h3>Adjustment Details</h3>
                <p class="sa-preview-empty" id="sa_preview_empty">Select an adjustment row to preview location, type, value, reason, and quick actions.</p>
                <div id="sa_preview_content" style="display:none;">
                    <div class="sa-preview-rows">
                        <div><span>Reference</span><strong id="sa_preview_ref">—</strong></div>
                        <div><span>Type</span><strong><span class="sa-badge" id="sa_preview_type_badge"><span id="sa_preview_type">—</span></span></strong></div>
                        <div><span>Date</span><strong id="sa_preview_date">—</strong></div>
                        <div><span>Location</span><strong id="sa_preview_location">—</strong></div>
                        <div><span>Total</span><strong id="sa_preview_total">—</strong></div>
                        <div><span>Recovered</span><strong id="sa_preview_recovered">—</strong></div>
                        <div><span>Reason / Notes</span><strong id="sa_preview_notes">—</strong></div>
                        <div><span>Added By</span><strong id="sa_preview_by">—</strong></div>
                    </div>
                    <div class="sa-preview-actions" id="sa_preview_actions"></div>
                    <div class="sa-note">
                        <strong>IMEI / Serial:</strong> Handled on adjustment create when product rows support it. Existing validation is unchanged.
                    </div>
                </div>
            </div>

            <div class="sa-summary-card">
                <h3>Page Summary</h3>
                <div class="sa-summary-rows">
                    <div><span>Filtered</span><strong id="sa_sum_filtered">—</strong></div>
                    <div><span>Normal (page)</span><strong id="sa_sum_normal">—</strong></div>
                    <div><span>Abnormal (page)</span><strong id="sa_sum_abnormal">—</strong></div>
                    <div><span>Value (page)</span><strong id="sa_sum_value">—</strong></div>
                </div>
                <div class="sa-reason-grid" aria-label="Common reasons (UI)">
                    <div class="sa-reason-chip">Physical Count</div>
                    <div class="sa-reason-chip">Damaged</div>
                    <div class="sa-reason-chip">Lost / Theft</div>
                    <div class="sa-reason-chip">Expired</div>
                    <div class="sa-reason-chip">Stock Correction</div>
                    <div class="sa-reason-chip">Other</div>
                </div>
                <p class="sa-note" style="margin-top:10px;">Reason chips are decorative. Actual reason text comes from existing records.</p>
            </div>
        </aside>
    </div>
</section>

<!-- /.content -->
@stop
@section('javascript')
	<script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/stock-adjustments-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection

@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot
