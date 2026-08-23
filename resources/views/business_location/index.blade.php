@extends('layouts.app')
@section('title', __('business.business_locations'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/business-locations-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content bloc-shell" id="bloc_shell">

    <div class="bloc-header" role="banner">
        <div class="bloc-header-left">
            <h1>
                <span class="bloc-title-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                @lang('business.business_locations')
            </h1>
            <p class="bloc-subtitle">Manage branches, stores, warehouses and business operating locations</p>
            <nav class="bloc-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Settings</span>
                <span aria-hidden="true">/</span>
                <span>@lang('business.business_locations')</span>
            </nav>
        </div>
        <div class="bloc-header-actions">
            <div class="bloc-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="bloc_quick_search" class="form-control" placeholder="Search locations…" aria-label="Search business locations" autocomplete="off">
                <button type="button" class="bloc-search-clear" id="bloc_search_clear" hidden title="Clear search" aria-label="Clear search">
                    <i class="fas fa-times"></i>
                </button>
                <span class="bloc-search-spinner" id="bloc_search_spinner" hidden aria-hidden="true"><i class="fas fa-circle-notch"></i></span>
            </div>
            <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_refresh_table" title="Refresh" aria-label="Refresh locations">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="fas fa-expand"></i>
            </button>
            <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_settings_toggle" title="Settings" aria-label="Display settings" aria-expanded="false" aria-controls="bloc_settings_panel">
                <i class="fas fa-cog"></i>
            </button>
            <button type="button" class="bloc-btn bloc-btn-primary btn-modal" id="bloc_add_btn"
                data-href="{{ action([\App\Http\Controllers\BusinessLocationController::class, 'create']) }}"
                data-container=".location_add_modal">
                <i class="fas fa-plus" aria-hidden="true"></i> @lang('messages.add')
            </button>
        </div>
        <div class="bloc-settings-panel" id="bloc_settings_panel" hidden>
            <label class="bloc-check">
                <input type="checkbox" id="bloc_set_hide_kpis"> Hide summary cards
            </label>
            <label class="bloc-check">
                <input type="checkbox" id="bloc_set_compact"> Compact table
            </label>
        </div>
    </div>

    <div class="bloc-kpi-grid" aria-label="Location summary">
        <div class="bloc-kpi tone-blue">
            <div class="bloc-kpi-icon"><i class="fas fa-building"></i></div>
            <span class="bloc-kpi-label">Total Locations</span>
            <span class="bloc-kpi-value" id="bloc_kpi_total">—</span>
            <span class="bloc-kpi-hint">From the location list</span>
        </div>
        <div class="bloc-kpi tone-green">
            <div class="bloc-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="bloc-kpi-label">Active</span>
            <span class="bloc-kpi-value" id="bloc_kpi_active">—</span>
            <span class="bloc-kpi-hint">Available for operations</span>
        </div>
        <div class="bloc-kpi tone-slate">
            <div class="bloc-kpi-icon"><i class="fas fa-minus-circle"></i></div>
            <span class="bloc-kpi-label">Inactive</span>
            <span class="bloc-kpi-value" id="bloc_kpi_inactive">—</span>
            <span class="bloc-kpi-hint">Deactivated locations</span>
        </div>
        <div class="bloc-kpi tone-teal">
            <div class="bloc-kpi-icon"><i class="fas fa-filter"></i></div>
            <span class="bloc-kpi-label">Showing</span>
            <span class="bloc-kpi-value" id="bloc_kpi_showing">—</span>
            <span class="bloc-kpi-hint">Matching search &amp; filter</span>
        </div>
    </div>

    <div class="bloc-card bloc-workspace">
        <div class="bloc-card-head">
            <div>
                <h2>@lang('business.all_your_business_locations')</h2>
                <p>@lang('business.manage_your_business_locations')</p>
            </div>
            <div class="bloc-toolbar">
                <div class="bloc-chip-group" role="group" aria-label="Location status">
                    <button type="button" class="bloc-chip is-on" data-bloc-status="all" id="bloc_filter_all">All</button>
                    <button type="button" class="bloc-chip" data-bloc-status="active" id="bloc_filter_active">Active</button>
                    <button type="button" class="bloc-chip" data-bloc-status="inactive" id="bloc_filter_inactive">Inactive</button>
                </div>
                <div class="bloc-view-toggle" role="group" aria-label="View mode">
                    <button type="button" class="bloc-btn bloc-btn-ghost is-on" id="bloc_view_table" title="Table view" aria-pressed="true">
                        <i class="fas fa-table" aria-hidden="true"></i> Table
                    </button>
                    <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_view_cards" title="Card view" aria-pressed="false">
                        <i class="fas fa-th-large" aria-hidden="true"></i> Cards
                    </button>
                </div>
            </div>
        </div>

        <div class="bloc-card-body">
            <div class="bloc-skeleton" id="bloc_skeleton" aria-hidden="true">
                <div class="bloc-skel-row"></div>
                <div class="bloc-skel-row"></div>
                <div class="bloc-skel-row"></div>
                <div class="bloc-skel-row"></div>
            </div>

            <div class="bloc-error" id="bloc_error" hidden>
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <h3>Unable to load business locations.</h3>
                <p>Please try again.</p>
                <button type="button" class="bloc-btn bloc-btn-primary" id="bloc_retry">Retry</button>
            </div>

            <div class="bloc-empty" id="bloc_empty" hidden>
                <div class="bloc-empty-icon" aria-hidden="true">📍</div>
                <h3>No business locations found</h3>
                <p>Create a business location to begin managing your stores and branches.</p>
                <button type="button" class="bloc-btn bloc-btn-primary" id="bloc_empty_add">
                    <i class="fas fa-plus" aria-hidden="true"></i> @lang('messages.add')
                </button>
            </div>

            <div class="bloc-empty" id="bloc_no_result" hidden>
                <i class="fas fa-search" aria-hidden="true"></i>
                <h3>No matching locations</h3>
                <p>Try a different search or status filter.</p>
                <button type="button" class="bloc-btn" id="bloc_reset_filters">Clear filters</button>
            </div>

            <div class="table-responsive bloc-table-wrap" id="bloc_table_wrap">
                <table class="table table-bordered table-striped" id="business_location_table">
                    <thead>
                        <tr>
                            <th>@lang('invoice.name')</th>
                            <th>@lang('lang_v1.location_id')</th>
                            <th>@lang('business.landmark')</th>
                            <th>@lang('business.city')</th>
                            <th>@lang('business.zip_code')</th>
                            <th>@lang('business.state')</th>
                            <th>@lang('business.country')</th>
                            <th>@lang('lang_v1.price_group')</th>
                            <th>@lang('invoice.invoice_scheme')</th>
                            <th>@lang('lang_v1.invoice_layout_for_pos')</th>
                            <th>@lang('lang_v1.invoice_layout_for_sale')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <div class="bloc-card-grid" id="bloc_card_grid" hidden></div>
        </div>
    </div>

    <div class="bloc-drawer-backdrop" id="bloc_drawer_backdrop" hidden></div>
    <aside class="bloc-drawer" id="bloc_drawer" role="dialog" aria-modal="true" aria-labelledby="bloc_drawer_title" hidden>
        <div class="bloc-drawer-head">
            <div>
                <h3 id="bloc_drawer_title">Location details</h3>
                <p class="bloc-drawer-code" id="bloc_drawer_code"></p>
            </div>
            <button type="button" class="bloc-btn bloc-btn-ghost" id="bloc_drawer_close" aria-label="Close details">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="bloc-drawer-status" id="bloc_drawer_status"></div>
        <dl class="bloc-drawer-dl" id="bloc_drawer_dl"></dl>
        <h4 class="bloc-drawer-section">Actions</h4>
        <div class="bloc-drawer-actions" id="bloc_drawer_actions"></div>
    </aside>

    <div class="modal fade location_add_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade location_edit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

</section>
@endsection

@section('javascript')
<script src="{{ asset('js/business-locations-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
