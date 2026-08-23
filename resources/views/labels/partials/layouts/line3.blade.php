@include('labels.partials.layouts._helpers')

@php
	$line1Text = '';
	$line1Size = 12;
	if (!empty($print['business_name'])) {
		$line1Text = $business_name;
		$line1Size = label_sticker_font_size($line1Text, (int) ($print['business_name_size'] ?? 12), 8, 30);
	} elseif (!empty($print['packing_date']) && !empty($page_product->packing_date)) {
		$line1Text = __('lang_v1.packing_date') . ': ' . $page_product->packing_date;
		$line1Size = label_sticker_font_size($line1Text, (int) ($print['packing_date_size'] ?? 12), 8, 30);
	}

	$nameText = !empty($print['name']) ? $page_product->product_actual_name : '';
	$nameSize = label_sticker_font_size($nameText, (int) ($print['name_size'] ?? 15), 9, 30);

	$variationText = '';
	if (!empty($print['variations']) && $page_product->is_dummy != 1) {
		$variationText = $page_product->product_variation_name . ': ' . $page_product->variation_name;
	}
	$variationSize = label_sticker_font_size($variationText, (int) ($print['variations_size'] ?? 12), 8, 24);

	$priceSize = (int) ($print['price_size'] ?? 14);
@endphp

<div class="label-sticker label-sticker-line3">
	@if($line1Text !== '')
		<div class="label-sticker-line3__row label-sticker-line3__row--meta">
			<span style="font-size: {{ $line1Size }}px">{{ $line1Text }}</span>
		</div>
	@endif

	<div class="label-sticker-line3__row label-sticker-line3__row--name">
		@if(!empty($print['name']) && $nameText !== '')
			<span class="label-sticker-line3__name"
				style="font-size: {{ $nameSize }}px"
				data-base-size="{{ $print['name_size'] ?? 15 }}">{{ $nameText }}</span>
		@endif
		@if($variationText !== '')
			<span class="label-sticker-line3__variation" style="font-size: {{ $variationSize }}px">{{ $variationText }}</span>
		@endif
	</div>

	<div class="label-sticker-line3__row label-sticker-line3__row--footer">
		@if(!empty($print['price']))
			<span class="label-sticker-line3__price" style="font-size: {{ $priceSize }}px">
				@include('labels.partials.layouts._price', ['price_size' => $priceSize])
			</span>
		@endif
		<div class="label-sticker-line3__barcode">
			@include('labels.partials.layouts._barcode', [
				'show_sku_text' => true,
				'barcode_height_factor' => 0.20,
			])
		</div>
	</div>
</div>
