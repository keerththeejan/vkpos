@extends('layouts.app')
@section('title', __('stock_adjustment.add'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/stock-adjustments-create-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print sac-shell">
    {{-- Sticky Header --}}
    <div class="sac-header" role="banner">
        <div class="sac-header-left">
            <h1>@lang('stock_adjustment.add')</h1>
            <p class="sac-subtitle">Inventory adjustment &amp; stock reconciliation workspace</p>
            <div class="sac-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('stock_adjustment.stock_adjustments')</span>
                <span>/</span>
                <span>@lang('messages.add')</span>
            </div>
        </div>
        <div class="sac-header-actions">
            <button type="button" class="sac-btn sac-btn-ghost" id="sac_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="sac-btn" id="sac_refresh_btn" title="Refresh">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a class="sac-btn" id="sac_cancel_link" href="{{ action([\App\Http\Controllers\StockAdjustmentController::class, 'index']) }}">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="button" class="sac-btn sac-btn-primary" id="sac_header_save">
                <i class="fas fa-save"></i> @lang('messages.save')
            </button>
        </div>
        <div class="sac-header-meta" aria-label="Adjustment context">
            <div class="sac-meta-item"><span>Adjustment #</span><strong>Auto</strong></div>
            <div class="sac-meta-item"><span>Reference</span><strong id="sac_meta_ref">Auto</strong></div>
            <div class="sac-meta-item"><span>Date</span><strong id="sac_meta_date">—</strong></div>
            <div class="sac-meta-item"><span>Location</span><strong id="sac_meta_location">—</strong></div>
            <div class="sac-meta-item"><span>Type</span><strong id="sac_meta_type">—</strong></div>
            <div class="sac-meta-item"><span>User</span><strong>{{ auth()->user()->first_name ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="sac-live-bar" aria-label="Live adjustment overview">
        <div class="sac-live-item">
            <span>Location</span>
            <strong id="sac_live_location">—</strong>
        </div>
        <div class="sac-live-item">
            <span>Type</span>
            <strong id="sac_live_type">—</strong>
        </div>
        <div class="sac-live-item">
            <span>Products</span>
            <strong id="sac_live_products">0</strong>
        </div>
        <div class="sac-live-item">
            <span>Adjustment Value</span>
            <strong id="sac_live_value">0.00</strong>
        </div>
    </div>

    <div class="sac-future-strip" aria-label="Coming soon">
        <span class="sac-chip"><i class="fas fa-sync"></i> Cycle Counting</span>
        <span class="sac-chip"><i class="fas fa-qrcode"></i> QR Inventory</span>
        <span class="sac-chip"><i class="fas fa-check-double"></i> Approval Workflow</span>
        <span class="sac-chip"><i class="fas fa-robot"></i> AI Qty Suggest</span>
        <span class="sac-chip"><i class="fas fa-signature"></i> Digital Signature</span>
        <span class="sac-chip muted">UI placeholders — existing AJAX &amp; validation unchanged</span>
    </div>

    <div class="sac-layout">
        <div class="sac-main">

        {!! Form::open([
            'url' => action([\App\Http\Controllers\StockAdjustmentController::class, 'store']),
            'method' => 'post',
            'id' => 'stock_adjustment_form',
        ]) !!}

        {{-- Adjustment Information --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="sac-section-title"><i class="fas fa-info-circle"></i> Adjustment Information</div>

            <div class="sac-section-title" style="margin-top:4px;font-size:13px;">
                <i class="fas fa-sliders-h"></i> Adjustment Type
            </div>
            <div class="sac-type-grid" role="group" aria-label="Adjustment type">
                <div class="sac-type-card" data-adj-type="normal" tabindex="0" role="button" aria-label="Normal adjustment">
                    <span class="sac-type-icon">➕</span>
                    <strong>@lang('stock_adjustment.normal')</strong>
                    <small>Existing option</small>
                </div>
                <div class="sac-type-card" data-adj-type="abnormal" tabindex="0" role="button" aria-label="Abnormal adjustment">
                    <span class="sac-type-icon">➖</span>
                    <strong>@lang('stock_adjustment.abnormal')</strong>
                    <small>Existing option</small>
                </div>
                <div class="sac-type-card decorative" aria-hidden="true">
                    <span class="sac-type-icon">🔍</span>
                    <strong>Physical Count</strong>
                    <small>UI only</small>
                </div>
                <div class="sac-type-card decorative" aria-hidden="true">
                    <span class="sac-type-icon">⚠</span>
                    <strong>Damaged / Expired</strong>
                    <small>UI only</small>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                        ]) !!}
                        <p class="sac-search-hint">F2 opens warehouse · unlocks product search</p>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                        {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('adjustment_type', __('stock_adjustment.adjustment_type') . ':*') !!} @show_tooltip(__('tooltip.adjustment_type'))
                        {!! Form::select(
                            'adjustment_type',
                            ['normal' => __('stock_adjustment.normal'), 'abnormal' => __('stock_adjustment.abnormal')],
                            null,
                            ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'],
                        ) !!}
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Product search + lines --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="sac-section-title"><i class="fas fa-search"></i> Smart Product Search</div>
            <div class="row">
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group sac-search-panel">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            {!! Form::text('search_product', null, [
                                'class' => 'form-control',
                                'id' => 'search_product_for_srock_adjustment',
                                'placeholder' => __('stock_adjustment.search_product'),
                                'disabled',
                                'autocomplete' => 'off',
                                'aria-label' => 'Search product by name, SKU or barcode',
                            ]) !!}
                        </div>
                        <p class="sac-search-hint">
                            Existing AJAX <code>/products/list</code> · USB/barcode scanners type here · F3 focus · F8 scan focus
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <input type="hidden" id="product_row_index" value="0">
                    <input type="hidden" id="total_amount" name="final_total" value="0">
                    <div class="sac-section-title"><i class="fas fa-th-list"></i> Product Adjustment Grid</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="stock_adjustment_product_table">
                            <thead>
                                <tr>
                                    <th class="col-sm-4 text-center">
                                        @lang('sale.product')
                                    </th>
                                    <th class="col-sm-2 text-center">
                                        @lang('sale.qty')
                                    </th>
                                    <th class="col-sm-2 text-center show_price_with_permission">
                                        @lang('sale.unit_price')
                                    </th>
                                    <th class="col-sm-2 text-center show_price_with_permission">
                                        @lang('sale.subtotal')
                                    </th>
                                    <th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot>
                                <tr class="text-center show_price_with_permission">
                                    <td colspan="3"></td>
                                    <td>
                                        <div class="pull-right"><b>@lang('stock_adjustment.total_amount'):</b> <span
                                                id="total_adjustment">0.00</span></div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="sac-note">
                        <strong>IMEI / Serial:</strong> When product rows include serial/IMEI controls from the existing
                        adjustment product row template, those fields and validations continue to work unchanged.
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Recovered + reason + save --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="sac-section-title"><i class="fas fa-sticky-note"></i> Notes &amp; Recovery</div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!} @show_tooltip(__('tooltip.total_amount_recovered'))
                        {!! Form::text('total_amount_recovered', 0, [
                            'class' => 'form-control input_number',
                            'placeholder' => __('stock_adjustment.total_amount_recovered'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
                        {!! Form::textarea('additional_notes', null, [
                            'class' => 'form-control',
                            'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'),
                            'rows' => 3,
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Attachments (UI)</label>
                        <div class="sac-note" style="margin-top:0;">
                            Photos / audit docs / count sheets — placeholders only, not connected to backend.
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 text-center sac-actions-bar">
                    <a class="sac-btn" href="{{ action([\App\Http\Controllers\StockAdjustmentController::class, 'index']) }}">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" id="save_stock_adjustment" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white sac-btn sac-btn-primary">
                        @lang('messages.save')
                    </button>
                </div>
            </div>
        @endcomponent

        <div class="sac-sticky-save" aria-label="Quick save">
            <span class="text-muted" style="font-size:12px;margin-right:auto;">Ctrl+S / F6 to save · ESC cancel</span>
            <a class="sac-btn" href="{{ action([\App\Http\Controllers\StockAdjustmentController::class, 'index']) }}">Cancel</a>
            <button type="button" class="sac-btn sac-btn-primary" id="sac_sticky_save">
                <i class="fas fa-save"></i> @lang('messages.save')
            </button>
        </div>

        {!! Form::close() !!}
        </div>

        <aside class="sac-side" aria-label="Adjustment summary">
            <div class="sac-summary-card">
                <h3>Financial Summary</h3>
                <div class="sac-summary-rows">
                    <div><span>Products</span><strong id="sac_sum_products">0</strong></div>
                    <div><span>Total Qty</span><strong id="sac_sum_qty">0</strong></div>
                    <div><span>Recovered</span><strong id="sac_sum_recovered">0</strong></div>
                    <div><span>Adjustment Value</span><strong id="sac_sum_value">0.00</strong></div>
                </div>
                <button type="button" class="sac-btn sac-btn-primary" id="sac_side_save" style="width:100%;margin-top:12px;">
                    <i class="fas fa-save"></i> @lang('messages.save')
                </button>
                <div class="sac-note">
                    Totals mirror <code>#total_adjustment</code> from existing JS.
                </div>
            </div>

            <div class="sac-audit-card">
                <h3>Audit Information</h3>
                <div class="sac-audit-rows">
                    <div><span>Created By</span><strong>{{ auth()->user()->first_name ?? '—' }}</strong></div>
                    <div><span>Status</span><strong>New</strong></div>
                    <div><span>Approval</span><strong>—</strong></div>
                </div>
                <div class="sac-note">Read-only UI · approval workflow placeholder</div>
            </div>

            <div class="sac-help-card">
                <h3>Shortcuts</h3>
                <ul class="sac-shortcuts">
                    <li><span>Warehouse</span><kbd>F2</kbd></li>
                    <li><span>Product search</span><kbd>F3</kbd></li>
                    <li><span>Save</span><kbd>F6</kbd></li>
                    <li><span>Print</span><kbd>F7</kbd></li>
                    <li><span>Barcode focus</span><kbd>F8</kbd></li>
                    <li><span>Save</span><kbd>Ctrl+S</kbd></li>
                    <li><span>Cancel</span><kbd>ESC</kbd></li>
                </ul>
            </div>
        </aside>
    </div>
</section>
@stop
@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/stock-adjustments-create-premium-ui.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        __page_leave_confirmation('#stock_adjustment_form');
    </script>
@endsection


@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot
