@extends('layouts.app')
@section('title', __('lang_v1.customer_groups_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/customer-group-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $cgr_master_count = is_countable($customer_group) ? max(0, count($customer_group) - 1) : 0;
    $cgr_location_count = is_countable($business_locations) ? max(0, count($business_locations) - 1) : 0;
    $cgr_fy_start = Session::get('financial_year.start');
    $cgr_fy_end = Session::get('financial_year.end');
@endphp

@section('content')

<section class="content cgr-shell" data-group-master="{{ $cgr_master_count }}" data-location-count="{{ $cgr_location_count }}">
    <div class="print_section">
        <h2>{{ session()->get('business.name') }} - {{ __('lang_v1.customer_groups_report') }}</h2>
    </div>

    {{-- Sticky Executive Header --}}
    <div class="cgr-header no-print" role="banner">
        <div class="cgr-header-left">
            <h1>{{ __('lang_v1.customer_groups_report') }}</h1>
            <p class="cgr-subtitle">Enterprise customer segmentation &amp; group revenue analytics</p>
            <div class="cgr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span>{{ __('lang_v1.customer_groups_report') }}</span>
            </div>
        </div>
        <div class="cgr-header-actions">
            <div class="cgr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="cgr_quick_search" class="form-control" placeholder="Search groups…" aria-label="Search customer groups" autocomplete="off">
            </div>
            <button type="button" class="cgr-btn cgr-btn-ghost" id="cgr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="cgr-btn cgr-btn-ghost" id="cgr_settings_toggle" title="Dashboard Settings" aria-label="Dashboard settings">
                <i class="bi bi-sliders"></i>
            </button>
            <button type="button" class="cgr-btn" id="cgr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button type="button" class="cgr-btn cgr-btn-primary" id="cgr_generate" aria-label="Generate report">
                <i class="bi bi-play-fill"></i> Generate Report
            </button>
            <button type="button" class="cgr-btn" id="cgr_export_excel" aria-label="Export Excel">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </button>
            <button type="button" class="cgr-btn" id="cgr_export_pdf" aria-label="Export PDF">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
            <button type="button" class="cgr-btn" id="cgr_print_page" aria-label="Print">
                <i class="bi bi-printer"></i> Print
            </button>
            <button type="button" class="cgr-btn" id="cgr_email_report" aria-label="Email report">
                <i class="bi bi-envelope"></i> Email
            </button>
        </div>
        <div class="cgr-header-actions" style="width:100%;justify-content:flex-start;margin-top:4px;">
            <div class="cgr-meta"><span>Location</span>&nbsp;<strong id="cgr_meta_location">—</strong></div>
            <div class="cgr-meta"><span>Customer Group</span>&nbsp;<strong id="cgr_meta_group">—</strong></div>
            <div class="cgr-meta"><span>Financial Year</span>&nbsp;<strong>{{ $cgr_fy_start ?: '—' }}{{ $cgr_fy_end ? ' – '.$cgr_fy_end : '' }}</strong></div>
            <div class="cgr-meta"><span>Date Range</span>&nbsp;<strong id="cgr_meta_range">—</strong></div>
        </div>
        <div class="cgr-settings-panel" id="cgr_settings_panel" role="dialog" aria-label="Dashboard settings">
            <label><input type="checkbox" id="cgr_set_hide_kpis"> Hide KPI cards</label>
            <label><input type="checkbox" id="cgr_set_hide_charts"> Hide analytics charts</label>
            <label><input type="checkbox" id="cgr_set_hide_future"> Hide future-ready chips</label>
        </div>
    </div>

    {{-- KPI Dashboard — values mirrored from DataTable page + master dropdowns --}}
    <div class="cgr-kpi-grid no-print" aria-label="Customer group KPIs">
        <div class="cgr-kpi tone-indigo">
            <div class="cgr-kpi-icon"><i class="bi bi-people"></i></div>
            <span class="cgr-kpi-label">Total Customer Groups</span>
            <span class="cgr-kpi-value" id="cgr_kpi_groups">—</span>
            <span class="cgr-kpi-hint">Filtered report rows</span>
        </div>
        <div class="cgr-kpi tone-blue">
            <div class="cgr-kpi-icon"><i class="bi bi-person"></i></div>
            <span class="cgr-kpi-label">Master Groups</span>
            <span class="cgr-kpi-value" id="cgr_kpi_groups_master">{{ $cgr_master_count }}</span>
            <span class="cgr-kpi-hint">From group dropdown</span>
        </div>
        <div class="cgr-kpi tone-green">
            <div class="cgr-kpi-icon"><i class="bi bi-currency-dollar"></i></div>
            <span class="cgr-kpi-label">Total Revenue</span>
            <span class="cgr-kpi-value" id="cgr_kpi_revenue">—</span>
            <span class="cgr-kpi-hint">This page · total sell</span>
        </div>
        <div class="cgr-kpi tone-teal">
            <div class="cgr-kpi-icon"><i class="bi bi-graph-up"></i></div>
            <span class="cgr-kpi-label">Avg Revenue / Group</span>
            <span class="cgr-kpi-value" id="cgr_kpi_avg_group">—</span>
            <span class="cgr-kpi-hint">This page</span>
        </div>
        <div class="cgr-kpi tone-orange">
            <div class="cgr-kpi-icon"><i class="bi bi-trophy"></i></div>
            <span class="cgr-kpi-label">Top Customer Group</span>
            <span class="cgr-kpi-value" id="cgr_kpi_top">—</span>
            <span class="cgr-kpi-hint">Highest sell this page</span>
        </div>
        <div class="cgr-kpi tone-red">
            <div class="cgr-kpi-icon"><i class="bi bi-credit-card"></i></div>
            <span class="cgr-kpi-label">Outstanding Receivables</span>
            <span class="cgr-kpi-value">—</span>
            <span class="cgr-kpi-hint">UI placeholder</span>
        </div>
        <div class="cgr-kpi tone-violet">
            <div class="cgr-kpi-icon"><i class="bi bi-star"></i></div>
            <span class="cgr-kpi-label">Reward Points Issued</span>
            <span class="cgr-kpi-value">—</span>
            <span class="cgr-kpi-hint">UI placeholder</span>
        </div>
        <div class="cgr-kpi tone-slate">
            <div class="cgr-kpi-icon"><i class="bi bi-bag-check"></i></div>
            <span class="cgr-kpi-label">Orders Completed</span>
            <span class="cgr-kpi-value">—</span>
            <span class="cgr-kpi-hint">UI placeholder</span>
        </div>
        <div class="cgr-kpi tone-blue">
            <div class="cgr-kpi-icon"><i class="bi bi-receipt"></i></div>
            <span class="cgr-kpi-label">Average Order Value</span>
            <span class="cgr-kpi-value">—</span>
            <span class="cgr-kpi-hint">UI placeholder</span>
        </div>
        <div class="cgr-kpi tone-green">
            <div class="cgr-kpi-icon"><i class="bi bi-bar-chart-line"></i></div>
            <span class="cgr-kpi-label">Revenue Growth</span>
            <span class="cgr-kpi-value">—</span>
            <span class="cgr-kpi-hint">UI placeholder</span>
        </div>
        <div class="cgr-kpi tone-indigo">
            <div class="cgr-kpi-icon"><i class="bi bi-cart3"></i></div>
            <span class="cgr-kpi-label">Total Sales</span>
            <span class="cgr-kpi-value" id="cgr_kpi_sales">—</span>
            <span class="cgr-kpi-hint">Same as page revenue</span>
        </div>
        <div class="cgr-kpi tone-teal">
            <div class="cgr-kpi-icon"><i class="bi bi-building"></i></div>
            <span class="cgr-kpi-label">Business Locations</span>
            <span class="cgr-kpi-value" id="cgr_kpi_locations">{{ $cgr_location_count }}</span>
            <span class="cgr-kpi-hint">From location dropdown</span>
        </div>
    </div>

    <div class="cgr-future-strip no-print" aria-label="Coming soon">
        <span class="cgr-chip soon"><i class="bi bi-robot"></i> AI Customer Segmentation</span>
        <span class="cgr-chip soon"><i class="bi bi-heart-pulse"></i> Customer Lifetime Value</span>
        <span class="cgr-chip soon"><i class="bi bi-pie-chart"></i> RFM Analysis</span>
        <span class="cgr-chip soon"><i class="bi bi-activity"></i> Churn Prediction</span>
        <span class="cgr-chip soon"><i class="bi bi-award"></i> Loyalty Analytics</span>
        <span class="cgr-chip soon"><i class="bi bi-gift"></i> Reward Program</span>
        <span class="cgr-chip soon"><i class="bi bi-graph-up-arrow"></i> Predictive Revenue</span>
        <span class="cgr-chip soon"><i class="bi bi-signpost-split"></i> Customer Journey</span>
        <span class="cgr-chip soon"><i class="bi bi-plug"></i> Power BI / Tableau</span>
        <span class="cgr-chip soon"><i class="bi bi-calendar-check"></i> Scheduled Reports</span>
        <span class="cgr-chip soon"><i class="bi bi-clock-history"></i> Digital Audit Trail</span>
        <span class="cgr-chip muted">UI placeholders — calculations unchanged</span>
    </div>

    {{-- Advanced Filters — original IDs preserved --}}
    <div class="cgr-card cgr-filters-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>@lang('report.filters')</h3>
                <p>Business location · customer group · date range drive the existing report AJAX</p>
            </div>
            <button type="button" class="cgr-btn cgr-btn-primary" id="cgr_apply">
                <i class="bi bi-check2"></i> Apply
            </button>
        </div>
        <div class="cgr-card-body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getCustomerGroup']), 'method' => 'get', 'id' => 'cg_report_filter_form' ]) !!}
                <div class="cgr-filter-grid">
                    <div class="form-group">
                        {!! Form::label('cg_customer_group_id', __( 'lang_v1.customer_group_name' ) . ':') !!}
                        {!! Form::select('cg_customer_group_id', $customer_group, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'cg_customer_group_id']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('cg_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('cg_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('cg_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'cg_date_range', 'readonly']); !!}
                    </div>
                </div>
            {!! Form::close() !!}

            <div class="cgr-ph-grid" aria-label="Additional filters coming soon">
                <div class="form-group">
                    <label for="cgr_ph_customer">Customer</label>
                    <select id="cgr_ph_customer" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_sr">Sales Representative</label>
                    <select id="cgr_ph_sr" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_price">Price Group</label>
                    <select id="cgr_ph_price" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_pay">Payment Status</label>
                    <select id="cgr_ph_pay" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_credit">Credit Status</label>
                    <select id="cgr_ph_credit" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_reward">Reward Point Status</label>
                    <select id="cgr_ph_reward" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_cat">Product Category</label>
                    <select id="cgr_ph_cat" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_brand">Brand</label>
                    <select id="cgr_ph_brand" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_city">City</label>
                    <select id="cgr_ph_city" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_district">District</label>
                    <select id="cgr_ph_district" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_country">Country</label>
                    <select id="cgr_ph_country" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
                <div class="form-group">
                    <label for="cgr_ph_status">Status</label>
                    <select id="cgr_ph_status" class="form-control" disabled><option>All — coming soon</option></select>
                </div>
            </div>

            <div class="cgr-filter-actions">
                <button type="button" class="cgr-btn cgr-btn-primary" id="cgr_generate_alt">
                    <i class="bi bi-play-fill"></i> Generate Report
                </button>
                <button type="button" class="cgr-btn" id="cgr_reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                <button type="button" class="cgr-btn" id="cgr_save_filter"><i class="bi bi-bookmark"></i> Save Filter</button>
            </div>
        </div>
    </div>

    {{-- Executive Summary --}}
    <div class="cgr-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Executive Summary</h3>
                <p>Mirrors the current DataTable page and existing filter dropdowns</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="cgr-summary-grid">
                <div class="cgr-summary-item"><span>Total Customer Groups</span><strong id="cgr_sum_groups">—</strong></div>
                <div class="cgr-summary-item"><span>Groups (server total)</span><strong id="cgr_sum_groups_total">—</strong></div>
                <div class="cgr-summary-item"><span>Master Groups</span><strong id="cgr_sum_master">—</strong></div>
                <div class="cgr-summary-item"><span>Page Groups</span><strong id="cgr_sum_page">—</strong></div>
                <div class="cgr-summary-item"><span>Total Revenue</span><strong id="cgr_sum_revenue">—</strong></div>
                <div class="cgr-summary-item"><span>Average / Group</span><strong id="cgr_sum_avg">—</strong></div>
                <div class="cgr-summary-item"><span>Top Group</span><strong id="cgr_sum_top">—</strong></div>
                <div class="cgr-summary-item"><span>Business Locations</span><strong id="cgr_sum_locations">—</strong></div>
                <div class="cgr-summary-item"><span>Active Groups</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Inactive Groups</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Total Customers</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Total Orders</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Average Order Value</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Outstanding Receivables</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Payments Received</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Reward Points</span><strong>—</strong></div>
            </div>
            <p class="cgr-note">Values without a source in this report remain placeholders. Group sales still come only from the existing Customer Group DataTable.</p>
        </div>
    </div>

    <div class="cgr-main-grid">
        <div class="cgr-main-col">
            {{-- Interactive report — original table IDs / classes / columns --}}
            <div class="cgr-card">
                <div class="cgr-card-head">
                    <div>
                        <h3>Interactive Customer Group Report</h3>
                        <p>Server-side DataTable · group name · total sell · existing AJAX</p>
                    </div>
                </div>
                <div class="cgr-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="cg_report_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.customer_group')</th>
                                    <th>@lang('report.total_sell')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <p class="cgr-note no-print">Click a row to open the read-only group profile. Extra CRM columns (customers, orders, outstanding, reward points) are not in this report’s API and are not added to the table.</p>
                </div>
            </div>
        </div>

        <aside class="cgr-side no-print" aria-label="Group insights">
            <div class="cgr-preview-card">
                <h3>Highest Revenue Groups</h3>
                <ul class="cgr-rank-list" id="cgr_high_revenue_list"></ul>
                <p class="cgr-note">Ranked from the current DataTable page.</p>
            </div>
            <div class="cgr-summary-card">
                <h3>Quick Actions</h3>
                <div class="cgr-filter-actions">
                    <button type="button" class="cgr-btn" id="cgr_share"><i class="bi bi-share"></i> Share</button>
                    <button type="button" class="cgr-btn" id="cgr_schedule"><i class="bi bi-calendar-plus"></i> Schedule</button>
                    <button type="button" class="cgr-btn cgr-btn-success" id="cgr_open_groups" data-url="{{ url('/customer-group') }}">
                        <i class="bi bi-box-arrow-up-right"></i> Open Customer Group
                    </button>
                </div>
            </div>
        </aside>
    </div>

    {{-- Customer Group Analytics --}}
    <div class="cgr-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Customer Group Analytics</h3>
                <p>Top groups on this page · sales contribution from existing total sell</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="cgr-charts-grid">
                <div>
                    <h3 style="font-size:14px;font-weight:800;margin:0 0 10px;">Top Customer Groups</h3>
                    <ul class="cgr-rank-list" id="cgr_top_groups_list"></ul>
                </div>
                <div>
                    <h3 style="font-size:14px;font-weight:800;margin:0 0 10px;">Fastest Growing / Outstanding</h3>
                    <div class="cgr-placeholder">
                        <i class="bi bi-speedometer2"></i>
                        <strong>Growth &amp; outstanding analysis</strong>
                        <small>UI placeholder — this report does not return growth or receivable fields.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Performance --}}
    <div class="cgr-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Customer Performance</h3>
                <p>Placeholders until customer-level metrics exist on this endpoint</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="cgr-summary-grid">
                <div class="cgr-summary-item"><span>Top Customers by Group</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Revenue per Customer</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Payment Collection</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Repeat Customers</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Customer Retention</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Purchase Frequency</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Average Customer Value</span><strong>—</strong></div>
                <div class="cgr-summary-item"><span>Groups on this page</span><strong id="cgr_kpi_page_groups">—</strong></div>
            </div>
        </div>
    </div>

    {{-- BI Charts — Chart.js only for existing name + total_sell --}}
    <div class="cgr-card cgr-bi-section no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Business Intelligence Charts</h3>
                <p>Sales by group uses this page’s DataTable rows. Other series need backend data that is not on this report.</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="cgr-charts-grid">
                <div class="cgr-chart-box">
                    <h3 style="font-size:14px;font-weight:800;margin:0 0 8px;">Sales by Customer Group</h3>
                    <canvas id="cgr_chart_sales_by_group" height="220" aria-label="Sales by customer group"></canvas>
                </div>
                <div class="cgr-chart-box">
                    <h3 style="font-size:14px;font-weight:800;margin:0 0 8px;">Revenue Distribution</h3>
                    <canvas id="cgr_chart_distribution" height="220" aria-label="Revenue distribution"></canvas>
                </div>
                <div class="cgr-chart-box">
                    <h3 style="font-size:14px;font-weight:800;margin:0 0 8px;">Top 10 Customer Groups</h3>
                    <canvas id="cgr_chart_top10" height="220" aria-label="Top 10 customer groups"></canvas>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-calendar3"></i>
                    <strong>Monthly Revenue by Group</strong>
                    <small>UI placeholder — no monthly series in this report.</small>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-person-plus"></i>
                    <strong>Monthly Customer Growth</strong>
                    <small>UI placeholder — customer counts are not returned here.</small>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-wallet2"></i>
                    <strong>Outstanding Receivables</strong>
                    <small>UI placeholder — receivables are not in this dataset.</small>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-cash-coin"></i>
                    <strong>Payment Collection</strong>
                    <small>UI placeholder — payments are not in this dataset.</small>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-geo-alt"></i>
                    <strong>Customer Distribution</strong>
                    <small>UI placeholder — geography is not in this dataset.</small>
                </div>
                <div class="cgr-placeholder">
                    <i class="bi bi-graph-up"></i>
                    <strong>Average Order Value Trend</strong>
                    <small>UI placeholder — order-level metrics are not in this dataset.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Business Location Comparison --}}
    <div class="cgr-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Business Location Comparison</h3>
                <p>Current location filter plus page-level sales from the existing report</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="table-responsive">
                <table class="cgr-compare-table">
                    <thead>
                        <tr>
                            <th>Business Location</th>
                            <th>Customer Groups</th>
                            <th>Customers</th>
                            <th>Sales / Revenue</th>
                            <th>Outstanding</th>
                            <th>Collections</th>
                            <th>Average Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="cgr_cmp_location">—</td>
                            <td id="cgr_cmp_groups">—</td>
                            <td>—</td>
                            <td id="cgr_cmp_sales">—</td>
                            <td>—</td>
                            <td>—</td>
                            <td id="cgr_cmp_avg">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="cgr-note">Per-location breakdown is not returned by this report. The row reflects the selected location filter and current page totals.</p>
        </div>
    </div>

    {{-- Export Center --}}
    <div class="cgr-card no-print">
        <div class="cgr-card-head">
            <div>
                <h3>Export Center</h3>
                <p>Uses the existing DataTables export buttons when the user has export permission</p>
            </div>
        </div>
        <div class="cgr-card-body">
            <div class="cgr-export-grid">
                <button type="button" class="cgr-btn" id="cgr_export_print"><i class="bi bi-printer"></i> Print Report</button>
                <button type="button" class="cgr-btn" id="cgr_export_excel_alt"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button type="button" class="cgr-btn" id="cgr_export_pdf_alt"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                <button type="button" class="cgr-btn" id="cgr_export_csv"><i class="bi bi-filetype-csv"></i> Export CSV</button>
                <button type="button" class="cgr-btn" id="cgr_download_charts"><i class="bi bi-image"></i> Download Charts</button>
                <button type="button" class="cgr-btn" id="cgr_email_report_alt"><i class="bi bi-envelope"></i> Email Report</button>
            </div>
        </div>
    </div>

    <div class="cgr-quick-bar no-print" aria-label="Quick actions">
        <button type="button" class="cgr-btn" id="cgr_refresh_alt"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        <button type="button" class="cgr-btn cgr-btn-primary" id="cgr_generate_footer"><i class="bi bi-play-fill"></i> Generate Report</button>
        <span class="cgr-chip muted">Schedule Report · Email Scheduler · Executive CRM Dashboard — UI placeholders</span>
    </div>
</section>

<div class="cgr-drawer-backdrop" id="cgr_drawer_backdrop" aria-hidden="true"></div>
<aside class="cgr-drawer" id="cgr_drawer" role="dialog" aria-modal="true" aria-labelledby="cgr_drawer_name" aria-hidden="true">
    <div class="cgr-drawer-head">
        <div>
            <p class="cgr-subtitle" style="margin:0;">Customer Group Profile</p>
            <h3 id="cgr_drawer_name">—</h3>
        </div>
        <button type="button" class="cgr-btn cgr-btn-ghost" id="cgr_drawer_close" aria-label="Close profile">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cgr-preview-rows">
        <div><span>Group Name</span><strong id="cgr_drawer_name_dup"> </strong></div>
        <div><span>Business Location</span><strong id="cgr_drawer_location">—</strong></div>
        <div><span>Date Range</span><strong id="cgr_drawer_range">—</strong></div>
        <div><span>Total Sell / Revenue</span><strong id="cgr_drawer_sales">—</strong></div>
        <div><span>Group Code</span><strong>—</strong></div>
        <div><span>Customer Count</span><strong>—</strong></div>
        <div><span>Outstanding Balance</span><strong>—</strong></div>
        <div><span>Payments</span><strong>—</strong></div>
        <div><span>Reward Points</span><strong>—</strong></div>
        <div><span>Credit Limits</span><strong>—</strong></div>
    </div>
    <p class="cgr-note">Read-only profile from this report’s two columns (group name and total sell). Description, top customers, recent transactions, and timeline require modules that are not part of this endpoint.</p>
</aside>

<div class="cgr-toast-host" id="cgr_toast_host" aria-live="polite" aria-atomic="true"></div>

@endsection

@section('javascript')

    <script type="text/javascript">
        $(document).ready(function(){
            if($('#cg_date_range').length == 1){
                $('#cg_date_range').daterangepicker(
                    dateRangeSettings,
                    function (start, end) {
                        $('#cg_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                        cg_report_table.ajax.reload();
                    }
                );

                $('#cg_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    cg_report_table.ajax.reload();
                });
            }

            cg_report_table = $('#cg_report_table').DataTable({
                            processing: true,
                            serverSide: true,
                            fixedHeader:false,
                            "ajax": {
                                "url": "/reports/customer-group",
                                "data": function ( d ) {
                                    d.location_id = $('#cg_location_id').val();
                                    d.customer_group_id = $('#cg_customer_group_id').val();
                                    d.start_date = $('#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                                    d.end_date = $('#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                                }
                            },
                            columns: [
                                {data: 'name', name: 'CG.name'},
                                {data: 'total_sell', name: 'total_sell', searchable: false}
                            ],
                            "fnDrawCallback": function (oSettings) {
                                __currency_convert_recursively($('#cg_report_table'));
                            }
                        });
            //Customer Group report filter
            $('select#cg_location_id, select#cg_customer_group_id, #cg_date_range').change( function(){
                cg_report_table.ajax.reload();
            });
        })
    </script>
    <script src="{{ asset('js/customer-group-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
