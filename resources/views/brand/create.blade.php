<div class="modal-dialog br-modal-dialog" role="document">
  <div class="modal-content br-modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\BrandController::class, 'store']), 'method' => 'post', 'id' => $quick_add ? 'quick_add_brand_form' : 'brand_add_form' ]) !!}

    <div class="modal-header br-modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title"><i class="fas fa-plus-circle"></i> @lang( 'brand.add_brand' )</h4>
      <p class="br-modal-sub">Create a brand to organize products in your catalog.</p>
    </div>

    <div class="modal-body br-modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'brand.brand_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'brand.brand_name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'brand.short_description' ) . ':') !!}
          {!! Form::text('description', null, ['class' => 'form-control','placeholder' => __( 'brand.short_description' )]); !!}
      </div>

        @if($is_repair_installed)
          <div class="form-group">
             <label>
                {!!Form::checkbox('use_for_repair', 1, false, ['class' => 'input-icheck']) !!}
                {{ __( 'repair::lang.use_for_repair' )}}
            </label>
            @show_tooltip(__('repair::lang.use_for_repair_help_text'))
          </div>
        @endif

    </div>

    <div class="modal-footer br-modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
