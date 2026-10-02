@php
    $paper_profile = isset($paper_profile) ? (string) $paper_profile : '80';
    if ($paper_profile !== '58') {
        $paper_profile = '80';
    }

    $hide_price = !empty($receipt_details->hide_price);
    $show_disc_price = !$hide_price && !empty($receipt_details->discounted_unit_price_label);
    $show_item_disc = !$hide_price && !empty($receipt_details->item_discount_label);

    $item_w = 42;
    $qty_w = 10;
    $unit_w = 13;
    $price_w = 17;
    $extra_w = 0;
    $amt_w = 18;
    if ($paper_profile === '58') {
        $item_w = 36;
        $qty_w = 12;
        $unit_w = 18;
        $price_w = 17;
        $amt_w = 17;
    }
    $extra_count = ($show_disc_price ? 1 : 0) + ($show_item_disc ? 1 : 0);
    if ($hide_price) {
        $item_w = 70;
        $qty_w = 14;
        $unit_w = 16;
        $price_w = 0;
        $amt_w = 0;
    } elseif ($extra_count === 1) {
        $item_w = 34;
        $qty_w = 10;
        $unit_w = 12;
        $price_w = 14;
        $extra_w = 15;
        $amt_w = 15;
    } elseif ($extra_count >= 2) {
        $item_w = 28;
        $qty_w = 9;
        $unit_w = 11;
        $price_w = 13;
        $extra_w = 13;
        $amt_w = 13;
    }

    $formatQty = function ($formatted, $uf = null) {
        if ($uf !== null && is_numeric($uf)) {
            $number = (float) $uf;
            if (abs($number - round($number)) < 0.0000001) {
                return (string) (int) round($number);
            }

            return $formatted;
        }

        $plain = trim(strip_tags((string) $formatted));
        if (preg_match('/^(-?\d+)[.,]0+$/', $plain, $match)) {
            return $match[1];
        }

        return $formatted;
    };

    $receiptProductName = function ($line) {
        if (!is_array($line)) {
            return '';
        }
        foreach (['receipt_name', 'short_name', 'display_name', 'name'] as $key) {
            if (!empty($line[$key])) {
                return trim((string) $line[$key]);
            }
        }

        return '';
    };

    $skuLines = function ($sku) {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $sku) ?: [];

        return array_values(array_filter($parts, function ($part) {
            return $part !== '';
        }));
    };

    $address_lines = [];
    if (!empty($receipt_details->address)) {
        $plain = trim(html_entity_decode(strip_tags(str_replace(
            ['<br>', '<br/>', '<br />'],
            ', ',
            $receipt_details->address
        ))));
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));
        $parts = array_values(array_filter(array_map('trim', explode(',', $plain)), function ($part) {
            return $part !== '';
        }));
        if (count($parts) >= 4) {
            $tail = array_splice($parts, -2);
            $address_lines[] = implode(', ', $parts);
            $address_lines[] = implode(', ', $tail);
        } elseif (!empty($parts)) {
            $address_lines[] = implode(', ', $parts);
        }
    }

    $customer_name = trim((string) ($receipt_details->customer_name ?? ''));
    $customer_mobile = trim((string) ($receipt_details->customer_mobile ?? ''));
    $extra_customer = '';
    if (!empty($receipt_details->customer_info)) {
        $extra_customer = (string) $receipt_details->customer_info;
        if ($customer_name !== '') {
            $extra_customer = (string) preg_replace(
                '/^\s*'.preg_quote($customer_name, '/').'(\s*<br\s*\/?>)?/iu',
                '',
                $extra_customer,
                1
            );
        }
        $extra_customer = trim((string) preg_replace(
            '/(\s*<br\s*\/?>)?\s*<b>\s*[^<]*<\/b>\s*:\s*[^<]*\s*$/iu',
            '',
            $extra_customer
        ));
    }

    $show_customer = $customer_name !== ''
        || $customer_mobile !== ''
        || $extra_customer !== ''
        || !empty($receipt_details->customer_label);

    $change_uf = (float) ($receipt_details->change_amount_uf ?? 0);
    $show_change = $change_uf > 0.00001;
    $show_due = !$show_change && !empty($receipt_details->total_due) && !empty($receipt_details->total_due_label);
@endphp
<meta charset="utf-8">
<style type="text/css">
@include('sale_pos.receipts.partial.premium_thermal_css')
@page {
    size: {{ $paper_profile }}mm auto;
    margin: 0;
}
</style>
<div class="receipt-container vkpos-thermal paper-{{ $paper_profile }}">
    <div class="receipt">
        @if(empty($receipt_details->letter_head))
            @if(!empty($receipt_details->logo))
                <div class="centered">
                    <img class="logo" src="{{ $receipt_details->logo }}" alt="">
                </div>
            @endif
            <div class="header">
                @if(!empty($receipt_details->header_text))
                    <div class="business-name shop-name">{!! $receipt_details->header_text !!}</div>
                @endif
                @if(!empty($receipt_details->display_name))
                    <div class="business-name shop-name">{{ $receipt_details->display_name }}</div>
                @endif
                <div class="shop-meta">
                    @foreach($address_lines as $address_line)
                        <div class="shop-line">{{ $address_line }}</div>
                    @endforeach
                    @if(!empty($receipt_details->contact))
                        <div class="shop-line">{!! $receipt_details->contact !!}</div>
                    @endif
                    @if(!empty($receipt_details->website))
                        <div class="shop-line">{{ $receipt_details->website }}</div>
                    @endif
                    @if(!empty($receipt_details->location_custom_fields))
                        <div class="shop-line">{{ $receipt_details->location_custom_fields }}</div>
                    @endif
                    @if(!empty($receipt_details->sub_heading_line1))
                        <div class="shop-line">{{ $receipt_details->sub_heading_line1 }}</div>
                    @endif
                    @if(!empty($receipt_details->sub_heading_line2))
                        <div class="shop-line">{{ $receipt_details->sub_heading_line2 }}</div>
                    @endif
                    @if(!empty($receipt_details->sub_heading_line3))
                        <div class="shop-line">{{ $receipt_details->sub_heading_line3 }}</div>
                    @endif
                    @if(!empty($receipt_details->sub_heading_line4))
                        <div class="shop-line">{{ $receipt_details->sub_heading_line4 }}</div>
                    @endif
                    @if(!empty($receipt_details->sub_heading_line5))
                        <div class="shop-line">{{ $receipt_details->sub_heading_line5 }}</div>
                    @endif
                    @if(!empty($receipt_details->tax_info1))
                        <div class="shop-line"><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}</div>
                    @endif
                    @if(!empty($receipt_details->tax_info2))
                        <div class="shop-line"><b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}</div>
                    @endif
                </div>
            </div>
        @else
            <div>
                <img class="letterhead" src="{{ $receipt_details->letter_head }}" alt="">
            </div>
        @endif

        @if(!empty($receipt_details->invoice_heading))
            <div class="receipt-title">{!! $receipt_details->invoice_heading !!}</div>
        @endif

        <div class="sep"></div>

        <div class="info-row">
            <span class="info-label">{!! $receipt_details->invoice_no_prefix !!}</span>
            <span class="info-value">{{ $receipt_details->invoice_no }}</span>
        </div>

        @if($show_customer)
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->customer_label ?? '' }}</span>
                <span class="info-value">{{ $customer_name !== '' ? $customer_name : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">@lang('contact.mobile')</span>
                <span class="info-value">{{ $customer_mobile !== '' ? $customer_mobile : '-' }}</span>
            </div>
            @if($extra_customer !== '')
                <div class="customer-block">{!! $extra_customer !!}</div>
            @endif
        @endif

        @if(!empty($receipt_details->date_label) || !empty($receipt_details->invoice_date))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->date_label !!}</span>
                <span class="info-value">{{ $receipt_details->invoice_date }}</span>
            </div>
        @endif

        @if(!empty($receipt_details->due_date_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->due_date_label }}</span>
                <span class="info-value">{{ $receipt_details->due_date ?? '' }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->types_of_service))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->types_of_service_label !!}</span>
                <span class="info-value">{{ $receipt_details->types_of_service }}</span>
            </div>
            @if(!empty($receipt_details->types_of_service_custom_fields))
                @foreach($receipt_details->types_of_service_custom_fields as $key => $value)
                    <div class="info-row">
                        <span class="info-label">{{ $key }}</span>
                        <span class="info-value">{{ $value }}</span>
                    </div>
                @endforeach
            @endif
        @endif
        @if(!empty($receipt_details->sales_person_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->sales_person_label }}</span>
                <span class="info-value">{{ $receipt_details->sales_person }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->commission_agent_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->commission_agent_label }}</span>
                <span class="info-value">{{ $receipt_details->commission_agent }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->brand_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_brand }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->device_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_device }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->model_no_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_model_no }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->serial_no_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_serial_no }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->repair_status_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_status }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->repair_warranty_label !!}</span>
                <span class="info-value">{{ $receipt_details->repair_warranty }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->service_staff_label !!}</span>
                <span class="info-value">{{ $receipt_details->service_staff }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
            <div class="info-row">
                <span class="info-label">{!! $receipt_details->table_label !!}</span>
                <span class="info-value">{{ $receipt_details->table }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->client_id_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->client_id_label }}</span>
                <span class="info-value">{{ $receipt_details->client_id }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->customer_tax_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->customer_tax_label }}</span>
                <span class="info-value">{{ $receipt_details->customer_tax_number }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->customer_custom_fields))
            <div class="customer-block">{!! $receipt_details->customer_custom_fields !!}</div>
        @endif
        @if(!empty($receipt_details->customer_rp_label))
            <div class="info-row">
                <span class="info-label">{{ $receipt_details->customer_rp_label }}</span>
                <span class="info-value">{{ $receipt_details->customer_total_rp }}</span>
            </div>
        @endif
        @foreach([1, 2, 3, 4] as $sell_field)
            @php $sell_value_key = 'sell_custom_field_'.$sell_field.'_value'; @endphp
            @if(!empty($receipt_details->$sell_value_key))
                <div class="info-row">
                    <span class="info-label">{!! $receipt_details->{'sell_custom_field_'.$sell_field.'_label'} !!}</span>
                    <span class="info-value">{{ $receipt_details->$sell_value_key }}</span>
                </div>
            @endif
        @endforeach
        @foreach([1, 2, 3, 4, 5] as $ship_field)
            @php $ship_label_key = 'shipping_custom_field_'.$ship_field.'_label'; @endphp
            @if(!empty($receipt_details->$ship_label_key))
                <div class="info-row">
                    <span class="info-label">{!! $receipt_details->$ship_label_key !!}</span>
                    <span class="info-value">{!! $receipt_details->{'shipping_custom_field_'.$ship_field.'_value'} ?? '' !!}</span>
                </div>
            @endif
        @endforeach
        @if(!empty($receipt_details->sale_orders_invoice_no))
            <div class="info-row">
                <span class="info-label">@lang('restaurant.order_no')</span>
                <span class="info-value">{!! $receipt_details->sale_orders_invoice_no !!}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sale_orders_invoice_date))
            <div class="info-row">
                <span class="info-label">@lang('lang_v1.order_dates')</span>
                <span class="info-value">{!! $receipt_details->sale_orders_invoice_date !!}</span>
            </div>
        @endif

        <div class="sep"></div>

        <table class="receipt-table">
            <colgroup>
                <col class="receipt-col-product" style="width: {{ $item_w }}%;">
                <col class="receipt-col-qty" style="width: {{ $qty_w }}%;">
                <col class="receipt-col-unit" style="width: {{ $unit_w }}%;">
                @if(!$hide_price)
                    <col class="receipt-col-price" style="width: {{ $price_w }}%;">
                    @if($show_disc_price)
                        <col class="receipt-col-extra" style="width: {{ $extra_w }}%;">
                    @endif
                    @if($show_item_disc)
                        <col class="receipt-col-extra" style="width: {{ $extra_w }}%;">
                    @endif
                    <col class="receipt-col-total" style="width: {{ $amt_w }}%;">
                @endif
            </colgroup>
            <thead>
                <tr>
                    <th class="col-item product-cell">{{ $receipt_details->table_product_label }}</th>
                    <th class="col-qty qty-cell">Qty</th>
                    <th class="col-unit unit-cell">@lang('product.unit')</th>
                    @if(!$hide_price)
                        <th class="col-price price-cell">Price</th>
                        @if($show_disc_price)
                            <th class="col-extra">{{ $receipt_details->discounted_unit_price_label }}</th>
                        @endif
                        @if($show_item_disc)
                            <th class="col-extra">{{ $receipt_details->item_discount_label }}</th>
                        @endif
                        <th class="col-amt total-cell">Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($receipt_details->lines as $line)
                    <tr>
                        <td class="col-item product-cell">
                            @if(!empty($line['image']))
                                <img class="item-photo" src="{{ $line['image'] }}" alt="">
                            @endif
                            <span class="item-name">{{ $receiptProductName($line) }}</span>
                            @if(!empty($line['product_variation']) || !empty($line['variation']))
                                <span class="item-meta">{{ trim(($line['product_variation'] ?? '').' '.($line['variation'] ?? '')) }}</span>
                            @endif
                            @foreach($skuLines($line['sub_sku'] ?? '') as $sku_line)
                                <span class="item-meta">{{ $sku_line }}</span>
                            @endforeach
                            @if(!empty($line['brand']))
                                <span class="item-meta">{{ $line['brand'] }}</span>
                            @endif
                            @if(!empty($line['cat_code']))
                                <span class="item-meta">{{ $line['cat_code'] }}</span>
                            @endif
                            @if(!empty($line['product_custom_fields']))
                                <span class="item-meta">{{ $line['product_custom_fields'] }}</span>
                            @endif
                            @if(!empty($line['product_description']))
                                <span class="item-meta">{!! $line['product_description'] !!}</span>
                            @endif
                            @if(!empty($line['sell_line_note']))
                                <span class="item-meta">{!! $line['sell_line_note'] !!}</span>
                            @endif
                            @if(!empty($line['lot_number']))
                                <span class="item-meta">{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}</span>
                            @endif
                            @if(!empty($line['product_expiry']))
                                <span class="item-meta">{{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}</span>
                            @endif
                            @if(!empty($line['warranty_name']))
                                <span class="item-meta">{{ $line['warranty_name'] }}
                                    @if(!empty($line['warranty_exp_date'])) - {{ @format_date($line['warranty_exp_date']) }} @endif
                                    @if(!empty($line['warranty_description'])) {{ $line['warranty_description'] ?? '' }} @endif
                                </span>
                            @endif
                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                <span class="item-meta">1 {{ $line['units'] }} = {{ $line['base_unit_multiplier'] }} {{ $line['base_unit_name'] }}</span>
                                <span class="item-meta">{{ $line['base_unit_price'] }} x {{ $line['orig_quantity'] }} = {{ $line['line_total'] }}</span>
                            @endif
                        </td>
                        <td class="col-qty qty-cell">
                            <span class="qty-num">{{ $formatQty($line['quantity'] ?? '', $line['quantity_uf'] ?? null) }}</span>
                        </td>
                        <td class="col-unit unit-cell">{{ $line['units'] ?? '' }}</td>
                        @if(!$hide_price)
                            <td class="col-price price-cell money">{{ $line['unit_price_before_discount'] }}</td>
                            @if($show_disc_price)
                                <td class="col-extra money">{{ $line['unit_price_inc_tax'] }}</td>
                            @endif
                            @if($show_item_disc)
                                <td class="col-extra money">{{ $line['total_line_discount'] ?? '0.00' }}@if(!empty($line['line_discount_percent'])) ({{ $line['line_discount_percent'] }}%)@endif</td>
                            @endif
                            <td class="col-amt total-cell money">{{ $line['line_total'] }}</td>
                        @endif
                    </tr>
                    @if(!empty($line['modifiers']))
                        @foreach($line['modifiers'] as $modifier)
                            <tr>
                                <td class="col-item product-cell">
                                    <span class="item-name">{{ $receiptProductName($modifier) }}</span>
                                    @if(!empty($modifier['variation']))
                                        <span class="item-meta">{{ $modifier['variation'] }}</span>
                                    @endif
                                    @foreach($skuLines($modifier['sub_sku'] ?? '') as $sku_line)
                                        <span class="item-meta">{{ $sku_line }}</span>
                                    @endforeach
                                    @if(!empty($modifier['cat_code']))
                                        <span class="item-meta">{{ $modifier['cat_code'] }}</span>
                                    @endif
                                    @if(!empty($modifier['sell_line_note']))
                                        <span class="item-meta">{!! $modifier['sell_line_note'] !!}</span>
                                    @endif
                                </td>
                                <td class="col-qty qty-cell">
                                    <span class="qty-num">{{ $formatQty($modifier['quantity'] ?? '') }}</span>
                                </td>
                                <td class="col-unit unit-cell">{{ $modifier['units'] ?? '' }}</td>
                                @if(!$hide_price)
                                    <td class="col-price price-cell money">{{ $modifier['unit_price_inc_tax'] }}</td>
                                    @if($show_disc_price)
                                        <td class="col-extra money">{{ $modifier['unit_price_exc_tax'] }}</td>
                                    @endif
                                    @if($show_item_disc)
                                        <td class="col-extra money">0.00</td>
                                    @endif
                                    <td class="col-amt total-cell money">{{ $modifier['line_total'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @empty
                @endforelse
            </tbody>
        </table>

        @if(!empty($receipt_details->total_quantity_label) || !empty($receipt_details->total_items_label))
            <div class="sep"></div>
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
        @endif

        @if(!$hide_price)
            @php
                $change_shown = false;
            @endphp
            @if(!empty($receipt_details->payments) || !empty($receipt_details->total_paid) || $show_change || $show_due || !empty($receipt_details->all_due))
                <div class="sep"></div>
                <div class="payment-block">
                    @if(!empty($receipt_details->payments))
                        <div class="section-label">@lang('lang_v1.payment')</div>
                        @foreach($receipt_details->payments as $payment)
                            @php
                                $pay_label = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($payment['method'] ?? '')))));
                                $is_change_payment = (bool) preg_match('/\(-\)\s*$/u', $pay_label);
                                if ($is_change_payment) {
                                    $change_shown = true;
                                    if (preg_match('/\(([^)]*)\)\s*\(-\)\s*$/u', $pay_label, $change_match) && trim($change_match[1]) !== '') {
                                        $pay_label = trim($change_match[1]);
                                    } else {
                                        $pay_label = __('lang_v1.change_return');
                                    }
                                }
                            @endphp
                            <div class="payment-row">
                                <span class="payment-label">{{ $pay_label }}</span>
                                <span class="payment-value money">{{ $payment['amount'] }}</span>
                            </div>
                        @endforeach
                    @endif
                    @if(!empty($receipt_details->total_paid))
                        <div class="payment-row">
                            <span class="payment-label">{!! $receipt_details->total_paid_label !!}</span>
                            <span class="payment-value money">{{ $receipt_details->total_paid }}</span>
                        </div>
                    @endif
                    @if($show_change && !$change_shown && !empty($receipt_details->change_amount))
                        <div class="payment-row">
                            <span class="payment-label">{{ __('lang_v1.change_return') }}</span>
                            <span class="payment-value money">{{ $receipt_details->change_amount }}</span>
                        </div>
                    @endif
                    @if($show_due)
                        <div class="payment-row">
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
                </div>
            @endif

            <div class="sep"></div>
            <div class="totals-block">
                <div class="total-row">
                    <span class="total-label">{!! $receipt_details->subtotal_label !!}</span>
                    <span class="total-value money">{{ $receipt_details->subtotal }}</span>
                </div>
                @if(!empty($receipt_details->total_exempt_uf))
                    <div class="total-row">
                        <span class="total-label">@lang('lang_v1.exempt')</span>
                        <span class="total-value money">{{ $receipt_details->total_exempt }}</span>
                    </div>
                @endif
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
                <div class="total-row grand">
                    <span class="total-label">{!! $receipt_details->total_label !!}</span>
                    <span class="total-value money">{{ $receipt_details->total }}</span>
                </div>
                @if(!empty($receipt_details->total_in_words))
                    <div class="words">({{ $receipt_details->total_in_words }})</div>
                @endif
            </div>

            @if(!empty($receipt_details->tax_summary_label) && !empty($receipt_details->taxes))
                <div class="sep"></div>
                <div class="section-label">{{ $receipt_details->tax_summary_label }}</div>
                @foreach($receipt_details->taxes as $key => $val)
                    <div class="total-row">
                        <span class="total-label">{{ $key }}</span>
                        <span class="total-value money">{{ $val }}</span>
                    </div>
                @endforeach
            @endif
        @endif

        @if(!empty($receipt_details->additional_notes))
            <div class="notes">{!! nl2br($receipt_details->additional_notes) !!}</div>
        @endif

        @if($receipt_details->show_barcode)
            <img class="barcode" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}" alt="">
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
