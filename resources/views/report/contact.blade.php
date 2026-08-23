@extends('layouts.app')
@section('title', __('report.customer') . ' - ' . __('report.supplier') . ' ' . __('report.reports'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-supplier-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content cs-shell">
    {{-- Sticky Header --}}
    <div class="cs-header no-print" role="banner">
        <div class="cs-header-left">
            <h1>{{ __('report.customer')}} & {{ __('report.supplier')}} {{ __('report.reports')}}</h1>
            <p class="cs-subtitle">Enterprise CRM &amp; relationship analytics</p>
            <div class="cs-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span>{{ __('report.customer')}} &amp; {{ __('report.supplier')}}</span>
            </div>
        </div>
        <div class="cs-header-actions">
            <div class="cs-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="cs_quick_search" class="form-control" placeholder="Search contacts…" aria-label="Search contacts" autocomplete="off">
            </div>
            <button type="button" class="cs-btn cs-btn-ghost" id="cs_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="cs-btn cs-btn-ghost" id="cs_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="cs-btn cs-btn-ghost" id="cs_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <button type="button" class="cs-btn cs-btn-primary" id="cs_generate_report">
                <i class="fas fa-play"></i> Generate Report
            </button>
        </div>
        <div class="cs-header-actions" style="width:100%;justify-content:flex-start;margin-top:4px;">
            <div class="cs-btn" style="cursor:default;"><span>Location</span>&nbsp;<strong id="cs_meta_location">—</strong></div>
            <div class="cs-btn" style="cursor:default;"><span>Type</span>&nbsp;<strong id="cs_meta_type">—</strong></div>
            <div class="cs-btn" style="cursor:default;"><span>Period</span>&nbsp;<strong id="cs_meta_range">—</strong></div>
        </div>
    </div>

    {{-- KPI cards from DataTable page.info + footers (no recalculation) --}}
    <div class="cs-kpi-grid no-print" aria-label="Customer & Supplier KPIs">
        <div class="cs-kpi tone-blue">
            <div class="cs-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="cs-kpi-label">Total Contacts</span>
            <span class="cs-kpi-value" id="cs_kpi_contacts">—</span>
            <span class="cs-kpi-hint">All matching records</span>
        </div>
        <div class="cs-kpi tone-teal">
            <div class="cs-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="cs-kpi-label">Filtered</span>
            <span class="cs-kpi-value" id="cs_kpi_filtered">—</span>
            <span class="cs-kpi-hint">Current filters</span>
        </div>
        <div class="cs-kpi tone-orange">
            <div class="cs-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
            <span class="cs-kpi-label">Total Purchase</span>
            <span class="cs-kpi-value" id="cs_kpi_purchase">—</span>
            <span class="cs-kpi-hint">Page footer</span>
        </div>
        <div class="cs-kpi tone-green">
            <div class="cs-kpi-icon"><i class="fas fa-cash-register"></i></div>
            <span class="cs-kpi-label">Total Sell</span>
            <span class="cs-kpi-value" id="cs_kpi_sell">—</span>
            <span class="cs-kpi-hint">Page footer</span>
        </div>
        <div class="cs-kpi tone-red">
            <div class="cs-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="cs-kpi-label">Total Due</span>
            <span class="cs-kpi-value" id="cs_kpi_due">—</span>
            <span class="cs-kpi-hint">Outstanding</span>
        </div>
        <div class="cs-kpi tone-violet">
            <div class="cs-kpi-icon"><i class="fas fa-balance-scale"></i></div>
            <span class="cs-kpi-label">Opening Balance Due</span>
            <span class="cs-kpi-value" id="cs_kpi_opening">—</span>
            <span class="cs-kpi-hint">Page footer</span>
        </div>
        <div class="cs-kpi tone-orange">
            <div class="cs-kpi-icon"><i class="fas fa-undo"></i></div>
            <span class="cs-kpi-label">Purchase Return</span>
            <span class="cs-kpi-value" id="cs_kpi_purchase_return">—</span>
            <span class="cs-kpi-hint">Page footer</span>
        </div>
        <div class="cs-kpi tone-slate">
            <div class="cs-kpi-icon"><i class="fas fa-reply"></i></div>
            <span class="cs-kpi-label">Sell Return</span>
            <span class="cs-kpi-value" id="cs_kpi_sell_return">—</span>
            <span class="cs-kpi-hint">Page footer</span>
        </div>
    </div>

    <div class="cs-future-strip no-print" aria-label="Coming soon">
        <span class="cs-chip"><i class="fas fa-robot"></i> AI Customer Insights</span>
        <span class="cs-chip"><i class="fas fa-chart-pie"></i> RFM / CLV</span>
        <span class="cs-chip"><i class="fas fa-star"></i> Supplier Scorecard</span>
        <span class="cs-chip"><i class="fas fa-plug"></i> Power BI / Tableau</span>
        <span class="cs-chip"><i class="fas fa-history"></i> Audit Trail</span>
        <span class="cs-chip muted">UI placeholders — calculations unchanged</span>
    </div>

    <div class="cs-main-grid">
        <div class="cs-main-col">
            {{-- Filters — IDs preserved for report.js --}}
            <div class="cs-card no-print">
                <div class="cs-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Group · type · location · contact · date (existing AJAX filters)</p>
                    </div>
                </div>
                <div class="cs-card-body">
                    @component('components.filters', ['title' => __('report.filters')])

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('cg_customer_group_id', __( 'lang_v1.customer_group_name' ) . ':') !!}
                                {!! Form::select('cnt_customer_group_id', $customer_group, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'cnt_customer_group_id']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('type', __( 'lang_v1.type' ) . ':') !!}
                                {!! Form::select('contact_type', $types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'contact_type']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('cs_report_location_id', __( 'sale.location' ) . ':') !!}
                                {!! Form::select('cs_report_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'cs_report_location_id']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('scr_contact_id', __( 'report.contact' ) . ':') !!}
                                {!! Form::select('scr_contact_id', $contact_dropdown, null , ['class' => 'form-control select2', 'id' => 'scr_contact_id', 'placeholder' => __('lang_v1.all'), 'style' => 'width:100%']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('scr_date_filter', __('report.date_range') . ':') !!}
                                {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'scr_date_filter', 'readonly']); !!}
                            </div>
                        </div>

                    @endcomponent
                    <div class="cs-filter-actions">
                        <button type="button" class="cs-btn cs-btn-primary" id="cs_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="cs-btn" id="cs_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="cs-card">
                <div class="cs-card-head">
                    <div>
                        <h3>Customer &amp; Supplier Report</h3>
                        <p>Server-side DataTable · purchases · sales · dues</p>
                    </div>
                </div>
                <div class="cs-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="supplier_report_tbl">
                            <thead>
                                <tr>
                                    <th>@lang('report.contact')</th>
                                    <th>@lang('report.total_purchase')</th>
                                    <th>@lang('lang_v1.total_purchase_return')</th>
                                    <th>@lang('report.total_sell')</th>
                                    <th>@lang('lang_v1.total_sell_return')</th>
                                    <th>@lang('lang_v1.opening_balance_due')</th>
                                    <th>@lang('report.total_due') &nbsp;&nbsp;<i class="fa fa-info-circle text-info no-print" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="{{ __('messages.due_tooltip')}}" aria-hidden="true"></i></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td><strong>@lang('sale.total'):</strong></td>
                                    <td><span class="display_currency" id="footer_total_purchase" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_purchase_return" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_sell" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_sell_return" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_opening_bal_due" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_due" data-currency_symbol ="true"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="cs-side no-print" aria-label="Contact details">
            <div class="cs-preview-card">
                <h3>Contact Profile</h3>
                <p class="cs-preview-empty" id="cs_preview_empty">Select a contact row to preview purchases, sales, returns, and outstanding balances.</p>
                <div id="cs_preview_content" style="display:none;">
                    <div class="cs-preview-rows">
                        <div><span>Contact</span><strong id="cs_preview_name">—</strong></div>
                        <div><span>Purchase</span><strong id="cs_preview_purchase">—</strong></div>
                        <div><span>Purchase Return</span><strong id="cs_preview_purchase_return">—</strong></div>
                        <div><span>Sell</span><strong id="cs_preview_sell">—</strong></div>
                        <div><span>Sell Return</span><strong id="cs_preview_sell_return">—</strong></div>
                        <div><span>Opening Due</span><strong id="cs_preview_opening">—</strong></div>
                        <div><span>Total Due</span><strong id="cs_preview_due">—</strong></div>
                    </div>
                    <div class="cs-note">
                        Profile drawer is read-only. Open the contact module for edit / payment history when needed.
                    </div>
                </div>
            </div>

            <div class="cs-summary-card">
                <h3>Financial Summary</h3>
                <div class="cs-summary-rows">
                    <div><span>Contacts (filtered)</span><strong id="cs_sum_contacts">—</strong></div>
                    <div><span>Purchase (page)</span><strong id="cs_sum_purchase">—</strong></div>
                    <div><span>Sell (page)</span><strong id="cs_sum_sell">—</strong></div>
                    <div><span>Due (page)</span><strong id="cs_sum_due">—</strong></div>
                </div>
                <div class="cs-note">
                    Mirrors DataTable footers (<code>#footer_total_*</code>).
                </div>
            </div>
        </aside>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/customer-supplier-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
