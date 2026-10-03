<!-- business information here -->

<style type="text/css">
	.invoice-print,
	.invoice-print * {
		box-sizing: border-box;
		font-family: Arial, Helvetica, "Noto Sans Tamil", "Noto Sans", sans-serif;
		color: #000;
		line-height: 1.25;
	}
	.invoice-print {
		width: 100%;
		max-width: 100%;
		margin: 0 auto;
		padding: 0 0 8px;
		font-size: 12px;
		font-weight: 500;
	}
	.invoice-print .row {
		margin-left: 0 !important;
		margin-right: 0 !important;
	}
	.invoice-print [class*="col-xs-"],
	.invoice-print [class*="col-sm-"],
	.invoice-print [class*="col-md-"],
	.invoice-print [class*="col-lg-"] {
		width: 100% !important;
		float: none !important;
		padding-left: 0 !important;
		padding-right: 0 !important;
	}
	.invoice-print .invoice-shop {
		margin: 0;
		font-size: 18px;
		font-weight: 800;
		text-align: center;
		line-height: 1.2;
	}
	.invoice-print .invoice-title {
		margin: 4px 0 6px;
		font-size: 15px;
		font-weight: 800;
		text-align: center;
		line-height: 1.2;
	}
	.invoice-print .invoice-address {
		margin: 2px 0;
		font-size: 11px;
		font-weight: 500;
		text-align: center;
		line-height: 1.25;
	}
	.invoice-print .invoice-sep {
		border: 0;
		border-top: 1px dashed #000;
		margin: 4px 0;
		height: 0;
	}
	.invoice-print .meta-row {
		display: grid;
		grid-template-columns: 38% minmax(0, 1fr);
		column-gap: 4px;
		align-items: start;
		margin: 0;
	}
	.invoice-print .meta-label {
		text-align: left;
		font-weight: 700;
	}
	.invoice-print .meta-value {
		text-align: left;
		font-weight: 500;
		overflow-wrap: anywhere;
	}
	.invoice-print .payment-row,
	.invoice-print .summary-row {
		display: grid;
		grid-template-columns: minmax(0, 1fr) auto;
		column-gap: 8px;
		align-items: baseline;
		margin: 0;
	}
	.invoice-print .payment-label,
	.invoice-print .summary-label {
		text-align: left;
		font-weight: 600;
		min-width: 0;
	}
	.invoice-print .payment-value,
	.invoice-print .summary-value,
	.invoice-print .money {
		text-align: right;
		white-space: nowrap !important;
		word-break: keep-all !important;
		overflow-wrap: normal !important;
		font-weight: 600;
	}
	.invoice-print .payment-date {
		display: block;
		font-size: 9px;
		font-weight: 500;
	}
	.invoice-print .summary-row.is-total {
		margin-top: 2px;
		padding-top: 2px;
		border-top: 1px solid #000;
		font-size: 15px;
		font-weight: 800;
	}
	.invoice-print .summary-row.is-total .summary-label,
	.invoice-print .summary-row.is-total .summary-value {
		font-weight: 800;
		font-size: 15px;
	}
	.invoice-print .invoice-words {
		display: block;
		margin-top: 2px;
		font-size: 10px;
		font-weight: 500;
		text-align: right;
	}
	.invoice-print table {
		margin-bottom: 0;
	}
</style>
<div class="invoice-print">
<div class="row" style="color: #000000 !important;">
		<!-- Logo -->
		@if(empty($receipt_details->letter_head))
			@if(!empty($receipt_details->logo))
				<img style="max-height: 120px; width: auto;" src="{{$receipt_details->logo}}" class="img img-responsive center-block">
			@endif

			<!-- Header text -->
			@if(!empty($receipt_details->header_text))
				<div class="col-xs-12">
					{!! $receipt_details->header_text !!}
				</div>
			@endif

			<!-- business information here -->
			<div class="col-xs-12 text-center">
				<h2 class="text-center invoice-shop">
					<!-- Shop & Location Name  -->
					@if(!empty($receipt_details->display_name))
						{{$receipt_details->display_name}}
					@endif
				</h2>

				<!-- Address -->
				<p class="invoice-address">
				@if(!empty($receipt_details->address))
						{!! $receipt_details->address !!}
				@endif
				@if(!empty($receipt_details->contact))
					<br/>{!! $receipt_details->contact !!}
				@endif
				@if(!empty($receipt_details->website))
					<br/>{{ $receipt_details->website }}
				@endif
				@if(!empty($receipt_details->location_custom_fields))
					<br>{{ $receipt_details->location_custom_fields }}
				@endif
				</p>
				<p>
				@if(!empty($receipt_details->sub_heading_line1))
					{{ $receipt_details->sub_heading_line1 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line2))
					<br>{{ $receipt_details->sub_heading_line2 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line3))
					<br>{{ $receipt_details->sub_heading_line3 }}
				@endif
				@if(!empty($receipt_details->sub_heading_line4))
					<br>{{ $receipt_details->sub_heading_line4 }}
				@endif		
				@if(!empty($receipt_details->sub_heading_line5))
					<br>{{ $receipt_details->sub_heading_line5 }}
				@endif
				</p>
				<p>
				@if(!empty($receipt_details->tax_info1))
					<b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
				@endif

				@if(!empty($receipt_details->tax_info2))
					<b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
				@endif
				</p>
			@endif


			<!-- Title of receipt -->
			@if(!empty($receipt_details->invoice_heading))
				<h3 class="text-center invoice-title">
					{!! $receipt_details->invoice_heading !!}
				</h3>
			@endif
		</div>
		@if(!empty($receipt_details->letter_head))
			<div class="col-xs-12 text-center">
				<img style="width: 100%;margin-bottom: 10px;" src="{{$receipt_details->letter_head}}">
			</div>
		@endif
	<div class="col-xs-12">
		<div class="invoice-meta">
			<div class="meta-row">
				<span class="meta-label">{!! $receipt_details->invoice_no_prefix !!}</span>
				<span class="meta-value">{{$receipt_details->invoice_no}}</span>
			</div>

			@if(!empty($receipt_details->types_of_service))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->types_of_service_label !!}</span>
					<span class="meta-value">{{$receipt_details->types_of_service}}
						@if(!empty($receipt_details->types_of_service_custom_fields))
							@foreach($receipt_details->types_of_service_custom_fields as $key => $value)
								<br>{{$key}}: {{$value}}
							@endforeach
						@endif
					</span>
				</div>
			@endif

			@if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->table_label !!}</span>
					<span class="meta-value">{{$receipt_details->table}}</span>
				</div>
			@endif

			@if(!empty($receipt_details->customer_info))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->customer_label }}</span>
					<span class="meta-value">{!! $receipt_details->customer_info !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->client_id_label))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->client_id_label }}</span>
					<span class="meta-value">{{ $receipt_details->client_id }}</span>
				</div>
			@endif
			@if(!empty($receipt_details->customer_tax_label))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->customer_tax_label }}</span>
					<span class="meta-value">{{ $receipt_details->customer_tax_number }}</span>
				</div>
			@endif
			@if(!empty($receipt_details->customer_custom_fields))
				<div class="meta-row">
					<span class="meta-label"></span>
					<span class="meta-value">{!! $receipt_details->customer_custom_fields !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sales_person_label))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->sales_person_label }}</span>
					<span class="meta-value">{{ $receipt_details->sales_person }}</span>
				</div>
			@endif
			@if(!empty($receipt_details->commission_agent_label))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->commission_agent_label }}</span>
					<span class="meta-value">{{ $receipt_details->commission_agent }}</span>
				</div>
			@endif
			@if(!empty($receipt_details->customer_rp_label))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->customer_rp_label }}</span>
					<span class="meta-value">{{ $receipt_details->customer_total_rp }}</span>
				</div>
			@endif

			<div class="meta-row">
				<span class="meta-label">{{$receipt_details->date_label}}</span>
				<span class="meta-value">{{$receipt_details->invoice_date}}</span>
			</div>

			@if(!empty($receipt_details->due_date_label))
				<div class="meta-row">
					<span class="meta-label">{{$receipt_details->due_date_label}}</span>
					<span class="meta-value">{{$receipt_details->due_date ?? ''}}</span>
				</div>
			@endif

			@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->brand_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_brand}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->device_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_device}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->model_no_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_model_no}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->serial_no_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_serial_no}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->repair_status_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_status}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->repair_warranty_label !!}</span>
					<span class="meta-value">{{$receipt_details->repair_warranty}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->service_staff_label !!}</span>
					<span class="meta-value">{{$receipt_details->service_staff}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_1_label))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->shipping_custom_field_1_label !!}</span>
					<span class="meta-value">{!! $receipt_details->shipping_custom_field_1_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_2_label))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->shipping_custom_field_2_label !!}</span>
					<span class="meta-value">{!! $receipt_details->shipping_custom_field_2_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_3_label))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->shipping_custom_field_3_label !!}</span>
					<span class="meta-value">{!! $receipt_details->shipping_custom_field_3_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_4_label))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->shipping_custom_field_4_label !!}</span>
					<span class="meta-value">{!! $receipt_details->shipping_custom_field_4_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_5_label))
				<div class="meta-row">
					<span class="meta-label">{!! $receipt_details->shipping_custom_field_5_label !!}</span>
					<span class="meta-value">{!! $receipt_details->shipping_custom_field_5_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sale_orders_invoice_no))
				<div class="meta-row">
					<span class="meta-label">@lang('restaurant.order_no')</span>
					<span class="meta-value">{!! $receipt_details->sale_orders_invoice_no ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sale_orders_invoice_date))
				<div class="meta-row">
					<span class="meta-label">@lang('lang_v1.order_dates')</span>
					<span class="meta-value">{!! $receipt_details->sale_orders_invoice_date ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sell_custom_field_1_value))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->sell_custom_field_1_label }}</span>
					<span class="meta-value">{!! $receipt_details->sell_custom_field_1_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sell_custom_field_2_value))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->sell_custom_field_2_label }}</span>
					<span class="meta-value">{!! $receipt_details->sell_custom_field_2_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sell_custom_field_3_value))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->sell_custom_field_3_label }}</span>
					<span class="meta-value">{!! $receipt_details->sell_custom_field_3_value ?? '' !!}</span>
				</div>
			@endif
			@if(!empty($receipt_details->sell_custom_field_4_value))
				<div class="meta-row">
					<span class="meta-label">{{ $receipt_details->sell_custom_field_4_label }}</span>
					<span class="meta-value">{!! $receipt_details->sell_custom_field_4_value ?? '' !!}</span>
				</div>
			@endif
		</div>
	</div>
</div>

<div class="row" style="color: #000000 !important;">
	@includeIf('sale_pos.receipts.partial.common_repair_invoice')
</div>

<div class="row" style="color: #000000 !important;">
	<div class="col-xs-12">
		<br/>
		@include('sale_pos.receipts.partial.receipt_items')
	</div>
</div>

<div class="row" style="color: #000000 !important;">
	<div class="col-xs-12">
		<hr class="invoice-sep"/>
		<div class="invoice-payments">
			@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
					<div class="payment-row">
						<span class="payment-label">
							{{$payment['method']}}
							@if(!empty($payment['date']))
								<span class="payment-date">{{$payment['date']}}</span>
							@endif
						</span>
						<span class="payment-value money">{{$payment['amount']}}</span>
					</div>
				@endforeach
			@endif

			@if(!empty($receipt_details->total_paid))
				<div class="payment-row">
					<span class="payment-label">{!! $receipt_details->total_paid_label !!}</span>
					<span class="payment-value money">{{$receipt_details->total_paid}}</span>
				</div>
			@endif

			@if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
				<div class="payment-row">
					<span class="payment-label">{!! $receipt_details->total_due_label !!}</span>
					<span class="payment-value money">{{$receipt_details->total_due}}</span>
				</div>
			@endif

			@if(!empty($receipt_details->all_due))
				<div class="payment-row">
					<span class="payment-label">{!! $receipt_details->all_bal_label !!}</span>
					<span class="payment-value money">{{$receipt_details->all_due}}</span>
				</div>
			@endif
		</div>

		<hr class="invoice-sep"/>
		<div class="invoice-summary">
			@if(!empty($receipt_details->total_quantity_label))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->total_quantity_label !!}</span>
					<span class="summary-value">{{$receipt_details->total_quantity}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->total_items_label))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->total_items_label !!}</span>
					<span class="summary-value">{{$receipt_details->total_items}}</span>
				</div>
			@endif
			<div class="summary-row">
				<span class="summary-label">{!! $receipt_details->subtotal_label !!}</span>
				<span class="summary-value money">{{$receipt_details->subtotal}}</span>
			</div>
			@if(!empty($receipt_details->total_exempt_uf))
				<div class="summary-row">
					<span class="summary-label">@lang('lang_v1.exempt')</span>
					<span class="summary-value money">{{$receipt_details->total_exempt}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_charges))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->shipping_charges_label !!}</span>
					<span class="summary-value money">{{$receipt_details->shipping_charges}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->packing_charge))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->packing_charge_label !!}</span>
					<span class="summary-value money">{{$receipt_details->packing_charge}}</span>
				</div>
			@endif
			@if( !empty($receipt_details->discount) )
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->discount_label !!}</span>
					<span class="summary-value money">(-) {{$receipt_details->discount}}</span>
				</div>
			@endif
			@if( !empty($receipt_details->total_line_discount) )
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->line_discount_label !!}</span>
					<span class="summary-value money">(-) {{$receipt_details->total_line_discount}}</span>
				</div>
			@endif
			@if( !empty($receipt_details->additional_expenses) )
				@foreach($receipt_details->additional_expenses as $key => $val)
					<div class="summary-row">
						<span class="summary-label">{{$key}}</span>
						<span class="summary-value money">(+) {{$val}}</span>
					</div>
				@endforeach
			@endif
			@if( !empty($receipt_details->reward_point_label) )
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->reward_point_label !!}</span>
					<span class="summary-value money">(-) {{$receipt_details->reward_point_amount}}</span>
				</div>
			@endif
			@if( !empty($receipt_details->tax) )
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->tax_label !!}</span>
					<span class="summary-value money">(+) {{$receipt_details->tax}}</span>
				</div>
			@endif
			@if( $receipt_details->round_off_amount > 0)
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->round_off_label !!}</span>
					<span class="summary-value money">{{$receipt_details->round_off}}</span>
				</div>
			@endif
			<div class="summary-row is-total">
				<span class="summary-label">{!! $receipt_details->total_label !!}</span>
				<span class="summary-value money">{{$receipt_details->total}}</span>
			</div>
			@if(!empty($receipt_details->total_in_words))
				<span class="invoice-words">({{$receipt_details->total_in_words}})</span>
			@endif
		</div>
	</div>

    <div class="border-bottom col-md-12">
	    @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
	        <!-- tax -->
	        @if(!empty($receipt_details->taxes))
	        	<table class="table table-slim table-bordered">
	        		<tr>
	        			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
	        		</tr>
	        		@foreach($receipt_details->taxes as $key => $val)
	        			<tr>
	        				<td class="text-center"><b>{{$key}}</b></td>
	        				<td class="text-center">{{$val}}</td>
	        			</tr>
	        		@endforeach
	        	</table>
	        @endif
	    @endif
	</div>

	@if(!empty($receipt_details->additional_notes))
	    <div class="col-xs-12">
	    	<p>{!! nl2br($receipt_details->additional_notes) !!}</p>
	    </div>
    @endif
    
</div>
<div class="row" style="color: #000000 !important;">
	@if(!empty($receipt_details->footer_text))
	<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
		{!! $receipt_details->footer_text !!}
	</div>
	@endif
	@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
		<div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
			@if($receipt_details->show_barcode)
				{{-- Barcode --}}
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif
			
			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
			@endif
		</div>
	@endif
</div>
</div>
