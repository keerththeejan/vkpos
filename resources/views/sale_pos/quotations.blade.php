@extends('layouts.app')
@section('title', __( 'lang_v1.quotation'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/quotations-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print qt-shell">
    {{-- Sticky Header --}}
    <div class="qt-header" role="banner">
        <div class="qt-header-left">
            <h1>@lang('lang_v1.list_quotations')</h1>
            <p class="qt-subtitle">Create · edit · convert · print sales quotations</p>
            <div class="qt-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
                <span>/</span>
                <span>@lang('lang_v1.quotation')</span>
            </div>
        </div>
        <div class="qt-header-actions">
            <div class="qt-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="qt_quick_search" class="form-control" placeholder="Search quotations…" aria-label="Search quotations" autocomplete="off">
            </div>
            <button type="button" class="qt-btn qt-btn-ghost" id="qt_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="qt-btn qt-btn-ghost" id="qt_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="qt-btn qt-btn-ghost" id="qt_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('direct_sell.access')
                <a class="qt-btn qt-btn-success" href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                    <i class="fas fa-file-invoice"></i> @lang('sale.sells')
                </a>
            @endcan
            <a class="qt-btn qt-btn-primary" href="{{action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'quotation'])}}">
                <i class="fas fa-plus"></i> @lang('lang_v1.add_quotation')
            </a>
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="qt-kpi-grid" aria-label="Quotation KPIs">
        <div class="qt-kpi tone-blue">
            <div class="qt-kpi-icon"><i class="fas fa-file-invoice"></i></div>
            <span class="qt-kpi-label">Total Quotations</span>
            <span class="qt-kpi-value" id="qt_kpi_total">—</span>
            <span class="qt-kpi-hint">All quotation records</span>
        </div>
        <div class="qt-kpi tone-teal">
            <div class="qt-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="qt-kpi-label">Filtered</span>
            <span class="qt-kpi-value" id="qt_kpi_filtered">—</span>
            <span class="qt-kpi-hint">Matching filters</span>
        </div>
        <div class="qt-kpi tone-orange">
            <div class="qt-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="qt-kpi-label">On This Page</span>
            <span class="qt-kpi-value" id="qt_kpi_page">—</span>
            <span class="qt-kpi-hint">Visible rows</span>
        </div>
        <div class="qt-kpi tone-green">
            <div class="qt-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="qt-kpi-label">Customers (page)</span>
            <span class="qt-kpi-value" id="qt_kpi_customers">—</span>
            <span class="qt-kpi-hint">Unique on page</span>
        </div>
        <div class="qt-kpi tone-violet">
            <div class="qt-kpi-icon"><i class="fas fa-boxes"></i></div>
            <span class="qt-kpi-label">Items (page)</span>
            <span class="qt-kpi-value" id="qt_kpi_items">—</span>
            <span class="qt-kpi-hint">Sum of line items</span>
        </div>
        <div class="qt-kpi tone-amber">
            <div class="qt-kpi-icon"><i class="fas fa-stamp"></i></div>
            <span class="qt-kpi-label">Status</span>
            <span class="qt-kpi-value">Quote</span>
            <span class="qt-kpi-hint">Quotation badge</span>
        </div>
        <div class="qt-kpi tone-slate">
            <div class="qt-kpi-icon"><i class="fas fa-exchange-alt"></i></div>
            <span class="qt-kpi-label">Convert to Sale</span>
            <span class="qt-kpi-value">—</span>
            <span class="qt-kpi-hint">Use row action menu</span>
        </div>
        <div class="qt-kpi tone-red">
            <div class="qt-kpi-icon"><i class="fas fa-chart-pie"></i></div>
            <span class="qt-kpi-label">Conversion Rate</span>
            <span class="qt-kpi-value">—</span>
            <span class="qt-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="qt-future-strip" aria-label="Coming soon">
        <span class="qt-chip"><i class="fas fa-check-double"></i> Multi-Level Approval</span>
        <span class="qt-chip"><i class="fas fa-file-signature"></i> E-Signature</span>
        <span class="qt-chip"><i class="fas fa-globe"></i> Online Accept</span>
        <span class="qt-chip"><i class="fas fa-whatsapp"></i> WhatsApp Approve</span>
        <span class="qt-chip"><i class="fas fa-robot"></i> AI Pricing</span>
        <span class="qt-chip"><i class="fas fa-link"></i> Payment Link</span>
        <span class="qt-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="qt-main-grid">
        <div class="qt-main-col">
            {{-- Filters: preserve IDs used by quotation DataTable ajax --}}
            <div class="qt-card qt-filter-card">
                <div class="qt-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the quotation DataTable AJAX</p>
                    </div>
                </div>
                <div class="qt-card-body">
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
                    <div class="qt-filter-actions">
                        <button type="button" class="qt-btn qt-btn-primary" id="qt_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="qt-btn" id="qt_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="qt-card">
                <div class="qt-card-head">
                    <div>
                        <h3>@lang('lang_v1.list_quotations')</h3>
                        <p>Server-side DataTable · existing convert / edit / print / delete actions</p>
                    </div>
                    <a class="qt-btn qt-btn-primary" href="{{action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'quotation'])}}">
                        <i class="fas fa-plus"></i> @lang('lang_v1.add_quotation')
                    </a>
                </div>
                <div class="qt-card-body">
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

        <aside class="qt-side" aria-label="Quotation details">
            <div class="qt-preview-card">
                <h3>Quotation Details</h3>
                <p class="qt-preview-empty" id="qt_preview_empty">Select a quotation row to preview customer, location, and quick actions. Edit / convert / print stay in the action menu.</p>
                <div id="qt_preview_content" style="display:none;">
                    <div class="qt-preview-rows">
                        <div><span>Quotation No</span><strong id="qt_preview_invoice">—</strong></div>
                        <div><span>Status</span><strong><span class="qt-badge" id="qt_preview_status">Quotation</span></strong></div>
                        <div><span>Date</span><strong id="qt_preview_date">—</strong></div>
                        <div><span>Customer</span><strong id="qt_preview_customer">—</strong></div>
                        <div><span>Phone</span><strong id="qt_preview_mobile">—</strong></div>
                        <div><span>Location</span><strong id="qt_preview_location">—</strong></div>
                        <div><span>Items</span><strong id="qt_preview_items">—</strong></div>
                        <div><span>Created By</span><strong id="qt_preview_by">—</strong></div>
                    </div>
                    <div class="qt-preview-actions" id="qt_preview_actions"></div>
                    <div class="qt-imei-note">
                        <strong>IMEI / Serial:</strong> Available when editing or converting the quotation via the existing product-row modal. Search by IMEI on the edit screen.
                    </div>
                </div>
            </div>

            <div class="qt-summary-card">
                <h3>Page Summary</h3>
                <div class="qt-summary-rows">
                    <div><span>Filtered quotations</span><strong id="qt_sum_filtered">—</strong></div>
                    <div><span>Customers (page)</span><strong id="qt_sum_customers">—</strong></div>
                    <div><span>Items (page)</span><strong id="qt_sum_items">—</strong></div>
                </div>
            </div>
        </aside>
    </div>
</section>
<!-- /.content -->
@stop

@section('javascript')
<script src="{{ asset('js/quotations-premium-ui.js?v=' . $asset_v) }}"></script>
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
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": '/sells/draft-dt?is_quotation=1',
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
