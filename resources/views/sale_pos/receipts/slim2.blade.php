@php
	$paid_tendered = $receipt_details->paid_tendered ?? ($receipt_details->total_paid ?? 0);
	$change_amount = $receipt_details->change_amount ?? 0;
	$due_amount = $receipt_details->total_due ?? 0;
	$change_uf = (float) ($receipt_details->change_amount_uf ?? 0);
	$due_uf = (float) ($receipt_details->due_uf ?? 0);
	$show_change = $change_uf > 0.00001;
	$show_due = !$show_change && $due_uf > 0.00001;
	$currency = $receipt_details->currency ?? [];
	$currency_symbol = is_array($currency) ? ($currency['symbol'] ?? '') : ($currency->symbol ?? '');
	$customer_name = trim($receipt_details->customer_name ?? '');
	$customer_wrap = mb_strlen($customer_name) > 22;
	$header_html = $receipt_details->header_text ?? '';
	$header_html = preg_replace('/\s+(MULTI\s+SHOP)/i', '<br>$1', $header_html);
	$header_html = preg_replace('/\s+(பல்பொருள் அங்காடி)/u', '<br>$1', $header_html);
	$display_name_html = preg_replace('/\s+(MULTI\s+SHOP)/i', '<br>$1', e($receipt_details->display_name ?? ''));
	$display_name_html = preg_replace('/\s+(பல்பொருள் அங்காடி)/u', '<br>$1', $display_name_html);
	if ($currency_symbol !== '') {
		$currency_symbol = rtrim($currency_symbol) . ' ';
	}
@endphp
<!DOCTYPE html>
<html lang="en" class="thermal-receipt">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@700;800&display=swap" rel="stylesheet">
	<title>Receipt-{{$receipt_details->invoice_no}}</title>
</head>
<body>
<div class="ticket">
	@if(empty($receipt_details->letter_head))
		@if(!empty($receipt_details->logo))
			<div class="centered">
				<img class="logo" src="{{$receipt_details->logo}}" alt="Logo">
			</div>
		@endif
		<div class="header">
			@if(!empty($receipt_details->header_text))
				<div class="shop-name">{!! $header_html !!}</div>
			@endif
			@if(!empty($receipt_details->display_name))
				<div class="shop-name">{!! $display_name_html !!}</div>
			@endif
			<div class="shop-meta">
				@if(!empty($receipt_details->address))
					{!! $receipt_details->address !!}<br>
				@endif
				@if(!empty($receipt_details->contact))
					{!! $receipt_details->contact !!}
				@endif
				@if(!empty($receipt_details->contact) && !empty($receipt_details->website)), @endif
				@if(!empty($receipt_details->website)){{ $receipt_details->website }}@endif
				@if(!empty($receipt_details->location_custom_fields))
					<br>{{ $receipt_details->location_custom_fields }}
				@endif
				@if(!empty($receipt_details->sub_heading_line1))<br>{{ $receipt_details->sub_heading_line1 }}@endif
				@if(!empty($receipt_details->sub_heading_line2))<br>{{ $receipt_details->sub_heading_line2 }}@endif
				@if(!empty($receipt_details->sub_heading_line3))<br>{{ $receipt_details->sub_heading_line3 }}@endif
				@if(!empty($receipt_details->sub_heading_line4))<br>{{ $receipt_details->sub_heading_line4 }}@endif
				@if(!empty($receipt_details->sub_heading_line5))<br>{{ $receipt_details->sub_heading_line5 }}@endif
				@if(!empty($receipt_details->tax_info1))
					<br>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
				@endif
				@if(!empty($receipt_details->tax_info2))
					<br>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
				@endif
			</div>
		</div>
	@endif
	@if(!empty($receipt_details->letter_head))
		<img class="letterhead" src="{{$receipt_details->letter_head}}" alt="">
	@endif

	<div class="sep"></div>

	<div class="row">
		<span class="lbl">{!! $receipt_details->invoice_no_prefix !!}</span>
		<span class="val">{{$receipt_details->invoice_no}}</span>
	</div>
	@if(!empty($receipt_details->date_label))
	<div class="row">
		<span class="lbl">{{$receipt_details->date_label}}</span>
		<span class="val">{{$receipt_details->invoice_date}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->due_date_label))
	<div class="row">
		<span class="lbl">{{$receipt_details->due_date_label}}</span>
		<span class="val">{{$receipt_details->due_date ?? ''}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->sales_person_label))
	<div class="row">
		<span class="lbl">{{$receipt_details->sales_person_label}}</span>
		<span class="val">{{$receipt_details->sales_person}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->commission_agent_label))
	<div class="row">
		<span class="lbl">{{$receipt_details->commission_agent_label}}</span>
		<span class="val">{{$receipt_details->commission_agent}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
	<div class="row">
		<span class="lbl">{{$receipt_details->brand_label}}</span>
		<span class="val">{{$receipt_details->repair_brand}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
	<div class="row">
		<span class="lbl">{{$receipt_details->device_label}}</span>
		<span class="val">{{$receipt_details->repair_device}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
	<div class="row">
		<span class="lbl">{{$receipt_details->model_no_label}}</span>
		<span class="val">{{$receipt_details->repair_model_no}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
	<div class="row">
		<span class="lbl">{{$receipt_details->serial_no_label}}</span>
		<span class="val">{{$receipt_details->repair_serial_no}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
	<div class="row">
		<span class="lbl">{!! $receipt_details->repair_status_label !!}</span>
		<span class="val">{{$receipt_details->repair_status}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
	<div class="row">
		<span class="lbl">{!! $receipt_details->repair_warranty_label !!}</span>
		<span class="val">{{$receipt_details->repair_warranty}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
	<div class="row">
		<span class="lbl">{!! $receipt_details->service_staff_label !!}</span>
		<span class="val">{{$receipt_details->service_staff}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
	<div class="row">
		<span class="lbl">{!! $receipt_details->table_label !!}</span>
		<span class="val">{{$receipt_details->table}}</span>
	</div>
	@endif
	@if (!empty($receipt_details->sell_custom_field_1_value))
	<div class="row">
		<span class="lbl">{!! $receipt_details->sell_custom_field_1_label !!}</span>
		<span class="val">{{$receipt_details->sell_custom_field_1_value}}</span>
	</div>
	@endif
	@if (!empty($receipt_details->sell_custom_field_2_value))
	<div class="row">
		<span class="lbl">{!! $receipt_details->sell_custom_field_2_label !!}</span>
		<span class="val">{{$receipt_details->sell_custom_field_2_value}}</span>
	</div>
	@endif
	@if (!empty($receipt_details->sell_custom_field_3_value))
	<div class="row">
		<span class="lbl">{!! $receipt_details->sell_custom_field_3_label !!}</span>
		<span class="val">{{$receipt_details->sell_custom_field_3_value}}</span>
	</div>
	@endif
	@if (!empty($receipt_details->sell_custom_field_4_value))
	<div class="row">
		<span class="lbl">{!! $receipt_details->sell_custom_field_4_label !!}</span>
		<span class="val">{{$receipt_details->sell_custom_field_4_value}}</span>
	</div>
	@endif

	@if(!empty($receipt_details->customer_label) || !empty($receipt_details->customer_name))
	<div class="row row-customer">
		<span class="lbl">{{$receipt_details->customer_label ?? ''}}</span>
		<span class="val {{ $customer_wrap ? 'wrap' : 'nowrap' }}">{{ $customer_name }}</span>
	</div>
	@endif
	@if(!empty($receipt_details->client_id_label))
	<div class="row">
		<span class="lbl">{{ $receipt_details->client_id_label }}</span>
		<span class="val">{{ $receipt_details->client_id }}</span>
	</div>
	@endif
	@if(!empty($receipt_details->customer_tax_label) && !empty($receipt_details->customer_tax_number))
	<div class="row">
		<span class="lbl">{{ $receipt_details->customer_tax_label }}</span>
		<span class="val">{{ $receipt_details->customer_tax_number }}</span>
	</div>
	@endif
	@if(!empty($receipt_details->customer_custom_fields))
		<div class="meta-block">{!! $receipt_details->customer_custom_fields !!}</div>
	@endif
	@if(!empty($receipt_details->customer_rp_label))
	<div class="row">
		<span class="lbl">{{ $receipt_details->customer_rp_label }}</span>
		<span class="val">{{ $receipt_details->customer_total_rp }}</span>
	</div>
	@endif
	@if(!empty($receipt_details->shipping_custom_field_1_label))
	<div class="row">
		<span class="lbl">{!!$receipt_details->shipping_custom_field_1_label!!}</span>
		<span class="val">{!!$receipt_details->shipping_custom_field_1_value ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->shipping_custom_field_2_label))
	<div class="row">
		<span class="lbl">{!!$receipt_details->shipping_custom_field_2_label!!}</span>
		<span class="val">{!!$receipt_details->shipping_custom_field_2_value ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->shipping_custom_field_3_label))
	<div class="row">
		<span class="lbl">{!!$receipt_details->shipping_custom_field_3_label!!}</span>
		<span class="val">{!!$receipt_details->shipping_custom_field_3_value ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->shipping_custom_field_4_label))
	<div class="row">
		<span class="lbl">{!!$receipt_details->shipping_custom_field_4_label!!}</span>
		<span class="val">{!!$receipt_details->shipping_custom_field_4_value ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->shipping_custom_field_5_label))
	<div class="row">
		<span class="lbl">{!!$receipt_details->shipping_custom_field_5_label!!}</span>
		<span class="val">{!!$receipt_details->shipping_custom_field_5_value ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->sale_orders_invoice_no))
	<div class="row">
		<span class="lbl">@lang('restaurant.order_no')</span>
		<span class="val">{!!$receipt_details->sale_orders_invoice_no ?? ''!!}</span>
	</div>
	@endif
	@if(!empty($receipt_details->sale_orders_invoice_date))
	<div class="row">
		<span class="lbl">@lang('lang_v1.order_dates')</span>
		<span class="val">{!!$receipt_details->sale_orders_invoice_date ?? ''!!}</span>
	</div>
	@endif

	<div class="sep"></div>

	@foreach($receipt_details->lines as $line)
		<div class="item">
			<div class="item-name">{{$line['name']}}
				@if(!empty($line['brand'])) {{$line['brand']}} @endif
				@if(!empty($line['variation'])) {{$line['product_variation']}} {{$line['variation']}} @endif
			</div>
			@if(!empty($line['sub_sku']))
				<div class="item-sku">{{$line['sub_sku']}}</div>
			@endif
			@if(!empty($line['sell_line_note']))
				<div class="item-sku">{!! $line['sell_line_note'] !!}</div>
			@endif
			@if(empty($receipt_details->hide_price))
			<div class="row item-line">
				<span class="lbl">{{$line['quantity']}} × {{ $currency_symbol }}{{$line['unit_price_before_discount']}}
					@if(!empty($line['total_line_discount']) && $line['total_line_discount'] != 0)
						- {{ $currency_symbol }}{{$line['total_line_discount']}}
					@endif
				</span>
				<span class="val">{{ $currency_symbol }}{{$line['line_total']}}</span>
			</div>
			@endif
			@if(!empty($line['modifiers']))
				@foreach($line['modifiers'] as $modifier)
					<div class="item-sku">{{$modifier['name']}} {{$modifier['variation']}}</div>
					@if(empty($receipt_details->hide_price))
					<div class="row item-line">
						<span class="lbl">{{$modifier['quantity']}} × {{ $currency_symbol }}{{$modifier['unit_price_inc_tax']}}</span>
						<span class="val">{{ $currency_symbol }}{{$modifier['line_total']}}</span>
					</div>
					@endif
				@endforeach
			@endif
		</div>
	@endforeach

	<div class="sep"></div>

	@if(!empty($receipt_details->total_quantity_label))
	<div class="row">
		<span class="lbl">{!! $receipt_details->total_quantity_label !!}</span>
		<span class="val">{{$receipt_details->total_quantity}}</span>
	</div>
	@endif
	@if(!empty($receipt_details->total_items_label))
	<div class="row">
		<span class="lbl">{!! $receipt_details->total_items_label !!}</span>
		<span class="val">{{$receipt_details->total_items}}</span>
	</div>
	@endif

	@if(empty($receipt_details->hide_price))
		<div class="row">
			<span class="lbl">{{ rtrim(strip_tags(html_entity_decode($receipt_details->subtotal_label ?? 'Subtotal')), " :") }}</span>
			<span class="val">{{$receipt_details->subtotal}}</span>
		</div>
		@if(!empty($receipt_details->shipping_charges))
		<div class="row">
			<span class="lbl">{!! $receipt_details->shipping_charges_label !!}</span>
			<span class="val">{{$receipt_details->shipping_charges}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->packing_charge))
		<div class="row">
			<span class="lbl">{!! $receipt_details->packing_charge_label !!}</span>
			<span class="val">{{$receipt_details->packing_charge}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->discount))
		<div class="row">
			<span class="lbl">{{ rtrim(strip_tags(html_entity_decode($receipt_details->discount_label ?? 'Discount')), " :") }}</span>
			<span class="val">- &nbsp;{{$receipt_details->discount}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->total_line_discount))
		<div class="row">
			<span class="lbl">{!! $receipt_details->line_discount_label !!}</span>
			<span class="val">- {{$receipt_details->total_line_discount}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->additional_expenses))
			@foreach($receipt_details->additional_expenses as $key => $val)
			<div class="row">
				<span class="lbl">{{$key}}</span>
				<span class="val">{{$val}}</span>
			</div>
			@endforeach
		@endif
		@if(!empty($receipt_details->reward_point_label))
		<div class="row">
			<span class="lbl">{!! $receipt_details->reward_point_label !!}</span>
			<span class="val">- {{$receipt_details->reward_point_amount}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->tax))
		<div class="row">
			<span class="lbl">{!! $receipt_details->tax_label !!}</span>
			<span class="val">{{$receipt_details->tax}}</span>
		</div>
		@endif
		@if(!empty($receipt_details->round_off_amount) && $receipt_details->round_off_amount > 0)
		<div class="row">
			<span class="lbl">{!! $receipt_details->round_off_label !!}</span>
			<span class="val">{{$receipt_details->round_off}}</span>
		</div>
		@endif

		<div class="row em total-row total">
			<span class="lbl">TOTAL</span>
			<span class="val">{{$receipt_details->total}}</span>
		</div>

		@if(!empty($paid_tendered))
		<div class="row paid-amount">
			<span class="lbl">Paid Amount</span>
			<span class="val">{{$paid_tendered}}</span>
		</div>
		@endif

		@if($show_change)
		<div class="row em change">
			<span class="lbl">CHANGE</span>
			<span class="val">{{$change_amount}}</span>
		</div>
		@elseif($show_due)
		<div class="row em balance-due">
			<span class="lbl">BALANCE DUE</span>
			<span class="val">{{$due_amount}}</span>
		</div>
		@endif
	@endif

	@if(!empty($receipt_details->additional_notes))
		<div class="meta-block">{!! nl2br($receipt_details->additional_notes) !!}</div>
	@endif

	@if($receipt_details->show_barcode)
		<img class="barcode" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
	@endif
	@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
		<img class="barcode" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE')}}">
	@endif

	<div class="sep"></div>
	<div class="footer">
		THANK YOU<br>
		PLEASE VISIT AGAIN
	</div>
	@if(!empty($receipt_details->footer_text))
		<div class="footer footer-extra">{!! $receipt_details->footer_text !!}</div>
	@endif
	<div class="sep"></div>
</div>
</body>
</html>
<style type="text/css">
html.thermal-receipt, html.thermal-receipt body {
	margin: 0;
	padding: 0;
	background: #292929;
	color: #f5f5f5;
	font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace;
	font-weight: 700;
	font-size: 13px;
	line-height: 1.5;
}
.ticket {
	width: 384px;
	max-width: 384px;
	margin: 0 auto;
	padding: 16px 14px 20px;
	background: #292929;
	color: #f5f5f5;
	font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace;
	font-weight: 700;
	font-size: 13px;
	line-height: 1.5;
	box-sizing: border-box;
}
.header, .centered, .footer {
	text-align: center;
}
.shop-name {
	font-family: "Noto Sans Tamil", Latha, "Nirmala UI", sans-serif;
	font-size: 14px;
	font-weight: 800;
	line-height: 1.35;
	text-align: center;
	white-space: normal;
	word-break: normal;
	overflow-wrap: break-word;
	margin: 0 0 2px;
}
.shop-name p,
.shop-name span,
.shop-name strong,
.shop-name b {
	font-family: inherit !important;
	font-size: 14px !important;
	font-weight: 800 !important;
	line-height: 1.35 !important;
	margin: 0 !important;
	padding: 0 !important;
}
.shop-name p + p {
	margin-top: 2px !important;
}
.shop-meta b,
.shop-meta strong {
	font-weight: 700 !important;
}
.shop-meta {
	font-family: "Noto Sans Tamil", Latha, "Nirmala UI", sans-serif;
	font-size: 12px;
	font-weight: 700;
	line-height: 1.4;
	text-align: center;
	margin: 8px 0 4px;
	white-space: normal;
	word-break: normal;
}
.logo {
	max-height: 72px;
	width: auto;
	margin: 0 auto 8px;
	display: block;
}
.letterhead {
	width: 100%;
	margin-bottom: 8px;
}
.sep {
	border: 0;
	border-bottom: 1px dashed #000000;
	margin: 16px 0;
	opacity: 1;
}
.row {
	display: flex;
	align-items: baseline;
	justify-content: space-between;
	flex-wrap: nowrap;
	gap: 12px;
	width: 100%;
	margin: 2px 0;
	font-weight: 700;
}
.row .lbl {
	flex: 1 1 auto;
	text-align: left;
	font-weight: 700;
	min-width: 0;
}
.row .val {
	flex: 0 0 auto;
	text-align: right;
	font-weight: 700;
	white-space: nowrap;
	font-variant-numeric: tabular-nums;
	min-width: 7.5em;
}
.row-customer {
	margin-top: 14px;
	margin-bottom: 6px;
}
.row-customer .val.nowrap {
	white-space: nowrap;
}
.row-customer .val.wrap {
	white-space: normal;
	word-break: break-word;
	overflow-wrap: anywhere;
	max-width: 62%;
}
.row.em .lbl,
.row.em .val,
.total,
.total .lbl,
.total .val,
.change,
.change .lbl,
.change .val,
.balance-due,
.balance-due .lbl,
.balance-due .val,
.paid-amount,
.paid-amount .lbl,
.paid-amount .val {
	font-weight: 800;
}
.total-row {
	margin-top: 14px;
	margin-bottom: 14px;
}
.item {
	margin: 12px 0 18px;
}
.item-name {
	text-align: left;
	font-weight: 700;
	font-size: 13px;
	white-space: normal;
	word-break: normal;
}
.item-sku {
	text-align: left;
	font-size: 12px;
	font-weight: 700;
	opacity: 1;
	margin: 1px 0 6px;
}
.item-line {
	margin-top: 2px;
}
.meta-block {
	margin: 8px 0;
	font-size: 12px;
	font-weight: 700;
	text-align: left;
}
.footer {
	font-weight: 700;
	font-size: 13px;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	line-height: 1.6;
	margin: 8px 0;
}
.footer-extra {
	text-transform: none;
	letter-spacing: 0;
	margin-top: 8px;
	font-size: 12px;
	font-weight: 700;
}
.barcode {
	display: block;
	margin: 10px auto 0;
	max-width: 100%;
}
img { max-width: 100%; }

@media print {
	html.thermal-receipt,
	html.thermal-receipt body,
	html, body, .ticket,
	.ticket * {
		background: #ffffff !important;
		color: #000000 !important;
		font-weight: 700 !important;
		opacity: 1 !important;
		text-shadow: none !important;
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		-webkit-font-smoothing: none;
	}
	html.thermal-receipt,
	html.thermal-receipt body,
	.ticket {
		font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace !important;
	}
	.shop-name,
	.shop-name *,
	.shop-meta,
	.shop-meta * {
		font-family: "Noto Sans Tamil", Latha, "Nirmala UI", sans-serif !important;
		color: #000000 !important;
		font-weight: 700 !important;
	}
	.shop-name,
	.shop-name * {
		font-weight: 800 !important;
	}
	.ticket {
		width: 72mm;
		max-width: 72mm;
		margin: 0;
		padding: 4px 6px 10px;
	}
	.sep {
		border-bottom: 1px dashed #000000 !important;
		opacity: 1 !important;
	}
	.row.em .lbl,
	.row.em .val,
	.total,
	.total *,
	.change,
	.change *,
	.balance-due,
	.balance-due *,
	.paid-amount,
	.paid-amount * {
		font-weight: 800 !important;
		color: #000000 !important;
	}
	.row .val {
		min-width: 7.5em;
	}
}
</style>
