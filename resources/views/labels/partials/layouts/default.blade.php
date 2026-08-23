@include('labels.partials.layouts._helpers')

{{-- Default layout: unchanged original sticker content --}}
{{-- Business Name --}}
@if(!empty($print['business_name']))
	<b style="display: block !important; font-size: {{ $print['business_name_size'] }}px">{{ $business_name }}</b>
@endif

{{-- Product Name --}}
@if(!empty($print['name']))
	<span style="display: block !important; font-size: {{ $print['name_size'] }}px">
		{{ $page_product->product_actual_name }}

		@if(!empty($print['lot_number']) && !empty($page_product->lot_number))
			<span style="font-size: {{ 12 * $factor }}px">
				 ({{ $page_product->lot_number }})
			</span>
		@endif
	</span>
@endif

{{-- Variation --}}
@if(!empty($print['variations']) && $page_product->is_dummy != 1)
	<span style="display: block !important; font-size: {{ $print['variations_size'] }}px">
		{{ $page_product->product_variation_name }}:<b>{{ $page_product->variation_name }}</b>
	</span>
@endif
{{-- product_custom_fields --}}
@php
	$custom_labels = json_decode(session('business.custom_labels'), true);
	$product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
@endphp

@foreach($product_custom_fields as $index => $cf)
	@php
		$field_name = 'product_custom_field' . $loop->iteration;
	@endphp
	@if(!empty($cf) && !empty($page_product->$field_name) && !empty($print[$field_name]))
		<span style="font-size: {{ $print[$field_name . '_size'] }}px">
			<b>{{ $cf }}:</b>
			{{ $page_product->$field_name }}
		</span>
	@endif
@endforeach
<br>

{{-- Price --}}
@if(!empty($print['price']))
	@include('labels.partials.layouts._price', ['price_size' => $print['price_size']])
@endif
@if(!empty($print['exp_date']) && !empty($page_product->exp_date))
	<br>
	<span style="font-size: {{ $print['exp_date_size'] }}px">
		<b>@lang('product.exp_date'):</b>
		{{ $page_product->exp_date }}
	</span>
	@if($barcode_details->is_continuous)
	<br>
	@endif
@endif

@if(!empty($print['packing_date']) && !empty($page_product->packing_date))
	<span style="font-size: {{ $print['packing_date_size'] }}px">
		<b>@lang('lang_v1.packing_date'):</b>
		{{ $page_product->packing_date }}
	</span>
@endif
{{-- Barcode --}}
@include('labels.partials.layouts._barcode', ['show_sku_text' => true, 'barcode_height_factor' => 0.24])
