<div class="modal-dialog is-modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\InvoiceSchemeController::class, 'update'], [$invoice->id]), 'method' => 'put', 'id' => 'invoice_scheme_add_form']) !!}

        <div class="modal-header is-modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="invoiceSchemeEditTitle">@lang('invoice.edit_invoice')</h4>
        </div>

        <div class="modal-body is-modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <p class="is-field-label">Format</p>
                </div>
                <div class="option-div-group is-format-group">
                    <div class="col-sm-4">
                        <label class="option-div is-format-card @if($invoice->scheme_type == 'blank') active @endif">
                            <input type="radio" name="scheme_type" value="blank" required @if($invoice->scheme_type == 'blank') checked @endif>
                            <span class="is-format-title">XXXX</span>
                            <span class="is-format-help">Custom prefix only</span>
                            <i class="fa fa-check-circle icon" aria-hidden="true"></i>
                        </label>
                    </div>
                    <div class="col-sm-4">
                        <label class="option-div is-format-card @if($invoice->scheme_type == 'year') active @endif">
                            <input type="radio" name="scheme_type" value="year" required @if($invoice->scheme_type == 'year') checked @endif>
                            <span class="is-format-title">{{ date('Y') }}{{ config('constants.invoice_scheme_separator') }}XXXX</span>
                            <span class="is-format-help">Prefix + current year</span>
                            <i class="fa fa-check-circle icon" aria-hidden="true"></i>
                        </label>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group is-preview-box">
                        <label>@lang('invoice.preview'):</label>
                        <div id="preview_format">@lang('invoice.not_selected')</div>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('name', __('invoice.name') . ':*') !!}
                        {!! Form::text('name', $invoice->name, ['class' => 'form-control', 'required', 'maxlength' => 191, 'placeholder' => __('invoice.name')]); !!}
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('invoice_number_type', __('invoice.number_type') . ':*') !!} @show_tooltip(__('invoice.number_type_tooltip'))
                        {!! Form::select('number_type', $number_types, $invoice->number_type, ['class' => 'form-control select2', 'id' => 'invoice_number_type', 'required']); !!}
                    </div>
                </div>

                <div id="invoice_format_settings">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('prefix', __('invoice.prefix') . ':') !!}
                            {!! Form::text('prefix', $invoice->prefix, ['class' => 'form-control', 'maxlength' => 191, 'placeholder' => __('invoice.prefix')]); !!}
                        </div>
                    </div>
                    <div class="col-sm-6 sequential_field @if($invoice->number_type == 'random') hide @endif">
                        <div class="form-group">
                            {!! Form::label('start_number', __('invoice.start_number') . ':') !!}
                            {!! Form::number('start_number', $invoice->start_number, ['class' => 'form-control', 'required', 'min' => 0]); !!}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('total_digits', __('invoice.total_digits') . ':') !!}
                            {!! Form::select('total_digits', ['4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10'], $invoice->total_digits, ['class' => 'form-control', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="is-readonly-box">
                            <span class="k">@lang('invoice.invoice_count')</span>
                            <span class="v">{{ (int) $invoice->invoice_count }}</span>
                            <p>@lang('invoice.invoice_count_locked')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer is-modal-footer">
            <button type="button" class="is-btn is-btn-ghost" data-dismiss="modal">@lang('messages.close')</button>
            <button type="submit" class="is-btn is-btn-primary">@lang('messages.save')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
