@extends('layouts.app')
@section('title', __('lang_v1.add_stock_transfer'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/stock-transfers-create-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print stc-shell">
    {{-- Sticky Header --}}
    <div class="stc-header" role="banner">
        <div class="stc-header-left">
            <h1>@lang('lang_v1.add_stock_transfer')</h1>
            <p class="stc-subtitle">Source warehouse → destination · products · shipping</p>
            <div class="stc-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('lang_v1.stock_transfers')</span>
                <span>/</span>
                <span>@lang('messages.add')</span>
            </div>
        </div>
        <div class="stc-header-actions">
            <button type="button" class="stc-btn stc-btn-ghost" id="stc_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="stc-btn" id="stc_refresh_btn" title="Refresh">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a class="stc-btn" id="stc_cancel_link" href="{{ action([\App\Http\Controllers\StockTransferController::class, 'index']) }}">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="button" class="stc-btn stc-btn-primary" id="stc_header_save">
                <i class="fas fa-save"></i> @lang('messages.save')
            </button>
        </div>
        <div class="stc-header-meta" aria-label="Transfer context">
            <div class="stc-meta-item"><span>Transfer #</span><strong>Auto</strong></div>
            <div class="stc-meta-item"><span>Reference</span><strong id="stc_meta_ref">Auto</strong></div>
            <div class="stc-meta-item"><span>Date</span><strong id="stc_meta_date">—</strong></div>
            <div class="stc-meta-item"><span>Status</span><strong id="stc_meta_status">—</strong></div>
            <div class="stc-meta-item"><span>From</span><strong id="stc_meta_from">—</strong></div>
            <div class="stc-meta-item"><span>To</span><strong id="stc_meta_to">—</strong></div>
            <div class="stc-meta-item"><span>User</span><strong>{{ auth()->user()->first_name ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="stc-live-bar" aria-label="Live transfer dashboard">
        <div class="stc-live-item">
            <span>Source</span>
            <strong id="stc_live_from">—</strong>
        </div>
        <div class="stc-live-item">
            <span>Destination</span>
            <strong id="stc_live_to">—</strong>
        </div>
        <div class="stc-live-item">
            <span>Products</span>
            <strong id="stc_live_products">0</strong>
        </div>
        <div class="stc-live-item">
            <span>Transfer Value</span>
            <strong id="stc_live_value">0.00</strong>
        </div>
    </div>

    <div class="stc-future-strip" aria-label="Coming soon">
        <span class="stc-chip"><i class="fas fa-qrcode"></i> QR Transfer</span>
        <span class="stc-chip"><i class="fas fa-camera"></i> Camera Scan</span>
        <span class="stc-chip"><i class="fas fa-check-double"></i> Multi-Level Approval</span>
        <span class="stc-chip"><i class="fas fa-truck"></i> Courier / GPS</span>
        <span class="stc-chip"><i class="fas fa-robot"></i> AI Stock Tips</span>
        <span class="stc-chip muted">UI placeholders — existing AJAX &amp; validation unchanged</span>
    </div>

    <div class="stc-layout">
        <div class="stc-main">

        {!! Form::open([
            'url' => action([\App\Http\Controllers\StockTransferController::class, 'store']),
            'method' => 'post',
            'id' => 'stock_transfer_form',
        ]) !!}

        {{-- Transfer Information + Warehouses --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="stc-section-title"><i class="fas fa-info-circle"></i> Transfer Information</div>
            <div class="row">
                <div class="col-sm-4">
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
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                        {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('status', __('sale.status') . ':*') !!} @show_tooltip(__('lang_v1.completed_status_help'))
                        {!! Form::select('status', $statuses, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'status',
                        ]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>

                <div class="col-sm-12">
                    <div class="stc-section-title" style="margin-top:8px;"><i class="fas fa-warehouse"></i> Warehouse Selection</div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('lang_v1.location_from') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'location_id',
                        ]) !!}
                        <p class="stc-search-hint">Source warehouse · product search unlocks after selection</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('transfer_location_id', __('lang_v1.location_to') . ':*') !!}
                        {!! Form::select('transfer_location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'required',
                            'id' => 'transfer_location_id',
                        ]) !!}
                        <p class="stc-search-hint">Must differ from source (existing validation)</p>
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Product search + lines --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="stc-section-title"><i class="fas fa-search"></i> Smart Product Search</div>
            <div class="row">
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group stc-search-panel">
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
                        <p class="stc-search-hint">
                            Existing AJAX <code>/products/list</code> · USB/barcode scanners type into this field · F3 focus · F8 scan focus
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <input type="hidden" id="product_row_index" value="0">
                    <input type="hidden" id="total_amount" name="final_total" value="0">
                    <div class="stc-section-title"><i class="fas fa-boxes"></i> Transfer Items</div>
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
                                        <div class="pull-right"><b>@lang('sale.total'): </b> <span
                                                id="total_adjustment">0.00</span></div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="stc-imei-note">
                        <strong>IMEI / Serial:</strong> When product rows include serial/IMEI controls from the existing
                        stock-transfer product row template, those fields and validations continue to work unchanged.
                    </div>
                </div>
            </div>
        @endcomponent

        {{-- Shipping + notes + save --}}
        @component('components.widget', ['class' => 'box-solid'])
            <div class="stc-section-title"><i class="fas fa-truck"></i> Shipping &amp; Notes</div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('shipping_charges', __('lang_v1.shipping_charges') . ':') !!}
                        {!! Form::text('shipping_charges', 0, [
                            'class' => 'form-control input_number',
                            'placeholder' => __('lang_v1.shipping_charges'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('purchase.additional_notes')) !!}
                        {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Attachments (UI)</label>
                        <div class="stc-imei-note" style="margin-top:0;">
                            Document / packing-list upload placeholders — not connected to backend.
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-right show_price_with_permission">
                    <b>@lang('stock_adjustment.total_amount'):</b> <span id="final_total_text">0.00</span>
                </div>
                <br>
                <br>
                <div class="col-sm-12 text-center stc-actions-bar" style="justify-content:center;">
                    <a class="stc-btn" href="{{ action([\App\Http\Controllers\StockTransferController::class, 'index']) }}">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" id="save_stock_transfer" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white stc-btn stc-btn-primary">
                        @lang('messages.save')
                    </button>
                </div>
            </div>
        @endcomponent

        {!! Form::close() !!}
        </div>

        <aside class="stc-side" aria-label="Transfer summary">
            <div class="stc-summary-card">
                <h3>Transfer Summary</h3>
                <div class="stc-summary-rows">
                    <div><span>Products</span><strong id="stc_sum_products">0</strong></div>
                    <div><span>Total Qty</span><strong id="stc_sum_qty">0</strong></div>
                    <div><span>Transfer Value</span><strong id="stc_sum_value">0.00</strong></div>
                    <div><span>Shipping</span><strong id="stc_sum_shipping">0</strong></div>
                    <div><span>Grand Total</span><strong id="stc_sum_grand">0.00</strong></div>
                </div>
                <button type="button" class="stc-btn stc-btn-primary" id="stc_side_save" style="width:100%;margin-top:12px;">
                    <i class="fas fa-save"></i> @lang('messages.save')
                </button>
                <div class="stc-imei-note">
                    Totals mirror <code>#total_adjustment</code> / <code>#final_total_text</code> from existing JS.
                </div>
            </div>

            <div class="stc-help-card">
                <h3>Shortcuts</h3>
                <ul class="stc-shortcuts">
                    <li><span>Source warehouse</span><kbd>F2</kbd></li>
                    <li><span>Product search</span><kbd>F3</kbd></li>
                    <li><span>Save</span><kbd>F6</kbd></li>
                    <li><span>Print</span><kbd>F7</kbd></li>
                    <li><span>Barcode focus</span><kbd>F8</kbd></li>
                    <li><span>Cancel</span><kbd>ESC</kbd></li>
                </ul>
            </div>
        </aside>
    </div>
</section>
@stop
@section('javascript')
    <script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/stock-transfers-create-premium-ui.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        __page_leave_confirmation('#stock_transfer_form');
    </script>
@endsection


@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot
