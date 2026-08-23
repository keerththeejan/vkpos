@extends('layouts.app')
@section('title', __('lang_v1.preview_imported_sales'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/import-sales-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content is-shell">
    <div class="is-header" role="banner">
        <div class="is-header-left">
            <h1>@lang('lang_v1.preview_imported_sales')</h1>
            <p class="is-subtitle">Map columns · set location · import</p>
            <div class="is-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                <span>@lang('lang_v1.import_sales')</span>
                <span>/</span>
                <span>Preview</span>
            </div>
        </div>
        <div class="is-header-actions">
            <button type="button" class="is-btn is-btn-ghost" id="is_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            <a href="{{ action([\App\Http\Controllers\ImportSalesController::class, 'index']) }}" class="is-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button type="submit" form="import_sale_form" class="is-btn is-btn-primary">
                <i class="fas fa-check"></i> @lang('messages.submit')
            </button>
        </div>
    </div>

    <div class="is-steps" aria-label="Import wizard steps">
        <span class="is-step"><span class="n">1</span> Upload file</span>
        <span class="is-step is-active"><span class="n">2</span> Map columns</span>
        <span class="is-step"><span class="n">3</span> Validate & import</span>
        <span class="is-step"><span class="n">4</span> History / revert</span>
    </div>

    {!! Form::open(['url' => action([\App\Http\Controllers\ImportSalesController::class, 'import']), 'method' => 'post', 'id' => 'import_sale_form']) !!}
    {!! Form::hidden('file_name', $file_name); !!}

    <div class="is-card">
        <div class="is-card-head">
            <div>
                <h3>Import Settings</h3>
                <p>Group sale lines and choose business location</p>
            </div>
        </div>
        <div class="is-card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('group_by', __('lang_v1.group_sale_line_by') . ':*') !!} @show_tooltip(__('lang_v1.group_by_tooltip'))
                        {!! Form::select('group_by', $parsed_array[0], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="is-card">
        <div class="is-card-head">
            <div>
                <h3>Field Mapping & Data Preview</h3>
                <p>First rows of the uploaded file · map each column to a sales field</p>
            </div>
        </div>
        <div class="is-card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="scroll-top-bottom" style="max-height: 400px;">
                        <table class="table table-condensed table-striped">
                            @foreach(array_slice($parsed_array, 0, 101) as $row)
                                <tr>
                                    <td>@if($loop->index > 0 ){{$loop->index}} @else # @endif</td>
                                    @foreach($row as $k => $v)
                                        @if($loop->parent->index == 0)
                                            <th>{{$v}}</th>
                                        @else
                                            <td>{{$v}}</td>
                                        @endif
                                    @endforeach
                                </tr>
                                @if($loop->index == 0)
                                    <tr>
                                    <td>@if($loop->index > 0 ){{$loop->index}}@endif</td>
                                    @foreach($row as $k => $v)
                                        <td>
                                            {!! Form::select('import_fields[' . $k . ']', $import_fields, $match_array[$k], ['class' => 'form-control import_fields select2', 'placeholder' => __('lang_v1.skip'), 'style' => 'width: 100%;']); !!}
                                        </td>
                                    @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="is-preview-toolbar">
                <a href="{{ action([\App\Http\Controllers\ImportSalesController::class, 'index']) }}" class="is-btn">Cancel</a>
                <button type="submit" class="is-btn is-btn-primary">@lang('messages.submit')</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop
@section('javascript')
<script src="{{ asset('js/import-sales-premium-ui.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).on('submit', 'form#import_sale_form', function(){
        var import_fields = [];

        $('.import_fields').each( function() {
            if ($(this).val()) {
                import_fields.push($(this).val());
            }
        });

        if (import_fields.indexOf('customer_phone_number') == -1 && import_fields.indexOf('customer_email') == -1) {
            alert("{{__('lang_v1.email_or_phone_required')}}");
            return false;
        }
        if (import_fields.indexOf('product') == -1 && import_fields.indexOf('sku') == -1) {
            alert("{{__('lang_v1.product_name_or_sku_is_required')}}");
            return false;
        }
        if (import_fields.indexOf('quantity') == -1) {
            alert("{{__('lang_v1.quantity_is_required')}}");
            return false;
        }
        if (import_fields.indexOf('unit_price') == -1) {
            alert("{{__('lang_v1.unit_price_is_required')}}");
            return false;
        }

        if(hasDuplicates(import_fields)) {
            alert("{{__('lang_v1.cannot_select_a_field_twice')}}");
            return false;
        }
        
    });

    function hasDuplicates(array) {
        return (new Set(array)).size !== array.length;
    }
</script>
@endsection
