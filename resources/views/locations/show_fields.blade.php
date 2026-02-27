<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', 'Code:') !!}
    <p>{{ $location->code }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $location->name }}</p>
</div>

<!-- Source Field -->
<div class="form-group">
    {!! Form::label('source', 'Source:') !!}
    <p>{{ $location->source == 1 ? "Yes" : "No" }}</p>
</div>

<!-- Destination Field -->
<div class="form-group">
    {!! Form::label('destination', 'Destination:') !!}
    <p>{{ $location->destination == 1 ? "Yes" : "No" }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', 'Phone:') !!}
    <p>{{ $location->phone }}</p>
</div>

{{-- <!-- Address1 Field -->
<div class="form-group">
    {!! Form::label('address1', 'Address1:') !!}
    <p>{{ $location->address1 }}</p>
</div>

<!-- Address2 Field -->
<div class="form-group">
    {!! Form::label('address2', 'Address2:') !!}
    <p>{{ $location->address2 }}</p>
</div>

<!-- Address3 Field -->
<div class="form-group">
    {!! Form::label('address3', 'Address3:') !!}
    <p>{{ $location->address3 }}</p>
</div>

<!-- Address4 Field -->
<div class="form-group">
    {!! Form::label('address4', 'Address4:') !!}
    <p>{{ $location->address4 }}</p>
</div> --}}

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $location->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $location->remark }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $location->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $location->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $location->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $location->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $location->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $location->INT_UDF3 }}</p>
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