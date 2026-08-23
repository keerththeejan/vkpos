{{-- Shared barcode image — sizing via barcode_height_factor + print engine --}}
@php
	$barcodeFactor = $barcode_height_factor ?? 0.34;
	$barcodeGenHeight = max(18, min(55, (int) round($barcodeFactor * 100)));
@endphp
<img class="label-sticker-barcode"
	src="data:image/png;base64,{{ DNS1D::getBarcodePNG($page_product->sub_sku, $page_product->barcode_type, 3, $barcodeGenHeight, [0, 0, 0], false) }}"
	alt="{{ $page_product->sub_sku }}"
	data-barcode-factor="{{ $barcodeFactor }}"
	style="max-width: calc(var(--label-w-mm, 50.8mm) - (var(--ld-safe-pad-mm, 1.2mm) * 2)); max-height: var(--barcode-max-h-mm, 8mm); width: auto; height: auto; object-fit: contain; object-position: center; display: block; margin: 0 auto; transform: none;">
@if(!empty($show_sku_text))
	<span class="label-sticker-sku">{{ $page_product->sub_sku }}</span>
@endif
