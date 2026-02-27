<!-- EmployeeId Field -->
<div class="form-group col-sm-6">
    {!! Form::label('employeeid', __('kelindans.employee_id')) !!}<span class="asterisk"> *</span>
    {!! Form::text('employeeid', null, ['class' => 'form-control', 'maxlength' => 20, 'autofocus']) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('kelindans.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Ic Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ic', __('kelindans.ic')) !!}
    {!! Form::text('ic', null, ['class' => 'form-control', 'maxlength' => 20]) !!}
</div>

<!-- Phone Field -->
<div class="form-group col-sm-6">
    {!! Form::label('phone', __('kelindans.phone')) !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}<span class="asterisk"> *</span>
    {!! Form::number('commissionrate', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required' => 'true']) !!}
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bankdetails1', __('kelindans.bank_details_1')) !!}
    {!! Form::text('bankdetails1', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- bankdetails2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bankdetails2', __('kelindans.bank_details_2')) !!}
    {!! Form::text('bankdetails2', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- firstvaccine Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('firstvaccine', '1st Vaccine Date:') !!}
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
@endpush -->

<!-- secondvaccine Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('secondvaccine', '2nd Vaccine Date:') !!}
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
@endpush -->

<!-- temperature Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('temperature', 'Body Temperature:') !!}
    {!! Form::number('temperature', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0']) !!}
</div> -->

<!-- permitdate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('permitdate', __('kelindans.permit_date')) !!}
    {!! Form::text('permitdate', null, ['class' => 'form-control', 'id' => 'permitdate']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#permitdate').datetimepicker({
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

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('kelindans.status')) !!}<span class="asterisk"> *</span>
    {{ Form::select('status', [
        1 => __('kelindans.active'),
        0 => __('kelindans.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', __('kelindans.remark')) !!}
    {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('kelindans.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('kelindans.index') }}" class="btn btn-secondary">{{ __('kelindans.cancel') }}</a>
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