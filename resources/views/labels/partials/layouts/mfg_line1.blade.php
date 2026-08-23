@include('labels.partials.layouts._mfg_init')

<div class="label-sticker label-sticker-mfg1">
	<div class="label-sticker-mfg1__row">
		<div class="label-sticker-mfg1__text">
			@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'product_name', 'fontSize' => $print['name_size'] ?? 13, 'charBudget' => 22])
			@if(!empty($labelFieldsByKey['product_name']) && (!empty($labelFieldsByKey['batch_number']) || !empty($labelFieldsByKey['lot_number'])))
				<span class="mfg-sep">|</span>
			@endif
			@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'batch_number', 'fontSize' => 11, 'charBudget' => 14])
			@if(empty($labelFieldsByKey['batch_number']))
				@include('labels.partials.layouts._mfg_text', ['fieldKey' => 'lot_number', 'fontSize' => 11, 'charBudget' => 14, 'prefix' => __('lang_v1.lot_number').': '])
			@endif
		</div>
		<div class="label-sticker-mfg1__codes">
			@if(!empty($labelFieldsByKey['qr_code']))
				@include('labels.partials.layouts._qr', ['qr_height_factor' => 0.16])
			@endif
			@if(!empty($labelFieldsByKey['barcode']) || \App\Utils\LabelFieldEngine::isEnabled($print, config('label_fields.barcode', [])))
				@include('labels.partials.layouts._barcode', ['show_sku_text' => false, 'barcode_height_factor' => 0.16])
			@endif
		</div>
	</div>
</div>
