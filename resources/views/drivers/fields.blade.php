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

<!-- Ic Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('ic', __('drivers.ic')) !!}
    {!! Form::text('ic', null, ['class' => 'form-control', 'maxlength' => 20]) !!}
</div> -->

<!-- Phone Field -->
<div class="form-group col-sm-6">
    {!! Form::label('phone', __('drivers.phone')) !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('commissionrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0', 'required'=> 'true']) !!}
</div> -->

<!-- bankdetails1 Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('bankdetails1', __('drivers.bank_details_1')) !!}
    {!! Form::text('bankdetails1', null, ['class' => 'form-control', 'maxlength' => 255]) !!} 
</div> -->

<!-- bankdetails2 Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('bankdetails2', __('drivers.bank_details_2')) !!}
    {!! Form::text('bankdetails2', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div> -->

<!-- firstvaccine Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('firstvaccine', '1st Vaccine Date:') !!}
    {!! Form::text('firstvaccine', null, ['class' => 'form-control','id'=>'firstvaccine']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#firstvaccine').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush -->

<!-- secondvaccine Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('secondvaccine', '2nd Vaccine Date:') !!}
    {!! Form::text('secondvaccine', null, ['class' => 'form-control','id'=>'secondvaccine']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#secondvaccine').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush -->

<!-- temperature Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('temperature', 'Body Temperature:') !!}
    {!! Form::number('temperature', null, ['class' => 'form-control','step'=>'0.1','min'=>'0']) !!}
</div> -->

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
    {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
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