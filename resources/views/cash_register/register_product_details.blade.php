@php
  $product_rows = $details['product_details'] ?? [];
  $brand_rows = $details['product_details_by_brand'] ?? [];
  $brand_revenue_total = 0;
  foreach ($brand_rows as $b) {
    $brand_revenue_total += $b->total_amount;
  }
  if ($brand_revenue_total <= 0) {
    $brand_revenue_total = 1;
  }
@endphp

<div class="cr-section">
  <div class="cr-section-head">
    <h3><i class="fas fa-box-open"></i> @lang('lang_v1.product_sold_details_register')</h3>
    <input type="search" class="cr-search-input cr-product-search" placeholder="@lang('lang_v1.search')..." aria-label="@lang('lang_v1.search')">
  </div>
  <div class="cr-table-wrap">
    <table class="table table-condensed cr-premium-table cr-products-table">
      <thead>
        <tr>
          <th>#</th>
          <th>@lang('product.sku')</th>
          <th>@lang('sale.product')</th>
          <th>@lang('sale.qty')</th>
          <th>@lang('sale.total_amount')</th>
        </tr>
      </thead>
      <tbody>
        @php
          $total_amount = 0;
          $total_quantity = 0;
        @endphp
        @foreach($details['product_details'] as $detail)
          <tr>
            <td>{{$loop->iteration}}.</td>
            <td><span class="cr-sku-badge">{{$detail->sku}}</span></td>
            <td>
              {{$detail->product_name}}
              @if($detail->type == 'variable')
               {{$detail->product_variation_name}} - {{$detail->variation_name}}
              @endif
            </td>
            <td>
              <span class="cr-qty-badge">{{@format_quantity($detail->total_quantity)}}</span>
              @php
                $total_quantity += $detail->total_quantity;
              @endphp
            </td>
            <td>
              <span class="display_currency" data-currency_symbol="true">
                {{$detail->total_amount}}
              </span>
              @php
                $total_amount += $detail->total_amount;
              @endphp
            </td>
          </tr>
        @endforeach

        @php
          $total_amount += ($details['transaction_details']->total_tax - $details['transaction_details']->total_discount);
          $total_amount += $details['transaction_details']->total_shipping_charges;
        @endphp

        <tr class="success">
          <th>#</th>
          <th></th>
          <th></th>
          <th>{{$total_quantity}}</th>
          <th>
            @if($details['transaction_details']->total_tax != 0)
              @lang('sale.order_tax'): (+)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_tax}}
              </span>
              <br/>
            @endif

            @if($details['transaction_details']->total_discount != 0)
              @lang('sale.discount'): (-)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_discount}}
              </span>
              <br/>
            @endif
            @if($details['transaction_details']->total_shipping_charges != 0)
              @lang('lang_v1.total_shipping_charges'): (+)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_shipping_charges}}
              </span>
              <br/>
            @endif

            @lang('lang_v1.grand_total'):
            <span class="display_currency" data-currency_symbol="true">
              {{$total_amount}}
            </span>
          </th>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="cr-section">
  <div class="cr-section-head">
    <h3><i class="fas fa-tags"></i> @lang('lang_v1.product_sold_details_register') (@lang('lang_v1.by_brand'))</h3>
  </div>
  <div class="cr-table-wrap">
    <table class="table table-condensed cr-premium-table">
      <thead>
        <tr>
          <th>#</th>
          <th>@lang('brand.brands')</th>
          <th>@lang('sale.qty')</th>
          <th>@lang('sale.total_amount')</th>
          <th>%</th>
        </tr>
      </thead>
      <tbody>
        @php
          $total_amount = 0;
          $total_quantity = 0;
        @endphp
        @foreach($details['product_details_by_brand'] as $detail)
          @php
            $brand_pct = min(100, round(($detail->total_amount / $brand_revenue_total) * 100));
          @endphp
          <tr>
            <td>{{$loop->iteration}}.</td>
            <td>
              <strong>{{$detail->brand_name}}</strong>
              <div class="cr-brand-bar"><span style="width: {{ $brand_pct }}%;"></span></div>
            </td>
            <td>
              <span class="cr-qty-badge">{{@format_quantity($detail->total_quantity)}}</span>
              @php
                $total_quantity += $detail->total_quantity;
              @endphp
            </td>
            <td>
              <span class="display_currency" data-currency_symbol="true">
                {{$detail->total_amount}}
              </span>
              @php
                $total_amount += $detail->total_amount;
              @endphp
            </td>
            <td>{{ $brand_pct }}%</td>
          </tr>
        @endforeach

        @php
          $total_amount += ($details['transaction_details']->total_tax - $details['transaction_details']->total_discount);
          $total_amount += $details['transaction_details']->total_shipping_charges;
        @endphp

        <tr class="success">
          <th>#</th>
          <th></th>
          <th>{{$total_quantity}}</th>
          <th>
            @if($details['transaction_details']->total_tax != 0)
              @lang('sale.order_tax'): (+)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_tax}}
              </span>
              <br/>
            @endif

            @if($details['transaction_details']->total_discount != 0)
              @lang('sale.discount'): (-)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_discount}}
              </span>
              <br/>
            @endif
            @if($details['transaction_details']->total_shipping_charges != 0)
              @lang('lang_v1.total_shipping_charges'): (+)
              <span class="display_currency" data-currency_symbol="true">
                {{$details['transaction_details']->total_shipping_charges}}
              </span>
              <br/>
            @endif

            @lang('lang_v1.grand_total'):
            <span class="display_currency" data-currency_symbol="true">
              {{$total_amount}}
            </span>
          </th>
          <th></th>
        </tr>
      </tbody>
    </table>
  </div>
</div>

@if($details['types_of_service_details'])
  <div class="cr-section">
    <div class="cr-section-head">
      <h3><i class="fas fa-concierge-bell"></i> @lang('lang_v1.types_of_service_details')</h3>
    </div>
    <div class="cr-table-wrap">
      <table class="table cr-premium-table">
        <thead>
          <tr>
            <th>#</th>
            <th>@lang('lang_v1.types_of_service')</th>
            <th>@lang('sale.total_amount')</th>
          </tr>
        </thead>
        <tbody>
          @php
            $total_sales = 0;
          @endphp
          @foreach($details['types_of_service_details'] as $detail)
            <tr>
              <td>{{$loop->iteration}}</td>
              <td>{{$detail->types_of_service_name ?? "--"}}</td>
              <td>
                <span class="display_currency" data-currency_symbol="true">
                  {{$detail->total_sales}}
                </span>
                @php
                  $total_sales += $detail->total_sales;
                @endphp
              </td>
            </tr>
            @php
              $total_sales += $detail->total_sales;
            @endphp
          @endforeach
          <tr class="success">
            <th>#</th>
            <th></th>
            <th>
              @lang('lang_v1.grand_total'):
              <span class="display_currency" data-currency_symbol="true">
                {{$total_amount}}
              </span>
            </th>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endif

<script>
  (function () {
    var input = document.querySelector('.cr-product-search');
    var table = document.querySelector('.cr-products-table');
    if (!input || !table) return;
    input.addEventListener('input', function () {
      var q = (this.value || '').toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        if (row.classList.contains('success')) return;
        var text = (row.textContent || '').toLowerCase();
        row.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  })();
</script>
