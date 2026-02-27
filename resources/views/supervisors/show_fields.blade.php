<!-- EmployeeId Field -->
<div class="form-group">
    {!! Form::label('Employeeid', __('operations.employee_id')) !!}:
    <p>{{ $supervisor->employeeid }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('operations.name')) !!}:
    <p>{{ $supervisor->name }}</p>
</div>

<!-- Ic Field -->
<div class="form-group">
    {!! Form::label('ic', __('operations.ic')) !!}:
    <p>{{ $supervisor->ic }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', __('operations.phone')) !!}:
    <p>{{ $supervisor->phone }}</p>
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group">
    {!! Form::label('phone', 'Commission Rate:') !!}
    <p>{{ $supervisor->commissionrate }}</p>
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group">
    {!! Form::label('bankdetails1', __('operations.bank_details_1')) !!}:
    <p>{{ $supervisor->bankdetails1 }}</p>
</div>

<!-- bankdetails2 Field -->
<div class="form-group">
    {!! Form::label('bankdetails2', __('operations.bank_details_2')) !!}:
    <p>{{ $supervisor->bankdetails2 }}</p>
</div>

<!-- 1vaccine Field -->
<div class="form-group">
    {!! Form::label('firstvaccine', __('operations.first_vaccine_date')) !!}:
    <p>{{ $supervisor->firstvaccine }}</p>
</div>

<!-- 2vaccine Field -->
<div class="form-group">
    {!! Form::label('secondvaccine', __('operations.second_vaccine_date')) !!}:
    <p>{{ $supervisor->secondvaccine }}</p>
</div>

<!-- temperature Field -->
<div class="form-group">
    {!! Form::label('temperature', __('operations.body_temperature')) !!}:
    <p>{{ $supervisor->temperature }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('operations.status')) !!}:
    <p>{{ $supervisor->status == 1 ? __('operations.active') : __('operations.unactive') }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('operations.remark')) !!}:
    <p>{{ $supervisor->remark }}</p>
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