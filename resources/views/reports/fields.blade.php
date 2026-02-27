<div class="form-group col-sm-6">
    {!! Form::label('name', __('report.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!}
</div>

<!-- Sqlvalue Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sqlvalue', __('report.sql')) !!}<span class="asterisk"> *</span>
    {!! Form::textarea('sqlvalue', null, ['class' => 'form-control', 'rows' => '1']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('report.status')) !!}<span class="asterisk"> *</span>
    {{ Form::select('status', [
        1 => __('report.active'),
        0 => __('report.unactive')
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Str Udf1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    {!! Form::textarea('STR_UDF1', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Str Udf2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    {!! Form::textarea('STR_UDF2', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Str Udf3 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    {!! Form::textarea('STR_UDF3', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Int Udf1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    {!! Form::number('INT_UDF1', null, ['class' => 'form-control']) !!}
</div>

<!-- Int Udf2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    {!! Form::number('INT_UDF2', null, ['class' => 'form-control']) !!}
</div>

<!-- Int Udf3 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    {!! Form::number('INT_UDF3', null, ['class' => 'form-control']) !!}
</div>
<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('report.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('reports.index') }}" class="btn btn-secondary">{{ __('report.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush