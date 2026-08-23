@extends('layouts.app')
@section('title', __('lang_v1.items_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/items-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $irp_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content irp-shell" id="irp_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.items_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="irp-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('purchase.supplier') }}: <span class="irp-print-supplier">—</span>
            &nbsp;·&nbsp;
            {{ __('contact.customer') }}: <span class="irp-print-customer">—</span>
        </p>
        <p>
            {{ __('purchase.purchase_date') }}: <span class="irp-print-purchase">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.sell_date') }}: <span class="irp-print-sale">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.items_report') }}</p>
    </div>

    <div class="irp-header no-print" role="banner">
        <div class="irp-header-left">
            <h1>
                <span class="irp-title-icon" aria-hidden="true"><i class="bi bi-list-check"></i></span>
                {{ __('lang_v1.items_report') }}
            </h1>
            <p class="irp-subtitle">Purchase-to-sale item mappings, quantities and values</p>
            <nav class="irp-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.items_report') }}</span>
            </nav>
        </div>
        <div class="irp-header-actions">
            <div class="irp-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="irp_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} product, SKU, purchase, invoice…" aria-label="Search items report" autocomplete="off">
                <button type="button" class="irp-search-clear" id="irp_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="irp-search-spinner" id="irp_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="irp-btn irp-btn-icon" id="irp_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="irp-btn irp-btn-icon" id="irp_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="irp_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="irp-btn irp-btn-icon" id="irp_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="irp-btn" id="irp_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($irp_has_export)
            <div class="irp-export-wrap">
                <button type="button" class="irp-btn" id="irp_export_toggle" aria-haspopup="true" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="irp-export-menu" id="irp_export_menu" role="menu" hidden>
                    <button type="button" class="irp-export-item" id="irp_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="irp-export-item" id="irp_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="irp-export-item" id="irp_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="irp-export-item" id="irp_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="irp-btn irp-btn-primary" id="irp_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="irp-header-meta" aria-label="Active filters">
            <div class="irp-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="irp_meta_location">—</strong></div>
            <div class="irp-meta"><span>{{ __('purchase.supplier') }}</span> <strong id="irp_meta_supplier">—</strong></div>
            <div class="irp-meta"><span>{{ __('contact.customer') }}</span> <strong id="irp_meta_customer">—</strong></div>
            <div class="irp-meta"><span>{{ __('purchase.purchase_date') }}</span> <strong id="irp_meta_purchase">—</strong></div>
            <div class="irp-meta"><span>{{ __('lang_v1.sell_date') }}</span> <strong id="irp_meta_sale">—</strong></div>
        </div>
        <div class="irp-settings-panel" id="irp_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="irp_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="irp_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="irp-kpi-grid no-print" aria-label="Items report summary">
        <div class="irp-kpi tone-blue">
            <div class="irp-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="irp-kpi-label">Matching lines</span>
            <span class="irp-kpi-value" id="irp_kpi_lines">—</span>
            <span class="irp-kpi-hint">Filtered item mappings</span>
        </div>
        <div class="irp-kpi tone-teal">
            <div class="irp-kpi-icon"><i class="bi bi-stack"></i></div>
            <span class="irp-kpi-label">{{ __('lang_v1.sell_quantity') }}</span>
            <span class="irp-kpi-value" id="irp_kpi_qty">—</span>
            <span class="irp-kpi-hint">This page</span>
        </div>
        <div class="irp-kpi tone-green">
            <div class="irp-kpi-icon"><i class="bi bi-cart"></i></div>
            <span class="irp-kpi-label">{{ __('lang_v1.purchase_price') }}</span>
            <span class="irp-kpi-value" id="irp_kpi_pp">—</span>
            <span class="irp-kpi-hint">This page</span>
        </div>
        <div class="irp-kpi tone-violet">
            <div class="irp-kpi-icon"><i class="bi bi-tag"></i></div>
            <span class="irp-kpi-label">{{ __('lang_v1.selling_price') }}</span>
            <span class="irp-kpi-value" id="irp_kpi_sp">—</span>
            <span class="irp-kpi-hint">This page</span>
        </div>
        <div class="irp-kpi tone-orange">
            <div class="irp-kpi-icon"><i class="bi bi-calculator"></i></div>
            <span class="irp-kpi-label">{{ __('sale.subtotal') }}</span>
            <span class="irp-kpi-value" id="irp_kpi_subtotal">—</span>
            <span class="irp-kpi-hint">This page</span>
        </div>
    </div>

    <div class="irp-card irp-filters-card no-print">
        <div class="irp-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Supplier, customer, location and dates</p>
            </div>
            <button type="button" class="irp-btn irp-btn-ghost" id="irp_filters_toggle" aria-expanded="true" aria-controls="irp_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="irp-card-body" id="irp_filters_body">
            <div class="irp-filter-grid">
                <div class="form-group">
                    {!! Form::label('ir_location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                        {!! Form::select('ir_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ir_supplier_id', __('purchase.supplier') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::select('ir_supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ir_purchase_date_filter', __('purchase.purchase_date') . ':') !!}
                    {!! Form::text('ir_purchase_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('ir_customer_id', __('contact.customer') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::select('ir_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ir_sale_date_filter', __('lang_v1.sell_date') . ':') !!}
                    {!! Form::text('ir_sale_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
                @if(Module::has('Manufacturing'))
                <div class="form-group irp-check-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('only_mfg', 1, false, ['class' => 'input-icheck', 'id' => 'only_mfg_products']); !!}
                            {{ __('manufacturing::lang.only_mfg_products') }}
                        </label>
                    </div>
                </div>
                @endif
            </div>
            <div class="irp-filter-actions">
                <button type="button" class="irp-btn irp-btn-primary" id="irp_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="irp-btn" id="irp_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="irp-btn" id="irp_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($irp_has_export)
                <button type="button" class="irp-btn" id="irp_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="irp-exception-strip no-print" id="irp_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="irp-alert irp-alert-error no-print" id="irp_error" hidden role="alert">
        <div>
            <strong>Unable to load items report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="irp-btn irp-btn-primary" id="irp_retry">Retry</button>
    </div>

    <div class="irp-card irp-table-card">
        <div class="irp-card-head no-print">
            <div>
                <h2>{{ __('lang_v1.items_report') }}</h2>
                <p>Purchase lines mapped to sales and stock adjustments</p>
            </div>
        </div>
        <div class="irp-card-body irp-table-body">
            <div class="irp-table-loading no-print" id="irp_table_loading" hidden>
                <div class="irp-skeleton-row"></div>
                <div class="irp-skeleton-row"></div>
                <div class="irp-skeleton-row"></div>
            </div>
            <div class="irp-empty no-print" id="irp_empty" hidden>
                <div class="irp-empty-icon" aria-hidden="true">📦</div>
                <strong>No items found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="irp-btn irp-btn-primary" id="irp_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="items_report_table">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('product.sku')</th>
                            <th>@lang('lang_v1.description')</th>
                            <th>@lang('purchase.purchase_date')</th>
                            <th>@lang('lang_v1.purchase')</th>
                            <th>@lang('lang_v1.lot_number')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('lang_v1.purchase_price')</th>
                            <th>@lang('lang_v1.sell_date')</th>
                            <th>@lang('business.sale')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('lang_v1.sell_quantity')</th>
                            <th>@lang('lang_v1.selling_price')</th>
                            <th>@lang('sale.subtotal')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_total_pp" class="display_currency" data-currency_symbol="true"></td>
                            <td colspan="4"></td>
                            <td id="footer_total_qty"></td>
                            <td id="footer_total_sp" class="display_currency" data-currency_symbol="true"></td>
                            <td id="footer_total_subtotal" class="display_currency" data-currency_symbol="true"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p class="irp-page-note no-print">Matching lines is the filtered DataTables count. Quantity and value cards copy the table footer for the current page.</p>

    <div class="irp-export-bar no-print">
        @if($irp_has_export)
        <button type="button" class="irp-btn" id="irp_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="irp-btn" id="irp_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="irp-btn" id="irp_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="irp-btn irp-btn-primary" id="irp_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="irp-drawer" id="irp_drawer" role="dialog" aria-modal="true" aria-labelledby="irp_drawer_title" aria-hidden="true">
    <div class="irp-drawer-head">
        <div>
            <h3 id="irp_drawer_title">Item</h3>
            <p class="irp-drawer-sku" id="irp_drawer_sku">—</p>
            <span class="irp-drawer-kind" id="irp_drawer_kind" hidden></span>
        </div>
        <button type="button" class="irp-btn irp-btn-icon" id="irp_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <details class="irp-drawer-section" open>
        <summary>Overview</summary>
        <dl class="irp-drawer-dl">
            <div><dt>@lang('lang_v1.description')</dt><dd id="irp_drawer_note">—</dd></div>
            <div><dt>@lang('sale.location')</dt><dd id="irp_drawer_location">—</dd></div>
            <div><dt>@lang('lang_v1.lot_number')</dt><dd id="irp_drawer_lot">—</dd></div>
        </dl>
    </details>
    <details class="irp-drawer-section" open>
        <summary>Purchase</summary>
        <dl class="irp-drawer-dl">
            <div><dt>@lang('purchase.purchase_date')</dt><dd id="irp_drawer_pdate">—</dd></div>
            <div><dt>@lang('lang_v1.purchase')</dt><dd id="irp_drawer_pref">—</dd></div>
            <div><dt>@lang('purchase.supplier')</dt><dd id="irp_drawer_supplier">—</dd></div>
            <div><dt>@lang('lang_v1.purchase_price')</dt><dd id="irp_drawer_pp">—</dd></div>
        </dl>
    </details>
    <details class="irp-drawer-section" open>
        <summary>Sale / Adjustment</summary>
        <dl class="irp-drawer-dl">
            <div><dt>@lang('lang_v1.sell_date')</dt><dd id="irp_drawer_sdate">—</dd></div>
            <div><dt>@lang('business.sale')</dt><dd id="irp_drawer_sref">—</dd></div>
            <div><dt>@lang('contact.customer')</dt><dd id="irp_drawer_customer">—</dd></div>
            <div><dt>@lang('lang_v1.sell_quantity')</dt><dd id="irp_drawer_qty">—</dd></div>
        </dl>
    </details>
    <details class="irp-drawer-section" open>
        <summary>Values</summary>
        <dl class="irp-drawer-dl">
            <div><dt>@lang('lang_v1.selling_price')</dt><dd id="irp_drawer_sp">—</dd></div>
            <div><dt>@lang('sale.subtotal')</dt><dd id="irp_drawer_subtotal">—</dd></div>
        </dl>
    </details>
</aside>
<div class="irp-drawer-backdrop" id="irp_drawer_backdrop" hidden></div>
<div class="irp-toast-host" id="irp_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/items-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
