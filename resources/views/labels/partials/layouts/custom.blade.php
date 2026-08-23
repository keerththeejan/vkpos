@include('labels.partials.layouts._mfg_init')

@php
	$elements = $custom_layout['elements'] ?? [];
	if (empty($elements)) {
		$elements = [
		 ['field' => 'product_name', 'x' => 2, 'y' => 4, 'font' => 13],
		 ['field' => 'batch_number', 'x' => 2, 'y' => 32, 'font' => 10],
		 ['field' => 'selling_price', 'x' => 2, 'y' => 55, 'font' => 11],
		 ['field' => 'barcode', 'x' => 38, 'y' => 48, 'font' => 10],
		];
	}
@endphp

<div class="label-sticker label-sticker-custom-layout">
	@foreach($elements as $element)
		@php
			$fieldKey = $element['field'] ?? '';
			$x = (float) ($element['x'] ?? 0);
			$y = (float) ($element['y'] ?? 0);
			$font = (int) ($element['font'] ?? 12);
		@endphp
		<div class="label-sticker-custom-layout__item"
			data-field="{{ $fieldKey }}"
			style="left: {{ $x }}%; top: {{ $y }}%; font-size: {{ $font }}px;">
			@if($fieldKey === 'barcode')
				@include('labels.partials.layouts._barcode', ['show_sku_text' => false, 'barcode_height_factor' => 0.14])
			@elseif($fieldKey === 'qr_code')
				@include('labels.partials.layouts._qr', ['qr_height_factor' => 0.14])
			@elseif($fieldKey === 'company_logo')
				<span class="mfg-logo-placeholder">LOGO</span>
			@elseif(!empty($labelFieldsByKey[$fieldKey]))
				<span class="mfg-field" data-base-size="{{ $font }}">{{ $labelFieldsByKey[$fieldKey]['value'] }}</span>
			@endif
		</div>
	@endforeach
</div>
