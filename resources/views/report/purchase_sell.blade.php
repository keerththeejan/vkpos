@extends('layouts.app')
@section('title', __( 'report.purchase_sell' ))

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase-sell-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content ps-shell">
    <div class="print_section"><h2>{{session()->get('business.name')}} - @lang( 'report.purchase_sell' )</h2></div>

    {{-- Sticky Header --}}
    <div class="ps-header no-print" role="banner">
        <div class="ps-header-left">
            <h1>@lang( 'report.purchase_sell' )</h1>
            <p class="ps-subtitle">@lang( 'report.purchase_sell_msg' )</p>
            <div class="ps-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span>@lang( 'report.purchase_sell' )</span>
            </div>
        </div>
        <div class="ps-header-actions">
            <button type="button" class="ps-btn ps-btn-ghost" id="ps_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ps-btn" id="ps_refresh_report" title="Refresh">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button type="button" class="ps-btn ps-btn-primary" id="ps_print_page" aria-label="Print">
                <i class="fas fa-print"></i> @lang('messages.print')
            </button>
        </div>
        <div class="ps-header-actions" style="width:100%;justify-content:flex-start;margin-top:4px;">
            <div class="ps-btn" style="cursor:default;"><span>Location</span>&nbsp;<strong id="ps_meta_location">—</strong></div>
            <div class="ps-btn" style="cursor:default;"><span>Period</span>&nbsp;<strong id="ps_meta_range">—</strong></div>
        </div>
    </div>

    {{-- KPI cards mirror existing AJAX-filled classes (no recalculation) --}}
    <div class="ps-kpi-grid no-print" aria-label="Purchase & Sales KPIs">
        <div class="ps-kpi tone-blue">
            <div class="ps-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
            <span class="ps-kpi-label">Total Purchase</span>
            <span class="ps-kpi-value" id="ps_kpi_purchase">—</span>
            <span class="ps-kpi-hint">Exc. tax</span>
        </div>
        <div class="ps-kpi tone-teal">
            <div class="ps-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <span class="ps-kpi-label">Purchase Inc. Tax</span>
            <span class="ps-kpi-value" id="ps_kpi_purchase_tax">—</span>
            <span class="ps-kpi-hint">From report</span>
        </div>
        <div class="ps-kpi tone-orange">
            <div class="ps-kpi-icon"><i class="fas fa-undo"></i></div>
            <span class="ps-kpi-label">Purchase Return</span>
            <span class="ps-kpi-value" id="ps_kpi_purchase_return">—</span>
            <span class="ps-kpi-hint">Inc. tax</span>
        </div>
        <div class="ps-kpi tone-red">
            <div class="ps-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="ps-kpi-label">Purchase Due</span>
            <span class="ps-kpi-value" id="ps_kpi_purchase_due">—</span>
            <span class="ps-kpi-hint">Outstanding</span>
        </div>
        <div class="ps-kpi tone-green">
            <div class="ps-kpi-icon"><i class="fas fa-cash-register"></i></div>
            <span class="ps-kpi-label">Total Sell</span>
            <span class="ps-kpi-value" id="ps_kpi_sell">—</span>
            <span class="ps-kpi-hint">Exc. tax</span>
        </div>
        <div class="ps-kpi tone-violet">
            <div class="ps-kpi-icon"><i class="fas fa-receipt"></i></div>
            <span class="ps-kpi-label">Sell Inc. Tax</span>
            <span class="ps-kpi-value" id="ps_kpi_sell_tax">—</span>
            <span class="ps-kpi-hint">From report</span>
        </div>
        <div class="ps-kpi tone-orange">
            <div class="ps-kpi-icon"><i class="fas fa-reply"></i></div>
            <span class="ps-kpi-label">Sell Return</span>
            <span class="ps-kpi-value" id="ps_kpi_sell_return">—</span>
            <span class="ps-kpi-hint">Inc. tax</span>
        </div>
        <div class="ps-kpi tone-slate">
            <div class="ps-kpi-icon"><i class="fas fa-balance-scale"></i></div>
            <span class="ps-kpi-label">Sell − Purchase</span>
            <span class="ps-kpi-value" id="ps_kpi_diff">—</span>
            <span class="ps-kpi-hint">Overall difference</span>
        </div>
    </div>

    <div class="ps-future-strip no-print" aria-label="Coming soon">
        <span class="ps-chip"><i class="fas fa-robot"></i> AI Sales Forecast</span>
        <span class="ps-chip"><i class="fas fa-truck"></i> Supplier Performance</span>
        <span class="ps-chip"><i class="fas fa-chart-pie"></i> ABC Inventory</span>
        <span class="ps-chip"><i class="fas fa-plug"></i> Power BI / Tableau</span>
        <span class="ps-chip"><i class="fas fa-calendar-check"></i> Scheduled Reports</span>
        <span class="ps-chip muted">UI placeholders — calculations unchanged</span>
    </div>

    {{-- Filters — exact IDs for report.js / updatePurchaseSell --}}
    <div class="ps-card no-print">
        <div class="ps-card-head">
            <div>
                <h3>Report Filters</h3>
                <p>Location &amp; date range drive existing AJAX purchase–sales summary</p>
            </div>
            <button type="button" class="ps-btn ps-btn-primary" id="ps_generate_report">
                <i class="fas fa-play"></i> Generate Report
            </button>
        </div>
        <div class="ps-card-body">
            <div class="ps-filters-row">
                <div class="ps-filter-item">
                    <label for="purchase_sell_location_filter">@lang('purchase.business_location')</label>
                    <div class="input-group">
                        <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                        <select class="form-control select2" id="purchase_sell_location_filter">
                            @foreach($business_locations as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ps-filter-item">
                    <label>@lang('messages.filter_by_date')</label>
                    <div class="form-group" style="margin:0;">
                        <div class="input-group" style="width:100%;">
                          <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="purchase_sell_date_filter">
                            <span>
                              <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                            </span>
                            <i class="fa fa-caret-down"></i>
                          </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ps-export-bar" style="margin-top:12px;margin-bottom:0;justify-content:flex-start;">
                <div class="ps-btn" style="cursor:default;"><span>Sell Due</span>&nbsp;<strong id="ps_kpi_sell_due">—</strong></div>
                <div class="ps-btn" style="cursor:default;"><span>Diff Due</span>&nbsp;<strong id="ps_kpi_diff_due">—</strong></div>
            </div>
        </div>
    </div>

    {{-- Purchase vs Sales summary — class names preserved for updatePurchaseSell --}}
    <div class="ps-compare-grid">
        <div class="col-xs-6" style="width:100%;float:none;padding:0;">
            @component('components.widget', ['title' => __('purchase.purchases')])
                <table class="table table-striped">
                    <tr>
                        <th>{{ __('report.total_purchase') }}:</th>
                        <td>
                            <span class="total_purchase">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('report.purchase_inc_tax') }}:</th>
                        <td>
                             <span class="purchase_inc_tax">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('lang_v1.total_purchase_return_inc_tax') }}:</th>
                        <td>
                             <span class="purchase_return_inc_tax">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('report.purchase_due') }}: @show_tooltip(__('tooltip.purchase_due'))</th>
                        <td>
                             <span class="purchase_due">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                </table>
            @endcomponent
        </div>

        <div class="col-xs-6" style="width:100%;float:none;padding:0;">
            @component('components.widget', ['title' => __('sale.sells')])
                <table class="table table-striped">
                    <tr>
                        <th>{{ __('report.total_sell') }}:</th>
                        <td>
                            <span class="total_sell">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('report.sell_inc_tax') }}:</th>
                        <td>
                             <span class="sell_inc_tax">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('lang_v1.total_sell_return_inc_tax') }}:</th>
                        <td>
                             <span class="total_sell_return">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('report.sell_due') }}: @show_tooltip(__('tooltip.sell_due'))</th>
                        <td>
                            <span class="sell_due">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                </table>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            @component('components.widget')
                @slot('title')
                    {{ __('lang_v1.overall') }} 
                    ((@lang('business.sale') - @lang('lang_v1.sell_return')) - (@lang('lang_v1.purchase') - @lang('lang_v1.purchase_return')) ) 
                    @show_tooltip(__('tooltip.over_all_sell_purchase'))
                @endslot
                <div class="ps-overall">
                <h3 class="text-muted">
                    {{ __('report.sell_minus_purchase') }}: 
                    <span class="sell_minus_purchase">
                        <i class="fas fa-sync fa-spin fa-fw"></i>
                    </span>
                </h3>

                <h3 class="text-muted">
                    {{ __('report.difference_due') }}: 
                    <span class="difference_due">
                        <i class="fas fa-sync fa-spin fa-fw"></i>
                    </span>
                </h3>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="ps-export-bar no-print">
        <button class="ps-btn ps-btn-primary" aria-label="Print"
            onclick="window.print();">
            <i class="fas fa-print"></i> @lang('messages.print')
        </button>
    </div>
	

</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/purchase-sell-premium-ui.js?v=' . $asset_v) }}"></script>

@endsection
