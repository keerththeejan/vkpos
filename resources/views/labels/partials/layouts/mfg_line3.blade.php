@include('labels.partials.layouts._mfg_init')

@php
	$line2Tokens = [];
	if (!empty($labelFieldsByKey['batch_number'])) {
		$line2Tokens[] = 'batch_number';
	} elseif (!empty($labelFieldsByKey['lot_number'])) {
		$line2Tokens[] = 'lot_number';
	}
	if (!empty($labelFieldsByKey['packing_date'])) {
		$line2Tokens[] = 'packing_date';
	} elseif (!empty($labelFieldsByKey['manufacturing_date'])) {
		$line2Tokens[] = 'manufacturing_date';
	}
@endphp

<div class="label-sticker label-sticker-mfg3">
	<div class="label-sticker-mfg3__line label-sticker-mfg3__line--title">
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'product_name', 'fontSize' => $print['name_size'] ?? 13, 'charBudget' => 32])
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'variation', 'fontSize' => $print['variations_size'] ?? 10, 'charBudget' => 22])
	</div>

	@if(count($line2Tokens))
		<div class="label-sticker-mfg3__line label-sticker-mfg3__line--batch">
			@foreach($line2Tokens as $i => $tokenKey)
				@if($i > 0)<span class="mfg-sep">/</span>@endif
				@include('labels.partials.layouts._mfg_text', ['fieldKey' => $tokenKey, 'fontSize' => 10, 'charBudget' => 16])
			@endforeach
		</div>
	@endif

	<div class="label-sticker-mfg3__line label-sticker-mfg3__line--footer">
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'selling_price', 'fontSize' => $print['price_size'] ?? 12, 'charBudget' => 10])
		@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'mrp', 'fontSize' => 10, 'charBudget' => 10, 'prefix' => 'MRP '])
		<div class="label-sticker-mfg3__codes">
			@if(!empty($labelFieldsByKey['qr_code']))
				@include('labels.partials.layouts._qr', ['qr_height_factor' => 0.15])
			@endif
			@if(!empty($labelFieldsByKey['barcode']) || \App\Utils\LabelFieldEngine::isEnabled($print, config('label_fields.barcode', [])))
				@include('labels.partials.layouts._barcode', ['show_sku_text' => true, 'barcode_height_factor' => 0.15])
			@endif
		</div>
	</div>
</div>
