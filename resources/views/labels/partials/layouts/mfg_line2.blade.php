@include('labels.partials.layouts._mfg_init')

@php
	$line2Parts = [];
	foreach (['selling_price', 'mrp', 'batch_number', 'lot_number', 'sku'] as $k) {
		if (!empty($labelFieldsByKey[$k]) && $labelFieldsByKey[$k]['type'] === 'text') {
			$line2Parts[] = $k;
		}
	}
@endphp

<div class="label-sticker label-sticker-mfg2">
	<div class="label-sticker-mfg2__line label-sticker-mfg2__line--name">
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'product_name', 'fontSize' => $print['name_size'] ?? 14, 'charBudget' => 30])
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'variation', 'fontSize' => $print['variations_size'] ?? 10, 'charBudget' => 24])
	</div>
	<div class="label-sticker-mfg2__line label-sticker-mfg2__line--meta">
		@foreach($line2Parts as $i => $partKey)
			@if($i > 0)<span class="mfg-sep">/</span>@endif
			@include('labels.partials.layouts._mfg_text', ['fieldKey' => $partKey, 'fontSize' => 10, 'charBudget' => 12])
		@endforeach
		@if(count($line2Parts) && (!empty($labelFieldsByKey['barcode']) || !empty($labelFieldsByKey['qr_code'])))
			<span class="mfg-sep">/</span>
		@endif
		<div class="label-sticker-mfg2__inline-code">
			@if(!empty($labelFieldsByKey['qr_code']))
				@include('labels.partials.layouts._qr', ['qr_height_factor' => 0.14])
			@endif
			@if(!empty($labelFieldsByKey['barcode']) || \App\Utils\LabelFieldEngine::isEnabled($print, config('label_fields.barcode', [])))
				@include('labels.partials.layouts._barcode', ['show_sku_text' => true, 'barcode_height_factor' => 0.14])
			@endif
		</div>
	</div>
</div>
