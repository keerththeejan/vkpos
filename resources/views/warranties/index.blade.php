@extends('layouts.app')
@section('title', __('lang_v1.warranties'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/warranties-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content wm-shell">
    {{-- Sticky Header --}}
    <div class="wm-header" role="banner">
        <div class="wm-header-left">
            <h1>@lang('lang_v1.warranties')</h1>
            <p class="wm-subtitle">Manage warranty plans for products and POS sales</p>
            <div class="wm-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('lang_v1.warranties')</span>
            </div>
        </div>
        <div class="wm-header-actions">
            <div class="wm-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="wm_quick_search" class="form-control" placeholder="Search warranties…" aria-label="Search warranties" autocomplete="off">
            </div>
            <button type="button" class="wm-btn wm-btn-ghost" id="wm_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="wm-btn wm-btn-ghost" id="wm_refresh_table" title="Refresh" aria-label="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="wm-btn wm-btn-ghost" id="wm_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="wm-btn wm-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal wm-btn wm-btn-primary"
                data-href="{{ action([\App\Http\Controllers\WarrantyController::class, 'create']) }}"
                data-container=".view_modal"
                id="wm_add_btn">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>
    </div>

    {{-- KPI cards (visual — from DataTable / existing feature set) --}}
    <div class="wm-kpi-grid" aria-label="Warranty summary">
        <div class="wm-kpi tone-blue">
            <div class="wm-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="wm-kpi-label">Total Plans</span>
            <span class="wm-kpi-value" id="wm_kpi_total">—</span>
            <span class="wm-kpi-hint">Warranty plans configured</span>
        </div>
        <div class="wm-kpi tone-green">
            <div class="wm-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="wm-kpi-label">Filtered</span>
            <span class="wm-kpi-value" id="wm_kpi_filtered">—</span>
            <span class="wm-kpi-hint">Matching search</span>
        </div>
        <div class="wm-kpi tone-purple">
            <div class="wm-kpi-icon"><i class="fas fa-eye"></i></div>
            <span class="wm-kpi-label">Selected</span>
            <span class="wm-kpi-value" id="wm_kpi_selected">0</span>
            <span class="wm-kpi-hint">Preview selection</span>
        </div>
        <div class="wm-kpi tone-orange">
            <div class="wm-kpi-icon"><i class="fas fa-box"></i></div>
            <span class="wm-kpi-label">Products</span>
            <span class="wm-kpi-value">Linked</span>
            <span class="wm-kpi-hint">Assigned on product / POS</span>
        </div>
        <div class="wm-kpi tone-teal">
            <div class="wm-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="wm-kpi-label">IMEI Ready</span>
            <span class="wm-kpi-value">Supported</span>
            <span class="wm-kpi-hint">Works with serial tracking products</span>
        </div>
        <div class="wm-kpi tone-slate">
            <div class="wm-kpi-icon"><i class="fas fa-store"></i></div>
            <span class="wm-kpi-label">POS</span>
            <span class="wm-kpi-value">Active</span>
            <span class="wm-kpi-hint">Selectable on sell lines</span>
        </div>
    </div>

    {{-- Future-ready placeholders (UI only) --}}
    <div class="wm-future-strip" aria-label="Coming soon features">
        <span class="wm-chip"><i class="fas fa-clipboard-check"></i> Claims</span>
        <span class="wm-chip"><i class="fas fa-qrcode"></i> QR Verify</span>
        <span class="wm-chip"><i class="fas fa-history"></i> Timeline</span>
        <span class="wm-chip"><i class="fas fa-bell"></i> Expiry Alerts</span>
        <span class="wm-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="wm-main-grid">
        <div class="wm-card wm-table-card">
            <div class="wm-card-head">
                <div>
                    <h3>@lang('lang_v1.all_warranties')</h3>
                    <p>Existing DataTable &amp; AJAX save logic is unchanged.</p>
                </div>
                <div class="wm-filter-bar">
                    <div class="wm-filter-field">
                        <label for="wm_filter_name">Name</label>
                        <input type="text" id="wm_filter_name" class="form-control" placeholder="Filter by name…" autocomplete="off">
                    </div>
                    <div class="wm-filter-field">
                        <label for="wm_filter_desc">Description</label>
                        <input type="text" id="wm_filter_desc" class="form-control" placeholder="Filter by description…" autocomplete="off">
                    </div>
                    <div class="wm-filter-field">
                        <label for="wm_filter_duration">Duration</label>
                        <input type="text" id="wm_filter_duration" class="form-control" placeholder="e.g. months…" autocomplete="off">
                    </div>
                    <div class="wm-filter-actions">
                        <button type="button" class="wm-btn wm-btn-primary" id="wm_apply_filters">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <button type="button" class="wm-btn" id="wm_reset_filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="wm-card-body">
                <div class="table-responsive wm-table-wrap">
                    <table class="table table-bordered table-striped" id="warranty_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.name')</th>
                                <th>@lang('lang_v1.description')</th>
                                <th>@lang('lang_v1.duration')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <aside class="wm-side-stack">
            <div class="wm-card wm-preview-card" id="wm_preview_card" aria-live="polite">
                <div class="wm-card-head">
                    <div>
                        <h3>Warranty Preview</h3>
                        <p>Select a plan to inspect details.</p>
                    </div>
                </div>
                <div class="wm-card-body">
                    <div class="wm-preview-empty" id="wm_preview_empty">
                        <i class="fas fa-shield-alt"></i>
                        <p>Click any warranty row to preview coverage duration and actions.</p>
                    </div>
                    <div class="wm-preview-content" id="wm_preview_content" style="display:none;">
                        <div class="wm-preview-badge"><i class="fas fa-shield-alt"></i></div>
                        <div class="wm-preview-title-row">
                            <h4 id="wm_preview_name">—</h4>
                            <span class="wm-pill success">Active</span>
                        </div>
                        <div class="wm-preview-duration" id="wm_preview_duration">—</div>
                        <p class="wm-preview-desc" id="wm_preview_desc">—</p>
                        <div class="wm-preview-meta">
                            <div>
                                <span class="k">Assignment</span>
                                <span class="v">Product · POS sell line</span>
                            </div>
                            <div>
                                <span class="k">Catalog</span>
                                <span class="v"><a href="{{ url('/products') }}">View products</a></span>
                            </div>
                        </div>
                        <h5 class="wm-preview-section-title">Quick Actions</h5>
                        <div class="wm-preview-actions" id="wm_preview_actions"></div>
                    </div>
                </div>
            </div>

            <div class="wm-card wm-examples-card">
                <div class="wm-card-head">
                    <div>
                        <h3>Plan Examples</h3>
                        <p>Visual reference only.</p>
                    </div>
                </div>
                <div class="wm-card-body">
                    <div class="wm-mini-card">
                        <strong>Standard Care</strong>
                        <span>12 Months</span>
                        <em>Manufacturing defects · Repair</em>
                    </div>
                    <div class="wm-mini-card">
                        <strong>Premium Shield</strong>
                        <span>24 Months</span>
                        <em>Parts · Labour · Display</em>
                    </div>
                    <div class="wm-mini-card">
                        <strong>Extended Pro</strong>
                        <span>36 Months</span>
                        <em>Full coverage · Priority service</em>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            //Status table
            var warranty_table = $('#warranty_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: "{{ action([\App\Http\Controllers\WarrantyController::class, 'index']) }}",
                columnDefs: [{
                    "targets": 3,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'duration',
                        name: 'duration'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });

            $(document).on('submit', 'form#warranty_form', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.view_modal').modal('hide');
                            toastr.success(result.msg);
                            warranty_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        });
    </script>
    <script src="{{ asset('js/warranties-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
