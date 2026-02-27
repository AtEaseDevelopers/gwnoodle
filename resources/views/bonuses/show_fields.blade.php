<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $bonus->name }}</p>
</div>

{{-- <!-- Vendor Id Field -->
<div class="form-group">
    {!! Form::label('vendor_id', 'Vendor:') !!}
    <p>{{ $bonus->vendor->code }}</p>
</div>

<!-- Source Id Field -->
<div class="form-group">
    {!! Form::label('source_id', 'Source:') !!}
    <p>{{ $bonus->source->code }}</p>
</div>

<!-- Destinate Id Field -->
<div class="form-group">
    {!! Form::label('destinate_id', 'Destination:') !!}
    <p>{{ $bonus->destinate->code }}</p>
</div> --}}

<!-- Target Field -->
<div class="form-group">
    {!! Form::label('target', 'Target:') !!}
    <p>{{ number_format($bonus->target,2,'.','') }}</p>
</div>

<!-- Bonusstart Field -->
<div class="form-group">
    {!! Form::label('bonusstart', 'Bonus Start:') !!}
    <p>{{ $bonus->bonusstart }}</p>
</div>

<!-- Bonusend Field -->
<div class="form-group">
    {!! Form::label('bonusend', 'Bonus End:') !!}
    <p>{{ $bonus->bonusend }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ number_format($bonus->amount,2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $bonus->status == 1 ? "Active" : "Unactive" }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $bonus->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $bonus->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $bonus->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $bonus->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $bonus->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $bonus->INT_UDF3 }}</p>
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