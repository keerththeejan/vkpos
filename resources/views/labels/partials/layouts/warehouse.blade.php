@include('labels.partials.layouts._helpers')
@php
	use App\Utils\LabelFieldEngine;

	$fields = LabelFieldEngine::fieldsByKey(
		LabelFieldEngine::visibleFields($page_product, $print, $business_name)
	);
	$align = request()->get('ld_text_align', 'left');
	$sku = $page_product->sub_sku ?? '';
	$name = $page_product->product_actual_name ?? '';
	$nameSize = label_sticker_font_size($name, (int) ($print['name_size'] ?? 11), 7, 24);
	$showBarcode = ! empty($fields['barcode']) || LabelFieldEngine::isEnabled($print, config('label_fields.barcode', []));
@endphp

<div class="label-sticker label-sticker-warehouse ld-align-{{ $align }}">
	@if($sku !== '')
		<div class="label-warehouse__sku" data-base-size="13">{{ $sku }}</div>
	@endif
	@if(! empty($print['name']) && $name !== '')
		<div class="label-warehouse__name"
			style="font-size: {{ $nameSize }}px"
			data-base-size="{{ $print['name_size'] ?? 11 }}"
			data-wrap-lines="2">{{ $name }}</div>
	@endif
	<div class="label-warehouse__meta">
		@if(! empty($fields['batch_number']))
			<span class="label-warehouse__tag" data-base-size="8">B: {{ $fields['batch_number']['value'] }}</span>
		@elseif(! empty($print['lot_number']) && ! empty($page_product->lot_number))
			<span class="label-warehouse__tag" data-base-size="8">B: {{ $page_product->lot_number }}</span>
		@endif
		@if(! empty($print['packing_date']) && ! empty($page_product->packing_date))
			<span class="label-warehouse__tag" data-base-size="8">{{ $page_product->packing_date }}</span>
		@endif
	</div>
	@if($showBarcode)
		<div class="label-warehouse__barcode">
			@include('labels.partials.layouts._barcode', ['show_sku_text' => false, 'barcode_height_factor' => 0.18])
		</div>
	@endif
</div>
