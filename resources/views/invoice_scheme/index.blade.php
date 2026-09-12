@extends('layouts.app')
@section('title', __('invoice.invoice_settings'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/invoice-schemes-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content is-shell">
    <div class="is-header" role="banner">
        <div class="is-header-left">
            <h1>@lang('invoice.invoice_schemes')</h1>
            <p class="is-subtitle">@lang('invoice.manage_your_invoices')</p>
        </div>
        <div class="is-header-actions">
            <button type="button" class="is-btn is-btn-ghost" id="is_refresh_table" title="@lang('lang_v1.refresh')" aria-label="@lang('lang_v1.refresh')">
                <i class="fa fa-refresh"></i>
            </button>
            <button type="button" class="is-btn is-btn-primary btn-modal" id="is_add_btn"
                data-href="{{ action([\App\Http\Controllers\InvoiceSchemeController::class, 'create']) }}"
                data-container=".invoice_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </button>
        </div>
    </div>

    <div class="is-card">
        <ul class="nav nav-tabs is-tabs" role="tablist">
            <li class="active" role="presentation">
                <a href="#tab_1" data-toggle="tab" aria-expanded="true" role="tab">@lang('invoice.invoice_schemes')</a>
            </li>
            <li role="presentation">
                <a href="#tab_2" data-toggle="tab" aria-expanded="false" role="tab">@lang('invoice.invoice_layouts')</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="tab_1" role="tabpanel">
                <div class="is-toolbar">
                    <div class="is-search-wrap" role="search">
                        <i class="fa fa-search" aria-hidden="true"></i>
                        <input type="search" id="is_quick_search" class="form-control" placeholder="@lang('lang_v1.search')" aria-label="@lang('invoice.invoice_schemes')" autocomplete="off">
                    </div>
                    <div class="is-filter-field">
                        <label for="is_filter_default" class="sr-only">@lang('barcode.default')</label>
                        <select id="is_filter_default" class="form-control">
                            <option value="">@lang('messages.all')</option>
                            <option value="1">@lang('barcode.default')</option>
                            <option value="0">@lang('invoice.standard')</option>
                        </select>
                    </div>
                    <div class="is-filter-field">
                        <label for="is_filter_number_type" class="sr-only">@lang('invoice.number_type')</label>
                        <select id="is_filter_number_type" class="form-control">
                            <option value="">@lang('invoice.number_type')</option>
                            @foreach($number_types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="is-btn is-btn-ghost" id="is_reset_filters">
                        <i class="fa fa-undo"></i> @lang('invoice.reset_filters')
                    </button>
                </div>

                <div class="table-responsive is-table-wrap">
                    <table class="table table-bordered table-striped" id="invoice_table">
                        <thead>
                            <tr>
                                <th>@lang('invoice.name') @show_tooltip(__('tooltip.invoice_scheme_name'))</th>
                                <th>@lang('invoice.prefix') @show_tooltip(__('tooltip.invoice_scheme_prefix'))</th>
                                <th>@lang('invoice.number_type') @show_tooltip(__('invoice.number_type_tooltip'))</th>
                                <th>@lang('invoice.start_number') @show_tooltip(__('tooltip.invoice_scheme_start_number'))</th>
                                <th>@lang('invoice.invoice_count') @show_tooltip(__('tooltip.invoice_scheme_count'))</th>
                                <th>@lang('invoice.total_digits') @show_tooltip(__('tooltip.invoice_scheme_total_digits'))</th>
                                <th>@lang('sale.status')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane" id="tab_2" role="tabpanel">
                <div class="is-toolbar is-toolbar-layouts">
                    <h2 class="is-section-title">@lang('invoice.all_your_invoice_layouts')</h2>
                    <a class="is-btn is-btn-primary" href="{{ action([\App\Http\Controllers\InvoiceLayoutController::class, 'create']) }}">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </a>
                </div>

                @if($invoice_layouts->isEmpty())
                    <div class="is-empty">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        <p>@lang('invoice.all_your_invoice_layouts')</p>
                    </div>
                @else
                    <div class="is-layout-grid">
                        @foreach($invoice_layouts as $layout)
                            <a class="is-layout-card" href="{{ action([\App\Http\Controllers\InvoiceLayoutController::class, 'edit'], [$layout->id]) }}">
                                <div class="is-layout-icon" aria-hidden="true"><i class="fa fa-file-text-o"></i></div>
                                <div class="is-layout-body">
                                    <div class="is-layout-name">
                                        {{ $layout->name }}
                                        @if($layout->is_default)
                                            <span class="is-status is-status-default"><span class="is-dot"></span> @lang('barcode.default')</span>
                                        @endif
                                    </div>
                                    @if($layout->locations->count())
                                        <div class="is-layout-meta">
                                            <span>@lang('invoice.used_in_locations'):</span>
                                            {{ $layout->locations->pluck('name')->implode(', ') }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade invoice_modal contains_select2" tabindex="-1" role="dialog" aria-labelledby="invoiceSchemeAddTitle"></div>
    <div class="modal fade invoice_edit_modal contains_select2" tabindex="-1" role="dialog" aria-labelledby="invoiceSchemeEditTitle"></div>
    <div class="modal fade invoice_view_modal" tabindex="-1" role="dialog" aria-labelledby="invoiceSchemeViewTitle"></div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/invoice-schemes-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
