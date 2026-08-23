@extends('layouts.app')
@section('title', __('lang_v1.stock_transfers'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/stock-transfers-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print st-shell">
    {{-- Sticky Header --}}
    <div class="st-header" role="banner">
        <div class="st-header-left">
            <h1>@lang('lang_v1.stock_transfers')</h1>
            <p class="st-subtitle">Warehouse-to-warehouse inventory movement</p>
            <div class="st-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Inventory</span>
                <span>/</span>
                <span>@lang('lang_v1.stock_transfers')</span>
            </div>
        </div>
        <div class="st-header-actions">
            <div class="st-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="st_quick_search" class="form-control" placeholder="Search transfers…" aria-label="Search transfers" autocomplete="off">
            </div>
            <button type="button" class="st-btn st-btn-ghost" id="st_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="st-btn st-btn-ghost" id="st_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="st-btn st-btn-ghost" id="st_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @if(auth()->user()->can('purchase.create'))
                <a class="st-btn st-btn-primary" href="{{action([\App\Http\Controllers\StockTransferController::class, 'create'])}}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endif
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="st-kpi-grid" aria-label="Stock transfer KPIs">
        <div class="st-kpi tone-blue">
            <div class="st-kpi-icon"><i class="fas fa-exchange-alt"></i></div>
            <span class="st-kpi-label">Total Transfers</span>
            <span class="st-kpi-value" id="st_kpi_total">—</span>
            <span class="st-kpi-hint">All transfer records</span>
        </div>
        <div class="st-kpi tone-teal">
            <div class="st-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="st-kpi-label">Filtered</span>
            <span class="st-kpi-value" id="st_kpi_filtered">—</span>
            <span class="st-kpi-hint">Matching search</span>
        </div>
        <div class="st-kpi tone-orange">
            <div class="st-kpi-icon"><i class="fas fa-clock"></i></div>
            <span class="st-kpi-label">Pending (page)</span>
            <span class="st-kpi-value" id="st_kpi_pending">—</span>
            <span class="st-kpi-hint">From status labels</span>
        </div>
        <div class="st-kpi tone-violet">
            <div class="st-kpi-icon"><i class="fas fa-truck"></i></div>
            <span class="st-kpi-label">In Transit (page)</span>
            <span class="st-kpi-value" id="st_kpi_transit">—</span>
            <span class="st-kpi-hint">From status labels</span>
        </div>
        <div class="st-kpi tone-green">
            <div class="st-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="st-kpi-label">Completed (page)</span>
            <span class="st-kpi-value" id="st_kpi_completed">—</span>
            <span class="st-kpi-hint">From status labels</span>
        </div>
        <div class="st-kpi tone-slate">
            <div class="st-kpi-icon"><i class="fas fa-warehouse"></i></div>
            <span class="st-kpi-label">Warehouses</span>
            <span class="st-kpi-value">—</span>
            <span class="st-kpi-hint">From / To columns</span>
        </div>
        <div class="st-kpi tone-blue">
            <div class="st-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="st-kpi-label">IMEI Transfers</span>
            <span class="st-kpi-value">—</span>
            <span class="st-kpi-hint">On transfer create/edit</span>
        </div>
        <div class="st-kpi tone-red">
            <div class="st-kpi-icon"><i class="fas fa-times-circle"></i></div>
            <span class="st-kpi-label">Cancelled</span>
            <span class="st-kpi-value">—</span>
            <span class="st-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="st-future-strip" aria-label="Coming soon">
        <span class="st-chip"><i class="fas fa-check-double"></i> Multi-Level Approval</span>
        <span class="st-chip"><i class="fas fa-qrcode"></i> QR Warehouse Scan</span>
        <span class="st-chip"><i class="fas fa-map-marker-alt"></i> GPS Tracking</span>
        <span class="st-chip"><i class="fas fa-robot"></i> AI Movement Tips</span>
        <span class="st-chip"><i class="fas fa-history"></i> Audit Trail</span>
        <span class="st-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="st-main-grid">
        <div class="st-main-col">
            <div class="st-card">
                <div class="st-card-head">
                    <div>
                        <h3>@lang('lang_v1.all_stock_transfers')</h3>
                        <p>Server-side DataTable · view details · update status · print</p>
                    </div>
                    @if(auth()->user()->can('purchase.create'))
                        <a class="st-btn st-btn-primary" href="{{action([\App\Http\Controllers\StockTransferController::class, 'create'])}}">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endif
                </div>
                <div class="st-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped ajax_view" id="stock_transfer_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('lang_v1.location_from')</th>
                                    <th>@lang('lang_v1.location_to')</th>
                                    <th>@lang('sale.status')</th>
                                    <th>@lang('lang_v1.shipping_charges')</th>
                                    <th>@lang('stock_adjustment.total_amount')</th>
                                    <th>@lang('purchase.additional_notes')</th>
                                    <th class="tw-w-full">@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="st-side" aria-label="Transfer details">
            <div class="st-preview-card">
                <h3>Transfer Details</h3>
                <p class="st-preview-empty" id="st_preview_empty">Select a transfer row to preview warehouses, status, totals, and quick actions. Use the eye icon for line details (existing expand).</p>
                <div id="st_preview_content" style="display:none;">
                    <div class="st-preview-rows">
                        <div><span>Reference</span><strong id="st_preview_ref">—</strong></div>
                        <div><span>Status</span><strong><span class="st-badge" id="st_preview_status">—</span></strong></div>
                        <div><span>Date</span><strong id="st_preview_date">—</strong></div>
                        <div><span>From</span><strong id="st_preview_from">—</strong></div>
                        <div><span>To</span><strong id="st_preview_to">—</strong></div>
                        <div><span>Shipping</span><strong id="st_preview_shipping">—</strong></div>
                        <div><span>Total</span><strong id="st_preview_total">—</strong></div>
                        <div><span>Notes</span><strong id="st_preview_notes">—</strong></div>
                    </div>
                    <div class="st-preview-actions" id="st_preview_actions"></div>
                    <div class="st-imei-note">
                        <strong>IMEI / Serial:</strong> Handled on transfer create/edit product rows. Existing validation and stock movement rules are unchanged.
                    </div>
                </div>
            </div>

            <div class="st-summary-card">
                <h3>Page Summary</h3>
                <div class="st-summary-rows">
                    <div><span>Filtered transfers</span><strong id="st_sum_filtered">—</strong></div>
                    <div><span>Pending (page)</span><strong id="st_sum_pending">—</strong></div>
                    <div><span>Completed (page)</span><strong id="st_sum_completed">—</strong></div>
                </div>
            </div>
        </aside>
    </div>
</section>

@include('stock_transfer.partials.update_status_modal')

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/stock-transfers-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection

@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot
