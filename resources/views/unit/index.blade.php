@extends('layouts.app')
@section('title', __('unit.units'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/units-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content um-shell">
    {{-- Sticky Header --}}
    <div class="um-header" role="banner">
        <div class="um-header-left">
            <h1>@lang('unit.units')</h1>
            <p class="um-subtitle">@lang('unit.manage_your_units')</p>
            <div class="um-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('unit.units')</span>
            </div>
        </div>
        <div class="um-header-actions">
            <div class="um-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="um_quick_search" class="form-control" placeholder="Search units…" aria-label="Search units" autocomplete="off">
            </div>
            <button type="button" class="um-btn um-btn-ghost" id="um_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="um-btn um-btn-ghost" id="um_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="um-btn um-btn-ghost" id="um_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="um-btn um-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            @can('unit.create')
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal um-btn um-btn-primary"
                    data-href="{{ action([\App\Http\Controllers\UnitController::class, 'create']) }}"
                    data-container=".unit_modal"
                    id="um_add_btn">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="um-kpi-grid" aria-label="Units summary">
        <div class="um-kpi tone-blue">
            <div class="um-kpi-icon"><i class="fas fa-ruler-combined"></i></div>
            <span class="um-kpi-label">Total Units</span>
            <span class="um-kpi-value" id="um_kpi_total">—</span>
            <span class="um-kpi-hint">Configured units of measure</span>
        </div>
        <div class="um-kpi tone-green">
            <div class="um-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="um-kpi-label">Filtered</span>
            <span class="um-kpi-value" id="um_kpi_filtered">—</span>
            <span class="um-kpi-hint">Matching current search</span>
        </div>
        <div class="um-kpi tone-purple">
            <div class="um-kpi-icon"><i class="fas fa-link"></i></div>
            <span class="um-kpi-label">Multi-unit</span>
            <span class="um-kpi-value" id="um_kpi_multi">—</span>
            <span class="um-kpi-hint">With base unit conversion (this page)</span>
        </div>
        <div class="um-kpi tone-orange">
            <div class="um-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="um-kpi-label">Selected</span>
            <span class="um-kpi-value" id="um_kpi_selected">0</span>
            <span class="um-kpi-hint">Preview selection</span>
        </div>
        <div class="um-kpi tone-teal">
            <div class="um-kpi-icon"><i class="fas fa-calculator"></i></div>
            <span class="um-kpi-label">Decimals</span>
            <span class="um-kpi-value">Supported</span>
            <span class="um-kpi-hint">Allow decimal per unit</span>
        </div>
        <div class="um-kpi tone-slate">
            <div class="um-kpi-icon"><i class="fas fa-box-open"></i></div>
            <span class="um-kpi-label">Products</span>
            <span class="um-kpi-value">Linked</span>
            <span class="um-kpi-hint">Used across product catalog</span>
        </div>
    </div>

    <div class="um-main-grid">
        <div class="um-card um-table-card">
            <div class="um-card-head">
                <div>
                    <h3>@lang('unit.all_your_units')</h3>
                    <p>Manage base units and multi-unit conversions. Existing DataTable &amp; AJAX logic is unchanged.</p>
                </div>
                <div class="um-filter-bar">
                    <div class="um-filter-field">
                        <label for="um_filter_name">Unit Name</label>
                        <input type="text" id="um_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                    </div>
                    <div class="um-filter-field">
                        <label for="um_filter_short">Short Name</label>
                        <input type="text" id="um_filter_short" class="form-control" placeholder="e.g. pc, kg…" autocomplete="off">
                    </div>
                    <div class="um-filter-actions">
                        <button type="button" class="um-btn um-btn-primary" id="um_apply_filters">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button type="button" class="um-btn" id="um_reset_filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="um-card-body">
                @can('unit.view')
                    <div class="table-responsive um-table-wrap">
                        <table class="table table-bordered table-striped" id="unit_table">
                            <thead>
                                <tr>
                                    <th>@lang('unit.name')</th>
                                    <th>@lang('unit.short_name')</th>
                                    <th>@lang('unit.allow_decimal') @show_tooltip(__('tooltip.unit_allow_decimal'))</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcan
            </div>
        </div>

        <aside class="um-side-stack">
            <div class="um-card um-preview-card" id="um_preview_card" aria-live="polite">
                <div class="um-card-head">
                    <div>
                        <h3>Unit Preview</h3>
                        <p>Select a row to inspect details.</p>
                    </div>
                </div>
                <div class="um-card-body">
                    <div class="um-preview-empty" id="um_preview_empty">
                        <i class="fas fa-ruler"></i>
                        <p>Click any unit row to preview conversion info and actions.</p>
                    </div>
                    <div class="um-preview-content" id="um_preview_content" style="display:none;">
                        <div class="um-preview-title-row">
                            <h4 id="um_preview_name">—</h4>
                            <span class="um-pill" id="um_preview_short">—</span>
                        </div>
                        <div class="um-preview-meta">
                            <div>
                                <span class="k">Allow Decimal</span>
                                <span class="v" id="um_preview_decimal">—</span>
                            </div>
                            <div>
                                <span class="k">Conversion</span>
                                <span class="v" id="um_preview_conversion">Base unit</span>
                            </div>
                        </div>
                        <h5 class="um-preview-section-title">Quick Actions</h5>
                        <div class="um-preview-actions" id="um_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="um-card um-examples-card">
                <div class="um-card-head">
                    <div>
                        <h3>Conversion Examples</h3>
                        <p>Visual reference only — calculations stay in existing logic.</p>
                    </div>
                </div>
                <div class="um-card-body">
                    <ul class="um-example-list">
                        <li><span>1 Box</span><span>=</span><span>12 Pieces</span></li>
                        <li><span>1 Carton</span><span>=</span><span>24 Boxes</span></li>
                        <li><span>1 Kg</span><span>=</span><span>1000 g</span></li>
                        <li><span>1 Liter</span><span>=</span><span>1000 ml</span></li>
                        <li><span>1 Dozen</span><span>=</span><span>12 Pieces</span></li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    {{-- Preserved modal container --}}
    <div class="modal fade unit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/units-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
