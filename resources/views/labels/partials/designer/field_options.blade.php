{{-- Core label field toggles — same name="" attributes as legacy form --}}
<div class="ld-field-grid ld-field-grid--3">
	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('barcode.print_name')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[name]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[name_size]" value="15">
		</div>
	</div>

	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('barcode.print_variations')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[variations]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[variations_size]" value="17">
		</div>
	</div>

	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('barcode.print_price')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[price]" value="1" id="is_show_price"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[price_size]" value="17">
		</div>
	</div>

	<div class="ld-field-card" id="price_type_div">
		<label>@lang('barcode.show_price')</label>
		{!! Form::select('print[price_type]', ['inclusive' => __('product.inc_of_tax'), 'exclusive' => __('product.exc_of_tax')], 'inclusive', ['class' => 'form-control input-sm']); !!}
		<p class="ld-note mb-0 mt-2">@lang('barcode.designer_price_types')</p>
	</div>

	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('barcode.print_business_name')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[business_name]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[business_name_size]" value="20">
		</div>
	</div>

	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('lang_v1.print_packing_date')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[packing_date]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[packing_date_size]" value="12">
		</div>
	</div>

	@if(request()->session()->get('business.enable_lot_number') == 1)
	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('lang_v1.print_lot_number')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[lot_number]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[lot_number_size]" value="12">
		</div>
	</div>
	@endif

	@if(request()->session()->get('business.enable_product_expiry') == 1)
	<div class="ld-field-card">
		<label class="ld-toggle-row">
			<span>@lang('lang_v1.print_exp_date')</span>
			<span class="ld-switch"><input type="checkbox" checked name="print[exp_date]" value="1"><span class="ld-switch__slider"></span></span>
		</label>
		<div class="input-group input-group-sm">
			<span class="input-group-addon">@lang('lang_v1.size')</span>
			<input type="text" class="form-control" name="print[exp_date_size]" value="12">
		</div>
	</div>
	@endif

	@php
		$custom_labels = json_decode(session('business.custom_labels'), true);
		$product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
	@endphp
	@foreach($product_custom_fields as $index => $cf)
		@if(!empty($cf))
			@php $field_name = 'product_custom_field' . $loop->iteration; @endphp
			<div class="ld-field-card">
				<label class="ld-toggle-row">
					<span>{{ $cf }}</span>
					<span class="ld-switch"><input type="checkbox" name="print[{{ $field_name }}]" value="1"><span class="ld-switch__slider"></span></span>
				</label>
				<div class="input-group input-group-sm">
					<span class="input-group-addon">@lang('lang_v1.size')</span>
					<input type="text" class="form-control" name="print[{{ $field_name }}_size]" value="12">
				</div>
			</div>
		@endif
	@endforeach
</div>
