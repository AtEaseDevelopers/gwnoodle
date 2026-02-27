<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $claim->date }}</p>
</div>

<!-- No Field -->
<div class="form-group">
    {!! Form::label('no', 'Number:') !!}
    <p>{{ $claim->no }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', 'Driver:') !!}
    <p>{{ $claim->driver->name }}</p>
</div>
@php
    if (isset($claim->lorry)) {
        $lorryno = $claim->lorry->lorryno;
    }else{
        $lorryno = '';
    }
@endphp
<!-- Lorry Id Field -->
<div class="form-group">
    {!! Form::label('lorry_id', 'Lorry:') !!}
    <p>{{ $lorryno }}</p>
</div>
@php
    if (isset($claim->deliveryorder)) {
        $dono = $claim->deliveryorder->dono;
    }else{
        $dono = '';
    }
@endphp
<!-- Deliveryorder Id Field -->
<div class="form-group">
    {!! Form::label('deliveryorder_id', 'Delivery Order:') !!}
    <p>{{ $dono }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $claim->description }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ number_format($claim->amount,2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $claim->status == 1 ? "Paid" : "Unpaid" }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $claim->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $claim->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $claim->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $claim->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $claim->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $claim->INT_UDF3 }}</p>
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