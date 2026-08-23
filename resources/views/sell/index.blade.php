@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/sells-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp

<section class="content no-print sl-shell">
    {{-- Sticky Header --}}
    <div class="sl-header" role="banner">
        <div class="sl-header-left">
            <h1>@lang('sale.sells')</h1>
            <p class="sl-subtitle">Invoices · payments · customers · shipping</p>
            <div class="sl-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
            </div>
        </div>
        <div class="sl-header-actions">
            <div class="sl-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="sl_quick_search" class="form-control" placeholder="Search invoices…" aria-label="Search sales" autocomplete="off">
            </div>
            <button type="button" class="sl-btn sl-btn-ghost" id="sl_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="sl-btn sl-btn-ghost" id="sl_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="sl-btn sl-btn-ghost" id="sl_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('sell.create')
                <a class="sl-btn sl-btn-success" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}">
                    <i class="fas fa-th"></i> POS
                </a>
            @endcan
            @can('direct_sell.access')
                <a class="sl-btn sl-btn-primary" href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTable / footer --}}
    <div class="sl-kpi-grid" aria-label="Sales KPIs">
        <div class="sl-kpi tone-blue">
            <div class="sl-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <span class="sl-kpi-label">Total Invoices</span>
            <span class="sl-kpi-value" id="sl_kpi_total">—</span>
            <span class="sl-kpi-hint">All sales records</span>
        </div>
        <div class="sl-kpi tone-teal">
            <div class="sl-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="sl-kpi-label">Filtered</span>
            <span class="sl-kpi-value" id="sl_kpi_filtered">—</span>
            <span class="sl-kpi-hint">Matching filters</span>
        </div>
        <div class="sl-kpi tone-green">
            <div class="sl-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="sl-kpi-label">Page Paid</span>
            <span class="sl-kpi-value sl-kpi-text" id="sl_kpi_paid">—</span>
            <span class="sl-kpi-hint">From table footer</span>
        </div>
        <div class="sl-kpi tone-orange">
            <div class="sl-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
            <span class="sl-kpi-label">Page Sales</span>
            <span class="sl-kpi-value sl-kpi-text" id="sl_kpi_page_total">—</span>
            <span class="sl-kpi-hint">Grand total on page</span>
        </div>
        <div class="sl-kpi tone-red">
            <div class="sl-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="sl-kpi-label">Balance Due</span>
            <span class="sl-kpi-value sl-kpi-text" id="sl_kpi_due">—</span>
            <span class="sl-kpi-hint">Sell due on page</span>
        </div>
        <div class="sl-kpi tone-slate">
            <div class="sl-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="sl-kpi-label">Customers (page)</span>
            <span class="sl-kpi-value" id="sl_kpi_customers">—</span>
            <span class="sl-kpi-hint">Unique on current page</span>
        </div>
    </div>

    <div class="sl-future-strip" aria-label="Coming soon">
        <span class="sl-chip"><i class="fas fa-qrcode"></i> QR Invoice</span>
        <span class="sl-chip"><i class="fas fa-file-signature"></i> E-Invoice</span>
        <span class="sl-chip"><i class="fas fa-credit-card"></i> Online Payment</span>
        <span class="sl-chip"><i class="fas fa-robot"></i> AI Insights</span>
        <span class="sl-chip"><i class="fas fa-truck"></i> Delivery Track</span>
        <span class="sl-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="sl-main-grid">
        <div class="sl-main-col">
            {{-- Filters: preserve all IDs used by sell DataTable ajax --}}
            <div class="sl-card sl-filter-card">
                <div class="sl-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the sales DataTable AJAX</p>
                    </div>
                </div>
                <div class="sl-card-body">
                    <div class="row">
                        @include('sell.partials.sell_list_filters')
                        @if ($payment_types)
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                                    {!! Form::select('payment_method', $payment_types, null, [
                                        'class' => 'form-control select2',
                                        'style' => 'width:100%',
                                        'placeholder' => __('lang_v1.all'),
                                    ]) !!}
                                </div>
                            </div>
                        @endif

                        @if (!empty($sources))
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('sell_list_filter_source', __('lang_v1.sources') . ':') !!}
                                    {!! Form::select('sell_list_filter_source', $sources, null, [
                                        'class' => 'form-control select2',
                                        'style' => 'width:100%',
                                        'placeholder' => __('lang_v1.all'),
                                    ]) !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="sl-filter-actions">
                        <button type="button" class="sl-btn sl-btn-primary" id="sl_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="sl-btn" id="sl_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                        <button type="button" class="sl-btn" id="sl_toggle_adv"><i class="fas fa-sliders-h"></i> Advanced</button>
                    </div>

                    <div class="sl-adv-filters" id="sl_adv_filters" style="display:none;" aria-label="Advanced filters placeholder">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Product / SKU</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>IMEI / Serial</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Brand / Category</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Order Status</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
                        </div>
                        <p class="text-muted" style="margin:0;font-size:12px;">Advanced filters are UI placeholders only — existing filters remain active.</p>
                    </div>
                </div>
            </div>

            <div class="sl-card">
                <div class="sl-card-head sl-card-head-row">
                    <div>
                        <h3>@lang('lang_v1.all_sales')</h3>
                        <p>Existing DataTable &amp; AJAX logic unchanged</p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @can('sell.create')
                            <a class="sl-btn" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}">
                                <i class="fas fa-th"></i> POS
                            </a>
                        @endcan
                        @can('direct_sell.access')
                            <a class="sl-btn sl-btn-primary" href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                                <i class="fas fa-plus"></i> @lang('messages.add')
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="sl-card-body">
                    @if (auth()->user()->can('direct_sell.view') ||
                            auth()->user()->can('view_own_sell_only') ||
                            auth()->user()->can('view_commission_agent_sell'))
                        <div class="table-responsive sl-table-wrap">
                            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('lang_v1.payment_method')</th>
                                        <th>@lang('sale.total_amount')</th>
                                        <th>@lang('sale.total_paid')</th>
                                        <th>@lang('lang_v1.sell_due')</th>
                                        <th>@lang('lang_v1.sell_return_due')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        <th>@lang('lang_v1.total_items')</th>
                                        <th>@lang('lang_v1.types_of_service')</th>
                                        <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1') }}
                                        </th>
                                        <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_2'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_3'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_4'] ?? '' }}</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                        <th>@lang('sale.sell_note')</th>
                                        <th>@lang('sale.staff_note')</th>
                                        <th>@lang('sale.shipping_details')</th>
                                        <th>@lang('restaurant.table')</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                        <td class="footer_payment_status_count"></td>
                                        <td class="payment_method_count"></td>
                                        <td class="footer_sale_total"></td>
                                        <td class="footer_total_paid"></td>
                                        <td class="footer_total_remaining"></td>
                                        <td class="footer_total_sell_return_due"></td>
                                        <td colspan="2"></td>
                                        <td class="service_type_count"></td>
                                        <td colspan="7"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <aside class="sl-side-col">
            <div class="sl-card" id="sl_preview_card" aria-live="polite">
                <div class="sl-card-head">
                    <div>
                        <h3>Invoice Details</h3>
                        <p>Select a row to inspect</p>
                    </div>
                </div>
                <div class="sl-card-body">
                    <div class="sl-preview-empty" id="sl_preview_empty">
                        <i class="fas fa-file-invoice"></i>
                        <p>Click any invoice row to preview customer, totals, payment, and actions.</p>
                    </div>
                    <div class="sl-preview-content" id="sl_preview_content" style="display:none;">
                        <div class="sl-preview-badge"><i class="fas fa-receipt"></i></div>
                        <div class="sl-preview-title-row">
                            <h4 id="sl_preview_invoice">—</h4>
                            <span class="sl-pill" id="sl_preview_payment">—</span>
                        </div>
                        <div class="sl-preview-rows">
                            <div><span>Date</span><strong id="sl_preview_date">—</strong></div>
                            <div><span>Customer</span><strong id="sl_preview_customer">—</strong></div>
                            <div><span>Mobile</span><strong id="sl_preview_mobile">—</strong></div>
                            <div><span>Location</span><strong id="sl_preview_location">—</strong></div>
                            <div><span>Payment Method</span><strong id="sl_preview_method">—</strong></div>
                            <div><span>Items</span><strong id="sl_preview_items">—</strong></div>
                            <div><span>Shipping</span><strong id="sl_preview_shipping">—</strong></div>
                            <div><span>Grand Total</span><strong id="sl_preview_total">—</strong></div>
                            <div><span>Paid</span><strong id="sl_preview_paid">—</strong></div>
                            <div><span>Balance Due</span><strong id="sl_preview_due">—</strong></div>
                        </div>
                        <div id="sl_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="sl-card sl-fin-card">
                <div class="sl-card-head">
                    <div>
                        <h3>Payment Summary</h3>
                        <p>Synced from table footer</p>
                    </div>
                </div>
                <div class="sl-card-body">
                    <div class="sl-preview-rows">
                        <div class="sl-total"><span>Sales Total</span><strong id="sl_fin_total">—</strong></div>
                        <div><span>Paid</span><strong id="sl_fin_paid">—</strong></div>
                        <div><span>Balance Due</span><strong id="sl_fin_due">—</strong></div>
                        <div><span>Return Due</span><strong id="sl_fin_return">—</strong></div>
                    </div>
                </div>
            </div>

            <div class="sl-card">
                <div class="sl-card-head">
                    <div>
                        <h3>Status Legend</h3>
                        <p>Payment badges (existing logic)</p>
                    </div>
                </div>
                <div class="sl-card-body">
                    <div class="sl-status-legend">
                        <span class="sl-status-chip paid">Paid</span>
                        <span class="sl-status-chip partial">Partial</span>
                        <span class="sl-status-chip due">Due</span>
                        <span class="sl-status-chip due">Overdue</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>
</section>

@endsection

@section('javascript')
    <script src="{{ asset('js/sells-premium-ui.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            //Date range as a button
            $('#sell_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
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
                aaSorting: [
                    [1, 'desc']
                ],
                "ajax": {
                    "url": "/sells",
                    "data": function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.is_direct_sale = 1;

                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();

                        if ($('#shipping_status').length) {
                            d.shipping_status = $('#shipping_status').val();
                        }

                        if ($('#sell_list_filter_source').length) {
                            d.source = $('#sell_list_filter_source').val();
                        }

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        if ($('#payment_method').length) {
                            d.payment_method = $('#payment_method').val();
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'payment_methods',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'total_paid',
                        name: 'total_paid',
                        "searchable": false
                    },
                    {
                        data: 'total_remaining',
                        name: 'total_remaining'
                    },
                    {
                        data: 'return_due',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    {
                        data: 'total_items',
                        name: 'total_items',
                        "searchable": false
                    },
                    {
                        data: 'types_of_service_name',
                        name: 'tos.name',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'service_custom_field_1',
                        name: 'service_custom_field_1',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_1',
                        name: 'transactions.custom_field_1',
                        @if (empty($custom_labels['sell']['custom_field_1']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_2',
                        name: 'transactions.custom_field_2',
                        @if (empty($custom_labels['sell']['custom_field_2']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_3',
                        name: 'transactions.custom_field_3',
                        @if (empty($custom_labels['sell']['custom_field_3']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_4',
                        name: 'transactions.custom_field_4',
                        @if (empty($custom_labels['sell']['custom_field_4']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                    {
                        data: 'additional_notes',
                        name: 'additional_notes'
                    },
                    {
                        data: 'staff_note',
                        name: 'staff_note'
                    },
                    {
                        data: 'shipping_details',
                        name: 'shipping_details'
                    },
                    {
                        data: 'table_name',
                        name: 'tables.name',
                        @if (empty($is_tables_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                            visible: false
                        @endif
                    },
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                "footerCallback": function(row, data, start, end, display) {
                    var footer_sale_total = 0;
                    var footer_total_paid = 0;
                    var footer_total_remaining = 0;
                    var footer_total_sell_return_due = 0;
                    for (var r in data) {
                        footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(
                            data[r].final_total).data('orig-value')) : 0;
                        footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(
                            data[r].total_paid).data('orig-value')) : 0;
                        footer_total_remaining += $(data[r].total_remaining).data('orig-value') ?
                            parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                        footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due')
                            .data('orig-value') ? parseFloat($(data[r].return_due).find(
                                '.sell_return_due').data('orig-value')) : 0;
                    }

                    $('.footer_total_sell_return_due').html(__currency_trans_from_en(
                        footer_total_sell_return_due));
                    $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
                    $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
                    $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));

                    $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
                    $('.service_type_count').html(__count_status(data, 'types_of_service_name'));
                    $('.payment_method_count').html(__count_status(data, 'payment_methods'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change',
                '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source, #payment_method',
                function() {
                    sell_table.ajax.reload();
                });

            $('#only_subscriptions').on('ifChanged', function(event) {
                sell_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
