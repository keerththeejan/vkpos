{{-- Compact 80mm thermal receipt. Values come from $receipt_details unchanged. --}}
<!DOCTYPE html>
<html lang="en" class="receipt-print">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Receipt-{{$receipt_details->invoice_no}}</title>
	<style>
		:root {
			--receipt-width: 80mm;
			--receipt-padding: 3mm;
			--receipt-font-size: 11px;
			--receipt-line-height: 1.25;
			--ink: #111;
		}

		* { box-sizing: border-box; }

		html, body {
			margin: 0;
			padding: 0;
			height: auto;
			min-height: 0;
			background: #f3f4f6;
			color: var(--ink);
			font-family: system-ui, "Segoe UI", "Noto Sans Tamil", Arial, sans-serif;
			font-size: var(--receipt-font-size);
			line-height: var(--receipt-line-height);
		}

		.receipt {
			width: var(--receipt-width);
			max-width: var(--receipt-width);
			margin: 8px auto;
			padding: var(--receipt-padding);
			background: #fff;
			color: var(--ink);
			height: auto;
			min-height: 0;
			box-shadow: none;
		}

		.receipt p { margin: 0; padding: 0; }

		.receipt-header { text-align: center; margin: 0; }
		.shop-name {
			text-align: center;
			font-size: 16px;
			font-weight: 700;
			line-height: 1.2;
			margin: 0 0 5px;
		}
		.shop-name p, .shop-name span, .shop-name strong, .shop-name b {
			font-size: inherit !important;
			font-weight: 700 !important;
			line-height: 1.2 !important;
			margin: 0 !important;
			padding: 0 !important;
			text-align: center !important;
		}
		.invoice-title {
			text-align: center;
			font-size: 13px;
			font-weight: 700;
			line-height: 1.2;
			margin: 0 0 6px;
		}
		.shop-meta {
			text-align: center;
			font-size: 10px;
			line-height: 1.25;
			margin: 0 0 4px;
		}
		.logo, .letterhead {
			display: block;
			max-height: 28px;
			width: auto;
			max-width: 100%;
			margin: 0 auto 4px;
		}

		.separator {
			border: 0;
			border-top: 1px solid #222;
			margin: 6px 0;
			height: 0;
		}

		.invoice-meta {
			display: grid;
			grid-template-columns: auto 1fr;
			column-gap: 8px;
			row-gap: 2px;
			font-size: 11px;
			line-height: 1.3;
		}
		.meta-label { font-weight: 600; text-align: left; }
		.meta-value {
			text-align: right;
			white-space: nowrap;
		}
		.meta-value.wrap {
			white-space: normal;
			overflow-wrap: anywhere;
		}

		.invoice-table {
			display: grid;
			grid-template-columns: 1.2em minmax(0, 1fr) minmax(2.2em, max-content) minmax(3.4em, max-content) minmax(3.6em, max-content);
			column-gap: 5px;
			row-gap: 1px;
			align-items: start;
			width: 100%;
			margin: 0;
		}
		.invoice-table.price-hidden {
			grid-template-columns: 1.2em minmax(0, 1fr) minmax(2.2em, max-content);
		}
		.invoice-table-header,
		.invoice-row { display: contents; }
		.invoice-table-header > span {
			font-size: 9px;
			font-weight: 700;
			line-height: 1.1;
			padding: 2px 0 3px;
			border-bottom: 1px solid #222;
		}
		.invoice-row > span {
			font-size: 10.5px;
			line-height: 1.25;
			padding: 1px 0;
		}
		.product-column { min-width: 0; text-align: left; }
		.product-name {
			overflow-wrap: anywhere;
			word-break: normal;
		}
		.product-extra {
			display: block;
			font-size: 9.5px;
			line-height: 1.2;
			overflow-wrap: anywhere;
		}
		.qty-column, .price-column, .total-column {
			text-align: right;
			white-space: nowrap;
			font-variant-numeric: tabular-nums;
		}
		.h-58 { display: none; }

		.summary, .payment-section { width: 100%; }
		.summary-row, .payment-row {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			width: 100%;
			margin: 3px 0;
			gap: 8px;
			font-size: 11px;
			line-height: 1.25;
		}
		.summary-label {
			text-align: left;
			flex: 1 1 auto;
			min-width: 0;
		}
		.summary-value {
			text-align: right;
			white-space: nowrap;
			flex: 0 0 auto;
			font-variant-numeric: tabular-nums;
		}
		.summary-value.can-wrap {
			white-space: normal;
			overflow-wrap: anywhere;
			max-width: 62%;
		}
		.summary-total {
			font-size: 13px;
			font-weight: 700;
		}
		.summary-total .summary-label,
		.summary-total .summary-value { font-weight: 700; }

		.invoice-footer {
			text-align: center;
			font-size: 10.5px;
			line-height: 1.25;
			margin: 0;
		}
		.invoice-footer p { margin: 0 !important; text-align: center !important; }
		.notes {
			font-size: 10px;
			line-height: 1.25;
			margin: 4px 0 0;
			text-align: center;
		}
		.barcode, .qr {
			display: block;
			margin: 4px auto 0;
			max-width: 70%;
			height: auto;
			max-height: 18mm;
		}
		.tax-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 10px;
			margin: 2px 0;
		}
		.tax-table td { padding: 1px 0; }
		.tax-table td:last-child { text-align: right; white-space: nowrap; }

		@media print {
			@page { size: auto; margin: 0; }
			html, body {
				margin: 0 !important;
				padding: 0 !important;
				background: #fff !important;
				height: auto !important;
				min-height: 0 !important;
				color: #000 !important;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
			html.receipt-print,
			.receipt {
				position: relative;
				width: 80mm !important;
				max-width: 80mm !important;
				margin: 0 !important;
				padding: 3mm !important;
				box-sizing: border-box !important;
				height: auto !important;
				min-height: 0 !important;
				box-shadow: none !important;
				background: #fff !important;
				color: #000 !important;
				font-family: Arial, "Segoe UI", "Noto Sans Tamil", sans-serif !important;
				font-weight: 600 !important;
				font-size: 11.5px !important;
				line-height: 1.3 !important;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				text-rendering: geometricPrecision;
			}
			.receipt * {
				color: #000 !important;
				opacity: 1 !important;
				text-shadow: none !important;
				filter: none !important;
				font-family: Arial, "Segoe UI", "Noto Sans Tamil", sans-serif !important;
			}
			.separator,
			.invoice-table-header > span {
				border-color: #000 !important;
			}
			.shop-name,
			.shop-name * {
				font-size: 16px !important;
				font-weight: 700 !important;
				line-height: 1.3 !important;
				text-align: center !important;
			}
			.invoice-title,
			.invoice-title * {
				font-size: 13px !important;
				font-weight: 700 !important;
				line-height: 1.3 !important;
			}
			.shop-meta,
			.shop-meta * {
				font-size: 11px !important;
				font-weight: 600 !important;
				line-height: 1.3 !important;
			}
			.invoice-meta,
			.invoice-meta * {
				font-size: 11.5px !important;
				font-weight: 600 !important;
				line-height: 1.3 !important;
			}
			.invoice-table-header > span {
				font-size: 10px !important;
				font-weight: 700 !important;
				line-height: 1.25 !important;
			}
			.invoice-row > span,
			.product-name {
				font-size: 11px !important;
				font-weight: 600 !important;
				line-height: 1.3 !important;
			}
			.product-extra {
				font-size: 10.5px !important;
				font-weight: 600 !important;
				line-height: 1.25 !important;
			}
			.qty-column,
			.price-column,
			.total-column,
			.summary-value,
			.meta-value {
				font-variant-numeric: tabular-nums !important;
				white-space: nowrap !important;
			}
			.meta-value.wrap,
			.summary-value.can-wrap {
				white-space: normal !important;
			}
			.summary,
			.summary-row,
			.payment-section,
			.payment-row {
				font-size: 11.5px !important;
				font-weight: 600 !important;
				line-height: 1.3 !important;
			}
			.summary-total,
			.summary-total * {
				font-size: 13px !important;
				font-weight: 700 !important;
			}
			.paid-total,
			.paid-total * {
				font-size: 11.5px !important;
				font-weight: 700 !important;
			}
			.invoice-footer,
			.invoice-footer *,
			.notes,
			.notes * {
				font-size: 11px !important;
				font-weight: 600 !important;
				line-height: 1.3 !important;
			}
		}

		@media print and (max-width: 60mm) {
			.receipt-print, .receipt {
				width: 58mm;
				max-width: 58mm;
				padding: 2mm;
			}
			.h-80 { display: none; }
			.h-58 { display: inline; }
		}

		@media screen and (max-width: 420px) {
			.receipt {
				width: 100%;
				max-width: 100%;
				margin: 0;
			}
		}
	</style>
</head>
<body>
<div class="receipt">
	@if(empty($receipt_details->letter_head))
		@if(!empty($receipt_details->logo))
			<img class="logo" src="{{$receipt_details->logo}}" alt="">
		@endif
		<div class="receipt-header">
			@if(!empty($receipt_details->header_text))
				<div class="shop-name">{!! $receipt_details->header_text !!}</div>
			@elseif(!empty($receipt_details->display_name))
				<div class="shop-name">{{$receipt_details->display_name}}</div>
			@endif
			@if(!empty($receipt_details->header_text) && !empty($receipt_details->display_name))
				@php
					$header_plain = trim(preg_replace('/\s+/', ' ', strip_tags($receipt_details->header_text)));
					$name_plain = trim($receipt_details->display_name);
				@endphp
				@if($header_plain !== '' && $header_plain !== $name_plain)
					<div class="shop-meta">{{$receipt_details->display_name}}</div>
				@endif
			@endif
			@if(!empty($receipt_details->address) || !empty($receipt_details->contact) || !empty($receipt_details->website) || !empty($receipt_details->location_custom_fields) || !empty($receipt_details->sub_heading_line1) || !empty($receipt_details->tax_info1) || !empty($receipt_details->tax_info2))
				<div class="shop-meta">
					@if(!empty($receipt_details->address)){!! $receipt_details->address !!}<br>@endif
					@if(!empty($receipt_details->contact)){!! $receipt_details->contact !!}@endif
					@if(!empty($receipt_details->contact) && !empty($receipt_details->website)), @endif
					@if(!empty($receipt_details->website)){{ $receipt_details->website }}@endif
					@if(!empty($receipt_details->location_custom_fields))<br>{{ $receipt_details->location_custom_fields }}@endif
					@if(!empty($receipt_details->sub_heading_line1))<br>{{ $receipt_details->sub_heading_line1 }}@endif
					@if(!empty($receipt_details->sub_heading_line2))<br>{{ $receipt_details->sub_heading_line2 }}@endif
					@if(!empty($receipt_details->sub_heading_line3))<br>{{ $receipt_details->sub_heading_line3 }}@endif
					@if(!empty($receipt_details->sub_heading_line4))<br>{{ $receipt_details->sub_heading_line4 }}@endif
					@if(!empty($receipt_details->sub_heading_line5))<br>{{ $receipt_details->sub_heading_line5 }}@endif
					@if(!empty($receipt_details->tax_info1))<br>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}@endif
					@if(!empty($receipt_details->tax_info2))<br>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}@endif
				</div>
			@endif
			@if(!empty($receipt_details->invoice_heading))
				<div class="invoice-title">{!! $receipt_details->invoice_heading !!}</div>
			@endif
		</div>
	@else
		<img class="letterhead" src="{{$receipt_details->letter_head}}" alt="">
		@if(!empty($receipt_details->invoice_heading))
			<div class="invoice-title">{!! $receipt_details->invoice_heading !!}</div>
		@endif
	@endif

	<div class="separator"></div>

	<div class="invoice-meta">
		<span class="meta-label">{!! $receipt_details->invoice_no_prefix !!}</span>
		<span class="meta-value">{{$receipt_details->invoice_no}}</span>
		@if(!empty($receipt_details->date_label))
			<span class="meta-label">{{$receipt_details->date_label}}</span>
			<span class="meta-value">{{$receipt_details->invoice_date}}</span>
		@endif
		@if(!empty($receipt_details->due_date_label))
			<span class="meta-label">{{$receipt_details->due_date_label}}</span>
			<span class="meta-value">{{$receipt_details->due_date ?? ''}}</span>
		@endif
		@if(!empty($receipt_details->sales_person_label))
			<span class="meta-label">{{$receipt_details->sales_person_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->sales_person}}</span>
		@endif
		@if(!empty($receipt_details->commission_agent_label))
			<span class="meta-label">{{$receipt_details->commission_agent_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->commission_agent}}</span>
		@endif
		@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
			<span class="meta-label">{{$receipt_details->brand_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_brand}}</span>
		@endif
		@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
			<span class="meta-label">{{$receipt_details->device_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_device}}</span>
		@endif
		@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
			<span class="meta-label">{{$receipt_details->model_no_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_model_no}}</span>
		@endif
		@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
			<span class="meta-label">{{$receipt_details->serial_no_label}}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_serial_no}}</span>
		@endif
		@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
			<span class="meta-label">{!! $receipt_details->repair_status_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_status}}</span>
		@endif
		@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
			<span class="meta-label">{!! $receipt_details->repair_warranty_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->repair_warranty}}</span>
		@endif
		@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
			<span class="meta-label">{!! $receipt_details->service_staff_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->service_staff}}</span>
		@endif
		@if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
			<span class="meta-label">{!! $receipt_details->table_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->table}}</span>
		@endif
		@if (!empty($receipt_details->sell_custom_field_1_value))
			<span class="meta-label">{!! $receipt_details->sell_custom_field_1_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->sell_custom_field_1_value}}</span>
		@endif
		@if (!empty($receipt_details->sell_custom_field_2_value))
			<span class="meta-label">{!! $receipt_details->sell_custom_field_2_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->sell_custom_field_2_value}}</span>
		@endif
		@if (!empty($receipt_details->sell_custom_field_3_value))
			<span class="meta-label">{!! $receipt_details->sell_custom_field_3_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->sell_custom_field_3_value}}</span>
		@endif
		@if (!empty($receipt_details->sell_custom_field_4_value))
			<span class="meta-label">{!! $receipt_details->sell_custom_field_4_label !!}</span>
			<span class="meta-value wrap">{{$receipt_details->sell_custom_field_4_value}}</span>
		@endif
		@if(!empty($receipt_details->customer_info))
			<span class="meta-label">{{$receipt_details->customer_label ?? ''}}</span>
			<span class="meta-value wrap">{!! $receipt_details->customer_info !!}</span>
		@endif
		@if(!empty($receipt_details->client_id_label))
			<span class="meta-label">{{ $receipt_details->client_id_label }}</span>
			<span class="meta-value">{{ $receipt_details->client_id }}</span>
		@endif
		@if(!empty($receipt_details->customer_tax_label) && !empty($receipt_details->customer_tax_number))
			<span class="meta-label">{{ $receipt_details->customer_tax_label }}</span>
			<span class="meta-value">{{ $receipt_details->customer_tax_number }}</span>
		@endif
		@if(!empty($receipt_details->customer_rp_label))
			<span class="meta-label">{{ $receipt_details->customer_rp_label }}</span>
			<span class="meta-value">{{ $receipt_details->customer_total_rp }}</span>
		@endif
		@if(!empty($receipt_details->shipping_custom_field_1_label))
			<span class="meta-label">{!!$receipt_details->shipping_custom_field_1_label!!}</span>
			<span class="meta-value wrap">{!!$receipt_details->shipping_custom_field_1_value ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->shipping_custom_field_2_label))
			<span class="meta-label">{!!$receipt_details->shipping_custom_field_2_label!!}</span>
			<span class="meta-value wrap">{!!$receipt_details->shipping_custom_field_2_value ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->shipping_custom_field_3_label))
			<span class="meta-label">{!!$receipt_details->shipping_custom_field_3_label!!}</span>
			<span class="meta-value wrap">{!!$receipt_details->shipping_custom_field_3_value ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->shipping_custom_field_4_label))
			<span class="meta-label">{!!$receipt_details->shipping_custom_field_4_label!!}</span>
			<span class="meta-value wrap">{!!$receipt_details->shipping_custom_field_4_value ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->shipping_custom_field_5_label))
			<span class="meta-label">{!!$receipt_details->shipping_custom_field_5_label!!}</span>
			<span class="meta-value wrap">{!!$receipt_details->shipping_custom_field_5_value ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->sale_orders_invoice_no))
			<span class="meta-label">@lang('restaurant.order_no')</span>
			<span class="meta-value wrap">{!!$receipt_details->sale_orders_invoice_no ?? ''!!}</span>
		@endif
		@if(!empty($receipt_details->sale_orders_invoice_date))
			<span class="meta-label">@lang('lang_v1.order_dates')</span>
			<span class="meta-value">{{$receipt_details->sale_orders_invoice_date}}</span>
		@endif
	</div>
	@if(!empty($receipt_details->customer_custom_fields))
		<div class="notes">{!! $receipt_details->customer_custom_fields !!}</div>
	@endif

	<div class="separator"></div>

	<div class="invoice-table @if(!empty($receipt_details->hide_price)) price-hidden @endif">
		<div class="invoice-table-header">
			<span>#</span>
			<span class="product-column">Product</span>
			<span class="qty-column">Qty</span>
			@if(empty($receipt_details->hide_price))
				<span class="price-column"><span class="h-80">Unit</span><span class="h-58">Price</span></span>
				<span class="total-column">Total</span>
			@endif
		</div>
		@forelse($receipt_details->lines as $line)
			<div class="invoice-row">
				<span>{{$loop->iteration}}</span>
				<span class="product-column">
					<span class="product-name">{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}}</span>
					@if(!empty($line['sub_sku']) || !empty($line['brand']) || !empty($line['cat_code']) || !empty($line['product_custom_fields']))
						<span class="product-extra">
							@if(!empty($line['sub_sku'])){{$line['sub_sku']}}@endif
							@if(!empty($line['brand'])) {{$line['brand']}}@endif
							@if(!empty($line['cat_code'])) {{$line['cat_code']}}@endif
							@if(!empty($line['product_custom_fields'])) {{$line['product_custom_fields']}}@endif
						</span>
					@endif
					@if(!empty($line['product_description']))
						<span class="product-extra">{!! $line['product_description'] !!}</span>
					@endif
					@if(!empty($line['sell_line_note']))
						<span class="product-extra">{!! $line['sell_line_note'] !!}</span>
					@endif
					@if(!empty($line['lot_number']))
						<span class="product-extra">{{$line['lot_number_label']}}: {{$line['lot_number']}}</span>
					@endif
					@if(!empty($line['product_expiry']))
						<span class="product-extra">{{$line['product_expiry_label']}}: {{$line['product_expiry']}}</span>
					@endif
					@if(!empty($line['warranty_name']) || !empty($line['warranty_exp_date']) || !empty($line['warranty_description']))
						<span class="product-extra">{{$line['warranty_name'] ?? ''}} @if(!empty($line['warranty_exp_date']))- {{@format_date($line['warranty_exp_date'])}}@endif {{$line['warranty_description'] ?? ''}}</span>
					@endif
					@if(!empty($receipt_details->show_base_unit_details) && !empty($line['quantity']) && $line['base_unit_multiplier'] !== 1)
						<span class="product-extra">1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}}</span>
					@endif
				</span>
				<span class="qty-column">{{$line['quantity']}}</span>
				@if(empty($receipt_details->hide_price))
					<span class="price-column">{{$line['unit_price_before_discount']}}</span>
					<span class="total-column">{{$line['line_total']}}</span>
				@endif
			</div>
			@if(!empty($line['modifiers']))
				@foreach($line['modifiers'] as $modifier)
					<div class="invoice-row">
						<span></span>
						<span class="product-column">
							<span class="product-name">{{$modifier['name']}} {{$modifier['variation']}}
								@if(!empty($modifier['sub_sku'])) {{$modifier['sub_sku']}}@endif
								@if(!empty($modifier['sell_line_note'])) ({!!$modifier['sell_line_note']!!})@endif
							</span>
						</span>
						<span class="qty-column">{{$modifier['quantity']}}</span>
						@if(empty($receipt_details->hide_price))
							<span class="price-column">{{$modifier['unit_price_inc_tax']}}</span>
							<span class="total-column">{{$modifier['line_total']}}</span>
						@endif
					</div>
				@endforeach
			@endif
		@empty
		@endforelse
	</div>

	<div class="separator"></div>

	@if(empty($receipt_details->hide_price))
		<div class="summary">
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
				<span class="summary-value">{{$receipt_details->subtotal}}</span>
			</div>
			@if(!empty($receipt_details->shipping_charges))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->shipping_charges_label !!}</span>
					<span class="summary-value">{{$receipt_details->shipping_charges}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->packing_charge))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->packing_charge_label !!}</span>
					<span class="summary-value">{{$receipt_details->packing_charge}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->discount))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->discount_label !!}</span>
					<span class="summary-value">(-) {{$receipt_details->discount}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->total_line_discount))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->line_discount_label !!}</span>
					<span class="summary-value">(-) {{$receipt_details->total_line_discount}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->additional_expenses))
				@foreach($receipt_details->additional_expenses as $key => $val)
					<div class="summary-row">
						<span class="summary-label">{{$key}}</span>
						<span class="summary-value">(+) {{$val}}</span>
					</div>
				@endforeach
			@endif
			@if(!empty($receipt_details->reward_point_label))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->reward_point_label !!}</span>
					<span class="summary-value">(-) {{$receipt_details->reward_point_amount}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->tax))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->tax_label !!}</span>
					<span class="summary-value">(+) {{$receipt_details->tax}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->round_off_amount) && $receipt_details->round_off_amount > 0)
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->round_off_label !!}</span>
					<span class="summary-value">{{$receipt_details->round_off}}</span>
				</div>
			@endif
			<div class="separator"></div>
			<div class="summary-row summary-total">
				<span class="summary-label">{!! $receipt_details->total_label !!}</span>
				<span class="summary-value">{{$receipt_details->total}}</span>
			</div>
			@if(!empty($receipt_details->total_in_words))
				<div class="notes">({{$receipt_details->total_in_words}})</div>
			@endif
		</div>

		<div class="payment-section">
			@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
					<div class="summary-row payment-row">
						<span class="summary-label">Payment</span>
						<span class="summary-value can-wrap">{{$payment['method']}}</span>
					</div>
					<div class="summary-row payment-row">
						<span class="summary-label">Paid</span>
						<span class="summary-value">{{$payment['amount']}}</span>
					</div>
				@endforeach
			@endif
			@if(!empty($receipt_details->total_paid))
				<div class="summary-row paid-total">
					<span class="summary-label">{!! $receipt_details->total_paid_label !!}</span>
					<span class="summary-value">{{$receipt_details->total_paid}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->total_due_label !!}</span>
					<span class="summary-value">{{$receipt_details->total_due}}</span>
				</div>
			@endif
			@if(!empty($receipt_details->all_due))
				<div class="summary-row">
					<span class="summary-label">{!! $receipt_details->all_bal_label !!}</span>
					<span class="summary-value">{{$receipt_details->all_due}}</span>
				</div>
			@endif
		</div>
	@endif

	@if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) && !empty($receipt_details->taxes))
		<div class="separator"></div>
		<div class="notes">{{$receipt_details->tax_summary_label}}</div>
		<table class="tax-table">
			@foreach($receipt_details->taxes as $key => $val)
				<tr>
					<td>{{$key}}</td>
					<td>{{$val}}</td>
				</tr>
			@endforeach
		</table>
	@endif

	@if(!empty($receipt_details->additional_notes))
		<div class="notes">{!! nl2br($receipt_details->additional_notes) !!}</div>
	@endif

	@if($receipt_details->show_barcode)
		<img class="barcode" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, array(39, 48, 54), true)}}" alt="">
	@endif
	@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
		<img class="qr" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE')}}" alt="">
	@endif

	<div class="separator"></div>
	<div class="invoice-footer">
		@if(!empty($receipt_details->footer_text))
			{!! $receipt_details->footer_text !!}
		@else
			Thank you for shopping<br>with us!
		@endif
	</div>
</div>
</body>
</html>
