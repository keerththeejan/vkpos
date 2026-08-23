@extends('layouts.app')
@section('title', __('repair::lang.repair') . ' '. __('business.dashboard'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/repair-dashboard-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $rd_total_jobs = collect($job_sheets_by_status)->sum('total_job_sheets');
    $rd_status_count = collect($job_sheets_by_status)->count();
    $rd_staff_count = collect($job_sheets_by_service_staff ?? [])->count();
    $rd_staff_jobs = collect($job_sheets_by_service_staff ?? [])->sum('total_job_sheets');
    $rd_top_status = collect($job_sheets_by_status)->sortByDesc('total_job_sheets')->first();
@endphp

@section('content')
@include('repair::layouts.nav')

<section class="content no-print rd-shell">
    {{-- Sticky Header --}}
    <div class="rd-header" role="banner">
        <div class="rd-header-left">
            <h1>@lang('repair::lang.repair') @lang('business.dashboard')</h1>
            <p class="rd-subtitle">Real-time overview of job sheets, technicians, and trending devices</p>
            <div class="rd-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('repair::lang.repair')</span>
                <span>/</span>
                <span>@lang('business.dashboard')</span>
            </div>
        </div>
        <div class="rd-header-actions">
            <div class="rd-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="rd_quick_search" class="form-control" placeholder="Filter status cards…" aria-label="Filter status cards" autocomplete="off">
            </div>
            <button type="button" class="rd-btn rd-btn-ghost" id="rd_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="rd-btn rd-btn-ghost" id="rd_refresh_page" title="Refresh" aria-label="Refresh dashboard">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="rd-btn rd-btn-ghost" id="rd_print_page" title="Print" aria-label="Print dashboard">
                <i class="fas fa-print"></i>
            </button>
            @if (auth()->user()->can('edit_repair_settings'))
                <a class="rd-btn rd-btn-ghost" href="{{ action([\Modules\Repair\Http\Controllers\RepairSettingsController::class, 'index']) }}" title="Settings" aria-label="Repair settings">
                    <i class="fas fa-cog"></i>
                </a>
            @endif
            @can('job_sheet.create')
                <a class="rd-btn rd-btn-primary" href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create']) }}">
                    <i class="fas fa-plus"></i> @lang('repair::lang.add_job_sheet')
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI Cards (computed from existing dashboard data) --}}
    <div class="rd-kpi-grid" aria-label="Repair KPIs">
        <div class="rd-kpi tone-blue">
            <div class="rd-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
            <span class="rd-kpi-label">Total Job Sheets</span>
            <span class="rd-kpi-value rd-counter" data-count="{{ (int) $rd_total_jobs }}">0</span>
            <span class="rd-kpi-hint">Across all statuses</span>
        </div>
        <div class="rd-kpi tone-purple">
            <div class="rd-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <span class="rd-kpi-label">Active Statuses</span>
            <span class="rd-kpi-value rd-counter" data-count="{{ (int) $rd_status_count }}">0</span>
            <span class="rd-kpi-hint">Configured repair stages</span>
        </div>
        <div class="rd-kpi tone-teal">
            <div class="rd-kpi-icon"><i class="fas fa-user-cog"></i></div>
            <span class="rd-kpi-label">Technicians</span>
            <span class="rd-kpi-value rd-counter" data-count="{{ (int) $rd_staff_count }}">0</span>
            <span class="rd-kpi-hint">Service staff with jobs</span>
        </div>
        <div class="rd-kpi tone-green">
            <div class="rd-kpi-icon"><i class="fas fa-tools"></i></div>
            <span class="rd-kpi-label">Assigned Jobs</span>
            <span class="rd-kpi-value rd-counter" data-count="{{ (int) $rd_staff_jobs }}">0</span>
            <span class="rd-kpi-hint">Linked to technicians</span>
        </div>
        <div class="rd-kpi tone-orange">
            <div class="rd-kpi-icon"><i class="fas fa-fire"></i></div>
            <span class="rd-kpi-label">Top Status</span>
            <span class="rd-kpi-value rd-kpi-text">{{ $rd_top_status->status_name ?? '—' }}</span>
            <span class="rd-kpi-hint">{{ isset($rd_top_status) ? $rd_top_status->total_job_sheets . ' jobs' : 'No data' }}</span>
        </div>
        <div class="rd-kpi tone-slate">
            <div class="rd-kpi-icon"><i class="fas fa-star"></i></div>
            <span class="rd-kpi-label">Satisfaction</span>
            <span class="rd-kpi-value">—</span>
            <span class="rd-kpi-hint">Placeholder — not in backend</span>
        </div>
    </div>

    {{-- Quick Actions (existing routes only) --}}
    <div class="rd-card rd-actions-card">
        <div class="rd-card-head">
            <div>
                <h3>Quick Actions</h3>
                <p>Jump to existing repair workflows</p>
            </div>
        </div>
        <div class="rd-card-body">
            <div class="rd-action-grid">
                @can('job_sheet.create')
                    <a class="rd-action" href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create']) }}">
                        <i class="fas fa-plus-circle"></i>
                        <span>@lang('repair::lang.add_job_sheet')</span>
                    </a>
                @endcan
                @if(auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all'))
                    <a class="rd-action" href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']) }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>@lang('repair::lang.job_sheets')</span>
                    </a>
                @endif
                @if(auth()->user()->can('repair.view') || auth()->user()->can('repair.view_own'))
                    <a class="rd-action" href="{{ action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']) }}">
                        <i class="fas fa-file-invoice"></i>
                        <span>@lang('repair::lang.list_invoices')</span>
                    </a>
                @endif
                @can('repair.create')
                    <a class="rd-action" href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) . '?sub_type=repair' }}">
                        <i class="fas fa-cash-register"></i>
                        <span>@lang('repair::lang.add_invoice')</span>
                    </a>
                @endcan
                <a class="rd-action" href="{{ url('/contacts') }}">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a class="rd-action" href="{{ url('/products') }}">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                @if(auth()->user()->can('brand.view') || auth()->user()->can('brand.create'))
                    <a class="rd-action" href="{{ action([\App\Http\Controllers\BrandController::class, 'index']) }}">
                        <i class="fas fa-tags"></i>
                        <span>@lang('brand.brands')</span>
                    </a>
                @endif
                @if (auth()->user()->can('edit_repair_settings'))
                    <a class="rd-action" href="{{ action([\Modules\Repair\Http\Controllers\RepairSettingsController::class, 'index']) }}">
                        <i class="fas fa-cog"></i>
                        <span>@lang('messages.settings')</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Future-ready placeholders --}}
    <div class="rd-future-strip" aria-label="Coming soon features">
        <span class="rd-chip"><i class="fas fa-qrcode"></i> QR Tracking</span>
        <span class="rd-chip"><i class="fas fa-signature"></i> Digital Signature</span>
        <span class="rd-chip"><i class="fas fa-sms"></i> SMS / WhatsApp</span>
        <span class="rd-chip"><i class="fas fa-map-marker-alt"></i> Tech GPS</span>
        <span class="rd-chip"><i class="fas fa-robot"></i> AI Assist</span>
        <span class="rd-chip muted">UI placeholders — not connected to backend</span>
    </div>

    <div class="rd-main-grid">
        <div class="rd-main-col">
            {{-- Status Board (existing $job_sheets_by_status) --}}
            <div class="rd-card">
                <div class="rd-card-head">
                    <div>
                        <h3>@lang('repair::lang.job_sheets_by_status')</h3>
                        <p>Kanban-style overview of existing repair statuses</p>
                    </div>
                </div>
                <div class="rd-card-body">
                    <div class="rd-status-board" id="rd_status_board">
                        @forelse($job_sheets_by_status as $job_sheet)
                            <div class="rd-status-card"
                                 data-status-name="{{ strtolower($job_sheet->status_name) }}"
                                 style="--rd-status-color: {{ $job_sheet->color }};">
                                <div class="rd-status-bar"></div>
                                <div class="rd-status-body">
                                    <span class="rd-status-name">{{ $job_sheet->status_name }}</span>
                                    <span class="rd-status-count rd-counter" data-count="{{ (int) $job_sheet->total_job_sheets }}">0</span>
                                    <span class="rd-status-meta">@lang('repair::lang.total_job_sheets')</span>
                                    <div class="rd-status-progress">
                                        @php
                                            $pct = $rd_total_jobs > 0 ? min(100, round(($job_sheet->total_job_sheets / $rd_total_jobs) * 100)) : 0;
                                        @endphp
                                        <div class="rd-status-progress-fill" style="width: {{ $pct }}%; background: {{ $job_sheet->color }};"></div>
                                    </div>
                                    <span class="rd-status-pct">{{ $pct }}% of total</span>
                                </div>
                            </div>
                        @empty
                            <div class="rd-empty">
                                <div class="alert alert-info" style="margin:0;">
                                    <h4>@lang('repair::lang.no_report_found')</h4>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Charts (unchanged backends) --}}
            <div class="rd-charts-grid">
                <div class="rd-card">
                    <div class="rd-card-head">
                        <div>
                            <h3>@lang('repair::lang.trending_brands')</h3>
                            <p>Existing chart data</p>
                        </div>
                    </div>
                    <div class="rd-card-body rd-chart-body">
                        {!! $trending_brand_chart->container() !!}
                    </div>
                </div>
                <div class="rd-card">
                    <div class="rd-card-head">
                        <div>
                            <h3>@lang('repair::lang.trending_devices')</h3>
                            <p>Existing chart data</p>
                        </div>
                    </div>
                    <div class="rd-card-body rd-chart-body">
                        {!! $trending_devices_chart->container() !!}
                    </div>
                </div>
                <div class="rd-card rd-chart-wide">
                    <div class="rd-card-head">
                        <div>
                            <h3>@lang('repair::lang.trending_device_models')</h3>
                            <p>Existing chart data</p>
                        </div>
                    </div>
                    <div class="rd-card-body rd-chart-body">
                        {!! $trending_dm_chart->container() !!}
                    </div>
                </div>
            </div>
        </div>

        <aside class="rd-side-col">
            {{-- Technician Performance --}}
            @if(in_array('service_staff', $enabled_modules))
                <div class="rd-card">
                    <div class="rd-card-head">
                        <div>
                            <h3>@lang('repair::lang.job_sheets_by_service_staff')</h3>
                            <p>Technician workload from existing data</p>
                        </div>
                    </div>
                    <div class="rd-card-body">
                        @forelse($job_sheets_by_service_staff as $job_sheet)
                            @php
                                $staff_pct = $rd_staff_jobs > 0 ? min(100, round(($job_sheet->total_job_sheets / $rd_staff_jobs) * 100)) : 0;
                            @endphp
                            <div class="rd-tech-card">
                                <div class="rd-tech-avatar"><i class="fas fa-user-cog"></i></div>
                                <div class="rd-tech-info">
                                    <strong>{{ $job_sheet->service_staff }}</strong>
                                    <span>{{ $job_sheet->total_job_sheets }} @lang('repair::lang.total_job_sheets')</span>
                                    <div class="rd-tech-bar">
                                        <div class="rd-tech-bar-fill" style="width: {{ $staff_pct }}%;"></div>
                                    </div>
                                </div>
                                <div class="rd-tech-stat">{{ $staff_pct }}%</div>
                            </div>
                        @empty
                            <p class="rd-muted-note">No technician assignments yet.</p>
                        @endforelse

                        {{-- Keep original table for accessibility / print consistency --}}
                        <div class="table-responsive rd-staff-table-wrap" style="display:none;" aria-hidden="true">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                        <th>@lang('repair::lang.total_job_sheets')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($job_sheets_by_service_staff as $job_sheet)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $job_sheet->service_staff }}</td>
                                            <td>{{ $job_sheet->total_job_sheets }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Service Queue placeholders --}}
            <div class="rd-card">
                <div class="rd-card-head">
                    <div>
                        <h3>Service Queue</h3>
                        <p>Visual categories — open Job Sheets for live queue</p>
                    </div>
                </div>
                <div class="rd-card-body">
                    <div class="rd-queue-list">
                        <div class="rd-queue-item tone-blue">
                            <i class="fas fa-inbox"></i>
                            <div>
                                <strong>All Job Sheets</strong>
                                <span>{{ (int) $rd_total_jobs }} tracked</span>
                            </div>
                            @if(auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all'))
                                <a href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']) }}" class="rd-link-btn" aria-label="Open job sheets"><i class="fas fa-arrow-right"></i></a>
                            @endif
                        </div>
                        <div class="rd-queue-item tone-orange">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Urgent / Overdue</strong>
                                <span>Open job sheets to filter</span>
                            </div>
                        </div>
                        <div class="rd-queue-item tone-green">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Warranty Repairs</strong>
                                <span>Uses existing warranty links</span>
                            </div>
                        </div>
                        <div class="rd-queue-item tone-purple">
                            <i class="fas fa-star"></i>
                            <div>
                                <strong>Priority / VIP</strong>
                                <span>Placeholder category</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Integrations (read-only info) --}}
            <div class="rd-card">
                <div class="rd-card-head">
                    <div>
                        <h3>Integrations</h3>
                        <p>Available through existing modules</p>
                    </div>
                </div>
                <div class="rd-card-body">
                    <div class="rd-integ-grid">
                        <div class="rd-integ"><i class="fas fa-mobile-alt"></i> IMEI / Serial</div>
                        <div class="rd-integ"><i class="fas fa-shield-alt"></i> Warranty</div>
                        <div class="rd-integ"><i class="fas fa-box-open"></i> Parts / Stock</div>
                        <div class="rd-integ"><i class="fas fa-file-invoice-dollar"></i> Invoices</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@stop

@section('javascript')
    {!! $trending_devices_chart->script() !!}
    {!! $trending_dm_chart->script() !!}
    {!! $trending_brand_chart->script() !!}
    <script src="{{ asset('js/repair-dashboard-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
