@extends('layouts.app')
@section('title', 'Brands')

@section('css')
<link rel="stylesheet" href="{{ asset('css/brands-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content br-shell">
    {{-- Sticky Header --}}
    <div class="br-header" role="banner">
        <div class="br-header-left">
            <h1>@lang('brand.brands')</h1>
            <p class="br-subtitle">@lang('brand.manage_your_brands')</p>
            <div class="br-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('brand.brands')</span>
            </div>
        </div>
        <div class="br-header-actions">
            <div class="br-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="br_quick_search" class="form-control" placeholder="Search brands…" aria-label="Search brands" autocomplete="off">
            </div>
            <button type="button" class="br-btn br-btn-ghost" id="br_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="br-btn br-btn-ghost" id="br_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="br-btn br-btn-ghost" id="br_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="br-btn br-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            @can('brand.create')
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal br-btn br-btn-primary"
                    data-href="{{ action([\App\Http\Controllers\BrandController::class, 'create']) }}"
                    data-container=".brands_modal"
                    id="br_add_btn">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="br-kpi-grid" aria-label="Brands summary">
        <div class="br-kpi tone-blue">
            <div class="br-kpi-icon"><i class="fas fa-copyright"></i></div>
            <span class="br-kpi-label">Total Brands</span>
            <span class="br-kpi-value" id="br_kpi_total">—</span>
            <span class="br-kpi-hint">In your catalog</span>
        </div>
        <div class="br-kpi tone-green">
            <div class="br-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="br-kpi-label">Filtered</span>
            <span class="br-kpi-value" id="br_kpi_filtered">—</span>
            <span class="br-kpi-hint">Matching search</span>
        </div>
        <div class="br-kpi tone-purple">
            <div class="br-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="br-kpi-label">Selected</span>
            <span class="br-kpi-value" id="br_kpi_selected">0</span>
            <span class="br-kpi-hint">Preview selection</span>
        </div>
        <div class="br-kpi tone-orange">
            <div class="br-kpi-icon"><i class="fas fa-box"></i></div>
            <span class="br-kpi-label">Products</span>
            <span class="br-kpi-value">Linked</span>
            <span class="br-kpi-hint">Assigned in product editor</span>
        </div>
        <div class="br-kpi tone-teal">
            <div class="br-kpi-icon"><i class="fas fa-bolt"></i></div>
            <span class="br-kpi-label">Quick Add</span>
            <span class="br-kpi-value">Ready</span>
            <span class="br-kpi-hint">Also available on product create</span>
        </div>
        <div class="br-kpi tone-slate">
            <div class="br-kpi-icon"><i class="fas fa-store"></i></div>
            <span class="br-kpi-label">POS</span>
            <span class="br-kpi-value">Active</span>
            <span class="br-kpi-hint">Used in filters &amp; reports</span>
        </div>
    </div>

    <div class="br-main-grid">
        <div class="br-card br-table-card">
            <div class="br-card-head">
                <div>
                    <h3>@lang('brand.all_your_brands')</h3>
                    <p>Manage product brands. Existing DataTable &amp; AJAX logic is unchanged.</p>
                </div>
                <div class="br-filter-bar">
                    <div class="br-filter-field">
                        <label for="br_filter_name">Brand Name</label>
                        <input type="text" id="br_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                    </div>
                    <div class="br-filter-field">
                        <label for="br_filter_note">Note</label>
                        <input type="text" id="br_filter_note" class="form-control" placeholder="Filter by note…" autocomplete="off">
                    </div>
                    <div class="br-filter-actions">
                        <button type="button" class="br-btn br-btn-primary" id="br_apply_filters">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button type="button" class="br-btn" id="br_reset_filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="br-card-body">
                @can('brand.view')
                    <div class="table-responsive br-table-wrap">
                        <table class="table table-bordered table-striped" id="brands_table">
                            <thead>
                                <tr>
                                    <th>@lang('brand.brands')</th>
                                    <th>@lang('brand.note')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcan
            </div>
        </div>

        <aside class="br-card br-preview-card" id="br_preview_card" aria-live="polite">
            <div class="br-card-head">
                <div>
                    <h3>Brand Preview</h3>
                    <p>Select a row to inspect details.</p>
                </div>
            </div>
            <div class="br-card-body">
                <div class="br-preview-empty" id="br_preview_empty">
                    <i class="fas fa-copyright"></i>
                    <p>Click any brand row to preview details and actions.</p>
                </div>
                <div class="br-preview-content" id="br_preview_content" style="display:none;">
                    <div class="br-preview-title-row">
                        <h4 id="br_preview_name">—</h4>
                        <span class="br-pill success">Brand</span>
                    </div>
                    <p class="br-preview-desc" id="br_preview_desc">—</p>
                    <div class="br-preview-meta">
                        <div>
                            <span class="k">Usage</span>
                            <span class="v">Products · Filters · Reports</span>
                        </div>
                        <div>
                            <span class="k">Catalog</span>
                            <span class="v"><a href="{{ url('/products') }}">View products</a></span>
                        </div>
                    </div>
                    <h5 class="br-preview-section-title">Quick Actions</h5>
                    <div class="br-preview-actions" id="br_preview_actions"></div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Preserved modal container --}}
    <div class="modal fade brands_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/brands-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
