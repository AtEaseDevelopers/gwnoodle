<!-- EmployeeId Field -->
<div class="form-group">
    {!! Form::label('Employeeid', __('kelindans.employee_id')) !!}:
    <p>{{ $kelindan->employeeid }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('kelindans.name')) !!}:
    <p>{{ $kelindan->name }}</p>
</div>

<!-- Ic Field -->
<div class="form-group">
    {!! Form::label('ic', __('kelindans.ic')) !!}:
    <p>{{ $kelindan->ic }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', __('kelindans.phone')) !!}:
    <p>{{ $kelindan->phone }}</p>
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group">
    {!! Form::label('phone', 'Commission Rate:') !!}
    <p>{{ $kelindan->commissionrate }}</p>
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group">
    {!! Form::label('bankdetails1', __('kelindans.bank_details_1')) !!}:
    <p>{{ $kelindan->bankdetails1 }}</p>
</div>

<!-- bankdetails2 Field -->
<div class="form-group">
    {!! Form::label('bankdetails2', __('kelindans.bank_details_2')) !!}:
    <p>{{ $kelindan->bankdetails2 }}</p>
</div>

<!-- 1vaccine Field -->
<!-- <div class="form-group">
    {!! Form::label('firstvaccine', '1st Vaccine Date:') !!}
    <p>{{ $kelindan->firstvaccine }}</p>
</div> -->

<!-- 2vaccine Field -->
<!-- <div class="form-group">
    {!! Form::label('secondvaccine', '2nd Vaccine Date:') !!}
    <p>{{ $kelindan->secondvaccine }}</p>
</div> -->

<!-- temperature Field -->
<!-- <div class="form-group">
    {!! Form::label('temperature', 'Body Temperature:') !!}
    <p>{{ $kelindan->temperature }}</p>
</div> -->

<!-- permitdate Field -->
<div class="form-group">
    {!! Form::label('permitdate', __('kelindans.permit_date')) !!}:
    <p>{{ $kelindan->permitdate }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('kelindans.status')) !!}:
    <p>{{ $kelindan->status == 1 ? __('kelindans.active') : __('kelindans.unactive') }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('kelindans.remark')) !!}:
    <p>{{ $kelindan->remark }}</p>
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