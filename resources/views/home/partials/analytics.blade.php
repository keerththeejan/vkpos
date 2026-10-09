<div id="dashboard_analytics" class="tw-mb-6" data-url="{{ url('/home/analytics') }}">
    <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-end tw-gap-3 tw-mb-4">
        <div class="tw-min-w-0">
            <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-1">@lang('home.analytics_title')</h2>
            <p class="tw-text-sm tw-text-gray-500 tw-mb-0">@lang('home.analytics_stock_note')</p>
            <p id="da_scope" class="tw-text-sm tw-text-sky-700 tw-mb-0"></p>
        </div>
        <div class="lg:tw-ml-auto tw-flex tw-flex-wrap tw-gap-2 tw-items-center">
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline da-preset" data-preset="today">@lang('home.today')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline da-preset" data-preset="yesterday">@lang('home.yesterday')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline da-preset" data-preset="week">@lang('home.this_week')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline da-preset" data-preset="month">@lang('home.this_month')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline da-preset" data-preset="30">@lang('home.sells_last_30_days')</button>
            <label class="tw-sr-only" for="da_category">@lang('product.category')</label>
            <select id="da_category" class="form-control input-sm" style="width:auto; min-width:160px;">
                <option value="">@lang('lang_v1.all')</option>
                @foreach ($analytics_categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div id="da_alert" class="tw-mb-3"></div>
    <div id="da_cards" class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 xl:tw-grid-cols-4 tw-gap-4"></div>
    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4 tw-mt-4">
        <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200 tw-p-4">
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">@lang('sale.sale') / @lang('lang_v1.purchase')</h3>
            <div style="position:relative; height:220px;"><canvas id="da_chart_compare"></canvas></div>
        </div>
        <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200 tw-p-4">
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">@lang('home.analytics_gross_profit')</h3>
            <div style="position:relative; height:220px;"><canvas id="da_chart_profit"></canvas></div>
        </div>
        <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200 tw-p-4">
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">@lang('home.analytics_stock_cost')</h3>
            <div style="position:relative; height:220px;"><canvas id="da_chart_category"></canvas></div>
            <div id="da_branch_table" class="tw-mt-3"></div>
        </div>
        <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200 tw-p-4">
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">@lang('home.top_products')</h3>
            <div id="da_lists"></div>
        </div>
    </div>
</div>
<script type="application/json" id="da-labels">{!! json_encode([
    'stockQty' => __('home.analytics_stock_qty'),
    'stockCost' => __('home.analytics_stock_cost'),
    'potential' => __('home.analytics_potential'),
    'lowStock' => __('home.analytics_low_stock'),
    'outOfStock' => __('home.analytics_out_of_stock'),
    'negative' => __('home.analytics_negative'),
    'todaySales' => __('home.analytics_today_sales'),
    'monthSales' => __('home.analytics_month_sales'),
    'periodSales' => __('home.analytics_period_sales'),
    'saleCount' => __('home.analytics_sale_count'),
    'avgSale' => __('home.analytics_avg_sale'),
    'grossProfit' => __('home.analytics_gross_profit'),
    'margin' => __('home.analytics_margin'),
    'purchases' => __('home.analytics_purchases'),
    'purchasesToday' => __('home.analytics_purchases_today'),
    'purchaseReturns' => __('home.analytics_purchase_returns'),
    'cash' => __('home.analytics_cash'),
    'bank' => __('home.analytics_bank'),
    'customerDue' => __('home.analytics_customer_due'),
    'supplierDue' => __('home.analytics_supplier_due'),
    'expenses' => __('home.analytics_expenses'),
    'potentialTip' => __('home.analytics_potential_tip'),
    'profitTip' => __('home.analytics_profit_tip'),
    'cashTip' => __('home.analytics_cash_tip'),
    'bankTip' => __('home.analytics_bank_tip'),
    'dueTip' => __('home.analytics_due_tip'),
    'currency' => session('currency.code') ?: 'LKR',
    'sales' => __('sale.sale'),
    'purchase' => __('lang_v1.purchase'),
    'branch' => __('business.business_locations'),
    'receivables' => __('home.analytics_customer_due'),
    'payables' => __('home.analytics_supplier_due'),
    'qty' => __('sale.qty'),
    'error' => __('messages.something_went_wrong'),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
