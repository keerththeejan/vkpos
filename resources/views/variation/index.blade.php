@extends('layouts.app')
@section('title', __('product.variations'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/variation-templates-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content vt-shell">
    {{-- Sticky Header --}}
    <div class="vt-header" role="banner">
        <div class="vt-header-left">
            <h1>@lang('product.variations')</h1>
            <p class="vt-subtitle">@lang('lang_v1.manage_product_variations')</p>
            <div class="vt-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('product.variations')</span>
            </div>
        </div>
        <div class="vt-header-actions">
            <div class="vt-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="vt_quick_search" class="form-control" placeholder="Search templates…" aria-label="Search variation templates" autocomplete="off">
            </div>
            <button type="button" class="vt-btn vt-btn-ghost" id="vt_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="vt-btn vt-btn-ghost" id="vt_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="vt-btn vt-btn-ghost" id="vt_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="vt-btn vt-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal vt-btn vt-btn-primary"
               data-href="{{ action([\App\Http\Controllers\VariationTemplateController::class, 'create']) }}"
               data-container=".variation_modal"
               id="vt_add_template_btn">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="vt-kpi-grid" aria-label="Variation template statistics">
        <div class="vt-kpi tone-blue">
            <div class="vt-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <span class="vt-kpi-label">Total Templates</span>
            <span class="vt-kpi-value" id="vt_kpi_total">—</span>
            <span class="vt-kpi-hint">Variation templates in catalog</span>
        </div>
        <div class="vt-kpi tone-green">
            <div class="vt-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="vt-kpi-label">Filtered</span>
            <span class="vt-kpi-value" id="vt_kpi_filtered">—</span>
            <span class="vt-kpi-hint">Matching current search</span>
        </div>
        <div class="vt-kpi tone-purple">
            <div class="vt-kpi-icon"><i class="fas fa-tags"></i></div>
            <span class="vt-kpi-label">Values (page)</span>
            <span class="vt-kpi-value" id="vt_kpi_values">—</span>
            <span class="vt-kpi-hint">Variant values on this page</span>
        </div>
        <div class="vt-kpi tone-orange">
            <div class="vt-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="vt-kpi-label">Selected</span>
            <span class="vt-kpi-value" id="vt_kpi_selected">0</span>
            <span class="vt-kpi-hint">Preview selection</span>
        </div>
        <div class="vt-kpi tone-teal">
            <div class="vt-kpi-icon"><i class="fas fa-bolt"></i></div>
            <span class="vt-kpi-label">Quick Add</span>
            <span class="vt-kpi-value">Ready</span>
            <span class="vt-kpi-hint">Create templates in one click</span>
        </div>
        <div class="vt-kpi tone-slate">
            <div class="vt-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="vt-kpi-label">Status</span>
            <span class="vt-kpi-value">Active</span>
            <span class="vt-kpi-hint">Reusable across products</span>
        </div>
    </div>

    <div class="vt-main-grid">
        {{-- Table + filters --}}
        <div class="vt-card vt-table-card">
            <div class="vt-card-head">
                <div>
                    <h3>@lang('lang_v1.all_variations')</h3>
                    <p>Search, review, edit, or delete variation templates. Existing DataTable &amp; AJAX logic is unchanged.</p>
                </div>
                <div class="vt-filter-bar">
                    <div class="vt-filter-field">
                        <label for="vt_filter_name">Template Name</label>
                        <input type="text" id="vt_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                    </div>
                    <div class="vt-filter-field">
                        <label for="vt_filter_values">Variant Values</label>
                        <input type="text" id="vt_filter_values" class="form-control" placeholder="e.g. XL, Red…" autocomplete="off">
                    </div>
                    <div class="vt-filter-actions">
                        <button type="button" class="vt-btn vt-btn-primary" id="vt_apply_filters">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button type="button" class="vt-btn" id="vt_reset_filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="vt-card-body">
                <div class="table-responsive vt-table-wrap">
                    <table class="table table-bordered table-striped" id="variation_table">
                        <thead>
                            <tr>
                                <th>@lang('product.variations')</th>
                                <th>@lang('lang_v1.values')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- Preview panel --}}
        <aside class="vt-card vt-preview-card" id="vt_preview_card" aria-live="polite">
            <div class="vt-card-head">
                <div>
                    <h3>Template Preview</h3>
                    <p>Select a row to inspect variant values.</p>
                </div>
            </div>
            <div class="vt-card-body">
                <div class="vt-preview-empty" id="vt_preview_empty">
                    <i class="fas fa-puzzle-piece"></i>
                    <p>Click any template row to preview its values here.</p>
                </div>
                <div class="vt-preview-content" id="vt_preview_content" style="display:none;">
                    <div class="vt-preview-title-row">
                        <h4 id="vt_preview_name">—</h4>
                        <span class="vt-pill success" id="vt_preview_status">Active</span>
                    </div>
                    <div class="vt-preview-meta">
                        <div>
                            <span class="k">Total Values</span>
                            <span class="v" id="vt_preview_count">0</span>
                        </div>
                        <div>
                            <span class="k">Linked Products</span>
                            <span class="v">See product editor</span>
                        </div>
                    </div>
                    <h5 class="vt-preview-section-title">Variants</h5>
                    <ul class="vt-preview-chips" id="vt_preview_chips"></ul>
                    <div class="vt-preview-actions" id="vt_preview_actions"></div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Preserved modal container --}}
    <div class="modal fade variation_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    {{-- Paste multiple values (UI helper — writes into existing variation_values[] inputs) --}}
    <div class="modal fade" id="vt_paste_modal" tabindex="-1" role="dialog" aria-labelledby="vt_paste_modal_title">
        <div class="modal-dialog" role="document">
            <div class="modal-content vt-modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="vt_paste_modal_title">
                        <i class="fas fa-paste"></i> Paste Multiple Values
                    </h4>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Paste one variation value per line. Blank lines are ignored.</p>
                    <textarea id="vt_paste_textarea" class="form-control" rows="10" placeholder="XS&#10;S&#10;M&#10;L&#10;XL&#10;XXL"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vt-btn" data-dismiss="modal">Cancel</button>
                    <button type="button" class="vt-btn vt-btn-primary" id="vt_paste_apply">
                        <i class="fas fa-check"></i> Add Values
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/variation-templates-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
