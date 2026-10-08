<div id="zl_workspace" class="zl-app">
	<header class="zl-header">
		<div>
			<h1 class="zl-header__title"><i class="fa fa-barcode"></i> Zebra labels</h1>
			<p class="zl-header__subtitle">30 mm × 15 mm stickers, three across. Preview is a guide. Print sends native ZPL to the ZD220.</p>
		</div>
		<div class="zl-header__meta">
			<span class="zl-pill">30 × 15 mm</span>
			<span class="zl-pill">3-up</span>
			<span class="zl-pill">203 DPI</span>
			<span class="zl-pill">ZPL · ZD220</span>
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
					<div class="zl-sku-fact" id="zl_sku_fact">
						<dt>Barcode / SKU</dt>
						<dd class="zl-sku">
							<div class="zl-sku__view" id="zl_sku_view">
								<span id="zl_product_sku">—</span>
								<button type="button" class="zl-sku__edit" id="zl_sku_edit" hidden>
									<i class="fa fa-pencil" aria-hidden="true"></i> Edit
								</button>
							</div>
							<div class="zl-sku__editor" id="zl_sku_editor" hidden>
								<label class="zl-sku__field-label" for="zl_sku_input">SKU / Barcode Data</label>
								<input type="text" id="zl_sku_input" class="form-control" maxlength="255" autocomplete="off" spellcheck="false" autocapitalize="off" inputmode="text" aria-describedby="zl_sku_error">
								<div class="zl-sku__actions">
									<button type="button" class="zl-btn zl-btn--primary" id="zl_sku_save"><i class="fa fa-check" aria-hidden="true"></i> Save</button>
									<button type="button" class="zl-btn" id="zl_sku_cancel"><i class="fa fa-times" aria-hidden="true"></i> Cancel</button>
								</div>
								<p class="zl-sku__error" id="zl_sku_error" role="alert" hidden></p>
							</div>
						</dd>
					</div>
					<div><dt>Price</dt><dd id="zl_product_price">—</dd></div>
					<div><dt>Product</dt><dd id="zl_product_meta">Search to load a product from VKPOS.</dd></div>
				</dl>
			</div>

			<label class="zl-label" for="zl_vertical">Vertical text</label>
			<input type="text" id="zl_vertical" class="form-control" maxlength="80" placeholder="Printed down the label">

			<p class="zl-hint">One print quantity is one row of three stickers. Quantity 1 prints the same product on all three labels.</p>
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

			<h3 id="zl_printer_settings">Printer settings</h3>
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
						<option value="203" selected>203</option>
						<option value="300">300</option>
					</select>
				</div>
				@include('labels.partials.zebra_field', ['id' => 'zl_label_gap_mm', 'label' => 'Gap between labels (mm)', 'step' => '0.1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_left_offset_mm', 'label' => 'Left offset (mm)', 'step' => '0.1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_top_offset_mm', 'label' => 'Top offset (mm)', 'step' => '0.1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_width', 'label' => '^PW print width (dots)', 'step' => '1', 'readonly' => true])
				@include('labels.partials.zebra_field', ['id' => 'zl_height', 'label' => '^LL label length (dots)', 'step' => '1', 'readonly' => true])
			</div>
			<p id="zl_geometry_summary" class="zl-hint"></p>
			<div class="zl-actions">
				<button type="button" class="zl-btn" id="zl_detect_printer">Detect ZD220</button>
			</div>
			<p class="zl-hint">Label size is 30 × 15 mm. The gap is the liner between the three stickers and is included in ^PW and the column origins. ^LL stays at 15 mm so the ZD220 gap sensor feeds each row. Positive left offset moves all three labels right. Positive top offset moves them down. The same shift is applied to every row.</p>

			<h3>Column origins</h3>
			<div class="zl-fields zl-fields--3">
				@include('labels.partials.zebra_field', ['id' => 'zl_col1_x', 'label' => 'Label 1 X', 'readonly' => true])
				@include('labels.partials.zebra_field', ['id' => 'zl_col2_x', 'label' => 'Label 2 X', 'readonly' => true])
				@include('labels.partials.zebra_field', ['id' => 'zl_col3_x', 'label' => 'Label 3 X', 'readonly' => true])
			</div>
			<p class="zl-hint">These origins are calculated. Label 2 is one label width plus the gap. Label 3 is twice that pitch.</p>

			<h3>Barcode</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_x', 'label' => 'Center nudge', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_y', 'label' => 'Barcode Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_width', 'label' => 'Barcode width', 'step' => '0.1'])
				@include('labels.partials.zebra_field', ['id' => 'zl_barcode_height', 'label' => 'Barcode height', 'step' => '1'])
			</div>

			<h3>Product name</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_product_name_x', 'label' => 'Product name X', 'readonly' => true])
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
			<p class="zl-hint">The name comes from the selected product and is centered in each 30 mm label. Long names are shortened with an ellipsis so they stay inside the sticker.</p>

			<h3>SKU</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_sku_x', 'label' => 'SKU X', 'readonly' => true])
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
				@include('labels.partials.zebra_field', ['id' => 'zl_price_x', 'label' => 'Price X', 'readonly' => true])
				@include('labels.partials.zebra_field', ['id' => 'zl_price_y', 'label' => 'Price Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_price_font_size', 'label' => 'Price font size', 'step' => '1'])
			</div>

			<h3>Vertical text</h3>
			<div class="zl-fields">
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_x', 'label' => 'Vertical X', 'axis' => 'x'])
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_y', 'label' => 'Vertical Y', 'axis' => 'y'])
				@include('labels.partials.zebra_field', ['id' => 'zl_vertical_font_size', 'label' => 'Vertical font size', 'step' => '1'])
			</div>
			<p class="zl-hint">Y arrows move 1 dot. Hold Shift for 10 dots. The same Y is used on all three labels. Barcode, number, price, and name stay centered.</p>
		</section>
	</div>

	<section class="zl-card zl-preview-card">
		<div class="zl-preview-head">
			<h2>3-up preview</h2>
			<div class="zl-toggles">
				<label><input type="checkbox" id="zl_show_bounds" checked> Show label boundaries</label>
				<label><input type="checkbox" id="zl_show_grid"> Show grid</label>
				<label><input type="checkbox" id="zl_show_coords"> Show coordinates</label>
			</div>
		</div>
		<p class="zl-hint">Each box is 30:15. The space between boxes is the label gap. This preview is not the print engine.</p>
		<div id="zl_stage" class="zl-stage">
			<div id="zl_scaler">
				<div id="zl_roll" class="zl-roll"></div>
			</div>
		</div>
		<div id="zl_warnings" class="zl-warnings" hidden></div>
		<div class="zl-printbar">
			<label class="zl-qty">
				<span>Print quantity</span>
				<input type="number" id="zl_qty" class="form-control" min="1" max="100" step="1" value="1">
			</label>
			<button type="button" class="zl-btn" id="zl_print_preview">Preview</button>
			<button type="button" class="zl-btn zl-btn--print" id="zl_print"><i class="fa fa-print"></i> Print</button>
			<button type="button" class="zl-btn" id="zl_test_print">Test print</button>
			<button type="button" class="zl-btn" id="zl_view_zpl">View ZPL</button>
			<button type="button" class="zl-btn" id="zl_printer_settings_btn">Printer settings</button>
		</div>
		<p id="zl_qty_summary" class="zl-qty-summary">1 row × 3 labels = 3 physical labels</p>
		<p class="zl-hint">Print and Test print send raw ZPL through QZ Tray. Quantity 10 is ten identical rows, not a shifting layout.</p>
	</section>
</div>

<div id="zl_zpl_modal" class="zl-modal" hidden>
	<div class="zl-modal__panel" role="dialog" aria-labelledby="zl_zpl_title">
		<div class="zl-modal__head">
			<h2 id="zl_zpl_title">Generated ZPL</h2>
			<button type="button" class="zl-btn" id="zl_zpl_close">Close</button>
		</div>
		<pre id="zl_zpl_text" class="zl-zpl"></pre>
	</div>
</div>

<script>
	window.ZL_BOOT = {
		ready: @json((bool) $zebra_ready),
		profiles: @json($label_profiles),
		product: @json($zebra_product),
		queue: @json($zebra_queue),
		defaults: @json($zebra_defaults),
		media: @json($zebra_media),
		canEditSku: @json(auth()->user() && auth()->user()->can('product.update')),
		urls: {
			product: @json(url('/labels/zebra/product')),
			profiles: @json(url('/labels/zebra/profiles')),
			zpl: @json(url('/labels/zebra/zpl')),
			search: @json(url('/purchases/get_products')),
			qz: @json(asset('js/vendor/qz-tray.js'))
		}
	};
</script>
