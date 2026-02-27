<!-- Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorry_id', 'Lorry Id:') !!}
    {!! Form::select('lorry_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Lorry Id...']) !!}
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

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('inventoryBalances.index') }}" class="btn btn-secondary">Cancel</a>
</div>
