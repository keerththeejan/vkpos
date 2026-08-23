{{-- Barcode display options (frontend only — maps to existing print[] fields) --}}
<div class="ld-barcode-options">
	<div class="ld-panel__head" style="margin:-16px -16px 12px;border-radius:0;">
		<i class="fa fa-barcode"></i> @lang('barcode.designer_barcode_options')
	</div>
	<div class="ld-toggle-row">
		<span>@lang('barcode.designer_barcode_auto_width')</span>
		<label class="ld-switch"><input type="checkbox" id="ld_barcode_auto_width" checked disabled><span class="ld-switch__slider"></span></label>
	</div>
	<div class="ld-toggle-row">
		<span>@lang('barcode.designer_barcode_center')</span>
		<label class="ld-switch"><input type="checkbox" id="ld_barcode_center" checked disabled><span class="ld-switch__slider"></span></label>
	</div>
	<div class="ld-toggle-row">
		<span>@lang('barcode.designer_barcode_text')</span>
		<label class="ld-switch"><input type="checkbox" id="ld_show_barcode_text" checked><span class="ld-switch__slider"></span></label>
	</div>
	<p class="ld-note mb-0">@lang('barcode.designer_barcode_types')</p>
</div>

<div class="ld-font-options mt-3">
	<div class="ld-panel__head" style="margin:-16px -16px 12px;border-radius:0;">
		<i class="fa fa-font"></i> @lang('barcode.designer_font_options')
	</div>
	<div class="ld-field-grid ld-field-grid--2">
		<div class="form-group">
			<label for="ld_text_lines_select">@lang('barcode.designer_text_lines')</label>
			<select class="form-control input-sm" id="ld_text_lines_select">
				<option value="1">1 @lang('barcode.designer_line')</option>
				<option value="2" selected>2 @lang('barcode.designer_lines')</option>
				<option value="3">3 @lang('barcode.designer_lines')</option>
			</select>
		</div>
		<div class="form-group">
			<label for="ld_text_align_select">@lang('barcode.designer_text_align')</label>
			<select class="form-control input-sm" id="ld_text_align_select">
				<option value="left">@lang('barcode.designer_align_left')</option>
				<option value="center" selected>@lang('barcode.designer_align_center')</option>
				<option value="right">@lang('barcode.designer_align_right')</option>
			</select>
		</div>
		<div class="form-group">
			<label for="ld_text_weight_select">@lang('barcode.designer_text_weight')</label>
			<select class="form-control input-sm" id="ld_text_weight_select">
				<option value="normal">@lang('barcode.designer_weight_normal')</option>
				<option value="bold" selected>@lang('barcode.designer_weight_bold')</option>
			</select>
		</div>
		<div class="form-group">
			<label>@lang('barcode.designer_price_display')</label>
			<select class="form-control input-sm" id="ld_price_display">
				<option value="inclusive">@lang('barcode.designer_price_retail')</option>
				<option value="exclusive">@lang('barcode.designer_price_wholesale')</option>
			</select>
		</div>
	</div>
	<p class="ld-note mb-0 mt-2">@lang('barcode.designer_price_note')</p>
</div>

<input type="hidden" name="design_template" id="design_template" value="">
<input type="hidden" name="ld_text_align" id="ld_text_align_field" value="center">
<input type="hidden" name="ld_text_weight" id="ld_text_weight_field" value="bold">
