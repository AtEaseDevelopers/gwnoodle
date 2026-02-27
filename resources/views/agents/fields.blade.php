<!-- EmployeeId Field -->
<div class="form-group col-sm-6">
    {!! Form::label('employeeid', __('agents.employee_id')) !!}<span class="asterisk"> *</span>
    {!! Form::text('employeeid', null, ['class' => 'form-control', 'maxlength' => 20, 'autofocus']) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('agents.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!}
</div>

<!-- Ic Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ic', __('agents.ic')) !!}
    {!! Form::text('ic', null, ['class' => 'form-control', 'maxlength' => 20]) !!}
</div>

<!-- Phone Field -->
<div class="form-group col-sm-6">
    {!! Form::label('phone', __('agents.phone')) !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('commissionrate', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required' => 'true']) !!}
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bankdetails1', __('agents.bank_details_1')) !!}
    {!! Form::text('bankdetails1', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- bankdetails2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bankdetails2', __('agents.bank_details_2')) !!}
    {!! Form::text('bankdetails2', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- firstvaccine Field -->
<div class="form-group col-sm-6">
    {!! Form::label('firstvaccine', __('agents.first_vaccine_date')) !!}
    {!! Form::text('firstvaccine', null, ['class' => 'form-control', 'id' => 'firstvaccine']) !!}
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
@endpush

<!-- secondvaccine Field -->
<div class="form-group col-sm-6">
    {!! Form::label('secondvaccine', __('agents.second_vaccine_date')) !!}
    {!! Form::text('secondvaccine', null, ['class' => 'form-control', 'id' => 'secondvaccine']) !!}
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
@endpush

<!-- temperature Field -->
<div class="form-group col-sm-6">
    {!! Form::label('temperature', __('agents.body_temperature')) !!}
    {!! Form::number('temperature', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('agents.status')) !!}<span class="asterisk"> *</span>
    {{ Form::select('status', [
        1 => __('agents.active'),
        0 => __('agents.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', __('agents.remark')) !!}
    {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('agents.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('agents.index') }}" class="btn btn-secondary">{{ __('agents.cancel') }}</a>
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