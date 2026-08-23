@extends('layouts.app')
@section('title', __( 'sale.drafts'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/drafts-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print df-shell">
    {{-- Sticky Header --}}
    <div class="df-header" role="banner">
        <div class="df-header-left">
            <h1>@lang('sale.drafts')</h1>
            <p class="df-subtitle">Resume · convert · edit · print draft invoices</p>
            <div class="df-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
                <span>/</span>
                <span>@lang('sale.drafts')</span>
            </div>
        </div>
        <div class="df-header-actions">
            <div class="df-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="df_quick_search" class="form-control" placeholder="Search drafts…" aria-label="Search drafts" autocomplete="off">
            </div>
            <button type="button" class="df-btn df-btn-ghost" id="df_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="df-btn df-btn-ghost" id="df_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="df-btn df-btn-ghost" id="df_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('sell.create')
                <a class="df-btn df-btn-success" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}">
                    <i class="fas fa-th"></i> POS
                </a>
            @endcan
            <a class="df-btn df-btn-primary" href="{{action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'draft'])}}">
                <i class="fas fa-plus"></i> @lang('lang_v1.add_draft')
            </a>
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="df-kpi-grid" aria-label="Draft KPIs">
        <div class="df-kpi tone-blue">
            <div class="df-kpi-icon"><i class="fas fa-file-alt"></i></div>
            <span class="df-kpi-label">Total Drafts</span>
            <span class="df-kpi-value" id="df_kpi_total">—</span>
            <span class="df-kpi-hint">All draft records</span>
        </div>
        <div class="df-kpi tone-teal">
            <div class="df-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="df-kpi-label">Filtered</span>
            <span class="df-kpi-value" id="df_kpi_filtered">—</span>
            <span class="df-kpi-hint">Matching filters</span>
        </div>
        <div class="df-kpi tone-orange">
            <div class="df-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="df-kpi-label">On This Page</span>
            <span class="df-kpi-value" id="df_kpi_page">—</span>
            <span class="df-kpi-hint">Visible rows</span>
        </div>
        <div class="df-kpi tone-green">
            <div class="df-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="df-kpi-label">Customers (page)</span>
            <span class="df-kpi-value" id="df_kpi_customers">—</span>
            <span class="df-kpi-hint">Unique on page</span>
        </div>
        <div class="df-kpi tone-violet">
            <div class="df-kpi-icon"><i class="fas fa-boxes"></i></div>
            <span class="df-kpi-label">Items (page)</span>
            <span class="df-kpi-value" id="df_kpi_items">—</span>
            <span class="df-kpi-hint">Sum of line items</span>
        </div>
        <div class="df-kpi tone-amber">
            <div class="df-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <span class="df-kpi-label">Pending</span>
            <span class="df-kpi-value">Draft</span>
            <span class="df-kpi-hint">Status badge</span>
        </div>
        <div class="df-kpi tone-slate">
            <div class="df-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="df-kpi-label">IMEI Products</span>
            <span class="df-kpi-value">—</span>
            <span class="df-kpi-hint">Shown in draft edit / row modal</span>
        </div>
        <div class="df-kpi tone-red">
            <div class="df-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <span class="df-kpi-label">Draft Value</span>
            <span class="df-kpi-value">—</span>
            <span class="df-kpi-hint">Open draft for totals</span>
        </div>
    </div>

    <div class="df-future-strip" aria-label="Coming soon">
        <span class="df-chip"><i class="fas fa-check-double"></i> Draft Approval</span>
        <span class="df-chip"><i class="fas fa-file-signature"></i> Customer Accept</span>
        <span class="df-chip"><i class="fas fa-whatsapp"></i> WhatsApp Approve</span>
        <span class="df-chip"><i class="fas fa-robot"></i> AI Suggestions</span>
        <span class="df-chip"><i class="fas fa-link"></i> Payment Link</span>
        <span class="df-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="df-main-grid">
        <div class="df-main-col">
            {{-- Filters: preserve IDs used by draft DataTable ajax --}}
            <div class="df-card df-filter-card">
                <div class="df-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the draft DataTable AJAX</p>
                    </div>
                </div>
                <div class="df-card-body">
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
                    </div>
                    <div class="df-filter-actions">
                        <button type="button" class="df-btn df-btn-primary" id="df_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="df-btn" id="df_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="df-card">
                <div class="df-card-head">
                    <div>
                        <h3>@lang('sale.drafts')</h3>
                        <p>Server-side DataTable · existing convert / edit / print / delete actions</p>
                    </div>
                    <a class="df-btn df-btn-primary" href="{{action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'draft'])}}">
                        <i class="fas fa-plus"></i> @lang('lang_v1.add_draft')
                    </a>
                </div>
                <div class="df-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped ajax_view" id="sell_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('sale.customer_name')</th>
                                    <th>@lang('lang_v1.contact_no')</th>
                                    <th>@lang('sale.location')</th>
                                    <th>@lang('lang_v1.total_items')</th>
                                    <th>@lang('lang_v1.added_by')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="df-side" aria-label="Draft details">
            <div class="df-preview-card">
                <h3>Draft Details</h3>
                <p class="df-preview-empty" id="df_preview_empty">Select a draft row to preview customer, location, and quick actions. Edit / convert / print stay in the action menu.</p>
                <div id="df_preview_content" style="display:none;">
                    <div class="df-preview-rows">
                        <div><span>Draft No</span><strong id="df_preview_invoice">—</strong></div>
                        <div><span>Status</span><strong><span class="df-badge" id="df_preview_status">Draft</span></strong></div>
                        <div><span>Date</span><strong id="df_preview_date">—</strong></div>
                        <div><span>Customer</span><strong id="df_preview_customer">—</strong></div>
                        <div><span>Phone</span><strong id="df_preview_mobile">—</strong></div>
                        <div><span>Location</span><strong id="df_preview_location">—</strong></div>
                        <div><span>Items</span><strong id="df_preview_items">—</strong></div>
                        <div><span>Created By</span><strong id="df_preview_by">—</strong></div>
                    </div>
                    <div class="df-preview-actions" id="df_preview_actions"></div>
                    <div class="df-imei-note">
                        <strong>IMEI / Serial:</strong> Available on draft edit / convert screens via existing product-row modal. Search drafts by IMEI using product search when editing.
                    </div>
                </div>
            </div>

            <div class="df-summary-card">
                <h3>Page Summary</h3>
                <div class="df-summary-rows">
                    <div><span>Filtered drafts</span><strong id="df_sum_filtered">—</strong></div>
                    <div><span>Customers (page)</span><strong id="df_sum_customers">—</strong></div>
                    <div><span>Items (page)</span><strong id="df_sum_items">—</strong></div>
                </div>
            </div>
        </aside>
    </div>
</section>
<!-- /.content -->
@stop

@section('javascript')
<script src="{{ asset('js/drafts-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
$(document).ready( function(){
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
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": '/sells/draft-dt?is_quotation=0',
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

                if($('#created_by').length) {
                    d.created_by = $('#created_by').val();
                }
            }
        },
        columnDefs: [ {
            "targets": 7,
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'conatct_name', name: 'conatct_name'},
            { data: 'mobile', name: 'contacts.mobile'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'total_items', name: 'total_items', "searchable": false},
            { data: 'added_by', name: 'added_by'},
            { data: 'action', name: 'action'}
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#purchase_table'));
        }
    });
    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
        sell_table.ajax.reload();
    });

    $(document).on('click', 'a.convert-to-proforma', function(e){
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(confirm => {
            if (confirm) {
                var url = $(this).attr('href');
                $.ajax({
                    method: 'GET',
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            sell_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});
</script>
	
@endsection
