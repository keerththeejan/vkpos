<div class="modal-dialog wm-modal-dialog" role="document">
  <div class="modal-content wm-modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\WarrantyController::class, 'store']), 'method' => 'post', 'id' => 'warranty_form']) !!}

    <div class="modal-header wm-modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title"><i class="fas fa-shield-alt"></i> @lang( 'lang_v1.add_warranty' )</h4>
      <p class="wm-modal-sub">Define a warranty plan with name, description, and duration.</p>
    </div>

    <div class="modal-body wm-modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'lang_v1.description' ) . ':') !!}
          {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.description' ), 'rows' => 3 ]); !!}
      </div>
      <strong>{!! Form::label('duration', __( 'lang_v1.duration' ) . ':') !!}*</strong>
      <div class="form-group wm-duration-row">
          {!! Form::number('duration', null, ['class' => 'form-control width-40 pull-left', 'placeholder' => __( 'lang_v1.duration' ), 'required' ]); !!}

          {!! Form::select('duration_type', ['days' => __('lang_v1.days'), 'months' => __('lang_v1.months'), 'years' => __('lang_v1.years')], '', ['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'required']); !!}
      </div>
    </div>

    <div class="modal-footer wm-modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
