<!-- From Driver Id Field -->
<div class="form-group">
    {!! Form::label('from_driver_id', 'From Driver Id:') !!}
    <p>{{ $inventoryTransfer->from_driver_id }}</p>
</div>

<!-- From Lorry Id Field -->
<div class="form-group">
    {!! Form::label('from_lorry_id', 'From Lorry Id:') !!}
    <p>{{ $inventoryTransfer->from_lorry_id }}</p>
</div>

<!-- To Driver Id Field -->
<div class="form-group">
    {!! Form::label('to_driver_id', 'To Driver Id:') !!}
    <p>{{ $inventoryTransfer->to_driver_id }}</p>
</div>

<!-- To Lorry Id Field -->
<div class="form-group">
    {!! Form::label('to_lorry_id', 'To Lorry Id:') !!}
    <p>{{ $inventoryTransfer->to_lorry_id }}</p>
</div>

<!-- Product Id Field -->
<div class="form-group">
    {!! Form::label('product_id', 'Product Id:') !!}
    <p>{{ $inventoryTransfer->product_id }}</p>
</div>

<!-- Quantity Field -->
<div class="form-group">
    {!! Form::label('quantity', 'Quantity:') !!}
    <p>{{ $inventoryTransfer->quantity }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $inventoryTransfer->status }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $inventoryTransfer->remark }}</p>
</div>

