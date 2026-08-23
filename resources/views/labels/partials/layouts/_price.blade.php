<span class="label-sticker-price" style="font-size: {{ $price_size ?? $print['price_size'] }}px;">
	@lang('lang_v1.price'):
	<b>{{ session('currency')['symbol'] ?? '' }}
	@if($print['price_type'] == 'inclusive')
		{{ @num_format($page_product->sell_price_inc_tax) }}
	@else
		{{ @num_format($page_product->default_sell_price) }}
	@endif</b>
</span>
