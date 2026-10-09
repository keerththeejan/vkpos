<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use App\User;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsController extends Controller
{
    protected $transactionUtil;

    protected $productUtil;

    protected $resultMemo = [];

    protected $alertRows;

    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    public function show(Request $request)
    {
        if (! auth()->user()->can('dashboard.data')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        if (empty($businessId)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'location_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
        ]);

        $start = Carbon::parse($validated['start'])->toDateString();
        $end = Carbon::parse($validated['end'])->toDateString();
        $locationId = ! empty($validated['location_id']) ? (int) $validated['location_id'] : null;
        $categoryId = ! empty($validated['category_id']) ? (int) $validated['category_id'] : null;
        $permitted = auth()->user()->permitted_locations();
        $locationIds = $this->locationIds($businessId, $permitted, $locationId);

        if (! empty($categoryId)) {
            $categoryOk = Category::where('business_id', $businessId)->where('id', $categoryId)->exists();
            if (! $categoryOk) {
                abort(422, 'Invalid category.');
            }
        }

        $canValue = auth()->user()->can('view_product_stock_value');
        $stockPermitted = $locationIds === null ? 'all' : $locationIds;
        $stockLocation = is_array($locationIds) && count($locationIds) === 1 ? $locationIds[0] : null;
        $snapshot = $this->stockSnapshot($businessId, $end, $stockLocation, $stockPermitted, $categoryId);
        $cost = $snapshot['cost'];
        $potential = $snapshot['potential'];
        $alerts = $this->alertRows($businessId, $stockPermitted);

        $today = Carbon::now()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $salesPeriod = $this->sales($businessId, $start, $end, $stockLocation, $stockPermitted, $categoryId);
        $salesToday = $this->sales($businessId, $today, $today, $stockLocation, $stockPermitted, $categoryId);
        $salesMonth = $this->sales($businessId, $monthStart, $today, $stockLocation, $stockPermitted, $categoryId);
        $profit = $this->profit($businessId, $start, $end, $stockLocation, $stockPermitted, $categoryId);
        $purchases = $this->purchases($businessId, $start, $end, $stockLocation, $stockPermitted);
        $purchasesToday = $this->purchases($businessId, $today, $today, $stockLocation, $stockPermitted);
        $finance = $this->finance($businessId, $end, $start, $stockLocation, $stockPermitted, $locationIds);

        $margin = null;
        if (abs($profit['revenue']) > 0.0001) {
            $margin = round(($profit['gross_profit'] / $profit['revenue']) * 100, 2);
        }

        $currency = session('currency.code') ?: '';

        return response()->json([
            'currency' => $currency,
            'as_of' => $end,
            'start' => $start,
            'end' => $end,
            'category_note' => empty($categoryId) ? '' : 'The category filter applies to stock, sales, profit, and product lists. Purchases, cash, bank, dues, and expenses stay at branch level.',
            'stock' => [
                'quantity' => round($snapshot['quantity'], 4),
                'live_quantity' => round($this->liveQuantity($businessId, $stockLocation, $stockPermitted, $categoryId), 4),
                'cost' => $canValue ? round($cost, 4) : null,
                'potential_sales' => $canValue ? round($potential, 4) : null,
                'low_stock' => $alerts->count(),
                'out_of_stock' => $this->outOfStockCount($businessId, $locationIds, $categoryId),
                'negative' => $this->negativeStockCount($businessId, $locationIds, $categoryId),
                'can_value' => $canValue,
                'branches' => $canValue ? $this->branchValues($businessId, $locationIds, $snapshot['by_location']) : [],
                'categories' => $canValue ? $this->categoryValues($businessId, $categoryId, $cost, $snapshot['by_category']) : [],
                'low_stock_rows' => $this->lowStockRows($alerts),
            ],
            'sales' => [
                'today' => $salesToday['net'],
                'month' => $salesMonth['net'],
                'period_net' => $salesPeriod['net'],
                'period_gross' => $salesPeriod['gross'],
                'returns' => $salesPeriod['returns'],
                'count' => $salesPeriod['count'],
                'average' => $salesPeriod['count'] > 0 ? round($salesPeriod['net'] / $salesPeriod['count'], 4) : 0,
            ],
            'profit' => [
                'gross_profit' => $profit['gross_profit'],
                'revenue' => $profit['revenue'],
                'cogs' => round($profit['revenue'] - $profit['gross_profit'], 4),
                'margin' => $margin,
            ],
            'purchases' => [
                'today' => $purchasesToday['gross'],
                'period' => $purchases['gross'],
                'returns' => $purchases['returns'],
                'net' => $purchases['net'],
            ],
            'finance' => $finance,
            'charts' => $this->charts($businessId, $start, $end, $stockLocation, $stockPermitted, $categoryId),
            'top_products' => $this->topProducts($businessId, $start, $end, $stockLocation, $stockPermitted, $categoryId),
            'receivables' => $this->contactBalances($businessId, $end, $stockLocation, $stockPermitted, 'sell'),
            'payables' => $this->contactBalances($businessId, $end, $stockLocation, $stockPermitted, 'purchase'),
            'links' => [
                'stock' => url('/reports/stock-report'),
                'profit' => url('/reports/profit-loss'),
                'sells' => url('/sells'),
                'purchases' => url('/purchases'),
                'expenses' => url('/expenses'),
                'accounts' => url('/account/account'),
                'low_stock' => url('/home').'#product_stock_alert',
            ],
        ]);
    }

    protected function locationIds($businessId, $permitted, $locationId)
    {
        $ids = $permitted === 'all' ? null : (is_array($permitted) ? array_values(array_map('intval', $permitted)) : []);
        if (! empty($locationId)) {
            if (! User::can_access_this_location($locationId, $businessId)) {
                abort(403, 'Unauthorized action.');
            }
            $exists = BusinessLocation::where('business_id', $businessId)->where('id', $locationId)->exists();
            if (! $exists) {
                abort(422, 'Invalid branch.');
            }

            return [(int) $locationId];
        }

        return $ids;
    }

    protected function sales($businessId, $start, $end, $locationId, $permitted, $categoryId)
    {
        $memoKey = implode('|', [
            'sales',
            $start,
            $end,
            (string) $locationId,
            is_array($permitted) ? implode(',', $permitted) : (string) $permitted,
            (string) $categoryId,
        ]);
        if (array_key_exists($memoKey, $this->resultMemo)) {
            return $this->resultMemo[$memoKey];
        }

        if (! empty($categoryId)) {
            $row = $this->sellLineQuery($businessId, $start, $end, $locationId, $permitted, $categoryId)
                ->select(
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as net'),
                    DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as gross'),
                    DB::raw('COUNT(DISTINCT sale.id) as sales_count')
                )
                ->first();
            $net = round((float) ($row->net ?? 0), 4);
            $gross = round((float) ($row->gross ?? 0), 4);

            return $this->resultMemo[$memoKey] = [
                'net' => $net,
                'gross' => $gross,
                'returns' => round($gross - $net, 4),
                'count' => (int) ($row->sales_count ?? 0),
            ];
        }

        $sell = $this->transactionUtil->getSellTotals($businessId, $start, $end, $locationId, null, $permitted);
        $returns = $this->transactionUtil->getTransactionTotals(
            $businessId,
            ['sell_return'],
            $start,
            $end,
            $locationId,
            null,
            $permitted
        );
        $gross = round((float) ($sell['total_sell_inc_tax'] ?? 0), 4);
        $returnTotal = round((float) ($returns['total_sell_return_inc_tax'] ?? 0), 4);
        $countQuery = Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);
        $this->limitTransaction($countQuery, $locationId, $permitted);

        return $this->resultMemo[$memoKey] = [
            'net' => round($gross - $returnTotal, 4),
            'gross' => $gross,
            'returns' => $returnTotal,
            'count' => (int) $countQuery->count(),
        ];
    }

    protected function purchases($businessId, $start, $end, $locationId, $permitted)
    {
        $memoKey = implode('|', [
            'purchases',
            $start,
            $end,
            (string) $locationId,
            is_array($permitted) ? implode(',', $permitted) : (string) $permitted,
        ]);
        if (array_key_exists($memoKey, $this->resultMemo)) {
            return $this->resultMemo[$memoKey];
        }

        $purchase = $this->transactionUtil->getPurchaseTotals($businessId, $start, $end, $locationId, null, $permitted);
        $returns = $this->transactionUtil->getTransactionTotals(
            $businessId,
            ['purchase_return'],
            $start,
            $end,
            $locationId,
            null,
            $permitted
        );
        $gross = round((float) ($purchase['total_purchase_inc_tax'] ?? 0), 4);
        $returnTotal = round((float) ($returns['total_purchase_return_inc_tax'] ?? 0), 4);

        return $this->resultMemo[$memoKey] = [
            'gross' => $gross,
            'returns' => $returnTotal,
            'net' => round($gross - $returnTotal, 4),
        ];
    }

    protected function profit($businessId, $start, $end, $locationId, $permitted, $categoryId)
    {
        $row = $this->profitQuery($businessId, $locationId, $permitted, $categoryId)
            ->whereDate('sale.transaction_date', '>=', $start)
            ->whereDate('sale.transaction_date', '<=', $end)
            ->select(
                DB::raw($this->grossProfitSql().' as gross_profit'),
                DB::raw($this->salesValueSql().' as revenue')
            )
            ->first();

        return [
            'gross_profit' => round((float) ($row->gross_profit ?? 0), 4),
            'revenue' => round((float) ($row->revenue ?? 0), 4),
        ];
    }

    protected function finance($businessId, $end, $start, $locationId, $permitted, $locationIds)
    {
        $sell = $this->transactionUtil->getSellTotals($businessId, null, $end, $locationId, null, $permitted);
        $sellReturns = $this->transactionUtil->getTransactionTotals(
            $businessId,
            ['sell_return'],
            null,
            $end,
            $locationId,
            null,
            $permitted
        );
        $purchase = $this->transactionUtil->getPurchaseTotals($businessId, null, $end, $locationId, null, $permitted);
        $expenses = $this->transactionUtil->getTransactionTotals(
            $businessId,
            ['expense'],
            $start,
            $end,
            $locationId,
            null,
            $permitted
        );

        $methods = $this->methodBalances($businessId, $end, $locationIds);
        $posted = $this->postedAccountBalances($businessId, $end, $locationIds);

        return [
            'cash' => round(($methods['cash'] ?? 0) + $posted['cash'], 4),
            'bank' => round(($methods['bank_transfer'] ?? 0) + $posted['bank'], 4),
            'card' => round($methods['card'] ?? 0, 4),
            'cheque' => round($methods['cheque'] ?? 0, 4),
            'customer_due' => round((float) ($sell['invoice_due'] ?? 0) - (float) ($sellReturns['total_sell_return_inc_tax'] ?? 0), 4),
            'supplier_due' => round((float) ($purchase['purchase_due'] ?? 0), 4),
            'expenses' => round((float) ($expenses['total_expense'] ?? 0), 4),
        ];
    }

    protected function charts($businessId, $start, $end, $locationId, $permitted, $categoryId)
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $byMonth = $startDate->diffInDays($endDate) > 62;
        $bucket = $byMonth ? '%Y-%m' : '%Y-%m-%d';

        $sales = $this->bucketTotals($this->salesBuckets($businessId, $start, $end, $locationId, $permitted, $categoryId, $bucket));
        $purchaseRows = Transaction::where('business_id', $businessId)
            ->where('type', 'purchase')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);
        $this->limitTransaction($purchaseRows, $locationId, $permitted);
        $purchases = $this->bucketTotals(
            $purchaseRows->select(
                DB::raw("DATE_FORMAT(transaction_date, '{$bucket}') as bucket"),
                DB::raw('SUM(final_total) as total')
            )->groupBy('bucket')->pluck('total', 'bucket')
        );
        $returnRows = Transaction::where('business_id', $businessId)
            ->where('type', 'purchase_return')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);
        $this->limitTransaction($returnRows, $locationId, $permitted);
        foreach ($returnRows->select(
            DB::raw("DATE_FORMAT(transaction_date, '{$bucket}') as bucket"),
            DB::raw('SUM(final_total) as total')
        )->groupBy('bucket')->pluck('total', 'bucket') as $bucketKey => $amount) {
            $purchases[$bucketKey] = round(($purchases[$bucketKey] ?? 0) - (float) $amount, 4);
        }

        $profitRows = $this->profitQuery($businessId, $locationId, $permitted, $categoryId)
            ->whereDate('sale.transaction_date', '>=', $start)
            ->whereDate('sale.transaction_date', '<=', $end)
            ->select(
                DB::raw("DATE_FORMAT(sale.transaction_date, '{$bucket}') as bucket"),
                DB::raw($this->grossProfitSql().' as gross_profit')
            )
            ->groupBy('bucket')
            ->pluck('gross_profit', 'bucket');
        $profits = $this->bucketTotals($profitRows);

        $labels = $this->buckets($startDate, $endDate, $byMonth);
        $salesSeries = [];
        $purchaseSeries = [];
        $profitSeries = [];
        foreach ($labels as $label) {
            $salesSeries[] = round((float) ($sales[$label] ?? 0), 4);
            $purchaseSeries[] = round((float) ($purchases[$label] ?? 0), 4);
            $profitSeries[] = round((float) ($profits[$label] ?? 0), 4);
        }

        return [
            'grouped_by' => $byMonth ? 'month' : 'day',
            'labels' => $labels,
            'sales' => $salesSeries,
            'purchases' => $purchaseSeries,
            'profit' => $profitSeries,
        ];
    }

    protected function salesBuckets($businessId, $start, $end, $locationId, $permitted, $categoryId, $bucket)
    {
        if (! empty($categoryId)) {
            return $this->sellLineQuery($businessId, $start, $end, $locationId, $permitted, $categoryId)
                ->select(
                    DB::raw("DATE_FORMAT(sale.transaction_date, '{$bucket}') as bucket"),
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as total')
                )
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
        }

        $sales = Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);
        $this->limitTransaction($sales, $locationId, $permitted);
        $totals = $sales->select(
            DB::raw("DATE_FORMAT(transaction_date, '{$bucket}') as bucket"),
            DB::raw('SUM(final_total) as total')
        )->groupBy('bucket')->pluck('total', 'bucket');

        $returns = Transaction::where('business_id', $businessId)
            ->where('type', 'sell_return')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);
        $this->limitTransaction($returns, $locationId, $permitted);
        foreach ($returns->select(
            DB::raw("DATE_FORMAT(transaction_date, '{$bucket}') as bucket"),
            DB::raw('SUM(final_total) as total')
        )->groupBy('bucket')->pluck('total', 'bucket') as $key => $amount) {
            $totals[$key] = (float) ($totals[$key] ?? 0) - (float) $amount;
        }

        return $totals;
    }

    protected function stockSnapshot($businessId, $date, $locationId, $permitted, $categoryId)
    {
        $empty = [
            'cost' => 0.0,
            'potential' => 0.0,
            'quantity' => 0.0,
            'by_location' => [],
            'by_category' => [],
        ];
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            return $empty;
        }

        $sold = "(SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0) FROM
                            transaction_sell_lines_purchase_lines AS tspl
                            JOIN transaction_sell_lines as tsl ON
                            tspl.sell_line_id=tsl.id
                            JOIN transactions as sale ON
                            tsl.transaction_id=sale.id
                            WHERE tspl.purchase_line_id = purchase_lines.id AND
                            date(sale.transaction_date) <= '{$date}')";
        $remaining = "(purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted - {$sold})";
        $costPrice = '(purchase_lines.purchase_price + COALESCE(purchase_lines.item_tax, 0))';

        $query = PurchaseLine::join('transactions as purchase', 'purchase_lines.transaction_id', '=', 'purchase.id')
            ->where('purchase.type', '!=', 'purchase_order')
            ->where('purchase.business_id', $businessId)
            ->leftJoin('variations as v', 'v.id', '=', 'purchase_lines.variation_id')
            ->leftJoin('products as p', 'p.id', '=', 'purchase_lines.product_id')
            ->whereRaw("date(purchase.transaction_date) <= '{$date}'");

        if (! empty($categoryId)) {
            $query->where('p.category_id', $categoryId);
        }
        if (! empty($permitted) && $permitted != 'all') {
            $query->whereIn('purchase.location_id', is_array($permitted) ? $permitted : [$permitted]);
        }
        if (! empty($locationId)) {
            $query->where('purchase.location_id', $locationId);
        }

        $rows = $query->groupBy('purchase.location_id', 'p.category_id')
            ->select(
                'purchase.location_id',
                'p.category_id',
                DB::raw("SUM({$remaining} * {$costPrice}) as cost"),
                DB::raw("SUM({$remaining} * v.sell_price_inc_tax) as potential"),
                DB::raw("SUM(IF(p.enable_stock = 1 AND p.is_inactive = 0, {$remaining}, 0)) as qty")
            )
            ->get();

        $cost = 0.0;
        $potential = 0.0;
        $quantity = 0.0;
        $byLocation = [];
        $byCategory = [];
        foreach ($rows as $row) {
            $rowCost = (float) $row->cost;
            $cost += $rowCost;
            $potential += (float) $row->potential;
            $quantity += (float) $row->qty;
            $locationKey = (int) $row->location_id;
            $byLocation[$locationKey] = ($byLocation[$locationKey] ?? 0) + $rowCost;
            $categoryKey = $row->category_id === null ? 'none' : (string) (int) $row->category_id;
            $byCategory[$categoryKey] = ($byCategory[$categoryKey] ?? 0) + $rowCost;
        }

        $empty['cost'] = $cost;
        $empty['potential'] = $potential;
        $empty['quantity'] = $quantity;
        $empty['by_location'] = $byLocation;
        $empty['by_category'] = $byCategory;

        return $empty;
    }

    protected function branchValues($businessId, $locationIds, array $byLocation)
    {
        $query = BusinessLocation::where('business_id', $businessId)->orderBy('name');
        if (is_array($locationIds)) {
            $query->whereIn('id', $locationIds ?: [0]);
        }
        $rows = [];
        foreach ($query->get(['id', 'name']) as $location) {
            $rows[] = [
                'name' => $location->name,
                'cost' => round((float) ($byLocation[$location->id] ?? 0), 4),
            ];
        }

        return $rows;
    }

    protected function categoryValues($businessId, $categoryId, $totalCost, array $byCategory)
    {
        if (! empty($categoryId)) {
            $category = Category::where('business_id', $businessId)->find($categoryId);

            return [[
                'name' => $category->name ?? ('#'.$categoryId),
                'cost' => round((float) $totalCost, 4),
            ]];
        }

        $categories = Category::where('business_id', $businessId)
            ->where('category_type', 'product')
            ->where(function ($query) {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = [];
        $assigned = 0;
        foreach ($categories as $category) {
            $cost = round((float) ($byCategory[(string) $category->id] ?? 0), 4);
            $assigned = round($assigned + $cost, 4);
            if ($cost != 0.0) {
                $rows[] = ['name' => $category->name, 'cost' => $cost];
            }
        }
        $other = round((float) $totalCost - $assigned, 4);
        if (abs($other) >= 0.01) {
            $rows[] = ['name' => __('lang_v1.uncategorized'), 'cost' => $other];
        }

        return $rows;
    }

    protected function layerQuantity($businessId, $date, $locationId, $permitted, $categoryId)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 0;
        }

        $query = PurchaseLine::query()
            ->join('transactions as purchase', 'purchase_lines.transaction_id', '=', 'purchase.id')
            ->join('products as p', 'p.id', '=', 'purchase_lines.product_id')
            ->where('purchase.business_id', $businessId)
            ->where('purchase.type', '!=', 'purchase_order')
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereRaw("date(purchase.transaction_date) <= '{$date}'");

        if (! empty($categoryId)) {
            $query->where('p.category_id', $categoryId);
        }
        if (is_array($permitted)) {
            $query->whereIn('purchase.location_id', $permitted ?: [0]);
        }
        if (! empty($locationId)) {
            $query->where('purchase.location_id', $locationId);
        }

        $sold = "(SELECT COALESCE(SUM(tspl.quantity - tspl.qty_returned), 0)
            FROM transaction_sell_lines_purchase_lines AS tspl
            JOIN transaction_sell_lines as tsl ON tspl.sell_line_id = tsl.id
            JOIN transactions as sale ON tsl.transaction_id = sale.id
            WHERE tspl.purchase_line_id = purchase_lines.id
            AND date(sale.transaction_date) <= '{$date}')";

        return (float) $query->select(DB::raw(
            "SUM(purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted - {$sold}) as qty"
        ))->value('qty');
    }

    protected function liveQuantity($businessId, $locationId, $permitted, $categoryId)
    {
        $query = VariationLocationDetails::query()
            ->join('products as p', 'p.id', '=', 'variation_location_details.product_id')
            ->join('variations as v', 'v.id', '=', 'variation_location_details.variation_id')
            ->where('p.business_id', $businessId)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at');
        if (! empty($categoryId)) {
            $query->where('p.category_id', $categoryId);
        }
        if (! empty($locationId)) {
            $query->where('variation_location_details.location_id', $locationId);
        } elseif (is_array($permitted)) {
            $query->whereIn('variation_location_details.location_id', $permitted ?: [0]);
        }

        return (float) $query->sum('variation_location_details.qty_available');
    }

    protected function alertRows($businessId, $permitted)
    {
        if ($this->alertRows === null) {
            $this->alertRows = $this->productUtil->getProductAlert($businessId, $permitted)->get();
        }

        return $this->alertRows;
    }

    protected function lowStockRows($alerts)
    {
        $rows = [];
        foreach ($alerts->take(8) as $row) {
            $name = $row->product;
            if ($row->type !== 'single') {
                $name .= ' - '.$row->product_variation.' - '.$row->variation;
            }
            $rows[] = [
                'name' => $name,
                'stock' => round((float) $row->stock, 4),
                'unit' => $row->unit,
                'location' => $row->location,
            ];
        }

        return $rows;
    }

    protected function outOfStockCount($businessId, $locationIds, $categoryId)
    {
        if (is_array($locationIds) && count($locationIds) === 0) {
            return 0;
        }

        $stock = DB::table('variation_location_details')
            ->select('product_id', DB::raw('SUM(qty_available) as qty'))
            ->groupBy('product_id');
        if (is_array($locationIds)) {
            $stock->whereIn('location_id', $locationIds);
        }

        $query = Product::where('products.business_id', $businessId)
            ->where('products.enable_stock', 1)
            ->where('products.is_inactive', 0)
            ->leftJoinSub($stock, 'stock_qty', 'stock_qty.product_id', '=', 'products.id')
            ->whereRaw('COALESCE(stock_qty.qty, 0) <= 0');
        if (! empty($categoryId)) {
            $query->where('products.category_id', $categoryId);
        }

        return (int) $query->count();
    }

    protected function negativeStockCount($businessId, $locationIds, $categoryId)
    {
        $query = VariationLocationDetails::query()
            ->join('products as p', 'p.id', '=', 'variation_location_details.product_id')
            ->where('p.business_id', $businessId)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->where('variation_location_details.qty_available', '<', 0);
        if (! empty($categoryId)) {
            $query->where('p.category_id', $categoryId);
        }
        if (is_array($locationIds)) {
            $query->whereIn('variation_location_details.location_id', $locationIds ?: [0]);
        }

        return (int) $query->count();
    }

    protected function topProducts($businessId, $start, $end, $locationId, $permitted, $categoryId)
    {
        $rows = $this->sellLineQuery($businessId, $start, $end, $locationId, $permitted, $categoryId)
            ->select(
                'p.name as name',
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as qty'),
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as revenue')
            )
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'name' => $row->name,
                'qty' => round((float) $row->qty, 4),
                'revenue' => round((float) $row->revenue, 4),
            ];
        }

        return $list;
    }

    protected function contactBalances($businessId, $end, $locationId, $permitted, $type)
    {
        $query = Transaction::query()
            ->join('contacts as c', 'c.id', '=', 'transactions.contact_id')
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', $type)
            ->whereDate('transactions.transaction_date', '<=', $end);
        if ($type === 'sell') {
            $query->where('transactions.status', 'final');
        }
        $this->limitTransaction($query, $locationId, $permitted);

        $rows = $query->select(
            'c.id',
            'c.name',
            'c.supplier_business_name',
            DB::raw("SUM(transactions.final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)), 0) FROM transaction_payments tp WHERE tp.transaction_id = transactions.id)) as due")
        )
            ->groupBy('c.id', 'c.name', 'c.supplier_business_name')
            ->having('due', '>', 0.0001)
            ->orderByDesc('due')
            ->limit(8)
            ->get();

        $list = [];
        foreach ($rows as $row) {
            $name = $row->supplier_business_name ?: $row->name;
            $list[] = [
                'name' => $name,
                'due' => round((float) $row->due, 4),
            ];
        }

        return $list;
    }

    protected function methodBalances($businessId, $end, $locationIds)
    {
        $query = AccountTransaction::unpostedPaymentQuery($businessId)
            ->whereDate('tp.paid_on', '<=', $end);
        if (is_array($locationIds)) {
            $query->whereIn('t.location_id', $locationIds ?: [0]);
        }
        $rows = $query->groupBy('tp.method')
            ->select([
                'tp.method',
                DB::raw('SUM('.AccountTransaction::signedAmountSql().') as balance'),
            ])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->method ?: ''] = round((float) $row->balance, 4);
        }

        return $map;
    }

    protected function postedAccountBalances($businessId, $end, $locationIds)
    {
        $cash = 0;
        $bank = 0;
        $locations = BusinessLocation::where('business_id', $businessId);
        if (is_array($locationIds)) {
            $locations->whereIn('id', $locationIds ?: [0]);
        }
        $cashIds = [];
        $bankIds = [];
        foreach ($locations->get(['default_payment_accounts']) as $location) {
            $decoded = json_decode($location->default_payment_accounts, true);
            if (! is_array($decoded)) {
                continue;
            }
            if (! empty($decoded['cash']['account'])) {
                $cashIds[] = (int) $decoded['cash']['account'];
            }
            if (! empty($decoded['bank_transfer']['account'])) {
                $bankIds[] = (int) $decoded['bank_transfer']['account'];
            }
        }
        $ids = array_values(array_unique(array_merge($cashIds, $bankIds)));
        if (empty($ids)) {
            return ['cash' => 0, 'bank' => 0];
        }

        $balances = DB::table('accounts')
            ->leftJoin('account_transactions as AT', function ($join) use ($end) {
                $join->on('AT.account_id', '=', 'accounts.id')
                    ->whereNull('AT.deleted_at')
                    ->whereDate('AT.operation_date', '<=', $end);
            })
            ->where('accounts.business_id', $businessId)
            ->whereNull('accounts.deleted_at')
            ->whereIn('accounts.id', $ids)
            ->groupBy('accounts.id')
            ->select('accounts.id', DB::raw("SUM(IF(AT.type='credit', AT.amount, -1 * AT.amount)) as balance"))
            ->pluck('balance', 'id');

        foreach ($cashIds as $id) {
            $cash = round($cash + (float) ($balances[$id] ?? 0), 4);
        }
        foreach ($bankIds as $id) {
            $bank = round($bank + (float) ($balances[$id] ?? 0), 4);
        }

        return ['cash' => $cash, 'bank' => $bank];
    }

    protected function sellLineQuery($businessId, $start, $end, $locationId, $permitted, $categoryId)
    {
        $query = TransactionSellLine::query()
            ->join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->join('products as p', 'p.id', '=', 'transaction_sell_lines.product_id')
            ->where('sale.business_id', $businessId)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('transaction_sell_lines.children_type', '!=', 'combo')
            ->whereDate('sale.transaction_date', '>=', $start)
            ->whereDate('sale.transaction_date', '<=', $end);
        if (! empty($categoryId)) {
            $query->where('p.category_id', $categoryId);
        }
        $this->limitSale($query, $locationId, $permitted);

        return $query;
    }

    protected function profitQuery($businessId, $locationId, $permitted, $categoryId)
    {
        $query = TransactionSellLine::query()
            ->join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftJoin('purchase_lines as PL', 'TSPL.purchase_line_id', '=', 'PL.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.business_id', $businessId)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('transaction_sell_lines.children_type', '!=', 'combo');
        if (! empty($categoryId)) {
            $query->where('P.category_id', $categoryId);
        }
        $this->limitSale($query, $locationId, $permitted);

        return $query;
    }

    protected function grossProfitSql()
    {
        return "SUM(IF (TSPL.id IS NULL AND P.type='combo', (
            SELECT SUM((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax))
                FROM transaction_sell_lines AS tsl
                JOIN transaction_sell_lines_purchase_lines AS tspl2 ON tsl.id = tspl2.sell_line_id
                JOIN purchase_lines AS pl2 ON tspl2.purchase_line_id = pl2.id
                WHERE tsl.parent_sell_line_id = transaction_sell_lines.id),
            IF(P.enable_stock=0, (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,
                (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) ))";
    }

    protected function salesValueSql()
    {
        return "SUM(IF (TSPL.id IS NULL AND P.type='combo',
            (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,
            IF(P.enable_stock=0, (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,
                (TSPL.quantity - TSPL.qty_returned) * transaction_sell_lines.unit_price_inc_tax) ))";
    }

    protected function limitTransaction($query, $locationId, $permitted)
    {
        if (is_array($permitted)) {
            $query->whereIn('transactions.location_id', $permitted ?: [0]);
        }
        if (! empty($locationId)) {
            $query->where('transactions.location_id', $locationId);
        }
    }

    protected function limitSale($query, $locationId, $permitted)
    {
        if (is_array($permitted)) {
            $query->whereIn('sale.location_id', $permitted ?: [0]);
        }
        if (! empty($locationId)) {
            $query->where('sale.location_id', $locationId);
        }
    }

    protected function bucketTotals($rows)
    {
        $totals = [];
        foreach ($rows as $key => $amount) {
            $totals[(string) $key] = round((float) $amount, 4);
        }

        return $totals;
    }

    protected function buckets(Carbon $start, Carbon $end, $byMonth)
    {
        $labels = [];
        $cursor = $start->copy();
        if ($byMonth) {
            $cursor->startOfMonth();
            $last = $end->format('Y-m');
            while ($cursor->format('Y-m') <= $last) {
                $labels[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $labels;
        }

        while ($cursor->toDateString() <= $end->toDateString()) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $labels;
    }
}
