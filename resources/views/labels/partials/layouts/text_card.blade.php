@include('labels.partials.layouts._helpers')
@php
	use App\Utils\LabelFieldEngine;

	$align = request()->get('ld_text_align', 'center');
	$weight = request()->get('ld_text_weight', 'bold');
	$headerText = ! empty($print['business_name']) ? $business_name : ($page_product->product_actual_name ?? '');
	$headerSize = label_sticker_font_size($headerText, (int) ($print['business_name_size'] ?? 11), 7, 22);
	$nameText = ! empty($print['name']) ? ($page_product->product_actual_name ?? '') : '';
	$nameSize = label_sticker_font_size($nameText, (int) ($print['name_size'] ?? 10), 7, 26);
	$partNo = $page_product->sub_sku ?? '';
	$batchNo = $page_product->lot_number ?? '';
	$dateText = $page_product->packing_date ?? ($page_product->exp_date ?? '');
	$priceSize = (int) ($print['price_size'] ?? 12);
@endphp

<div class="label-sticker label-sticker-textcard ld-align-{{ $align }} ld-weight-{{ $weight }}"
	style="--ld-text-align: {{ $align }};">

	@if(!empty($print['business_name']) && $headerText !== '')
		<div class="label-textcard__header"
			style="font-size: {{ $headerSize }}px"
			data-base-size="{{ $print['business_name_size'] ?? 11 }}">{{ $headerText }}</div>
	@endif

	@if($nameText !== '' && empty($print['business_name']))
		<div class="label-textcard__header"
			style="font-size: {{ $headerSize }}px"
			data-base-size="{{ $print['name_size'] ?? 11 }}">{{ $nameText }}</div>
	@elseif($nameText !== '')
		<div class="label-textcard__name"
			style="font-size: {{ $nameSize }}px"
			data-base-size="{{ $print['name_size'] ?? 10 }}"
			data-wrap-lines="2">{{ $nameText }}</div>
	@endif

	@if($partNo !== '' && (! empty($print['show_sku']) || ! empty($print['name'])))
		<div class="label-textcard__line label-textcard__part" data-base-size="9">
			<span class="label-textcard__lbl">PN</span> {{ $partNo }}
		</div>
	@endif

	@if($batchNo !== '' && ! empty($print['lot_number']))
		<div class="label-textcard__line label-textcard__batch" data-base-size="9">
			<span class="label-textcard__lbl">@lang('lang_v1.lot_number')</span> {{ $batchNo }}
		</div>
	@elseif(! empty($print['show_batch_number']) && ! empty($page_product->lot_number))
		<div class="label-textcard__line label-textcard__batch" data-base-size="9">
			<span class="label-textcard__lbl">Batch</span> {{ $page_product->lot_number }}
		</div>
	@endif

	@if($dateText !== '' && (! empty($print['packing_date']) || ! empty($print['exp_date'])))
		<div class="label-textcard__line label-textcard__date" data-base-size="9">
			<span class="label-textcard__lbl">Date</span> {{ $dateText }}
		</div>
	@endif

	@if(! empty($print['price']))
		<div class="label-textcard__price" style="font-size: {{ $priceSize }}px">
			@include('labels.partials.layouts._price', ['price_size' => $priceSize])
		</div>
	@endif
</div>
