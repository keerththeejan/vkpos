@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell-return-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print sr-shell">
    {{-- Sticky Header --}}
    <div class="sr-header" role="banner">
        <div class="sr-header-left">
            <h1>@lang('lang_v1.sell_return')</h1>
            <p class="sr-subtitle">Returns · refunds · exchanges · IMEI restore</p>
            <div class="sr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
                <span>/</span>
                <span>@lang('lang_v1.sell_return')</span>
            </div>
        </div>
        <div class="sr-header-actions">
            <div class="sr-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="sr_quick_search" class="form-control" placeholder="Search returns…" aria-label="Search returns" autocomplete="off">
            </div>
            <div class="sr-invoice-lookup" title="Start return from invoice (same as POS)">
                <input type="text" id="sr_return_invoice_no" class="form-control" placeholder="@lang('sale.invoice_no')" aria-label="@lang('sale.invoice_no')" autocomplete="off">
                <button type="button" class="sr-btn sr-btn-danger" id="sr_send_return"><i class="fas fa-undo"></i> Return</button>
            </div>
            <button type="button" class="sr-btn sr-btn-ghost" id="sr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="sr-btn sr-btn-ghost" id="sr_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="sr-btn sr-btn-ghost" id="sr_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

    {{-- KPI cards from live DataTable / footer (UI only) --}}
    <div class="sr-kpi-grid" aria-label="Sales return KPIs">
        <div class="sr-kpi tone-red">
            <div class="sr-kpi-icon"><i class="fas fa-undo"></i></div>
            <span class="sr-kpi-label">Total Returns</span>
            <span class="sr-kpi-value" id="sr_kpi_total">—</span>
            <span class="sr-kpi-hint">All return records</span>
        </div>
        <div class="sr-kpi tone-teal">
            <div class="sr-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="sr-kpi-label">Filtered</span>
            <span class="sr-kpi-value" id="sr_kpi_filtered">—</span>
            <span class="sr-kpi-hint">Matching filters</span>
        </div>
        <div class="sr-kpi tone-orange">
            <div class="sr-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
            <span class="sr-kpi-label">Page Refund Total</span>
            <span class="sr-kpi-value sr-kpi-text" id="sr_kpi_refund">—</span>
            <span class="sr-kpi-hint">From table footer</span>
        </div>
        <div class="sr-kpi tone-blue">
            <div class="sr-kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <span class="sr-kpi-label">Payment Due</span>
            <span class="sr-kpi-value sr-kpi-text" id="sr_kpi_due">—</span>
            <span class="sr-kpi-hint">Refund due on page</span>
        </div>
        <div class="sr-kpi tone-green">
            <div class="sr-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="sr-kpi-label">Customers (page)</span>
            <span class="sr-kpi-value" id="sr_kpi_customers">—</span>
            <span class="sr-kpi-hint">Unique on page</span>
        </div>
        <div class="sr-kpi tone-violet">
            <div class="sr-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="sr-kpi-label">IMEI Returns</span>
            <span class="sr-kpi-value">—</span>
            <span class="sr-kpi-hint">Handled on return form</span>
        </div>
        <div class="sr-kpi tone-slate">
            <div class="sr-kpi-icon"><i class="fas fa-exchange-alt"></i></div>
            <span class="sr-kpi-label">Exchanges</span>
            <span class="sr-kpi-value">—</span>
            <span class="sr-kpi-hint">UI placeholder</span>
        </div>
        <div class="sr-kpi tone-orange">
            <div class="sr-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <span class="sr-kpi-label">Return Rate</span>
            <span class="sr-kpi-value">—</span>
            <span class="sr-kpi-hint">UI placeholder</span>
        </div>
        <div class="sr-kpi tone-blue">
            <div class="sr-kpi-icon"><i class="fas fa-boxes"></i></div>
            <span class="sr-kpi-label">Top Returned</span>
            <span class="sr-kpi-value">—</span>
            <span class="sr-kpi-hint">UI placeholder</span>
        </div>
        <div class="sr-kpi tone-red">
            <div class="sr-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="sr-kpi-label">Warranty Claims</span>
            <span class="sr-kpi-value">—</span>
            <span class="sr-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="sr-future-strip" aria-label="Coming soon">
        <span class="sr-chip"><i class="fas fa-check-double"></i> Return Approval</span>
        <span class="sr-chip"><i class="fas fa-qrcode"></i> QR Return</span>
        <span class="sr-chip"><i class="fas fa-wallet"></i> Store Credit</span>
        <span class="sr-chip"><i class="fas fa-robot"></i> AI Fraud Check</span>
        <span class="sr-chip"><i class="fas fa-tools"></i> Repair Link</span>
        <span class="sr-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="sr-main-grid">
        <div class="sr-main-col">
            {{-- Filters: preserve IDs used by sell_return DataTable ajax --}}
            <div class="sr-card sr-filter-card">
                <div class="sr-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the sales return DataTable AJAX</p>
                    </div>
                </div>
                <div class="sr-card-body">
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
                        @can('access_sell_return')
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('created_by',  __('report.user') . ':') !!}
                                {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                        @endcan
                    </div>
                    <div class="sr-filter-actions">
                        <button type="button" class="sr-btn sr-btn-primary" id="sr_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="sr-btn" id="sr_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="sr-card">
                <div class="sr-card-head">
                    <div>
                        <h3>@lang('lang_v1.sell_return')</h3>
                        <p>Server-side DataTable · existing view / print / payment / delete actions</p>
                    </div>
                </div>
                <div class="sr-card-body">
                    @include('sell_return.partials.sell_return_list')
                </div>
            </div>
        </div>

        <aside class="sr-side" aria-label="Return details">
            <div class="sr-preview-card">
                <h3>Return Details</h3>
                <p class="sr-preview-empty" id="sr_preview_empty">Select a return row to preview invoice, customer, refund totals, and quick actions.</p>
                <div id="sr_preview_content" style="display:none;">
                    <div class="sr-preview-rows">
                        <div><span>Return No</span><strong id="sr_preview_invoice">—</strong></div>
                        <div><span>Status</span><strong><span class="sr-badge" id="sr_preview_status">Return</span></strong></div>
                        <div><span>Parent Sale</span><strong id="sr_preview_parent">—</strong></div>
                        <div><span>Date</span><strong id="sr_preview_date">—</strong></div>
                        <div><span>Customer</span><strong id="sr_preview_customer">—</strong></div>
                        <div><span>Location</span><strong id="sr_preview_location">—</strong></div>
                        <div><span>Payment</span><strong id="sr_preview_payment">—</strong></div>
                        <div><span>Refund Total</span><strong id="sr_preview_total">—</strong></div>
                        <div><span>Due</span><strong id="sr_preview_due">—</strong></div>
                    </div>
                    <div class="sr-preview-actions" id="sr_preview_actions"></div>
                    <div class="sr-imei-note">
                        <strong>IMEI / Serial:</strong> Validated on the return form against the original invoice. Only sold IMEIs can be returned — existing rules unchanged.
                    </div>
                </div>
            </div>

            <div class="sr-summary-card">
                <h3>Refund Summary</h3>
                <div class="sr-summary-rows">
                    <div><span>Filtered returns</span><strong id="sr_sum_filtered">—</strong></div>
                    <div><span>Customers (page)</span><strong id="sr_sum_customers">—</strong></div>
                    <div><span>Page refund total</span><strong id="sr_sum_refund">—</strong></div>
                    <div><span>Payment due</span><strong id="sr_sum_due">—</strong></div>
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
</section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/sell-return-premium-ui.js?v=' . $asset_v) }}"></script>
<script>
    $(document).ready(function(){
        $('#sell_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                sell_return_table.ajax.reload();
            }
        );
        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#sell_list_filter_date_range').val('');
            sell_return_table.ajax.reload();
        });

        sell_return_table = $('#sell_return_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader:false,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": "/sell-return",
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
                "targets": [7, 8],
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'transaction_date', name: 'transaction_date'  },
                { data: 'invoice_no', name: 'invoice_no'},
                { data: 'parent_sale', name: 'T1.invoice_no'},
                { data: 'name', name: 'contacts.name'},
                { data: 'business_location', name: 'bl.name'},
                { data: 'payment_status', name: 'payment_status'},
                { data: 'final_total', name: 'final_total'},
                { data: 'payment_due', name: 'payment_due'},
                { data: 'action', name: 'action'}
            ],
            "fnDrawCallback": function (oSettings) {
                var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                $('#footer_sell_return_total').text(total_sell);
                
                $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'), 'payment-status-label'));

                var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                $('#footer_total_due_sr').text(total_due);

                __currency_convert_recursively($('#sell_return_table'));
            },
            createdRow: function( row, data, dataIndex ) {
                $( row ).find('td:eq(2)').attr('class', 'clickable_td');
            }
        });
        $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
            sell_return_table.ajax.reload();
        });
    })

    $(document).on('click', 'a.delete_sell_return', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            sell_return_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>
	
@endsection
