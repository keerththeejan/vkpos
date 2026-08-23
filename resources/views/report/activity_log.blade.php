@extends('layouts.app')
@section('title', __('lang_v1.activity_log'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/activity-log-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $alg_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content alg-shell" id="alg_shell">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('lang_v1.activity_log') }}</h2>
        <p>
            {{ __('lang_v1.by') }}: <span class="alg-print-user">—</span>
            &nbsp;·&nbsp;
            {{ __('lang_v1.subject_type') }}: <span class="alg-print-subject">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="alg-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('lang_v1.activity_log') }}</p>
    </div>

    <div class="alg-header no-print" role="banner">
        <div class="alg-header-left">
            <h1>
                <span class="alg-title-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                {{ __('lang_v1.activity_log') }}
            </h1>
            <p class="alg-subtitle">System activity, user actions and enterprise audit trail</p>
            <nav class="alg-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.activity_log') }}</span>
            </nav>
        </div>
        <div class="alg-header-actions">
            <div class="alg-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="alg_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} user, action, note…" aria-label="Search activity log" autocomplete="off">
                <button type="button" class="alg-search-clear" id="alg_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="alg-search-spinner" id="alg_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="alg-btn alg-btn-icon" id="alg_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="alg-btn alg-btn-icon" id="alg_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="alg_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="alg-btn alg-btn-icon" id="alg_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="alg-btn" id="alg_refresh" title="Refresh" aria-label="Refresh activity log">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($alg_has_export)
            <div class="alg-export-wrap">
                <button type="button" class="alg-btn" id="alg_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="alg_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="alg-export-menu" id="alg_export_menu" role="menu" hidden>
                    <button type="button" class="alg-export-item" id="alg_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="alg-export-item" id="alg_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="alg-export-item" id="alg_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="alg-export-item" id="alg_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="alg-btn alg-btn-primary" id="alg_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="alg-header-meta" aria-label="Active filters">
            <div class="alg-meta"><span>{{ __('lang_v1.by') }}</span> <strong id="alg_meta_user">—</strong></div>
            <div class="alg-meta"><span>{{ __('lang_v1.subject_type') }}</span> <strong id="alg_meta_subject">—</strong></div>
            <div class="alg-meta"><span>{{ __('report.date_range') }}</span> <strong id="alg_meta_range">—</strong></div>
        </div>
        <div class="alg-settings-panel" id="alg_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="alg_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="alg_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="alg-kpi-grid no-print" aria-label="Activity summary">
        <div class="alg-kpi tone-blue">
            <div class="alg-kpi-icon"><i class="bi bi-list-ul"></i></div>
            <span class="alg-kpi-label">Matching activities</span>
            <span class="alg-kpi-value" id="alg_kpi_total">—</span>
            <span class="alg-kpi-hint">Filtered activity records</span>
        </div>
        <div class="alg-kpi tone-slate">
            <div class="alg-kpi-icon"><i class="bi bi-file-earmark-text"></i></div>
            <span class="alg-kpi-label">This page</span>
            <span class="alg-kpi-value" id="alg_kpi_page">—</span>
            <span class="alg-kpi-hint">Rows on the current page</span>
        </div>
    </div>

    <div class="alg-card alg-filters-card no-print">
        <div class="alg-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>User, subject type and date range</p>
            </div>
            <button type="button" class="alg-btn alg-btn-ghost" id="alg_filters_toggle" aria-expanded="true" aria-controls="alg_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="alg-card-body" id="alg_filters_body">
            <div class="alg-filter-grid">
                <div class="form-group">
                    {!! Form::label('al_users_filter', __( 'lang_v1.by' ) . ':') !!}
                    {!! Form::select('al_users_filter', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'al_users_filter', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('subject_type', __( 'lang_v1.subject_type' ) . ':') !!}
                    {!! Form::select('subject_type', $transaction_types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'subject_type', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('al_date_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('al_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>

            <div class="alg-period-chips" id="alg_subject_chips" role="group" aria-label="Subject type">
                <button type="button" class="alg-chip is-active" data-subject="">ALL</button>
                @foreach($transaction_types as $key => $label)
                    <button type="button" class="alg-chip" data-subject="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>

            <div class="alg-period-chips" id="alg_period_chips" role="group" aria-label="Activity period">
                <button type="button" class="alg-chip" data-period="all">All</button>
                <button type="button" class="alg-chip" data-period="today">Today</button>
                <button type="button" class="alg-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="alg-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="alg-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="alg-chip" data-period="this_month">This Month</button>
                <button type="button" class="alg-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="alg-chip" data-period="this_year">This Year</button>
                <button type="button" class="alg-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="alg-filter-actions">
                <button type="button" class="alg-btn alg-btn-primary" id="alg_apply_filters">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="alg-btn" id="alg_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="alg-btn" id="alg_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($alg_has_export)
                <button type="button" class="alg-btn" id="alg_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="alg-exception-strip no-print" id="alg_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="alg-alert alg-alert-error no-print" id="alg_error" hidden role="alert">
        <div>
            <strong>Unable to load activity log.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="alg-btn alg-btn-primary" id="alg_retry">Retry</button>
    </div>

    <div class="alg-card alg-table-card">
        <div class="alg-card-head no-print">
            <div>
                <h2>{{ __('lang_v1.activity_log') }}</h2>
                <p>Chronological audit records — newest first</p>
            </div>
        </div>
        <div class="alg-card-body alg-table-body">
            <div class="alg-table-loading no-print" id="alg_table_loading" hidden>
                <div class="alg-skeleton-row"></div>
                <div class="alg-skeleton-row"></div>
                <div class="alg-skeleton-row"></div>
            </div>
            <div class="alg-empty no-print" id="alg_empty" hidden>
                <div class="alg-empty-icon" aria-hidden="true">📝</div>
                <strong>No activity records found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="alg-btn alg-btn-primary" id="alg_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="activity_log_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.date')</th>
                            <th>@lang('lang_v1.subject_type')</th>
                            <th>@lang('messages.action')</th>
                            <th>@lang('lang_v1.by')</th>
                            <th>@lang('brand.note')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <p class="alg-page-note no-print">Matching activities is the filtered DataTables count. This log records model changes (contacts, users, sales, purchases and related documents). It is not a login/logout trail.</p>

    <div class="alg-export-bar no-print">
        @if($alg_has_export)
        <button type="button" class="alg-btn" id="alg_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="alg-btn" id="alg_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="alg-btn" id="alg_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="alg-btn alg-btn-primary" id="alg_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="alg-drawer" id="alg_drawer" role="dialog" aria-modal="true" aria-labelledby="alg_drawer_title" aria-hidden="true">
    <div class="alg-drawer-head">
        <div>
            <h3 id="alg_drawer_title">Activity</h3>
            <p class="alg-drawer-sku" id="alg_drawer_sub">—</p>
        </div>
        <button type="button" class="alg-btn alg-btn-icon" id="alg_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="alg-drawer-status" id="alg_drawer_status"></div>
    <dl class="alg-drawer-dl" id="alg_drawer_dl"></dl>
</aside>
<div class="alg-drawer-backdrop" id="alg_drawer_backdrop" hidden></div>
<div class="alg-toast-host" id="alg_toast_host" aria-live="polite"></div>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
        $('#al_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#al_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            activity_log_table.ajax.reload();
        });
        $('#al_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#al_date_filter').val('');
            activity_log_table.ajax.reload();
        });

        activity_log_table = $('#activity_log_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader:false,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": '{{action([\App\Http\Controllers\ReportController::class, 'activityLog'])}}',
                "data": function ( d ) {
                    var start_date = '';
                    var end_date = '';
                    if ($('#al_date_filter').val()) {
                        d.start_date = $('input#al_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#al_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }

                    d.user_id = $('#al_users_filter').val();
                    d.subject_type = $('#subject_type').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'created_at'  },
                { data: 'subject_type', "orderable": false, "searchable": false},
                { data: 'description', name: 'description'},
                { data: 'created_by', name: 'created_by'},
                { data: 'note', name: 'note'}
            ]
        });  

        $(document).on('change', '#al_users_filter, #subject_type', function(){
            activity_log_table.ajax.reload();
        })
    });
</script>
<script src="{{ asset('js/activity-log-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
