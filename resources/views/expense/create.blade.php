@extends('layouts.app')
@section('title', __('expense.add_expense'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/expenses-create-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content exc-shell">
	{{-- Sticky Header --}}
	<div class="exc-header" role="banner">
		<div class="exc-header-left">
			<h1>@lang('expense.add_expense')</h1>
			<p class="exc-subtitle">Enterprise expense entry &amp; financial transaction</p>
			<div class="exc-breadcrumb" aria-label="Breadcrumb">
				<span>Home</span>
				<span>/</span>
				<span>@lang('expense.expenses')</span>
				<span>/</span>
				<span>@lang('messages.add')</span>
			</div>
		</div>
		<div class="exc-header-actions">
			<button type="button" class="exc-btn exc-btn-ghost" id="exc_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
				<i class="fas fa-moon"></i>
			</button>
			<button type="button" class="exc-btn" id="exc_refresh_btn" title="Refresh">
				<i class="fas fa-sync-alt"></i> Refresh
			</button>
			<a class="exc-btn" id="exc_cancel_link" href="{{ action([\App\Http\Controllers\ExpenseController::class, 'index']) }}">
				<i class="fas fa-times"></i> Cancel
			</a>
			<button type="button" class="exc-btn exc-btn-primary" id="exc_header_save">
				<i class="fas fa-save"></i> @lang('messages.save')
			</button>
		</div>
		<div class="exc-header-meta" aria-label="Expense context">
			<div class="exc-meta-item"><span>Expense #</span><strong>Auto</strong></div>
			<div class="exc-meta-item"><span>Reference</span><strong id="exc_meta_ref">Auto</strong></div>
			<div class="exc-meta-item"><span>Date</span><strong id="exc_meta_date">—</strong></div>
			<div class="exc-meta-item"><span>Location</span><strong id="exc_meta_location">—</strong></div>
			<div class="exc-meta-item"><span>Category</span><strong id="exc_meta_category">—</strong></div>
			<div class="exc-meta-item"><span>User</span><strong>{{ auth()->user()->first_name ?? '—' }}</strong></div>
		</div>
	</div>

	<div class="exc-live-bar" aria-label="Live expense overview">
		<div class="exc-live-item">
			<span>Location</span>
			<strong id="exc_live_location">—</strong>
		</div>
		<div class="exc-live-item">
			<span>Category</span>
			<strong id="exc_live_category">—</strong>
		</div>
		<div class="exc-live-item">
			<span>Payee</span>
			<strong id="exc_live_payee">—</strong>
		</div>
		<div class="exc-live-item">
			<span>Total</span>
			<strong id="exc_live_total">0</strong>
		</div>
	</div>

	<div class="exc-future-strip" aria-label="Coming soon">
		<span class="exc-chip"><i class="fas fa-check-double"></i> Multi-Level Approval</span>
		<span class="exc-chip"><i class="fas fa-redo"></i> Recurring</span>
		<span class="exc-chip"><i class="fas fa-camera"></i> OCR Receipt</span>
		<span class="exc-chip"><i class="fas fa-robot"></i> AI Category</span>
		<span class="exc-chip"><i class="fas fa-sitemap"></i> Cost Center</span>
		<span class="exc-chip muted">UI placeholders — accounting &amp; payment logic unchanged</span>
	</div>

	<div class="exc-layout">
		<div class="exc-main">

	{!! Form::open(['url' => action([\App\Http\Controllers\ExpenseController::class, 'store']), 'method' => 'post', 'id' => 'add_expense_form', 'files' => true ]) !!}

	{{-- Expense Information --}}
	<div class="box box-solid">
		<div class="box-body">
			<div class="exc-section-title"><i class="fas fa-info-circle"></i> Expense Information</div>
			<div class="exc-placeholder-grid" aria-label="Future allocation fields">
				<div class="exc-ph">Cost Center (UI)</div>
				<div class="exc-ph">Project (UI)</div>
				<div class="exc-ph">Department (UI)</div>
			</div>
			<div class="row">

				@if(count($business_locations) == 1)
					@php 
						$default_location = current(array_keys($business_locations->toArray())) 
					@endphp
				@else
					@php $default_location = null; @endphp
				@endif
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('location_id', __('purchase.business_location').':*') !!}
						{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
					</div>
				</div>

				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('expense_category_id', __('expense.expense_category').':') !!}
						{!! Form::select('expense_category_id', $expense_categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
						<p class="exc-hint">F2 opens category</p>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
			            {!! Form::label('expense_sub_category_id', __('product.sub_category') . ':') !!}
			              {!! Form::select('expense_sub_category_id', [],  null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
			          </div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
						<p class="help-block">
			                @lang('lang_v1.leave_empty_to_autogenerate')
			            </p>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'expense_transaction_date']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('expense_for', __('expense.expense_for').':') !!} @show_tooltip(__('tooltip.expense_for'))
						{!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('contact_id', __('lang_v1.expense_for_contact').':') !!} 
						{!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
						<p class="exc-hint">F3 opens contact / payee</p>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                        <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        @includeIf('components.document_help_text')</p></small>
						<p class="exc-hint">F8 opens attachment picker</p>
                    </div>
                </div>
				<div class="col-md-4">
			    	<div class="form-group">
			            {!! Form::label('tax_id', __('product.applicable_tax') . ':' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::select('tax_id', $taxes['tax_rates'], null, ['class' => 'form-control'], $taxes['attributes']); !!}

							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" 
							value="0">
			            </div>
			        </div>
			    </div>
			    <div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('final_total', __('sale.total_amount') . ':*') !!}
						{!! Form::text('final_total', null, ['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required']); !!}
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('additional_notes', __('expense.expense_note') . ':') !!}
								{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
				</div>
				<div class="col-md-4 col-sm-6">
					<br>
					<label>
		              {!! Form::checkbox('is_refund', 1, false, ['class' => 'input-icheck', 'id' => 'is_refund']); !!} @lang('lang_v1.is_refund')?
		            </label>@show_tooltip(__('lang_v1.is_refund_help'))
				</div>
			</div>
		</div>
	</div> <!--box end-->

	@include('expense.recur_expense_form_part')

	@component('components.widget', ['class' => 'box-solid', 'id' => "payment_rows_div", 'title' => __('purchase.add_payment')])
	<div class="exc-section-title" style="margin-top:4px;"><i class="fas fa-credit-card"></i> Payment Information</div>
	<p class="exc-hint" style="margin-top:-8px;margin-bottom:12px;">F4 opens payment method · existing payment row logic unchanged</p>
	<div class="payment_row">
		@include('sale_pos.partials.payment_row_form', ['row_index' => 0, 'show_date' => true])
		<hr>
		<div class="row">
			<div class="col-sm-12">
				<div class="pull-right">
					<strong>@lang('purchase.payment_due'):</strong>
					<span id="payment_due">{{@num_format(0)}}</span>
				</div>
			</div>
		</div>
	</div>
	@endcomponent

	<div class="col-sm-12 text-center exc-actions-bar">
		<a class="exc-btn" href="{{ action([\App\Http\Controllers\ExpenseController::class, 'index']) }}">
			<i class="fas fa-times"></i> Cancel
		</a>
		<button type="submit" id="save_expense_btn" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white exc-btn exc-btn-primary">@lang('messages.save')</button>
	</div>

	<div class="exc-sticky-save" aria-label="Quick save">
		<span class="text-muted" style="font-size:12px;margin-right:auto;">Ctrl+S / F6 to save · ESC cancel</span>
		<a class="exc-btn" href="{{ action([\App\Http\Controllers\ExpenseController::class, 'index']) }}">Cancel</a>
		<button type="button" class="exc-btn exc-btn-primary" id="exc_sticky_save">
			<i class="fas fa-save"></i> @lang('messages.save')
		</button>
	</div>

{!! Form::close() !!}
		</div>

		<aside class="exc-side" aria-label="Expense summary">
			<div class="exc-summary-card">
				<h3>Live Financial Summary</h3>
				<div class="exc-summary-rows">
					<div><span>Tax</span><strong id="exc_sum_tax">—</strong></div>
					<div><span>Total Amount</span><strong id="exc_sum_total">0</strong></div>
					<div><span>Paid</span><strong id="exc_sum_paid">0</strong></div>
					<div><span>Outstanding</span><strong id="exc_sum_due">—</strong></div>
				</div>
				<button type="button" class="exc-btn exc-btn-primary" id="exc_side_save" style="width:100%;margin-top:12px;">
					<i class="fas fa-save"></i> @lang('messages.save')
				</button>
				<div class="exc-note">
					Outstanding mirrors <code>#payment_due</code> from existing calculation.
				</div>
			</div>

			<div class="exc-audit-card">
				<h3>Audit Information</h3>
				<div class="exc-audit-rows">
					<div><span>Created By</span><strong>{{ auth()->user()->first_name ?? '—' }}</strong></div>
					<div><span>Status</span><strong>New</strong></div>
					<div><span>Approval</span><strong>—</strong></div>
				</div>
				<div class="exc-note">Read-only UI · approval workflow placeholder</div>
			</div>

			<div class="exc-help-card">
				<h3>Shortcuts</h3>
				<ul class="exc-shortcuts">
					<li><span>Category</span><kbd>F2</kbd></li>
					<li><span>Contact / Payee</span><kbd>F3</kbd></li>
					<li><span>Payment method</span><kbd>F4</kbd></li>
					<li><span>Save</span><kbd>F6</kbd></li>
					<li><span>Print</span><kbd>F7</kbd></li>
					<li><span>Attachment</span><kbd>F8</kbd></li>
					<li><span>Save</span><kbd>Ctrl+S</kbd></li>
					<li><span>Cancel</span><kbd>ESC</kbd></li>
				</ul>
			</div>
		</aside>
	</div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('js/expenses-create-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
	$(document).ready( function(){
		$('.paid_on').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
	});
	
	__page_leave_confirmation('#add_expense_form');
	$(document).on('change', 'input#final_total, input.payment-amount', function() {
		calculateExpensePaymentDue();
	});

	function calculateExpensePaymentDue() {
		var final_total = __read_number($('input#final_total'));
		var payment_amount = __read_number($('input.payment-amount'));
		var payment_due = final_total - payment_amount;
		$('#payment_due').text(__currency_trans_from_en(payment_due, true, false));
	}

	$(document).on('change', '#recur_interval_type', function() {
	    if ($(this).val() == 'months') {
	        $('.recur_repeat_on_div').removeClass('hide');
	    } else {
	        $('.recur_repeat_on_div').addClass('hide');
	    }
	});

	$('#is_refund').on('ifChecked', function(event){
		$('#recur_expense_div').addClass('hide');
	});
	$('#is_refund').on('ifUnchecked', function(event){
		$('#recur_expense_div').removeClass('hide');
	});

	$(document).on('change', '.payment_types_dropdown, #location_id', function(e) {
	    var default_accounts = $('select#location_id').length ? 
	                $('select#location_id')
	                .find(':selected')
	                .data('default_payment_accounts') : [];
	    var payment_types_dropdown = $('.payment_types_dropdown');
	    var payment_type = payment_types_dropdown.val();
	    if (payment_type) {
	        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
	            default_accounts[payment_type]['account'] : '';
	        var payment_row = payment_types_dropdown.closest('.payment_row');
	        var row_index = payment_row.find('.payment_row_index').val();

	        var account_dropdown = payment_row.find('select#account_' + row_index);
	        if (account_dropdown.length && default_accounts) {
	            account_dropdown.val(default_account);
	            account_dropdown.change();
	        }
	    }
	});
</script>
@endsection
