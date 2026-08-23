@extends('layouts.app')
@section('title', __( 'lang_v1.shipments'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/shipments-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp

<section class="content no-print sh-shell">
    {{-- Sticky Header --}}
    <div class="sh-header" role="banner">
        <div class="sh-header-left">
            <h1>@lang( 'lang_v1.shipments')</h1>
            <p class="sh-subtitle">Pack · dispatch · track · deliver</p>
            <div class="sh-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
                <span>/</span>
                <span>@lang('lang_v1.shipments')</span>
            </div>
        </div>
        <div class="sh-header-actions">
            <div class="sh-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="sh_quick_search" class="form-control" placeholder="Search shipments…" aria-label="Search shipments" autocomplete="off">
            </div>
            <button type="button" class="sh-btn sh-btn-ghost" id="sh_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="sh-btn sh-btn-ghost" id="sh_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="sh-btn sh-btn-ghost" id="sh_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('direct_sell.access')
                <a class="sh-btn sh-btn-success" href="{{ action([\App\Http\Controllers\SellController::class, 'index']) }}">
                    <i class="fas fa-file-invoice"></i> @lang('sale.sells')
                </a>
            @endcan
            @can('sell.create')
                <a class="sh-btn sh-btn-primary" href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="sh-kpi-grid" aria-label="Shipment KPIs">
        <div class="sh-kpi tone-blue">
            <div class="sh-kpi-icon"><i class="fas fa-truck"></i></div>
            <span class="sh-kpi-label">Total Shipments</span>
            <span class="sh-kpi-value" id="sh_kpi_total">—</span>
            <span class="sh-kpi-hint">All shipment records</span>
        </div>
        <div class="sh-kpi tone-teal">
            <div class="sh-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="sh-kpi-label">Filtered</span>
            <span class="sh-kpi-value" id="sh_kpi_filtered">—</span>
            <span class="sh-kpi-hint">Matching filters</span>
        </div>
        <div class="sh-kpi tone-orange">
            <div class="sh-kpi-icon"><i class="fas fa-clock"></i></div>
            <span class="sh-kpi-label">Pending (page)</span>
            <span class="sh-kpi-value" id="sh_kpi_pending">—</span>
            <span class="sh-kpi-hint">From status labels</span>
        </div>
        <div class="sh-kpi tone-violet">
            <div class="sh-kpi-icon"><i class="fas fa-shipping-fast"></i></div>
            <span class="sh-kpi-label">In Transit (page)</span>
            <span class="sh-kpi-value" id="sh_kpi_transit">—</span>
            <span class="sh-kpi-hint">Dispatched / transit</span>
        </div>
        <div class="sh-kpi tone-green">
            <div class="sh-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="sh-kpi-label">Delivered (page)</span>
            <span class="sh-kpi-value" id="sh_kpi_delivered">—</span>
            <span class="sh-kpi-hint">From status labels</span>
        </div>
        <div class="sh-kpi tone-slate">
            <div class="sh-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="sh-kpi-label">Customers (page)</span>
            <span class="sh-kpi-value" id="sh_kpi_customers">—</span>
            <span class="sh-kpi-hint">Unique on page</span>
        </div>
        <div class="sh-kpi tone-blue">
            <div class="sh-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="sh-kpi-label">IMEI Shipments</span>
            <span class="sh-kpi-value">—</span>
            <span class="sh-kpi-hint">On invoice / packing</span>
        </div>
        <div class="sh-kpi tone-teal">
            <div class="sh-kpi-icon"><i class="fas fa-globe"></i></div>
            <span class="sh-kpi-label">International</span>
            <span class="sh-kpi-value">—</span>
            <span class="sh-kpi-hint">UI placeholder</span>
        </div>
        <div class="sh-kpi tone-orange">
            <div class="sh-kpi-icon"><i class="fas fa-box"></i></div>
            <span class="sh-kpi-label">Ready to Ship</span>
            <span class="sh-kpi-value">—</span>
            <span class="sh-kpi-hint">Filter by status</span>
        </div>
        <div class="sh-kpi tone-red">
            <div class="sh-kpi-icon"><i class="fas fa-times-circle"></i></div>
            <span class="sh-kpi-label">Cancelled</span>
            <span class="sh-kpi-value">—</span>
            <span class="sh-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="sh-future-strip" aria-label="Coming soon">
        <span class="sh-chip"><i class="fas fa-map-marker-alt"></i> Live GPS</span>
        <span class="sh-chip"><i class="fas fa-qrcode"></i> QR Package</span>
        <span class="sh-chip"><i class="fas fa-signature"></i> Proof of Delivery</span>
        <span class="sh-chip"><i class="fas fa-sms"></i> SMS Alerts</span>
        <span class="sh-chip"><i class="fas fa-route"></i> Route Optimize</span>
        <span class="sh-chip"><i class="fas fa-robot"></i> AI ETA</span>
        <span class="sh-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="sh-main-grid">
        <div class="sh-main-col">
            {{-- Filters: preserve IDs used by shipments DataTable ajax --}}
            <div class="sh-card sh-filter-card">
                <div class="sh-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the shipments DataTable (`only_shipments`)</p>
                    </div>
                </div>
                <div class="sh-card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}
                                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                                {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('created_by',  __('report.user') . ':') !!}
                                {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('sell_list_filter_payment_status',  __('purchase.payment_status') . ':') !!}
                                {!! Form::select('sell_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('shipping_status',  __('lang_v1.shipping_status') . ':') !!}
                                {!! Form::select('shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('delivery_person',  __('lang_v1.delivery_person') . ':') !!}
                                {!! Form::select('delivery_person', $delevery_person, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                        @if(!empty($service_staffs))
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('service_staffs', __('restaurant.service_staff') . ':') !!}
                                    {!! Form::select('service_staffs', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="sh-filter-actions">
                        <button type="button" class="sh-btn sh-btn-primary" id="sh_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="sh-btn" id="sh_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="sh-card">
                <div class="sh-card-head">
                    <div>
                        <h3>@lang( 'lang_v1.shipments')</h3>
                        <p>Server-side DataTable · edit shipping status via existing modal actions</p>
                    </div>
                </div>
                <div class="sh-card-body">
                    @if(auth()->user()->can('access_shipping') ||
                     auth()->user()->can('access_own_shipping') ||
                      auth()->user()->can('access_commission_agent_shipping') )
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('lang_v1.delivery_person')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        @if(!empty($custom_labels['shipping']['custom_field_1']))
                                            <th>
                                                {{$custom_labels['shipping']['custom_field_1']}}
                                            </th>
                                        @endif
                                        @if(!empty($custom_labels['shipping']['custom_field_2']))
                                            <th>
                                                {{$custom_labels['shipping']['custom_field_2']}}
                                            </th>
                                        @endif
                                        @if(!empty($custom_labels['shipping']['custom_field_3']))
                                            <th>
                                                {{$custom_labels['shipping']['custom_field_3']}}
                                            </th>
                                        @endif
                                        @if(!empty($custom_labels['shipping']['custom_field_4']))
                                            <th>
                                                {{$custom_labels['shipping']['custom_field_4']}}
                                            </th>
                                        @endif
                                        @if(!empty($custom_labels['shipping']['custom_field_5']))
                                            <th>
                                                {{$custom_labels['shipping']['custom_field_5']}}
                                            </th>
                                        @endif
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <aside class="sh-side" aria-label="Shipment details">
            <div class="sh-preview-card">
                <h3>Shipment Details</h3>
                <p class="sh-preview-empty" id="sh_preview_empty">Select a shipment row to preview customer, courier, status, and quick actions. Update shipping via the status badge / action menu.</p>
                <div id="sh_preview_content" style="display:none;">
                    <div class="sh-preview-rows">
                        <div><span>Invoice</span><strong id="sh_preview_invoice">—</strong></div>
                        <div><span>Status</span><strong><span class="sh-badge" id="sh_preview_status">—</span></strong></div>
                        <div><span>Date</span><strong id="sh_preview_date">—</strong></div>
                        <div><span>Customer</span><strong id="sh_preview_customer">—</strong></div>
                        <div><span>Phone</span><strong id="sh_preview_mobile">—</strong></div>
                        <div><span>Location</span><strong id="sh_preview_location">—</strong></div>
                        <div><span>Courier / Delivery</span><strong id="sh_preview_courier">—</strong></div>
                        <div><span>Payment</span><strong id="sh_preview_payment">—</strong></div>
                    </div>
                    <div class="sh-preview-actions" id="sh_preview_actions"></div>
                    <div class="sh-imei-note">
                        <strong>IMEI / Serial:</strong> Tracked on the related sale / packing documents. Update shipping details with the existing edit-shipping modal.
                    </div>
                </div>
            </div>

            <div class="sh-summary-card">
                <h3>Page Summary</h3>
                <div class="sh-summary-rows">
                    <div><span>Filtered shipments</span><strong id="sh_sum_filtered">—</strong></div>
                    <div><span>Customers (page)</span><strong id="sh_sum_customers">—</strong></div>
                    <div><span>Delivered (page)</span><strong id="sh_sum_delivered">—</strong></div>
                </div>
            </div>
        </aside>
    </div>
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>

@stop

@section('javascript')
<script src="{{ asset('js/shipments-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
    });

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[1, 'desc']],
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        "ajax": {
            "url": "/sells",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                if($('#sell_list_filter_location_id').length) {
                    d.location_id = $('#sell_list_filter_location_id').val();
                }
                d.customer_id = $('#sell_list_filter_customer_id').val();

                if($('#sell_list_filter_payment_status').length) {
                    d.payment_status = $('#sell_list_filter_payment_status').val();
                }
                if($('#created_by').length) {
                    d.created_by = $('#created_by').val();
                }
                if($('#service_staffs').length) {
                    d.service_staffs = $('#service_staffs').val();
                }
                d.only_shipments = true;
                d.shipping_status = $('#shipping_status').val();
                d.delivery_person = $('#delivery_person').val();
            }
        },
        columns: [
            { data: 'action', name: 'action', searchable: false, orderable: false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'conatct_name', name: 'conatct_name'},
            { data: 'mobile', name: 'contacts.mobile'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'delivery_person', name: 'delivery_person'},
            { data: 'shipping_status', name: 'shipping_status'},
            @if(!empty($custom_labels['shipping']['custom_field_1']))
                { data: 'shipping_custom_field_1', name: 'shipping_custom_field_1'},
            @endif
            @if(!empty($custom_labels['shipping']['custom_field_2']))
                { data: 'shipping_custom_field_2', name: 'shipping_custom_field_2'},
            @endif
            @if(!empty($custom_labels['shipping']['custom_field_3']))
                { data: 'shipping_custom_field_3', name: 'shipping_custom_field_3'},
            @endif
            @if(!empty($custom_labels['shipping']['custom_field_4']))
                { data: 'shipping_custom_field_4', name: 'shipping_custom_field_4'},
            @endif
            @if(!empty($custom_labels['shipping']['custom_field_5']))
                { data: 'shipping_custom_field_5', name: 'shipping_custom_field_5'},
            @endif
            { data: 'payment_status', name: 'payment_status'},
            { data: 'waiter', name: 'ss.first_name', @if(empty($is_service_staff_enabled)) visible: false @endif }
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#sell_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(4)').attr('class', 'clickable_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #shipping_status, #service_staffs, #delivery_person',  function() {
        sell_table.ajax.reload();
    });
});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
