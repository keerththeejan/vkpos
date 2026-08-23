@extends('layouts.app')
@section('title', __('lang_v1.import_sales'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/import-sales-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

@php
    $batch_count = is_array($imported_sales_array) ? count($imported_sales_array) : 0;
    $invoice_count = 0;
    if (!empty($imported_sales_array)) {
        foreach ($imported_sales_array as $batch) {
            $invoice_count += !empty($batch['invoices']) ? count($batch['invoices']) : 0;
        }
    }
@endphp

<section class="content is-shell">
    {{-- Sticky Header --}}
    <div class="is-header" role="banner">
        <div class="is-header-left">
            <h1>@lang('lang_v1.import_sales')</h1>
            <p class="is-subtitle">Upload · map · validate · import sales batches</p>
            <div class="is-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('sale.sells')</span>
                <span>/</span>
                <span>@lang('lang_v1.import_sales')</span>
            </div>
        </div>
        <div class="is-header-actions">
            <button type="button" class="is-btn is-btn-ghost" id="is_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="is-btn is-btn-ghost" id="is_refresh_page" title="Refresh" aria-label="Refresh page">
                <i class="fas fa-sync-alt"></i>
            </button>
            <a href="{{ asset('files/import_sales_template.xlsx') }}" class="is-btn is-btn-success" download>
                <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
            </a>
            <a href="#is_upload_card" class="is-btn is-btn-primary">
                <i class="fas fa-upload"></i> Upload
            </a>
        </div>
    </div>

    {{-- KPI cards from existing import history (UI only) --}}
    <div class="is-kpi-grid" aria-label="Import KPIs">
        <div class="is-kpi tone-blue">
            <div class="is-kpi-icon"><i class="fas fa-file-excel"></i></div>
            <span class="is-kpi-label">Import Batches</span>
            <span class="is-kpi-value" id="is_kpi_batches">{{ $batch_count }}</span>
            <span class="is-kpi-hint">From import history</span>
        </div>
        <div class="is-kpi tone-green">
            <div class="is-kpi-icon"><i class="fas fa-file-invoice"></i></div>
            <span class="is-kpi-label">Imported Invoices</span>
            <span class="is-kpi-value" id="is_kpi_invoices">{{ $invoice_count }}</span>
            <span class="is-kpi-hint">Across all batches</span>
        </div>
        <div class="is-kpi tone-teal">
            <div class="is-kpi-icon"><i class="fas fa-check-circle"></i></div>
            <span class="is-kpi-label">Ready to Import</span>
            <span class="is-kpi-value">CSV / XLSX</span>
            <span class="is-kpi-hint">Upload & review</span>
        </div>
        <div class="is-kpi tone-orange">
            <div class="is-kpi-icon"><i class="fas fa-map"></i></div>
            <span class="is-kpi-label">Mappable Fields</span>
            <span class="is-kpi-value">{{ count($import_fields) }}</span>
            <span class="is-kpi-hint">Importable columns</span>
        </div>
        <div class="is-kpi tone-violet">
            <div class="is-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
            <span class="is-kpi-label">IMEI Support</span>
            <span class="is-kpi-value">—</span>
            <span class="is-kpi-hint">Via field mapping</span>
        </div>
        <div class="is-kpi tone-red">
            <div class="is-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <span class="is-kpi-label">Validation</span>
            <span class="is-kpi-value">Preview</span>
            <span class="is-kpi-hint">On upload & review</span>
        </div>
        <div class="is-kpi tone-blue">
            <div class="is-kpi-icon"><i class="fas fa-users"></i></div>
            <span class="is-kpi-label">Customers</span>
            <span class="is-kpi-value">—</span>
            <span class="is-kpi-hint">Created on import</span>
        </div>
        <div class="is-kpi tone-green">
            <div class="is-kpi-icon"><i class="fas fa-undo"></i></div>
            <span class="is-kpi-label">Revert</span>
            <span class="is-kpi-value">Batch</span>
            <span class="is-kpi-hint">History actions</span>
        </div>
    </div>

    <div class="is-steps" aria-label="Import wizard steps">
        <span class="is-step is-active"><span class="n">1</span> Upload file</span>
        <span class="is-step"><span class="n">2</span> Map columns</span>
        <span class="is-step"><span class="n">3</span> Validate & import</span>
        <span class="is-step"><span class="n">4</span> History / revert</span>
    </div>

    <div class="is-future-strip" aria-label="Coming soon">
        <span class="is-chip"><i class="fas fa-cloud"></i> Cloud Import</span>
        <span class="is-chip"><i class="fas fa-robot"></i> AI Field Mapping</span>
        <span class="is-chip"><i class="fas fa-clock"></i> Scheduled Imports</span>
        <span class="is-chip"><i class="fas fa-history"></i> Audit Trail</span>
        <span class="is-chip muted">UI placeholders — not connected to backend</span>
    </div>

    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
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

    <div class="is-layout-2">
        <div class="is-card" id="is_upload_card">
            <div class="is-card-head">
                <div>
                    <h3>Upload & Review</h3>
                    <p>Same form posts to existing preview endpoint</p>
                </div>
            </div>
            <div class="is-card-body">
                {!! Form::open(['url' => action([\App\Http\Controllers\ImportSalesController::class, 'preview']), 'method' => 'post', 'enctype' => 'multipart/form-data', 'id' => 'is_upload_form' ]) !!}
                    <div class="is-upload-zone" id="is_upload_zone">
                        <div class="is-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <h4>Drop Excel / CSV here</h4>
                        <p>Or browse to select a file, then upload & review</p>
                        <div class="is-upload-actions">
                            <div class="form-group" style="margin:0;">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':', ['class' => 'sr-only']) !!}
                                {!! Form::file('sales', ['required' => 'required', 'id' => 'sales', 'accept' => '.csv,.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']); !!}
                            </div>
                            <button type="button" class="is-btn" id="is_browse_file"><i class="fas fa-folder-open"></i> Browse</button>
                            <button type="submit" class="is-btn is-btn-primary"><i class="fas fa-eye"></i> @lang('lang_v1.upload_and_review')</button>
                        </div>
                        <div class="is-file-meta" id="is_file_meta" aria-live="polite"></div>
                    </div>
                    <div style="margin-top:14px;">
                        <a href="{{ asset('files/import_sales_template.xlsx') }}" class="is-btn is-btn-success" download>
                            <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
                        </a>
                    </div>
                {!! Form::close() !!}
            </div>
        </div>

        <div class="is-card">
            <div class="is-card-head">
                <div>
                    <h3>@lang('lang_v1.instructions')</h3>
                    <p>Importable fields and guidance</p>
                </div>
            </div>
            <div class="is-card-body" style="max-height:420px;overflow:auto;">
                <table class="table table-condensed">
                    <tr>
                        <td>1.</td>
                        <td>@lang('lang_v1.upload_data_in_excel_format')</td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>@lang('lang_v1.choose_location_and_group_by')</td>
                    </tr>
                    <tr>
                        <td>3.</td>
                        <td>@lang('lang_v1.map_columns_with_respective_sales_fields')</td>
                    </tr>
                </table>
                <table class="table table-striped table-slim">
                    <tr>
                        <th>@lang('lang_v1.importable_fields')</th>
                        <th>@lang('lang_v1.instructions')</th>
                    </tr>
                    @foreach($import_fields as $key => $value)
                        <tr>
                            <td>{{$value['label']}}</td>
                            <td><small>{{$value['instruction'] ?? ''}}</small></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="is-card">
        <div class="is-card-head">
            <div>
                <h3>@lang('lang_v1.imports')</h3>
                <p>Import history · revert uses existing batch action</p>
            </div>
        </div>
        <div class="is-card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.import_batch')</th>
                            <th>@lang('lang_v1.import_time')</th>
                            <th>@lang('business.created_by')</th>
                            <th>@lang('lang_v1.invoices')</th>
                            @can('sell.delete')
                                <th>@lang('messages.action')</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody id="is_import_history_body">
                        @forelse($imported_sales_array as $key => $value)
                            <tr>
                                <td>{{$key}}</td>
                                <td>{{@format_datetime($value['import_time'])}}</td>
                                <td>{{$value['created_by']}}</td>
                                <td>
                                    {{implode(', ', $value['invoices'])}} <br>
                                    <p class="text-muted text-right">
                                    <small>(@lang('sale.total'): {{count($value['invoices'])}})</small>
                                    </p>
                                </td>
                                @can('sell.delete')
                                    <td><a href="{{action([\App\Http\Controllers\ImportSalesController::class, 'revertSaleImport'], $key)}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error revert_import"><i class="fas fa-undo"></i> @lang('lang_v1.revert_import')</a></td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No import batches yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@stop
@section('javascript')
<script src="{{ asset('js/import-sales-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).on('click', 'a.revert_import', function(e){
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                window.location = $(this).attr('href');
            } else {
                return false;
            }
        });
    });
</script>
@endsection
