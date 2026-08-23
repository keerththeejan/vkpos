@extends('layouts.app')
@section('title', __('report.expense_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/expense-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $exr_has_export = auth()->user()->can('view_export_buttons');
    $exr_rows = [];
    $total_expense = 0;
    $exr_top = null;
    foreach ($expenses as $expense) {
        $cat = $expense['category'] ?? __('report.others');
        $amt = (float) $expense['total_expense'];
        $total_expense += $amt;
        $row = [
            'category' => $cat,
            'total' => $amt,
        ];
        $exr_rows[] = $row;
        if ($exr_top === null || $amt > $exr_top['total']) {
            $exr_top = $row;
        }
    }
    $exr_count = count($exr_rows);
@endphp

@section('content')

<section class="content exr-shell" id="exr_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.expense_report') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="exr-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('category.category') }}: <span class="exr-print-category">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="exr-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('report.expense_report') }}</p>
    </div>

    <div class="exr-header no-print" role="banner">
        <div class="exr-header-left">
            <h1>
                <span class="exr-title-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                {{ __('report.expense_report') }}
            </h1>
            <p class="exr-subtitle">Expense activity, financial analysis and business spending</p>
            <nav class="exr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.expense_report') }}</span>
            </nav>
        </div>
        <div class="exr-header-actions">
            <div class="exr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="exr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} {{ __('expense.expense_categories') }}…" aria-label="Search expense report" autocomplete="off">
                <button type="button" class="exr-search-clear" id="exr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="exr-search-spinner" id="exr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="exr-btn exr-btn-icon" id="exr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="exr-btn exr-btn-icon" id="exr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="exr_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="exr-btn exr-btn-icon" id="exr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="exr-btn" id="exr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($exr_has_export)
            <div class="exr-export-wrap">
                <button type="button" class="exr-btn" id="exr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="exr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="exr-export-menu" id="exr_export_menu" role="menu" hidden>
                    <button type="button" class="exr-export-item" id="exr_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="exr-export-item" id="exr_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="exr-export-item" id="exr_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="exr-export-item" id="exr_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="exr-btn exr-btn-primary" id="exr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="exr-header-meta" aria-label="Active filters">
            <div class="exr-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="exr_meta_location">—</strong></div>
            <div class="exr-meta"><span>{{ __('category.category') }}</span> <strong id="exr_meta_category">—</strong></div>
            <div class="exr-meta"><span>{{ __('report.date_range') }}</span> <strong id="exr_meta_range">—</strong></div>
        </div>
        <div class="exr-settings-panel" id="exr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="exr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="exr_set_hide_chart"> Hide chart</label>
            <label><input type="checkbox" id="exr_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="exr-kpi-grid no-print" aria-label="Expense summary">
        <div class="exr-kpi tone-blue">
            <div class="exr-kpi-icon"><i class="bi bi-folder2"></i></div>
            <span class="exr-kpi-label">{{ __('expense.expense_categories') }}</span>
            <span class="exr-kpi-value">{{ $exr_count }}</span>
            <span class="exr-kpi-hint">Categories in this report</span>
        </div>
        <div class="exr-kpi tone-green">
            <div class="exr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="exr-kpi-label">{{ __('report.total_expense') }}</span>
            <span class="exr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $total_expense }}</span></span>
            <span class="exr-kpi-hint">Sum of shown categories</span>
        </div>
        <div class="exr-kpi tone-orange">
            <div class="exr-kpi-icon"><i class="bi bi-trophy"></i></div>
            <span class="exr-kpi-label">Highest category</span>
            <span class="exr-kpi-value" title="{{ $exr_top['category'] ?? '—' }}">{{ $exr_top['category'] ?? '—' }}</span>
            <span class="exr-kpi-hint">
                @if($exr_top)
                    <span class="display_currency" data-currency_symbol="true">{{ $exr_top['total'] }}</span>
                @else
                    —
                @endif
            </span>
        </div>
    </div>

    @if($exr_top)
    <div class="exr-exception-strip no-print" role="status">
        <span class="exr-chip amber">Highest expense category · {{ $exr_top['category'] }}</span>
    </div>
    @endif

    <div class="exr-card exr-filters-card no-print">
        <div class="exr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Location, category and date range submit the existing report</p>
            </div>
            <button type="button" class="exr-btn exr-btn-ghost" id="exr_filters_toggle" aria-expanded="true" aria-controls="exr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="exr-card-body" id="exr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getExpenseReport']), 'method' => 'get', 'id' => 'expense_report_form']) !!}
            <div class="exr-filter-grid">
                <div class="form-group">
                    {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, request('location_id'), ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('category_id', __('category.category').':') !!}
                    {!! Form::select('category', $categories, request('category'), ['placeholder' =>
                    __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('trending_product_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', request('date_range'), ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'trending_product_date_range', 'readonly']); !!}
                </div>
            </div>

            <div class="exr-period-chips" id="exr_period_chips" role="group" aria-label="Expense period">
                <button type="button" class="exr-chip" data-period="today">Today</button>
                <button type="button" class="exr-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="exr-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="exr-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="exr-chip" data-period="this_month">This Month</button>
                <button type="button" class="exr-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="exr-chip" data-period="this_year">This Year</button>
                <button type="button" class="exr-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="exr-filter-actions">
                <button type="submit" class="exr-btn exr-btn-primary">
                    <i class="bi bi-funnel"></i> @lang('report.apply_filters')
                </button>
                <button type="button" class="exr-btn" id="exr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="exr-btn" id="exr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($exr_has_export)
                <button type="button" class="exr-btn" id="exr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="exr-card exr-table-card {{ $exr_count === 0 ? 'is-empty' : '' }}">
        <div class="exr-card-head no-print">
            <div>
                <h2>{{ __('report.expense_report') }}</h2>
                <p>Expense totals by category — same data as the chart</p>
            </div>
        </div>
        <div class="exr-card-body exr-table-body">
            <div class="exr-empty no-print" id="exr_empty" @if($exr_count > 0) hidden @endif>
                <div class="exr-empty-icon" aria-hidden="true">💸</div>
                <strong>No expense records found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="exr-btn exr-btn-primary" id="exr_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="expense_report_table">
                    <thead>
                        <tr>
                            <th>@lang( 'expense.expense_categories' )</th>
                            <th>@lang( 'report.total_expense' )</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exr_rows as $row)
                            @php
                                $is_top = $exr_top && $row['category'] === $exr_top['category'] && $row['total'] === $exr_top['total'];
                                $share = ($total_expense > 0) ? max(0, min(100, ($row['total'] / $total_expense) * 100)) : 0;
                            @endphp
                            <tr class="{{ $is_top ? 'exr-row-top' : '' }}"
                                data-category="{{ $row['category'] }}"
                                data-total="{{ $row['total'] }}"
                                tabindex="0">
                                <td>
                                    <span class="exr-cat-name">{{ $row['category'] }}</span>
                                    @if($is_top)
                                        <span class="exr-badge exr-badge-low">HIGHEST</span>
                                    @endif
                                    <span class="exr-share" aria-hidden="true"><span style="width: {{ number_format($share, 2, '.', '') }}%"></span></span>
                                </td>
                                <td><span class="display_currency" data-currency_symbol="true">{{ $row['total'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>@lang('sale.total')</td>
                            <td><span class="display_currency" data-currency_symbol="true">{{ $total_expense }}</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="exr-page-note no-print">Totals come from the existing expense-report query. Category share bars are visual only and use this page’s category totals.</p>
        </div>
    </div>

    <details class="exr-card exr-chart-card" open>
        <summary class="exr-card-head no-print">
            <div>
                <h2>{{ __('report.expense_report') }}</h2>
                <p>Existing Highcharts column chart</p>
            </div>
        </summary>
        <div class="exr-card-body exr-chart-body">
            {!! $chart->container() !!}
        </div>
    </details>

    <div class="exr-export-bar no-print">
        @if($exr_has_export)
        <button type="button" class="exr-btn" id="exr_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="exr-btn" id="exr_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="exr-btn" id="exr_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="exr-btn exr-btn-primary" id="exr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="exr-drawer" id="exr_drawer" role="dialog" aria-modal="true" aria-labelledby="exr_drawer_title" aria-hidden="true">
    <div class="exr-drawer-head">
        <div>
            <h3 id="exr_drawer_title">Category</h3>
            <p class="exr-drawer-sku" id="exr_drawer_hint">Expense total for this category</p>
        </div>
        <button type="button" class="exr-btn exr-btn-icon" id="exr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <dl class="exr-drawer-dl">
        <div><dt>@lang('expense.expense_categories')</dt><dd id="exr_drawer_category">—</dd></div>
        <div><dt>@lang('report.total_expense')</dt><dd id="exr_drawer_total">—</dd></div>
    </dl>
</aside>
<div class="exr-drawer-backdrop" id="exr_drawer_backdrop" hidden></div>
<div class="exr-toast-host" id="exr_toast_host" aria-live="polite"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    {!! $chart->script() !!}
    <script src="{{ asset('js/expense-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
