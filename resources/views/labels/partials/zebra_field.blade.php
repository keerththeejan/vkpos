<div class="zl-field">
	<span class="zl-field__label">{{ $label }}</span>
	<div class="zl-field__control">
		@if(!empty($axis ?? null) && empty($readonly ?? null))
			<button type="button" class="zl-nudge" data-target="{{ $id }}" data-step="-1" aria-label="Decrease {{ $label }}">{{ ($axis ?? '') === 'y' ? '↑' : '←' }}</button>
		@endif
		<input id="{{ $id }}" class="form-control" type="number" step="{{ $step ?? '1' }}" @if(!empty($axis ?? null) && empty($readonly ?? null)) data-axis="{{ $axis }}" @endif @if(!empty($readonly ?? null)) readonly @endif>
		@if(!empty($axis ?? null) && empty($readonly ?? null))
			<button type="button" class="zl-nudge" data-target="{{ $id }}" data-step="1" aria-label="Increase {{ $label }}">{{ ($axis ?? '') === 'y' ? '↓' : '→' }}</button>
		@endif
	</div>
</div>
