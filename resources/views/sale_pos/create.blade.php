@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print premium-pos-shell">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
            $show_products = empty($pos_settings['hide_product_suggestion']);
        @endphp

        {{-- Enterprise meta / future-ready (UI only) --}}
        <div class="pos-enterprise-strip" aria-label="POS tools">
            <span class="pos-enterprise-chip"><i class="fas fa-barcode"></i> Barcode Scan</span>
            <span class="pos-enterprise-chip"><i class="fas fa-mobile-alt"></i> IMEI Select</span>
            <span class="pos-enterprise-chip"><i class="fas fa-qrcode"></i> QR Pay</span>
            <span class="pos-enterprise-chip"><i class="fas fa-camera"></i> Camera Scan</span>
            <span class="pos-enterprise-chip"><i class="fas fa-robot"></i> AI Recommend</span>
            <span class="pos-enterprise-chip muted">UI shell — existing POS logic unchanged</span>
        </div>

        <div class="pos-kpi-bar" aria-label="Live invoice KPIs">
            <div class="pos-kpi-card"><span class="k">Items</span><strong id="pos_kpi_items">0</strong></div>
            <div class="pos-kpi-card"><span class="k">Quantity</span><strong id="pos_kpi_qty">0</strong></div>
            <div class="pos-kpi-card"><span class="k">Subtotal</span><strong id="pos_kpi_subtotal">0</strong></div>
            <div class="pos-kpi-card"><span class="k">Payable</span><strong id="pos_kpi_payable">0</strong></div>
        </div>

        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}

        <div class="premium-pos-workspace @if(!$show_products || isMobile()) is-cart-only @endif">

            @if ($show_products && !isMobile())
                {{-- LEFT: Categories / Brands --}}
                <aside class="premium-pos-panel premium-pos-left" aria-label="{{ __('category.category') }}">
                    <div class="premium-pos-panel-head">
                        <h3><i class="fas fa-th-large"></i> @lang('category.category')</h3>
                    </div>
                    <div class="premium-pos-panel-body">
                        <input type="search" id="pos_category_filter" class="pos-cat-search" placeholder="@lang('lang_v1.search')..." autocomplete="off" aria-label="@lang('lang_v1.search')">
                        @include('sale_pos.partials.pos_sidebar', ['premium_layout' => true, 'categories_only' => true])
                    </div>
                </aside>

                {{-- CENTER: Search + Products --}}
                <section class="premium-pos-panel premium-pos-center" aria-label="@lang('sale.products')">
                    <div class="premium-pos-search-wrap premium-pos-search-bar">
                        <div class="form-group" style="margin:0;">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal" data-target="#configure_search_modal" title="{{__('lang_v1.configure_product_search')}}"><i class="fas fa-search-plus"></i></button>
                                </div>
                                {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'),
                                'disabled' => is_null($default_location)? true : false,
                                'autofocus' => is_null($default_location)? false : true,
                                ]); !!}
                                <span class="input-group-btn">
                                    @if(isset($pos_settings['enable_weighing_scale']) && $pos_settings['enable_weighing_scale'] == 1)
                                        <button type="button" class="btn btn-default bg-white btn-flat" id="weighing_scale_btn" data-toggle="modal" data-target="#weighing_scale_modal"
                                        title="@lang('lang_v1.weighing_scale')"><i class="fa fa-digital-tachograph text-primary fa-lg"></i></button>
                                    @endif
                                    <button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="premium-pos-products-scroll">
                        @include('sale_pos.partials.pos_sidebar', ['premium_layout' => true, 'products_only' => true])
                    </div>
                </section>
            @endif

            {{-- RIGHT: Cart --}}
            <aside class="premium-pos-panel premium-pos-right" aria-label="@lang('sale.pos_sale')">
                <div class="premium-pos-panel-head">
                    <h3><i class="fas fa-shopping-bag"></i> @lang('sale.pos_sale')</h3>
                    <span class="pos-chip" style="background:var(--pos-accent-soft);color:#059669;">
                        <i class="fas fa-user"></i> {{ auth()->user()->first_name ?? '' }}
                    </span>
                </div>
                <div class="premium-pos-panel-body">
                    {!! Form::hidden('location_id', $default_location->id ?? null, [
                        'id' => 'location_id',
                        'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                            ? $default_location->receipt_printer_type
                            : 'browser',
                        'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '',
                    ]) !!}
                    {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                    <input type="hidden" id="item_addition_method"
                        value="{{ $business_details->item_addition_method }}">

                    <div class="premium-pos-cart-customer">
                        <div class="pos-customer-card-title">
                            <span><i class="fas fa-user-circle"></i> Customer</span>
                            <span class="pos-enterprise-chip muted" style="padding:3px 8px;">F2</span>
                        </div>
                        @include('sale_pos.partials.pos_form', [
                            'premium_layout' => true,
                            'hide_search_in_form' => ($show_products && !isMobile()),
                        ])
                        <div class="pos-customer-meta" aria-hidden="true">
                            <div><span>Due</span><strong id="pos_cust_due">—</strong></div>
                            <div><span>Reward</span><strong id="pos_cust_rp">—</strong></div>
                        </div>
                    </div>

                    {{-- IMEI UI guidance; real selection stays in product-row modal --}}
                    <div class="pos-imei-panel" id="pos_imei_placeholder">
                        <h4><i class="fas fa-barcode"></i> IMEI / Serial Number</h4>
                        <p>For IMEI-tracked products, use the product row modal <strong>Available</strong> serial picker. Buttons below are UI cues only.</p>
                        <div class="pos-imei-actions">
                            <button type="button" class="btn btn-default btn-sm" disabled title="Use product row IMEI picker"><i class="fas fa-qrcode"></i> Scan IMEI</button>
                            <button type="button" class="btn btn-default btn-sm" disabled title="Use product row IMEI picker"><i class="fas fa-list"></i> Select IMEI</button>
                            <button type="button" class="btn btn-default btn-sm" disabled title="Use product row IMEI picker"><i class="fas fa-paste"></i> Paste IMEI</button>
                            <button type="button" class="btn btn-default btn-sm" disabled title="Search via barcode / IMEI in search box"><i class="fas fa-search"></i> Search IMEI</button>
                        </div>
                    </div>

                    <div class="pos-invoice-summary-card">
                        @include('sale_pos.partials.pos_form_totals')
                    </div>

                    @include('sale_pos.partials.payment_modal')

                    @if (empty($pos_settings['disable_suspend']))
                        @include('sale_pos.partials.suspend_note_modal')
                    @endif

                    @if (empty($pos_settings['disable_recurring_invoice']))
                        @include('sale_pos.partials.recurring_invoice_modal')
                    @endif
                </div>
            </aside>
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
    @if ($show_products && isMobile())
        @include('sale_pos.partials.mobile_product_suggestions')
    @endif
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('sale_pos.partials.configure_search_modal')
    @include('sale_pos.partials.recent_transactions_modal')
    @include('sale_pos.partials.weighing_scale_modal')

@stop
@section('css')
    <link rel="stylesheet" href="{{ asset('css/pos-premium.css?v=' . $asset_v) }}">
    <link rel="stylesheet" href="{{ asset('css/register-premium.css?v=' . $asset_v) }}">
    @if (!empty($sub_type) && $sub_type === 'repair')
        <link rel="stylesheet" href="{{ asset('css/repair-register-premium.css?v=' . $asset_v) }}">
    @endif
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop
@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-serial-picker.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-premium-ui.js?v=' . $asset_v) }}"></script>
    @if (!empty($sub_type) && $sub_type === 'repair')
        <script src="{{ asset('js/repair-register-premium-ui.js?v=' . $asset_v) }}"></script>
    @endif
    @include('sale_pos.partials.keyboard_shortcuts')

    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection
