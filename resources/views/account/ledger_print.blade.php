<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $business_name }} - @lang('lang_v1.ledger_title')</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #000; font-size: 12px; margin: 0; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 15px; margin: 4px 0 0; letter-spacing: 0.06em; text-transform: uppercase; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #bbb; padding: 4px 6px; vertical-align: top; }
        th { text-align: left; border-bottom: 2px solid #000; font-size: 11px; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .opening td { background: #f3f3f3; font-weight: bold; }
        .total td { font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .meta { margin-top: 10px; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">@lang('messages.print')</button>
    <h1>{{ $business_name }}</h1>
    <h2>@lang('lang_v1.ledger_title')</h2>
    <p>@lang('lang_v1.ledger_statement')</p>
    <div class="meta">
        <div><strong>@lang('account.account'):</strong> {{ $statement['account']['code'] }} - {{ $statement['account']['name'] }}</div>
        <div><strong>@lang('account.account_type'):</strong> {{ $statement['account']['group'] }}</div>
        <div><strong>@lang('lang_v1.ledger_period'):</strong> {{ $period_label }}</div>
        <div><strong>@lang('lang_v1.ledger_location'):</strong> {{ $location_name }}</div>
        <div><strong>@lang('lang_v1.opening_balance'):</strong> {{ $statement['opening_display'] }}</div>
    </div>
    @if (empty($statement['rows']))
        <p style="margin-top: 16px;"><strong>@lang('lang_v1.ledger_none_title')</strong><br>@lang('lang_v1.ledger_none_help')</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>@lang('lang_v1.date')</th>
                    <th>@lang('lang_v1.voucher_no')</th>
                    <th>@lang('lang_v1.type')</th>
                    <th>@lang('lang_v1.ref_no')</th>
                    <th>@lang('lang_v1.description')</th>
                    <th class="num">@lang('account.debit')</th>
                    <th class="num">@lang('account.credit')</th>
                    <th class="num">@lang('lang_v1.balance')</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening">
                    <td colspan="5">@lang('lang_v1.opening_balance')</td>
                    <td class="num">-</td>
                    <td class="num">-</td>
                    <td class="num">{{ $statement['opening_display'] }}</td>
                </tr>
                @foreach ($statement['rows'] as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['voucher'] }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td>{{ $row['reference'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="num">{{ $row['debit'] }}</td>
                        <td class="num">{{ $row['credit'] }}</td>
                        <td class="num">{{ $row['balance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="5">@lang('sale.total')</td>
                    <td class="num">{{ $statement['debit_display'] }}</td>
                    <td class="num">{{ $statement['credit_display'] }}</td>
                    <td class="num">{{ $statement['closing_display'] }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
    <p style="margin-top: 12px;"><strong>@lang('lang_v1.ledger_closing'):</strong> {{ $statement['closing_display'] }}</p>
    <p>@lang('lang_v1.ledger_generated'): {{ $generated_at }}</p>
    <script>
        window.addEventListener('load', function () {
            if (window.location.search.indexOf('auto=1') !== -1) {
                window.print();
            }
        });
    </script>
</body>
</html>
