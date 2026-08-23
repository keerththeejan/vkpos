@extends('layouts.app')

@section('title', __('repair::lang.job_sheets'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/job-sheet-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@include('repair::layouts.nav')

<section class="content no-print js-shell">
    {{-- Sticky Header --}}
    <div class="js-header" role="banner">
        <div class="js-header-left">
            <h1>@lang('repair::lang.job_sheets')</h1>
            <p class="js-subtitle">Intake, diagnosis, parts, and delivery tracking</p>
            <div class="js-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('repair::lang.repair')</span>
                <span>/</span>
                <span>@lang('repair::lang.job_sheets')</span>
            </div>
        </div>
        <div class="js-header-actions">
            <div class="js-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="js_quick_search" class="form-control" placeholder="Search job sheets…" aria-label="Search job sheets" autocomplete="off">
            </div>
            <button type="button" class="js-btn js-btn-ghost" id="js_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="js-btn js-btn-ghost" id="js_refresh_tables" title="Refresh" aria-label="Refresh tables">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="js-btn js-btn-ghost" id="js_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @if (auth()->user()->can('edit_repair_settings'))
                <a class="js-btn js-btn-ghost" href="{{ action([\Modules\Repair\Http\Controllers\RepairSettingsController::class, 'index']) }}" title="Settings" aria-label="Settings">
                    <i class="fas fa-cog"></i>
                </a>
            @endif
            @can('job_sheet.create')
                <a class="js-btn js-btn-primary" href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="js-kpi-grid" aria-label="Job sheet summary">
        <div class="js-kpi tone-orange">
            <div class="js-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="js-kpi-label">@lang('repair::lang.pending')</span>
            <span class="js-kpi-value" id="js_kpi_pending">—</span>
            <span class="js-kpi-hint">Open / in-progress jobs</span>
        </div>
        <div class="js-kpi tone-green">
            <div class="js-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="js-kpi-label">@lang('repair::lang.completed')</span>
            <span class="js-kpi-value" id="js_kpi_completed">—</span>
            <span class="js-kpi-hint">Completed status jobs</span>
        </div>
        <div class="js-kpi tone-blue">
            <div class="js-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="js-kpi-label">Filtered Pending</span>
            <span class="js-kpi-value" id="js_kpi_filtered_pending">—</span>
            <span class="js-kpi-hint">Matching current filters</span>
        </div>
        <div class="js-kpi tone-teal">
            <div class="js-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="js-kpi-label">Filtered Completed</span>
            <span class="js-kpi-value" id="js_kpi_filtered_completed">—</span>
            <span class="js-kpi-hint">Matching current filters</span>
        </div>
        <div class="js-kpi tone-purple">
            <div class="js-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="js-kpi-label">IMEI / Serial</span>
            <span class="js-kpi-value">Ready</span>
            <span class="js-kpi-hint">Tracked on each job sheet</span>
        </div>
        <div class="js-kpi tone-slate">
            <div class="js-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="js-kpi-label">Warranty</span>
            <span class="js-kpi-value">Linked</span>
            <span class="js-kpi-hint">Uses existing warranty module</span>
        </div>
    </div>

    {{-- Progress tracker (visual only) --}}
    <div class="js-progress-track" aria-label="Repair workflow stages">
        <div class="js-stage active"><span>Received</span></div>
        <div class="js-stage"><span>Inspection</span></div>
        <div class="js-stage"><span>Diagnosis</span></div>
        <div class="js-stage"><span>Approval</span></div>
        <div class="js-stage"><span>Repair</span></div>
        <div class="js-stage"><span>Testing</span></div>
        <div class="js-stage"><span>Ready</span></div>
        <div class="js-stage"><span>Delivered</span></div>
    </div>

    <div class="js-future-strip" aria-label="Coming soon">
        <span class="js-chip"><i class="fas fa-qrcode"></i> QR Tracking</span>
        <span class="js-chip"><i class="fas fa-signature"></i> Digital Signature</span>
        <span class="js-chip"><i class="fas fa-robot"></i> AI Fault Detect</span>
        <span class="js-chip"><i class="fas fa-stopwatch"></i> SLA Timer</span>
        <span class="js-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="js-main-grid">
        <div class="js-main-col">
            {{-- Filters: preserve all IDs used by DataTables AJAX --}}
            <div class="js-card js-filter-card">
                <div class="js-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive both pending &amp; completed tables</p>
                    </div>
                </div>
                <div class="js-card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('contact_id',  __('role.customer') . ':') !!}
                                {!! Form::select('contact_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                        @if(in_array('service_staff' ,$enabled_modules) && !$is_user_service_staff)
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('technician',  __('repair::lang.technician') . ':') !!}
                                    {!! Form::select('technician', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('status_id',  __('sale.status') . ':') !!}
                                {!! Form::select('status_id', $status_dropdown['statuses'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="js-card">
                <div class="js-card-body js-tabs-wrap">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#pending_job_sheet_tab" data-toggle="tab" aria-expanded="true">
                                    <i class="fas fa-exclamation-circle text-orange"></i>
                                    @lang('repair::lang.pending')
                                    @show_tooltip(__('repair::lang.common_pending_status_tooltip'))
                                </a>
                            </li>
                            <li>
                                <a href="#completed_job_sheet_tab" data-toggle="tab" aria-expanded="true">
                                    <i class="fa fas fa-check-circle text-success"></i>
                                    @lang('repair::lang.completed')
                                    @show_tooltip(__('repair::lang.common_completed_status_tooltip'))
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="pending_job_sheet_tab">
                                <div class="row">
                                    <div class="col-md-12 mb-12">
                                        <a type="button" class="btn btn-sm btn-primary pull-right m-5 js-add-inline" href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create'])}}" id="add_job_sheet">
                                            <i class="fa fa-plus"></i> @lang('messages.add')
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive js-table-wrap">
                                    <table class="table table-bordered table-striped" id="pending_job_sheets_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>
                                                    @lang('repair::lang.service_type')
                                                </th>
                                                <th>
                                                    @lang('lang_v1.due_date')
                                                </th>
                                                <th>
                                                    @lang('repair::lang.job_sheet_no')
                                                </th>
                                                <th>@lang('sale.invoice_no')</th>
                                                <th>@lang('sale.status')</th>
                                                @if(in_array('service_staff' ,$enabled_modules))
                                                    <th>@lang('repair::lang.technician')</th>
                                                @endif
                                                <th>
                                                    @lang('role.customer')
                                                </th>
                                                <th>@lang('business.location')</th>
                                                <th>@lang('product.brand')</th>
                                                <th>@lang('repair::lang.device')</th>
                                                <th>@lang('repair::lang.device_model')</th>
                                                <th>@lang('repair::lang.serial_no')</th>
                                                <th>@lang('repair::lang.estimated_cost')</th>
                                                @if(!empty($repair_settings['job_sheet_custom_field_1']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_1']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_2']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_2']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_3']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_3']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_4']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_4']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_5']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_5']}}</th>
                                                @endif
                                                <th>@lang('lang_v1.added_by')</th>
                                                <th>@lang('lang_v1.created_at')</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="completed_job_sheet_tab">
                                <div class="row">
                                    <div class="col-md-12 mb-12">
                                       <a type="button" class="btn btn-sm btn-primary pull-right m-5 js-add-inline" href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create'])}}" id="add_job_sheet">
                                            <i class="fa fa-plus"></i> @lang('messages.add')
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive js-table-wrap">
                                    <table class="table table-bordered table-striped" id="completed_job_sheets_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>
                                                    @lang('repair::lang.service_type')
                                                </th>
                                                <th>
                                                    @lang('lang_v1.due_date')
                                                </th>
                                                <th>
                                                    @lang('repair::lang.job_sheet_no')
                                                </th>
                                                <th>@lang('sale.invoice_no')</th>
                                                <th>@lang('sale.status')</th>
                                                @if(in_array('service_staff' ,$enabled_modules))
                                                    <th>@lang('repair::lang.technician')</th>
                                                @endif
                                                <th>
                                                    @lang('role.customer')
                                                </th>
                                                <th>@lang('business.location')</th>
                                                <th>@lang('product.brand')</th>
                                                <th>@lang('repair::lang.device')</th>
                                                <th>@lang('repair::lang.device_model')</th>
                                                <th>@lang('repair::lang.serial_no')</th>
                                                <th>@lang('repair::lang.estimated_cost')</th>
                                                @if(!empty($repair_settings['job_sheet_custom_field_1']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_1']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_2']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_2']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_3']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_3']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_4']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_4']}}</th>
                                                @endif
                                                @if(!empty($repair_settings['job_sheet_custom_field_5']))
                                                    <th>{{$repair_settings['job_sheet_custom_field_5']}}</th>
                                                @endif
                                                <th>@lang('lang_v1.added_by')</th>
                                                <th>@lang('lang_v1.created_at')</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="js-side-col">
            <div class="js-card js-preview-card" id="js_preview_card" aria-live="polite">
                <div class="js-card-head">
                    <div>
                        <h3>Job Preview</h3>
                        <p>Select a row to inspect details</p>
                    </div>
                </div>
                <div class="js-card-body">
                    <div class="js-preview-empty" id="js_preview_empty">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Click any job sheet row to preview customer, device, serial, and actions.</p>
                    </div>
                    <div class="js-preview-content" id="js_preview_content" style="display:none;">
                        <div class="js-preview-badge"><i class="fas fa-wrench"></i></div>
                        <div class="js-preview-title-row">
                            <h4 id="js_preview_no">—</h4>
                            <span class="js-pill" id="js_preview_status">—</span>
                        </div>
                        <div class="js-preview-meta">
                            <div><span class="k">Customer</span><span class="v" id="js_preview_customer">—</span></div>
                            <div><span class="k">Device</span><span class="v" id="js_preview_device">—</span></div>
                            <div><span class="k">Serial / IMEI</span><span class="v" id="js_preview_serial">—</span></div>
                            <div><span class="k">Technician</span><span class="v" id="js_preview_tech">—</span></div>
                            <div><span class="k">Due</span><span class="v" id="js_preview_due">—</span></div>
                            <div><span class="k">Est. Cost</span><span class="v" id="js_preview_cost">—</span></div>
                        </div>
                        <h5 class="js-preview-section-title">Quick Actions</h5>
                        <div class="js-preview-actions" id="js_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="js-card">
                <div class="js-card-head">
                    <div>
                        <h3>Quick Links</h3>
                        <p>Existing repair routes</p>
                    </div>
                </div>
                <div class="js-card-body">
                    <div class="js-quick-links">
                        @can('job_sheet.create')
                            <a href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create']) }}"><i class="fas fa-plus"></i> New Job Sheet</a>
                        @endcan
                        <a href="{{ action([\Modules\Repair\Http\Controllers\DashboardController::class, 'index']) }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        @if(auth()->user()->can('repair.view') || auth()->user()->can('repair.view_own'))
                            <a href="{{ action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']) }}"><i class="fas fa-file-invoice"></i> Invoices</a>
                        @endif
                        <a href="{{ url('/contacts') }}"><i class="fas fa-users"></i> Customers</a>
                        <a href="{{ url('/products') }}"><i class="fas fa-box"></i> Products / Parts</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade" id="status_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function () {
            pending_job_sheets_datatable = $("#pending_job_sheets_table").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax:{
                        url: '/repair/job-sheet',
                        "data": function ( d ) {
                            d.location_id = $('#location_id').val();
                            d.contact_id = $('#contact_id').val();
                            d.status_id = $('#status_id').val();
                            d.is_completed_status = 0;
                            @if(in_array('service_staff' ,$enabled_modules))
                                d.technician = $('#technician').val();
                            @endif
                        }
                    },
                    columnDefs: [{
                        targets: [0, 4],
                        orderable: false,
                        searchable: false
                    }],
                    aaSorting:[[2, 'asc']],
                    columns:[
                        { data: 'action', name: 'action' },
                        { data: 'service_type', name: 'service_type'},
                        {
                            data: 'delivery_date', name: 'delivery_date'
                        },
                        {
                            data: 'job_sheet_no', name: 'job_sheet_no'
                        },
                        {
                            data: 'repair_no', name: 'repair_no'
                        },
                        { data:'status', name: 'rs.name' },
                        @if(in_array('service_staff' ,$enabled_modules))
                            { data: 'technecian', name: 'technecian', searchable: false},
                        @endif
                        { data: 'customer', name : 'contacts.name'},
                        { data: 'location', name: 'bl.name' },
                        { data: 'brand', name: 'b.name' },
                        { data: 'device', name: 'device.name' },
                        { data: 'device_model', name: 'rdm.name' },
                        {
                            data: 'serial_no', name: 'serial_no'
                        },
                        {
                            data: 'estimated_cost', name: 'estimated_cost'
                        },
                        @if(!empty($repair_settings['job_sheet_custom_field_1']))
                            {
                                data: 'custom_field_1', name: 'repair_job_sheets.custom_field_1'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_2']))
                            {
                                data: 'custom_field_2', name: 'repair_job_sheets.custom_field_2'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_3']))
                            {
                                data: 'custom_field_3', name: 'repair_job_sheets.custom_field_3'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_4']))
                            {
                                data: 'custom_field_4', name: 'repair_job_sheets.custom_field_4'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_5']))
                            {
                                data: 'custom_field_5', name: 'repair_job_sheets.custom_field_5'
                            },
                        @endif
                        { data: 'added_by', name: 'added_by', searchable: false},
                        { data: 'created_at',
                            name: 'repair_job_sheets.created_at'
                        }
                    ],
                    "fnDrawCallback": function (oSettings) {
                        __currency_convert_recursively($('#pending_job_sheets_table'));
                    }
            });

            completed_job_sheets_datatable = $("#completed_job_sheets_table").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax:{
                        url: '/repair/job-sheet',
                        "data": function ( d ) {
                            d.location_id = $('#location_id').val();
                            d.contact_id = $('#contact_id').val();
                            d.status_id = $('#status_id').val();
                            d.is_completed_status = 1;
                            @if(in_array('service_staff' ,$enabled_modules))
                                d.technician = $('#technician').val();
                            @endif
                        }
                    },
                    columnDefs: [{
                        targets: [0, 4],
                        orderable: false,
                        searchable: false
                    }],
                    aaSorting:[[2, 'asc']],
                    columns:[
                        { data: 'action', name: 'action' },
                        { data: 'service_type', name: 'service_type'},
                        {
                            data: 'delivery_date', name: 'delivery_date'
                        },
                        {
                            data: 'job_sheet_no', name: 'job_sheet_no'
                        },
                        {
                            data: 'repair_no', name: 'repair_no'
                        },
                        { data:'status', name: 'rs.name' },
                        @if(in_array('service_staff' ,$enabled_modules))
                            { data: 'technecian', name: 'technecian', searchable: false},
                        @endif
                        { data: 'customer', name : 'contacts.name'},
                        { data: 'location', name: 'bl.name' },
                        { data: 'brand', name: 'b.name' },
                        { data: 'device', name: 'device.name' },
                        { data: 'device_model', name: 'rdm.name' },
                        {
                            data: 'serial_no', name: 'serial_no'
                        },
                        {
                            data: 'estimated_cost', name: 'estimated_cost'
                        },
                        @if(!empty($repair_settings['job_sheet_custom_field_1']))
                            {
                                data: 'custom_field_1', name: 'repair_job_sheets.custom_field_1'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_2']))
                            {
                                data: 'custom_field_2', name: 'repair_job_sheets.custom_field_2'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_3']))
                            {
                                data: 'custom_field_3', name: 'repair_job_sheets.custom_field_3'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_4']))
                            {
                                data: 'custom_field_4', name: 'repair_job_sheets.custom_field_4'
                            },
                        @endif
                        @if(!empty($repair_settings['job_sheet_custom_field_5']))
                            {
                                data: 'custom_field_5', name: 'repair_job_sheets.custom_field_5'
                            },
                        @endif
                        { data: 'added_by', name: 'added_by', searchable: false},
                        { data: 'created_at',
                            name: 'repair_job_sheets.created_at'
                        }
                    ],
                    "fnDrawCallback": function (oSettings) {
                        __currency_convert_recursively($('#completed_job_sheets_table'));
                    }
            });

            $(document).on('click', '#delete_job_sheet', function (e) {
                e.preventDefault();
                var url = $(this).data('href');
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    pending_job_sheets_datatable.ajax.reload();
                                    completed_job_sheets_datatable.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            @if(auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))
                $(document).on('click', '.edit_job_sheet_status', function () {
                    var url = $(this).data('href');
                    $.ajax({
                        method: 'GET',
                        url: url,
                        dataType: 'html',
                        success: function(result) {
                            $('#status_modal').html(result).modal('show');
                        }
                    });
                });
            @endif

            $('#status_modal').on('shown.bs.modal', function (e) {

                //initialize editor
                tinymce.init({
                    selector: 'textarea#email_body',
                });

                $('#send_sms').change(function() {
                    if ($(this). is(":checked")) {
                        $('div.sms_body').fadeIn();
                    } else {
                        $('div.sms_body').fadeOut();
                    }
                });

                $('#send_email').change(function() {
                    if ($(this). is(":checked")) {
                        $('div.email_template').fadeIn();
                    } else {
                        $('div.email_template').fadeOut();
                    }
                });

                if ($('#status_id_modal').length) {
                    ;
                    $("#sms_body").val($("#status_id_modal :selected").data('sms_template'));
                    $("#email_subject").val($("#status_id_modal :selected").data('email_subject'));
                    tinymce.activeEditor.setContent($("#status_id_modal :selected").data('email_body'));  
                }

                $('#status_id_modal').on('change', function() {
                    var sms_template = $(this).find(':selected').data('sms_template');
                    var email_subject = $(this).find(':selected').data('email_subject');
                    var email_body = $(this).find(':selected').data('email_body');

                    $("#sms_body").val(sms_template);
                    $("#email_subject").val(email_subject);
                    tinymce.activeEditor.setContent(email_body);

                    if ($('#status_modal .mark-as-complete-btn').length) {
                        if ($(this).find(':selected').data('is_completed_status') == 1) 
                        {
                            $('#status_modal').find('.mark-as-complete-btn').removeClass('hide');
                            $('#status_modal').find('.mark-as-incomplete-btn').addClass('hide');
                        } else {
                            $('#status_modal').find('.mark-as-complete-btn').addClass('hide');
                            $('#status_modal').find('.mark-as-incomplete-btn').removeClass('hide');
                        }
                    }
                });
            });
            
            $('#status_modal').on('hidden.bs.modal', function(){
                tinymce.remove("textarea#email_body");
            });
            
            $(document).on('click', '.update_status_button', function(){
                $('#status_form_redirect').val($(this).data('href'));
            })
            $(document).on('submit', 'form#update_status_form', function(e){
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
                            $('#status_modal').modal('hide');
                            if (result.msg) {
                                toastr.success(result.msg);
                            }

                            if ($('#status_form_redirect').val()) {
                                window.location = $('#status_form_redirect').val();
                            }
                            pending_job_sheets_datatable.ajax.reload();
                            completed_job_sheets_datatable.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('change', '#location_id, #contact_id, #status_id, #technician',  function() {
                pending_job_sheets_datatable.ajax.reload();
                completed_job_sheets_datatable.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/job-sheet-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
