<!-- From Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('from_driver_id', 'From Driver Id:') !!}
    {!! Form::select('from_driver_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a From Driver Id...']) !!}
</div>


<!-- From Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('from_lorry_id', 'From Lorry Id:') !!}
    {!! Form::select('from_lorry_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a From Lorry Id...']) !!}
</div>


<!-- To Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('to_driver_id', 'To Driver Id:') !!}
    {!! Form::select('to_driver_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a To Driver Id...']) !!}
</div>


<!-- To Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('to_lorry_id', 'To Lorry Id:') !!}
    {!! Form::select('to_lorry_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a To Lorry Id...']) !!}
</div>


<!-- Product Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('product_id', 'Product Id:') !!}
    {!! Form::select('product_id', $productItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Product Id...']) !!}
</div>


<!-- Quantity Field -->
<div class="form-group col-sm-6">
    {!! Form::label('quantity', 'Quantity:') !!}
    {!! Form::text('quantity', null, ['class' => 'form-control']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::text('status', null, ['class' => 'form-control']) !!}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', 'Remark:') !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('inventoryTransfers.index') }}" class="btn btn-secondary">Cancel</a>
</div>
