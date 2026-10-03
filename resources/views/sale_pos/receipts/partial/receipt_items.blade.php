{{-- Five-column thermal item table. Widths are presentation only. --}}
@php
	if (! function_exists('receipt_product_name')) {
		function receipt_product_name(array $line): string
		{
			$parts = [];
			foreach (['name', 'product_variation', 'variation'] as $key) {
				$part = trim((string) ($line[$key] ?? ''));
				if ($part !== '' && $part !== 'DUMMY') {
					$parts[] = $part;
				}
			}
			return trim(implode(' ', $parts));
		}
	}

	if (! function_exists('receipt_qty_label')) {
		function receipt_qty_label(array $line): string
		{
			$raw = $line['quantity_uf'] ?? null;
			if ($raw !== null && $raw !== '' && is_numeric($raw)) {
				$number = (float) $raw;
				if (abs($number - round($number)) < 0.0000001) {
					return (string) (int) round($number);
				}
			}

			return (string) ($line['quantity'] ?? '');
		}
	}

	$showPrice = empty($receipt_details->hide_price);
	$colCount = $showPrice ? 5 : 3;
@endphp

<style>
	table.receipt-items {
		width: 100% !important;
		table-layout: fixed !important;
		border-collapse: collapse;
		margin-top: 8px;
	}
	table.receipt-items th,
	table.receipt-items td {
		padding: 1px 2px !important;
		border: 0;
		vertical-align: top;
		min-width: 0;
		font-size: 11px;
	}
	table.receipt-items .product-cell {
		width: 32%;
		text-align: left;
		overflow-wrap: break-word;
		word-break: normal;
	}
	table.receipt-items .qty-cell {
		width: 8%;
		text-align: center;
		white-space: nowrap !important;
		word-break: keep-all !important;
		overflow-wrap: normal !important;
	}
	table.receipt-items .unit-cell {
		width: 10%;
		text-align: center;
		white-space: nowrap !important;
		word-break: keep-all !important;
		overflow-wrap: normal !important;
	}
	table.receipt-items .price-cell,
	table.receipt-items .total-cell {
		width: 25%;
		text-align: right;
		white-space: nowrap !important;
		word-break: keep-all !important;
		overflow-wrap: normal !important;
		font-size: 10px !important;
	}
	table.receipt-items .receipt-name {
		display: block;
		font-size: 12px !important;
		font-weight: 600 !important;
		line-height: 1.2;
	}
	table.receipt-items .receipt-sku,
	table.receipt-items .receipt-meta {
		display: block;
		font-size: 9px !important;
		font-weight: 400 !important;
		line-height: 1.15;
	}
	table.receipt-items thead th {
		font-size: 10px !important;
		font-weight: 700 !important;
		line-height: 1.1;
		border-bottom: 1px solid #242424;
	}
</style>

<table class="receipt-items">
	<colgroup>
		@if($showPrice)
			<col class="receipt-col-product" style="width:32%">
			<col class="receipt-col-qty" style="width:8%">
			<col class="receipt-col-unit" style="width:10%">
			<col class="receipt-col-price" style="width:25%">
			<col class="receipt-col-total" style="width:25%">
		@else
			<col class="receipt-col-product" style="width:74%">
			<col class="receipt-col-qty" style="width:13%">
			<col class="receipt-col-unit" style="width:13%">
		@endif
	</colgroup>
	<thead>
		<tr>
			<th class="product-cell">Product</th>
			<th class="qty-cell">Qty</th>
			<th class="unit-cell">Unit</th>
			@if($showPrice)
				<th class="price-cell">Price</th>
				<th class="total-cell">Total</th>
			@endif
		</tr>
	</thead>
	<tbody>
		@forelse($receipt_details->lines as $line)
			<tr>
				<td class="product-cell">
					<span class="receipt-name">{{ receipt_product_name($line) }}</span>
					@if(!empty($line['brand']))
						<span class="receipt-sku">{{ $line['brand'] }}</span>
					@endif
					@if(!empty($line['sub_sku']))
						@foreach(preg_split('/\s+/u', trim($line['sub_sku'])) as $skuPart)
							@if($skuPart !== '')
								<span class="receipt-sku">{{ $skuPart }}</span>
							@endif
						@endforeach
					@endif
					@if(!empty($line['cat_code']))
						<span class="receipt-sku">{{ $line['cat_code'] }}</span>
					@endif
					@if(!empty($line['product_custom_fields']))
						<span class="receipt-meta">{{ $line['product_custom_fields'] }}</span>
					@endif
					@if(!empty($line['product_description']))
						<span class="receipt-meta">{!! $line['product_description'] !!}</span>
					@endif
					@if(!empty($line['sell_line_note']))
						<span class="receipt-meta">{!! $line['sell_line_note'] !!}</span>
					@endif
					@if(!empty($line['lot_number']))
						<span class="receipt-meta">{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}</span>
					@endif
					@if(!empty($line['product_expiry']))
						<span class="receipt-meta">{{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}</span>
					@endif
					@if(!empty($line['warranty_name']) || !empty($line['warranty_exp_date']) || !empty($line['warranty_description']))
						<span class="receipt-meta">
							{{ $line['warranty_name'] ?? '' }}
							@if(!empty($line['warranty_exp_date'])) - {{ @format_date($line['warranty_exp_date']) }} @endif
							{{ $line['warranty_description'] ?? '' }}
						</span>
					@endif
					@if(!empty($receipt_details->item_discount_label) && !empty($line['total_line_discount']) && $line['total_line_discount'] != 0)
						<span class="receipt-meta">{{ strip_tags($receipt_details->item_discount_label) }} {{ $line['total_line_discount'] }}@if(!empty($line['line_discount_percent'])) ({{ $line['line_discount_percent'] }}%)@endif</span>
					@endif
					@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
						<span class="receipt-meta">1 {{ $line['units'] }} = {{ $line['base_unit_multiplier'] }} {{ $line['base_unit_name'] }}</span>
					@endif
				</td>
				<td class="qty-cell">{{ receipt_qty_label($line) }}</td>
				<td class="unit-cell">{{ $line['units'] }}</td>
				@if($showPrice)
					<td class="price-cell">{{ $line['unit_price_before_discount'] }}</td>
					<td class="total-cell">{{ $line['line_total'] }}</td>
				@endif
			</tr>
			@if(!empty($line['modifiers']))
				@foreach($line['modifiers'] as $modifier)
					<tr>
						<td class="product-cell">
							<span class="receipt-name">{{ receipt_product_name($modifier) }}</span>
							@if(!empty($modifier['sub_sku']))
								@foreach(preg_split('/\s+/u', trim($modifier['sub_sku'])) as $skuPart)
									@if($skuPart !== '')
										<span class="receipt-sku">{{ $skuPart }}</span>
									@endif
								@endforeach
							@endif
							@if(!empty($modifier['sell_line_note']))
								<span class="receipt-meta">{!! $modifier['sell_line_note'] !!}</span>
							@endif
						</td>
						<td class="qty-cell">{{ receipt_qty_label($modifier) }}</td>
						<td class="unit-cell">{{ $modifier['units'] ?? '' }}</td>
						@if($showPrice)
							<td class="price-cell">{{ $modifier['unit_price_inc_tax'] }}</td>
							<td class="total-cell">{{ $modifier['line_total'] }}</td>
						@endif
					</tr>
				@endforeach
			@endif
		@empty
			<tr>
				<td class="product-cell" colspan="{{ $colCount }}">&nbsp;</td>
			</tr>
		@endforelse
	</tbody>
</table>
