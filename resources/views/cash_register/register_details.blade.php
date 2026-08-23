<link rel="stylesheet" href="{{ asset('css/register-premium.css?v=' . ($asset_v ?? time())) }}">
@php
  $cr_expected_cash = $register_details->cash_in_hand + $register_details->total_sale - $register_details->total_refund - $register_details->total_expense;
  $open_label = \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A');
  $close_label = \Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('jS M, Y h:i A');
  $is_open = empty($register_details->closed_at);
@endphp
<div class="modal-dialog modal-lg cr-premium-dialog" role="document">
  <div class="modal-content cr-premium-content">
    <div class="modal-header cr-premium-header mini_print">
      <div>
        <h3 class="modal-title">@lang('cash_register.register_details')</h3>
        <div class="cr-header-meta">
          <span class="cr-badge {{ $is_open ? '' : 'is-closed' }}">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $is_open ? '#10B981' : '#EF4444' }};display:inline-block;"></span>
            {{ $is_open ? 'Register Open' : 'Register Closed' }}
          </span>
          <span class="cr-chip"><i class="fas fa-clock"></i> {{ $open_label }} — {{ $close_label }}</span>
          <span class="cr-chip"><i class="fas fa-map-marker-alt"></i> {{ $register_details->location_name }}</span>
          <span class="cr-chip"><i class="fas fa-user"></i> {{ $register_details->user_name }}</span>
        </div>
      </div>
      <div class="cr-header-actions">
        <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    </div>

    <div class="modal-body cr-premium-body">
      <div class="cr-layout">
        <div class="cr-layout-main">
          @include('cash_register.payment_details')

          @if(!empty($register_details->denominations))
            @php
              $total = 0;
            @endphp
            <div class="cr-section">
              <div class="cr-section-head">
                <h3><i class="fas fa-money-bill-alt"></i> @lang('lang_v1.cash_denominations')</h3>
              </div>
              <table class="table table-slim cr-denom-table">
                <thead>
                  <tr>
                    <th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
                    <th width="20%">&nbsp;</th>
                    <th width="20%" class="text-center">@lang('lang_v1.count')</th>
                    <th width="20%">&nbsp;</th>
                    <th width="20%" class="text-left">@lang('sale.subtotal')</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($register_details->denominations as $key => $value)
                  <tr>
                    <td class="text-right">{{$key}}</td>
                    <td class="text-center">X</td>
                    <td class="text-center">{{$value ?? 0}}</td>
                    <td class="text-center">=</td>
                    <td class="text-left">
                      @format_currency($key * $value)
                    </td>
                  </tr>
                  @php
                    $total += ($key * $value);
                  @endphp
                  @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="4" class="text-center">@lang('sale.total')</th>
                    <td>@format_currency($total)</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          @endif

          <div class="cr-section">
            <div class="cr-section-head">
              <h3><i class="fas fa-id-badge"></i> Cashier</h3>
            </div>
            <div class="cr-user-card">
              <div class="cr-user-item">
                <span class="u-label">@lang('report.user')</span>
                <span class="u-value">{{ $register_details->user_name}}</span>
              </div>
              <div class="cr-user-item">
                <span class="u-label">@lang('business.email')</span>
                <span class="u-value">{{ $register_details->email}}</span>
              </div>
              <div class="cr-user-item">
                <span class="u-label">@lang('business.business_location')</span>
                <span class="u-value">{{ $register_details->location_name}}</span>
              </div>
              @if(!empty($register_details->closing_note))
                <div class="cr-user-item">
                  <span class="u-label">@lang('cash_register.closing_note')</span>
                  <span class="u-value">{{$register_details->closing_note}}</span>
                </div>
              @endif
            </div>
          </div>
        </div>

        <aside class="cr-side-summary">
          <h4><i class="fas fa-balance-scale"></i> Summary</h4>
          <div class="cr-side-row">
            <span class="label">@lang('messages.opening')</span>
            <span class="value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></span>
          </div>
          <div class="cr-side-row">
            <span class="label">@lang('business.sale')</span>
            <span class="value"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale }}</span></span>
          </div>
          <div class="cr-side-row">
            <span class="label">@lang('lang_v1.refund')</span>
            <span class="value" style="color:#DC2626;"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></span>
          </div>
          <div class="cr-side-row">
            <span class="label">@lang('lang_v1.expense')</span>
            <span class="value" style="color:#D97706;"><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</span></span>
          </div>
          <div class="cr-side-row is-total">
            <span class="label">Expected Cash</span>
            <span class="value"><span class="display_currency" data-currency_symbol="true">{{ $cr_expected_cash }}</span></span>
          </div>
        </aside>
      </div>
    </div>

    <div class="modal-footer cr-premium-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print print-mini-button" aria-label="Print">
        <i class="fa fa-print"></i> @lang('messages.print_mini')
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print"
        aria-label="Print"
        onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang('messages.print_detailed')
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang('messages.cancel')</button>
    </div>
  </div>
</div>

<style type="text/css">
  @media print {
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        overflow: visible!important;
    }
}
</style>
<script>
  $(document).ready(function () {
      $(document).on('click', '.print-mini-button', function () {
          $('.mini_print').printThis();
      });
  });
</script>
