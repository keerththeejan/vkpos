@extends('layouts.app')
@section('title', __('lang_v1.update_product_price'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/update-price-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@php
    $upp_business = session('business.name') ?? 'Business';
    $upp_now = \Carbon\Carbon::now()->timezone(session('business.time_zone') ?? config('app.timezone'))->format('d M Y, h:i A');
@endphp

<section class="content upp-shell">
    {{-- Sticky Header --}}
    <div class="upp-header" role="banner">
        <div class="upp-header-left">
            <h1>@lang('lang_v1.update_product_price')</h1>
            <p class="upp-subtitle">@lang('lang_v1.import_export_product_price')</p>
            <div class="upp-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('lang_v1.update_product_price')</span>
            </div>
            <div class="upp-meta-row">
                <span class="upp-chip"><i class="fas fa-building"></i> {{ $upp_business }}</span>
                <span class="upp-chip"><i class="fas fa-clock"></i> Last opened: {{ $upp_now }}</span>
            </div>
        </div>
        <div class="upp-header-actions">
            <div class="upp-search-wrap" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="upp_page_search" class="form-control" placeholder="Search instructions…" aria-label="Search page content" autocomplete="off">
            </div>
            <button type="button" class="upp-btn upp-btn-ghost" id="upp_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="upp-btn upp-btn-ghost" id="upp_refresh_page" title="Refresh" aria-label="Refresh page">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="upp-btn upp-btn-ghost" id="upp_print_page" title="Print" aria-label="Print page">
                <i class="fas fa-print"></i>
            </button>
            <a class="upp-btn upp-btn-ghost" href="{{ action([\App\Http\Controllers\SellingPriceGroupController::class, 'index']) }}" title="Settings" aria-label="Price group settings">
                <i class="fas fa-cog"></i>
            </a>
            <a href="{{ action([\App\Http\Controllers\SellingPriceGroupController::class, 'export']) }}"
               class="tw-dw-btn tw-dw-btn-primary tw-text-white upp-btn upp-btn-primary"
               id="upp_export_btn">
                <i class="fas fa-file-export"></i> @lang('lang_v1.export_product_prices')
            </a>
        </div>
    </div>

    {{-- Notifications (existing session flash — preserved) --}}
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible upp-alert upp-alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Summary / Workflow KPI Cards (visual only — no business logic) --}}
    <div class="upp-kpi-grid" aria-label="Pricing workflow overview">
        <div class="upp-kpi tone-blue">
            <div class="upp-kpi-icon"><i class="fas fa-file-excel"></i></div>
            <span class="upp-kpi-label">Export</span>
            <span class="upp-kpi-value">XLSX</span>
            <span class="upp-kpi-hint">Download current prices</span>
        </div>
        <div class="upp-kpi tone-green">
            <div class="upp-kpi-icon"><i class="fas fa-edit"></i></div>
            <span class="upp-kpi-label">Edit Offline</span>
            <span class="upp-kpi-value">Excel</span>
            <span class="upp-kpi-hint">Update selling & group prices</span>
        </div>
        <div class="upp-kpi tone-orange">
            <div class="upp-kpi-icon"><i class="fas fa-upload"></i></div>
            <span class="upp-kpi-label">Import</span>
            <span class="upp-kpi-value">Bulk</span>
            <span class="upp-kpi-hint">Upload updated workbook</span>
        </div>
        <div class="upp-kpi tone-purple">
            <div class="upp-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <span class="upp-kpi-label">Price Groups</span>
            <span class="upp-kpi-value">Multi</span>
            <span class="upp-kpi-hint">Default + group columns</span>
        </div>
        <div class="upp-kpi tone-slate">
            <div class="upp-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="upp-kpi-label">Validation</span>
            <span class="upp-kpi-value">SKU</span>
            <span class="upp-kpi-hint">Rows matched by product SKU</span>
        </div>
        <div class="upp-kpi tone-teal">
            <div class="upp-kpi-icon"><i class="fas fa-percentage"></i></div>
            <span class="upp-kpi-label">Margins</span>
            <span class="upp-kpi-value">Auto</span>
            <span class="upp-kpi-hint">Profit % recalculated on import</span>
        </div>
        <div class="upp-kpi tone-rose">
            <div class="upp-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <span class="upp-kpi-label">Errors</span>
            <span class="upp-kpi-value">Safe</span>
            <span class="upp-kpi-hint">Invalid rows roll back safely</span>
        </div>
        <div class="upp-kpi tone-indigo">
            <div class="upp-kpi-icon"><i class="fas fa-bolt"></i></div>
            <span class="upp-kpi-label">Scale</span>
            <span class="upp-kpi-value">Fast</span>
            <span class="upp-kpi-hint">Built for large catalogs</span>
        </div>
    </div>

    {{-- Main import / export workspace --}}
    <div class="upp-workspace">
        {{-- Export panel --}}
        <div class="upp-card upp-export-card" data-upp-search="export download product prices excel">
            <div class="upp-card-head">
                <div class="upp-card-title-wrap">
                    <div class="upp-card-icon tone-blue"><i class="fas fa-download"></i></div>
                    <div>
                        <h3>@lang('lang_v1.export_product_prices')</h3>
                        <p>Download the latest product selling prices and price-group columns as an Excel workbook.</p>
                    </div>
                </div>
                <span class="upp-badge">Step 1</span>
            </div>
            <div class="upp-card-body">
                <ul class="upp-feature-list">
                    <li><i class="fas fa-check"></i> Product name &amp; variation</li>
                    <li><i class="fas fa-check"></i> SKU (required for import)</li>
                    <li><i class="fas fa-check"></i> Selling price including tax</li>
                    <li><i class="fas fa-check"></i> Active selling price groups</li>
                </ul>
                <a href="{{ action([\App\Http\Controllers\SellingPriceGroupController::class, 'export']) }}"
                   class="tw-dw-btn tw-dw-btn-primary tw-text-white upp-btn upp-btn-primary upp-btn-block">
                    <i class="fas fa-file-excel"></i> @lang('lang_v1.export_product_prices')
                </a>
            </div>
        </div>

        {{-- Import panel — preserves exact form + field name --}}
        <div class="upp-card upp-import-card" data-upp-search="import upload file product prices submit">
            <div class="upp-card-head">
                <div class="upp-card-title-wrap">
                    <div class="upp-card-icon tone-green"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div>
                        <h3>@lang('product.file_to_import')</h3>
                        <p>Upload the edited Excel file to update product prices in bulk. Changes save only when you submit.</p>
                    </div>
                </div>
                <span class="upp-badge upp-badge-success">Step 2</span>
            </div>
            <div class="upp-card-body">
                {!! Form::open([
                    'url' => action([\App\Http\Controllers\SellingPriceGroupController::class, 'import']),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                    'id' => 'upp_import_form',
                ]) !!}

                <div class="form-group upp-dropzone" id="upp_dropzone">
                    {!! Form::label('name', __( 'product.file_to_import' ) . ':', ['class' => 'upp-sr-only']) !!}
                    <div class="upp-dropzone-inner">
                        <div class="upp-dropzone-icon"><i class="fas fa-file-excel"></i></div>
                        <h4>Drop Excel file here</h4>
                        <p>or click to browse · .xlsx / .xls / .csv</p>
                        <span class="upp-file-name" id="upp_file_name">No file selected</span>
                        {!! Form::file('product_group_prices', [
                            'required' => 'required',
                            'id' => 'product_group_prices',
                            'class' => 'upp-file-input',
                            'accept' => '.xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
                            'aria-label' => __('product.file_to_import'),
                        ]); !!}
                    </div>
                </div>

                <div class="upp-preview-panel" id="upp_preview_panel">
                    <div class="upp-preview-title">
                        <i class="fas fa-eye"></i> Ready to update
                    </div>
                    <div class="upp-preview-grid">
                        <div>
                            <span class="k">Action</span>
                            <span class="v">Bulk price import</span>
                        </div>
                        <div>
                            <span class="k">File</span>
                            <span class="v" id="upp_preview_filename">—</span>
                        </div>
                        <div>
                            <span class="k">Save mode</span>
                            <span class="v">On submit only</span>
                        </div>
                    </div>
                    <p class="upp-preview-note">Review your Excel changes carefully. Submitting will update matching SKUs using the existing import logic.</p>
                </div>

                <div class="form-group upp-submit-row">
                    <button type="button" class="upp-btn upp-btn-ghost" id="upp_reset_file">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white upp-btn upp-btn-primary" id="upp_submit_import">
                        <i class="fas fa-check-circle"></i> @lang('messages.submit')
                    </button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    {{-- Instructions (existing lang keys preserved) --}}
    <div class="upp-card upp-instructions-card" data-upp-search="instructions price import export sku">
        <div class="upp-card-head">
            <div class="upp-card-title-wrap">
                <div class="upp-card-icon tone-slate"><i class="fas fa-list-ol"></i></div>
                <div>
                    <h3>@lang('lang_v1.instructions')</h3>
                    <p>Follow these steps exactly — the import engine matches products by SKU.</p>
                </div>
            </div>
        </div>
        <div class="upp-card-body">
            <ol class="upp-steps">
                <li data-upp-search="instruction 1">
                    <span class="upp-step-num">1</span>
                    <div>
                        <strong>Prepare</strong>
                        <p>@lang('lang_v1.price_import_instruction_1')</p>
                    </div>
                </li>
                <li data-upp-search="instruction 2">
                    <span class="upp-step-num">2</span>
                    <div>
                        <strong>Edit prices</strong>
                        <p>@lang('lang_v1.price_import_instruction_2')</p>
                    </div>
                </li>
                <li data-upp-search="instruction 3">
                    <span class="upp-step-num">3</span>
                    <div>
                        <strong>Keep structure</strong>
                        <p>@lang('lang_v1.price_import_instruction_3')</p>
                    </div>
                </li>
                <li data-upp-search="instruction 4">
                    <span class="upp-step-num">4</span>
                    <div>
                        <strong>Import</strong>
                        <p>@lang('lang_v1.price_import_instruction_4')</p>
                    </div>
                </li>
            </ol>
        </div>
    </div>

    {{-- Column reference table (visual guide only — not editable data) --}}
    <div class="upp-card" data-upp-search="columns sku selling price group table reference">
        <div class="upp-card-head">
            <div class="upp-card-title-wrap">
                <div class="upp-card-icon tone-indigo"><i class="fas fa-table"></i></div>
                <div>
                    <h3>Excel Column Reference</h3>
                    <p>Your exported file uses these columns. Do not rename headers used by price groups.</p>
                </div>
            </div>
        </div>
        <div class="upp-card-body upp-table-wrap">
            <table class="table table-bordered upp-ref-table" aria-label="Excel column reference">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Field</th>
                        <th>Required</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>A</td>
                        <td>Product</td>
                        <td><span class="upp-pill muted">Info</span></td>
                        <td>Product / variation name (read-only context)</td>
                    </tr>
                    <tr>
                        <td>B</td>
                        <td>SKU</td>
                        <td><span class="upp-pill danger">Required</span></td>
                        <td>Used to match the variation on import</td>
                    </tr>
                    <tr>
                        <td>C</td>
                        <td>Selling Price Including Tax</td>
                        <td><span class="upp-pill success">Editable</span></td>
                        <td>Default selling price (inc. tax)</td>
                    </tr>
                    <tr>
                        <td>D+</td>
                        <td>Price Group columns</td>
                        <td><span class="upp-pill success">Editable</span></td>
                        <td>One column per active selling price group</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/update-price-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
