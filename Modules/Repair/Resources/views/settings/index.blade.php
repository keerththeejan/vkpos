@extends('layouts.app')
@section('title', __('messages.settings'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/repair-settings-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@include('repair::layouts.nav')

<section class="content rs-shell">
    <div class="rs-header" role="banner">
        <div class="rs-header-left">
            <h1><i class="fas fa-tools"></i> @lang('messages.settings')</h1>
            <p class="rs-subtitle">Service center configuration · statuses · devices · job sheet PDF</p>
            <div class="rs-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('repair::lang.repair')</span>
                <span>/</span>
                <span>@lang('messages.settings')</span>
            </div>
        </div>
        <div class="rs-header-actions">
            <div class="rs-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="rs_quick_search" class="form-control" placeholder="Search settings…" aria-label="Search settings" autocomplete="off">
            </div>
            <button type="button" class="rs-btn rs-btn-ghost" id="rs_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <a class="rs-btn" href="{{ action([\Modules\Repair\Http\Controllers\DashboardController::class, 'index']) }}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <button type="button" class="rs-btn rs-btn-primary" id="rs_save_active" title="Save current tab form">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="rs-kpi-grid" aria-label="Settings overview">
        <div class="rs-kpi tone-blue">
            <div class="rs-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="rs-kpi-label">Statuses</span>
            <span class="rs-kpi-value">{{ count($repair_statuses ?? []) }}</span>
            <span class="rs-kpi-hint">Configured repair stages</span>
        </div>
        <div class="rs-kpi tone-teal">
            <div class="rs-kpi-icon"><i class="fas fa-desktop"></i></div>
            <span class="rs-kpi-label">Devices</span>
            <span class="rs-kpi-value">{{ count($devices ?? []) }}</span>
            <span class="rs-kpi-hint">Device categories</span>
        </div>
        <div class="rs-kpi tone-purple">
            <div class="rs-kpi-icon"><i class="fas fa-bolt"></i></div>
            <span class="rs-kpi-label">Brands</span>
            <span class="rs-kpi-value">{{ count($brands ?? []) }}</span>
            <span class="rs-kpi-hint">Linked for models</span>
        </div>
        <div class="rs-kpi tone-green">
            <div class="rs-kpi-icon"><i class="fas fa-cogs"></i></div>
            <span class="rs-kpi-label">Job Prefix</span>
            <span class="rs-kpi-value rs-kpi-text">{{ $repair_settings['job_sheet_prefix'] ?? '—' }}</span>
            <span class="rs-kpi-hint">Job sheet numbering</span>
        </div>
        <div class="rs-kpi tone-orange">
            <div class="rs-kpi-icon"><i class="fas fa-clipboard"></i></div>
            <span class="rs-kpi-label">PDF / Labels</span>
            <span class="rs-kpi-value">Ready</span>
            <span class="rs-kpi-hint">Job sheet print config</span>
        </div>
        <div class="rs-kpi tone-slate">
            <div class="rs-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="rs-kpi-label">Integrations</span>
            <span class="rs-kpi-value">Active</span>
            <span class="rs-kpi-hint">IMEI · Warranty · POS</span>
        </div>
    </div>

    <div class="rs-future-strip" aria-label="Coming soon">
        <span class="rs-chip"><i class="fas fa-robot"></i> AI Assignment</span>
        <span class="rs-chip"><i class="fab fa-whatsapp"></i> WhatsApp API</span>
        <span class="rs-chip"><i class="fas fa-qrcode"></i> QR Tracking</span>
        <span class="rs-chip"><i class="fas fa-signature"></i> Digital Signature</span>
        <span class="rs-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="rs-layout">
        <nav class="rs-side-nav" aria-label="Settings sections">
            <div class="rs-nav-title">Configuration</div>
            <a href="#repair_status_tab" class="rs-nav-item active" data-rs-tab="#repair_status_tab">
                <i class="fas fa-check-circle"></i> @lang('sale.status')
            </a>
            <a href="#repair_device_tab" class="rs-nav-item" data-rs-tab="#repair_device_tab">
                <i class="fas fa-desktop"></i> @lang('repair::lang.devices')
            </a>
            <a href="#repair_device_models_tab" class="rs-nav-item" data-rs-tab="#repair_device_models_tab">
                <i class="fas fa-bolt"></i> @lang('repair::lang.device_models')
            </a>
            <a href="#repair_settings_tab" class="rs-nav-item" data-rs-tab="#repair_settings_tab">
                <i class="fas fa-cogs"></i> @lang('repair::lang.repair_settings')
            </a>
            <a href="#jobsheet_settings_tab" class="rs-nav-item" data-rs-tab="#jobsheet_settings_tab">
                <i class="fas fa-clipboard"></i> @lang('repair::lang.jobsheet_pdf_settings')
            </a>
            <div class="rs-nav-title">Coming soon</div>
            <span class="rs-nav-item muted"><i class="fas fa-bell"></i> Notifications</span>
            <span class="rs-nav-item muted"><i class="fas fa-print"></i> Printing</span>
            <span class="rs-nav-item muted"><i class="fas fa-credit-card"></i> Payments</span>
            <span class="rs-nav-item muted"><i class="fas fa-bolt"></i> Automation</span>
        </nav>

        <div class="rs-main">
            @php
                $cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
            @endphp
            <div class="rs-card rs-tabs-card">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#repair_status_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fas fa-check-circle"></i>
                                @lang('sale.status')
                                @show_tooltip(__('repair::lang.all_js_status_tooltip'))
                            </a>
                        </li>
                        <li>
                            <a href="#repair_device_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fas fa fa-desktop"></i>
                                @lang('repair::lang.devices')
                                @show_tooltip(__('repair::lang.device_tooltip'))
                            </a>
                        </li>
                        <li>
                            <a href="#repair_device_models_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fas fa fa-bolt"></i>
                                @lang('repair::lang.device_models')
                                @show_tooltip(__('repair::lang.device_models_tooltip'))
                            </a>
                        </li>
                        <li>
                            <a href="#repair_settings_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fas fa-cogs"></i>
                                @lang('repair::lang.repair_settings')
                            </a>
                        </li>
                        <li>
                            <a href="#jobsheet_settings_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fas fa-clipboard"></i>
                                @lang('repair::lang.jobsheet_pdf_settings')
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="repair_status_tab">
                            @includeIf('repair::status.index')
                        </div>
                        <!-- Device (Taxonomy)-->
                        <input type="hidden" name="category_type" id="category_type" value="device">
                        <div class="tab-pane taxonomy_body" id="repair_device_tab">
                        </div>
                        <!-- /Device (Taxonomy)-->
                        <div class="tab-pane" id="repair_device_models_tab">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('brand_id',  __('product.brand') . ':') !!}
                                        {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('device_id',  __('repair::lang.device') . ':') !!}
                                        {!! Form::select('device_id', $devices, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                    </div>
                                </div>
                            </div>
                            @includeIf('repair::device_model.index')
                        </div>
                        <div class="tab-pane" id="repair_settings_tab">
                            @includeIf('repair::settings.partials.repair_settings_tab')
                        </div>
                        <div class="tab-pane" id="jobsheet_settings_tab">
                            @includeIf('repair::settings.partials.jobsheet_settings_tab')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="rs-preview">
            <div class="rs-card rs-preview-card">
                <div class="rs-card-head">
                    <div>
                        <h3>Live Preview</h3>
                        <p>Current configuration snapshot</p>
                    </div>
                </div>
                <div class="rs-card-body">
                    <div class="rs-preview-meta">
                        <div>
                            <span class="k">Active Tab</span>
                            <span class="v" id="rs_preview_tab">Statuses</span>
                        </div>
                        <div>
                            <span class="k">Job Sheet Prefix</span>
                            <span class="v">{{ $repair_settings['job_sheet_prefix'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="k">Default Product</span>
                            <span class="v">{{ $default_product_name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="k">Statuses</span>
                            <span class="v">{{ count($repair_statuses ?? []) }} configured</span>
                        </div>
                    </div>
                    <h5 class="rs-preview-section-title">Status Flow</h5>
                    <div class="rs-status-flow">
                        @forelse(($repair_statuses ?? []) as $st)
                            <div class="rs-status-pill" style="--rs-st-color: {{ $st->color ?? '#2563EB' }};">
                                <span class="dot"></span>{{ $st->name }}
                            </div>
                        @empty
                            <p class="rs-muted">No statuses yet — add them in the Statuses tab.</p>
                        @endforelse
                    </div>
                    <h5 class="rs-preview-section-title">Quick Links</h5>
                    <div class="rs-quick-links">
                        <a href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']) }}"><i class="fas fa-clipboard-list"></i> Job Sheets</a>
                        <a href="{{ action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']) }}"><i class="fas fa-file-invoice"></i> Repair Orders</a>
                        <a href="{{ url('/warranties') }}"><i class="fas fa-shield-alt"></i> Warranties</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if ( target == '#repair_settings_tab') {
                //Repair Settings Tab Code
                $('#search_product').autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: '/purchases/get_products?check_enable_stock=false',
                            dataType: 'json',
                            data: {
                                term: request.term,
                            },
                            success: function(data) {
                                response(
                                    $.map(data, function(v, i) {
                                        if (v.variation_id) {
                                            return { label: v.text, value: v.variation_id };
                                        }
                                        return false;
                                    })
                                );
                            },
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        $('#default_product')
                            .val(ui.item.value);
                        event.preventDefault();
                        $('#selected_default_product').text(ui.item.label);
                        $(this).val(ui.item.label);
                    },
                    focus: function(event, ui) {
                        event.preventDefault();
                        $(this).val(ui.item.label);
                    },
                });

                var data = [{
                  id: "",
                  text: '@lang("messages.please_select")',
                  html: '@lang("messages.please_select")',
                }, 
                @foreach($repair_statuses as $repair_status)
                    {
                    id: {{$repair_status->id}},
                    @if(!empty($repair_status->color))
                        text: '<i class="fa fa-circle" aria-hidden="true" style="color: {{$repair_status->color}};"></i> {{$repair_status->name}}',
                        title: '{{$repair_status->name}}'
                    @else
                        text: "{{$repair_status->name}}"
                    @endif
                    },
                @endforeach
                ];

                $("select#repair_status_id").select2({
                  data: data,
                  escapeMarkup: function(markup) {
                    return markup;
                  }
                });

                @if(!empty($repair_settings['default_status']))
                    $("select#repair_status_id").val({{$repair_settings['default_status']}}).change();
                @endif

                if ($('#repair_tc_condition').length) {
                    tinymce.init({
                        selector: 'textarea#repair_tc_condition',
                    });
                }
            }
        });
        //Repair Status Tab Code
        var status_table = $('#status_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{action([\Modules\Repair\Http\Controllers\RepairStatusController::class, 'index'])}}",
                aaSorting: [[2, 'desc']],
                columnDefs: [ {
                    "targets": 3,
                    "orderable": false,
                    "searchable": false
                } ]
            });

        $(document).on('submit', 'form#status_form', function(e){
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success: function(result){
                    if(result.success == true){
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        status_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
        $(document).on('shown.bs.modal', '.view_modal', function() {
            $('input#color').colorpicker({format: 'hex'});
        })
        //Repair Device Model Code
        model_datatable = $("#model_table").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "/repair/device-models",
                        data:function(d) {
                            d.brand_id = $("#brand_id").val();
                            d.device_id = $("#device_id").val();
                        }
                    },
                    columnDefs: [
                        {
                            targets: [0, 2],
                            orderable: false,
                            searchable: false,
                        },
                    ],
                    aaSorting: [[1, 'desc']],
                    columns: [
                        { data: 'action', name: 'action' },
                        { data: 'name', name: 'name' },
                        { data: 'repair_checklist', name: 'repair_checklist' },
                        { data: 'device_id', name: 'device_id' },
                        { data: 'brand_id', name: 'brand_id' },
                    ]
            });

        $(document).on('change', "#brand_id, #device_id", function(){
            model_datatable.ajax.reload();
        });

        $(document).on('click', '#add_device_model', function () {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#device_model_modal').html(result).modal('show');
                }
            });
        });

        $(document).on('click', '.edit_device_model', function () {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#device_model_modal').html(result).modal('show');
                }
            });
        });

        $('#device_model_modal').on('show.bs.modal', function (event) {
            $('form#device_model').validate();
            $("form#device_model .select2").select2();
        });

        $(document).on('submit', 'form#device_model', function(e){
            e.preventDefault();
            var url = $('form#device_model').attr('action');
            var method = $('form#device_model').attr('method');
            var data = $('form#device_model').serialize();
            $.ajax({
                method: method,
                dataType: "json",
                url: url,
                data:data,
                success: function(result){
                    if (result.success) {
                        $('#device_model_modal').modal("hide");
                        toastr.success(result.msg);
                        model_datatable.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('click', '#delete_a_model', function(e) {
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
                                model_datatable.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@includeIf('taxonomy.taxonomies_js')
<script src="{{ asset('js/repair-settings-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
