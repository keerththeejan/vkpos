@extends('layouts.app')
@section('title', __('lang_v1.purchase_return'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase-return-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content no-print pr-shell">
    {{-- Sticky Header --}}
    <div class="pr-header" role="banner">
        <div class="pr-header-left">
            <h1>@lang('lang_v1.purchase_return')</h1>
            <p class="pr-subtitle">Supplier returns · credit notes · inventory reverse</p>
            <div class="pr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('lang_v1.purchase_return')</span>
            </div>
        </div>
        <div class="pr-header-actions">
            <div class="pr-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="pr_quick_search" class="form-control" placeholder="Search returns…" aria-label="Search purchase returns" autocomplete="off">
            </div>
            <button type="button" class="pr-btn pr-btn-ghost" id="pr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="pr-btn pr-btn-ghost" id="pr_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="pr-btn pr-btn-ghost" id="pr_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('purchase.update')
                <a class="pr-btn pr-btn-primary" href="{{ action([\App\Http\Controllers\CombinedPurchaseReturnController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards (from live DataTable / footer) --}}
    <div class="pr-kpi-grid" aria-label="Purchase return KPIs">
        <div class="pr-kpi tone-blue">
            <div class="pr-kpi-icon"><i class="fas fa-undo"></i></div>
            <span class="pr-kpi-label">Total Returns</span>
            <span class="pr-kpi-value" id="pr_kpi_total">—</span>
            <span class="pr-kpi-hint">All purchase returns</span>
        </div>
        <div class="pr-kpi tone-teal">
            <div class="pr-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="pr-kpi-label">Filtered</span>
            <span class="pr-kpi-value" id="pr_kpi_filtered">—</span>
            <span class="pr-kpi-hint">Matching filters</span>
        </div>
        <div class="pr-kpi tone-green">
            <div class="pr-kpi-icon"><i class="fas fa-industry"></i></div>
            <span class="pr-kpi-label">Suppliers (page)</span>
            <span class="pr-kpi-value" id="pr_kpi_suppliers">—</span>
            <span class="pr-kpi-hint">On current page</span>
        </div>
        <div class="pr-kpi tone-orange">
            <div class="pr-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
            <span class="pr-kpi-label">Page Return Value</span>
            <span class="pr-kpi-value pr-kpi-text" id="pr_kpi_page_total">—</span>
            <span class="pr-kpi-hint">From table footer</span>
        </div>
        <div class="pr-kpi tone-red">
            <div class="pr-kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <span class="pr-kpi-label">Supplier Credit Due</span>
            <span class="pr-kpi-value pr-kpi-text" id="pr_kpi_due">—</span>
            <span class="pr-kpi-hint">Payment due on page</span>
        </div>
        <div class="pr-kpi tone-slate">
            <div class="pr-kpi-icon"><i class="fas fa-store"></i></div>
            <span class="pr-kpi-label">Locations</span>
            <span class="pr-kpi-value">{{ count($business_locations ?? []) }}</span>
            <span class="pr-kpi-hint">In location filter</span>
        </div>
    </div>

    <div class="pr-future-strip" aria-label="Coming soon">
        <span class="pr-chip"><i class="fas fa-check-double"></i> Return Approval</span>
        <span class="pr-chip"><i class="fas fa-qrcode"></i> QR Return</span>
        <span class="pr-chip"><i class="fas fa-barcode"></i> Barcode Return</span>
        <span class="pr-chip"><i class="fas fa-robot"></i> AI Analysis</span>
        <span class="pr-chip"><i class="fas fa-tools"></i> Repair Integration</span>
        <span class="pr-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="pr-main-grid">
        <div class="pr-main-col">
            {{-- Filters: preserve IDs used by inline DataTable ajax --}}
            <div class="pr-card pr-filter-card">
                <div class="pr-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Location &amp; date range drive the existing AJAX table</p>
                    </div>
                </div>
                <div class="pr-card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('purchase_list_filter_location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select('purchase_list_filter_location_id', $business_locations, null, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'placeholder' => __('lang_v1.all'),
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('purchase_list_filter_date_range', null, [
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="control-label">&nbsp;</label>
                            <div class="pr-filter-actions">
                                <button type="button" class="pr-btn pr-btn-primary" id="pr_apply_filters"><i class="fas fa-check"></i> Apply</button>
                                <button type="button" class="pr-btn" id="pr_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                                <button type="button" class="pr-btn" id="pr_toggle_adv"><i class="fas fa-sliders-h"></i> Advanced</button>
                            </div>
                        </div>
                    </div>

                    <div class="pr-adv-filters" id="pr_adv_filters" style="display:none;" aria-label="Advanced filters placeholder">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
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
                                    <label>Payment Status</label>
                                    <input type="text" class="form-control" disabled placeholder="Coming soon">
                                </div>
                            </div>
                        </div>
                        <p class="text-muted" style="margin:0;font-size:12px;">Advanced filters are UI placeholders only — existing Location + Date Range filters remain active.</p>
                    </div>
                </div>
            </div>

            <div class="pr-card">
                <div class="pr-card-head pr-card-head-row">
                    <div>
                        <h3>@lang('lang_v1.all_purchase_returns')</h3>
                        <p>Existing DataTable &amp; AJAX logic unchanged</p>
                    </div>
                    @can('purchase.update')
                        <a class="pr-btn pr-btn-primary" href="{{ action([\App\Http\Controllers\CombinedPurchaseReturnController::class, 'create']) }}">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endcan
                </div>
                <div class="pr-card-body">
                    <div class="table-responsive pr-table-wrap">
                        @can('purchase.view')
                            @include('purchase_return.partials.purchase_return_list')
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <aside class="pr-side-col">
            <div class="pr-card" id="pr_preview_card" aria-live="polite">
                <div class="pr-card-head">
                    <div>
                        <h3>Return Details</h3>
                        <p>Select a row to inspect</p>
                    </div>
                </div>
                <div class="pr-card-body">
                    <div class="pr-preview-empty" id="pr_preview_empty">
                        <i class="fas fa-undo"></i>
                        <p>Click any return row to preview supplier, purchase, totals, and actions.</p>
                    </div>
                    <div class="pr-preview-content" id="pr_preview_content" style="display:none;">
                        <div class="pr-preview-badge"><i class="fas fa-file-invoice"></i></div>
                        <div class="pr-preview-title-row">
                            <h4 id="pr_preview_ref">—</h4>
                            <span class="pr-pill" id="pr_preview_payment">—</span>
                        </div>
                        <div class="pr-preview-rows">
                            <div><span>Purchase No</span><strong id="pr_preview_parent">—</strong></div>
                            <div><span>Date</span><strong id="pr_preview_date">—</strong></div>
                            <div><span>Supplier</span><strong id="pr_preview_supplier">—</strong></div>
                            <div><span>Location</span><strong id="pr_preview_location">—</strong></div>
                            <div><span>Return Total</span><strong id="pr_preview_total">—</strong></div>
                            <div><span>Credit Due</span><strong id="pr_preview_due">—</strong></div>
                        </div>
                        <div id="pr_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="pr-card pr-fin-card">
                <div class="pr-card-head">
                    <div>
                        <h3>Financial Summary</h3>
                        <p>Synced from table footer</p>
                    </div>
                </div>
                <div class="pr-card-body">
                    <div class="pr-preview-rows">
                        <div class="pr-total"><span>Return Total</span><strong id="pr_fin_total">—</strong></div>
                        <div><span>Supplier Credit Due</span><strong id="pr_fin_due">—</strong></div>
                        <div><span>Payment Status</span><strong id="pr_fin_status">—</strong></div>
                    </div>
                </div>
            </div>

            <div class="pr-card">
                <div class="pr-card-head">
                    <div>
                        <h3>Return Reasons</h3>
                        <p>Reference labels (UI only)</p>
                    </div>
                </div>
                <div class="pr-card-body">
                    <div class="pr-reason-grid">
                        <div class="pr-reason-chip">Damaged</div>
                        <div class="pr-reason-chip">Wrong Item</div>
                        <div class="pr-reason-chip">Expired</div>
                        <div class="pr-reason-chip">Quality Issue</div>
                        <div class="pr-reason-chip">Supplier Error</div>
                        <div class="pr-reason-chip">Other</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>

@endsection

@section('javascript')
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/purchase-return-premium-ui.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function() {
            $('#purchase_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end
                        .format(moment_date_format));
                    purchase_return_table.ajax.reload();
                }
            );
            $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#purchase_list_filter_date_range').val('');
                purchase_return_table.ajax.reload();
            });

            //Purchase table
            purchase_return_table = $('#purchase_return_datatable').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '/purchase-return',
                    data: function(d) {
                        if ($('#purchase_list_filter_location_id').length) {
                            d.location_id = $('#purchase_list_filter_location_id').val();
                        }

                        var start = '';
                        var end = '';
                        if ($('#purchase_list_filter_date_range').val()) {
                            start = $('input#purchase_list_filter_date_range')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            end = $('input#purchase_list_filter_date_range')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                        }
                        d.start_date = start;
                        d.end_date = end;
                    },
                },
                columnDefs: [{
                    "targets": [7, 8],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'parent_purchase',
                        name: 'T.ref_no'
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name'
                    },
                    {
                        data: 'name',
                        name: 'contacts.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'payment_due',
                        name: 'payment_due'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
                "fnDrawCallback": function(oSettings) {
                    var total_purchase = sum_table_col($('#purchase_return_datatable'), 'final_total');
                    $('#footer_purchase_return_total').text(total_purchase);

                    $('#footer_payment_status_count').html(__sum_status_html($(
                        '#purchase_return_datatable'), 'payment-status-label'));

                    var total_due = sum_table_col($('#purchase_return_datatable'), 'payment_due');
                    $('#footer_total_due').text(total_due);

                    __currency_convert_recursively($('#purchase_return_datatable'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(5)').attr('class', 'clickable_td');
                }
            });

            $(document).on(
                'change',
                '#purchase_list_filter_location_id',
                function() {
                    purchase_return_table.ajax.reload();
                }
            );
        });
    </script>

@endsection
