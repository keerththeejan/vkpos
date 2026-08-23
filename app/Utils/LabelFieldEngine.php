<?php

namespace App\Utils;

class LabelFieldEngine
{
    /**
     * Resolve visible label fields for manufacturing / custom layouts.
     *
     * @return array<int, array{key: string, label: string, value: string, type: string}>
     */
    public static function visibleFields(object $product, array $print, string $businessName, array $meta = []): array
    {
        $resolved = [];

        foreach (config('label_fields', []) as $key => $definition) {
            if (! self::isEnabled($print, $definition)) {
                continue;
            }

            $value = self::resolveValue($key, $product, $print, $businessName, $meta);
            $type = in_array($key, ['barcode', 'qr_code', 'company_logo'], true) ? $key : 'text';

            if ($type === 'text' && trim((string) $value) === '') {
                continue;
            }

            $resolved[] = [
                'key' => $key,
                'label' => $definition['label'],
                'value' => $value,
                'type' => $type,
            ];
        }

        return $resolved;
    }

    public static function isEnabled(array $print, array $definition): bool
    {
        $printKey = $definition['print_key'];

        if (! empty($definition['legacy'])) {
            return ! empty($print[$printKey]);
        }

        if (! empty($definition['default'])) {
            return ! isset($print[$printKey]) || ! empty($print[$printKey]);
        }

        return ! empty($print[$printKey]);
    }

    public static function resolveValue(
        string $key,
        object $product,
        array $print,
        string $businessName,
        array $meta = []
    ): string {
        $currency = session('currency')['symbol'] ?? '';

        return match ($key) {
            'product_name' => (string) ($product->product_actual_name ?? ''),
            'variation' => ($product->is_dummy ?? 1) != 1
                ? trim(($product->product_variation_name ?? '').': '.($product->variation_name ?? ''), ': ')
                : '',
            'selling_price' => $currency.self::formatPrice($product, $print),
            'mrp' => $currency.self::formatPrice($product, $print, true),
            'business_name' => $businessName,
            'branch_name' => (string) (session('business.name') ?? $businessName),
            'packing_date' => (string) ($product->packing_date ?? ''),
            'manufacturing_date' => (string) ($product->packing_date ?? ''),
            'expiry_date', 'best_before' => (string) ($product->exp_date ?? ''),
            'lot_number' => (string) ($product->lot_number ?? ''),
            'batch_number' => (string) ($product->lot_number ?? $meta['batch_number'] ?? ''),
            'serial_number' => (string) ($meta['serial_number'] ?? $product->product_custom_field3 ?? ''),
            'model_number' => (string) ($product->product_custom_field4 ?? ''),
            'sku' => (string) ($product->sub_sku ?? ''),
            'product_code' => (string) ($product->product_id ?? ''),
            'item_code' => (string) ($product->sub_sku ?? ''),
            'barcode_number' => (string) ($product->sub_sku ?? ''),
            'barcode', 'qr_code' => (string) ($product->sub_sku ?? ''),
            'net_weight' => (string) ($product->product_custom_field1 ?? ''),
            'gross_weight' => (string) ($product->product_custom_field2 ?? ''),
            'unit' => (string) ($product->unit ?? ''),
            'quantity' => (string) ($meta['quantity'] ?? ''),
            'carton_number' => (string) ($meta['carton_number'] ?? ''),
            'po_number' => (string) ($meta['po_number'] ?? ''),
            'invoice_number' => (string) ($meta['invoice_number'] ?? ''),
            'country_of_origin' => (string) ($product->product_custom_field2 ?? ''),
            'custom_field_1' => (string) ($product->product_custom_field1 ?? ''),
            'custom_field_2' => (string) ($product->product_custom_field2 ?? ''),
            'custom_field_3' => (string) ($product->product_custom_field3 ?? ''),
            'company_logo' => 'logo',
            default => '',
        };
    }

    public static function formatPrice(object $product, array $print, bool $useIncTax = false): string
    {
        $useIncTax = $useIncTax || (($print['price_type'] ?? 'inclusive') === 'inclusive');

        $amount = $useIncTax
            ? ($product->sell_price_inc_tax ?? 0)
            : ($product->default_sell_price ?? 0);

        return number_format((float) $amount, 2, '.', '');
    }

    public static function fieldsByKey(array $fields): array
    {
        $map = [];
        foreach ($fields as $field) {
            $map[$field['key']] = $field;
        }

        return $map;
    }
}
