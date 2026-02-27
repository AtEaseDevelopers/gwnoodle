<!-- Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('code', 'Code:') !!}<span class="asterisk"> *</span>
    {!! Form::text('code', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255,'autofocus']) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Billingrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billingrate', 'Billingrate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('billingrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Commissionrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commissionrate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('commissionrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}<span class="asterisk"> *</span>
    {{ Form::select('status', array(1 => 'Active', 0 => 'Unactive'), null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', 'Remark:') !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

{{-- <!-- Str Udf1 Field -->
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
</div> --}}

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
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
