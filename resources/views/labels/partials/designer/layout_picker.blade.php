{{-- All sticker_layout values — single radio group (controlled by template gallery + pro buttons) --}}
<div class="ld-visually-hidden" aria-hidden="true" id="sticker_layout_radios">
	@foreach(['default', 'line1', 'line2', 'line3', 'mfg_line1', 'mfg_line2', 'mfg_line3', 'custom'] as $layoutVal)
		<label>
			<input type="radio" name="sticker_layout" value="{{ $layoutVal }}" class="ld-layout-input"
				@if($layoutVal === 'line2') checked @endif>
		</label>
	@endforeach
</div>

<div class="ld-panel__head mt-3"><i class="fa fa-industry"></i> @lang('barcode.designer_pro_layouts')</div>
<div class="ld-layout-grid mt-2">
	<button type="button" class="ld-template-card" data-set-layout="mfg_line1">
		<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
		<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg1')</div>
		<div class="ld-template-card__desc">@lang('barcode.sticker_layout_mfg1_help')</div>
	</button>
	<button type="button" class="ld-template-card" data-set-layout="mfg_line2">
		<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
		<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg2')</div>
		<div class="ld-template-card__desc">@lang('barcode.sticker_layout_mfg2_help')</div>
	</button>
	<button type="button" class="ld-template-card" data-set-layout="mfg_line3">
		<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
		<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg3')</div>
		<div class="ld-template-card__desc">@lang('barcode.sticker_layout_mfg3_help')</div>
	</button>
	<button type="button" class="ld-template-card" data-set-layout="custom">
		<div class="ld-template-card__icon"><i class="fa fa-arrows"></i></div>
		<div class="ld-template-card__name">@lang('barcode.sticker_layout_custom')</div>
		<div class="ld-template-card__desc">@lang('barcode.sticker_layout_custom_help')</div>
	</button>
</div>

@include('labels.partials.custom_designer')

<p class="ld-note mb-0">@lang('barcode.designer_layout_note')</p>
