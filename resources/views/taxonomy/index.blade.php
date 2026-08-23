@extends('layouts.app')
@php
    $heading = !empty($module_category_data['heading']) ? $module_category_data['heading'] : __('category.categories');
    $navbar = !empty($module_category_data['navbar']) ? $module_category_data['navbar'] : null;
    $cat_code_enabled =
        isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code']
            ? false
            : true;
    $can_add = true;
    if (request()->get('type') == 'product' && !auth()->user()->can('category.create')) {
        $can_add = false;
    }
    $taxonomy_type = request()->get('type');
@endphp
@section('title', $heading)

@section('css')
<link rel="stylesheet" href="{{ asset('css/taxonomy-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
    @if (!empty($navbar))
        @include($navbar)
    @endif

<section class="content tax-shell" data-cat-code-enabled="{{ $cat_code_enabled ? '1' : '0' }}">
    <input type="hidden" id="category_type" value="{{ $taxonomy_type }}">

    {{-- Sticky Header --}}
    <div class="tax-header" role="banner">
        <div class="tax-header-left">
            <h1>
                {{ $heading }}
                @if (isset($module_category_data['heading_tooltip']))
                    @show_tooltip($module_category_data['heading_tooltip'])
                @endif
            </h1>
            <p class="tax-subtitle">{{ $module_category_data['sub_heading'] ?? __('category.manage_your_categories') }}</p>
            <div class="tax-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>{{ $heading }}</span>
            </div>
        </div>
        <div class="tax-header-actions">
            <div class="tax-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="tax_quick_search" class="form-control" placeholder="Search categories…" aria-label="Search categories" autocomplete="off">
            </div>
            <button type="button" class="tax-btn tax-btn-ghost" id="tax_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="tax-btn tax-btn-ghost" id="tax_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="tax-btn tax-btn-ghost" id="tax_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @if($taxonomy_type === 'product')
                <a class="tax-btn tax-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                    <i class="fas fa-cog"></i>
                </a>
            @endif
            @if ($can_add)
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal tax-btn tax-btn-primary"
                    data-href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'create']) }}?type={{ $taxonomy_type }}"
                    data-container=".category_modal"
                    id="tax_add_btn">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endif
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="tax-kpi-grid" aria-label="Category summary">
        <div class="tax-kpi tone-blue">
            <div class="tax-kpi-icon"><i class="fas fa-folder"></i></div>
            <span class="tax-kpi-label">Total</span>
            <span class="tax-kpi-value" id="tax_kpi_total">—</span>
            <span class="tax-kpi-hint">All categories</span>
        </div>
        <div class="tax-kpi tone-green">
            <div class="tax-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="tax-kpi-label">Filtered</span>
            <span class="tax-kpi-value" id="tax_kpi_filtered">—</span>
            <span class="tax-kpi-hint">Matching search</span>
        </div>
        <div class="tax-kpi tone-purple">
            <div class="tax-kpi-icon"><i class="fas fa-sitemap"></i></div>
            <span class="tax-kpi-label">Parents</span>
            <span class="tax-kpi-value" id="tax_kpi_parents">—</span>
            <span class="tax-kpi-hint">On this page</span>
        </div>
        <div class="tax-kpi tone-orange">
            <div class="tax-kpi-icon"><i class="fas fa-folder-open"></i></div>
            <span class="tax-kpi-label">Subcategories</span>
            <span class="tax-kpi-value" id="tax_kpi_children">—</span>
            <span class="tax-kpi-hint">On this page</span>
        </div>
        <div class="tax-kpi tone-teal">
            <div class="tax-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="tax-kpi-label">Selected</span>
            <span class="tax-kpi-value" id="tax_kpi_selected">0</span>
            <span class="tax-kpi-hint">Preview selection</span>
        </div>
        <div class="tax-kpi tone-slate">
            <div class="tax-kpi-icon"><i class="fas fa-box"></i></div>
            <span class="tax-kpi-label">Products</span>
            <span class="tax-kpi-value">Linked</span>
            <span class="tax-kpi-hint">Assigned in product editor</span>
        </div>
    </div>

    <div class="tax-main-grid">
        {{-- Left: category tree (built from DataTable rows — UI only) --}}
        <aside class="tax-card tax-tree-card">
            <div class="tax-card-head">
                <div>
                    <h3>Category Tree</h3>
                    <p>Expand parents and jump to a row.</p>
                </div>
                <div class="tax-tree-tools">
                    <button type="button" class="tax-btn tax-btn-sm" id="tax_tree_expand" title="Expand all">Expand</button>
                    <button type="button" class="tax-btn tax-btn-sm" id="tax_tree_collapse" title="Collapse all">Collapse</button>
                </div>
            </div>
            <div class="tax-card-body">
                <div class="tax-tree-search">
                    <i class="fas fa-search"></i>
                    <input type="search" id="tax_tree_search" class="form-control" placeholder="Filter tree…" autocomplete="off" aria-label="Filter category tree">
                </div>
                <div class="tax-tree" id="tax_tree" role="tree" aria-label="Categories">
                    <div class="tax-tree-empty" id="tax_tree_empty">Loading categories…</div>
                </div>
            </div>
        </aside>

        {{-- Right: table + preview --}}
        <div class="tax-right-stack">
            <div class="tax-card tax-table-card">
                <div class="tax-card-head">
                    <div>
                        <h3>
                            @if (!empty($module_category_data['taxonomy_label']))
                                {{ $module_category_data['taxonomy_label'] }}
                            @else
                                @lang('category.categories')
                            @endif
                        </h3>
                        <p>Existing DataTable &amp; AJAX logic is unchanged.</p>
                    </div>
                    <div class="tax-filter-bar">
                        <div class="tax-filter-field">
                            <label for="tax_filter_name">Name</label>
                            <input type="text" id="tax_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                        </div>
                        @if ($cat_code_enabled)
                        <div class="tax-filter-field">
                            <label for="tax_filter_code">{{ $module_category_data['taxonomy_code_label'] ?? __('category.code') }}</label>
                            <input type="text" id="tax_filter_code" class="form-control" placeholder="Filter by code…" autocomplete="off">
                        </div>
                        @endif
                        <div class="tax-filter-actions">
                            <button type="button" class="tax-btn tax-btn-primary" id="tax_apply_filters">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <button type="button" class="tax-btn" id="tax_reset_filters">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tax-card-body">
                    <div class="table-responsive tax-table-wrap">
                        <table class="table table-bordered table-striped" id="category_table">
                            <thead>
                                <tr>
                                    <th>
                                        @if (!empty($module_category_data['taxonomy_label']))
                                            {{ $module_category_data['taxonomy_label'] }}
                                        @else
                                            @lang('category.category')
                                        @endif
                                    </th>
                                    @if ($cat_code_enabled)
                                        <th>{{ $module_category_data['taxonomy_code_label'] ?? __('category.code') }}</th>
                                    @endif
                                    <th>@lang('lang_v1.description')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tax-card tax-preview-card" id="tax_preview_card" aria-live="polite">
                <div class="tax-card-head">
                    <div>
                        <h3>Category Preview</h3>
                        <p>Select a row or tree item to inspect details.</p>
                    </div>
                </div>
                <div class="tax-card-body">
                    <div class="tax-preview-empty" id="tax_preview_empty">
                        <i class="fas fa-folder"></i>
                        <p>Click a category to preview hierarchy and actions.</p>
                    </div>
                    <div class="tax-preview-content" id="tax_preview_content" style="display:none;">
                        <div class="tax-preview-title-row">
                            <h4 id="tax_preview_name">—</h4>
                            <span class="tax-pill" id="tax_preview_type">Category</span>
                        </div>
                        <div class="tax-preview-meta">
                            @if ($cat_code_enabled)
                            <div>
                                <span class="k">{{ $module_category_data['taxonomy_code_label'] ?? __('category.code') }}</span>
                                <span class="v" id="tax_preview_code">—</span>
                            </div>
                            @endif
                            <div>
                                <span class="k">@lang('lang_v1.description')</span>
                                <span class="v" id="tax_preview_desc">—</span>
                            </div>
                        </div>
                        <h5 class="tax-preview-section-title">Quick Actions</h5>
                        <div class="tax-preview-actions" id="tax_preview_actions"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preserved modal container --}}
    <div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection

@section('javascript')
    @includeIf('taxonomy.taxonomies_js')
    <script src="{{ asset('js/taxonomy-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
