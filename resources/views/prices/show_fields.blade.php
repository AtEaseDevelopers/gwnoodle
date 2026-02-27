<!-- Item Id Field -->
<div class="form-group">
    {!! Form::label('item_id', 'Product:') !!}
    <p>{{ $price->item->code }}</p>
</div>

<!-- Vendor Id Field -->
<div class="form-group">
    {!! Form::label('vendor_id', 'Vendor:') !!}
    <p>{{ $price->vendor->code }}</p>
</div>

<!-- Source Id Field -->
<div class="form-group">
    {!! Form::label('source_id', 'Source:') !!}
    <p>{{ $price->source->code }}</p>
</div>

<!-- Destinate Id Field -->
<div class="form-group">
    {!! Form::label('destinate_id', 'Destination:') !!}
    <p>{{ $price->destinate->code }}</p>
</div>

<!-- Minrange Field -->
<div class="form-group">
    {!! Form::label('minrange', 'Min (TON):') !!}
    <p>{{ number_format($price->minrange,2,'.','') }}</p>
</div>

<!-- Maxrange Field -->
<div class="form-group">
    {!! Form::label('maxrange', 'Max (TON):') !!}
    <p>{{ number_format($price->maxrange,2,'.','') }}</p>
</div>

<!-- Billingrate Field -->
<div class="form-group">
    {!! Form::label('billingrate', 'Billing Rate:') !!}
    <p>{{ number_format($price->billingrate,2) }}</p>
</div>

<!-- Commissionrate Field -->
<div class="form-group">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}
    <p>{{ number_format($price->commissionrate,2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $price->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $price->remark }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $price->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $price->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $price->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $price->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $price->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $price->INT_UDF3 }}</p>
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