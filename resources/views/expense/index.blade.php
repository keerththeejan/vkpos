@extends('layouts.app')
@section('title', __('expense.expenses'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/expenses-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content ex-shell">
    {{-- Sticky Header --}}
    <div class="ex-header" role="banner">
        <div class="ex-header-left">
            <h1>@lang('expense.expenses')</h1>
            <p class="ex-subtitle">Enterprise expense &amp; financial management</p>
            <div class="ex-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Finance</span>
                <span>/</span>
                <span>@lang('expense.expenses')</span>
            </div>
        </div>
        <div class="ex-header-actions">
            <div class="ex-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="ex_quick_search" class="form-control" placeholder="Search expenses…" aria-label="Search expenses" autocomplete="off">
            </div>
            <button type="button" class="ex-btn ex-btn-ghost" id="ex_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ex-btn ex-btn-ghost" id="ex_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="ex-btn ex-btn-ghost" id="ex_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('expense.add')
                <a class="ex-btn" href="{{action([\App\Http\Controllers\ExpenseController::class, 'importExpense'])}}">
                    <i class="fas fa-file-import"></i> @lang('expense.import_expense')
                </a>
                <a class="ex-btn ex-btn-primary" href="{{action([\App\Http\Controllers\ExpenseController::class, 'create'])}}">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTable / footer (UI only) --}}
    <div class="ex-kpi-grid" aria-label="Expense KPIs">
        <div class="ex-kpi tone-blue">
            <div class="ex-kpi-icon"><i class="fas fa-receipt"></i></div>
            <span class="ex-kpi-label">Total Expenses</span>
            <span class="ex-kpi-value" id="ex_kpi_total">—</span>
            <span class="ex-kpi-hint">All expense records</span>
        </div>
        <div class="ex-kpi tone-teal">
            <div class="ex-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="ex-kpi-label">Filtered</span>
            <span class="ex-kpi-value" id="ex_kpi_filtered">—</span>
            <span class="ex-kpi-hint">Matching filters</span>
        </div>
        <div class="ex-kpi tone-green">
            <div class="ex-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="ex-kpi-label">Paid (page)</span>
            <span class="ex-kpi-value" id="ex_kpi_paid">—</span>
            <span class="ex-kpi-hint">From payment status</span>
        </div>
        <div class="ex-kpi tone-orange">
            <div class="ex-kpi-icon"><i class="fas fa-adjust"></i></div>
            <span class="ex-kpi-label">Partial (page)</span>
            <span class="ex-kpi-value" id="ex_kpi_partial">—</span>
            <span class="ex-kpi-hint">From payment status</span>
        </div>
        <div class="ex-kpi tone-red">
            <div class="ex-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <span class="ex-kpi-label">Due (page)</span>
            <span class="ex-kpi-value" id="ex_kpi_due">—</span>
            <span class="ex-kpi-hint">From payment status</span>
        </div>
        <div class="ex-kpi tone-violet">
            <div class="ex-kpi-icon"><i class="fas fa-coins"></i></div>
            <span class="ex-kpi-label">Amount (page)</span>
            <span class="ex-kpi-value" id="ex_kpi_amount">—</span>
            <span class="ex-kpi-hint">From footer total</span>
        </div>
        <div class="ex-kpi tone-slate">
            <div class="ex-kpi-icon"><i class="fas fa-folder-open"></i></div>
            <span class="ex-kpi-label">Categories</span>
            <span class="ex-kpi-value">—</span>
            <span class="ex-kpi-hint">Use category filter</span>
        </div>
        <div class="ex-kpi tone-blue">
            <div class="ex-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <span class="ex-kpi-label">Pending Approvals</span>
            <span class="ex-kpi-value">—</span>
            <span class="ex-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="ex-future-strip" aria-label="Coming soon">
        <span class="ex-chip"><i class="fas fa-check-double"></i> Multi-Level Approval</span>
        <span class="ex-chip"><i class="fas fa-redo"></i> Recurring Expenses</span>
        <span class="ex-chip"><i class="fas fa-camera"></i> Receipt OCR</span>
        <span class="ex-chip"><i class="fas fa-robot"></i> AI Categorization</span>
        <span class="ex-chip"><i class="fas fa-chart-pie"></i> Budget Limits</span>
        <span class="ex-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="ex-main-grid">
        <div class="ex-main-col">
            {{-- Existing filters — IDs preserved for app.js --}}
            <div class="ex-card">
                <div class="ex-card-head">
                    <div>
                        <h3>@lang('report.filters')</h3>
                        <p>Location · category · date · payment status (existing AJAX filters)</p>
                    </div>
                </div>
                <div class="ex-card-body">
                    @component('components.filters', ['title' => __('report.filters')])
                        @if(auth()->user()->can('all_expense.access'))
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('expense_for', __('expense.expense_for').':') !!}
                                    {!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('expense_contact_filter',  __('contact.contact') . ':') !!}
                                    {!! Form::select('expense_contact_filter', $contacts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('expense_category_id',__('expense.expense_category').':') !!}
                                {!! Form::select('expense_category_id', $categories, null, ['placeholder' =>
                                __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'expense_category_id']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('expense_sub_category_id_filter',__('product.sub_category').':') !!}
                                {!! Form::select('expense_sub_category_id_filter', $sub_categories, null, ['placeholder' =>
                                __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'expense_sub_category_id_filter']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('expense_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('expense_payment_status',  __('purchase.payment_status') . ':') !!}
                                {!! Form::select('expense_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                    @endcomponent
                    <div class="ex-filter-actions">
                        <button type="button" class="ex-btn ex-btn-primary" id="ex_apply_filters"><i class="fas fa-check"></i> Apply</button>
                        <button type="button" class="ex-btn" id="ex_reset_filters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>

            <div class="ex-card">
                <div class="ex-card-head">
                    <div>
                        <h3>@lang('expense.all_expenses')</h3>
                        <p>Server-side DataTable · payments · delete · print</p>
                    </div>
                    @can('expense.add')
                        <a class="ex-btn ex-btn-primary" href="{{action([\App\Http\Controllers\ExpenseController::class, 'create'])}}">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endcan
                </div>
                <div class="ex-card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="expense_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.action')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('lang_v1.recur_details')</th>
                                    <th>@lang('expense.expense_category')</th>
                                    <th>@lang('product.sub_category')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('sale.payment_status')</th>
                                    <th>@lang('product.tax')</th>
                                    <th>@lang('sale.total_amount')</th>
                                    <th>@lang('purchase.payment_due')</th>
                                    <th>@lang('expense.expense_for')</th>
                                    <th>@lang('contact.contact')</th>
                                    <th>@lang('expense.expense_note')</th>
                                    <th>@lang('lang_v1.added_by')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center footer-total">
                                    <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                                    <td class="footer_payment_status_count"></td>
                                    <td></td>
                                    <td class="footer_expense_total"></td>
                                    <td class="footer_total_due"></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="ex-side" aria-label="Expense details">
            <div class="ex-preview-card">
                <h3>Expense Details</h3>
                <p class="ex-preview-empty" id="ex_preview_empty">Select an expense row to preview category, payment status, totals, and quick actions.</p>
                <div id="ex_preview_content" style="display:none;">
                    <div class="ex-preview-rows">
                        <div><span>Reference</span><strong id="ex_preview_ref">—</strong></div>
                        <div><span>Status</span><strong><span class="ex-badge" id="ex_preview_status_badge"><span id="ex_preview_status">—</span></span></strong></div>
                        <div><span>Date</span><strong id="ex_preview_date">—</strong></div>
                        <div><span>Category</span><strong id="ex_preview_category">—</strong></div>
                        <div><span>Sub Category</span><strong id="ex_preview_sub">—</strong></div>
                        <div><span>Location</span><strong id="ex_preview_location">—</strong></div>
                        <div><span>Tax</span><strong id="ex_preview_tax">—</strong></div>
                        <div><span>Total</span><strong id="ex_preview_total">—</strong></div>
                        <div><span>Due</span><strong id="ex_preview_due">—</strong></div>
                        <div><span>Expense For</span><strong id="ex_preview_for">—</strong></div>
                        <div><span>Contact</span><strong id="ex_preview_contact">—</strong></div>
                        <div><span>Notes</span><strong id="ex_preview_notes">—</strong></div>
                        <div><span>Added By</span><strong id="ex_preview_by">—</strong></div>
                    </div>
                    <div class="ex-preview-actions" id="ex_preview_actions"></div>
                    <div class="ex-note">
                        Attachments / approval timeline appear when already supported by existing actions.
                    </div>
                </div>
            </div>

            <div class="ex-summary-card">
                <h3>Financial Summary</h3>
                <div class="ex-summary-rows">
                    <div><span>Filtered</span><strong id="ex_sum_filtered">—</strong></div>
                    <div><span>Paid (page)</span><strong id="ex_sum_paid">—</strong></div>
                    <div><span>Due (page)</span><strong id="ex_sum_due">—</strong></div>
                    <div><span>Page Total</span><strong id="ex_sum_amount">—</strong></div>
                    <div><span>Page Due Amt</span><strong id="ex_sum_due_amt">—</strong></div>
                </div>
                <div class="ex-note">
                    Mirrors DataTable footer (<code>.footer_expense_total</code> / <code>.footer_total_due</code>).
                </div>
            </div>
        </aside>
    </div>
</section>

<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
 <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
 <script src="{{ asset('js/expenses-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
