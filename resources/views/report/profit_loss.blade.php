@extends('layouts.app')
@section('title', __('report.profit_loss'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/profit-loss-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content pl-shell">
        <div class="print_section">
            <h2>{{ session()->get('business.name') }} - @lang('report.profit_loss')</h2>
        </div>

        {{-- Sticky Header --}}
        <div class="pl-header no-print" role="banner">
            <div class="pl-header-left">
                <h1>@lang('report.profit_loss')</h1>
                <p class="pl-subtitle">Executive financial analytics &amp; profitability dashboard</p>
                <div class="pl-breadcrumb" aria-label="Breadcrumb">
                    <span>Home</span>
                    <span>/</span>
                    <span>Reports</span>
                    <span>/</span>
                    <span>@lang('report.profit_loss')</span>
                </div>
            </div>
            <div class="pl-header-actions">
                <button type="button" class="pl-btn pl-btn-ghost" id="pl_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                    <i class="fas fa-moon"></i>
                </button>
                <button type="button" class="pl-btn" id="pl_refresh_report" title="Refresh">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="pl-btn pl-btn-primary" id="pl_print_page" aria-label="Print">
                    <i class="fas fa-print"></i> @lang('messages.print')
                </button>
            </div>
            <div class="pl-header-actions" style="width:100%;justify-content:flex-start;margin-top:4px;">
                <div class="pl-btn" style="cursor:default;"><span>Location</span>&nbsp;<strong id="pl_meta_location">—</strong></div>
                <div class="pl-btn" style="cursor:default;"><span>Period</span>&nbsp;<strong id="pl_meta_range">—</strong></div>
            </div>
        </div>

        {{-- KPI cards (values mirrored from existing P&L HTML — no recalculation) --}}
        <div class="pl-kpi-grid no-print" aria-label="Financial KPIs">
            <div class="pl-kpi tone-green">
                <div class="pl-kpi-icon"><i class="fas fa-chart-line"></i></div>
                <span class="pl-kpi-label">Gross Profit</span>
                <span class="pl-kpi-value" id="pl_kpi_gross">—</span>
                <span class="pl-kpi-hint">From P&amp;L statement</span>
            </div>
            <div class="pl-kpi tone-blue">
                <div class="pl-kpi-icon"><i class="fas fa-coins"></i></div>
                <span class="pl-kpi-label">Net Profit</span>
                <span class="pl-kpi-value" id="pl_kpi_net">—</span>
                <span class="pl-kpi-hint">From P&amp;L statement</span>
            </div>
            <div class="pl-kpi tone-orange">
                <div class="pl-kpi-icon"><i class="fas fa-boxes"></i></div>
                <span class="pl-kpi-label">COGS</span>
                <span class="pl-kpi-value" id="pl_kpi_cogs">—</span>
                <span class="pl-kpi-hint">Cost of goods sold</span>
            </div>
            <div class="pl-kpi tone-red">
                <div class="pl-kpi-icon"><i class="fas fa-receipt"></i></div>
                <span class="pl-kpi-label">Total Expenses</span>
                <span class="pl-kpi-value" id="pl_kpi_expense">—</span>
                <span class="pl-kpi-hint">From left panel</span>
            </div>
            <div class="pl-kpi tone-teal">
                <div class="pl-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
                <span class="pl-kpi-label">Purchases</span>
                <span class="pl-kpi-value" id="pl_kpi_purchase">—</span>
                <span class="pl-kpi-hint">From left panel</span>
            </div>
            <div class="pl-kpi tone-violet">
                <div class="pl-kpi-icon"><i class="fas fa-warehouse"></i></div>
                <span class="pl-kpi-label">Opening Stock</span>
                <span class="pl-kpi-value" id="pl_kpi_opening">—</span>
                <span class="pl-kpi-hint">By purchase price</span>
            </div>
            <div class="pl-kpi tone-slate">
                <div class="pl-kpi-icon"><i class="fas fa-percentage"></i></div>
                <span class="pl-kpi-label">Gross Margin %</span>
                <span class="pl-kpi-value">—</span>
                <span class="pl-kpi-hint">UI placeholder</span>
            </div>
            <div class="pl-kpi tone-blue">
                <div class="pl-kpi-icon"><i class="fas fa-map-marker-alt"></i></div>
                <span class="pl-kpi-label">Cash Flow</span>
                <span class="pl-kpi-value">—</span>
                <span class="pl-kpi-hint">UI placeholder</span>
            </div>
        </div>

        <div class="pl-future-strip no-print" aria-label="Coming soon">
            <span class="pl-chip"><i class="fas fa-robot"></i> AI Profit Forecast</span>
            <span class="pl-chip"><i class="fas fa-balance-scale"></i> Budget vs Actual</span>
            <span class="pl-chip"><i class="fas fa-building"></i> Cost Center P&amp;L</span>
            <span class="pl-chip"><i class="fas fa-chart-area"></i> Cash Flow Forecast</span>
            <span class="pl-chip"><i class="fas fa-plug"></i> Power BI / Tableau</span>
            <span class="pl-chip muted">UI placeholders — calculations unchanged</span>
        </div>

        {{-- Filters — exact IDs for report.js / updateProfitLoss --}}
        <div class="pl-card no-print">
            <div class="pl-card-head">
                <div>
                    <h3>Report Filters</h3>
                    <p>Location &amp; date range drive existing AJAX P&amp;L + profit tabs</p>
                </div>
                <button type="button" class="pl-btn pl-btn-primary" id="pl_generate_report">
                    <i class="fas fa-play"></i> Generate Report
                </button>
            </div>
            <div class="pl-card-body">
                <div class="pl-filters-row">
                    <div class="pl-filter-item">
                        <label for="profit_loss_location_filter">@lang('purchase.business_location')</label>
                        <div class="input-group">
                            <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                            <select class="form-control select2" id="profit_loss_location_filter">
                                @foreach ($business_locations as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="pl-filter-item">
                        <label>@lang('messages.filter_by_date')</label>
                        <div class="form-group" style="margin:0;">
                            <div class="input-group" style="width:100%;">
                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="profit_loss_date_filter">
                                    <span>
                                        <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                                    </span>
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pl-export-bar" style="margin-top:12px;margin-bottom:0;">
                    <div class="pl-btn" style="cursor:default;"><span>Gross</span>&nbsp;<strong id="pl_sum_gross">—</strong></div>
                    <div class="pl-btn" style="cursor:default;"><span>COGS</span>&nbsp;<strong id="pl_sum_cogs">—</strong></div>
                    <div class="pl-btn" style="cursor:default;"><span>Net</span>&nbsp;<strong id="pl_sum_net">—</strong></div>
                </div>
            </div>
        </div>

        {{-- P&L statement loaded by updateProfitLoss() --}}
        <div class="pl-card">
            <div class="pl-card-head no-print">
                <div>
                    <h3>Profit &amp; Loss Statement</h3>
                    <p>Opening / closing stock · COGS · gross &amp; net profit (existing HTML)</p>
                </div>
            </div>
            <div class="pl-card-body">
                <div class="row">
                    <div id="pl_data_div">
                    </div>
                </div>
            </div>
        </div>

        <div class="pl-export-bar no-print">
            <button class="pl-btn pl-btn-primary" aria-label="Print" onclick="window.print();">
                <i class="fas fa-print"></i> @lang('messages.print')
            </button>
        </div>

        {{-- Profit analysis tabs — IDs preserved --}}
        <div class="row no-print">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#profit_by_products" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_products')</a>
                        </li>

                        <li>
                            <a href="#profit_by_categories" data-toggle="tab" aria-expanded="true"><i class="fa fa-tags"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_categories')</a>
                        </li>

                        <li>
                            <a href="#profit_by_brands" data-toggle="tab" aria-expanded="true"><i class="fa fa-diamond"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_brands')</a>
                        </li>

                        <li>
                            <a href="#profit_by_locations" data-toggle="tab" aria-expanded="true"><i
                                    class="fa fa-map-marker" aria-hidden="true"></i> @lang('lang_v1.profit_by_locations')</a>
                        </li>

                        <li>
                            <a href="#profit_by_invoice" data-toggle="tab" aria-expanded="true"><i class="fa fa-file-alt"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_invoice')</a>
                        </li>

                        <li>
                            <a href="#profit_by_date" data-toggle="tab" aria-expanded="true"><i class="fa fa-calendar"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_date')</a>
                        </li>
                        <li>
                            <a href="#profit_by_customer" data-toggle="tab" aria-expanded="true"><i class="fa fa-user"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_customer')</a>
                        </li>
                        <li>
                            <a href="#profit_by_day" data-toggle="tab" aria-expanded="true"><i class="fa fa-calendar"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_day')</a>
                        </li>
                        <li>
                            <a href="#profit_by_service_staff" data-toggle="tab" aria-expanded="true"><i class="fa fa-user-secret"
                                    aria-hidden="true"></i> @lang('lang_v1.profit_by_service_staff')</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="profit_by_products">
                            @include('report.partials.profit_by_products')
                        </div>

                        <div class="tab-pane" id="profit_by_categories">
                            @include('report.partials.profit_by_categories')
                        </div>

                        <div class="tab-pane" id="profit_by_brands">
                            @include('report.partials.profit_by_brands')
                        </div>

                        <div class="tab-pane" id="profit_by_locations">
                            @include('report.partials.profit_by_locations')
                        </div>

                        <div class="tab-pane" id="profit_by_invoice">
                            @include('report.partials.profit_by_invoice')
                        </div>

                        <div class="tab-pane" id="profit_by_date">
                            @include('report.partials.profit_by_date')
                        </div>

                        <div class="tab-pane" id="profit_by_customer">
                            @include('report.partials.profit_by_customer')
                        </div>
                        <div class="tab-pane" id="profit_by_service_staff">
                            @include('report.partials.profit_by_service_staff')
                        </div>

                        <div class="tab-pane" id="profit_by_day">

                        </div>
                    </div>
                </div>
            </div>
        </div>


</section>
    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/profit-loss-premium-ui.js?v=' . $asset_v) }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            profit_by_products_table = $('#profit_by_products_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                "ajax": {
                    "url": "/reports/get-profit/product",
                    "data": function(d) {
                        d.start_date = $('#profit_loss_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('#profit_loss_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.location_id = $('#profit_loss_location_filter').val();
                    }
                },
                columns: [{
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'gross_profit',
                        "searchable": false
                    },
                ],
                footerCallback: function(row, data, start, end, display) {
                    var total_profit = 0;
                    for (var r in data) {
                        total_profit += $(data[r].gross_profit).data('orig-value') ?
                            parseFloat($(data[r].gross_profit).data('orig-value')) : 0;
                    }

                    $('#profit_by_products_table .footer_total').html(__currency_trans_from_en(
                        total_profit));
                }
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (target == '#profit_by_categories') {
                    if (typeof profit_by_categories_datatable == 'undefined') {
                        profit_by_categories_datatable = $('#profit_by_categories_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/category",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'category',
                                    name: 'C.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_categories_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_categories_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_brands') {
                    if (typeof profit_by_brands_datatable == 'undefined') {
                        profit_by_brands_datatable = $('#profit_by_brands_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/brand",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'brand',
                                    name: 'B.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_brands_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_brands_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_locations') {
                    if (typeof profit_by_locations_datatable == 'undefined') {
                        profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/location",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'location',
                                    name: 'L.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_locations_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_locations_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_invoice') {
                    if (typeof profit_by_invoice_datatable == 'undefined') {
                        profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/invoice",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'invoice_no',
                                    name: 'sale.invoice_no'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_invoice_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_invoice_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_date') {
                    if (typeof profit_by_date_datatable == 'undefined') {
                        profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/date",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'transaction_date',
                                    name: 'sale.transaction_date'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_date_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_date_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_customer') {
                    if (typeof profit_by_customers_table == 'undefined') {
                        profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/customer",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'customer',
                                    name: 'CU.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_customer_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_customers_table.ajax.reload();
                    }
                } else if (target == '#profit_by_service_staff') {
                    if (typeof profit_by_service_staffs_table == 'undefined') {
                        
                        profit_by_service_staffs_table = $('#profit_by_service_staff_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/get-profit/service_staff",
                                "data": function(d) {
                                    d.start_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#profit_loss_date_filter')
                                        .data('daterangepicker')
                                        .endDate.format('YYYY-MM-DD');
                                    d.location_id = $('#profit_loss_location_filter').val();
                                }
                            },
                            columns: [{
                                    data: 'staff_name',
                                    name: 'U.first_name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += $(data[r].gross_profit).data('orig-value') ?
                                        parseFloat($(data[r].gross_profit).data('orig-value')) :
                                        0;
                                }

                                $('#profit_by_service_staff_table .footer_total').html(
                                    __currency_trans_from_en(total_profit));
                            },
                        });
                    } else {
                        profit_by_service_staffs_table.ajax.reload();
                    }
                } else if (target == '#profit_by_day') {
                    var start_date = $('#profit_loss_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');

                    var end_date = $('#profit_loss_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                    var location_id = $('#profit_loss_location_filter').val();

                    var url = '/reports/get-profit/day?start_date=' + start_date + '&end_date=' + end_date +
                        '&location_id=' + location_id;
                    $.ajax({
                        url: url,
                        dataType: 'html',
                        success: function(result) {
                            $('#profit_by_day').html(result);
                            profit_by_days_table = $('#profit_by_day_table').DataTable({
                                "searching": false,
                                'paging': false,
                                'ordering': false,
                            });
                            var total_profit = sum_table_col($('#profit_by_day_table'),
                                'gross-profit');
                            $('#profit_by_day_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_day_table'));
                        },
                    });
                } else if (target == '#profit_by_products') {
                    profit_by_products_table.ajax.reload();
                }
                $("a.btn").removeClass("btn btn-default buttons-excel buttons-html5");
            });
        });
    </script>

@endsection
