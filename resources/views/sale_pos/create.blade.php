@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
<section class="content no-print">
	<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
	@if(!empty($pos_settings['allow_overselling']))
		<input type="hidden" id="is_overselling_allowed">
	@endif
	@if(session('business.enable_rp') == 1)
        <input type="hidden" id="reward_point_enabled">
    @endif
    @php
		$is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
		$is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
	@endphp
	{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_pos_sell_form' ]) !!}
	<div class="row mb-12">
		<div class="col-md-12 tw-pt-0 tw-mb-14">
			<div class="row tw-flex lg:tw-flex-row md:tw-flex-col sm:tw-flex-col tw-flex-col tw-items-start md:tw-gap-4">
				<div class="tw-px-3 tw-w-full lg:tw-px-0 pos-excel-main">
					<div class="pos-excel-sheet tw-bg-white tw-mb-2 md:tw-mb-8 tw-p-2">
						<div class="box-body pb-0">
							{!! Form::hidden('location_id', $default_location->id ?? null, ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '']); !!}
							<!-- sub_type -->
							{!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
							<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
								@include('sale_pos.partials.pos_form')

								@include('sale_pos.partials.pos_form_totals')

								@include('sale_pos.partials.payment_modal')

								@if(empty($pos_settings['disable_suspend']))
									@include('sale_pos.partials.suspend_note_modal')
								@endif

								@if(empty($pos_settings['disable_recurring_invoice']))
									@include('sale_pos.partials.recurring_invoice_modal')
								@endif
							</div>
						</div>
					</div>
				@if(empty($pos_settings['hide_product_suggestion'])  && !isMobile())
					<div class="col-md-5 no-padding pos-product-cards-panel" aria-hidden="true">
						@include('sale_pos.partials.pos_sidebar')
					</div>
				@endif
			</div>
		</div>
	</div>
	@include('sale_pos.partials.pos_form_actions')
	{!! Form::close() !!}
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
@if(empty($pos_settings['hide_product_suggestion']) && isMobile())
	@include('sale_pos.partials.mobile_product_suggestions')
@endif
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@include('sale_pos.partials.configure_search_modal')

@include('sale_pos.partials.recent_transactions_modal')

@include('sale_pos.partials.weighing_scale_modal')

@stop

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/pos-serial-picker.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	@include('sale_pos.partials.keyboard_shortcuts')

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif

    <!-- include module js -->
    @if(!empty($pos_module_data))
	    @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
	    @endforeach
	@endif
@endsection

@section('css')
	<style type="text/css">
		.print_section,
		#receipt_section {
		    display: none;
		}
		@media print {
			.no-print,
			.pos-header,
			.pos-form-actions,
			.main-footer,
			.modal,
			.scrolltop,
			nav,
			aside {
				display: none !important;
			}
		    .print_section,
		    #receipt_section {
		        display: block !important;
		        color: #000000 !important;
		        background: #ffffff !important;
		        font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace !important;
		        font-weight: 700 !important;
		        opacity: 1 !important;
		        -webkit-print-color-adjust: exact;
		        print-color-adjust: exact;
		    }
		    #receipt_section *,
		    .print_section * {
		        color: #000000 !important;
		        font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace !important;
		        font-weight: 700 !important;
		        opacity: 1 !important;
		        text-shadow: none !important;
		        -webkit-print-color-adjust: exact;
		        print-color-adjust: exact;
		        -webkit-font-smoothing: none;
		        word-break: normal !important;
		    }
		    #receipt_section .shop-name,
		    #receipt_section .shop-name *,
		    #receipt_section .shop-meta,
		    #receipt_section .shop-meta *,
		    .print_section .shop-name,
		    .print_section .shop-name *,
		    .print_section .shop-meta,
		    .print_section .shop-meta * {
		        font-family: "Noto Sans Tamil", Latha, "Nirmala UI", sans-serif !important;
		        color: #000000 !important;
		        font-weight: 700 !important;
		    }
		    #receipt_section .shop-name,
		    #receipt_section .shop-name *,
		    .print_section .shop-name,
		    .print_section .shop-name * {
		        font-weight: 800 !important;
		    }
		    #receipt_section .row.em .lbl,
		    #receipt_section .row.em .val,
		    #receipt_section .total,
		    #receipt_section .total *,
		    #receipt_section .change,
		    #receipt_section .change *,
		    #receipt_section .balance-due,
		    #receipt_section .balance-due *,
		    #receipt_section .paid-amount,
		    #receipt_section .paid-amount *,
		    .print_section .row.em .lbl,
		    .print_section .row.em .val,
		    .print_section .total,
		    .print_section .total *,
		    .print_section .change,
		    .print_section .change *,
		    .print_section .balance-due,
		    .print_section .balance-due *,
		    .print_section .paid-amount,
		    .print_section .paid-amount * {
		        font-weight: 800 !important;
		        color: #000000 !important;
		    }
		    #receipt_section .sep,
		    .print_section .sep {
		        border-bottom-color: #000000 !important;
		        opacity: 1 !important;
		    }
		    .color-555,
		    .color-555 *,
		    .text-muted,
		    .text-muted-imp {
		        color: #000000 !important;
		        opacity: 1 !important;
		    }
		}
		@page {
		    size: 80mm auto;
		    height: auto !important;
		    margin-top: 0mm;
		    margin-bottom: 0mm;
		    margin-left: 2mm;
		    margin-right: 2mm;
		}
	</style>
	<!-- include module css -->
    @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@endsection
