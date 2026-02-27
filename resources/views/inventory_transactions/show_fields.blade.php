<!-- Lorry Id Field -->
<div class="form-group">
    {!! Form::label('lorry_id', 'Lorry Id:') !!}
    <p>{{ $inventoryTransaction->lorry_id }}</p>
</div>

<!-- Product Id Field -->
<div class="form-group">
    {!! Form::label('product_id', 'Product Id:') !!}
    <p>{{ $inventoryTransaction->product_id }}</p>
</div>

<!-- Quantity Field -->
<div class="form-group">
    {!! Form::label('quantity', 'Quantity:') !!}
    <p>{{ $inventoryTransaction->quantity }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $inventoryTransaction->type }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $inventoryTransaction->remark }}</p>
</div>

<!-- User Field -->
<div class="form-group">
    {!! Form::label('user', 'User:') !!}
    <p>{{ $inventoryTransaction->user }}</p>
</div>

