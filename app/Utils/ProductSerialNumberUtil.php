<?php

namespace App\Utils;

use App\Product;
use App\ProductSerialNumber;
use App\PurchaseLine;
use App\Transaction;
use Illuminate\Support\Facades\DB;

class ProductSerialNumberUtil
{
    /**
     * Resolve serial list from request (JSON preferred for large batches).
     */
    public function serialsFromRequest($request)
    {
        $json = $request->input('product_serial_numbers_json');
        if (! empty($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->input('product_serial_numbers', []);
    }

    /**
     * Normalize raw serial inputs into unique trimmed values.
     */
    public function normalizeSerials($serials = [])
    {
        if (! is_array($serials)) {
            $serials = [];
        }

        $clean = [];
        foreach ($serials as $serial) {
            if (is_array($serial)) {
                $serial = $serial['serial_number'] ?? $serial['value'] ?? '';
            }
            $value = trim((string) $serial);
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > 100) {
                $value = mb_substr($value, 0, 100);
            }
            $clean[] = $value;
        }

        // Preserve order, remove duplicates (case-insensitive)
        $unique = [];
        $seen = [];
        foreach ($clean as $value) {
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $value;
        }

        return $unique;
    }

    /**
     * Sync serial numbers for a product (product master).
     */
    public function syncProductSerials($product_id, $business_id, $serials = [])
    {
        $serials = $this->normalizeSerials($serials);

        return DB::transaction(function () use ($product_id, $business_id, $serials) {
            $existing = ProductSerialNumber::where('product_id', $product_id)->get();

            $existing_map = [];
            foreach ($existing as $row) {
                $existing_map[mb_strtolower($row->serial_number)] = $row;
            }

            $incoming_keys = [];
            foreach ($serials as $serial) {
                $incoming_keys[mb_strtolower($serial)] = $serial;
            }

            foreach ($existing as $row) {
                $key = mb_strtolower($row->serial_number);
                if (! isset($incoming_keys[$key]) && $row->status === 'available' && empty($row->purchase_id)) {
                    $row->delete();
                }
            }

            $now = now();
            $to_insert = [];
            foreach ($incoming_keys as $key => $serial) {
                if (isset($existing_map[$key])) {
                    continue;
                }
                $to_insert[] = [
                    'product_id' => $product_id,
                    'business_id' => $business_id,
                    'serial_number' => $serial,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($to_insert)) {
                foreach (array_chunk($to_insert, 500) as $chunk) {
                    ProductSerialNumber::insert($chunk);
                }
            }

            return ProductSerialNumber::where('product_id', $product_id)->count();
        });
    }

    /**
     * Validate purchase IMEI payload before save.
     * Returns array of error messages (empty = ok).
     */
    public function validatePurchaseSerials($purchases, $business_id, $exclude_purchase_id = null)
    {
        $errors = [];
        if (empty($purchases) || ! is_array($purchases)) {
            return $errors;
        }

        $all_in_request = [];

        foreach ($purchases as $idx => $data) {
            $product_id = $data['product_id'] ?? null;
            if (empty($product_id)) {
                continue;
            }

            $product = Product::find($product_id);
            if (empty($product) || empty($product->enable_sr_no)) {
                continue;
            }

            $qty = (int) round((float) ($data['quantity'] ?? 0));
            $serials_raw = $data['serial_numbers'] ?? [];
            if (! is_array($serials_raw)) {
                $serials_raw = [];
            }

            // Keep empties for count check
            $filled = [];
            foreach ($serials_raw as $s) {
                $v = trim((string) (is_array($s) ? ($s['serial_number'] ?? '') : $s));
                if ($v !== '') {
                    $filled[] = $v;
                }
            }

            $normalized = $this->normalizeSerials($filled);

            if ($qty < 1) {
                $errors[] = __('Invalid quantity for IMEI product: :name', ['name' => $product->name]);
                continue;
            }

            if (count($filled) !== $qty) {
                $errors[] = __(':name requires exactly :qty IMEI/Serial numbers (entered: :entered).', [
                    'name' => $product->name,
                    'qty' => $qty,
                    'entered' => count($filled),
                ]);
            }

            if (count($filled) !== count($normalized)) {
                $errors[] = __('Duplicate IMEI/Serial numbers found for :name in this purchase.', [
                    'name' => $product->name,
                ]);
            }

            foreach ($normalized as $serial) {
                $key = mb_strtolower($serial);
                if (isset($all_in_request[$key])) {
                    $errors[] = __('IMEI/Serial :serial is duplicated across products in this purchase.', [
                        'serial' => $serial,
                    ]);
                }
                $all_in_request[$key] = true;

                $query = ProductSerialNumber::where('business_id', $business_id)
                    ->whereRaw('LOWER(serial_number) = ?', [$key]);

                if (! empty($exclude_purchase_id)) {
                    $query->where(function ($q) use ($exclude_purchase_id) {
                        $q->whereNull('purchase_id')
                            ->orWhere('purchase_id', '!=', $exclude_purchase_id);
                    });
                }

                // Allow editing same purchase line serials: exclude current purchase's available/reserved
                if (! empty($exclude_purchase_id)) {
                    $exists_other = (clone $query)->exists();
                    if ($exists_other) {
                        $errors[] = __('IMEI/Serial :serial already exists in stock.', ['serial' => $serial]);
                    }
                } else {
                    if ($query->exists()) {
                        $errors[] = __('IMEI/Serial :serial already exists in stock.', ['serial' => $serial]);
                    }
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Live-check a single serial (AJAX).
     */
    public function checkSerialAvailability($serial, $business_id, $exclude_purchase_id = null, $product_id = null)
    {
        $serial = trim((string) $serial);
        if ($serial === '') {
            return ['ok' => false, 'status' => 'empty', 'message' => 'Empty'];
        }

        $key = mb_strtolower($serial);
        $query = ProductSerialNumber::where('business_id', $business_id)
            ->whereRaw('LOWER(serial_number) = ?', [$key]);

        if (! empty($exclude_purchase_id)) {
            $query->where(function ($q) use ($exclude_purchase_id) {
                $q->whereNull('purchase_id')
                    ->orWhere('purchase_id', '!=', $exclude_purchase_id);
            });
        }

        $existing = $query->first();
        if ($existing) {
            return [
                'ok' => false,
                'status' => 'exists',
                'message' => 'Already Exists',
                'current_status' => $existing->status,
            ];
        }

        return ['ok' => true, 'status' => 'valid', 'message' => 'Valid'];
    }

    /**
     * Sync IMEIs from purchase form after purchase lines are saved.
     */
    public function syncPurchaseSerials(Transaction $transaction, array $purchases)
    {
        if ($transaction->type !== 'purchase') {
            return;
        }

        $business_id = $transaction->business_id;
        $location_id = $transaction->location_id;
        $status = $transaction->status === 'received' ? 'available' : 'reserved';
        $matched_line_ids = [];

        $transaction->load('purchase_lines');

        foreach ($purchases as $data) {
            $product_id = $data['product_id'] ?? null;
            $variation_id = $data['variation_id'] ?? null;
            if (empty($product_id) || empty($variation_id)) {
                continue;
            }

            $product = Product::find($product_id);
            if (empty($product) || empty($product->enable_sr_no)) {
                // If product no longer tracks serials, clear available/reserved for this line if known
                if (! empty($data['purchase_line_id'])) {
                    $this->deleteAvailableForPurchaseLine($data['purchase_line_id']);
                }
                continue;
            }

            $line = null;
            if (! empty($data['purchase_line_id'])) {
                $line = $transaction->purchase_lines->firstWhere('id', (int) $data['purchase_line_id']);
            }

            if (empty($line)) {
                $line = $transaction->purchase_lines
                    ->where('product_id', (int) $product_id)
                    ->where('variation_id', (int) $variation_id)
                    ->filter(function ($pl) use ($matched_line_ids) {
                        return ! in_array($pl->id, $matched_line_ids, true);
                    })
                    ->sortBy('id')
                    ->first();
            }

            if (empty($line)) {
                continue;
            }

            $matched_line_ids[] = $line->id;
            $serials = $this->normalizeSerials($data['serial_numbers'] ?? []);

            $this->syncPurchaseLineSerials(
                $transaction->id,
                $line->id,
                $product_id,
                $variation_id,
                $business_id,
                $location_id,
                $serials,
                $status
            );
        }

        // Delete serials for removed purchase lines (available/reserved only)
        $keep_ids = $transaction->purchase_lines->pluck('id')->all();
        ProductSerialNumber::where('purchase_id', $transaction->id)
            ->whereNotIn('purchase_line_id', $keep_ids ?: [0])
            ->whereIn('status', ['available', 'reserved'])
            ->delete();
    }

    /**
     * Sync serials for one purchase line.
     */
    public function syncPurchaseLineSerials(
        $purchase_id,
        $purchase_line_id,
        $product_id,
        $variation_id,
        $business_id,
        $location_id,
        array $serials,
        $status = 'available'
    ) {
        $existing = ProductSerialNumber::where('purchase_line_id', $purchase_line_id)->get();
        $existing_map = [];
        foreach ($existing as $row) {
            $existing_map[mb_strtolower($row->serial_number)] = $row;
        }

        $incoming = [];
        foreach ($serials as $serial) {
            $incoming[mb_strtolower($serial)] = $serial;
        }

        // Remove available/reserved serials no longer in the list
        foreach ($existing as $row) {
            $key = mb_strtolower($row->serial_number);
            if (! isset($incoming[$key]) && in_array($row->status, ['available', 'reserved'], true)) {
                $row->delete();
            }
        }

        $now = now();
        $to_insert = [];
        foreach ($incoming as $key => $serial) {
            if (isset($existing_map[$key])) {
                $row = $existing_map[$key];
                // Update metadata / status if still unsold
                if (in_array($row->status, ['available', 'reserved'], true)) {
                    $row->status = $status;
                    $row->variation_id = $variation_id;
                    $row->location_id = $location_id;
                    $row->purchase_id = $purchase_id;
                    $row->business_id = $business_id;
                    $row->save();
                }
                continue;
            }

            $to_insert[] = [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'business_id' => $business_id,
                'location_id' => $location_id,
                'purchase_id' => $purchase_id,
                'purchase_line_id' => $purchase_line_id,
                'serial_number' => $serial,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($to_insert)) {
            foreach (array_chunk($to_insert, 500) as $chunk) {
                ProductSerialNumber::insert($chunk);
            }
        }
    }

    public function deleteAvailableForPurchaseLine($purchase_line_id)
    {
        ProductSerialNumber::where('purchase_line_id', $purchase_line_id)
            ->whereIn('status', ['available', 'reserved'])
            ->delete();
    }

    /**
     * On purchase delete — remove unsold serials; block if sold exist.
     */
    public function handlePurchaseDeleted(Transaction $transaction)
    {
        $sold = ProductSerialNumber::where('purchase_id', $transaction->id)
            ->where('status', 'sold')
            ->count();

        if ($sold > 0) {
            throw new \Exception(__('Cannot delete purchase: :count IMEI/Serial number(s) already sold.', [
                'count' => $sold,
            ]));
        }

        ProductSerialNumber::where('purchase_id', $transaction->id)->delete();
    }

    /**
     * When purchase status changes to/from received, update serial availability.
     */
    public function syncPurchaseStatus(Transaction $transaction, $before_status)
    {
        if ($transaction->status === 'received' && $before_status !== 'received') {
            ProductSerialNumber::where('purchase_id', $transaction->id)
                ->where('status', 'reserved')
                ->update(['status' => 'available']);
        } elseif ($transaction->status !== 'received' && $before_status === 'received') {
            ProductSerialNumber::where('purchase_id', $transaction->id)
                ->where('status', 'available')
                ->update(['status' => 'reserved']);
        }
    }

    /**
     * Available serials for POS.
     */
    public function getAvailableSerials($product_id, $business_id, $location_id = null, $variation_id = null)
    {
        $q = ProductSerialNumber::where('product_id', $product_id)
            ->where('business_id', $business_id)
            ->where('status', 'available')
            ->orderBy('serial_number');

        if (! empty($location_id)) {
            $q->where(function ($query) use ($location_id) {
                $query->whereNull('location_id')
                    ->orWhere('location_id', $location_id);
            });
        }

        if (! empty($variation_id)) {
            $q->where(function ($query) use ($variation_id) {
                $query->whereNull('variation_id')
                    ->orWhere('variation_id', $variation_id);
            });
        }

        return $q->get(['id', 'serial_number', 'variation_id', 'location_id', 'purchase_id']);
    }

    /**
     * Mark serials sold from POS/sale products payload.
     */
    public function markSoldFromSale(Transaction $transaction, array $products)
    {
        if ($transaction->status !== 'final') {
            return;
        }

        $transaction->loadMissing('sell_lines');
        $matched = [];

        foreach ($products as $product) {
            $serial_ids = $product['product_serial_number_ids'] ?? [];
            $serial_texts = $product['product_serial_numbers'] ?? [];

            if (empty($serial_ids) && empty($serial_texts) && ! empty($product['sell_line_note'])) {
                // Fallback: parse sell_line_note lines as serials
                $serial_texts = preg_split('/[\r\n,]+/', $product['sell_line_note']);
            }

            if (empty($serial_ids) && empty($serial_texts)) {
                continue;
            }

            $sell_line = null;
            if (! empty($product['transaction_sell_lines_id'])) {
                $sell_line = $transaction->sell_lines->firstWhere('id', (int) $product['transaction_sell_lines_id']);
            }

            if (empty($sell_line)) {
                $sell_line = $transaction->sell_lines
                    ->where('product_id', (int) ($product['product_id'] ?? 0))
                    ->where('variation_id', (int) ($product['variation_id'] ?? 0))
                    ->filter(function ($sl) use ($matched) {
                        return ! in_array($sl->id, $matched, true);
                    })
                    ->sortBy('id')
                    ->first();
            }

            if (empty($sell_line)) {
                continue;
            }
            $matched[] = $sell_line->id;

            $ids = is_array($serial_ids) ? array_filter($serial_ids) : [];
            if (! empty($ids)) {
                ProductSerialNumber::whereIn('id', $ids)
                    ->where('business_id', $transaction->business_id)
                    ->where('status', 'available')
                    ->update([
                        'status' => 'sold',
                        'sell_line_id' => $sell_line->id,
                        'updated_at' => now(),
                    ]);
            }

            $texts = $this->normalizeSerials($serial_texts);
            foreach ($texts as $text) {
                ProductSerialNumber::where('business_id', $transaction->business_id)
                    ->where('product_id', $sell_line->product_id)
                    ->whereRaw('LOWER(serial_number) = ?', [mb_strtolower($text)])
                    ->where('status', 'available')
                    ->update([
                        'status' => 'sold',
                        'sell_line_id' => $sell_line->id,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Restore serials to available when sell return is processed.
     */
    public function restoreFromSellReturn(Transaction $parent_sell, array $returned_products = [])
    {
        $parent_sell->loadMissing('sell_lines');

        foreach ($returned_products as $product) {
            $sell_line_id = $product['sell_line_id'] ?? $product['transaction_sell_lines_id'] ?? null;
            $qty = (float) ($product['quantity'] ?? 0);

            if (empty($sell_line_id) || $qty <= 0) {
                continue;
            }

            $serials = ProductSerialNumber::where('sell_line_id', $sell_line_id)
                ->where('status', 'sold')
                ->orderBy('id')
                ->limit((int) ceil($qty))
                ->get();

            foreach ($serials as $serial) {
                $serial->status = 'available';
                $serial->sell_line_id = null;
                $serial->save();
            }
        }
    }

    /**
     * Serials for a purchase (edit screen).
     */
    public function getSerialsForPurchase($purchase_id)
    {
        return ProductSerialNumber::where('purchase_id', $purchase_id)
            ->orderBy('id')
            ->get()
            ->groupBy('purchase_line_id');
    }
}
