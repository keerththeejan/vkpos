@extends('layouts.app')
@section('title', __('report.register_report'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/register-report-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $rgr_has_export = auth()->user()->can('view_export_buttons');
@endphp

@section('content')

<section class="content rgr-shell" id="rgr_shell"
    data-open="{{ __('cash_register.open') }}"
    data-close="{{ __('cash_register.close') }}">

    <div class="print_section print_table_part">
        <h2>{{ session()->get('business.name') }} — {{ __('report.register_report') }}</h2>
        <p>
            {{ __('report.user') }}: <span class="rgr-print-user">—</span>
            &nbsp;·&nbsp;
            {{ __('sale.status') }}: <span class="rgr-print-status">—</span>
            &nbsp;·&nbsp;
            {{ __('report.date_range') }}: <span class="rgr-print-range">—</span>
        </p>
        <p>{{ session()->get('business.name') }} · {{ __('report.register_report') }}</p>
    </div>

    <div class="rgr-header no-print" role="banner">
        <div class="rgr-header-left">
            <h1>
                <span class="rgr-title-icon" aria-hidden="true"><i class="bi bi-cash-register"></i></span>
                {{ __('report.register_report') }}
            </h1>
            <p class="rgr-subtitle">Cash register sessions, POS activity and financial reconciliation</p>
            <nav class="rgr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Reports</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('report.register_report') }}</span>
            </nav>
        </div>
        <div class="rgr-header-actions">
            <div class="rgr-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="rgr_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} user, location…" aria-label="Search register report" autocomplete="off">
                <button type="button" class="rgr-search-clear" id="rgr_search_clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="rgr-search-spinner" id="rgr_search_spinner" hidden aria-hidden="true">
                    <i class="bi bi-arrow-repeat"></i>
                </span>
            </div>
            <button type="button" class="rgr-btn rgr-btn-icon" id="rgr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="rgr-btn rgr-btn-icon" id="rgr_settings_toggle" title="Settings" aria-label="Dashboard settings" aria-expanded="false" aria-controls="rgr_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="rgr-btn rgr-btn-icon" id="rgr_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="rgr-btn" id="rgr_refresh" title="Refresh" aria-label="Refresh report">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            @if($rgr_has_export)
            <div class="rgr-export-wrap">
                <button type="button" class="rgr-btn" id="rgr_export_toggle" aria-haspopup="true" aria-expanded="false" aria-controls="rgr_export_menu">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="rgr-export-menu" id="rgr_export_menu" role="menu" hidden>
                    <button type="button" class="rgr-export-item" id="rgr_export_excel" role="menuitem"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="rgr-export-item" id="rgr_export_csv" role="menuitem"><i class="bi bi-filetype-csv"></i> CSV</button>
                    <button type="button" class="rgr-export-item" id="rgr_export_pdf" role="menuitem"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button type="button" class="rgr-export-item" id="rgr_colvis" role="menuitem"><i class="bi bi-layout-three-columns"></i> Columns</button>
                </div>
            </div>
            @endif
            <button type="button" class="rgr-btn rgr-btn-primary" id="rgr_print" aria-label="Print">
                <i class="bi bi-printer"></i> {{ __('messages.print') }}
            </button>
        </div>
        <div class="rgr-header-meta" aria-label="Active filters">
            <div class="rgr-meta"><span>{{ __('report.user') }}</span> <strong id="rgr_meta_user">—</strong></div>
            <div class="rgr-meta"><span>{{ __('sale.status') }}</span> <strong id="rgr_meta_status">—</strong></div>
            <div class="rgr-meta"><span>{{ __('report.date_range') }}</span> <strong id="rgr_meta_range">—</strong></div>
        </div>
        <div class="rgr-settings-panel" id="rgr_settings_panel" role="dialog" aria-label="Dashboard settings" hidden>
            <label><input type="checkbox" id="rgr_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="rgr_set_compact"> Compact table</label>
        </div>
    </div>

    <div class="rgr-kpi-grid no-print" aria-label="Register summary">
        <div class="rgr-kpi tone-blue">
            <div class="rgr-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="rgr-kpi-label">Matching sessions</span>
            <span class="rgr-kpi-value" id="rgr_kpi_sessions">—</span>
            <span class="rgr-kpi-hint">Filtered register sessions</span>
        </div>
        <div class="rgr-kpi tone-green">
            <div class="rgr-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <span class="rgr-kpi-label">{{ __('sale.total') }}</span>
            <span class="rgr-kpi-value" id="rgr_kpi_total">—</span>
            <span class="rgr-kpi-hint">This page</span>
        </div>
        <div class="rgr-kpi tone-teal">
            <div class="rgr-kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <span class="rgr-kpi-label">{{ __('cash_register.total_cash') }}</span>
            <span class="rgr-kpi-value" id="rgr_kpi_cash">—</span>
            <span class="rgr-kpi-hint">This page</span>
        </div>
        <div class="rgr-kpi tone-violet">
            <div class="rgr-kpi-icon"><i class="bi bi-credit-card"></i></div>
            <span class="rgr-kpi-label">{{ __('cash_register.total_card_slips') }}</span>
            <span class="rgr-kpi-value" id="rgr_kpi_card">—</span>
            <span class="rgr-kpi-hint">This page</span>
        </div>
    </div>

    <div class="rgr-card rgr-filters-card no-print">
        <div class="rgr-card-head">
            <div>
                <h2>{{ __('report.filters') }}</h2>
                <p>User, status and open date</p>
            </div>
            <button type="button" class="rgr-btn rgr-btn-ghost" id="rgr_filters_toggle" aria-expanded="true" aria-controls="rgr_filters_body">
                <i class="bi bi-chevron-up"></i> Hide
            </button>
        </div>
        <div class="rgr-card-body" id="rgr_filters_body">
            {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'register_report_filter_form' ]) !!}
            <div class="rgr-filter-grid">
                <div class="form-group">
                    {!! Form::label('register_user_id',  __('report.user') . ':') !!}
                    {!! Form::select('register_user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('register_status',  __('sale.status') . ':') !!}
                    {!! Form::select('register_status', ['open' => __('cash_register.open'), 'close' => __('cash_register.close')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('register_report_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('register_report_date_range', null , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'register_report_date_range', 'readonly']); !!}
                </div>
            </div>
            {!! Form::close() !!}

            <div class="rgr-period-chips" id="rgr_status_chips" role="group" aria-label="Register status">
                <button type="button" class="rgr-chip is-active" data-status="">ALL</button>
                <button type="button" class="rgr-chip" data-status="open">{{ __('cash_register.open') }}</button>
                <button type="button" class="rgr-chip" data-status="close">{{ __('cash_register.close') }}</button>
            </div>

            <div class="rgr-period-chips" id="rgr_period_chips" role="group" aria-label="Register period">
                <button type="button" class="rgr-chip" data-period="all">All</button>
                <button type="button" class="rgr-chip" data-period="today">Today</button>
                <button type="button" class="rgr-chip" data-period="yesterday">Yesterday</button>
                <button type="button" class="rgr-chip" data-period="last_7_days">Last 7 Days</button>
                <button type="button" class="rgr-chip" data-period="last_30_days">Last 30 Days</button>
                <button type="button" class="rgr-chip" data-period="this_month">This Month</button>
                <button type="button" class="rgr-chip" data-period="last_month">Previous Month</button>
                <button type="button" class="rgr-chip" data-period="this_year">This Year</button>
                <button type="button" class="rgr-chip" data-period="custom">Custom Range</button>
            </div>

            <div class="rgr-filter-actions">
                <button type="submit" form="register_report_filter_form" class="rgr-btn rgr-btn-primary">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" class="rgr-btn" id="rgr_reset_filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button type="button" class="rgr-btn" id="rgr_focus_search">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($rgr_has_export)
                <button type="button" class="rgr-btn" id="rgr_export_excel_alt">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="rgr-exception-strip no-print" id="rgr_exception_strip" hidden aria-label="This-page exceptions"></div>

    <div class="rgr-alert rgr-alert-error no-print" id="rgr_error" hidden role="alert">
        <div>
            <strong>Unable to load register report.</strong>
            <p>Please try again.</p>
        </div>
        <button type="button" class="rgr-btn rgr-btn-primary" id="rgr_retry">Retry</button>
    </div>

    <div class="rgr-card rgr-table-card">
        <div class="rgr-card-head no-print">
            <div>
                <h2>{{ __('report.register_report') }}</h2>
                <p>Register sessions with payment method totals</p>
            </div>
        </div>
        <div class="rgr-card-body rgr-table-body">
            <div class="rgr-table-loading no-print" id="rgr_table_loading" hidden>
                <div class="rgr-skeleton-row"></div>
                <div class="rgr-skeleton-row"></div>
                <div class="rgr-skeleton-row"></div>
            </div>
            <div class="rgr-empty no-print" id="rgr_empty" hidden>
                <div class="rgr-empty-icon" aria-hidden="true">💰</div>
                <strong>No register sessions found</strong>
                <p>Try changing your search or filters.</p>
                <button type="button" class="rgr-btn rgr-btn-primary" id="rgr_empty_clear">Clear Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="register_report_table">
                    <thead>
                        <tr>
                            <th>@lang('report.open_time')</th>
                            <th>@lang('report.close_time')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('report.user')</th>
                            <th>@lang('cash_register.total_card_slips')</th>
                            <th>@lang('cash_register.total_cheques')</th>
                            <th>@lang('cash_register.total_cash')</th>
                            <th>@lang('lang_v1.total_bank_transfer')</th>
                            <th>@lang('lang_v1.total_advance_payment')</th>
                            <th>{{$payment_types['custom_pay_1']}}</th>
                            <th>{{$payment_types['custom_pay_2']}}</th>
                            <th>{{$payment_types['custom_pay_3']}}</th>
                            <th>{{$payment_types['custom_pay_4']}}</th>
                            <th>{{$payment_types['custom_pay_5']}}</th>
                            <th>{{$payment_types['custom_pay_6']}}</th>
                            <th>{{$payment_types['custom_pay_7']}}</th>
                            <th>@lang('cash_register.other_payments')</th>
                            <th>@lang('sale.total')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_total_card_payment"></td>
                            <td class="footer_total_cheque_payment"></td>
                            <td class="footer_total_cash_payment"></td>
                            <td class="footer_total_bank_transfer_payment"></td>
                            <td class="footer_total_advance_payment"></td>'
                            <td class="footer_total_custom_pay_1"></td>
                            <td class="footer_total_custom_pay_2"></td>
                            <td class="footer_total_custom_pay_3"></td>
                            <td class="footer_total_custom_pay_4"></td>
                            <td class="footer_total_custom_pay_5"></td>
                            <td class="footer_total_custom_pay_6"></td>
                            <td class="footer_total_custom_pay_7"></td>
                            <td class="footer_total_other_payments"></td>
                            <td class="footer_total"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <details class="rgr-card no-print" id="rgr_paymix_card">
        <summary class="rgr-card-head">
            <div>
                <h2>This-page payment mix</h2>
                <p>Copied from the table footer for the current page</p>
            </div>
        </summary>
        <div class="rgr-card-body">
            <div class="rgr-paymix" id="rgr_paymix"></div>
        </div>
    </details>

    <p class="rgr-page-note no-print">Matching sessions is the filtered DataTables count. Amounts copy the table footer for the current page. Opening cash, expected closing and cash difference are in the existing View Register details — they are not calculated here.</p>

    <div class="rgr-export-bar no-print">
        @if($rgr_has_export)
        <button type="button" class="rgr-btn" id="rgr_export_excel_foot"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button type="button" class="rgr-btn" id="rgr_export_csv_foot"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="rgr-btn" id="rgr_export_pdf_foot"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        @endif
        <button type="button" class="rgr-btn rgr-btn-primary" id="rgr_print_foot">
            <i class="bi bi-printer"></i> {{ __('messages.print') }}
        </button>
    </div>
</section>

<aside class="rgr-drawer" id="rgr_drawer" role="dialog" aria-modal="true" aria-labelledby="rgr_drawer_title" aria-hidden="true">
    <div class="rgr-drawer-head">
        <div>
            <h3 id="rgr_drawer_title">Register session</h3>
            <p class="rgr-drawer-sku" id="rgr_drawer_user">—</p>
        </div>
        <button type="button" class="rgr-btn rgr-btn-icon" id="rgr_drawer_close" aria-label="Close details">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="rgr-drawer-status" id="rgr_drawer_status"></div>

    <details class="rgr-drawer-section" open>
        <summary>Overview</summary>
        <dl class="rgr-drawer-dl">
            <div><dt>@lang('report.open_time')</dt><dd id="rgr_drawer_open">—</dd></div>
            <div><dt>@lang('report.close_time')</dt><dd id="rgr_drawer_close">—</dd></div>
            <div><dt>@lang('sale.location')</dt><dd id="rgr_drawer_location">—</dd></div>
            <div><dt>@lang('report.user')</dt><dd id="rgr_drawer_user2">—</dd></div>
        </dl>
    </details>
    <details class="rgr-drawer-section" open>
        <summary>Payments</summary>
        <dl class="rgr-drawer-dl" id="rgr_drawer_payments"></dl>
    </details>
    <details class="rgr-drawer-section" id="rgr_drawer_close_section" hidden>
        <summary>Closing</summary>
        <dl class="rgr-drawer-dl">
            <div id="rgr_row_closing"><dt>Closing amount</dt><dd id="rgr_drawer_closing">—</dd></div>
            <div id="rgr_row_note"><dt>Note</dt><dd id="rgr_drawer_note">—</dd></div>
        </dl>
        <ul class="rgr-denom-list" id="rgr_drawer_denoms" hidden></ul>
    </details>
    <div class="rgr-drawer-actions" id="rgr_drawer_actions"></div>
</aside>
<div class="rgr-drawer-backdrop" id="rgr_drawer_backdrop" hidden></div>
<div class="rgr-toast-host" id="rgr_toast_host" aria-live="polite"></div>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/register-report-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
