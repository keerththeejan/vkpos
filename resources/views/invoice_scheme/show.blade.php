<div class="modal-dialog is-modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header is-modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="invoiceSchemeViewTitle">@lang('messages.view') — {{ $invoice->name }}</h4>
        </div>
        <div class="modal-body is-modal-body">
            <dl class="is-view-grid">
                <div>
                    <dt>@lang('invoice.name')</dt>
                    <dd>{{ $invoice->name }}</dd>
                </div>
                <div>
                    <dt>@lang('sale.status')</dt>
                    <dd>
                        @if($invoice->is_default)
                            <span class="is-status is-status-default"><span class="is-dot"></span> @lang('barcode.default')</span>
                        @else
                            <span class="is-status is-status-standard"><span class="is-dot"></span> @lang('invoice.standard')</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>@lang('invoice.prefix')</dt>
                    <dd>
                        @if($invoice->scheme_type == 'year')
                            {{ $invoice->prefix }}{{ date('Y') }}{{ config('constants.invoice_scheme_separator') }}
                        @else
                            {{ $invoice->prefix ?: '—' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>@lang('invoice.number_type')</dt>
                    <dd>{{ $number_types[$invoice->number_type] ?? $invoice->number_type }}</dd>
                </div>
                <div>
                    <dt>@lang('invoice.start_number')</dt>
                    <dd>{{ $invoice->start_number }}</dd>
                </div>
                <div>
                    <dt>@lang('invoice.invoice_count')</dt>
                    <dd>{{ (int) $invoice->invoice_count }}</dd>
                </div>
                <div>
                    <dt>@lang('invoice.total_digits')</dt>
                    <dd>{{ $invoice->total_digits }}</dd>
                </div>
                <div>
                    <dt>@lang('invoice.next_number')</dt>
                    <dd>{{ $next_count }}</dd>
                </div>
                <div class="is-view-span">
                    <dt>@lang('invoice.used_in_locations')</dt>
                    <dd>{{ count($used_locations) ? implode(', ', $used_locations) : '—' }}</dd>
                </div>
            </dl>
        </div>
        <div class="modal-footer is-modal-footer">
            <button type="button" class="is-btn is-btn-ghost" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
