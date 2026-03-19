<!-- EmployeeId Field -->
<div class="form-group col-sm-6">
    {!! Form::label('employeeid', __('drivers.employee_id')) !!}<span class="asterisk"> *</span>
    {!! Form::text('employeeid', null, ['class' => 'form-control', 'maxlength' => 20, 'autofocus']) !!}
</div>

<!-- Password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password', __('drivers.employee_password')) !!}<span class="asterisk"> *</span>
    {!! Form::text('password', null, ['class' => 'form-control', 'maxlength' => 65535]) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('drivers.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Invoice Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('invoice_code', __('Invoice Code')) !!}<span class="asterisk"> *</span>
    {!! Form::text('invoice_code', null, ['class' => 'form-control', 'maxlength' => 10]) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('drivers.status')) !!}<span class="asterisk"> *</span>
    {{ Form::select('status', [
        1 => __('drivers.active'),
        0 => __('drivers.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', __('drivers.remark')) !!}
    {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('drivers.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">{{ __('drivers.cancel') }}</a>
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