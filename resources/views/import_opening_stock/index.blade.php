@extends('layouts.app')
@section('title', __('lang_v1.import_opening_stock'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/import-opening-stock-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content ios-shell">
    {{-- Sticky Header --}}
    <div class="ios-header" role="banner">
        <div class="ios-header-left">
            <h1>@lang('lang_v1.import_opening_stock')</h1>
            <p class="ios-subtitle">Initialize inventory balances for products already in your catalog</p>
            <div class="ios-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('lang_v1.import_opening_stock')</span>
            </div>
        </div>
        <div class="ios-header-actions">
            <button type="button" class="ios-btn ios-btn-ghost" id="ios_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ios-btn ios-btn-ghost" id="ios_refresh_page" title="Refresh" aria-label="Refresh page">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="ios-btn ios-btn-ghost" id="ios_help_toggle" title="Help" aria-label="Toggle instructions">
                <i class="fas fa-question-circle"></i>
            </button>
            <a class="ios-btn ios-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            <a href="{{ asset('files/import_opening_stock_csv_template.xls') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white ios-btn ios-btn-success" download id="ios_download_template">
                <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
            </a>
        </div>
    </div>

    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible ios-alert ios-alert-danger">
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

    {{-- Summary cards (visual — no backend stats without controller changes) --}}
    <div class="ios-kpi-grid" aria-label="Opening stock import overview">
        <div class="ios-kpi tone-blue">
            <div class="ios-kpi-icon"><i class="fas fa-boxes"></i></div>
            <span class="ios-kpi-label">Purpose</span>
            <span class="ios-kpi-value">Opening</span>
            <span class="ios-kpi-hint">Stock for existing products</span>
        </div>
        <div class="ios-kpi tone-green">
            <div class="ios-kpi-icon"><i class="fas fa-barcode"></i></div>
            <span class="ios-kpi-label">Match By</span>
            <span class="ios-kpi-value">SKU</span>
            <span class="ios-kpi-hint">Products must already exist</span>
        </div>
        <div class="ios-kpi tone-purple">
            <div class="ios-kpi-icon"><i class="fas fa-map-marker-alt"></i></div>
            <span class="ios-kpi-label">Locations</span>
            <span class="ios-kpi-value">Supported</span>
            <span class="ios-kpi-hint">Optional location column</span>
        </div>
        <div class="ios-kpi tone-orange">
            <div class="ios-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="ios-kpi-label">File Status</span>
            <span class="ios-kpi-value" id="ios_kpi_file_status">Waiting</span>
            <span class="ios-kpi-hint" id="ios_kpi_file_hint">No file selected</span>
        </div>
        <div class="ios-kpi tone-teal">
            <div class="ios-kpi-icon"><i class="fas fa-file-excel"></i></div>
            <span class="ios-kpi-label">Format</span>
            <span class="ios-kpi-value">XLS</span>
            <span class="ios-kpi-hint">Use the official template</span>
        </div>
        <div class="ios-kpi tone-slate">
            <div class="ios-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="ios-kpi-label">Validation</span>
            <span class="ios-kpi-value">Server</span>
            <span class="ios-kpi-hint">Existing import rules apply</span>
        </div>
    </div>

    {{-- Stepper --}}
    <div class="ios-stepper" id="ios_stepper" aria-label="Import steps">
        <div class="ios-step is-active" data-step="1">
            <span class="ios-step-num">1</span>
            <span class="ios-step-label">Choose File</span>
        </div>
        <div class="ios-step-line"></div>
        <div class="ios-step" data-step="2">
            <span class="ios-step-num">2</span>
            <span class="ios-step-label">Review Guide</span>
        </div>
        <div class="ios-step-line"></div>
        <div class="ios-step" data-step="3">
            <span class="ios-step-num">3</span>
            <span class="ios-step-label">Confirm</span>
        </div>
        <div class="ios-step-line"></div>
        <div class="ios-step" data-step="4">
            <span class="ios-step-num">4</span>
            <span class="ios-step-label">Import</span>
        </div>
    </div>

    {!! Form::open([
        'url' => action([\App\Http\Controllers\ImportOpeningStockController::class, 'store']),
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'id' => 'ios_import_form',
    ]) !!}

    <div class="ios-wizard-panels">
        {{-- Step 1 --}}
        <div class="ios-panel is-active" id="ios_panel_1" data-panel="1">
            <div class="ios-card">
                <div class="ios-card-head">
                    <div class="ios-card-icon tone-blue"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div>
                        <h3>@lang('product.file_to_import')
                            @show_tooltip(__('lang_v1.tooltip_import_opening_stock'))
                        </h3>
                        <p>@lang('lang_v1.tooltip_import_opening_stock')</p>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="form-group ios-dropzone" id="ios_dropzone">
                        {!! Form::label('name', __( 'product.file_to_import' ) . ':', ['class' => 'ios-sr-only']) !!}
                        <div class="ios-dropzone-inner">
                            <div class="ios-dropzone-icon"><i class="fas fa-warehouse"></i></div>
                            <h4>Drop opening stock file here</h4>
                            <p>or click to browse · Excel (.xls)</p>
                            <div class="ios-file-types">
                                <span><i class="fas fa-file-excel"></i> .xls</span>
                            </div>
                            <span class="ios-file-name" id="ios_file_name">No file selected</span>
                            <span class="ios-file-meta" id="ios_file_meta"></span>
                            {!! Form::file('products_csv', [
                                'accept'=> '.xls',
                                'required' => 'required',
                                'id' => 'products_csv',
                                'class' => 'ios-file-input',
                                'aria-label' => __('product.file_to_import'),
                            ]); !!}
                        </div>
                    </div>

                    <div class="ios-upload-actions">
                        <button type="button" class="ios-btn" id="ios_remove_file">
                            <i class="fas fa-trash"></i> Remove File
                        </button>
                        <a href="{{ asset('files/import_opening_stock_csv_template.xls') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white ios-btn ios-btn-success" download>
                            <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
                        </a>
                        <button type="button" class="ios-btn ios-btn-primary" id="ios_goto_step2" disabled>
                            Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="ios-panel" id="ios_panel_2" data-panel="2" style="display:none;">
            <div class="ios-card">
                <div class="ios-card-head">
                    <div class="ios-card-icon tone-purple"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <h3>Import checklist</h3>
                        <p>These behaviors are handled by the existing importer — no extra options are required.</p>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="ios-settings-grid">
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Products must exist</strong>
                                <span>Use Import Products first if SKUs are missing</span>
                            </div>
                        </div>
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Match by SKU</strong>
                                <span>Column 1 identifies each product / variation</span>
                            </div>
                        </div>
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Quantity &amp; unit cost</strong>
                                <span>Required for every stock row</span>
                            </div>
                        </div>
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Business location</strong>
                                <span>Optional — defaults when omitted</span>
                            </div>
                        </div>
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Lot &amp; expiry</strong>
                                <span>Optional batch tracking fields</span>
                            </div>
                        </div>
                        <div class="ios-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Server validation</strong>
                                <span>Invalid rows stop the import with a clear message</span>
                            </div>
                        </div>
                    </div>

                    <div class="ios-panel-nav">
                        <button type="button" class="ios-btn" data-ios-goto="1"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="ios-btn ios-btn-primary" data-ios-goto="3">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="ios-panel" id="ios_panel_3" data-panel="3" style="display:none;">
            <div class="ios-card">
                <div class="ios-card-head">
                    <div class="ios-card-icon tone-orange"><i class="fas fa-eye"></i></div>
                    <div>
                        <h3>Confirm before import</h3>
                        <p>Review the selected file. Stock transactions are created by the existing import logic on submit.</p>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="ios-preview-panel">
                        <div class="ios-preview-grid">
                            <div>
                                <span class="k">File name</span>
                                <span class="v" id="ios_preview_filename">—</span>
                            </div>
                            <div>
                                <span class="k">Size</span>
                                <span class="v" id="ios_preview_size">—</span>
                            </div>
                            <div>
                                <span class="k">Type</span>
                                <span class="v" id="ios_preview_type">—</span>
                            </div>
                            <div>
                                <span class="k">Action</span>
                                <span class="v">Create opening stock</span>
                            </div>
                        </div>
                        <div class="ios-validation-list">
                            <div class="ios-val-item ok"><i class="fas fa-check"></i> File selected</div>
                            <div class="ios-val-item ok"><i class="fas fa-check"></i> Extension allowed (.xls)</div>
                            <div class="ios-val-item warn"><i class="fas fa-info-circle"></i> Products must already exist (matched by SKU)</div>
                            <div class="ios-val-item ok"><i class="fas fa-check"></i> Ready to submit</div>
                        </div>
                    </div>

                    <div class="ios-panel-nav">
                        <button type="button" class="ios-btn" data-ios-goto="2"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="ios-btn ios-btn-primary" data-ios-goto="4">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4 --}}
        <div class="ios-panel" id="ios_panel_4" data-panel="4" style="display:none;">
            <div class="ios-card">
                <div class="ios-card-head">
                    <div class="ios-card-icon tone-green"><i class="fas fa-rocket"></i></div>
                    <div>
                        <h3>Import opening stock</h3>
                        <p>Submitting uses the existing opening-stock processor. Keep this tab open until the page responds.</p>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="ios-progress-panel" id="ios_progress_panel" style="display:none;">
                        <div class="ios-progress-title"><i class="fas fa-spinner fa-spin"></i> Import in progress…</div>
                        <div class="ios-progress-bar"><span></span></div>
                        <p class="ios-progress-note">Uploading file and creating opening stock transactions.</p>
                    </div>

                    <div class="ios-final-summary">
                        <div>
                            <span class="k">Selected file</span>
                            <span class="v" id="ios_final_filename">—</span>
                        </div>
                        <div>
                            <span class="k">Destination</span>
                            <span class="v">Inventory balances</span>
                        </div>
                        <div>
                            <span class="k">Save mode</span>
                            <span class="v">On submit only</span>
                        </div>
                    </div>

                    <div class="ios-panel-nav">
                        <button type="button" class="ios-btn" data-ios-goto="3"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ios-btn ios-btn-primary" id="ios_submit_import">
                            <i class="fas fa-check-circle"></i> @lang('messages.submit')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {!! Form::close() !!}

    {{-- Instructions --}}
    <div class="ios-card ios-instructions-card" id="ios_instructions_card">
        <div class="ios-card-head">
            <div class="ios-card-icon tone-slate"><i class="fas fa-list-ol"></i></div>
            <div>
                <h3>@lang('lang_v1.instructions')</h3>
                <p><strong>@lang('lang_v1.instruction_line1')</strong></p>
            </div>
            <button type="button" class="ios-btn ios-btn-ghost" id="ios_toggle_instructions" aria-expanded="true" aria-controls="ios_instructions_body" title="Collapse">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div class="ios-card-body" id="ios_instructions_body">
            <p class="ios-instructions-lead">@lang('lang_v1.instruction_line2')</p>
            <div class="ios-table-wrap">
                <table class="table table-striped ios-ref-table">
                    <tr>
                        <th>@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>@lang('business.location') <small class="text-muted">(@lang('lang_v1.optional')) <br>@lang('lang_v1.location_ins')</small></td>
                        <td>@lang('lang_v1.location_ins1')</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>@lang('lang_v1.quantity') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>@lang('purchase.unit_cost_before_tax') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>@lang('lang_v1.lot_number') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>@lang('lang_v1.expiry_date') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.expiry_date_in_business_date_format') !!} <br/> <b>{{$date_format}}</b>, @lang('lang_v1.type'): <b>text</b>, @lang('lang_v1.example'): <b>{{@format_date('today')}}</b></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/import-opening-stock-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
