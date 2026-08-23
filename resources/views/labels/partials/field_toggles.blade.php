@php
	$optionalFields = collect(config('label_fields', []))->filter(function ($field, $key) {
		return empty($field['legacy']);
	});
@endphp

<div class="row" id="label_optional_fields">
	@foreach($optionalFields as $key => $field)
		<div class="col-sm-6 col-md-4 col-lg-3">
			<div class="checkbox">
				<label>
					<input type="checkbox"
						name="print[{{ $field['print_key'] }}]"
						value="1"
						class="label-field-toggle"
						data-default-checked="{{ !empty($field['default']) ? '1' : '0' }}"
						@if(!empty($field['default'])) checked @endif>
					<b>{{ $field['label'] }}</b>
				</label>
			</div>
		</div>
	@endforeach
</div>
<p class="text-muted small">
	@lang('barcode.optional_fields_help')
</p>
