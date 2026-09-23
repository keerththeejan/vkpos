<div class="row pos_form_totals">
	<div class="col-md-12">
		<div class="payment-summary pos-pay-summary-card" role="region" aria-label="Payment Summary">
			<div class="payment-summary-title">Payment Summary</div>

			<div class="summary-row summary-row-items items-row">
				<span class="summary-label">Total Items</span>
				<strong class="summary-value"><span class="pos_total_items">0</span></strong>
			</div>

			<div class="summary-row">
				<span class="summary-label">@lang('sale.subtotal')</span>
				<strong class="summary-value"><span class="price_total">0</span></strong>
			</div>

			<div class="summary-row @if(!Gate::check('disable_discount') || auth()->user()->can('superadmin') || auth()->user()->can('admin')) @else hide @endif">
				<span class="summary-label">
					@if($is_discount_enabled)
						@lang('sale.discount')
						@show_tooltip(__('tooltip.sale_discount'))
					@endif
					@if($is_rp_enabled)
						{{ session('business.rp_name') }}
					@endif
					@if($is_discount_enabled)
						@if($edit_discount)
						<i class="fas fa-edit cursor-pointer summary-edit" id="pos-edit-discount" title="@lang('sale.edit_discount')" aria-hidden="true" data-toggle="modal" data-target="#posEditDiscountModal"></i>
						@endif
					@endif
				</span>
				<strong class="summary-value">
					@if($is_discount_enabled)
						- <span id="total_discount">0</span>
					@endif
				</strong>
			</div>

			<div class="summary-row summary-row-extra @if($pos_settings['disable_order_tax'] != 0) hide @endif">
				<span class="summary-label">
					@lang('sale.order_tax') @show_tooltip(__('tooltip.sale_tax'))
					<i class="fas fa-edit cursor-pointer summary-edit" title="@lang('sale.edit_order_tax')" aria-hidden="true" data-toggle="modal" data-target="#posEditOrderTaxModal" id="pos-edit-tax"></i>
				</span>
				<strong class="summary-value"><span id="order_tax">@if(empty($edit))0@else{{$transaction->tax_amount}}@endif</span></strong>
			</div>

			<div class="summary-row summary-row-extra">
				<span class="summary-label">
					@lang('sale.shipping') @show_tooltip(__('tooltip.shipping'))
					<i class="fas fa-edit cursor-pointer summary-edit" title="@lang('sale.shipping')" aria-hidden="true" data-toggle="modal" data-target="#posShippingModal"></i>
				</span>
				<strong class="summary-value"><span id="shipping_charges_amount">0</span></strong>
			</div>

			@if(in_array('types_of_service', $enabled_modules))
			<div class="summary-row summary-row-extra">
				<span class="summary-label">
					@lang('lang_v1.packing_charge')
					<i class="fas fa-edit cursor-pointer service_modal_btn summary-edit"></i>
				</span>
				<strong class="summary-value"><span id="packing_charge_text">0</span></strong>
			</div>
			@endif

			@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
			<div class="summary-row summary-row-extra">
				<span class="summary-label" id="round_off">@lang('lang_v1.round_off')</span>
				<strong class="summary-value">
					<span id="round_off_text">0</span>
				</strong>
			</div>
			@endif

			<div class="summary-sep"></div>

			<div class="summary-row summary-row-total">
				<span class="summary-label">@lang('sale.total')</span>
				<strong class="summary-value summary-total-value"><span class="total_payable_span">0</span></strong>
			</div>
			<span id="pos_excel_total_display" class="hide">0</span>
			<span id="pos_excel_payable_display" class="hide">0</span>

			<div class="summary-row summary-row-paid">
				<span class="summary-label">Paid Amount</span>
				<strong class="summary-value summary-paid-value"><span class="total_paying">0</span></strong>
			</div>

			<div class="summary-sep summary-sep-change"></div>

			<div class="summary-row summary-row-change pos-pay-change-wrap">
				<span class="summary-label">Change</span>
				<strong class="summary-value summary-change-value"><span class="change_return_span">0</span></strong>
			</div>

			<div class="summary-row summary-row-due pos-pay-due-wrap">
				<span class="summary-label">Balance Due</span>
				<strong class="summary-value"><span class="balance_due">0</span></strong>
			</div>
		</div>

		<div class="hide pos-summary-hidden-fields">
			<input type="hidden" name="discount_type" id="discount_type" value="@if(empty($edit)){{'percentage'}}@else{{$transaction->discount_type}}@endif" data-default="percentage">
			<input type="hidden" name="discount_amount" id="discount_amount" value="@if(empty($edit)){{@num_format($business_details->default_sales_discount)}}@else{{@num_format($transaction->discount_amount)}}@endif" data-default="{{$business_details->default_sales_discount}}">
			<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="@if(empty($edit)){{'0'}}@else{{$transaction->rp_redeemed}}@endif">
			<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="@if(empty($edit)){{'0'}}@else{{$transaction->rp_redeemed_amount}}@endif">
			<input type="hidden" name="tax_rate_id" id="tax_rate_id" value="@if(empty($edit)){{$business_details->default_sales_tax}}@else{{$transaction->tax_id}}@endif" data-default="{{$business_details->default_sales_tax}}">
			<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" value="@if(empty($edit)){{@num_format($business_details->tax_calculation_amount)}}@else{{@num_format($transaction->tax?->amount)}}@endif" data-default="{{$business_details->tax_calculation_amount}}">
			<input type="hidden" name="shipping_details" id="shipping_details" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_details}}@endif" data-default="">
			<input type="hidden" name="shipping_address" id="shipping_address" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_address}}@endif">
			<input type="hidden" name="shipping_status" id="shipping_status" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_status}}@endif">
			<input type="hidden" name="delivered_to" id="delivered_to" value="@if(empty($edit)){{''}}@else{{$transaction->delivered_to}}@endif">
			<input type="hidden" name="delivery_person" id="delivery_person" value="@if(empty($edit)){{''}}@else{{$transaction->delivery_person}}@endif">
			<input type="hidden" name="shipping_charges" id="shipping_charges" value="@if(empty($edit)){{@num_format(0.00)}}@else{{@num_format($transaction->shipping_charges)}}@endif" data-default="0.00">
			@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
				<input type="hidden" name="round_off_amount" id="round_off_amount" value=0>
			@endif
		</div>
	</div>
</div>
