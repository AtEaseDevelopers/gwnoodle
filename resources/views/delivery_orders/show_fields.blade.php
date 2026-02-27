<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $deliveryOrder->date }}</p>
</div>

<!-- Dono Field -->
<div class="form-group">
    {!! Form::label('dono', 'Do Number:') !!}
    <p>{{ $deliveryOrder->dono }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', 'Driver:') !!}
    <p>{{ $deliveryOrder->driver->name }}</p>
</div>

<!-- Lorry Id Field -->
<div class="form-group">
    {!! Form::label('lorry_id', 'Lorry:') !!}
    <p>{{ $deliveryOrder->lorry->lorryno }}</p>
</div>

<!-- Vendor Id Field -->
<div class="form-group">
    {!! Form::label('vendor_id', 'Vendor:') !!}
    <p>{{ $deliveryOrder->vendor->code }}</p>
</div>

<!-- Source Id Field -->
<div class="form-group">
    {!! Form::label('source_id', 'Source:') !!}
    <p>{{ $deliveryOrder->source->code }}</p>
</div>

<!-- Destinate Id Field -->
<div class="form-group">
    {!! Form::label('destinate_id', 'Destination:') !!}
    <p>{{ $deliveryOrder->destinate->code }}</p>
</div>

<!-- Item Id Field -->
<div class="form-group">
    {!! Form::label('item_id', 'Product:') !!}
    <p>{{ $deliveryOrder->item->code }}</p>
</div>

<!-- Weight Field -->
<div class="form-group">
    {!! Form::label('weight', 'Source Weight:') !!}
    <p>{{ number_format($deliveryOrder->weight,2,'.','') }}</p>
</div>

<!-- Shipweight Field -->
<div class="form-group">
    {!! Form::label('shipweight', 'Destination Weight:') !!}
    <p>{{ number_format($deliveryOrder->shipweight,2,'.','') }}</p>
</div>

<!-- Fees Field -->
<div class="form-group">
    {!! Form::label('fees', 'Loading/Unloading Fees:') !!}
    <p>{{ number_format($deliveryOrder->fees,2,'.','') }}</p>
</div>

<!-- Tol Field -->
<div class="form-group">
    {!! Form::label('tol', 'Tol:') !!}
    <p>{{ number_format($deliveryOrder->tol,2,'.','') }}</p>
</div>

<!-- Billingrate Field -->
<div class="form-group">
    {!! Form::label('billingrate', 'Billing Rate:') !!}
    <p>{{ number_format($deliveryOrder->billingrate,2,'.','') }}</p>
</div>

<!-- Commissionrate Field -->
<div class="form-group">
    {!! Form::label('billingrate', 'Commission Rate:') !!}
    <p>{{ number_format($deliveryOrder->commissionrate,2,'.','') }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $deliveryOrder->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $deliveryOrder->remark }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $deliveryOrder->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $deliveryOrder->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $deliveryOrder->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $deliveryOrder->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $deliveryOrder->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $deliveryOrder->INT_UDF3 }}</p>
</div> --}}

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush