@extends('layouts.app')
@section('title', __('lang_v1.ledger_title'))

@section('css')
<style>
    .vk-ledger-page { max-width: 100%; }
    .vk-ledger-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .vk-ledger-page h1 { margin: 0; font-size: 22px; letter-spacing: 0.04em; text-transform: uppercase; color: #0f2744; font-weight: 700; }
    .vk-ledger-page .vk-ledger-sub { margin: 2px 0 0; color: #1e3a5f; font-size: 14px; font-weight: 600; }
    .vk-ledger-page .vk-ledger-help { margin: 2px 0 0; color: #64748b; font-size: 12px; }
    .vk-ledger-head-actions .btn { margin-left: 4px; }
    .vk-ledger-filters { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 12px 8px; margin-bottom: 12px; }
    .vk-ledger-filters h2 { margin: 0 0 10px; font-size: 14px; font-weight: 700; color: #0f2744; }
    .vk-acc-opt { line-height: 1.25; padding: 2px 0; }
    .vk-acc-opt strong { display: block; font-size: 12px; color: #0f2744; }
    .vk-acc-opt span { display: block; font-size: 13px; }
    .vk-acc-opt small { display: block; color: #64748b; font-size: 11px; }
    .vk-badge { display: inline-block; margin-left: 4px; padding: 0 6px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.02em; vertical-align: middle; }
    .vk-badge-cancel { background: #fee2e2; color: #991b1b; }
    .vk-badge-draft { background: #fef3c7; color: #92400e; }
    .vk-ledger-filters .form-group { margin-bottom: 8px; }
    .vk-ledger-filters label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #111827; }
    .vk-ledger-presets { margin: 2px 0 8px; }
    .vk-ledger-presets .btn { margin: 0 4px 4px 0; }
    .vk-ledger-actions .btn { margin: 0 4px 4px 0; }
    .vk-ledger-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
    .vk-ledger-account { padding: 12px 14px; margin-bottom: 10px; }
    .vk-ledger-account h2 { margin: 0; font-size: 18px; }
    .vk-ledger-account p { margin: 2px 0 0; color: #4b5563; font-size: 13px; }
    .vk-ledger-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
    .vk-ledger-summary div { background: #fff; border: 1px solid #e5e7eb; border-top: 3px solid #1d4ed8; border-radius: 8px; padding: 8px 12px; }
    .vk-ledger-summary span { display: block; color: #64748b; font-size: 11px; letter-spacing: 0.03em; text-transform: uppercase; }
    .vk-ledger-summary strong { display: block; margin-top: 2px; font-size: 15px; color: #0f2744; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .vk-ledger-kicker { margin: 0 0 2px; font-size: 11px; letter-spacing: 0.06em; text-transform: uppercase; color: #1d4ed8; font-weight: 700; }
    .vk-ledger-meta { display: flex; flex-wrap: wrap; gap: 8px 22px; margin-top: 8px; }
    .vk-ledger-meta span { display: block; color: #64748b; font-size: 11px; }
    .vk-ledger-meta strong { font-size: 13px; font-weight: 600; color: #0f2744; font-variant-numeric: tabular-nums; }
    .vk-ledger-scroll { max-height: 62vh; overflow: auto; }
    .vk-ledger-table { margin-bottom: 0; }
    .vk-ledger-table th, .vk-ledger-table td { vertical-align: middle; }
    .vk-ledger-table { font-size: 13px; }
    .vk-ledger-table tbody tr:hover td { background: #f8fafc; }
    .vk-ledger-table thead th { position: sticky; top: 0; z-index: 2; background: #f8fafc; font-size: 11px; letter-spacing: 0.03em; text-transform: uppercase; color: #334155; border-bottom: 2px solid #0f2744; }
    .vk-ledger-num { white-space: nowrap; font-variant-numeric: tabular-nums; text-align: right; }
    .vk-ledger-opening td { background: #f8fafc; font-weight: 600; }
    .vk-ledger-total td { font-weight: 700; border-top: 2px solid #111; background: #f8fafc; }
    .vk-ledger-cancelled td { color: #6b7280; text-decoration: line-through; }
    .vk-ledger-empty, .vk-ledger-none { text-align: center; padding: 28px 16px; }
    .vk-ledger-empty h3, .vk-ledger-none h3 { margin: 0 0 6px; font-size: 16px; }
    .vk-ledger-empty p, .vk-ledger-none p { margin: 0; color: #6b7280; }
    .vk-ledger-page :focus-visible { outline: 2px solid #111827; outline-offset: 2px; }
    @media (max-width: 991px) {
        .vk-ledger-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .vk-ledger-page { overflow-x: hidden; }
        .vk-ledger-head { flex-direction: column; }
    }
    @media print {
        .no-print, .side-bar, .main-header, .main-footer, .scrolltop, .vk-ledger-filters { display: none !important; }
        .vk-ledger-scroll { max-height: none; overflow: visible; }
        .content-wrapper, .vk-ledger-card { margin: 0 !important; border: 0 !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<section class="content vk-ledger-page">
    <div class="vk-ledger-head">
        <div>
            <h1>@lang('lang_v1.ledger_title')</h1>
            <p class="vk-ledger-sub">@lang('lang_v1.ledger_statement')</p>
            <p class="vk-ledger-help">@lang('lang_v1.ledger_help')</p>
        </div>
        <div class="vk-ledger-head-actions no-print">
            <a href="#" class="btn btn-default btn-sm disabled" id="ledger_print" target="_blank" aria-disabled="true"><i class="fa fa-print" aria-hidden="true"></i> @lang('messages.print')</a>
            <a href="#" class="btn btn-default btn-sm disabled" id="ledger_excel" aria-disabled="true"><i class="fa fa-file-excel" aria-hidden="true"></i> @lang('lang_v1.export_excel')</a>
            <a href="#" class="btn btn-default btn-sm disabled" id="ledger_pdf" aria-disabled="true"><i class="fa fa-file-pdf" aria-hidden="true"></i> @lang('lang_v1.export_pdf')</a>
        </div>
    </div>
    <div class="vk-ledger-filters no-print">
        <h2>@lang('lang_v1.ledger_filters')</h2>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="ledger_account">@lang('account.account')</label>
                    <select id="ledger_account" class="form-control" style="width:100%" aria-label="@lang('lang_v1.ledger_search_account')"></select>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_start">@lang('lang_v1.ledger_date_from')</label>
                    <input type="text" id="ledger_start" class="form-control" value="{{ $default_start }}" readonly>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_end">@lang('lang_v1.ledger_date_to')</label>
                    <input type="text" id="ledger_end" class="form-control" value="{{ $default_end }}" readonly>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_location">@lang('purchase.business_location')</label>
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control', 'id' => 'ledger_location', 'style' => 'width:100%', 'aria-label' => __('purchase.business_location')]) !!}
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_voucher">@lang('lang_v1.type')</label>
                    {!! Form::select('voucher', $voucher_types, null, ['class' => 'form-control', 'id' => 'ledger_voucher', 'aria-label' => __('lang_v1.type')]) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="ledger_search">@lang('lang_v1.search')</label>
                    <input type="text" id="ledger_search" class="form-control" placeholder="@lang('lang_v1.ledger_search_placeholder')" aria-label="@lang('lang_v1.search')">
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_status">@lang('sale.status')</label>
                    <select id="ledger_status" class="form-control">
                        <option value="posted">@lang('lang_v1.ledger_posted')</option>
                        <option value="draft">@lang('lang_v1.ledger_draft')</option>
                        <option value="all">@lang('lang_v1.all')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="form-group">
                    <label for="ledger_per_page">@lang('lang_v1.ledger_per_page')</label>
                    <select id="ledger_per_page" class="form-control">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="checkbox" style="margin-top: 24px;">
                    <label for="ledger_include_cancelled"><input type="checkbox" id="ledger_include_cancelled"> @lang('lang_v1.ledger_include_cancelled')</label>
                </div>
            </div>
        </div>
        <div class="vk-ledger-presets" role="group" aria-label="@lang('report.date_range')">
            <button type="button" class="btn btn-default btn-sm" data-preset="today">@lang('home.today')</button>
            <button type="button" class="btn btn-default btn-sm" data-preset="week">@lang('lang_v1.ledger_this_week')</button>
            <button type="button" class="btn btn-primary btn-sm" data-preset="month">@lang('lang_v1.ledger_this_month')</button>
            <button type="button" class="btn btn-default btn-sm" data-preset="prev">@lang('lang_v1.ledger_previous_month')</button>
            <button type="button" class="btn btn-default btn-sm" data-preset="fy">@lang('lang_v1.ledger_this_fy')</button>
            <button type="button" class="btn btn-default btn-sm" data-preset="custom">@lang('lang_v1.ledger_custom')</button>
        </div>
        <div class="vk-ledger-actions">
            <button type="button" class="btn btn-primary btn-sm" id="ledger_search_btn"><i class="fa fa-search" aria-hidden="true"></i> @lang('lang_v1.search')</button>
            <button type="button" class="btn btn-default btn-sm" id="ledger_reset_btn">@lang('lang_v1.reset')</button>
        </div>
    </div>

    <div id="ledger_identity" class="vk-ledger-card vk-ledger-account" style="display:none;"></div>
    <div id="ledger_summary" class="vk-ledger-summary" style="display:none;"></div>
    <p id="ledger_filter_note" class="text-muted" style="display:none;"></p>

    <div class="vk-ledger-card">
        <div id="ledger_loading" class="vk-ledger-empty" style="display:none;" role="status">
            <i class="fas fa-sync fa-spin" aria-hidden="true"></i>
        </div>
        <div id="ledger_prompt" class="vk-ledger-empty" role="status">
            <h3>@lang('lang_v1.ledger_empty_title')</h3>
            <p>@lang('lang_v1.ledger_empty_help')</p>
        </div>
        <div id="ledger_none" class="vk-ledger-none" style="display:none;" role="status">
            <h3>@lang('lang_v1.ledger_none_title')</h3>
            <p>@lang('lang_v1.ledger_none_help')</p>
            <p id="ledger_none_meta" style="margin-top: 8px;"></p>
            <button type="button" class="btn btn-default btn-sm" id="ledger_change_filters" style="margin-top: 10px;">@lang('lang_v1.ledger_change_filters')</button>
        </div>
        <div id="ledger_table_wrap" class="table-responsive vk-ledger-scroll" style="display:none;">
            <table class="table table-bordered vk-ledger-table" id="ledger_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.date')</th>
                        <th class="text-center">@lang('lang_v1.voucher_no')</th>
                        <th class="text-center">@lang('lang_v1.type')</th>
                        <th>@lang('lang_v1.ref_no')</th>
                        <th>@lang('lang_v1.description')</th>
                        <th class="vk-ledger-num">@lang('account.debit')</th>
                        <th class="vk-ledger-num">@lang('account.credit')</th>
                        <th class="vk-ledger-num">@lang('lang_v1.balance')</th>
                        <th class="text-center no-print">@lang('lang_v1.ledger_view_row')</th>
                    </tr>
                </thead>
                <tbody id="ledger_body"></tbody>
                <tfoot id="ledger_foot"></tfoot>
            </table>
        </div>
        <div id="ledger_pager" class="clearfix no-print" style="display:none; padding: 10px 12px;">
            <span id="ledger_pager_text" class="text-muted"></span>
            <div class="pull-right">
                <button type="button" class="btn btn-default btn-sm" id="ledger_prev">@lang('lang_v1.previous')</button>
                <button type="button" class="btn btn-default btn-sm" id="ledger_next">@lang('lang_v1.next')</button>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="ledger_detail_modal" tabindex="-1" role="dialog" aria-labelledby="ledger_detail_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="ledger_detail_title">@lang('lang_v1.ledger_title')</h4>
            </div>
            <div class="modal-body" id="ledger_detail_body"></div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="ledger_detail_open" target="_blank">@lang('lang_v1.ledger_view_voucher')</a>
                <a href="#" class="btn btn-default" id="ledger_detail_print" target="_blank">@lang('messages.print')</a>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
    var ledgerPage = 1;
    var ledgerAccountName = '';
    var ledgerUrls = {
        data: "{{ action([\App\Http\Controllers\AccountLedgerController::class, 'data']) }}",
        accounts: "{{ action([\App\Http\Controllers\AccountLedgerController::class, 'accounts']) }}",
        excel: "{{ action([\App\Http\Controllers\AccountLedgerController::class, 'exportExcel']) }}",
        pdf: "{{ action([\App\Http\Controllers\AccountLedgerController::class, 'exportPdf']) }}",
        print: "{{ action([\App\Http\Controllers\AccountLedgerController::class, 'printLedger']) }}"
    };

    function ledgerParams(page) {
        return {
            account_key: $('#ledger_account').val(),
            start_date: $('#ledger_start').val(),
            end_date: $('#ledger_end').val(),
            location_id: $('#ledger_location').val(),
            voucher: $('#ledger_voucher').val(),
            search: $('#ledger_search').val(),
            status: $('#ledger_status').val(),
            include_cancelled: $('#ledger_include_cancelled').is(':checked') ? 1 : 0,
            page: page || ledgerPage,
            per_page: $('#ledger_per_page').val()
        };
    }

    function ledgerExportQuery() {
        var params = ledgerParams(1);
        delete params.page;
        delete params.per_page;
        return $.param(params);
    }

    function markPreset(name) {
        $('[data-preset]').removeClass('btn-primary').addClass('btn-default');
        $('[data-preset="' + name + '"]').removeClass('btn-default').addClass('btn-primary');
    }

    var ledgerDateLock = false;
    function setLedgerDates(start, end) {
        ledgerDateLock = true;
        $('#ledger_start').datepicker('setDate', start.toDate());
        $('#ledger_end').datepicker('setDate', end.toDate());
        ledgerDateLock = false;
    }

    function setExports(enabled, query) {
        $('#ledger_print, #ledger_excel, #ledger_pdf').toggleClass('disabled', !enabled).attr('aria-disabled', enabled ? 'false' : 'true');
        if (!enabled) {
            $('#ledger_print, #ledger_excel, #ledger_pdf').attr('href', '#');
            return;
        }
        $('#ledger_excel').attr('href', ledgerUrls.excel + '?' + query);
        $('#ledger_pdf').attr('href', ledgerUrls.pdf + '?' + query);
        $('#ledger_print').attr('href', ledgerUrls.print + '?' + query + '&auto=1');
    }

    function ledgerCell(text, className) {
        return $('<td>').addClass(className || '').text(text || '-');
    }

    function showLedgerEmpty() {
        $('#ledger_loading, #ledger_identity, #ledger_summary, #ledger_filter_note, #ledger_none, #ledger_table_wrap, #ledger_pager').hide();
        $('#ledger_prompt').show();
        $('#ledger_prompt p').text(@json(__('lang_v1.ledger_empty_help')));
        setExports(false);
    }

    function openLedgerDetail(row) {
        var body = $('#ledger_detail_body').empty();
        var lines = [
            [@json(__('lang_v1.voucher_no')), row.voucher],
            [@json(__('lang_v1.date')), row.date],
            [@json(__('lang_v1.type')), row.type],
            [@json(__('lang_v1.ref_no')), row.reference],
            [@json(__('lang_v1.description')), row.description],
            [@json(__('account.account')), ledgerAccountName],
            [@json(__('account.debit')), row.debit],
            [@json(__('account.credit')), row.credit],
            [@json(__('sale.status')), row.status],
            [@json(__('lang_v1.added_by')), row.created_by],
            [@json(__('lang_v1.created_at')), row.created_at]
        ];
        $.each(lines, function (_, line) {
            body.append($('<p>').append($('<strong>').text(line[0] + ': ')).append(document.createTextNode(line[1] || '-')));
        });
        $('#ledger_detail_title').text(row.voucher || @json(__('lang_v1.ledger_title')));
        if (row.url) {
            $('#ledger_detail_open').attr('href', row.url).show();
        } else {
            $('#ledger_detail_open').hide();
        }
        var printHref = $('#ledger_print').attr('href');
        if (printHref && printHref !== '#') {
            $('#ledger_detail_print').attr('href', printHref).show();
        } else {
            $('#ledger_detail_print').hide();
        }
        $('#ledger_detail_modal').modal('show');
    }

    function renderLedger(result) {
        $('#ledger_loading').hide();
        if (!result || result.error) {
            showLedgerEmpty();
            return;
        }

        var account = result.account;
        ledgerAccountName = account.code + ' - ' + account.name;
        $('#ledger_prompt, #ledger_none').hide();
        function ledgerMeta(label, value) {
            return $('<div>').append($('<span>').text(label), $('<strong>').text(value || '-'));
        }
        $('#ledger_identity').show().empty().append(
            $('<p class="vk-ledger-kicker">').text(@json(__('lang_v1.ledger_account_statement'))),
            $('<h2>').text(account.code + ' — ' + account.name),
            $('<p>').text(account.group),
            $('<div class="vk-ledger-meta">').append(
                ledgerMeta(@json(__('lang_v1.ledger_period')), result.period_label),
                ledgerMeta(@json(__('lang_v1.ledger_location')), result.location_name),
                ledgerMeta(@json(__('lang_v1.ledger_normal')), account.normal),
                ledgerMeta(@json(__('lang_v1.opening_balance')), result.opening_display)
            )
        );

        $('#ledger_summary').show().empty();
        $.each([
            [@json(__('lang_v1.opening_balance')), result.opening_display],
            [@json(__('account.debit')), result.debit_display],
            [@json(__('account.credit')), result.credit_display],
            [@json(__('lang_v1.ledger_closing')), result.closing_display]
        ], function (_, item) {
            $('#ledger_summary').append($('<div>').append($('<span>').text(item[0]), $('<strong>').text(item[1])));
        });
        $('#ledger_filter_note').toggle(!!result.filtered).text(result.filtered ? @json(__('lang_v1.ledger_filtered_note')) : '');

        if (!result.rows.length) {
            $('#ledger_table_wrap, #ledger_pager').hide();
            $('#ledger_none').show();
            $('#ledger_none_meta').text(ledgerAccountName + ' · ' + (result.period_label || ''));
        } else {
            var body = $('#ledger_body').empty();
            body.append(
                $('<tr class="vk-ledger-opening">').append(
                    $('<td colspan="5">').text(@json(__('lang_v1.opening_balance'))),
                    ledgerCell('-', 'vk-ledger-num'),
                    ledgerCell('-', 'vk-ledger-num'),
                    ledgerCell(result.opening_display, 'vk-ledger-num'),
                    $('<td class="no-print">')
                )
            );
            $.each(result.rows, function (_, row) {
                var voucherCell = $('<td class="text-center">');
                if (row.url) {
                    voucherCell.append($('<a>').attr({href: row.url, target: '_blank'}).text(row.voucher));
                } else {
                    voucherCell.text(row.voucher);
                }
                var viewBtn = $('<button type="button" class="btn btn-default btn-xs ledger-view">')
                    .text(@json(__('lang_v1.ledger_view_row')))
                    .attr('aria-label', @json(__('lang_v1.ledger_view_row')));
                var typeCell = ledgerCell(row.type, 'text-center');
                if (row.cancelled || row.status_key === 'draft') {
                    typeCell.append($('<span class="vk-badge">')
                        .addClass(row.cancelled ? 'vk-badge-cancel' : 'vk-badge-draft')
                        .text(row.status));
                }
                body.append(
                    $('<tr class="ledger-row">').toggleClass('vk-ledger-cancelled', !!row.cancelled).data('row', row).append(
                        ledgerCell(row.date),
                        voucherCell,
                        typeCell,
                        ledgerCell(row.reference),
                        ledgerCell(row.description),
                        ledgerCell(row.debit, 'vk-ledger-num'),
                        ledgerCell(row.credit, 'vk-ledger-num'),
                        ledgerCell(row.balance, 'vk-ledger-num'),
                        $('<td class="text-center no-print">').append(viewBtn)
                    )
                );
            });
            $('#ledger_foot').empty().append(
                $('<tr class="vk-ledger-total">').append(
                    $('<td colspan="5">').text(@json(__('sale.total'))),
                    ledgerCell(result.debit_display, 'vk-ledger-num'),
                    ledgerCell(result.credit_display, 'vk-ledger-num'),
                    ledgerCell(result.closing_display, 'vk-ledger-num'),
                    $('<td class="no-print">')
                )
            );
            $('#ledger_none').hide();
            $('#ledger_table_wrap, #ledger_pager').show();
        }

        $('#ledger_pager_text').text(@json(__('lang_v1.ledger_showing'))
            .replace(':from', result.from)
            .replace(':to', result.to)
            .replace(':total', result.count));
        $('#ledger_prev').prop('disabled', result.page <= 1);
        $('#ledger_next').prop('disabled', result.page >= result.last_page);
        ledgerPage = result.page;
        setExports(true, ledgerExportQuery());
    }

    function loadLedger(page) {
        if (!$('#ledger_account').val()) {
            showLedgerEmpty();
            return;
        }
        ledgerPage = page || 1;
        $('#ledger_prompt, #ledger_none, #ledger_table_wrap').hide();
        $('#ledger_loading').show();
        $.get(ledgerUrls.data, ledgerParams(ledgerPage))
            .done(renderLedger)
            .fail(function () {
                $('#ledger_loading').hide();
                $('#ledger_prompt').show();
                $('#ledger_prompt p').text(@json(__('messages.something_went_wrong')));
            });
    }

    $(function () {
        $('#ledger_start, #ledger_end').datepicker({autoclose: true, format: datepicker_date_format})
            .on('changeDate', function () { if (!ledgerDateLock) markPreset('custom'); });
        __select2($('#ledger_location'));
        $('#ledger_account').select2({
            ajax: {
                url: ledgerUrls.accounts,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {q: params.term || '', location_id: $('#ledger_location').val()};
                },
                processResults: function (data) {
                    return {results: data.results || []};
                }
            },
            placeholder: @json(__('lang_v1.ledger_search_account')),
            allowClear: true,
            width: '100%',
            templateResult: function (item) {
                if (!item.id || !item.code) return item.text;
                return $('<div class="vk-acc-opt">')
                    .append($('<strong>').text(item.code))
                    .append($('<span>').text(item.name || ''))
                    .append($('<small>').text(item.group || ''));
            },
            templateSelection: function (item) {
                return item.text || item.name || '';
            }
        }).on('select2:select', function () {
            loadLedger(1);
        }).on('select2:clear', function () {
            showLedgerEmpty();
        });

        $('[data-preset]').on('click', function () {
            var preset = $(this).data('preset');
            var now = moment();
            markPreset(preset);
            if (preset === 'custom') return;
            if (preset === 'today') setLedgerDates(now.clone(), now.clone());
            if (preset === 'week') setLedgerDates(now.clone().startOf('week'), now.clone().endOf('week'));
            if (preset === 'month') setLedgerDates(now.clone().startOf('month'), now.clone().endOf('month'));
            if (preset === 'prev') {
                var prev = now.clone().subtract(1, 'month');
                setLedgerDates(prev.clone().startOf('month'), prev.clone().endOf('month'));
            }
            if (preset === 'fy') setLedgerDates(financial_year.start.clone(), financial_year.end.clone());
            markPreset(preset);
        });

        $('#ledger_search_btn').on('click', function () { loadLedger(1); });
        $('#ledger_search').on('keydown', function (e) { if (e.key === 'Enter') loadLedger(1); });
        $('#ledger_per_page').on('change', function () { if ($('#ledger_account').val()) loadLedger(1); });
        $('#ledger_prev').on('click', function () { if (ledgerPage > 1) loadLedger(ledgerPage - 1); });
        $('#ledger_next').on('click', function () { loadLedger(ledgerPage + 1); });
        $('#ledger_print, #ledger_excel, #ledger_pdf').on('click', function (e) {
            if ($(this).hasClass('disabled')) e.preventDefault();
        });
        $('#ledger_reset_btn').on('click', function () {
            $('#ledger_account').val(null).trigger('change');
            $('#ledger_voucher').val('');
            $('#ledger_search').val('');
            $('#ledger_status').val('posted');
            $('#ledger_include_cancelled').prop('checked', false);
            $('#ledger_per_page').val('25');
            $('#ledger_location').val($('#ledger_location option:first').val()).trigger('change.select2');
            setLedgerDates(moment().startOf('month'), moment().endOf('month'));
            markPreset('month');
            showLedgerEmpty();
        });

        $('#ledger_change_filters').on('click', function () {
            var top = $('.vk-ledger-filters').offset().top - 70;
            $('html, body').animate({scrollTop: top}, 200);
            $('#ledger_account').select2('open');
        });
        $('#ledger_body').on('click', '.ledger-view', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var row = $(this).closest('tr').data('row');
            if (row) openLedgerDetail(row);
        });
    });
</script>
@endsection
