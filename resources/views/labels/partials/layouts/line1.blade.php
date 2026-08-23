@include('labels.partials.layouts._helpers')

@php
	$nameText = !empty($print['name']) ? $page_product->product_actual_name : '';
	$nameSize = label_sticker_font_size($nameText, (int) ($print['name_size'] ?? 15), 8, 22);
	$priceSize = label_sticker_font_size(
		(string) ($page_product->sell_price_inc_tax ?? ''),
		(int) ($print['price_size'] ?? 14),
		8,
		10
	);
@endphp

<div class="label-sticker label-sticker-line1">
	@if(!empty($print['name']) && $nameText !== '')
		<span class="label-sticker-line1__name"
			style="font-size: {{ $nameSize }}px"
			data-base-size="{{ $print['name_size'] ?? 15 }}">{{ $nameText }}</span>
	@endif

	@if(!empty($print['price']))
		<span class="label-sticker-line1__sep" aria-hidden="true">|</span>
		<span class="label-sticker-line1__price" style="font-size: {{ $priceSize }}px">
			@include('labels.partials.layouts._price', ['price_size' => $priceSize])
		</span>
	@endif

	<div class="label-sticker-line1__barcode">
		@include('labels.partials.layouts._barcode', [
			'show_sku_text' => false,
			'barcode_height_factor' => 0.18,
		])
	</div>
</div>
