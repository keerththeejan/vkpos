@php
	$hasAxis = !empty($axis ?? null) && empty($readonly ?? null);
	$hasNudge = !empty($nudge ?? null) && empty($readonly ?? null);
@endphp
<div class="zl-field">
	<span class="zl-field__label">{{ $label }}</span>
	<div class="zl-field__control">
		@if($hasAxis || $hasNudge)
			<button type="button" class="zl-nudge" data-target="{{ $id }}" data-step="-1" aria-label="Decrease {{ $label }}">{{ $hasAxis && ($axis ?? '') === 'y' ? '↑' : ($hasAxis ? '←' : '−') }}</button>
		@endif
		<input id="{{ $id }}" class="form-control" type="number" step="{{ $step ?? '1' }}" @if($hasAxis) data-axis="{{ $axis }}" @endif @if(isset($min)) min="{{ $min }}" @endif @if(isset($max)) max="{{ $max }}" @endif @if(!empty($readonly ?? null)) readonly @endif>
		@if($hasAxis || $hasNudge)
			<button type="button" class="zl-nudge" data-target="{{ $id }}" data-step="1" aria-label="Increase {{ $label }}">{{ $hasAxis && ($axis ?? '') === 'y' ? '↓' : ($hasAxis ? '→' : '+') }}</button>
		@endif
	</div>
</div>
