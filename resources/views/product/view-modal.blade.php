<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content">
		<div class="modal-header">
		    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      <h4 class="modal-title" id="modalTitle">{{$product->name}}</h4>
	    </div>
	    <div class="modal-body">
      		<div class="row">
      			<div class="col-sm-9">
	      			<div class="col-sm-4 invoice-col">
	      				<b>@lang('product.sku'):</b>
						{{$product->sku }}<br>
						<b>@lang('product.brand'): </b>
						{{$product->brand->name ?? '--' }}<br>
						<b>@lang('product.unit'): </b>
						{{$product->unit->short_name ?? '--' }}<br>
						<b>@lang('product.barcode_type'): </b>
						{{$product->barcode_type ?? '--' }}
						@php 
    						$custom_labels = json_decode(session('business.custom_labels'), true);
						@endphp

                        @for($i = 1; $i <= 20; $i++)
                            @php
                                $db_field = 'product_custom_field' . $i;
                                $label = 'custom_field_' .$i;
                            @endphp

                            @if(!empty($product->$db_field))
                                <br/>
                                <b>{{ $custom_labels['product'][$label] ?? '' }}: </b>
                                {{$product->$db_field }}
                            @endif
                        @endfor
						
						<br>
						<strong>@lang('lang_v1.available_in_locations'):</strong>
						@if(count($product->product_locations) > 0)
							{{implode(', ', $product->product_locations->pluck('name')->toArray())}}
						@else
							@lang('lang_v1.none')
						@endif
						@if(!empty($product->media->first())) <br>
							<strong>@lang('lang_v1.product_brochure'):</strong>
							<a href="{{$product->media->first()->display_url}}" download="{{$product->media->first()->display_name}}">
								<span class="label label-info">
									<i class="fas fa-download"></i>
									{{$product->media->first()->display_name}}
								</span>
							</a>
						@endif
	      			</div>

	      			<div class="col-sm-4 invoice-col">
						<b>@lang('product.category'): </b>
						{{$product->category->name ?? '--' }}<br>
						<b>@lang('product.sub_category'): </b>
						{{$product->sub_category->name ?? '--' }}<br>	
						
						<b>@lang('product.manage_stock'): </b>
						@if($product->enable_stock)
							@lang('messages.yes')
						@else
							@lang('messages.no')
						@endif
						<br>
						@if($product->enable_stock)
							<b>@lang('product.alert_quantity'): </b>
							{{$product->alert_quantity ?? '--' }}
						@endif

						@if(!empty($product->warranty))
							<br>
							<b>@lang('lang_v1.warranty'): </b>
							{{$product->warranty->display_name }}
						@endif
	      			</div>
					
	      			<div class="col-sm-4 invoice-col">
	      				<b>@lang('product.expires_in'): </b>
	      				@php
	  						$expiry_array = ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ];
	  					@endphp
	      				@if(!empty($product->expiry_period) && !empty($product->expiry_period_type))
							{{$product->expiry_period}} {{$expiry_array[$product->expiry_period_type]}}
						@else
							{{$expiry_array['']}}
	      				@endif
	      				<br>
						@if($product->weight)
							<b>@lang('lang_v1.weight'): </b>
							{{$product->weight }}<br>
						@endif
						<b>@lang('product.applicable_tax'): </b>
						{{$product->product_tax->name ?? __('lang_v1.none') }}<br>
						@php
							$tax_type = ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')];
						@endphp
						<b>@lang('product.selling_price_tax_type'): </b>
						{{$tax_type[$product->tax_type]  }}<br>
						<b>@lang('product.product_type'): </b>
						@lang('lang_v1.' . $product->type)
						
	      			</div>
	      			<div class="clearfix"></div>
	      			<br>
      				<div class="col-sm-12">
      					{!! $product->product_description !!}
      				</div>
					@if(!empty($product->enable_sr_no) && isset($product->serial_numbers) && $product->serial_numbers->count())
					@php
						$psn_available = $product->serial_numbers->where('status', 'available')->count();
						$psn_sold = $product->serial_numbers->where('status', 'sold')->count();
						$psn_reserved = $product->serial_numbers->where('status', 'reserved')->count();
						$psn_damaged = $product->serial_numbers->where('status', 'damaged')->count();
						$psn_returned = $product->serial_numbers->where('status', 'returned')->count();
					@endphp
					<div class="col-sm-12">
						<style>
							.psn-view-section{margin-top:16px;padding:16px;background:#F8FAFC;border:1px solid #E5E7EB;border-radius:14px}
							.psn-view-section h4{margin:0 0 12px;font-size:15px;font-weight:800;color:#0F172A}
							.psn-view-kpis{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
							.psn-view-kpi{flex:1 1 110px;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:10px 12px;text-align:center}
							.psn-view-kpi .n{display:block;font-size:18px;font-weight:800;color:#0F172A}
							.psn-view-kpi .l{display:block;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase}
							.psn-view-kpi.is-available .n{color:#059669}.psn-view-kpi.is-sold .n{color:#2563EB}
							.psn-view-kpi.is-reserved .n{color:#D97706}.psn-view-kpi.is-damaged .n,.psn-view-kpi.is-returned .n{color:#DC2626}
							.psn-view-list{max-height:180px;overflow:auto;background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:8px 10px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
							.psn-view-item{display:flex;justify-content:space-between;gap:8px;padding:4px 0;border-bottom:1px dashed #E5E7EB}
							.psn-view-item:last-child{border-bottom:0}
						</style>
						<div class="psn-view-section">
							<h4><i class="fas fa-barcode"></i> IMEI / Serial Numbers</h4>
							<div class="psn-view-kpis">
								<div class="psn-view-kpi is-available"><span class="n">{{ $psn_available }}</span><span class="l">Available</span></div>
								<div class="psn-view-kpi is-sold"><span class="n">{{ $psn_sold }}</span><span class="l">Sold</span></div>
								<div class="psn-view-kpi is-reserved"><span class="n">{{ $psn_reserved }}</span><span class="l">Reserved</span></div>
								<div class="psn-view-kpi is-damaged"><span class="n">{{ $psn_damaged }}</span><span class="l">Damaged</span></div>
								<div class="psn-view-kpi is-returned"><span class="n">{{ $psn_returned }}</span><span class="l">Returned</span></div>
							</div>
							<div class="psn-view-list">
								@foreach($product->serial_numbers->sortBy('serial_number') as $sn)
									<div class="psn-view-item">
										<span>{{ $sn->serial_number }}</span>
										<span class="label label-{{ $sn->status === 'available' ? 'success' : ($sn->status === 'sold' ? 'primary' : ($sn->status === 'reserved' ? 'warning' : 'danger')) }}">{{ ucfirst($sn->status) }}</span>
									</div>
								@endforeach
							</div>
						</div>
					</div>
					@endif
	      		</div>
      			<div class="col-sm-3 col-md-3 invoice-col">
      				<div class="thumbnail">
      					<img src="{{$product->image_url}}" alt="Product image">
      				</div>
      			</div>
      		</div>
      		@if($rack_details->count())
      		@if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
      			<div class="row">
      				<div class="col-md-12">
      					<h4>@lang('lang_v1.rack_details'):</h4>
      				</div>
      				<div class="col-md-12">
      					<div class="table-responsive">
      					<table class="table table-condensed bg-gray">
      						<tr class="bg-green">
      							<th>@lang('business.location')</th>
      							@if(session('business.enable_racks'))
      								<th>@lang('lang_v1.rack')</th>
      							@endif
      							@if(session('business.enable_row'))
      								<th>@lang('lang_v1.row')</th>
      							@endif
      							@if(session('business.enable_position'))
      								<th>@lang('lang_v1.position')</th>
      							@endif
      							</tr>
      						@foreach($rack_details as $rd)
      							<tr>
	      							<td>{{$rd->name}}</td>
	      							@if(session('business.enable_racks'))
	      								<td>{{$rd->rack}}</td>
	      							@endif
	      							@if(session('business.enable_row'))
	      								<td>{{$rd->row}}</td>
	      							@endif
	      							@if(session('business.enable_position'))
	      								<td>{{$rd->position}}</td>
	      							@endif
      							</tr>
      						@endforeach
      					</table>
      					</div>
      				</div>
      			</div>
      		@endif
      		@endif
      		@if($product->type == 'single')
      			@include('product.partials.single_product_details')
      		@elseif($product->type == 'variable')
      			@include('product.partials.variable_product_details')
      		@elseif($product->type == 'combo')
      			@include('product.partials.combo_product_details')
      		@endif
      		@if($product->enable_stock == 1)
	      		<div class="row">
	      			<div class="col-md-12">
	      				<strong>@lang('lang_v1.product_stock_details')</strong>
	      			</div>
	      			<div class="col-md-12" id="view_product_stock_details" data-product_id="{{$product->id}}">
	      			</div>
	      		</div>
      		@endif
      	</div>
      	<div class="modal-footer">
      		<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
	        aria-label="Print" 
	          onclick="$(this).closest('div.modal').printThis();">
	        <i class="fa fa-print"></i> @lang( 'messages.print' )
	      </button>
	      	<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
	</div>
</div>
