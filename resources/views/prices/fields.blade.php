<!-- Item Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('item_id', 'Product:') !!}<span class="asterisk"> *</span>
    {!! Form::select('item_id', $itemItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Item...','data-live-search'=>'true','autofocus']) !!}
</div>

<!-- Vendor Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vendor_id', 'Vendor:') !!}<span class="asterisk"> *</span>
    {!! Form::select('vendor_id', $vendorItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Vendor...','data-live-search'=>'true']) !!}
</div>

<!-- Source Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('source_id', 'Source:') !!}<span class="asterisk"> *</span>
    {!! Form::select('source_id', $sourceItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Source...','data-live-search'=>'true']) !!}
</div>

<!-- Destinate Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('destinate_id', 'Destination:') !!}<span class="asterisk"> *</span>
    {!! Form::select('destinate_id', $destinateItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Destinate...','data-live-search'=>'true']) !!}
</div>

<!-- Minrange Field -->
<div class="form-group col-sm-6">
    {!! Form::label('minrange', 'Min (TON):') !!}<span class="asterisk"> *</span>
    {!! Form::number('minrange', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Maxrange Field -->
<div class="form-group col-sm-6">
    {!! Form::label('maxrange', 'Max (TON):') !!}<span class="asterisk"> *</span>
    {!! Form::number('maxrange', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Billingrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billingrate', 'Billing Rate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('billingrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Commissionrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}<span class="asterisk"> *</span>
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
    <a href="{{ route('prices.index') }}" class="btn btn-secondary">Cancel</a>
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