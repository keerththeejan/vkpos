@if(!empty($labelFieldsByKey[$fieldKey]) && ($labelFieldsByKey[$fieldKey]['type'] ?? 'text') === 'text')
	@php
		$fieldVal = $labelFieldsByKey[$fieldKey]['value'];
		$baseSize = (int) ($fontSize ?? 12);
		$computedSize = label_sticker_font_size($fieldVal, $baseSize, 7, $charBudget ?? 24);
	@endphp
	<span class="mfg-field mfg-field--{{ $fieldKey }}"
		style="font-size: {{ $computedSize }}px"
		data-base-size="{{ $baseSize }}">{{ $prefix ?? '' }}{{ $fieldVal }}{{ $suffix ?? '' }}</span>
@endif
