@extends('layouts.app')
@section('title', __('barcode.print_labels'))

@section('content')
<section class="content zb-page">
	<div class="zb-head">
		<h1><i class="fa fa-barcode"></i> @lang('barcode.print_labels')</h1>
		<p class="zb-lead">@lang('barcode.zebra_subtitle')</p>
		<div id="zb-status" class="zb-status" role="status"><i></i> <span>QZ Tray not connected</span></div>
	</div>

	<div id="zb-alert" class="alert zb-alert" role="alert"></div>

	<div class="row">
		<div class="col-lg-5">
			<div class="zb-card">
				<h2>@lang('barcode.zebra_product')</h2>
				<div class="zb-body">
					@if(!empty($zebraConfig['queue']) && count($zebraConfig['queue']) > 1)
						<ul class="zb-queue" id="zb-queue"></ul>
					@endif
					<div class="zb-search">
						<label for="zb-search">Search product</label>
						<input type="text" id="zb-search" class="form-control" placeholder="Name, SKU, barcode, or product code" autocomplete="off">
						<div id="zb-results" class="zb-results"></div>
					</div>
					<div class="zb-grid" style="margin-top:10px;">
						<div class="zb-wide">
							<label for="zb-name">Product name</label>
							<input type="text" id="zb-name" class="form-control" readonly>
						</div>
						<div>
							<label for="zb-sku">SKU</label>
							<input type="text" id="zb-sku" class="form-control" readonly>
						</div>
						<div>
							<label for="zb-barcode">Barcode</label>
							<input type="text" id="zb-barcode" class="form-control" readonly>
						</div>
						<div>
							<label for="zb-code">Product code</label>
							<input type="text" id="zb-code" class="form-control" readonly>
						</div>
						<div>
							<label for="zb-price">Selling price</label>
							<input type="text" id="zb-price" class="form-control" readonly>
						</div>
						<div class="zb-wide">
							<label for="zb-vertical">Vertical text</label>
							<input type="text" id="zb-vertical" class="form-control" maxlength="80" placeholder="Optional text printed vertically">
						</div>
						<div>
							<label for="zb-qty">Quantity</label>
							<input type="number" id="zb-qty" class="form-control" min="1" max="500" value="1">
						</div>
					</div>
					<div class="zb-checks">
						<label><input type="checkbox" id="zb-show-barcode" checked> Barcode</label>
						<label><input type="checkbox" id="zb-show-sku" checked> SKU</label>
						<label><input type="checkbox" id="zb-show-price" checked> Price</label>
						<label><input type="checkbox" id="zb-show-vertical" checked> Vertical text</label>
						<label><input type="checkbox" id="zb-price-prefix"> Prefix with “Price”</label>
					</div>
					<p id="zb-product-error" class="text-danger zb-hint" style="display:none;"></p>
				</div>
			</div>

			<div class="zb-card">
				<h2>@lang('barcode.zebra_print_settings')</h2>
				<div class="zb-body">
					<div class="zb-grid">
						<div class="zb-wide">
							<label for="zb-printer">Printer</label>
							<select id="zb-printer" class="form-control">
								<option value="">Select a printer</option>
							</select>
						</div>
						<div>
							<label for="zb-mode">Print mode</label>
							<select id="zb-mode" class="form-control">
								<option value="row">@lang('barcode.zebra_mode_row')</option>
								<option value="individual">@lang('barcode.zebra_mode_individual')</option>
							</select>
						</div>
						<div>
							<label for="zb-copies">Copies</label>
							<input type="number" id="zb-copies" class="form-control" min="1" max="20" value="1">
						</div>
					</div>
					<p id="zb-job" class="zb-hint"></p>
					<div class="zb-actions">
						<button type="button" class="btn btn-default" id="zb-detect"><i class="fa fa-search"></i> @lang('barcode.zebra_detect_printers')</button>
						<button type="button" class="btn btn-default" id="zb-test"><i class="fa fa-flask"></i> @lang('barcode.zebra_test_print')</button>
						<button type="button" class="btn btn-primary" id="zb-print" disabled><i class="fa fa-print"></i> @lang('barcode.zebra_print_labels')</button>
					</div>
				</div>
			</div>

			<div class="zb-card">
				<h2>@lang('barcode.zebra_layout_settings')</h2>
				<div class="zb-body">
					<div class="zb-grid">
						<div>
							<label for="zb-col1">Column 1 base X</label>
							<input type="number" id="zb-col1" class="form-control zb-layout" data-k="col1X" value="{{ $zebraConfig['defaults']['col1X'] }}">
						</div>
						<div>
							<label for="zb-col2">Column 2 base X</label>
							<input type="number" id="zb-col2" class="form-control zb-layout" data-k="col2X" value="{{ $zebraConfig['defaults']['col2X'] }}">
						</div>
						<div>
							<label for="zb-col3">Column 3 base X</label>
							<input type="number" id="zb-col3" class="form-control zb-layout" data-k="col3X" value="{{ $zebraConfig['defaults']['col3X'] }}">
						</div>
						<div>
							<label for="zb-bc-x">Barcode offset X</label>
							<input type="number" id="zb-bc-x" class="form-control zb-layout" data-k="barcode.x" value="{{ $zebraConfig['defaults']['barcode']['x'] }}">
						</div>
						<div>
							<label for="zb-bc-y">Barcode offset Y</label>
							<input type="number" id="zb-bc-y" class="form-control zb-layout" data-k="barcode.y" value="{{ $zebraConfig['defaults']['barcode']['y'] }}">
						</div>
						<div>
							<label for="zb-bc-w">Barcode width</label>
							<input type="number" id="zb-bc-w" class="form-control zb-layout" data-k="barcode.width" step="0.1" min="1" max="10" value="{{ $zebraConfig['defaults']['barcode']['width'] }}">
						</div>
						<div>
							<label for="zb-bc-h">Barcode height</label>
							<input type="number" id="zb-bc-h" class="form-control zb-layout" data-k="barcode.height" min="10" max="120" value="{{ $zebraConfig['defaults']['barcode']['height'] }}">
						</div>
						<div>
							<label for="zb-sku-x">SKU offset X</label>
							<input type="number" id="zb-sku-x" class="form-control zb-layout" data-k="sku.x" value="{{ $zebraConfig['defaults']['sku']['x'] }}">
						</div>
						<div>
							<label for="zb-sku-y">SKU offset Y</label>
							<input type="number" id="zb-sku-y" class="form-control zb-layout" data-k="sku.y" value="{{ $zebraConfig['defaults']['sku']['y'] }}">
						</div>
						<div>
							<label for="zb-price-x">Price offset X</label>
							<input type="number" id="zb-price-x" class="form-control zb-layout" data-k="price.x" value="{{ $zebraConfig['defaults']['price']['x'] }}">
						</div>
						<div>
							<label for="zb-price-y">Price offset Y</label>
							<input type="number" id="zb-price-y" class="form-control zb-layout" data-k="price.y" value="{{ $zebraConfig['defaults']['price']['y'] }}">
						</div>
						<div>
							<label for="zb-vert-x">Vertical text offset X</label>
							<input type="number" id="zb-vert-x" class="form-control zb-layout" data-k="vertical.x" value="{{ $zebraConfig['defaults']['vertical']['x'] }}">
						</div>
						<div>
							<label for="zb-vert-y">Vertical text offset Y</label>
							<input type="number" id="zb-vert-y" class="form-control zb-layout" data-k="vertical.y" value="{{ $zebraConfig['defaults']['vertical']['y'] }}">
						</div>
					</div>
					<div class="zb-actions">
						<button type="button" class="btn btn-primary" id="zb-save"><i class="fa fa-save"></i> @lang('barcode.zebra_save_layout')</button>
						<button type="button" class="btn btn-default" id="zb-reset">@lang('barcode.zebra_reset_layout')</button>
					</div>
					<p class="zb-hint">Saved in this browser as {{ $zebraConfig['settingsKey'] }}. Print a test label, adjust X/Y, then save.</p>
				</div>
			</div>
		</div>

		<div class="col-lg-7">
			<div class="zb-card">
				<h2>@lang('barcode.zebra_live_preview')</h2>
				<div class="zb-body">
					<div class="zb-preview-meta">
						<span id="zb-preview-caption">800 × 140 dots · 203 DPI · Zebra ZD230</span>
						<label id="zb-preview-row-wrap" style="display:none;margin:0;">
							Preview
							<select id="zb-preview-row" class="form-control input-sm" style="display:inline-block;width:auto;height:28px;">
								<option value="first">First row</option>
								<option value="last">Last row</option>
							</select>
						</label>
					</div>
					<div class="zb-preview-scroll">
						<div id="zb-scale-host" class="zb-scale-host">
							<div id="zb-stage" class="zb-stage" aria-label="Label preview 800 by 140 dots">
								<div class="zb-sticker" data-col="0"><svg class="zb-bc"></svg><div class="zb-sku"></div><div class="zb-price"></div><div class="zb-vert"></div><span class="zb-col-index">1</span></div>
								<div class="zb-sticker" data-col="1"><svg class="zb-bc"></svg><div class="zb-sku"></div><div class="zb-price"></div><div class="zb-vert"></div><span class="zb-col-index">2</span></div>
								<div class="zb-sticker" data-col="2"><svg class="zb-bc"></svg><div class="zb-sku"></div><div class="zb-price"></div><div class="zb-vert"></div><span class="zb-col-index">3</span></div>
							</div>
						</div>
					</div>
					<p id="zb-layout-note" class="zb-hint"></p>
					@if(!empty($zebraConfig['debug']))
						<button type="button" class="btn btn-default btn-sm" id="zb-show-zpl" style="margin-top:8px;">@lang('barcode.zebra_show_zpl')</button>
						<textarea id="zb-zpl" class="form-control zb-zpl" readonly placeholder="Generated ZPL appears here"></textarea>
					@endif
				</div>
			</div>
		</div>
	</div>
</section>
@endsection

@section('css')
	<link rel="stylesheet" href="{{ asset('css/labels-zebra.css?v=' . $asset_v) }}">
@endsection

@section('javascript')
	<script>
		window.ZB_CONFIG = @json($zebraConfig);
	</script>
	<script src="{{ asset('js/vendor/JsBarcode.all.min.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/vendor/qz-tray.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/labels-zebra.js?v=' . $asset_v) }}"></script>
@endsection
