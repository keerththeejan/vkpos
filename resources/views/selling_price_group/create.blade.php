<div class="modal-dialog spg-modal-dialog" role="document">
  <div class="modal-content spg-modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\SellingPriceGroupController::class, 'store']), 'method' => 'post', 'id' => 'selling_price_group_form' ]) !!}

    <div class="modal-header spg-modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title"><i class="fas fa-plus-circle"></i> @lang( 'lang_v1.add_selling_price_group' )</h4>
      <p class="spg-modal-sub">Create a reusable price list for customers, locations, or POS.</p>
    </div>

    <div class="modal-body spg-modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'lang_v1.description' ) . ':') !!}
          {!! Form::textarea('description', null, ['class' => 'form-control','placeholder' => __( 'lang_v1.description' ), 'rows' => 3]); !!}
      </div>

      <div class="spg-modal-hint">
        <i class="fas fa-info-circle"></i>
        After creating a group, assign product prices from product edit or <a href="{{ url('/update-product-price') }}" target="_blank">Update Product Price</a>.
      </div>
    </div>

    <div class="modal-footer spg-modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
