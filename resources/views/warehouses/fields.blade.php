<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Warehouse Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Enter warehouse name...', 'required']) !!}
</div>

<!-- Location Field -->
<div class="form-group col-sm-6">
    {!! Form::label('location', 'Location:') !!}
    {!! Form::text('location', null, ['class' => 'form-control', 'placeholder' => 'Enter location...']) !!}
</div>

<!-- Stock Out Enabled Field -->
<div class="form-group col-sm-12">
    <div class="custom-control custom-checkbox">
        {!! Form::checkbox('stock_out_enabled', 1, null, ['class' => 'custom-control-input', 'id' => 'stock_out_enabled']) !!}
        {!! Form::label('stock_out_enabled', 'Enable Stock Out', ['class' => 'custom-control-label']) !!}
        <small class="form-text text-muted">If enabled, this warehouse can be used for stock out operations</small>
    </div>
</div>

<!-- Status Field - Only show on edit page -->
@if(isset($warehouse))
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive'], null, ['class' => 'form-control', 'placeholder' => 'Select status...', 'required']) !!}
</div>
@endif

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush