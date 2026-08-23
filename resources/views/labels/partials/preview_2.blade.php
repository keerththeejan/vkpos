@php
    $stickers_per_row = max(1, (int) $barcode_details->stickers_in_one_row);
    $ld_cols = (int) request()->get('ld_print_columns', 0);
    if ($ld_cols >= 1 && $ld_cols <= 3) {
        $stickers_per_row = $ld_cols;
    }
    $label_w_mm = round((float) $barcode_details->width * 25.4, 2);
    $label_h_mm = round((float) $barcode_details->height * 25.4, 2);
    $paper_w_mm = round((float) $paper_width * 25.4, 2);
    $paper_h_mm = round((float) $paper_height * 25.4, 2);
    $col_gap_mm = round((float) $barcode_details->col_distance * 25.4, 2);
    $row_gap_mm = round((float) $barcode_details->row_distance * 25.4, 2);
    $margin_top_mm = round((float) $margin_top * 25.4, 2);
    $margin_left_mm = round((float) $margin_left * 25.4, 2);
    $is_continuous = !empty($barcode_details->is_continuous);
    $is_live_preview = request()->get('live_preview');
    $ld_w = request()->get('ld_print_width');
    $ld_h = request()->get('ld_print_height');
    $ld_col_gap = request()->get('ld_print_col_gap');
    $ld_row_gap = request()->get('ld_print_row_gap');
    $ld_mtop = request()->get('ld_print_margin_top');
    $ld_mleft = request()->get('ld_print_margin_left');
    if ($ld_col_gap !== null && $ld_col_gap !== '' && is_numeric($ld_col_gap)) {
        $col_gap_mm = round((float) $ld_col_gap, 2);
    }
    if ($ld_row_gap !== null && $ld_row_gap !== '' && is_numeric($ld_row_gap)) {
        $row_gap_mm = round((float) $ld_row_gap, 2);
    }
    if ($ld_mtop !== null && $ld_mtop !== '' && is_numeric($ld_mtop)) {
        $margin_top_mm = round((float) $ld_mtop, 2);
    }
    if ($ld_mleft !== null && $ld_mleft !== '' && is_numeric($ld_mleft)) {
        $margin_left_mm = round((float) $ld_mleft, 2);
    }

    $raw_w_mm = round((float) $barcode_details->width * 25.4, 2);
    if ($ld_w && is_numeric($ld_w) && (float) $ld_w > 0) {
        $label_w_mm = round((float) $ld_w, 2);
    } elseif ($stickers_per_row > 1) {
        $label_w_mm = round($raw_w_mm / $stickers_per_row, 2);
    }

    if ($ld_h && is_numeric($ld_h) && (float) $ld_h > 0) {
        $label_h_mm = round((float) $ld_h, 2);
    }

    if ($ld_w || $ld_cols || $ld_col_gap !== null) {
        $paper_w_mm = round(
            ($label_w_mm * $stickers_per_row)
            + ($col_gap_mm * max(0, $stickers_per_row - 1))
            + ($margin_left_mm * 2),
            2
        );
    }

    $barcode_max_h_mm = round($label_h_mm * 0.38, 2);
    $safe_pad_mm = 0.5;
    $print_page_w_mm = round(
        ($label_w_mm * $stickers_per_row) + ($col_gap_mm * max(0, $stickers_per_row - 1)),
        2
    );
    $print_page_h_mm = $label_h_mm;
    $labels_on_page = count($page_products);
@endphp

@if(!empty($is_first))
    @if(empty($is_live_preview))
<!DOCTYPE html>
<html lang="en" class="thermal-print-root ld-print-zebra" style="--ld-label-w-mm: {{ $label_w_mm }}mm; --ld-label-h-mm: {{ $label_h_mm }}mm;">
<head>
    <meta charset="utf-8">
    <title>{{ __('barcode.print_labels') }}</title>
    @endif
    <link rel="stylesheet" href="{{ asset('css/labels-sticker.css') }}?v={{ $asset_v ?? time() }}">
    <link rel="stylesheet" href="{{ asset('css/labels-print-zebra.css') }}?v={{ $asset_v ?? time() }}">
    <style>
        @if(empty($is_live_preview))
        @page {
            size: {{ $label_w_mm }}mm {{ $label_h_mm }}mm;
            margin: 0;
        }
        html.thermal-print-root,
        html.thermal-print-root body.thermal-print-body {
            width: {{ $label_w_mm }}mm;
            height: {{ $label_h_mm }}mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            box-sizing: border-box;
        }
        @endif
    </style>
    @if(empty($is_live_preview))
</head>
<body class="thermal-print-body thermal-print-body--sheet ld-print-zebra-body" style="width:{{ $label_w_mm }}mm;margin:0;padding:0;overflow:hidden;box-sizing:border-box;">
    @endif
@endif

<div class="thermal-label-sheet thermal-label-ctx thermal-label-ctx-0 {{ $is_continuous ? 'thermal-label-sheet--continuous' : 'thermal-label-sheet--sheet' }} {{ $stickers_per_row > 1 ? 'thermal-label-sheet--multi-col' : 'thermal-label-sheet--single-col' }}"
    style="--label-w-mm: {{ $label_w_mm }}; --label-h-mm: {{ $label_h_mm }}; --paper-w-mm: {{ $print_page_w_mm }}; --paper-h-mm: {{ $print_page_h_mm }}; --col-gap-mm: {{ $col_gap_mm }}; --row-gap-mm: {{ $row_gap_mm }}; --margin-top-mm: 0; --margin-left-mm: 0; --barcode-max-h-mm: {{ $barcode_max_h_mm }}; --ld-safe-pad-mm: {{ $safe_pad_mm }}; --ld-dpi: 203; --ld-dpm: 8;"
    data-label-w-mm="{{ $label_w_mm }}"
    data-label-h-mm="{{ $label_h_mm }}"
    data-paper-w-mm="{{ $print_page_w_mm }}"
    data-paper-h-mm="{{ $print_page_h_mm }}"
    data-col-gap-mm="{{ $col_gap_mm }}"
    data-row-gap-mm="{{ $row_gap_mm }}"
    data-margin-top-mm="{{ $margin_top_mm }}"
    data-margin-left-mm="{{ $margin_left_mm }}"
    data-stickers-per-row="{{ $stickers_per_row }}"
    data-labels-on-page="{{ $labels_on_page }}"
    data-continuous="{{ $is_continuous ? '1' : '0' }}">

<table class="thermal-label-table" cellspacing="0" cellpadding="0">
@foreach($page_products as $page_product)
	@if($loop->index % $stickers_per_row === 0)
	<tr class="thermal-label-row">
	@endif
		<td class="thermal-label-cell">
			@php
				$lw = $label_w_mm;
				$lh = $label_h_mm;
				$bmax = $barcode_max_h_mm;
			@endphp
			<div class="label-sticker-wrap label-sticker-wrap--{{ request()->get('design_template') ?: ($sticker_layout ?? 'default') }}"
				data-width-mm="{{ $lw }}"
				data-height-mm="{{ $lh }}"
				data-barcode-max-mm="{{ $bmax }}"
				data-text-align="{{ request()->get('ld_text_align', 'center') }}"
				style="--label-w-mm: {{ $lw }}mm; --label-h-mm: {{ $lh }}mm; --barcode-max-h-mm: {{ $bmax }}mm; --ld-safe-pad-mm: {{ $safe_pad_mm }}mm; --ld-align: {{ request()->get('ld_text_align', 'center') }};">
				<div class="label-sticker-inner">
					@php
						$designTemplate = request()->get('design_template', '');
						$layoutFile = $layout_partial ?? 'default';
						if ($designTemplate && view()->exists('labels.partials.layouts.' . $designTemplate)) {
							$layoutFile = $designTemplate;
						}
					@endphp
					@include('labels.partials.layouts.' . $layoutFile)
				</div>
			</div>
		</td>
	@if($loop->iteration % $stickers_per_row === 0 || $loop->last)
	</tr>
	@endif
@endforeach
</table>
</div>

@if(!empty($is_last))
    @if(empty($is_live_preview))
<script src="{{ asset('js/labels-print-engine.js') }}?v={{ $asset_v ?? time() }}"></script>
<script>
    (function initThermalPrint() {
        if (window.LabelPrintEngine) {
            window.LabelPrintEngine.prepareForPrint(document);
            window.LabelPrintEngine.lockRenderedStyles(document);
            document.documentElement.setAttribute('data-print-ready', '1');
        } else {
            setTimeout(initThermalPrint, 15);
        }
    })();
</script>
</body>
</html>
    @endif
@endif
