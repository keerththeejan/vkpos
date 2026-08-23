<div class="modal-dialog modal-lg vt-modal-dialog" role="document">
  <div class="modal-content vt-modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\VariationTemplateController::class, 'store']), 'method' => 'post', 'id' => 'variation_add_form', 'class' => 'form-horizontal' ]) !!}
    <div class="modal-header vt-modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">
        <i class="fas fa-plus-circle"></i> @lang('lang_v1.add_variation')
      </h4>
      <p class="vt-modal-sub">Define a reusable attribute template (Size, Color, Material, etc.).</p>
    </div>

    <div class="modal-body vt-modal-body">
      <div class="form-group">
        {!! Form::label('name',__('lang_v1.variation_name') . ':*', ['class' => 'col-sm-3 control-label']) !!}

        <div class="col-sm-9">
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.variation_name')]); !!}
        </div>
      </div>

      <div class="vt-values-panel">
        <div class="vt-values-panel-head">
          <div>
            <strong>@lang('lang_v1.add_variation_values')</strong>
            <span>Add values one by one or paste a list</span>
          </div>
          <div class="vt-values-panel-actions">
            <button type="button" class="vt-btn vt-btn-sm" id="vt_open_paste_modal">
              <i class="fas fa-paste"></i> Paste Multiple
            </button>
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="add_variation_values" title="Add value" aria-label="Add variation value">+</button>
          </div>
        </div>

        <div class="form-group vt-first-value-row">
          <label class="col-sm-3 control-label">Value:*</label>
          <div class="col-sm-9">
             {!! Form::text('variation_values[]', null, ['class' => 'form-control', 'required', 'placeholder' => 'e.g. Small']); !!}
          </div>
        </div>
        <div id="variation_values"></div>
      </div>
    </div>

    <div class="modal-footer vt-modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
