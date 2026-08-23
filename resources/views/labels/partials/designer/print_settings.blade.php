{{-- Print layout controls — sync to existing barcode_setting presets (no backend changes) --}}
<div class="ld-print-settings">
	<div class="ld-print-settings__hero">
		<strong><i class="fa fa-print"></i> @lang('barcode.designer_zebra_target')</strong>
		<p class="mb-2 small text-muted">50 mm × 25 mm · 2 across · gap · 203 DPI</p>
		<button type="button" class="ld-btn ld-btn--primary ld-btn--sm" id="ld_apply_zebra_5025">
			<i class="fa fa-magic"></i> @lang('barcode.designer_apply_5025')
		</button>
		<button type="button" class="ld-btn ld-btn--outline ld-btn--sm" id="ld_btn_print_test">
			<i class="fa fa-flask"></i> @lang('barcode.designer_print_test')
		</button>
	</div>

	<div class="ld-print-grid">
		<div class="ld-print-field">
			<label for="ld_print_width">@lang('barcode.width') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_width" name="ld_print_width" min="20" max="120" step="0.1" value="50">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_height">@lang('barcode.height') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_height" name="ld_print_height" min="10" max="80" step="0.1" value="25">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_columns">@lang('barcode.designer_columns')</label>
			<select class="form-control input-sm" id="ld_print_columns" name="ld_print_columns">
				<option value="1">1 @lang('barcode.designer_column')</option>
				<option value="2" selected>2 @lang('barcode.designer_columns')</option>
				<option value="3">3 @lang('barcode.designer_columns')</option>
			</select>
		</div>
		<div class="ld-print-field">
			<label for="ld_print_col_gap">@lang('barcode.col_distance') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_col_gap" name="ld_print_col_gap" min="0" max="10" step="0.1" value="2">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_row_gap">@lang('barcode.row_distance') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_row_gap" name="ld_print_row_gap" min="0" max="10" step="0.1" value="2">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_margin_top">@lang('barcode.top_margin') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_margin_top" name="ld_print_margin_top" min="0" max="10" step="0.1" value="0">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_margin_left">@lang('barcode.left_margin') (mm)</label>
			<input type="number" class="form-control input-sm" id="ld_print_margin_left" name="ld_print_margin_left" min="0" max="10" step="0.1" value="0">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_start_pos">@lang('barcode.designer_start_position')</label>
			<input type="number" class="form-control input-sm" id="ld_print_start_pos" name="ld_print_start_pos" min="0" max="999" step="1" value="0" title="@lang('barcode.designer_start_position_help')">
		</div>
		<div class="ld-print-field">
			<label for="ld_print_copies">@lang('barcode.designer_copies')</label>
			<input type="number" class="form-control input-sm" id="ld_print_copies" min="1" max="9999" value="1">
		</div>
	</div>

	<div class="ld-print-feed">
		<span class="ld-feed-badge" id="ld_feed_type"><i class="fa fa-minus"></i> @lang('barcode.designer_feed_gap')</span>
		<span class="ld-feed-badge" id="ld_dots_calc">400 × 200 dots</span>
	</div>

	<p class="ld-note mb-2">@lang('barcode.designer_print_sync_note')</p>

	<div class="form-group mb-0">
		<label><i class="fa fa-cog"></i> @lang('barcode.barcode_setting')</label>
		{!! Form::select('barcode_setting', $barcode_settings, !empty($default) ? $default->id : null, ['class' => 'form-control', 'id' => 'barcode_setting']); !!}
	</div>

	<ul class="ld-info-list mt-3">
		<li><span>@lang('barcode.designer_preset_name')</span><span id="ld_barcode_setting_name">—</span></li>
		<li><span>@lang('barcode.designer_label_dims')</span><span id="ld_label_dims">—</span></li>
		<li><span>@lang('barcode.designer_total_copies')</span><span id="ld_total_copies">0</span></li>
	</ul>

	<p class="ld-note mt-2 mb-0">@lang('barcode.designer_zebra_notes')</p>
</div>
