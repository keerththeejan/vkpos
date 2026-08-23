@extends('layouts.app')
@section('title', __('product.import_products'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/import-products-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
<section class="content ip-shell">
    {{-- Sticky Header --}}
    <div class="ip-header" role="banner">
        <div class="ip-header-left">
            <h1>@lang('product.import_products')</h1>
            <p class="ip-subtitle">Bulk upload products from Excel / CSV using the official template</p>
            <div class="ip-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>Products</span>
                <span>/</span>
                <span>@lang('product.import_products')</span>
            </div>
        </div>
        <div class="ip-header-actions">
            <button type="button" class="ip-btn ip-btn-ghost" id="ip_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="ip-btn ip-btn-ghost" id="ip_refresh_page" title="Refresh" aria-label="Refresh page">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="ip-btn ip-btn-ghost" id="ip_help_toggle" title="Help" aria-label="Toggle instructions">
                <i class="fas fa-question-circle"></i>
            </button>
            <a class="ip-btn ip-btn-ghost" href="{{ url('/products') }}" title="Products" aria-label="Open products">
                <i class="fas fa-cog"></i>
            </a>
            <a href="{{ asset('files/import_products_csv_template.xls') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white ip-btn ip-btn-success" download id="ip_download_template">
                <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
            </a>
        </div>
    </div>

    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible ip-alert ip-alert-danger">
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

    {{-- Summary cards (visual workflow — no backend stats available without controller changes) --}}
    <div class="ip-kpi-grid" aria-label="Import overview">
        <div class="ip-kpi tone-blue">
            <div class="ip-kpi-icon"><i class="fas fa-file-excel"></i></div>
            <span class="ip-kpi-label">Supported</span>
            <span class="ip-kpi-value">XLS / CSV</span>
            <span class="ip-kpi-hint">.xls · .xlsx · .csv</span>
        </div>
        <div class="ip-kpi tone-green">
            <div class="ip-kpi-icon"><i class="fas fa-download"></i></div>
            <span class="ip-kpi-label">Template</span>
            <span class="ip-kpi-value">Ready</span>
            <span class="ip-kpi-hint">Download before filling data</span>
        </div>
        <div class="ip-kpi tone-purple">
            <div class="ip-kpi-icon"><i class="fas fa-columns"></i></div>
            <span class="ip-kpi-label">Columns</span>
            <span class="ip-kpi-value">37+</span>
            <span class="ip-kpi-hint">Use the latest template</span>
        </div>
        <div class="ip-kpi tone-orange">
            <div class="ip-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="ip-kpi-label">File Status</span>
            <span class="ip-kpi-value" id="ip_kpi_file_status">Waiting</span>
            <span class="ip-kpi-hint" id="ip_kpi_file_hint">No file selected</span>
        </div>
        <div class="ip-kpi tone-teal">
            <div class="ip-kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <span class="ip-kpi-label">Validation</span>
            <span class="ip-kpi-value">Server</span>
            <span class="ip-kpi-hint">Existing import rules apply</span>
        </div>
        <div class="ip-kpi tone-slate">
            <div class="ip-kpi-icon"><i class="fas fa-bolt"></i></div>
            <span class="ip-kpi-label">Import Mode</span>
            <span class="ip-kpi-value">Bulk</span>
            <span class="ip-kpi-hint">Creates products on submit</span>
        </div>
    </div>

    {{-- Stepper --}}
    <div class="ip-stepper" id="ip_stepper" aria-label="Import steps">
        <div class="ip-step is-active" data-step="1">
            <span class="ip-step-num">1</span>
            <span class="ip-step-label">Choose File</span>
        </div>
        <div class="ip-step-line"></div>
        <div class="ip-step" data-step="2">
            <span class="ip-step-num">2</span>
            <span class="ip-step-label">Review Guide</span>
        </div>
        <div class="ip-step-line"></div>
        <div class="ip-step" data-step="3">
            <span class="ip-step-num">3</span>
            <span class="ip-step-label">Confirm</span>
        </div>
        <div class="ip-step-line"></div>
        <div class="ip-step" data-step="4">
            <span class="ip-step-num">4</span>
            <span class="ip-step-label">Import</span>
        </div>
    </div>

    {!! Form::open([
        'url' => action([\App\Http\Controllers\ImportProductsController::class, 'store']),
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'id' => 'ip_import_form',
    ]) !!}

    <div class="ip-wizard-panels">
        {{-- Step 1: Upload --}}
        <div class="ip-panel is-active" id="ip_panel_1" data-panel="1">
            <div class="ip-card">
                <div class="ip-card-head">
                    <div class="ip-card-icon tone-blue"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div>
                        <h3>@lang('product.file_to_import')</h3>
                        <p>Upload your completed product workbook. Drag &amp; drop or browse — the same server import runs on submit.</p>
                    </div>
                </div>
                <div class="ip-card-body">
                    <div class="form-group ip-dropzone" id="ip_dropzone">
                        {!! Form::label('name', __( 'product.file_to_import' ) . ':', ['class' => 'ip-sr-only']) !!}
                        <div class="ip-dropzone-inner">
                            <div class="ip-dropzone-icon"><i class="fas fa-file-excel"></i></div>
                            <h4>Drop Excel / CSV here</h4>
                            <p>or click to browse</p>
                            <div class="ip-file-types">
                                <span><i class="fas fa-file-excel"></i> .xlsx</span>
                                <span><i class="fas fa-file-excel"></i> .xls</span>
                                <span><i class="fas fa-file-csv"></i> .csv</span>
                            </div>
                            <span class="ip-file-name" id="ip_file_name">No file selected</span>
                            <span class="ip-file-meta" id="ip_file_meta"></span>
                            {!! Form::file('products_csv', [
                                'accept'=> '.xls, .xlsx, .csv',
                                'required' => 'required',
                                'id' => 'products_csv',
                                'class' => 'ip-file-input',
                                'aria-label' => __('product.file_to_import'),
                            ]); !!}
                        </div>
                    </div>

                    <div class="ip-upload-actions">
                        <button type="button" class="ip-btn" id="ip_remove_file">
                            <i class="fas fa-trash"></i> Remove File
                        </button>
                        <a href="{{ asset('files/import_products_csv_template.xls') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white ip-btn ip-btn-success" download>
                            <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
                        </a>
                        <button type="button" class="ip-btn ip-btn-primary" id="ip_goto_step2" disabled>
                            Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Guide / what importer does (informational — no fake settings) --}}
        <div class="ip-panel" id="ip_panel_2" data-panel="2" style="display:none;">
            <div class="ip-card">
                <div class="ip-card-head">
                    <div class="ip-card-icon tone-purple"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <h3>Import checklist</h3>
                        <p>Confirm your sheet matches the template. These behaviors are handled by the existing importer — no extra options are required.</p>
                    </div>
                </div>
                <div class="ip-card-body">
                    <div class="ip-settings-grid">
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Use latest template</strong>
                                <span>All required columns must be present (37+)</span>
                            </div>
                        </div>
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Required fields</strong>
                                <span>Name, Unit, Manage Stock, Tax Type, Product Type</span>
                            </div>
                        </div>
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Brands &amp; categories</strong>
                                <span>Created automatically when missing (existing logic)</span>
                            </div>
                        </div>
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>SKU optional</strong>
                                <span>Auto-generated when left blank</span>
                            </div>
                        </div>
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Single &amp; variable</strong>
                                <span>Variation columns apply for variable products</span>
                            </div>
                        </div>
                        <div class="ip-setting-card">
                            <i class="fas fa-check text-success"></i>
                            <div>
                                <strong>Server validation</strong>
                                <span>Invalid rows stop the import with a clear message</span>
                            </div>
                        </div>
                    </div>

                    <div class="ip-panel-nav">
                        <button type="button" class="ip-btn" data-ip-goto="1"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="ip-btn ip-btn-primary" data-ip-goto="3">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Confirm / preview readiness --}}
        <div class="ip-panel" id="ip_panel_3" data-panel="3" style="display:none;">
            <div class="ip-card">
                <div class="ip-card-head">
                    <div class="ip-card-icon tone-orange"><i class="fas fa-eye"></i></div>
                    <div>
                        <h3>Confirm before import</h3>
                        <p>Review the selected file. Import runs on the server when you click Submit — prices, stock, and validations use existing logic.</p>
                    </div>
                </div>
                <div class="ip-card-body">
                    <div class="ip-preview-panel" id="ip_preview_panel">
                        <div class="ip-preview-grid">
                            <div>
                                <span class="k">File name</span>
                                <span class="v" id="ip_preview_filename">—</span>
                            </div>
                            <div>
                                <span class="k">Size</span>
                                <span class="v" id="ip_preview_size">—</span>
                            </div>
                            <div>
                                <span class="k">Type</span>
                                <span class="v" id="ip_preview_type">—</span>
                            </div>
                            <div>
                                <span class="k">Action</span>
                                <span class="v">Create products via import</span>
                            </div>
                        </div>
                        <div class="ip-validation-list">
                            <div class="ip-val-item ok"><i class="fas fa-check"></i> File selected</div>
                            <div class="ip-val-item ok"><i class="fas fa-check"></i> Extension allowed (.xls / .xlsx / .csv)</div>
                            <div class="ip-val-item warn"><i class="fas fa-info-circle"></i> Row-level validation happens during import</div>
                            <div class="ip-val-item ok"><i class="fas fa-check"></i> Ready to submit</div>
                        </div>
                    </div>

                    <div class="ip-panel-nav">
                        <button type="button" class="ip-btn" data-ip-goto="2"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="ip-btn ip-btn-primary" data-ip-goto="4">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4: Submit --}}
        <div class="ip-panel" id="ip_panel_4" data-panel="4" style="display:none;">
            <div class="ip-card">
                <div class="ip-card-head">
                    <div class="ip-card-icon tone-green"><i class="fas fa-rocket"></i></div>
                    <div>
                        <h3>Import products</h3>
                        <p>Submitting uses the existing import processor. Do not close the browser until the page responds.</p>
                    </div>
                </div>
                <div class="ip-card-body">
                    <div class="ip-progress-panel" id="ip_progress_panel" style="display:none;">
                        <div class="ip-progress-title"><i class="fas fa-spinner fa-spin"></i> Import in progress…</div>
                        <div class="ip-progress-bar"><span id="ip_progress_bar"></span></div>
                        <p class="ip-progress-note">Uploading file and processing rows on the server.</p>
                    </div>

                    <div class="ip-final-summary">
                        <div>
                            <span class="k">Selected file</span>
                            <span class="v" id="ip_final_filename">—</span>
                        </div>
                        <div>
                            <span class="k">Destination</span>
                            <span class="v">Product catalog</span>
                        </div>
                        <div>
                            <span class="k">Save mode</span>
                            <span class="v">On submit only</span>
                        </div>
                    </div>

                    <div class="ip-panel-nav">
                        <button type="button" class="ip-btn" data-ip-goto="3"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ip-btn ip-btn-primary" id="ip_submit_import">
                            <i class="fas fa-check-circle"></i> @lang('messages.submit')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {!! Form::close() !!}

    {{-- Instructions (existing content preserved) --}}
    <div class="ip-card ip-instructions-card" id="ip_instructions_card">
        <div class="ip-card-head">
            <div class="ip-card-icon tone-slate"><i class="fas fa-list-ol"></i></div>
            <div>
                <h3>@lang('lang_v1.instructions')</h3>
                <p>@lang('lang_v1.instruction_line1')</p>
            </div>
            <button type="button" class="ip-btn ip-btn-ghost" id="ip_toggle_instructions" aria-expanded="true" aria-controls="ip_instructions_body" title="Collapse">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div class="ip-card-body" id="ip_instructions_body">
            <p class="ip-instructions-lead">@lang('lang_v1.instruction_line2')</p>
            <div class="ip-table-wrap">
                <table class="table table-striped ip-ref-table">
                    <tr>
                        <th>@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>@lang('product.product_name') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.name_ins')</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>@lang('product.brand') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.brand_ins') <br><small class="text-muted">(@lang('lang_v1.brand_ins2'))</small></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>@lang('product.unit') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.unit_ins')</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>@lang('product.category') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.category_ins') <br><small class="text-muted">(@lang('lang_v1.category_ins2'))</small></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>@lang('product.sub_category') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sub_category_ins') <br><small class="text-muted">({!! __('lang_v1.sub_category_ins2') !!})</small></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sku_ins')</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>@lang('product.barcode_type') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): C128)</small></td>
                        <td>@lang('lang_v1.barcode_type_ins') <br>
                            <strong>@lang('lang_v1.barcode_type_ins2'): C128, C39, EAN-13, EAN-8, UPC-A, UPC-E, ITF-14</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>@lang('product.manage_stock') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.manage_stock_ins')<br>
                            <strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>@lang('product.alert_quantity') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('product.alert_quantity')</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>@lang('product.expires_in') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expires_in_ins')</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>@lang('lang_v1.expire_period_unit') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expire_period_unit_ins')<br>
                            <strong>@lang('lang_v1.available_options'): days, months</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>@lang('product.applicable_tax') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.applicable_tax_ins') {!! __('lang_v1.applicable_tax_help') !!}</td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>@lang('product.selling_price_tax_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.selling_price_tax_type') <br>
                            <strong>@lang('lang_v1.available_options'): inclusive, exclusive</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>@lang('product.product_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.product_type') <br>
                            <strong>@lang('lang_v1.available_options'): single, variable</strong></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>@lang('product.variation_name') <small class="text-muted">(@lang('lang_v1.variation_name_ins'))</small></td>
                        <td>@lang('lang_v1.variation_name_ins2')</td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>@lang('product.variation_values') <small class="text-muted">(@lang('lang_v1.variation_values_ins'))</small></td>
                        <td>{!! __('lang_v1.variation_values_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>@lang('lang_v1.variation_sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.variation_sku_ins') !!}</td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td> @lang('lang_v1.purchase_price_inc_tax')<br><small class="text-muted">(@lang('lang_v1.purchase_price_inc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_inc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td>@lang('lang_v1.purchase_price_exc_tax')  <br><small class="text-muted">(@lang('lang_v1.purchase_price_exc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_exc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>20</td>
                        <td>@lang('lang_v1.profit_margin') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.profit_margin_ins')<br>
                            <small class="text-muted">{!! __('lang_v1.profit_margin_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>21</td>
                        <td>@lang('lang_v1.selling_price') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.selling_price_ins')<br>
                         <small class="text-muted">{!! __('lang_v1.selling_price_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>22</td>
                        <td>@lang('lang_v1.opening_stock') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.opening_stock_ins') {!! __('lang_v1.opening_stock_help_text') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>23</td>
                        <td>@lang('lang_v1.opening_stock_location') <small class="text-muted">(@lang('lang_v1.optional')) <br>@lang('lang_v1.location_ins')</small></td>
                        <td>@lang('lang_v1.location_ins1')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>24</td>
                        <td>@lang('lang_v1.expiry_date') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.expiry_date_ins') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>25</td>
                        <td>@lang('lang_v1.enable_imei_or_sr_no') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): 0)</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>26</td>
                        <td>@lang('lang_v1.weight') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.optional')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>27</td>
                        <td>@lang('lang_v1.rack') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.rack_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>28</td>
                        <td>@lang('lang_v1.row') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.row_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>29</td>
                        <td>@lang('lang_v1.position') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.position_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>30</td>
                        <td>@lang('lang_v1.image') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.image_help_text', ['path' => 'public/uploads/'.config('constants.product_img_path')]) !!} <br><br>
                            {{__('lang_v1.img_url_help_text')}}
                        </td>
                    </tr>
                    <tr>
                        <td>31</td>
                        <td>@lang('lang_v1.product_description') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>32</td>
                        <td>@lang('lang_v1.product_custom_field1') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>33</td>
                        <td>@lang('lang_v1.product_custom_field2') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>34</td>
                        <td>@lang('lang_v1.product_custom_field3') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>35</td>
                        <td>@lang('lang_v1.product_custom_field4') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>36</td>
                        <td>@lang('lang_v1.not_for_selling') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>37</td>
                        <td>@lang('lang_v1.product_locations') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.product_locations_ins')
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/import-products-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
