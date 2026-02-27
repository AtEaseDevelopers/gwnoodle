<!-- EmployeeId Field -->
<div class="form-group">
    {!! Form::label('Employeeid', __('drivers.employee_id')) !!}:
    <p>{{ $driver->employeeid }}</p>
</div>

<!-- Password Field -->
<div class="form-group">
    {!! Form::label('password', __('drivers.employee_password')) !!}:
    <p>{{ $driver->password }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('drivers.name')) !!}:
    <p>{{ $driver->name }}</p>
</div>

<!-- Ic Field -->
<div class="form-group">
    {!! Form::label('ic', __('drivers.ic')) !!}:
    <p>{{ $driver->ic }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', __('drivers.phone')) !!}:
    <p>{{ $driver->phone }}</p>
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group">
    {!! Form::label('phone', 'Commission Rate:') !!}
    <p>{{ $driver->commissionrate }}</p>
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group">
    {!! Form::label('bankdetails1', __('drivers.bank_details_1')) !!}:
    <p>{{ $driver->bankdetails1 }}</p>
</div>

<!-- bankdetails2 Field -->
<div class="form-group">
    {!! Form::label('bankdetails2', __('drivers.bank_details_2')) !!}:
    <p>{{ $driver->bankdetails2 }}</p>
</div>

<!-- 1vaccine Field -->
<!-- <div class="form-group">
    {!! Form::label('firstvaccine', '1st Vaccine Date:') !!}
    <p>{{ $driver->firstvaccine }}</p>
</div> -->

<!-- 2vaccine Field -->
<!-- <div class="form-group">
    {!! Form::label('secondvaccine', '2nd Vaccine Date:') !!}
    <p>{{ $driver->secondvaccine }}</p>
</div> -->

<!-- temperature Field -->
<!-- <div class="form-group">
    {!! Form::label('temperature', 'Body Temperature:') !!}
    <p>{{ $driver->temperature }}</p>
</div> -->

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('drivers.status')) !!}:
    <p>{{ $driver->status == 1 ? __('drivers.active') : __('drivers.unactive') }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('drivers.remark')) !!}:
    <p>{{ $driver->remark }}</p>
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