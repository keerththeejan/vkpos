@extends('layouts.app')
@section('title', __('lang_v1.selling_price_group'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/selling-price-group-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content spg-shell">
    {{-- Sticky Header --}}
    <div class="spg-header" role="banner">
        <div class="spg-header-left">
            <h1>@lang('lang_v1.selling_price_group')</h1>
            <p class="spg-subtitle">@lang('lang_v1.selling_price_help_text')</p>
            <div class="spg-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('lang_v1.selling_price_group')</span>
            </div>
        </div>
        <div class="spg-header-actions">
            <div class="spg-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="spg_quick_search" class="form-control" placeholder="Search price groups…" aria-label="Search selling price groups" autocomplete="off">
            </div>
            <button type="button" class="spg-btn spg-btn-ghost" id="spg_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="spg-btn spg-btn-ghost" id="spg_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="spg-btn spg-btn-ghost" id="spg_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="spg-btn spg-btn-ghost" href="{{ url('/update-product-price') }}" title="Update product prices" aria-label="Update product prices">
                <i class="fas fa-cog"></i>
            </a>
            <a class="spg-btn" href="{{ url('/update-product-price') }}" title="Import / Export prices">
                <i class="fas fa-file-excel"></i> Import / Export
            </a>
            <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal spg-btn spg-btn-primary"
                data-href="{{ action([\App\Http\Controllers\SellingPriceGroupController::class, 'create']) }}"
                data-container=".view_modal"
                id="spg_add_btn">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>
    </div>

    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible spg-alert spg-alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if (!empty($notification['msg']))
                        {{ $notification['msg'] }}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- KPI cards --}}
    <div class="spg-kpi-grid" aria-label="Pricing summary">
        <div class="spg-kpi tone-blue">
            <div class="spg-kpi-icon"><i class="fas fa-tags"></i></div>
            <span class="spg-kpi-label">Total Groups</span>
            <span class="spg-kpi-value" id="spg_kpi_total">—</span>
            <span class="spg-kpi-hint">Selling price groups</span>
        </div>
        <div class="spg-kpi tone-green">
            <div class="spg-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="spg-kpi-label">Filtered</span>
            <span class="spg-kpi-value" id="spg_kpi_filtered">—</span>
            <span class="spg-kpi-hint">Matching current search</span>
        </div>
        <div class="spg-kpi tone-purple">
            <div class="spg-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="spg-kpi-label">Selected</span>
            <span class="spg-kpi-value" id="spg_kpi_selected">0</span>
            <span class="spg-kpi-hint">Preview selection</span>
        </div>
        <div class="spg-kpi tone-orange">
            <div class="spg-kpi-icon"><i class="fas fa-file-import"></i></div>
            <span class="spg-kpi-label">Bulk Prices</span>
            <span class="spg-kpi-value">Excel</span>
            <span class="spg-kpi-hint">Via Update Product Price</span>
        </div>
        <div class="spg-kpi tone-teal">
            <div class="spg-kpi-icon"><i class="fas fa-store"></i></div>
            <span class="spg-kpi-label">POS Ready</span>
            <span class="spg-kpi-value">Active</span>
            <span class="spg-kpi-hint">Used in customer & location pricing</span>
        </div>
        <div class="spg-kpi tone-slate">
            <div class="spg-kpi-icon"><i class="fas fa-info-circle"></i></div>
            <span class="spg-kpi-label">Help</span>
            <span class="spg-kpi-value">Guide</span>
            <span class="spg-kpi-hint">@lang('lang_v1.selling_price_help_text')</span>
        </div>
    </div>

    <div class="spg-main-grid">
        <div class="spg-card spg-table-card">
            <div class="spg-card-head">
                <div>
                    <h3>@lang('lang_v1.all_selling_price_group')</h3>
                    <p>@lang('lang_v1.selling_price_help_text')</p>
                </div>
                <div class="spg-filter-bar">
                    <div class="spg-filter-field">
                        <label for="spg_filter_name">Group Name</label>
                        <input type="text" id="spg_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                    </div>
                    <div class="spg-filter-field">
                        <label for="spg_filter_desc">Description</label>
                        <input type="text" id="spg_filter_desc" class="form-control" placeholder="Filter by description…" autocomplete="off">
                    </div>
                    <div class="spg-filter-actions">
                        <button type="button" class="spg-btn spg-btn-primary" id="spg_apply_filters">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button type="button" class="spg-btn" id="spg_reset_filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="spg-card-body">
                <div class="table-responsive spg-table-wrap">
                    <table class="table table-bordered table-striped" id="selling_price_group_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.name')</th>
                                <th>@lang('lang_v1.description')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <aside class="spg-card spg-preview-card" id="spg_preview_card" aria-live="polite">
            <div class="spg-card-head">
                <div>
                    <h3>Group Preview</h3>
                    <p>Select a row to inspect this price group.</p>
                </div>
            </div>
            <div class="spg-card-body">
                <div class="spg-preview-empty" id="spg_preview_empty">
                    <i class="fas fa-tags"></i>
                    <p>Click any price group row to preview details and quick actions.</p>
                </div>
                <div class="spg-preview-content" id="spg_preview_content" style="display:none;">
                    <div class="spg-preview-title-row">
                        <h4 id="spg_preview_name">—</h4>
                        <span class="spg-pill success">Price List</span>
                    </div>
                    <p class="spg-preview-desc" id="spg_preview_desc">—</p>
                    <div class="spg-preview-meta">
                        <div>
                            <span class="k">Usage</span>
                            <span class="v">Customers / Locations / POS</span>
                        </div>
                        <div>
                            <span class="k">Bulk edit</span>
                            <span class="v"><a href="{{ url('/update-product-price') }}">Update Product Price</a></span>
                        </div>
                    </div>
                    <h5 class="spg-preview-section-title">Quick Actions</h5>
                    <div class="spg-preview-actions" id="spg_preview_actions"></div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Preserved unused modal container from original page --}}
    <div class="modal fade brands_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {

            //selling_price_group_table
            var selling_price_group_table = $('#selling_price_group_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: '/selling-price-group',
                columnDefs: [{
                    "targets": 2,
                    "orderable": false,
                    "searchable": false
                }]
            });

            $(document).on('submit', 'form#selling_price_group_form', function(e) {
                e.preventDefault();
                var data = $(this).serialize();

                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.view_modal').modal('hide');
                            toastr.success(result.msg);
                            selling_price_group_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', 'button.delete_spg_button', function() {
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    selling_price_group_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', 'button.activate_deactivate_spg', function() {
                var href = $(this).data('href');
                $.ajax({
                    url: href,
                    dataType: "json",
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            selling_price_group_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

        });
    </script>
    <script src="{{ asset('js/selling-price-group-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
