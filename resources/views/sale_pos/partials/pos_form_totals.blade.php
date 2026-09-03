<div class="row pos_form_totals">
	<div class="col-md-12">
		<div class="pos-inline-summary-title">Payment Summary</div>
		<table class="table table-condensed pos-excel-summary">
			<tr>
				<td>Items</td>
				<td class="pos-sum-val"><span class="total_quantity">0</span></td>
			</tr>
			<tr>
				<td>Subtotal</td>
				<td class="pos-sum-val"><span class="price_total">0</span></td>
			</tr>
			<tr @if(!Gate::check('disable_discount') || auth()->user()->can('superadmin') || auth()->user()->can('admin')) @else class="hide" @endif>
				<td>
					@if($is_discount_enabled)
						Discount (-)
						@show_tooltip(__('tooltip.sale_discount'))
					@endif
					@if($is_rp_enabled)
						{{session('business.rp_name')}}
					@endif
					@if($is_discount_enabled)
						@if($edit_discount)
						<i class="fas fa-edit cursor-pointer" id="pos-edit-discount" title="@lang('sale.edit_discount')" aria-hidden="true" data-toggle="modal" data-target="#posEditDiscountModal"></i>
						@endif
					@endif
					<input type="hidden" name="discount_type" id="discount_type" value="@if(empty($edit)){{'percentage'}}@else{{$transaction->discount_type}}@endif" data-default="percentage">
					<input type="hidden" name="discount_amount" id="discount_amount" value="@if(empty($edit)) {{@num_format($business_details->default_sales_discount)}} @else {{@num_format($transaction->discount_amount)}} @endif" data-default="{{$business_details->default_sales_discount}}">
					<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="@if(empty($edit)){{'0'}}@else{{$transaction->rp_redeemed}}@endif">
					<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="@if(empty($edit)){{'0'}}@else {{$transaction->rp_redeemed_amount}} @endif">
				</td>
				<td class="pos-sum-val">
					@if($is_discount_enabled)
						<span id="total_discount">0</span>
					@endif
				</td>
			</tr>
			<tr class="@if($pos_settings['disable_order_tax'] != 0) hide @endif">
				<td>
					@lang('sale.order_tax') @show_tooltip(__('tooltip.sale_tax'))
					<i class="fas fa-edit cursor-pointer" title="@lang('sale.edit_order_tax')" aria-hidden="true" data-toggle="modal" data-target="#posEditOrderTaxModal" id="pos-edit-tax" ></i>
					<input type="hidden" name="tax_rate_id"
						id="tax_rate_id"
						value="@if(empty($edit)) {{$business_details->default_sales_tax}} @else {{$transaction->tax_id}} @endif"
						data-default="{{$business_details->default_sales_tax}}">
					<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount"
						value="@if(empty($edit)) {{@num_format($business_details->tax_calculation_amount)}} @else {{@num_format($transaction->tax?->amount)}} @endif" data-default="{{$business_details->tax_calculation_amount}}">
				</td>
				<td class="pos-sum-val">
					<span id="order_tax">
						@if(empty($edit))
							0
						@else
							{{$transaction->tax_amount}}
						@endif
					</span>
				</td>
			</tr>
			<tr>
				<td>
					@lang('sale.shipping') @show_tooltip(__('tooltip.shipping'))
					<i class="fas fa-edit cursor-pointer"  title="@lang('sale.shipping')" aria-hidden="true" data-toggle="modal" data-target="#posShippingModal"></i>
					<input type="hidden" name="shipping_details" id="shipping_details" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_details}}@endif" data-default="">
					<input type="hidden" name="shipping_address" id="shipping_address" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_address}}@endif">
					<input type="hidden" name="shipping_status" id="shipping_status" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_status}}@endif">
					<input type="hidden" name="delivered_to" id="delivered_to" value="@if(empty($edit)){{''}}@else{{$transaction->delivered_to}}@endif">
					<input type="hidden" name="delivery_person" id="delivery_person" value="@if(empty($edit)){{''}}@else{{$transaction->delivery_person}}@endif">
					<input type="hidden" name="shipping_charges" id="shipping_charges" value="@if(empty($edit)){{@num_format(0.00)}} @else{{@num_format($transaction->shipping_charges)}} @endif" data-default="0.00">
				</td>
				<td class="pos-sum-val"><span id="shipping_charges_amount">0</span></td>
			</tr>
			@if(in_array('types_of_service', $enabled_modules))
			<tr>
				<td>
					@lang('lang_v1.packing_charge')
					<i class="fas fa-edit cursor-pointer service_modal_btn"></i>
				</td>
				<td class="pos-sum-val"><span id="packing_charge_text">0</span></td>
			</tr>
			@endif
			@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
			<tr>
				<td id="round_off">@lang('lang_v1.round_off')</td>
				<td class="pos-sum-val">
					<span id="round_off_text">0</span>
					<input type="hidden" name="round_off_amount" id="round_off_amount" value=0>
				</td>
			</tr>
			@endif
			<tr class="pos-sum-total">
				<td>TOTAL</td>
				<td class="pos-sum-val"><span id="pos_excel_total_display">0</span></td>
			</tr>
			<tr class="pos-sum-payable">
				<td>PAYABLE</td>
				<td class="pos-sum-val"><span id="pos_excel_payable_display">0</span></td>
			</tr>
		</table>
	</div>
</div>
