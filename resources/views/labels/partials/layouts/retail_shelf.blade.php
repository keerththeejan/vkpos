@include('labels.partials.layouts._helpers')
@php
	$align = request()->get('ld_text_align', 'center');
	$nameText = ! empty($print['name']) ? ($page_product->product_actual_name ?? '') : '';
	$nameSize = label_sticker_font_size($nameText, (int) ($print['name_size'] ?? 14), 8, 28);
	$priceSize = (int) ($print['price_size'] ?? 16);
	$showBarcode = ! empty($print['show_barcode']) || ! isset($print['show_barcode']);
@endphp

<div class="label-sticker label-sticker-retail-shelf ld-align-{{ $align }}">
	@if(! empty($print['business_name']))
		<div class="label-retail-shelf__store" data-base-size="{{ $print['business_name_size'] ?? 10 }}">{{ $business_name }}</div>
	@endif
	@if($nameText !== '')
		<div class="label-retail-shelf__name"
			style="font-size: {{ $nameSize }}px"
			data-base-size="{{ $print['name_size'] ?? 14 }}"
			data-wrap-lines="2">{{ $nameText }}</div>
	@endif
	@if(! empty($print['price']))
		<div class="label-retail-shelf__price" style="font-size: {{ $priceSize }}px">
			@include('labels.partials.layouts._price', ['price_size' => $priceSize])
		</div>
	@endif
	@if(! empty($print['show_sku']) && ! empty($page_product->sub_sku))
		<div class="label-retail-shelf__sku" data-base-size="8">{{ $page_product->sub_sku }}</div>
	@endif
	@if($showBarcode && empty($print['show_qr_code']))
		<div class="label-retail-shelf__barcode">
			@include('labels.partials.layouts._barcode', ['show_sku_text' => false, 'barcode_height_factor' => 0.16])
		</div>
	@endif
</div>
