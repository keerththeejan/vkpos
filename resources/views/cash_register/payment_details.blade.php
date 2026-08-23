@php
  $cr_expected_cash = $register_details->cash_in_hand + $register_details->total_sale - $register_details->total_refund - $register_details->total_expense;
  $cr_total_payment = $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund;
  $cr_credit_sales = $details['transaction_details']->total_sales - $register_details->total_sale;
  $cr_cash_expected = $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense;

  $cr_pay_methods = [
    [
      'name' => __('cash_register.cash_payment'),
      'icon' => 'fas fa-money-bill-wave',
      'sale' => $register_details->total_cash,
      'expense' => $register_details->total_cash_expense,
    ],
    [
      'name' => __('cash_register.checque_payment'),
      'icon' => 'fas fa-money-check',
      'sale' => $register_details->total_cheque,
      'expense' => $register_details->total_cheque_expense,
    ],
    [
      'name' => __('cash_register.card_payment'),
      'icon' => 'fas fa-credit-card',
      'sale' => $register_details->total_card,
      'expense' => $register_details->total_card_expense,
    ],
    [
      'name' => __('cash_register.bank_transfer'),
      'icon' => 'fas fa-university',
      'sale' => $register_details->total_bank_transfer,
      'expense' => $register_details->total_bank_transfer_expense,
    ],
    [
      'name' => __('lang_v1.advance_payment'),
      'icon' => 'fas fa-hand-holding-usd',
      'sale' => $register_details->total_advance,
      'expense' => $register_details->total_advance_expense,
    ],
  ];

  for ($i = 1; $i <= 7; $i++) {
    $key = 'custom_pay_' . $i;
    if (array_key_exists($key, $payment_types)) {
      $sale_key = 'total_custom_pay_' . $i;
      $exp_key = 'total_custom_pay_' . $i . '_expense';
      $cr_pay_methods[] = [
        'name' => $payment_types[$key],
        'icon' => 'fas fa-wallet',
        'sale' => $register_details->{$sale_key},
        'expense' => $register_details->{$exp_key},
      ];
    }
  }

  $cr_pay_methods[] = [
    'name' => __('cash_register.other_payments'),
    'icon' => 'fas fa-ellipsis-h',
    'sale' => $register_details->total_other,
    'expense' => $register_details->total_other_expense,
  ];

  $cr_pay_max = 0;
  foreach ($cr_pay_methods as $pm) {
    $cr_pay_max = max($cr_pay_max, abs($pm['sale']), abs($pm['expense']));
  }
  if ($cr_pay_max <= 0) {
    $cr_pay_max = 1;
  }
@endphp

<div class="mini_print">
  {{-- KPI Summary Cards --}}
  <div class="cr-kpi-grid">
    <div class="cr-kpi-card tone-slate">
      <div class="cr-kpi-icon"><i class="fas fa-coins"></i></div>
      <span class="cr-kpi-label">@lang('cash_register.cash_in_hand')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></span>
      <span class="cr-kpi-hint">@lang('messages.opening')</span>
    </div>

    <div class="cr-kpi-card tone-blue">
      <div class="cr-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
      <span class="cr-kpi-label">@lang('cash_register.total_sales')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales }}</span></span>
      <span class="cr-kpi-hint">@lang('business.sale')</span>
    </div>

    <div class="cr-kpi-card tone-green">
      <div class="cr-kpi-icon"><i class="fas fa-receipt"></i></div>
      <span class="cr-kpi-label">@lang('lang_v1.total_payment')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $cr_total_payment }}</span></span>
      <span class="cr-kpi-hint">Cash based</span>
    </div>

    <div class="cr-kpi-card tone-red">
      <div class="cr-kpi-icon"><i class="fas fa-undo"></i></div>
      <span class="cr-kpi-label">@lang('cash_register.total_refund')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></span>
      <span class="cr-kpi-hint">
        @if($register_details->total_cash_refund != 0)
          Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span>
        @endif
      </span>
    </div>

    <div class="cr-kpi-card tone-orange">
      <div class="cr-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
      <span class="cr-kpi-label">@lang('report.total_expense')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</span></span>
      <span class="cr-kpi-hint">@lang('lang_v1.expense')</span>
    </div>

    <div class="cr-kpi-card tone-purple">
      <div class="cr-kpi-icon"><i class="fas fa-chart-line"></i></div>
      <span class="cr-kpi-label">@lang('lang_v1.credit_sales')</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $cr_credit_sales }}</span></span>
      <span class="cr-kpi-hint">@lang('sale.total')</span>
    </div>

    <div class="cr-kpi-card tone-green">
      <div class="cr-kpi-icon"><i class="fas fa-piggy-bank"></i></div>
      <span class="cr-kpi-label">Expected Cash</span>
      <span class="cr-kpi-value"><span class="display_currency" data-currency_symbol="true">{{ $cr_expected_cash }}</span></span>
      <span class="cr-kpi-hint">Opening + Sales − Refund − Expense</span>
    </div>
  </div>

  {{-- Payment method cards --}}
  <div class="cr-section">
    <div class="cr-section-head">
      <h3><i class="fas fa-credit-card"></i> @lang('lang_v1.payment_method')</h3>
    </div>
    <div class="cr-pay-grid">
      <div class="cr-pay-card">
        <div class="cr-pay-top">
          <span class="cr-pay-icon"><i class="fas fa-vault"></i></span>
          <h4 class="cr-pay-name">@lang('cash_register.cash_in_hand')</h4>
        </div>
        <div class="cr-pay-metric">
          <span>@lang('sale.sale')</span>
          <strong><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></strong>
        </div>
        <div class="cr-pay-metric">
          <span>@lang('lang_v1.expense')</span>
          <strong>--</strong>
        </div>
        <div class="cr-pay-net">
          <span>Net</span>
          <span><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></span>
        </div>
      </div>

      @foreach ($cr_pay_methods as $pm)
        @php
          $net = $pm['sale'] - $pm['expense'];
          $pct = min(100, round((abs($pm['sale']) / $cr_pay_max) * 100));
        @endphp
        <div class="cr-pay-card">
          <div class="cr-pay-top">
            <span class="cr-pay-icon"><i class="{{ $pm['icon'] }}"></i></span>
            <h4 class="cr-pay-name">{{ $pm['name'] }}</h4>
          </div>
          <div class="cr-pay-metric">
            <span>@lang('sale.sale')</span>
            <strong><span class="display_currency" data-currency_symbol="true">{{ $pm['sale'] }}</span></strong>
          </div>
          <div class="cr-pay-metric">
            <span>@lang('lang_v1.expense')</span>
            <strong><span class="display_currency" data-currency_symbol="true">{{ $pm['expense'] }}</span></strong>
          </div>
          <div class="cr-pay-net">
            <span>Net</span>
            <span><span class="display_currency" data-currency_symbol="true">{{ $net }}</span></span>
          </div>
          <div class="cr-progress"><span style="width: {{ $pct }}%;"></span></div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Financial timeline --}}
  <div class="cr-section">
    <div class="cr-section-head">
      <h3><i class="fas fa-calculator"></i> Financial Overview</h3>
    </div>
    <div class="cr-timeline">
      <div class="cr-timeline-item tone-slate">
        <span class="t-label">@lang('messages.opening')</span>
        <span class="t-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></span>
      </div>
      <span class="cr-timeline-op">+</span>
      <div class="cr-timeline-item tone-blue">
        <span class="t-label">@lang('business.sale')</span>
        <span class="t-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale }}</span></span>
      </div>
      <span class="cr-timeline-op">−</span>
      <div class="cr-timeline-item tone-red">
        <span class="t-label">@lang('lang_v1.refund')</span>
        <span class="t-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></span>
      </div>
      <span class="cr-timeline-op">−</span>
      <div class="cr-timeline-item tone-orange">
        <span class="t-label">@lang('lang_v1.expense')</span>
        <span class="t-value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</span></span>
      </div>
      <span class="cr-timeline-op">=</span>
      <div class="cr-timeline-item tone-result">
        <span class="t-label">@lang('sale.total')</span>
        <span class="t-value"><span class="display_currency" data-currency_symbol="true">{{ $cr_expected_cash }}</span></span>
      </div>
    </div>

    <div style="margin-top:14px;">
      <div class="cr-pay-metric">
        <span>@lang('cash_register.total_refund') breakdown</span>
        <strong><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></strong>
      </div>
      <small style="color:var(--cr-muted);display:block;margin-top:6px;">
        @if($register_details->total_cash_refund != 0)
          Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span>&nbsp;
        @endif
        @if($register_details->total_cheque_refund != 0)
          Cheque: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span>&nbsp;
        @endif
        @if($register_details->total_card_refund != 0)
          Card: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_refund }}</span>&nbsp;
        @endif
        @if($register_details->total_bank_transfer_refund != 0)
          Bank Transfer: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span>&nbsp;
        @endif
        @if(array_key_exists('custom_pay_1', $payment_types) && $register_details->total_custom_pay_1_refund != 0)
          {{$payment_types['custom_pay_1']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span>&nbsp;
        @endif
        @if(array_key_exists('custom_pay_2', $payment_types) && $register_details->total_custom_pay_2_refund != 0)
          {{$payment_types['custom_pay_2']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span>&nbsp;
        @endif
        @if(array_key_exists('custom_pay_3', $payment_types) && $register_details->total_custom_pay_3_refund != 0)
          {{$payment_types['custom_pay_3']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span>&nbsp;
        @endif
        @if($register_details->total_other_refund != 0)
          Other: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
        @endif
      </small>
    </div>

    {{-- Keep original formula text for print compatibility --}}
    <div class="help-block" style="margin-top:12px;font-size:12px;color:var(--cr-muted);">
      @lang('sale.total') =
      @format_currency($register_details->cash_in_hand) (@lang('messages.opening')) +
      @format_currency($register_details->total_sale) (@lang('business.sale')) -
      @format_currency($register_details->total_refund) (@lang('lang_v1.refund')) -
      @format_currency($register_details->total_expense) (@lang('lang_v1.expense'))
      = @format_currency($cr_expected_cash)
    </div>
  </div>
</div>

@include('cash_register.register_product_details')
