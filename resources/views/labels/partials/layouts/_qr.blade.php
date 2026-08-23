@if(\App\Utils\LabelFieldEngine::isEnabled($print, config('label_fields.qr_code', [])))
	<img class="label-sticker-qr"
		style="max-height: {{ $barcode_details->height * ($qr_height_factor ?? 0.22) }}in !important; display: block;"
		src="data:image/png;base64,{{ DNS2D::getBarcodePNG($page_product->sub_sku, 'QRCODE', 3, 3) }}"
		alt="QR">
@endif
