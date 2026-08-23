@foreach($featured_products as $variation)
	@php
		$image_url = asset('/img/default.png');
		if (count($variation->media) > 0) {
			$image_url = $variation->media->first()->display_url;
		} elseif (!empty($variation->product->image_url)) {
			$image_url = $variation->product->image_url;
		}
	@endphp
	<div class="col-md-3 col-xs-4 product_list no-print">
		<div class="product_box" data-toggle="tooltip" data-placement="bottom" data-variation_id="{{$variation->id}}" title="{{$variation->full_name}}">

		<div class="image-container"
			style="background-image: url('{{ $image_url }}');
			background-repeat: no-repeat; background-position: center;
			background-size: contain;">
		</div>

		<span class="pos-quick-add" aria-hidden="true"><i class="fas fa-plus"></i></span>

		<div class="text_div">
			<small class="text text-muted pos-prod-name">{{$variation->product->name}}
			@if($variation->product->type == 'variable')
				- {{$variation->name}}
			@endif
			</small>

			<small class="text-muted pos-prod-sku">
				({{$variation->sub_sku}})
			</small>
		</div>

		</div>
	</div>
@endforeach
