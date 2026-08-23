@include('labels.partials.layouts._helpers')

@php
	use App\Utils\LabelFieldEngine;

	$labelFields = LabelFieldEngine::visibleFields($page_product, $print, $business_name);
	$labelFieldsByKey = LabelFieldEngine::fieldsByKey($labelFields);

	$nameSize = (int) ($print['name_size'] ?? 15);
	$priceSize = (int) ($print['price_size'] ?? 14);
	$showName = ! empty($print['name']) && ! empty($labelFieldsByKey['product_name']);
	$showPrice = ! empty($print['price']);
	$showBarcode = ! empty($labelFieldsByKey['barcode'])
		|| LabelFieldEngine::isEnabled($print, config('label_fields.barcode', []));
	$showQr = ! empty($labelFieldsByKey['qr_code']);

	$nameText = $showName ? ($labelFieldsByKey['product_name']['value'] ?? '') : '';
	$nameFontSize = label_sticker_font_size($nameText, $nameSize, 7, 28);
@endphp

{{-- Single vertical stack: name → price → barcode → barcode number (once each) --}}
<div class="label-sticker label-sticker-line2 label-sticker-line2--stack">
	@if($showName && $nameText !== '')
		<div class="label-sticker-line2__block label-sticker-line2__block--name">
			<span class="label-sticker-line2__name"
				style="font-size: {{ $nameFontSize }}px"
				data-base-size="{{ $nameSize }}"
				data-wrap-lines="2">{{ $nameText }}</span>
		</div>
	@endif

	@if($showPrice)
		<div class="label-sticker-line2__block label-sticker-line2__block--price">
			@include('labels.partials.layouts._price', ['price_size' => $priceSize])
		</div>
	@endif

	@if($showBarcode || $showQr)
		<div class="label-sticker-line2__block label-sticker-line2__block--barcode">
			@if($showQr)
				@include('labels.partials.layouts._qr', ['qr_height_factor' => 0.14])
			@endif
			@if($showBarcode)
	@include('labels.partials.layouts._barcode', [
					'show_sku_text' => true,
					'barcode_height_factor' => 0.32,
				])
			@endif
		</div>
	@endif
</div>
