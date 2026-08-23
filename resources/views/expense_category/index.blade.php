@extends('layouts.app')
@section('title', __('expense.expense_categories'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/expense-categories-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content ec-shell">
    {{-- Sticky Header --}}
    <div class="ec-header" role="banner">
        <div class="ec-header-left">
            <h1>@lang('expense.expense_categories')</h1>
            <p class="ec-subtitle">@lang('expense.manage_your_expense_categories')</p>
            <div class="ec-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Finance</span>
                <span>/</span>
                <span>@lang('expense.expense_categories')</span>
            </div>
        </div>
        <div class="ec-header-actions">
            <div class="ec-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="ec_quick_search" class="form-control" placeholder="Search categories…" aria-label="Search categories" autocomplete="off">
            </div>
            <button type="button" class="ec-btn ec-btn-ghost" id="ec_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ec-btn ec-btn-ghost" id="ec_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="ec-btn ec-btn-ghost" id="ec_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="ec-btn ec-btn-primary btn-modal"
                data-href="{{action([\App\Http\Controllers\ExpenseCategoryController::class, 'create'])}}"
                data-container=".expense_category_modal">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="ec-kpi-grid" aria-label="Expense category KPIs">
        <div class="ec-kpi tone-blue">
            <div class="ec-kpi-icon"><i class="fas fa-folder-open"></i></div>
            <span class="ec-kpi-label">Total Categories</span>
            <span class="ec-kpi-value" id="ec_kpi_total">—</span>
            <span class="ec-kpi-hint">All category records</span>
        </div>
        <div class="ec-kpi tone-teal">
            <div class="ec-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="ec-kpi-label">Filtered</span>
            <span class="ec-kpi-value" id="ec_kpi_filtered">—</span>
            <span class="ec-kpi-hint">Matching search</span>
        </div>
        <div class="ec-kpi tone-violet">
            <div class="ec-kpi-icon"><i class="fas fa-list"></i></div>
            <span class="ec-kpi-label">On This Page</span>
            <span class="ec-kpi-value" id="ec_kpi_page">—</span>
            <span class="ec-kpi-hint">Current page rows</span>
        </div>
        <div class="ec-kpi tone-green">
            <div class="ec-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="ec-kpi-label">Active</span>
            <span class="ec-kpi-value">—</span>
            <span class="ec-kpi-hint">UI placeholder</span>
        </div>
        <div class="ec-kpi tone-orange">
            <div class="ec-kpi-icon"><i class="fas fa-coins"></i></div>
            <span class="ec-kpi-label">Expenses Assigned</span>
            <span class="ec-kpi-value">—</span>
            <span class="ec-kpi-hint">Shown on expenses module</span>
        </div>
        <div class="ec-kpi tone-slate">
            <div class="ec-kpi-icon"><i class="fas fa-sitemap"></i></div>
            <span class="ec-kpi-label">Parent / Sub</span>
            <span class="ec-kpi-value">—</span>
            <span class="ec-kpi-hint">Via add-as-sub-cat</span>
        </div>
        <div class="ec-kpi tone-blue">
            <div class="ec-kpi-icon"><i class="fas fa-star"></i></div>
            <span class="ec-kpi-label">Most Used</span>
            <span class="ec-kpi-value">—</span>
            <span class="ec-kpi-hint">UI placeholder</span>
        </div>
        <div class="ec-kpi tone-red">
            <div class="ec-kpi-icon"><i class="fas fa-ban"></i></div>
            <span class="ec-kpi-label">Inactive</span>
            <span class="ec-kpi-value">—</span>
            <span class="ec-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="ec-future-strip" aria-label="Coming soon">
        <span class="ec-chip"><i class="fas fa-sitemap"></i> Multi-Level Hierarchy</span>
        <span class="ec-chip"><i class="fas fa-building"></i> Cost Center Map</span>
        <span class="ec-chip"><i class="fas fa-wallet"></i> Budget Allocation</span>
        <span class="ec-chip"><i class="fas fa-robot"></i> AI Classification</span>
        <span class="ec-chip"><i class="fas fa-history"></i> Audit Trail</span>
        <span class="ec-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="ec-main-grid">
        <div class="ec-main-col">
            <div class="ec-card">
                <div class="ec-card-head">
                    <div>
                        <h3>@lang('expense.all_your_expense_categories')</h3>
                        <p>Server-side DataTable · add / edit modal · delete</p>
                    </div>
                    <a class="ec-btn ec-btn-primary btn-modal"
                        data-href="{{action([\App\Http\Controllers\ExpenseCategoryController::class, 'create'])}}"
                        data-container=".expense_category_modal">
                        <i class="fas fa-plus"></i> @lang('messages.add')
                    </a>
                </div>
                <div class="ec-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="expense_category_table">
                            <thead>
                                <tr>
                                    <th>@lang('expense.category_name')</th>
                                    <th>@lang('expense.category_code')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="ec-side" aria-label="Category details">
            <div class="ec-preview-card">
                <h3>Category Details</h3>
                <p class="ec-preview-empty" id="ec_preview_empty">Select a category row to preview name, code, and quick actions (edit / delete).</p>
                <div id="ec_preview_content" style="display:none;">
                    <div class="ec-preview-rows">
                        <div><span>Name</span><strong id="ec_preview_name">—</strong></div>
                        <div><span>Code</span><strong id="ec_preview_code">—</strong></div>
                        <div><span>Parent</span><strong>—</strong></div>
                        <div><span>Status</span><strong>Active</strong></div>
                    </div>
                    <div class="ec-preview-actions" id="ec_preview_actions"></div>
                    <div class="ec-note">
                        Parent category is set in the add/edit modal via existing “add as sub category” option.
                    </div>
                </div>
            </div>

            <div class="ec-summary-card">
                <h3>Category Summary</h3>
                <div class="ec-summary-rows">
                    <div><span>Total</span><strong id="ec_sum_total">—</strong></div>
                    <div><span>Filtered</span><strong id="ec_sum_filtered">—</strong></div>
                    <div><span>On page</span><strong id="ec_sum_page">—</strong></div>
                </div>
                <div class="ec-note">
                    Totals from live DataTable (<code>/expense-categories</code>).
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade expense_category_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>
</section>

@endsection

@section('javascript')
<script src="{{ asset('js/expense-categories-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
