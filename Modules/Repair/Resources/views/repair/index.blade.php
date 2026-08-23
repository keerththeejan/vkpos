@extends('layouts.app')
@section('title', __('repair::lang.repair'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/repair-orders-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@include('repair::layouts.nav')

<section class="content no-print ro-shell">
    {{-- Sticky Header --}}
    <div class="ro-header" role="banner">
        <div class="ro-header-left">
            <h1>@lang('repair::lang.invoices')</h1>
            <p class="ro-subtitle">Service orders · invoices · payments · warranty</p>
            <div class="ro-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('repair::lang.repair')</span>
                <span>/</span>
                <span>@lang('repair::lang.invoices')</span>
            </div>
        </div>
        <div class="ro-header-actions">
            <div class="ro-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="ro_quick_search" class="form-control" placeholder="Search repair orders…" aria-label="Search repair orders" autocomplete="off">
            </div>
            <button type="button" class="ro-btn ro-btn-ghost" id="ro_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ro-btn ro-btn-ghost" id="ro_refresh_tables" title="Refresh" aria-label="Refresh tables">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="ro-btn ro-btn-ghost" id="ro_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @if (auth()->user()->can('edit_repair_settings'))
                <a class="ro-btn ro-btn-ghost" href="{{ action([\Modules\Repair\Http\Controllers\RepairSettingsController::class, 'index']) }}" title="Settings" aria-label="Settings">
                    <i class="fas fa-cog"></i>
                </a>
            @endif
            @can('repair.create')
                <a class="ro-btn ro-btn-primary" target="_blank" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) . '?sub_type=repair' }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTables --}}
    <div class="ro-kpi-grid" aria-label="Repair order KPIs">
        <div class="ro-kpi tone-orange">
            <div class="ro-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="ro-kpi-label">@lang('repair::lang.pending')</span>
            <span class="ro-kpi-value" id="ro_kpi_pending">—</span>
            <span class="ro-kpi-hint">Open repair invoices</span>
        </div>
        <div class="ro-kpi tone-green">
            <div class="ro-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="ro-kpi-label">@lang('repair::lang.completed')</span>
            <span class="ro-kpi-value" id="ro_kpi_completed">—</span>
            <span class="ro-kpi-hint">Completed status invoices</span>
        </div>
        <div class="ro-kpi tone-blue">
            <div class="ro-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="ro-kpi-label">Filtered Pending</span>
            <span class="ro-kpi-value" id="ro_kpi_filtered_pending">—</span>
            <span class="ro-kpi-hint">Matching filters</span>
        </div>
        <div class="ro-kpi tone-teal">
            <div class="ro-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="ro-kpi-label">Filtered Completed</span>
            <span class="ro-kpi-value" id="ro_kpi_filtered_completed">—</span>
            <span class="ro-kpi-hint">Matching filters</span>
        </div>
        <div class="ro-kpi tone-purple">
            <div class="ro-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="ro-kpi-label">Warranty</span>
            <span class="ro-kpi-value">Linked</span>
            <span class="ro-kpi-hint">Shown per invoice row</span>
        </div>
        <div class="ro-kpi tone-slate">
            <div class="ro-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="ro-kpi-label">IMEI / Serial</span>
            <span class="ro-kpi-value">Ready</span>
            <span class="ro-kpi-hint">Tracked on each order</span>
        </div>
    </div>

    <div class="ro-future-strip" aria-label="Coming soon">
        <span class="ro-chip"><i class="fas fa-qrcode"></i> QR Tracking</span>
        <span class="ro-chip"><i class="fab fa-whatsapp"></i> WhatsApp Status</span>
        <span class="ro-chip"><i class="fas fa-signature"></i> Digital Signature</span>
        <span class="ro-chip"><i class="fas fa-robot"></i> AI Diagnosis</span>
        <span class="ro-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="ro-main-grid">
        <div class="ro-main-col">
            {{-- Filters: preserve all IDs used by DataTables AJAX --}}
            <div class="ro-card ro-filter-card">
                <div class="ro-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Existing filters drive pending &amp; completed tables</p>
                    </div>
                </div>
                <div class="ro-card-body">
                    <div class="row">
                        @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_location_id', 'sell_list_filter_customer_id', 'sell_list_filter_payment_status', 'sell_list_filter_date_range', 'created_by']])
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('repair_status_id',  __('sale.status') . ':') !!}
                                {!! Form::select('repair_status_id', $repair_status_dropdown['statuses'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        @if(in_array('service_staff' ,$enabled_modules) && !$is_service_staff)
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('service_staff_id',  __('repair::lang.technician') . ':') !!}
                                {!! Form::select('service_staff_id', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ro-card">
                <div class="ro-card-body ro-tabs-wrap">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#pending_repair_tab" data-toggle="tab" aria-expanded="true">
                                    <i class="fas fa-exclamation-circle text-orange"></i>
                                    @lang('repair::lang.pending')
                                    @show_tooltip(__('repair::lang.common_pending_status_tooltip'))
                                </a>
                            </li>
                            <li>
                                <a href="#completed_repair_tab" data-toggle="tab" aria-expanded="true">
                                    <i class="fa fas fa-check-circle text-success"></i>
                                    @lang('repair::lang.completed')
                                    @show_tooltip(__('repair::lang.common_completed_status_tooltip'))
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="pending_repair_tab">
                                <div class="row">
                                    <div class="col-md-12 mb-12">
                                        <a target="_blank" class="btn btn-sm btn-primary pull-right ro-add-inline" href="{{action([\App\Http\Controllers\SellPosController::class, 'create']). '?sub_type=repair'}}">
                                            <i class="fa fa-plus"></i> @lang('messages.add')
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive ro-table-wrap">
                                    <table class="table table-bordered table-striped ajax_view" id="pending_repair_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>@lang('receipt.date')</th>
                                                <th>
                                                    @lang('repair::lang.delivery_date')
                                                    @show_tooltip(__('repair::lang.repair_due_date_tooltip'))
                                                </th>
                                                <th>@lang('repair::lang.job_sheet_no')</th>
                                                <th>@lang('sale.invoice_no')</th>
                                                @if(in_array('service_staff' ,$enabled_modules))
                                                    <th>@lang('repair::lang.technician')</th>
                                                @endif
                                                <th>@lang('lang_v1.added_by')</th>
                                                <th>@lang('sale.customer_name')</th>
                                                <th>@lang('product.brand')</th>
                                                <th>@lang('repair::lang.device_model')</th>
                                                <th>@lang('repair::lang.serial_no')</th>
                                                <th>@lang('sale.status')</th>
                                                <th>@lang('sale.location')</th>
                                                <th>@lang('repair::lang.repair_warranty')</th>
                                                <th>@lang('sale.payment_status')</th>
                                                <th>@lang('sale.total_amount')</th>
                                                <th>@lang('purchase.payment_due')</th>
                                                <th>@lang('lang_v1.sell_return_due')</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr class="bg-gray font-17 footer-total text-center">
                                                <td
                                                    @if(in_array('service_staff' ,$enabled_modules))
                                                        colspan="11"
                                                    @else
                                                        colspan="10"
                                                    @endif>
                                                        <strong>@lang('sale.total'):</strong>
                                                </td>
                                                <td id="footer_pending_repair_status_count"></td>
                                                <td></td>
                                                <td></td>
                                                <td id="pending_repair_footer_payment_status_count"></td>
                                                <td>
                                                    <span class="display_currency" id="pending_repair_footer_total" data-currency_symbol ="true"></span>
                                                </td>
                                                <td class="text-left">
                                                    <small>
                                                        <span class="display_currency" id="pending_repair_footer_total_remaining" data-currency_symbol ="true"></span>
                                                    </small>
                                                </td>
                                                <td><span class="display_currency" id="pending_repair_footer_total_sell_return_due" data-currency_symbol ="true"></span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="completed_repair_tab">
                                <div class="row">
                                    <div class="col-md-12 mb-12">
                                        <a target="_blank" class="btn btn-sm btn-primary pull-right ro-add-inline" href="{{action([\App\Http\Controllers\SellPosController::class, 'create']). '?sub_type=repair'}}">
                                            <i class="fa fa-plus"></i> @lang('messages.add')
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive ro-table-wrap">
                                    <table class="table table-bordered table-striped ajax_view" id="sell_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>@lang('receipt.date')</th>
                                                <th>
                                                    @lang('repair::lang.delivery_date')
                                                    @show_tooltip(__('repair::lang.repair_due_date_tooltip'))
                                                </th>
                                                <th>@lang('repair::lang.job_sheet_no')</th>
                                                <th>@lang('sale.invoice_no')</th>
                                                @if(in_array('service_staff' ,$enabled_modules))
                                                    <th>@lang('repair::lang.technician')</th>
                                                @endif
                                                <th>@lang('lang_v1.added_by')</th>
                                                <th>@lang('sale.customer_name')</th>
                                                <th>@lang('product.brand')</th>
                                                <th>@lang('repair::lang.device_model')</th>
                                                <th>@lang('repair::lang.serial_no')</th>
                                                <th>@lang('sale.status')</th>
                                                <th>@lang('sale.location')</th>
                                                <th>@lang('repair::lang.repair_warranty')</th>
                                                <th>@lang('sale.payment_status')</th>
                                                <th>@lang('sale.total_amount')</th>
                                                <th>@lang('purchase.payment_due')</th>
                                                <th>@lang('lang_v1.sell_return_due')</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr class="bg-gray font-17 footer-total text-center">
                                                <td
                                                    @if(in_array('service_staff' ,$enabled_modules))
                                                        colspan="11"
                                                    @else
                                                        colspan="10"
                                                    @endif>
                                                        <strong>@lang('sale.total'):</strong>
                                                </td>
                                                <td id="footer_repair_status_count"></td>
                                                <td></td>
                                                <td></td>
                                                <td id="footer_payment_status_count"></td>
                                                <td>
                                                    <span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span>
                                                </td>
                                                <td class="text-left">
                                                    <small>
                                                        <span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span>
                                                    </small>
                                                </td>
                                                <td><span class="display_currency" id="footer_total_sell_return_due" data-currency_symbol ="true"></span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="ro-side-col">
            <div class="ro-card ro-preview-card" id="ro_preview_card" aria-live="polite">
                <div class="ro-card-head">
                    <div>
                        <h3>Order Preview</h3>
                        <p>Select a row to inspect details</p>
                    </div>
                </div>
                <div class="ro-card-body">
                    <div class="ro-preview-empty" id="ro_preview_empty">
                        <i class="fas fa-file-invoice"></i>
                        <p>Click any repair invoice row to preview customer, device, serial, payment, and actions.</p>
                    </div>
                    <div class="ro-preview-content" id="ro_preview_content" style="display:none;">
                        <div class="ro-preview-badge"><i class="fas fa-wrench"></i></div>
                        <div class="ro-preview-title-row">
                            <h4 id="ro_preview_invoice">—</h4>
                            <span class="ro-pill" id="ro_preview_status">—</span>
                        </div>
                        <div class="ro-preview-meta">
                            <div><span class="k">Customer</span><span class="v" id="ro_preview_customer">—</span></div>
                            <div><span class="k">Device</span><span class="v" id="ro_preview_device">—</span></div>
                            <div><span class="k">Serial / IMEI</span><span class="v" id="ro_preview_serial">—</span></div>
                            <div><span class="k">Technician</span><span class="v" id="ro_preview_tech">—</span></div>
                            <div><span class="k">Warranty</span><span class="v" id="ro_preview_warranty">—</span></div>
                            <div><span class="k">Payment</span><span class="v" id="ro_preview_payment">—</span></div>
                            <div><span class="k">Total</span><span class="v" id="ro_preview_total">—</span></div>
                            <div><span class="k">Balance Due</span><span class="v" id="ro_preview_due">—</span></div>
                        </div>
                        <h5 class="ro-preview-section-title">Quick Actions</h5>
                        <div class="ro-preview-actions" id="ro_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="ro-card">
                <div class="ro-card-head">
                    <div>
                        <h3>Financial Snapshot</h3>
                        <p>From table footer totals (active tab)</p>
                    </div>
                </div>
                <div class="ro-card-body">
                    <div class="ro-finance-list">
                        <div class="ro-finance-item">
                            <span>Pending Total</span>
                            <strong id="ro_fin_pending_total">—</strong>
                        </div>
                        <div class="ro-finance-item">
                            <span>Pending Due</span>
                            <strong id="ro_fin_pending_due">—</strong>
                        </div>
                        <div class="ro-finance-item">
                            <span>Completed Total</span>
                            <strong id="ro_fin_completed_total">—</strong>
                        </div>
                        <div class="ro-finance-item">
                            <span>Completed Due</span>
                            <strong id="ro_fin_completed_due">—</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ro-card">
                <div class="ro-card-head">
                    <div>
                        <h3>Quick Links</h3>
                        <p>Existing repair routes</p>
                    </div>
                </div>
                <div class="ro-card-body">
                    <div class="ro-quick-links">
                        @can('repair.create')
                            <a target="_blank" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) . '?sub_type=repair' }}"><i class="fas fa-plus"></i> New Repair Invoice</a>
                        @endcan
                        @if(auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all'))
                            <a href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']) }}"><i class="fas fa-clipboard-list"></i> Job Sheets</a>
                        @endif
                        <a href="{{ action([\Modules\Repair\Http\Controllers\DashboardController::class, 'index']) }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="{{ url('/contacts') }}"><i class="fas fa-users"></i> Customers</a>
                        <a href="{{ url('/products') }}"><i class="fas fa-box"></i> Products / Parts</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="edit_repair_status_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
            pending_repair_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
        pending_repair_table.ajax.reload();
    });

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[2, 'asc']],
        "ajax": {
            "url": "/repair/repair",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_direct_sale = 1;
                d.is_completed_status = 1;
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sub_type = 'repair';
                d.repair_status_id = $('#repair_status_id').val();
                @if(in_array('service_staff' ,$enabled_modules))
                    d.service_staff_id = $('#service_staff_id').val();
                @endif
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'repair_due_date', name: 'repair_due_date'  },
            { data: 'job_sheet_no', name: 'rjs.job_sheet_no'},
            { data: 'invoice_no', name: 'invoice_no'},
            @if(in_array('service_staff' ,$enabled_modules))
                { data: 'service_staff', name: 'ss.first_name'},
            @endif
            { data: 'added_by', name: 'added_by', orderable: false, searchable: false},
            { data: 'name', name: 'contacts.name'},
            { data: 'brand', name: 'b.name'},
            { data: 'device_model', name: 'rdm.name'},
            { data: 'repair_serial_no', name: 'transactions.repair_serial_no'},
            { data: 'repair_status', name: 'rs.name'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'warranty_name', name: 'rw.name'},
            { data: 'payment_status', name: 'payment_status'},
            { data: 'final_total', name: 'final_total', orderable: false, searchable: false},
            { data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false},
            { data: 'return_due', name: 'return_due', orderable: false, searchable: false}
        ],
        "fnDrawCallback": function (oSettings) {

            $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));

            $('#footer_total_remaining').text(sum_table_col($('#sell_table'), 'payment_due'));

            $('#footer_total_sell_return_due').text(sum_table_col($('#sell_table'), 'sell_return_due'));
            
            $('#footer_payment_status_count').html(__sum_status_html($('#sell_table'), 'payment-status-label'));

            $('#footer_repair_status_count').html(__sum_status_html($('#sell_table'), 'edit_repair_status'));

            __currency_convert_recursively($('#sell_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(11)').attr('class', 'clickable_td');
            $( row ).find('td:eq(14)').attr('class', 'clickable_td edit_status_td');
        }
    });

    pending_repair_table = $('#pending_repair_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[2, 'asc']],
        "ajax": {
            "url": "/repair/repair",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_direct_sale = 1;
                d.is_completed_status = 0;
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sub_type = 'repair';
                d.repair_status_id = $('#repair_status_id').val();
                @if(in_array('service_staff' ,$enabled_modules))
                    d.service_staff_id = $('#service_staff_id').val();
                @endif
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'repair_due_date', name: 'repair_due_date'  },
            { data: 'job_sheet_no', name: 'rjs.job_sheet_no'},
            { data: 'invoice_no', name: 'invoice_no'},
            @if(in_array('service_staff' ,$enabled_modules))
                { data: 'service_staff', name: 'ss.first_name'},
            @endif
            { data: 'added_by', name: 'added_by', orderable: false, searchable: false},
            { data: 'name', name: 'contacts.name'},
            { data: 'brand', name: 'b.name'},
            { data: 'device_model', name: 'rdm.name'},
            { data: 'repair_serial_no', name: 'transactions.repair_serial_no'},
            { data: 'repair_status', name: 'rs.name'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'warranty_name', name: 'rw.name'},
            { data: 'payment_status', name: 'payment_status'},
            { data: 'final_total', name: 'final_total', orderable: false, searchable: false},
            { data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false},
            { data: 'return_due', name: 'return_due', orderable: false, searchable: false}
        ],
        "fnDrawCallback": function (oSettings) {

            $('#pending_repair_footer_total').text(sum_table_col($('#pending_repair_table'), 'final-total'));

            $('#pending_repair_footer_total_remaining').text(sum_table_col($('#pending_repair_table'), 'payment_due'));

            $('#pending_repair_footer_total_sell_return_due').text(sum_table_col($('#pending_repair_table'), 'sell_return_due'));
            
            $('#pending_repair_footer_payment_status_count').html(__sum_status_html($('#pending_repair_table'), 'payment-status-label'));

            $('#footer_pending_repair_status_count').html(__sum_status_html($('#pending_repair_table'), 'edit_repair_status'));

            __currency_convert_recursively($('#pending_repair_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(11)').attr('class', 'clickable_td');
            $( row ).find('td:eq(14)').attr('class', 'clickable_td edit_status_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #service_staff_id, #repair_status_id, #created_by',  function() {
        sell_table.ajax.reload();
        pending_repair_table.ajax.reload();
    });
    @can("repair_status.update")
        $(document).on('click', '.edit_repair_status', function(e){
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#edit_repair_status_modal').html(result).modal('show');
                }
            });
        });
    @endcan

    $('#edit_repair_status_modal').on('shown.bs.modal', function (e) {
        $('#send_sms').change(function() {
            if ($(this). is(":checked")) {
                $('div.sms_body').fadeIn();
            } else {
                $('div.sms_body').fadeOut();
            }
        });

        if ($('#repair_status_id_modal').length) {
            ;
            $("#sms_body").val($("#repair_status_id_modal :selected").data('sms_template'));
        }

        $('#repair_status_id_modal').on('change', function() {
            var sms_template = $(this).find(':selected').data('sms_template');
            $("#sms_body").val(sms_template);
        });
    });

    $(document).on('submit', 'form#update_repair_status_form', function(e){
        e.preventDefault();
        var data = $(this).serialize();
        var ladda = Ladda.create(document.querySelector('.ladda-button'));
        ladda.start();
        $.ajax({
            method: $(this).attr("method"),
            url: $(this).attr("action"),
            dataType: "json",
            data: data,
            success: function(result){
                ladda.stop();
                if(result.success == true){
                    $('#edit_repair_status_modal').modal('hide');
                    toastr.success(result.msg);
                    sell_table.ajax.reload();
                    pending_repair_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    $(document).on('click', '.delete_media', function(e){
        e.preventDefault();
        var this_btn = $(this);
        $.ajax({
            url: $(this).attr("href"),
            dataType: "json",
            success: function(result){
                if(result.success == true){
                    this_btn.closest('tr').remove();
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });
    $(document).on('click', '.collapsed-box-title', function(e){
        if (e.target.tagName == 'BUTTON' || e.target.tagName == 'I') {
            return false;
        }
        $(this).find('.box-tools button').click();
    });

});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/repair-orders-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
