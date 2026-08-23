@extends('layouts.app')
@section('title', __('lang_v1.manage_modules'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/manage-modules-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@php
    $mm_icon_map = [
        'Repair' => 'bi-tools',
        'Essentials' => 'bi-briefcase',
        'Superadmin' => 'bi-shield-check',
        'Woocommerce' => 'bi-shop',
        'Manufacturing' => 'bi-gear-wide-connected',
        'Project' => 'bi-kanban',
        'Crm' => 'bi-people',
        'ProductCatalogue' => 'bi-journal-richtext',
        'Accounting' => 'bi-calculator',
        'AiAssistance' => 'bi-stars',
        'AssetManagement' => 'bi-building',
        'Cms' => 'bi-window',
        'Connector' => 'bi-plug',
        'Gym' => 'bi-heart-pulse',
        'Hms' => 'bi-building',
    ];

    $mm_modules = is_array($modules) ? $modules : [];
    $mm_installed = [];
    $mm_not_installed = [];
    $mm_update_count = 0;

    foreach ($mm_modules as $mm_key => $mm_mod) {
        if (!empty($mm_mod['is_installed'])) {
            $mm_installed[$mm_key] = $mm_mod;
            if (!empty($mm_mod['version']['is_update_available'])) {
                $mm_update_count++;
            }
        } else {
            $mm_not_installed[$mm_key] = $mm_mod;
        }
    }

    $mm_catalog = [];
    $mm_mods_raw = @unserialize($mods);
    if (is_array($mm_mods_raw)) {
        foreach ($mm_mods_raw as $mm_cat) {
            if (isset($mm_cat->n) && !isset($mm_modules[$mm_cat->n])) {
                $mm_catalog[] = $mm_cat;
            }
        }
    }

    $mm_total = count($mm_modules);
    $mm_installed_count = count($mm_installed);
    $mm_not_installed_count = count($mm_not_installed);
    $mm_catalog_count = count($mm_catalog);
    $mm_has_any = ($mm_total + $mm_catalog_count) > 0;
@endphp

<section class="content mm-shell" id="mm_shell">

    <div class="mm-header no-print" role="banner">
        <div class="mm-header-left">
            <h1>
                <span class="mm-title-icon" aria-hidden="true"><i class="bi bi-puzzle"></i></span>
                {{ __('lang_v1.manage_modules') }}
            </h1>
            <p class="mm-subtitle">Manage VKPOS modules, features and system capabilities</p>
            <nav class="mm-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Settings</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.manage_modules') }}</span>
            </nav>
        </div>
        <div class="mm-header-actions">
            <div class="mm-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="mm_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} modules…" aria-label="Search modules" autocomplete="off">
                <button type="button" class="mm-search-clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <button type="button" class="mm-btn mm-btn-icon" id="mm_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="mm-btn mm-btn-icon" id="mm_settings_toggle" title="View Settings" aria-label="View settings" aria-expanded="false" aria-controls="mm_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="mm-btn mm-btn-icon" id="mm_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="mm-btn" id="mm_refresh" title="Refresh" aria-label="Refresh modules">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button type="button" class="mm-btn mm-btn-primary upload_module_btn">
                <i class="fas fa-upload"></i>
                @lang('lang_v1.upload_module')
            </button>
            <a class="mm-btn" href="{{ action([\App\Http\Controllers\Install\ModulesController::class, 'regenerate']) }}">
                <i class="fas fa-tools"></i>
                Regenerate @show_tooltip("<br/>1. Regenerate/publish modules css/js to fix not found issue. <br/> 2. Publish api module oauth files")
            </a>
        </div>
        <div class="mm-header-meta" aria-label="Module summary">
            <div class="mm-meta"><span>On this page</span> <strong>{{ $mm_total }}</strong></div>
            <div class="mm-meta"><span>Visible</span> <strong id="mm_meta_visible">{{ $mm_total + $mm_catalog_count }}</strong></div>
            @if($is_demo)
                <div class="mm-meta"><span>@lang('lang_v1.disabled_in_demo')</span></div>
            @endif
        </div>
        <div class="mm-settings-panel" id="mm_settings_panel" role="dialog" aria-label="View settings" hidden>
            <label><input type="checkbox" id="mm_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="mm_set_compact"> Compact module cards</label>
        </div>
    </div>

    <div class="mm-kpi-grid no-print" aria-label="Module summary">
        <div class="mm-kpi tone-blue">
            <div class="mm-kpi-icon"><i class="bi bi-puzzle"></i></div>
            <span class="mm-kpi-label">Total modules</span>
            <span class="mm-kpi-value">{{ $mm_total }}</span>
            <span class="mm-kpi-hint">Returned by the system</span>
        </div>
        <div class="mm-kpi tone-green">
            <div class="mm-kpi-icon"><i class="bi bi-check-circle"></i></div>
            <span class="mm-kpi-label">Installed</span>
            <span class="mm-kpi-value">{{ $mm_installed_count }}</span>
            <span class="mm-kpi-hint">Currently installed modules</span>
        </div>
        <div class="mm-kpi tone-slate">
            <div class="mm-kpi-icon"><i class="bi bi-pause-circle"></i></div>
            <span class="mm-kpi-label">Not installed</span>
            <span class="mm-kpi-value">{{ $mm_not_installed_count }}</span>
            <span class="mm-kpi-hint">Present but not installed</span>
        </div>
        @if($mm_update_count > 0)
        <div class="mm-kpi tone-orange">
            <div class="mm-kpi-icon"><i class="bi bi-arrow-repeat"></i></div>
            <span class="mm-kpi-label">Updates</span>
            <span class="mm-kpi-value">{{ $mm_update_count }}</span>
            <span class="mm-kpi-hint">Update flagged by the system</span>
        </div>
        @endif
        @if($mm_catalog_count > 0)
        <div class="mm-kpi tone-teal">
            <div class="mm-kpi-icon"><i class="bi bi-bag"></i></div>
            <span class="mm-kpi-label">Available to buy</span>
            <span class="mm-kpi-value">{{ $mm_catalog_count }}</span>
            <span class="mm-kpi-hint">Listed by the system catalog</span>
        </div>
        @endif
    </div>

    @if($mm_update_count > 0)
    <div class="mm-chip-row no-print" aria-label="Attention">
        <button type="button" class="mm-chip amber" id="mm_update_chip">
            <i class="bi bi-exclamation-circle"></i>
            Updates available
            <span class="mm-count">{{ $mm_update_count }}</span>
        </button>
    </div>
    @endif

    @if($is_demo)
    <div class="mm-alert mm-alert-warn no-print" role="status">
        <div>
            <strong>@lang('lang_v1.disabled_in_demo')</strong>
            <p>Install, uninstall, delete and upload stay disabled in demo mode.</p>
        </div>
    </div>
    @endif

    <div class="form_col mm-upload-wrap" style="display: none;">
        <div class="mm-upload-card">
            <h2>@lang('lang_v1.upload_module')</h2>
            <p class="help-block" style="margin-top:0;">@lang('lang_v1.pls_upload_valid_zip_file')</p>
            {!! Form::open(['url' => action([\App\Http\Controllers\Install\ModulesController::class, 'uploadModule']), 'id' => 'upload_module_form','files' => true, 'style' => 'display:none']) !!}
                <div class="form-group">
                    {!! Form::label('module', __('lang_v1.upload_module') . ':*') !!}
                    {!! Form::file('module', ['required', 'accept' => 'application/zip']) !!}
                    <p class="help-block">
                        @lang('lang_v1.pls_upload_valid_zip_file')
                    </p>
                </div>
                <div class="mm-upload-actions">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm">
                        @lang('lang_v1.upload')
                    </button>
                    <button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm cancel_upload_btn">
                        @lang('messages.cancel')
                    </button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="mm-panel no-print">
        <div class="mm-panel-head">
            <div>
                <h2>Find modules</h2>
                <p>Search by name, description or status. Filters use modules already on this page.</p>
            </div>
        </div>
        <div class="mm-panel-body">
            <div class="mm-global-search" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="mm_global_search" class="form-control" placeholder="Search module name, description or status…" aria-label="Search all modules" autocomplete="off">
                <button type="button" class="mm-search-clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="mm-chip-row" id="mm_status_chips" role="toolbar" aria-label="Status filter">
                <button type="button" class="mm-chip is-active" data-status="all">All <span class="mm-count">{{ $mm_total + $mm_catalog_count }}</span></button>
                <button type="button" class="mm-chip" data-status="installed">Installed <span class="mm-count">{{ $mm_installed_count }}</span></button>
                <button type="button" class="mm-chip" data-status="not_installed">Not installed <span class="mm-count">{{ $mm_not_installed_count }}</span></button>
                @if($mm_update_count > 0)
                    <button type="button" class="mm-chip amber" data-status="update">Update available <span class="mm-count">{{ $mm_update_count }}</span></button>
                @endif
                @if($mm_catalog_count > 0)
                    <button type="button" class="mm-chip" data-status="catalog">Available to buy <span class="mm-count">{{ $mm_catalog_count }}</span></button>
                @endif
            </div>
        </div>
    </div>

    <div id="mm_empty_all" class="mm-panel" @if($mm_has_any) hidden @endif>
        <div class="mm-empty">
            <div class="mm-empty-icon" aria-hidden="true">⚙</div>
            <strong>No modules available</strong>
            <p>No module records were returned by the system.</p>
            <button type="button" class="mm-btn mm-btn-primary" id="mm_retry" onclick="window.location.reload()">Retry</button>
        </div>
    </div>

    <div id="mm_search_empty" class="mm-panel" hidden>
        <div class="mm-empty">
            <div class="mm-empty-icon" aria-hidden="true">🔍</div>
            <strong>No matching modules</strong>
            <p>Try another search term.</p>
            <button type="button" class="mm-btn mm-btn-primary" id="mm_clear_search">Clear Search</button>
        </div>
    </div>

    @if(count($mm_installed))
    <section class="mm-section" data-mm-section="installed" aria-label="Installed modules">
        <div class="mm-section-head">
            <h2>Installed modules</h2>
            <span>{{ $mm_installed_count }}</span>
        </div>
        <div class="mm-grid">
            @foreach($mm_installed as $module)
                @include('install.modules.partials.module_card', ['module' => $module, 'is_demo' => $is_demo, 'mm_icon_map' => $mm_icon_map, 'mm_kind' => 'installed'])
            @endforeach
        </div>
    </section>
    @endif

    @if(count($mm_not_installed))
    <section class="mm-section" data-mm-section="not_installed" aria-label="Not installed modules">
        <div class="mm-section-head">
            <h2>Not installed</h2>
            <span>{{ $mm_not_installed_count }}</span>
        </div>
        <div class="mm-grid">
            @foreach($mm_not_installed as $module)
                @include('install.modules.partials.module_card', ['module' => $module, 'is_demo' => $is_demo, 'mm_icon_map' => $mm_icon_map, 'mm_kind' => 'not_installed'])
            @endforeach
        </div>
    </section>
    @endif

    @if(count($mm_catalog))
    <section class="mm-section" data-mm-section="catalog" aria-label="Available to buy">
        <div class="mm-section-head">
            <h2>Available to buy</h2>
            <span>{{ $mm_catalog_count }}</span>
        </div>
        <div class="mm-grid">
            @foreach($mm_catalog as $mod)
                @php
                    $mm_cat_icon = $mm_icon_map[$mod->n] ?? 'bi-puzzle';
                    $mm_cat_search = strtolower(($mod->dn ?? '') . ' ' . ($mod->d ?? '') . ' catalog buy available');
                @endphp
                <article class="mm-card is-catalog"
                    data-mm-kind="catalog"
                    data-mm-update="0"
                    data-mm-name="{{ $mod->dn }}"
                    data-mm-desc="{{ $mod->d }}"
                    data-mm-version=""
                    data-mm-requires=""
                    data-mm-search="{{ $mm_cat_search }}">
                    <div class="mm-card-top">
                        <span class="mm-mod-icon" aria-hidden="true"><i class="bi {{ $mm_cat_icon }}"></i></span>
                        <span class="mm-badge is-catalog">Available to buy</span>
                    </div>
                    <h3 class="mm-card-name">{{ $mod->dn }}</h3>
                    <p class="mm-card-desc">{{ $mod->d }}</p>
                    <div class="mm-card-actions">
                        <button type="button" onclick="window.open('{{ $mod->u }}', '_blank')"
                            class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent">
                            <i class="fas fa-money-bill"></i> Buy
                        </button>
                        <button type="button" class="mm-btn mm-details-btn" aria-label="Details for {{ $mod->dn }}">Details</button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    <div class="mm-panel no-print">
        <div class="mm-panel-head">
            <div>
                <h2>System information</h2>
                <p>Facts already provided by this page</p>
            </div>
        </div>
        <div class="mm-panel-body">
            <dl class="mm-sys">
                <div class="mm-sys-item">
                    <dt>Access</dt>
                    <dd>Only superadmin can access manage modules</dd>
                </div>
                <div class="mm-sys-item">
                    <dt>Modules loaded</dt>
                    <dd>{{ $mm_total }} local · {{ $mm_catalog_count }} catalog</dd>
                </div>
                @if($is_demo)
                <div class="mm-sys-item">
                    <dt>Demo</dt>
                    <dd>@lang('lang_v1.disabled_in_demo')</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="mm-drawer-backdrop" id="mm_drawer_backdrop" hidden></div>
    <aside class="mm-drawer" id="mm_drawer" role="dialog" aria-label="Module details" aria-modal="true">
        <div class="mm-drawer-head">
            <div>
                <h3 data-mm-d="name">—</h3>
                <p class="mm-drawer-sku">Module details</p>
            </div>
            <button type="button" class="mm-btn mm-btn-icon" id="mm_drawer_close" aria-label="Close details">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mm-drawer-status">
            <span class="mm-badge" data-mm-d="status">—</span>
        </div>
        <dl class="mm-drawer-dl">
            <div>
                <dt>Description</dt>
                <dd data-mm-d="desc">—</dd>
            </div>
            <div data-mm-row="version">
                <dt>@lang('lang_v1.version')</dt>
                <dd data-mm-d="version">—</dd>
            </div>
            <div data-mm-row="update" hidden>
                <dt>Update</dt>
                <dd data-mm-d="update">—</dd>
            </div>
            <div data-mm-row="requires" hidden>
                <dt>Required modules</dt>
                <dd data-mm-d="requires">—</dd>
            </div>
        </dl>
    </aside>

    <div class="mm-toast-host" id="mm_toast_host" aria-live="polite"></div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    //show a hidden form on upload_module_btn click
    $(document).on('click', '.upload_module_btn', function(){
        $(".form_col,form#upload_module_form").fadeToggle();
    });

    //hide form on cancel_upload_btn click
    $(document).on('click', '.cancel_upload_btn', function(){
        $("form#upload_module_form")[0].reset();
        $(".form_col,form#upload_module_form").fadeOut();
    });

</script>
<script src="{{ asset('js/manage-modules-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
