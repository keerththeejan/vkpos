@php
    $paper_profile = isset($paper_profile) ? (string) $paper_profile : '80';
    if ($paper_profile !== '58') {
        $paper_profile = '80';
    }
    $hide_price = !empty($receipt_details->hide_price);
    $show_disc_price = !$hide_price && !empty($receipt_details->discounted_unit_price_label);
    $show_item_disc = !$hide_price && !empty($receipt_details->item_discount_label);
    $table_cols = 'cols-5';
    if ($show_disc_price && $show_item_disc) {
        $table_cols = 'cols-7';
    } elseif ($show_disc_price || $show_item_disc) {
        $table_cols = 'cols-6';
    } elseif ($hide_price) {
        $table_cols = 'cols-3';
    }

    $invoice_datetime = trim((string) ($receipt_details->invoice_date ?? ''));
    $invoice_date_display = $invoice_datetime;
    $invoice_time_display = '';
    if (preg_match('/^(.*?)[\s]+(\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AaPp][Mm])?)$/u', $invoice_datetime, $m)) {
        $invoice_date_display = trim($m[1]);
        $invoice_time_display = trim($m[2]);
    }

    $change_uf = (float) ($receipt_details->change_amount_uf ?? 0);
    $show_change = $change_uf > 0.00001;
    $show_due = !$show_change && !empty($receipt_details->total_due) && !empty($receipt_details->total_due_label);
@endphp
<style type="text/css">
@include('sale_pos.receipts.partial.premium_thermal_css')
@page {
    size: auto;
    margin: 0;
}
</style>
<div class="receipt-container vkpos-thermal paper-{{ $paper_profile }}">
    <div class="receipt">
        @if(empty($receipt_details->letter_head))
            @if(!empty($receipt_details->logo))
                <div class="centered receipt-section">
                    <img class="logo" src="{{ $receipt_details->logo }}" alt="">
                </div>
            @endif
            <div class="header receipt-section">
                @if(!empty($receipt_details->header_text))
                    <div class="business-name shop-name">{!! $receipt_details->header_text !!}</div>
                @endif
                @if(!empty($receipt_details->display_name))
                    <div class="business-name shop-name">{{ $receipt_details->display_name }}</div>
                @endif
                @if(!empty($receipt_details->address) || !empty($receipt_details->contact) || !empty($receipt_details->website) || !empty($receipt_details->location_custom_fields) || !empty($receipt_details->sub_heading_line1) || !empty($receipt_details->sub_heading_line2) || !empty($receipt_details->sub_heading_line3) || !empty($receipt_details->sub_heading_line4) || !empty($receipt_details->sub_heading_line5) || !empty($receipt_details->tax_info1) || !empty($receipt_details->tax_info2))
                <div class="shop-meta">
                    @if(!empty($receipt_details->address))
                        {!! $receipt_details->address !!}
                    @endif
                    @if(!empty($receipt_details->contact))
                        @if(!empty($receipt_details->address))<br>@endif
                        {!! $receipt_details->contact !!}
                    @endif
                    @if(!empty($receipt_details->contact) && !empty($receipt_details->website)), @endif
                    @if(!empty($receipt_details->website)){{ $receipt_details->website }}@endif
                    @if(!empty($receipt_details->location_custom_fields))
                        <br>{{ $receipt_details->location_custom_fields }}
                    @endif
                    @if(!empty($receipt_details->sub_heading_line1))<br>{{ $receipt_details->sub_heading_line1 }}@endif
                    @if(!empty($receipt_details->sub_heading_line2))<br>{{ $receipt_details->sub_heading_line2 }}@endif
                    @if(!empty($receipt_details->sub_heading_line3))<br>{{ $receipt_details->sub_heading_line3 }}@endif
                    @if(!empty($receipt_details->sub_heading_line4))<br>{{ $receipt_details->sub_heading_line4 }}@endif
                    @if(!empty($receipt_details->sub_heading_line5))<br>{{ $receipt_details->sub_heading_line5 }}@endif
                    @if(!empty($receipt_details->tax_info1))
                        <br><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                    @endif
                    @if(!empty($receipt_details->tax_info2))
                        <br><b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
                    @endif
                </div>
                @endif
            </div>
        @else
            <div class="receipt-section">
                <img class="letterhead" src="{{ $receipt_details->letter_head }}" alt="">
            </div>
        @endif

        @if(!empty($receipt_details->invoice_heading))
            <div class="receipt-title">{!! $receipt_details->invoice_heading !!}</div>
        @endif

        <div class="sep"></div>

        <div class="info-row">
            <span class="info-label"><strong>{!! $receipt_details->invoice_no_prefix !!}</strong></span>
            <span class="info-value">{{ $receipt_details->invoice_no }}</span>
        </div>
        @if(!empty($receipt_details->date_label) || $invoice_date_display !== '')
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->date_label !!}</strong></span>
                <span class="info-value">{{ $invoice_date_display }}</span>
            </div>
        @endif
        @if($invoice_time_display !== '')
            <div class="info-row">
                <span class="info-label"><strong>Time</strong></span>
                <span class="info-value">{{ $invoice_time_display }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->due_date_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->due_date_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->due_date ?? '' }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sales_person_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->sales_person_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->sales_person }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->commission_agent_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->commission_agent_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->commission_agent }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->brand_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_brand }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->device_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_device }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->model_no_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_model_no }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->serial_no_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_serial_no }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->repair_status_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_status }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->repair_warranty_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->repair_warranty }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->service_staff_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->service_staff }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->table_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->table }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sell_custom_field_1_value))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->sell_custom_field_1_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->sell_custom_field_1_value }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sell_custom_field_2_value))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->sell_custom_field_2_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->sell_custom_field_2_value }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sell_custom_field_3_value))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->sell_custom_field_3_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->sell_custom_field_3_value }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sell_custom_field_4_value))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->sell_custom_field_4_label !!}</strong></span>
                <span class="info-value">{{ $receipt_details->sell_custom_field_4_value }}</span>
            </div>
        @endif

        @if(!empty($receipt_details->customer_label) || !empty($receipt_details->customer_info) || !empty($receipt_details->customer_name))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->customer_label ?? '' }}</strong></span>
                @if(empty($receipt_details->customer_info) && !empty($receipt_details->customer_name))
                    <span class="info-value" style="white-space: normal; overflow-wrap: anywhere;">{{ $receipt_details->customer_name }}</span>
                @endif
            </div>
            @if(!empty($receipt_details->customer_info))
                <div class="customer-block">{!! $receipt_details->customer_info !!}</div>
            @endif
        @endif
        @if(!empty($receipt_details->client_id_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->client_id_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->client_id }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->customer_tax_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->customer_tax_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->customer_tax_number }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->customer_custom_fields))
            <div class="customer-block">{!! $receipt_details->customer_custom_fields !!}</div>
        @endif
        @if(!empty($receipt_details->customer_rp_label))
            <div class="info-row">
                <span class="info-label"><strong>{{ $receipt_details->customer_rp_label }}</strong></span>
                <span class="info-value">{{ $receipt_details->customer_total_rp }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->shipping_custom_field_1_label))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->shipping_custom_field_1_label !!}</strong></span>
                <span class="info-value">{!! $receipt_details->shipping_custom_field_1_value ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->shipping_custom_field_2_label))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->shipping_custom_field_2_label !!}</strong></span>
                <span class="info-value">{!! $receipt_details->shipping_custom_field_2_value ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->shipping_custom_field_3_label))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->shipping_custom_field_3_label !!}</strong></span>
                <span class="info-value">{!! $receipt_details->shipping_custom_field_3_value ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->shipping_custom_field_4_label))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->shipping_custom_field_4_label !!}</strong></span>
                <span class="info-value">{!! $receipt_details->shipping_custom_field_4_value ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->shipping_custom_field_5_label))
            <div class="info-row">
                <span class="info-label"><strong>{!! $receipt_details->shipping_custom_field_5_label !!}</strong></span>
                <span class="info-value">{!! $receipt_details->shipping_custom_field_5_value ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sale_orders_invoice_no))
            <div class="info-row">
                <span class="info-label"><strong>@lang('restaurant.order_no')</strong></span>
                <span class="info-value">{!! $receipt_details->sale_orders_invoice_no ?? '' !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sale_orders_invoice_date))
            <div class="info-row">
                <span class="info-label"><strong>@lang('lang_v1.order_dates')</strong></span>
                <span class="info-value">{!! $receipt_details->sale_orders_invoice_date ?? '' !!}</span>
            </div>
        @endif

        <div class="sep"></div>

        <table class="receipt-table {{ $table_cols }}">
            <thead>
                <tr>
                    <th class="col-no">#</th>
                    <th class="col-item">{{ $receipt_details->table_product_label }}</th>
                    <th class="col-qty">{{ $receipt_details->table_qty_label }}</th>
                    @if(empty($receipt_details->hide_price))
                        <th class="col-price">{{ $receipt_details->table_unit_price_label }}</th>
                        @if(!empty($receipt_details->discounted_unit_price_label))
                            <th class="col-extra">{{ $receipt_details->discounted_unit_price_label }}</th>
                        @endif
                        @if(!empty($receipt_details->item_discount_label))
                            <th class="col-extra">{{ $receipt_details->item_discount_label }}</th>
                        @endif
                        <th class="col-amt">{{ $receipt_details->table_subtotal_label }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($receipt_details->lines as $line)
                    <tr>
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td class="col-item">
                            {{ $line['name'] }} {{ $line['product_variation'] }} {{ $line['variation'] }}
                            @if(!empty($line['sub_sku'])), {{ $line['sub_sku'] }} @endif
                            @if(!empty($line['brand'])), {{ $line['brand'] }} @endif
                            @if(!empty($line['cat_code'])), {{ $line['cat_code'] }}@endif
                            @if(!empty($line['product_custom_fields'])), {{ $line['product_custom_fields'] }} @endif
                            @if(!empty($line['product_description']))
                                <div class="item-meta">{!! $line['product_description'] !!}</div>
                            @endif
                            @if(!empty($line['sell_line_note']))
                                <div class="item-meta">{!! $line['sell_line_note'] !!}</div>
                            @endif
                            @if(!empty($line['lot_number']))
                                <div class="item-meta">{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}</div>
                            @endif
                            @if(!empty($line['product_expiry']))
                                <div class="item-meta">{{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}</div>
                            @endif
                            @if(!empty($line['warranty_name']))
                                <div class="item-meta">{{ $line['warranty_name'] }}
                                    @if(!empty($line['warranty_exp_date'])) - {{ @format_date($line['warranty_exp_date']) }} @endif
                                    @if(!empty($line['warranty_description'])) {{ $line['warranty_description'] ?? '' }} @endif
                                </div>
                            @endif
                            @if(empty($receipt_details->hide_price))
                                <div class="item-meta unit-price-hint">{{ $line['unit_price_before_discount'] }}</div>
                            @endif
                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                <div class="item-meta">1 {{ $line['units'] }} = {{ $line['base_unit_multiplier'] }} {{ $line['base_unit_name'] }}</div>
                                <div class="item-meta">{{ $line['base_unit_price'] }} x {{ $line['orig_quantity'] }} = {{ $line['line_total'] }}</div>
                            @endif
                        </td>
                        <td class="col-qty">
                            <span class="qty-num">{{ $line['quantity'] }}</span>@if(!empty($line['units'])) <span class="qty-unit">{{ $line['units'] }}</span>@endif
                        </td>
                        @if(empty($receipt_details->hide_price))
                            <td class="col-price money">{{ $line['unit_price_before_discount'] }}</td>
                            @if(!empty($receipt_details->discounted_unit_price_label))
                                <td class="col-extra money">{{ $line['unit_price_inc_tax'] }}</td>
                            @endif
                            @if(!empty($receipt_details->item_discount_label))
                                <td class="col-extra money">{{ $line['total_line_discount'] ?? '0.00' }}@if(!empty($line['line_discount_percent'])) ({{ $line['line_discount_percent'] }}%)@endif</td>
                            @endif
                            <td class="col-amt money">{{ $line['line_total'] }}</td>
                        @endif
                    </tr>
                    @if(!empty($line['modifiers']))
                        @foreach($line['modifiers'] as $modifier)
                            <tr>
                                <td class="col-no"></td>
                                <td class="col-item">
                                    {{ $modifier['name'] }} {{ $modifier['variation'] }}
                                    @if(!empty($modifier['sub_sku'])), {{ $modifier['sub_sku'] }} @endif
                                    @if(!empty($modifier['cat_code'])), {{ $modifier['cat_code'] }}@endif
                                    @if(!empty($modifier['sell_line_note'])) ({!! $modifier['sell_line_note'] !!}) @endif
                                </td>
                                <td class="col-qty">
                                    <span class="qty-num">{{ $modifier['quantity'] }}</span>@if(!empty($modifier['units'])) <span class="qty-unit">{{ $modifier['units'] }}</span>@endif
                                </td>
                                @if(empty($receipt_details->hide_price))
                                    <td class="col-price money">{{ $modifier['unit_price_inc_tax'] }}</td>
                                    @if(!empty($receipt_details->discounted_unit_price_label))
                                        <td class="col-extra money">{{ $modifier['unit_price_exc_tax'] }}</td>
                                    @endif
                                    @if(!empty($receipt_details->item_discount_label))
                                        <td class="col-extra money">0.00</td>
                                    @endif
                                    <td class="col-amt money">{{ $modifier['line_total'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @empty
                @endforelse
            </tbody>
        </table>

        @if(!empty($receipt_details->total_quantity_label))
            <div class="total-row">
                <span class="total-label">{!! $receipt_details->total_quantity_label !!}</span>
                <span class="total-value">{{ $receipt_details->total_quantity }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->total_items_label))
            <div class="total-row">
                <span class="total-label">{!! $receipt_details->total_items_label !!}</span>
                <span class="total-value">{{ $receipt_details->total_items }}</span>
            </div>
        @endif

        @if(empty($receipt_details->hide_price))
            <div class="sep"></div>
            <div class="total-row">
                <span class="total-label">{!! $receipt_details->subtotal_label !!}</span>
                <span class="total-value money">{{ $receipt_details->subtotal }}</span>
            </div>
            @if(!empty($receipt_details->shipping_charges))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->shipping_charges_label !!}</span>
                    <span class="total-value money">{{ $receipt_details->shipping_charges }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->packing_charge))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->packing_charge_label !!}</span>
                    <span class="total-value money">{{ $receipt_details->packing_charge }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->discount))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->discount_label !!}</span>
                    <span class="total-value money">(-) {{ $receipt_details->discount }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->total_line_discount))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->line_discount_label !!}</span>
                    <span class="total-value money">(-) {{ $receipt_details->total_line_discount }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->additional_expenses))
                @foreach($receipt_details->additional_expenses as $key => $val)
                    <div class="total-row">
                        <span class="total-label">{{ $key }}:</span>
                        <span class="total-value money">(+) {{ $val }}</span>
                    </div>
                @endforeach
            @endif
            @if(!empty($receipt_details->reward_point_label))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->reward_point_label !!}</span>
                    <span class="total-value money">(-) {{ $receipt_details->reward_point_amount }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->tax))
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->tax_label !!}</span>
                    <span class="total-value money">(+) {{ $receipt_details->tax }}</span>
                </div>
            @endif
            @if($receipt_details->round_off_amount > 0)
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->round_off_label !!}</span>
                    <span class="total-value money">{{ $receipt_details->round_off }}</span>
                </div>
            @endif

            <div class="sep"></div>
            <div class="total-row em total">
                <span class="total-label">{!! $receipt_details->total_label !!}</span>
                <span class="total-value money">{{ $receipt_details->total }}</span>
            </div>
            @if(!empty($receipt_details->total_in_words))
                <div class="customer-block">({{ $receipt_details->total_in_words }})</div>
            @endif

            @if(!empty($receipt_details->payments))
                @foreach($receipt_details->payments as $payment)
                    <div class="payment-row">
                        <span class="payment-label">{{ $payment['method'] }}</span>
                        <span class="payment-value money">{{ $payment['amount'] }}</span>
                    </div>
                @endforeach
            @endif

            @if(!empty($receipt_details->total_paid))
                <div class="payment-row paid-amount">
                    <span class="payment-label">{!! $receipt_details->total_paid_label !!}</span>
                    <span class="payment-value money">{{ $receipt_details->total_paid }}</span>
                </div>
            @endif
            @if($show_change && !empty($receipt_details->change_amount))
                <div class="payment-row change">
                    <span class="payment-label">{{ __('lang_v1.change_return') }}</span>
                    <span class="payment-value money">{{ $receipt_details->change_amount }}</span>
                </div>
            @endif
            @if($show_due)
                <div class="payment-row balance-due">
                    <span class="payment-label">{!! $receipt_details->total_due_label !!}</span>
                    <span class="payment-value money">{{ $receipt_details->total_due }}</span>
                </div>
            @endif
            @if(!empty($receipt_details->all_due))
                <div class="payment-row">
                    <span class="payment-label">{!! $receipt_details->all_bal_label !!}</span>
                    <span class="payment-value money">{{ $receipt_details->all_due }}</span>
                </div>
            @endif
        @endif

        @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) && !empty($receipt_details->taxes))
            <div class="sep"></div>
            <div class="centered"><strong>{{ $receipt_details->tax_summary_label }}</strong></div>
            @foreach($receipt_details->taxes as $key => $val)
                <div class="total-row">
                    <span class="total-label">{{ $key }}</span>
                    <span class="total-value money">{{ $val }}</span>
                </div>
            @endforeach
        @endif

        @if(!empty($receipt_details->additional_notes))
            <div class="customer-block receipt-section">{!! nl2br($receipt_details->additional_notes) !!}</div>
        @endif

        @if($receipt_details->show_barcode)
            <img class="barcode" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, array(39, 48, 54), true) }}" alt="">
        @endif
        @if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
            <img class="barcode" src="data:image/png;base64,{{ DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE') }}" alt="">
        @endif

        @if(!empty($receipt_details->footer_text))
            <div class="sep"></div>
            <div class="footer">{!! $receipt_details->footer_text !!}</div>
        @endif
    </div>
</div>
