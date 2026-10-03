<div id="zl_workspace" class="zl-app">
	<header class="zl-header">
		<div>
			<h1 class="zl-header__title"><i class="fa fa-barcode"></i> Zebra labels</h1>
			<p class="zl-header__subtitle">Search a VKPOS product, align three labels, save the profile, then print native ZPL.</p>
		</div>
		<div class="zl-header__meta">
			<span class="zl-pill">800 × 140 dots</span>
			<span class="zl-pill">3 labels per row</span>
			<span class="zl-pill">CODE128</span>
		</div>
	</header>

	<div id="zl_status" class="zl-status" role="status" aria-live="polite"></div>

	@if(empty($zebra_ready))
		<div class="zl-status is-error">Label profiles are not available yet. The sheet designer below still works. Reload this page after the database update.</div>
	@endif

	<div class="zl-grid-page">
		<section class="zl-card">
			<h2>Product</h2>
			<label class="zl-label" for="zl_search">Search product</label>
			<div class="zl-search">
				<i class="fa fa-search"></i>
				<input type="text" id="zl_search" class="form-control" placeholder="Name, SKU, or barcode" autocomplete="off" autofocus>
			</div>
			<div id="zl_queue" class="zl-queue"></div>

			<label class="zl-label" for="zl_price_group">Selling price</label>
			<select id="zl_price_group" class="form-control">
				<option value="">Default selling price</option>
				@foreach($price_groups as $groupId => $groupName)
					<option value="{{ $groupId }}">{{ $groupName }}</option>
				@endforeach
			</select>

			<div class="zl-product" id="zl_product_card">
				<div class="zl-product__name" id="zl_product_name">No product selected</div>
				<dl class="zl-facts">
					<div><dt>Barcode / SKU</dt><dd id="zl_product_sku">—</dd></div>
					<div><dt>Price</dt><dd id="zl_product_price">—</dd></div>
					<div><dt>Product</dt><dd id="zl_product_meta">Search to load a product from VKPOS.</dd></div>
				</dl>
			</div>

			<label class="zl-label" for="zl_vertical">Vertical text</label>
			<input type="text" id="zl_vertical" class="form-control" maxlength="80" placeholder="Printed down the label">

			<label class="zl-label" for="zl_qty">Print quantity (rows)</label>
			<input type="number" id="zl_qty" class="form-control" min="1" max="100" step="1" value="1">
			<p id="zl_qty_summary" class="zl-qty-summary">1 row × 3 labels = 3 physical labels</p>
		</section>

		<section class="zl-card">
			<h2>Label profile</h2>
			<div class="zl-profile-row">
				<div>
					<label class="zl-label" for="zl_profile">Saved layouts</label>
					<select id="zl_profile" class="form-control"></select>
				</div>
				<div>
					<label class="zl-label" for="zl_profile_name">Profile name</label>
					<input type="text" id="zl_profile_name" class="form-control" maxlength="80">
				</div>
			</div>
			<div class="zl-actions zl-actions--wrap">
				<button type="button" class="zl-btn" id="zl_load">Load</button>
				<button type="button" class="zl-btn zl-btn--primary" id="zl_save">Save layout</button>
				<button type="button" class="zl-btn" id="zl_duplicate">Duplicate</button>
				<button type="button" class="zl-btn" id="zl_rename">Rename</button>
				<button type="button" class="zl-btn" id="zl_delete">Delete</button>
				<button type="button" class="zl-btn" id="zl_default">Set as default</button>
				<button type="button" class="zl-btn" id="zl_reset">Reset layout</button>
			</div>
			<p class="zl-hint">Alignment is stored for this business. Preview updates immediately. Nothing is saved until you press Save layout.</p>

			<h3>Printer</h3>
			<div class="zl-fields zl-fields--3">
				<div class="zl-field zl-field--wide">
					<span class="zl-field__label">Printer name</span>
					<input type="text" id="zl_printer_name" class="form-control" list="zl_printer_list" maxlength="120" autocomplete="off">
					<datalist id="zl_printer_list">
						<option value="ZDesigner ZD220-203dpi ZPL"></option>
						<option value="ZDesigner ZD230-203dpi ZPL"></option>
						@foreach($printer_names as $printerName)
							<option value="{{ $printerName }}"></option>
						@endforeach
					</datalist>
				</div>
				<div class="zl-field">
					<span class="zl-field__label">Printer DPI</span>
					<select id="zl_printer_dpi" class="form-control">
						<option value="203">203</option>
						<option value="300">300</option>
					</select>
				</div>
				@include('labels.partials.zebra_field', ['id' => 'zl_width', 'label' => 'Print width', 'step' => '1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_height', 'label' => 'Print height', 'step' => '1'])
			</div>

			<h3>Column base X</h3>
			<div class="zl-fields zl-fields--3">
				@include('labels.partials.zebra_field', ['id' => 'zl_col1_x', 'label' => 'Column 1', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_col2_x', 'label' => 'Column 2', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_col3_x', 'label' => 'Column 3', 'axis' => 'x'])
			</div>

			<h3>Barcode</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_x', 'label' => 'Barcode X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_y', 'label' => 'Barcode Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_width', 'label' => 'Barcode width', 'step' => '0.1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_height', 'label' => 'Barcode height', 'step' => '1'])
			</div>

			<h3>Product name</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_x', 'label' => 'Product name X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_y', 'label' => 'Product name Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_font_size', 'label' => 'Font size', 'step' => '1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_font_width', 'label' => 'Font width', 'step' => '1'])
				<div class="zl-field">
					<span class="zl-field__label">Font weight</span>
					<select id="zl_product_name_font_weight" class="form-control">
						<option value="bold">Bold</option>
						<option value="normal">Normal</option>
					</select>
				</div>
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_max_width', 'label' => 'Maximum width', 'step' => '1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_max_lines', 'label' => 'Maximum lines', 'step' => '1'])
				<div class="zl-field">
					<span class="zl-field__label">Alignment</span>
					<select id="zl_product_name_align" class="form-control">
						<option value="left">Left</option>
						<option value="center" selected>Center</option>
						<option value="right">Right</option>
					</select>
				</div>
			</div>
			<div class="zl-toggles">
				<label><input type="checkbox" id="zl_product_name_show" checked> Show product name</label>
				<label><input type="checkbox" id="zl_product_name_wrap"> Wrap long names</label>
			</div>
			<p class="zl-hint">The name is taken from the selected product. It is aligned inside each label, at column base X + Product name X. Long names stay inside the maximum width.</p>

			<h3>SKU</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_sku_x', 'label' => 'SKU X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_sku_y', 'label' => 'SKU Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_sku_font_size', 'label' => 'SKU font size', 'step' => '1'])
				<div class="zl-field">
					<span class="zl-field__label">SKU font weight</span>
					<select id="zl_sku_font_weight" class="form-control">
						<option value="bold">Bold</option>
						<option value="normal">Normal</option>
					</select>
				</div>
			</div>

			<h3>Price</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_price_x', 'label' => 'Price X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_price_y', 'label' => 'Price Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_price_font_size', 'label' => 'Price font size', 'step' => '1'])
			</div>

			<h3>Vertical text</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_x', 'label' => 'Vertical X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_y', 'label' => 'Vertical Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_font_size', 'label' => 'Vertical font size', 'step' => '1'])
			</div>
			<p class="zl-hint">Arrow buttons move 1 dot. Hold Shift for 10 dots. Final print position is column base X + offset.</p>
		</section>
	</div>

	<section class="zl-card zl-preview-card">
		<div class="zl-preview-head">
			<h2>Configuration preview</h2>
			<div class="zl-toggles">
				<label><input type="checkbox" id="zl_show_bounds" checked> Show label boundaries</label>
				<label><input type="checkbox" id="zl_show_grid"> Show grid</label>
				<label><input type="checkbox" id="zl_show_coords"> Show coordinates</label>
			</div>
		</div>
		<div id="zl_stage" class="zl-stage">
			<div id="zl_scaler">
				<div id="zl_canvas" class="zl-canvas"></div>
			</div>
		</div>
		<div id="zl_warnings" class="zl-warnings" hidden></div>
		<div class="zl-printbar">
			<button type="button" class="zl-btn" id="zl_print_preview">Print preview</button>
			<button type="button" class="zl-btn zl-btn--print" id="zl_print"><i class="fa fa-print"></i> PRINT LABELS</button>
		</div>
		<p class="zl-hint">Print preview shows only the label. Press Esc to close it. PRINT LABELS uses the same print window as the sheet designer.</p>
	</section>
</div>

<script>
	window.ZL_BOOT = {
		ready: @json((bool) $zebra_ready),
		profiles: @json($label_profiles),
		product: @json($zebra_product),
		queue: @json($zebra_queue),
		defaults: @json($zebra_defaults),
		urls: {
			product: @json(url('/labels/zebra/product')),
			profiles: @json(url('/labels/zebra/profiles')),
			zpl: @json(url('/labels/zebra/zpl')),
			search: @json(url('/purchases/get_products')),
			qz: @json(asset('js/vendor/qz-tray.js'))
		}
	};
</script>
