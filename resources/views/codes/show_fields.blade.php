<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', __('codes.code')) !!}
    <p>{{ $code->code }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', __('codes.description')) !!}
    <p>{{ $code->description }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    {!! Form::label('value', __('codes.value')) !!}
    <p>{{ $code->value }}</p>
</div>

<!-- Sequence Field -->
<div class="form-group">
    {!! Form::label('sequence', __('codes.sequence')) !!}
    <p>{{ $code->sequence }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $code->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $code->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $code->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $code->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $code->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $code->INT_UDF3 }}</p>
</div> --}}

<!-- Created At Field -->
<div class="form-group">
    {!! Form::label('created_at', __('codes.created_at')) !!}
    <p>{{ $code->created_at }}</p>
</div>

<!-- Updated At Field -->
<div class="form-group">
    {!! Form::label('updated_at', __('codes.updated_at')) !!}
    <p>{{ $code->updated_at }}</p>
</div>

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