@forelse($products as $product)
	@php
		$stock_qty = $product->enable_stock ? (float) $product->qty_available : null;
		$stock_class = '';
		if ($product->enable_stock) {
			if ($stock_qty <= 0) {
				$stock_class = 'is-out';
			} elseif ($stock_qty <= 5) {
				$stock_class = 'is-low';
			}
		}
		$image_url = asset('/img/default.png');
		if (count($product->media) > 0) {
			$image_url = $product->media->first()->display_url;
		} elseif (!empty($product->product_image)) {
			$image_url = asset('/uploads/img/' . rawurlencode($product->product_image));
		}
	@endphp
	<div class="col-md-3 col-xs-4 product_list no-print">
		<div class="product_box" data-variation_id="{{$product->id}}" title="{{$product->name}} @if($product->type == 'variable')- {{$product->variation}} @endif {{ '(' . $product->sub_sku . ')'}} @if(!empty($show_prices)) @lang('lang_v1.default') - @format_currency($product->selling_price) @foreach($product->group_prices as $group_price) @if(array_key_exists($group_price->price_group_id, $allowed_group_prices)) {{$allowed_group_prices[$group_price->price_group_id]}} - @format_currency($group_price->price_inc_tax) @endif @endforeach @endif">

		<div class="image-container"
			style="background-image: url('{{ $image_url }}');
			background-repeat: no-repeat; background-position: center;
			background-size: contain;">
		</div>

		<span class="pos-quick-add" aria-hidden="true"><i class="fas fa-plus"></i></span>

		<div class="text_div">
			<small class="text text-muted pos-prod-name">{{$product->name}}
			@if($product->type == 'variable')
				- {{$product->variation}}
			@endif
			</small>

			<small class="text-muted pos-prod-sku">
				({{$product->sub_sku}})
			</small>

			<div class="pos-prod-meta">
				@if(!empty($show_prices))
					<span class="pos-prod-price">@format_currency($product->selling_price)</span>
				@else
					<span class="pos-prod-price">&nbsp;</span>
				@endif

				@if($product->enable_stock)
					<span class="pos-stock-pill {{ $stock_class }}">
						{{ @num_format($product->qty_available) }} {{$product->unit}}
					</span>
				@else
					<span class="pos-stock-pill">--</span>
				@endif
			</div>
		</div>

		</div>
	</div>
@empty
	<input type="hidden" id="no_products_found">
	<div class="col-md-12">
		<h4 class="text-center" style="color:var(--pos-text-muted);padding:28px 12px;">
			@lang('lang_v1.no_products_to_display')
		</h4>
	</div>
@endforelse
