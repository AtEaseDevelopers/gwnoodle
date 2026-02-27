<!-- EmployeeId Field -->
<div class="form-group">
    {!! Form::label('Employeeid', __('agents.employee_id')) !!}:
    <p>{{ $agent->employeeid }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('agents.name')) !!}:
    <p>{{ $agent->name }}</p>
</div>

<!-- Ic Field -->
<div class="form-group">
    {!! Form::label('ic', __('agents.ic')) !!}:
    <p>{{ $agent->ic }}</p>
</div>

<!-- Phone Field -->
<div class="form-group">
    {!! Form::label('phone', __('agents.phone')) !!}:
    <p>{{ $agent->phone }}</p>
</div>

<!-- CommissionRate Field -->
<!-- <div class="form-group">
    {!! Form::label('phone', 'Commission Rate:') !!}
    <p>{{ $agent->commissionrate }}</p>
</div> -->

<!-- bankdetails1 Field -->
<div class="form-group">
    {!! Form::label('bankdetails1', __('agents.bank_details_1')) !!}:
    <p>{{ $agent->bankdetails1 }}</p>
</div>

<!-- bankdetails2 Field -->
<div class="form-group">
    {!! Form::label('bankdetails2', __('agents.bank_details_2')) !!}:
    <p>{{ $agent->bankdetails2 }}</p>
</div>

<!-- 1vaccine Field -->
<div class="form-group">
    {!! Form::label('firstvaccine', __('agents.first_vaccine_date')) !!}:
    <p>{{ $agent->firstvaccine }}</p>
</div>

<!-- 2vaccine Field -->
<div class="form-group">
    {!! Form::label('secondvaccine', __('agents.second_vaccine_date')) !!}:
    <p>{{ $agent->secondvaccine }}</p>
</div>

<!-- temperature Field -->
<div class="form-group">
    {!! Form::label('temperature', __('agents.body_temperature')) !!}:
    <p>{{ $agent->temperature }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('agents.status')) !!}:
    <p>{{ $agent->status == 1 ? __('agents.active') : __('agents.unactive') }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('agents.remark'))!!}:
    <p>{{ $agent->remark }}</p>
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