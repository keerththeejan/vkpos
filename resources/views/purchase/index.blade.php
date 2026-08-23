@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchases-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content no-print pu-shell">
    {{-- Sticky Header --}}
    <div class="pu-header" role="banner">
        <div class="pu-header-left">
            <h1>@lang('purchase.purchases')</h1>
            <p class="pu-subtitle">Procurement · suppliers · stock receiving · payments</p>
            <div class="pu-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('purchase.purchases')</span>
            </div>
        </div>
        <div class="pu-header-actions">
            <div class="pu-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="pu_quick_search" class="form-control" placeholder="Search purchases…" aria-label="Search purchases" autocomplete="off">
            </div>
            <button type="button" class="pu-btn pu-btn-ghost" id="pu_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="pu-btn pu-btn-ghost" id="pu_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="pu-btn pu-btn-ghost" id="pu_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('purchase.create')
                <a class="pu-btn pu-btn-primary" href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTable --}}
    <div class="pu-kpi-grid" aria-label="Purchase KPIs">
        <div class="pu-kpi tone-blue">
            <div class="pu-kpi-icon"><i class="fas fa-file-invoice"></i></div>
            <span class="pu-kpi-label">Total POs</span>
            <span class="pu-kpi-value" id="pu_kpi_total">—</span>
            <span class="pu-kpi-hint">All purchase orders</span>
        </div>
        <div class="pu-kpi tone-teal">
            <div class="pu-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="pu-kpi-label">Filtered</span>
            <span class="pu-kpi-value" id="pu_kpi_filtered">—</span>
            <span class="pu-kpi-hint">Matching filters</span>
        </div>
        <div class="pu-kpi tone-green">
            <div class="pu-kpi-icon"><i class="fas fa-truck"></i></div>
            <span class="pu-kpi-label">Suppliers</span>
            <span class="pu-kpi-value">{{ count($suppliers ?? []) }}</span>
            <span class="pu-kpi-hint">In filter dropdown</span>
        </div>
        <div class="pu-kpi tone-purple">
            <div class="pu-kpi-icon"><i class="fas fa-store"></i></div>
            <span class="pu-kpi-label">Locations</span>
            <span class="pu-kpi-value">{{ count($business_locations ?? []) }}</span>
            <span class="pu-kpi-hint">Business locations</span>
        </div>
        <div class="pu-kpi tone-orange">
            <div class="pu-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
            <span class="pu-kpi-label">Page Total</span>
            <span class="pu-kpi-value pu-kpi-text" id="pu_kpi_page_total">—</span>
            <span class="pu-kpi-hint">From table footer</span>
        </div>
        <div class="pu-kpi tone-slate">
            <div class="pu-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="pu-kpi-label">Due</span>
            <span class="pu-kpi-value pu-kpi-text" id="pu_kpi_due">—</span>
            <span class="pu-kpi-hint">Purchase due on page</span>
        </div>
    </div>

    <div class="pu-future-strip" aria-label="Coming soon">
        <span class="pu-chip"><i class="fas fa-check-double"></i> Approval Workflow</span>
        <span class="pu-chip"><i class="fas fa-qrcode"></i> QR Receiving</span>
        <span class="pu-chip"><i class="fas fa-robot"></i> AI Reorder</span>
        <span class="pu-chip"><i class="fas fa-scan"></i> OCR Invoice</span>
        <span class="pu-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="pu-main-grid">
        <div class="pu-main-col">
            {{-- Filters: preserve all IDs used by purchase.js --}}
            <div class="pu-card pu-filter-card">
                <div class="pu-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Same filters drive the purchase DataTable</p>
                    </div>
                </div>
                <div class="pu-card-body">
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
                                {!! Form::label('purchase_list_filter_supplier_id', __('purchase.supplier') . ':') !!}
                                {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'placeholder' => __('lang_v1.all'),
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('purchase_list_filter_status', __('purchase.purchase_status') . ':') !!}
                                {!! Form::select('purchase_list_filter_status', $orderStatuses, null, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'placeholder' => __('lang_v1.all'),
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('purchase_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
                                {!! Form::select(
                                    'purchase_list_filter_payment_status',
                                    [
                                        'paid' => __('lang_v1.paid'),
                                        'due' => __('lang_v1.due'),
                                        'partial' => __('lang_v1.partial'),
                                        'overdue' => __('lang_v1.overdue'),
                                    ],
                                    null,
                                    ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')],
                                ) !!}
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
                    </div>
                </div>
            </div>

            <div class="pu-card">
                <div class="pu-card-head pu-card-head-row">
                    <div>
                        <h3>@lang('purchase.all_purchases')</h3>
                        <p>Existing DataTable &amp; AJAX logic unchanged</p>
                    </div>
                    @can('purchase.create')
                        <a class="pu-btn pu-btn-primary" href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endcan
                </div>
                <div class="pu-card-body">
                    <div class="table-responsive pu-table-wrap">
                        @include('purchase.partials.purchase_table')
                    </div>
                </div>
            </div>
        </div>

        <aside class="pu-side-col">
            <div class="pu-card pu-preview-card" id="pu_preview_card" aria-live="polite">
                <div class="pu-card-head">
                    <div>
                        <h3>Purchase Preview</h3>
                        <p>Select a row to inspect details</p>
                    </div>
                </div>
                <div class="pu-card-body">
                    <div class="pu-preview-empty" id="pu_preview_empty">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Click any purchase row to preview supplier, totals, payment, and actions.</p>
                    </div>
                    <div class="pu-preview-content" id="pu_preview_content" style="display:none;">
                        <div class="pu-preview-badge"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="pu-preview-title-row">
                            <h4 id="pu_preview_ref">—</h4>
                            <span class="pu-pill" id="pu_preview_status">—</span>
                        </div>
                        <div class="pu-preview-meta">
                            <div><span class="k">Date</span><span class="v" id="pu_preview_date">—</span></div>
                            <div><span class="k">Supplier</span><span class="v" id="pu_preview_supplier">—</span></div>
                            <div><span class="k">Location</span><span class="v" id="pu_preview_location">—</span></div>
                            <div><span class="k">Payment</span><span class="v" id="pu_preview_payment">—</span></div>
                            <div><span class="k">Grand Total</span><span class="v" id="pu_preview_total">—</span></div>
                            <div><span class="k">Payment Due</span><span class="v" id="pu_preview_due">—</span></div>
                            <div><span class="k">Added By</span><span class="v" id="pu_preview_by">—</span></div>
                        </div>
                        <h5 class="pu-preview-section-title">Quick Actions</h5>
                        <div class="pu-preview-actions" id="pu_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="pu-card">
                <div class="pu-card-head">
                    <div>
                        <h3>Financial Snapshot</h3>
                        <p>From table footer totals</p>
                    </div>
                </div>
                <div class="pu-card-body">
                    <div class="pu-finance-list">
                        <div class="pu-finance-item">
                            <span>Grand Total</span>
                            <strong id="pu_fin_total">—</strong>
                        </div>
                        <div class="pu-finance-item">
                            <span>Purchase Due</span>
                            <strong id="pu_fin_due">—</strong>
                        </div>
                        <div class="pu-finance-item">
                            <span>Return Due</span>
                            <strong id="pu_fin_return">—</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pu-card">
                <div class="pu-card-head">
                    <div>
                        <h3>Quick Links</h3>
                        <p>Existing purchase routes</p>
                    </div>
                </div>
                <div class="pu-card-body">
                    <div class="pu-quick-links">
                        @can('purchase.create')
                            <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}"><i class="fas fa-plus"></i> New Purchase</a>
                        @endcan
                        <a href="{{ url('/contacts?type=supplier') }}"><i class="fas fa-truck"></i> Suppliers</a>
                        <a href="{{ url('/products') }}"><i class="fas fa-box"></i> Products</a>
                        <a href="{{ url('/purchase-return') }}"><i class="fas fa-undo"></i> Purchase Return</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('purchase.partials.update_purchase_status_modal')
</section>

<section id="receipt_section" class="print_section"></section>
@stop

@section('javascript')
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        //Date range as a button
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });

        $(document).on('click', '.update_status', function(e) {
            e.preventDefault();
            $('#update_purchase_status_form').find('#status').val($(this).data('status'));
            $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
            $('#update_purchase_status_modal').modal('show');
        });

        $(document).on('submit', '#update_purchase_status_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function(result) {
                    if (result.success == true) {
                        $('#update_purchase_status_modal').modal('hide');
                        toastr.success(result.msg);
                        purchase_table.ajax.reload();
                        $('#update_purchase_status_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    </script>
    <script src="{{ asset('js/purchases-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
