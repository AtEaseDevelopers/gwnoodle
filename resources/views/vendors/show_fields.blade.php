<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', 'Code:') !!}
    <p>{{ $vendor->code }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $vendor->name }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', 'Phone:') !!}
    <p>{{ $vendor->phone }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $vendor->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $vendor->remark }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $vendor->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $vendor->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $vendor->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $vendor->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $vendor->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $vendor->INT_UDF3 }}</p>
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

