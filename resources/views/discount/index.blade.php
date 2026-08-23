@extends('layouts.app')
@section('title', __('sale.discount'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/discount-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content dc-shell">
    {{-- Sticky Header --}}
    <div class="dc-header" role="banner">
        <div class="dc-header-left">
            <h1>@lang('sale.discount')</h1>
            <p class="dc-subtitle">Promotions · product · brand · category · location</p>
            <div class="dc-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('sale.discount')</span>
            </div>
        </div>
        <div class="dc-header-actions">
            <div class="dc-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="dc_quick_search" class="form-control" placeholder="Search discounts…" aria-label="Search discounts" autocomplete="off">
            </div>
            <button type="button" class="dc-btn dc-btn-ghost" id="dc_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="dc-btn dc-btn-ghost" id="dc_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="dc-btn dc-btn-ghost" id="dc_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            @can('brand.create')
                <a class="dc-btn dc-btn-primary btn-modal"
                    data-href="{{ action([\App\Http\Controllers\DiscountController::class, 'create']) }}"
                    data-container=".discount_modal">
                    <i class="fas fa-plus"></i> @lang('messages.add')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI cards from live DataTable (UI only) --}}
    <div class="dc-kpi-grid" aria-label="Discount KPIs">
        <div class="dc-kpi tone-amber">
            <div class="dc-kpi-icon"><i class="fas fa-tags"></i></div>
            <span class="dc-kpi-label">Total Discounts</span>
            <span class="dc-kpi-value" id="dc_kpi_total">—</span>
            <span class="dc-kpi-hint">All discount records</span>
        </div>
        <div class="dc-kpi tone-teal">
            <div class="dc-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="dc-kpi-label">Filtered</span>
            <span class="dc-kpi-value" id="dc_kpi_filtered">—</span>
            <span class="dc-kpi-hint">Matching search</span>
        </div>
        <div class="dc-kpi tone-green">
            <div class="dc-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="dc-kpi-label">Active (page)</span>
            <span class="dc-kpi-value" id="dc_kpi_active">—</span>
            <span class="dc-kpi-hint">From status labels</span>
        </div>
        <div class="dc-kpi tone-orange">
            <div class="dc-kpi-icon"><i class="fas fa-pause-circle"></i></div>
            <span class="dc-kpi-label">Inactive (page)</span>
            <span class="dc-kpi-value" id="dc_kpi_inactive">—</span>
            <span class="dc-kpi-hint">Needs reactivation</span>
        </div>
        <div class="dc-kpi tone-blue">
            <div class="dc-kpi-icon"><i class="fas fa-percent"></i></div>
            <span class="dc-kpi-label">% / Fixed</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">Shown per discount</span>
        </div>
        <div class="dc-kpi tone-violet">
            <div class="dc-kpi-icon"><i class="fas fa-box"></i></div>
            <span class="dc-kpi-label">Product Discounts</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">UI placeholder</span>
        </div>
        <div class="dc-kpi tone-slate">
            <div class="dc-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="dc-kpi-label">Customer Offers</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">UI placeholder</span>
        </div>
        <div class="dc-kpi tone-teal">
            <div class="dc-kpi-icon"><i class="fas fa-calendar"></i></div>
            <span class="dc-kpi-label">Scheduled</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">Start / end dates</span>
        </div>
        <div class="dc-kpi tone-red">
            <div class="dc-kpi-icon"><i class="fas fa-hourglass-end"></i></div>
            <span class="dc-kpi-label">Expired</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">UI placeholder</span>
        </div>
        <div class="dc-kpi tone-amber">
            <div class="dc-kpi-icon"><i class="fas fa-star"></i></div>
            <span class="dc-kpi-label">Most Used</span>
            <span class="dc-kpi-value">—</span>
            <span class="dc-kpi-hint">UI placeholder</span>
        </div>
    </div>

    <div class="dc-future-strip" aria-label="Coming soon">
        <span class="dc-chip"><i class="fas fa-gift"></i> BOGO</span>
        <span class="dc-chip"><i class="fas fa-layer-group"></i> Bundles</span>
        <span class="dc-chip"><i class="fas fa-ticket-alt"></i> Coupons</span>
        <span class="dc-chip"><i class="fas fa-bolt"></i> Flash Sales</span>
        <span class="dc-chip"><i class="fas fa-robot"></i> AI Pricing</span>
        <span class="dc-chip"><i class="fas fa-globe"></i> Geo Promos</span>
        <span class="dc-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="dc-main-grid">
        <div class="dc-main-col">
            <div class="dc-card">
                <div class="dc-card-head">
                    <div>
                        <h3>@lang('sale.discount')</h3>
                        <p>Server-side DataTable · create/edit modal · mass deactivate</p>
                    </div>
                    @can('brand.create')
                        <a class="dc-btn dc-btn-primary btn-modal"
                            data-href="{{ action([\App\Http\Controllers\DiscountController::class, 'create']) }}"
                            data-container=".discount_modal">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    @endcan
                </div>
                <div class="dc-card-body">
                    @can('brand.view')
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="discounts_table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all-row" data-table-id="discounts_table"></th>
                                        <th>@lang('unit.name')</th>
                                        <th>@lang('lang_v1.starts_at')</th>
                                        <th>@lang('lang_v1.ends_at')</th>
                                        <th>@lang('sale.discount_amount')</th>
                                        <th>@lang('lang_v1.priority')</th>
                                        <th>@lang('product.brand')</th>
                                        <th>@lang('product.category')</th>
                                        <th>@lang('report.products')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <td colspan="11">
                                            <div style="display: flex; width: 100%;">
                                                {!! Form::open([
                                                    'url' => action([\App\Http\Controllers\DiscountController::class, 'massDeactivate']),
                                                    'method' => 'post',
                                                    'id' => 'mass_deactivate_form',
                                                ]) !!}
                                                {!! Form::hidden('selected_discounts', null, ['id' => 'selected_discounts']) !!}
                                                {!! Form::submit(__('lang_v1.deactivate_selected'), [
                                                    'class' => 'tw-dw-btn tw-dw-btn-warning tw-text-white tw-dw-btn-xs',
                                                    'id' => 'deactivate-selected',
                                                ]) !!}
                                                {!! Form::close() !!}
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <aside class="dc-side" aria-label="Discount details">
            <div class="dc-preview-card">
                <h3>Discount Details</h3>
                <p class="dc-preview-empty" id="dc_preview_empty">Select a discount row to preview rules, products, dates, and quick actions. Add/Edit still opens the existing modal.</p>
                <div id="dc_preview_content" style="display:none;">
                    <div class="dc-preview-rows">
                        <div><span>Name</span><strong id="dc_preview_name">—</strong></div>
                        <div><span>Status</span><strong><span class="dc-badge" id="dc_preview_status">Active</span></strong></div>
                        <div><span>Starts</span><strong id="dc_preview_starts">—</strong></div>
                        <div><span>Ends</span><strong id="dc_preview_ends">—</strong></div>
                        <div><span>Value</span><strong id="dc_preview_amount">—</strong></div>
                        <div><span>Priority</span><strong id="dc_preview_priority">—</strong></div>
                        <div><span>Brand</span><strong id="dc_preview_brand">—</strong></div>
                        <div><span>Category</span><strong id="dc_preview_category">—</strong></div>
                        <div><span>Products</span><strong id="dc_preview_products">—</strong></div>
                        <div><span>Location</span><strong id="dc_preview_location">—</strong></div>
                    </div>
                    <div class="dc-preview-actions" id="dc_preview_actions"></div>
                </div>
            </div>

            <div class="dc-summary-card">
                <h3>Promotion Summary</h3>
                <div class="dc-summary-rows">
                    <div><span>Filtered discounts</span><strong id="dc_sum_filtered">—</strong></div>
                    <div><span>Active on page</span><strong id="dc_sum_active">—</strong></div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade discount_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/discount-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).on('click', '#deactivate-selected', function(e) {
        e.preventDefault();
        var selected_rows = [];
        var i = 0;
        $('.row-select:checked').each(function() {
            selected_rows[i++] = $(this).val();
        });

        if (selected_rows.length > 0) {
            $('input#selected_discounts').val(selected_rows);
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $('form#mass_deactivate_form').submit();
                }
            });
        } else {
            $('input#selected_discounts').val('');
            swal('@lang('lang_v1.no_row_selected')');
        }
    });

    $(document).on('click', '.activate-discount', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        $.ajax({
            method: "get",
            url: href,
            dataType: "json",
            success: function(result) {
                if (result.success == true) {
                    toastr.success(result.msg);
                    discounts_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    $(document).on('shown.bs.modal', '.discount_modal', function() {
        $('#variation_ids').select2({
            ajax: {
                url: '/purchases/get_products?check_enable_stock=false&only_variations=true',
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    var results = [];
                    for (var item in data) {
                        results.push({
                            id: data[item].variation_id,
                            text: data[item].text,
                        });
                    }
                    return {
                        results: results,
                    };
                },
            },
            minimumInputLength: 1,
            closeOnSelect: false
        });
    });

    $(document).on('change', '#variation_ids', function() {
        if ($(this).val().length) {
            $('#brand_input').addClass('hide');
            $('#category_input').addClass('hide');
        } else {
            $('#brand_input').removeClass('hide');
            $('#category_input').removeClass('hide');
        }
    });

    $(document).on('hidden.bs.modal', '.discount_modal', function() {
        $("#variation_ids").select2('destroy');
    });
</script>
@endsection
