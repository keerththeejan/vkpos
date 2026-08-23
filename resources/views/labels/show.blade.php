@extends('layouts.app')
@section('title', __('barcode.print_labels'))

@section('content')
@php
	$ldBarcodeMeta = \App\Barcode::query()
		->where(function ($q) {
			$q->where('business_id', request()->session()->get('user.business_id'))
				->orWhereNull('business_id');
		})
		->get()
		->mapWithKeys(function ($b) {
			return [$b->id => [
				'id' => $b->id,
				'name' => $b->name,
				'width_mm' => round((float) $b->width * 25.4, 2),
				'height_mm' => round((float) $b->height * 25.4, 2),
				'stickers_in_one_row' => (int) $b->stickers_in_one_row,
				'col_gap_mm' => round((float) $b->col_distance * 25.4, 2),
				'row_gap_mm' => round((float) $b->row_distance * 25.4, 2),
				'margin_top_mm' => round((float) $b->top_margin * 25.4, 2),
				'margin_left_mm' => round((float) $b->left_margin * 25.4, 2),
				'is_continuous' => (bool) $b->is_continuous,
				'paper_width_mm' => round((float) $b->paper_width * 25.4, 2),
			]];
		});
@endphp

<div class="ld-page no-print">
	<header class="ld-header">
		<h1 class="ld-header__title">
			<i class="fa fa-barcode"></i>
			@lang('barcode.print_labels')
			@show_tooltip(__('tooltip.print_label'))
			<span class="ld-badge"><i class="fa fa-print"></i> Zebra ZD230 · 203 DPI</span>
			<span class="ld-badge ld-badge--target">50×25 mm · 2-up</span>
		</h1>
		<p class="ld-header__subtitle">@lang('barcode.designer_subtitle')</p>
	</header>

	{!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'preview_setting_form', 'onsubmit' => 'return false']) !!}
	<input type="hidden" name="ld_text_lines" id="ld_text_lines" value="2">

	{{-- sticker_layout radios (backend required) — driven by template picker --}}
	<div class="ld-visually-hidden" aria-hidden="true">
		@foreach(['default', 'line1', 'line2', 'line3', 'mfg_line1', 'mfg_line2', 'mfg_line3', 'custom'] as $lv)
			<label><input type="radio" name="sticker_layout" value="{{ $lv }}" @if($lv==='line2') checked @endif></label>
		@endforeach
	</div>

	<div class="ld-workspace">
		<aside class="ld-sidebar">
			<div class="ld-panel">
				<div class="ld-tabs" role="tablist">
					<button type="button" class="ld-tab is-active" data-tab="ld_tab_products"><i class="fa fa-cube"></i> @lang('barcode.designer_tab_products')</button>
					<button type="button" class="ld-tab" data-tab="ld_tab_templates"><i class="fa fa-th"></i> @lang('barcode.designer_tab_templates')</button>
					<button type="button" class="ld-tab" data-tab="ld_tab_fields"><i class="fa fa-font"></i> @lang('barcode.designer_tab_fields')</button>
					<button type="button" class="ld-tab" data-tab="ld_tab_print"><i class="fa fa-cog"></i> @lang('barcode.designer_tab_print')</button>
					<button type="button" class="ld-tab" data-tab="ld_tab_advanced"><i class="fa fa-sliders"></i> @lang('barcode.designer_tab_advanced')</button>
				</div>

				{{-- Products --}}
				<div class="ld-tab-pane is-active ld-panel__body" id="ld_tab_products">
					<div class="ld-search-wrap">
						<i class="fa fa-search"></i>
						{!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_label', 'placeholder' => __('lang_v1.enter_product_name_to_print_labels'), 'autofocus']); !!}
					</div>
					<div class="table-responsive">
						<table class="table table-bordered table-condensed ld-product-table" id="product_table">
							<thead>
								<tr>
									<th>@lang('barcode.products')</th>
									<th>@lang('barcode.no_of_labels')</th>
									@if(request()->session()->get('business.enable_lot_number') == 1)
										<th>@lang('lang_v1.lot_number')</th>
									@endif
									@if(request()->session()->get('business.enable_product_expiry') == 1)
										<th>@lang('product.exp_date')</th>
									@endif
									<th>@lang('lang_v1.packing_date')</th>
									<th>@lang('lang_v1.selling_price_group')</th>
								</tr>
							</thead>
							<tbody>
								@include('labels.partials.show_table_rows', ['index' => 0])
							</tbody>
						</table>
					</div>
				</div>

				{{-- Templates --}}
				<div class="ld-tab-pane ld-panel__body" id="ld_tab_templates">
					<p class="text-muted small">@lang('barcode.designer_templates_help')</p>
					<div class="ld-template-list" id="ld_template_grid"></div>

					<div class="ld-panel__head mt-3"><i class="fa fa-industry"></i> @lang('barcode.designer_pro_layouts')</div>
					<div class="ld-layout-grid mt-2">
						<button type="button" class="ld-template-card" data-set-layout="line1">
							<div class="ld-template-card__icon"><i class="fa fa-minus"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_line1')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="line2">
							<div class="ld-template-card__icon"><i class="fa fa-bars"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_line2')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="line3">
							<div class="ld-template-card__icon"><i class="fa fa-th-list"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_line3')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="mfg_line1">
							<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg1')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="mfg_line2">
							<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg2')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="mfg_line3">
							<div class="ld-template-card__icon"><i class="fa fa-industry"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_mfg3')</div>
						</button>
						<button type="button" class="ld-template-card" data-set-layout="custom">
							<div class="ld-template-card__icon"><i class="fa fa-arrows"></i></div>
							<div class="ld-template-card__name">@lang('barcode.sticker_layout_custom')</div>
						</button>
					</div>
					@include('labels.partials.custom_designer')
				</div>

				{{-- Fields + barcode/font options --}}
				<div class="ld-tab-pane ld-panel__body" id="ld_tab_fields">
					@include('labels.partials.designer.barcode_font_options')
					<hr>
					<p class="text-muted small mb-2">@lang('barcode.info_in_labels')</p>
					@include('labels.partials.designer.field_options')
				</div>

				{{-- Print settings --}}
				<div class="ld-tab-pane ld-panel__body" id="ld_tab_print">
					@include('labels.partials.designer.print_settings')
					<p class="ld-note mt-3">@lang('barcode.designer_size_reference')</p>
					<div class="ld-size-chips" id="ld_size_chips"></div>
				</div>

				{{-- Advanced --}}
				<div class="ld-tab-pane ld-panel__body" id="ld_tab_advanced">
					<div class="ld-panel__head" style="margin:-16px -16px 12px;border-radius:0;"><i class="fa fa-toggle-on"></i> @lang('barcode.designer_advanced_toggles')</div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_barcode')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_barcode" checked><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.print_name')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_name" checked><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_sku')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_sku"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_product_code')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_product_code"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.print_price')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_price" checked><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_logo')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_logo"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_qr')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_qr"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('lang_v1.print_exp_date')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_expiry"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_batch')</span><label class="ld-switch"><input type="checkbox" id="ld_adv_batch"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_show_border')</span><label class="ld-switch"><input type="checkbox" id="ld_preview_border"><span class="ld-switch__slider"></span></label></div>
					<div class="ld-toggle-row"><span>@lang('barcode.designer_rounded_corners')</span><label class="ld-switch"><input type="checkbox" id="ld_preview_rounded" checked><span class="ld-switch__slider"></span></label></div>

					<div class="ld-panel__head mt-4" style="margin-left:-16px;margin-right:-16px;border-radius:0;"><i class="fa fa-list"></i> @lang('barcode.optional_label_fields')</div>
					<div class="mt-2">@include('labels.partials.field_toggles')</div>
				</div>
			</div>
		</aside>

		<main class="ld-preview-panel">
			<div class="ld-preview-toolbar">
				<strong><i class="fa fa-eye"></i> @lang('barcode.live_preview')</strong>
				<span class="ld-preview-meta" id="ld_preview_meta">—</span>
				<span class="text-muted small" id="ld_zoom_label">100%</span>
				<div class="ld-zoom-group">
					@foreach([50, 75, 100, 150, 200] as $z)
						<button type="button" class="ld-zoom-btn @if($z===100) is-active @endif" data-zoom="{{ $z }}">{{ $z }}%</button>
					@endforeach
				</div>
			</div>
			<div class="ld-preview-stage">
				<div id="label_live_preview_loading" class="hide">
					<i class="fa fa-spinner fa-spin"></i> @lang('barcode.updating_preview')
				</div>
				<div class="ld-preview-viewport" id="ld_preview_viewport">
					<div id="preview_box" class="label-live-preview-box"></div>
				</div>
			</div>
			<div class="ld-actions">
				<button type="button" class="ld-btn ld-btn--success" id="ld_btn_print"><i class="fa fa-print"></i> @lang('barcode.designer_print_label')</button>
				<button type="button" class="ld-btn ld-btn--primary" id="ld_btn_print_all"><i class="fa fa-print"></i> @lang('barcode.designer_print_all')</button>
				<button type="button" class="ld-btn ld-btn--outline" id="ld_btn_preview_refresh"><i class="fa fa-refresh"></i> @lang('barcode.preview')</button>
				<button type="button" class="ld-btn ld-btn--outline" id="ld_btn_save_template"><i class="fa fa-save"></i> @lang('barcode.designer_save_template')</button>
				<button type="button" class="ld-btn ld-btn--outline" id="ld_btn_load_template"><i class="fa fa-folder-open"></i> @lang('barcode.designer_load_template')</button>
				<button type="button" class="ld-btn ld-btn--outline" id="ld_btn_duplicate"><i class="fa fa-copy"></i> @lang('barcode.designer_duplicate')</button>
				<button type="button" class="ld-btn ld-btn--outline" id="ld_btn_reset"><i class="fa fa-undo"></i> @lang('barcode.reset_layout')</button>
				<button type="button" class="ld-btn ld-btn--outline ld-visually-hidden" id="labels_preview">@lang('barcode.preview')</button>
			</div>
			<p class="text-muted small mt-2 mb-0">@lang('barcode.live_preview_help')</p>
		</main>
	</div>

	{!! Form::close() !!}
</div>

<script>
	window.LD_BARCODE_META = @json($ldBarcodeMeta);
	window.LD_ZEBRA_TARGET = { width_mm: 50, height_mm: 25, columns: 2, col_gap_mm: 2, row_gap_mm: 2 };
</script>
@stop

@section('css')
	<link rel="stylesheet" href="{{ asset('css/labels-print.css?v=' . $asset_v) }}">
	<link rel="stylesheet" href="{{ asset('css/labels-sticker.css?v=' . $asset_v) }}">
	<link rel="stylesheet" href="{{ asset('css/labels-designer.css?v=' . $asset_v) }}">
@endsection

@section('javascript')
	<script src="{{ asset('js/labels-print-engine.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/labels-custom-designer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/labels-designer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/labels.js?v=' . $asset_v) }}"></script>
@endsection
