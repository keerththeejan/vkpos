<link rel="stylesheet" href="{{ asset('css/register-premium.css?v=' . ($asset_v ?? time())) }}">
@php
  $cr_expected_cash = $register_details->cash_in_hand + $register_details->total_sale - $register_details->total_refund - $register_details->total_expense;
  $cr_cash_expected = $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense;
  $open_label = \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A');
  $now_label = \Carbon::now()->format('jS M, Y h:i A');
  $duration = \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->diffForHumans(null, true);
@endphp
<div class="modal-dialog modal-lg cr-premium-dialog" role="document">
  <div class="modal-content cr-premium-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'postCloseRegister']), 'method' => 'post' ]) !!}
    {!! Form::hidden('user_id', $register_details->user_id); !!}

    <div class="modal-header cr-premium-header">
      <div>
        <h3 class="modal-title">@lang('cash_register.current_register')</h3>
        <div class="cr-header-meta">
          <span class="cr-badge"><span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block;"></span> Register Open</span>
          <span class="cr-chip"><i class="fas fa-clock"></i> {{ $open_label }} — {{ $now_label }}</span>
          <span class="cr-chip"><i class="fas fa-hourglass-half"></i> {{ $duration }}</span>
          <span class="cr-chip"><i class="fas fa-map-marker-alt"></i> {{ $register_details->location_name }}</span>
          <span class="cr-chip"><i class="fas fa-user"></i> {{ $register_details->user_name }}</span>
        </div>
      </div>
      <div class="cr-header-actions">
        <button type="button" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-primary no-print" onclick="$(this).closest('div.modal').printThis();" title="@lang('messages.print')">
          <i class="fa fa-print"></i>
        </button>
        <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    </div>

    <div class="modal-body cr-premium-body">
      <div class="cr-layout">
        <div class="cr-layout-main">
          @include('cash_register.payment_details')

          <div class="cr-section">
            <div class="cr-section-head">
              <h3><i class="fas fa-lock"></i> @lang('cash_register.close_register')</h3>
            </div>

            <div class="cr-close-grid">
              <div class="cr-input-card">
                <div class="form-group" style="margin:0;">
                  {!! Form::label('closing_amount', __( 'cash_register.total_cash' ) . ':*') !!}
                  {!! Form::text('closing_amount', @num_format($register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); !!}
                </div>
              </div>
              <div class="cr-input-card">
                <div class="form-group" style="margin:0;">
                  {!! Form::label('total_card_slips', __( 'cash_register.total_card_slips' ) . ':*') !!} @show_tooltip(__('tooltip.total_card_slips'))
                  {!! Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_card_slips' ), 'min' => 0 ]); !!}
                </div>
              </div>
              <div class="cr-input-card">
                <div class="form-group" style="margin:0;">
                  {!! Form::label('total_cheques', __( 'cash_register.total_cheques' ) . ':*') !!} @show_tooltip(__('tooltip.total_cheques'))
                  {!! Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_cheques' ), 'min' => 0 ]); !!}
                </div>
              </div>
            </div>

            <div style="margin-top:16px;">
              <h4 style="font-size:14px;font-weight:800;margin:0 0 10px;">@lang('lang_v1.cash_denominations')</h4>
              @if(!empty($pos_settings['cash_denominations']))
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
                    @foreach(explode(',', $pos_settings['cash_denominations']) as $dnm)
                    <tr>
                      <td class="text-right">{{$dnm}}</td>
                      <td class="text-center">X</td>
                      <td>{!! Form::number("denominations[$dnm]", null, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;' ]); !!}</td>
                      <td class="text-center">=</td>
                      <td class="text-left">
                        <span class="denomination_subtotal">0</span>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="4" class="text-center">@lang('sale.total')</th>
                      <td><span class="denomination_total">0</span></td>
                    </tr>
                  </tfoot>
                </table>
              @else
                <p class="help-block">@lang('lang_v1.denomination_add_help_text')</p>
              @endif
            </div>

            <div class="cr-input-card" style="margin-top:16px;">
              <div class="form-group" style="margin:0;">
                {!! Form::label('closing_note', __( 'cash_register.closing_note' ) . ':') !!}
                {!! Form::textarea('closing_note', null, ['class' => 'form-control', 'placeholder' => __( 'cash_register.closing_note' ), 'rows' => 3 ]); !!}
              </div>
            </div>
          </div>

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
          <div class="cr-side-row">
            <span class="label">@lang('cash_register.total_cash')</span>
            <span class="value"><span class="display_currency" data-currency_symbol="true">{{ $cr_cash_expected }}</span></span>
          </div>
        </aside>
      </div>
    </div>

    <div class="modal-footer cr-premium-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.cancel')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-primary no-print" onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang('messages.print')
      </button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
        <i class="fas fa-lock"></i> @lang('cash_register.close_register')
      </button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
