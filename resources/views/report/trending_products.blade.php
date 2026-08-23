@extends('layouts.app')
@section('title', __('report.trending_products'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/trending-products-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $tp_labels = is_array($chart->labels ?? null) ? $chart->labels : [];
    $tp_values = [];
    if (!empty($chart->datasets[0]) && is_object($chart->datasets[0]) && isset($chart->datasets[0]->values)) {
        $tp_values = $chart->datasets[0]->values;
    }
    $tp_rows = [];
    $tp_total_units = 0;
    foreach ($tp_labels as $i => $label) {
        $sold = isset($tp_values[$i]) ? (float) $tp_values[$i] : 0;
        $tp_total_units += $sold;
        $product = $label;
        $sku = '';
        $unit = '';
        if (preg_match('/^(.*) - (.+) \(([^)]+)\)\s*$/u', $label, $m)) {
            $product = $m[1];
            $sku = $m[2];
            $unit = $m[3];
        }
        $tp_rows[] = [
            'rank' => $i + 1,
            'label' => $label,
            'product' => $product,
            'sku' => $sku,
            'unit' => $unit,
            'sold' => $sold,
        ];
    }
    $tp_top = $tp_rows[0] ?? null;
    $tp_count = count($tp_rows);
    $tp_limit = request('limit', 5);
@endphp

@section('content')

<section class="content tp-shell" id="tp_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.trending_products') }}</h2>
        <p>
            {{ __('purchase.business_location') }}: <span class="tp-print-location">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="tp-print-range">—</span>
        </p>
    </div>

    <div class="tp-header no-print" role="banner">
        <div class="tp-header-left">
            <h1>
                <span class="tp-title-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                {{ __('report.trending_products') }}
            </h1>
            <p class="tp-subtitle">Product demand, sales performance and inventory movement</p>
            <nav class="tp-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.trending_products') }}</span>
            </nav>
        </div>
        <div class="tp-header-actions">
            <div class="tp-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="tp_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} product, SKU…" aria-label="Search trending products" autocomplete="off">
                <button type="button" class="tp-search-clear" id="tp_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <button type="button" class="tp-btn tp-btn-icon" id="tp_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="tp-btn tp-btn-icon" id="tp_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="tp_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="tp-btn tp-btn-icon" id="tp_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="tp-btn" id="tp_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button type="button" class="tp-btn tp-btn-primary" id="tp_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="tp-header-meta" aria-label="Active filters">
            <div class="tp-meta"><span>{{ __('purchase.business_location') }}</span> <strong id="tp_meta_location">—</strong></div>
            <div class="tp-meta"><span>{{ __('report.date_range') }}</span> <strong id="tp_meta_range">—</strong></div>
            <div class="tp-meta"><span>{{ __('lang_v1.no_of_products') }}</span> <strong id="tp_meta_limit">{{ $tp_limit }}</strong></div>
        </div>
        <div class="tp-settings-panel" id="tp_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="tp_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="tp_set_hide_chart"> Hide chart</label>
            <label><input type="checkbox" id="tp_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="tp-kpi-grid no-print" aria-label="Product performance summary">
        <div class="tp-kpi tone-blue">
            <div class="tp-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="tp-kpi-label">{{ __('report.top_trending_products') }}</span>
            <span class="tp-kpi-value">{{ $tp_count }}</span>
            <span class="tp-kpi-hint">Products in this list</span>
        </div>
        <div class="tp-kpi tone-green">
            <div class="tp-kpi-icon"><i class="bi bi-trophy"></i></div>
            <span class="tp-kpi-label">Top selling product</span>
            <span class="tp-kpi-value" title="{{ $tp_top['product'] ?? '—' }}">{{ $tp_top['product'] ?? '—' }}</span>
            <span class="tp-kpi-hint">Rank #1 by units sold</span>
        </div>
        <div class="tp-kpi tone-violet">
            <div class="tp-kpi-icon"><i class="bi bi-box-seam"></i></div>
            <span class="tp-kpi-label">{{ __('report.total_unit_sold') }}</span>
            <span class="tp-kpi-value">{{ $tp_top ? rtrim(rtrim(number_format($tp_top['sold'], 4, '.', ','), '0'), '.') : '—' }}{{ !empty($tp_top['unit']) ? ' '.$tp_top['unit'] : '' }}</span>
            <span class="tp-kpi-hint">Top product quantity</span>
        </div>
        <div class="tp-kpi tone-teal">
            <div class="tp-kpi-icon"><i class="bi bi-plus-slash-minus"></i></div>
            <span class="tp-kpi-label">Units in list</span>
            <span class="tp-kpi-value">{{ rtrim(rtrim(number_format($tp_total_units, 4, '.', ','), '0'), '.') }}</span>
            <span class="tp-kpi-hint">Sum of shown products</span>
        </div>
    </div>

    @if($tp_top)
    <div class="tp-exception-strip no-print" role="status">
        <span class="tp-chip amber">#1 {{ $tp_top['product'] }} · {{ rtrim(rtrim(number_format($tp_top['sold'], 4, '.', ','), '0'), '.') }} {{ $tp_top['unit'] }}</span>
    </div>
    @endif

    <div class="tp-card tp-filters-card no-print">
        <div class="tp-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>Location, category, brand, unit, type and date range submit the existing report</p>
            </div>
            <button type="button" class="tp-btn tp-btn-ghost" id="tp_filters_toggle" aria-expanded="true" aria-controls="tp_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="tp-card-body" id="tp_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getTrendingProducts']), 'method' => 'get', 'id' => 'trending_products_filter_form']) !!}
                <div class="tp-filter-grid">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, request('location_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'aria-label' => __('purchase.business_location')]); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('category_id', __('product.category') . ':') !!}
                        {!! Form::select('category', $categories, request('category'), ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), request('sub_category'), ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, request('brand'), ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('unit', __('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, request('unit'), ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('trending_product_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', request('date_range'), ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'trending_product_date_range', 'readonly']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('limit', __('lang_v1.no_of_products') . ':') !!} @show_tooltip(__('tooltip.no_of_products_for_trending_products'))
                        {!! Form::number('limit', request('limit', 5), ['placeholder' => __('lang_v1.no_of_products'), 'class' => 'form-control', 'min' => 1, 'id' => 'limit']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('product_type', __('product.product_type') . ':') !!}
                        {!! Form::select('product_type', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')], request()->input('product_type'), ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="tp-period-chips" id="tp_period_chips" role="group" aria-label="Date period">
                    <button type="button" class="tp-chip" data-range="today">Today</button>
                    <button type="button" class="tp-chip" data-range="yesterday">Yesterday</button>
                    <button type="button" class="tp-chip" data-range="last_7_days">7 Days</button>
                    <button type="button" class="tp-chip" data-range="last_30_days">30 Days</button>
                    <button type="button" class="tp-chip" data-range="this_month">This Month</button>
                    <button type="button" class="tp-chip" data-range="this_year">This Year</button>
                    <button type="button" class="tp-chip" data-range="custom" id="tp_period_custom">Custom</button>
                </div>

                <div class="tp-filter-actions">
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="bi bi-funnel"></i> @lang('report.apply_filters')
                    </button>
                    <button type="button" class="tp-btn" id="tp_reset_filters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    <button type="button" class="tp-btn" id="tp_focus_search">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="tp-card tp-table-card">
        <div class="tp-card-head">
            <div>
                <h2>{{ __('report.top_trending_products') }}</h2>
                <p>Ranked by {{ __('report.total_unit_sold') }} — same data as the chart</p>
            </div>
        </div>
        <div class="tp-card-body tp-table-body">
            @if($tp_count === 0)
                <div class="tp-empty" id="tp_empty">
                    <div class="tp-empty-icon" aria-hidden="true">📦</div>
                    <strong>No trending products found</strong>
                    <p>Try changing your filters or date range.</p>
                    <button type="button" class="tp-btn tp-btn-primary" id="tp_empty_clear">Clear Filters</button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered" id="trending_products_table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>@lang('business.product')</th>
                                <th>SKU</th>
                                <th>@lang('product.unit')</th>
                                <th>@lang('report.total_unit_sold')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tp_rows as $row)
                            <tr data-search="{{ strtolower($row['product'].' '.$row['sku']) }}"
                                data-product="{{ $row['product'] }}"
                                data-sku="{{ $row['sku'] }}"
                                data-unit="{{ $row['unit'] }}"
                                data-sold="{{ $row['sold'] }}"
                                data-rank="{{ $row['rank'] }}">
                                <td>
                                    <span class="tp-rank {{ $row['rank'] <= 3 ? 'top-'.$row['rank'] : '' }}">#{{ $row['rank'] }}</span>
                                </td>
                                <td>
                                    <strong>{{ $row['product'] }}</strong>
                                </td>
                                <td>{{ $row['sku'] }}</td>
                                <td>{{ $row['unit'] }}</td>
                                <td class="tp-sold">{{ rtrim(rtrim(number_format($row['sold'], 4, '.', ','), '0'), '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="tp-page-note">Quantities come from the existing trending-products query. No extra calculations.</p>
            @endif
        </div>
    </div>

    <details class="tp-card tp-chart-card" open>
        <summary class="tp-card-head no-print">
            <div>
                <h2>{{ __('report.top_trending_products') }} @show_tooltip(__('tooltip.top_trending_products'))</h2>
                <p>Existing Highcharts column chart</p>
            </div>
        </summary>
        <div class="tp-card-body tp-chart-body">
            {!! $chart->container() !!}
        </div>
    </details>

    <div class="tp-export-bar no-print">
        <button type="button" class="tp-btn tp-btn-primary" id="tp_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="tp-drawer" id="tp_drawer" role="dialog" aria-modal="true" aria-labelledby="tp_drawer_title" aria-hidden="true">
    <div class="tp-drawer-head">
        <div>
            <h3 id="tp_drawer_title">Product</h3>
            <p class="tp-drawer-sku" id="tp_drawer_sku">—</p>
        </div>
        <button type="button" class="tp-btn tp-btn-icon" id="tp_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="tp-drawer-status" id="tp_drawer_status"></div>
    <dl class="tp-drawer-dl">
        <div><dt>Rank</dt><dd id="tp_drawer_rank">—</dd></div>
        <div><dt>SKU</dt><dd id="tp_drawer_sku2">—</dd></div>
        <div><dt>@lang('product.unit')</dt><dd id="tp_drawer_unit">—</dd></div>
        <div><dt>@lang('report.total_unit_sold')</dt><dd id="tp_drawer_sold">—</dd></div>
    </dl>
</aside>
<div class="tp-drawer-backdrop" id="tp_drawer_backdrop" hidden></div>
<div class="tp-toast-host" id="tp_toast_host" aria-live="polite"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    {!! $chart->script() !!}
    <script src="{{ asset('js/trending-products-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
