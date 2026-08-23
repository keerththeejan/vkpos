@include('labels.partials.layouts._helpers')
@php
	use App\Utils\LabelFieldEngine;

	$align = request()->get('ld_text_align', 'center');
	$nameText = ! empty($print['name']) ? ($page_product->product_actual_name ?? '') : '';
	$nameSize = label_sticker_font_size($nameText, (int) ($print['name_size'] ?? 12), 7, 24);
	$priceSize = (int) ($print['price_size'] ?? 13);
	$mrpSize = 10;
	$showBarcode = LabelFieldEngine::isEnabled($print, config('label_fields.barcode', []));
	$showMrp = LabelFieldEngine::isEnabled($print, config('label_fields.mrp', []));
@endphp

<div class="label-sticker label-sticker-mrp-offer ld-align-{{ $align }}">
	@if($nameText !== '')
		<div class="label-mrp-offer__name"
			style="font-size: {{ $nameSize }}px"
			data-base-size="{{ $print['name_size'] ?? 12 }}"
			data-wrap-lines="2">{{ $nameText }}</div>
	@endif
	@if($showBarcode)
		<div class="label-mrp-offer__barcode">
			@include('labels.partials.layouts._barcode', ['show_sku_text' => true, 'barcode_height_factor' => 0.22])
		</div>
	@endif
	<div class="label-mrp-offer__prices">
		@if($showMrp)
			<span class="label-mrp-offer__mrp" style="font-size: {{ $mrpSize }}px">
				MRP {{ session('currency')['symbol'] ?? '' }}{{ @num_format($page_product->sell_price_inc_tax) }}
			</span>
		@endif
		@if(! empty($print['price']))
			<span class="label-mrp-offer__offer" style="font-size: {{ $priceSize }}px">
				@include('labels.partials.layouts._price', ['price_size' => $priceSize])
			</span>
		@endif
	</div>
</div>
