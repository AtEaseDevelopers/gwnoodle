<div class="row">
    <div class="col-md-6">
        <!-- Code Field -->
        <div class="form-group">
            {!! Form::label('code', __('customers.code')) !!}:
            <p>{{ $customer->code }}</p>
        </div>

        <!-- Company Field -->
        <div class="form-group">
            {!! Form::label('company', __('customers.company')) !!}:
            <p>{{ $customer->company }}</p>
        </div>

        <!-- Paymentterm Field -->
        <div class="form-group">
            {!! Form::label('paymentterm', __('customers.payment_term')) !!}:
            @php
                $paymenttermText = '';
                if($customer->paymentterm == 1){
                    $paymenttermText = __('customers.payment_term_cash');
                } elseif($customer->paymentterm == 2){
                    $paymenttermText = __('customers.payment_term_bankin');
                } elseif($customer->paymentterm == 3){
                    $paymenttermText = __('customers.payment_term_credit_note');
                } else {
                    $paymenttermText = $customer->paymentterm;
                }
            @endphp
            <p>{{ $paymenttermText }}</p>
        </div>

        <!-- Group Field -->
        <div class="form-group">
            {!! Form::label('group', __('customers.group')) !!}:
            <p>{{ $customer->group ?? '-' }}</p>
        </div>

        <!-- Category Field -->
        <div class="form-group">
            {!! Form::label('category', __('Category')) !!}:
            <p>{{ $customer->category ?? '-' }}</p>
        </div>

        <!-- Driver Id Field -->
        <div class="form-group">
            {!! Form::label('driver_id', __('Driver')) !!}:
            <p>{{ $customer->driver->name ?? '-' }}</p>
        </div>

        <!-- Agent Id Field -->
        <div class="form-group">
            {!! Form::label('agent_id', __('customers.agent')) !!}:
            <p>{{ $customer->agent->name ?? '-' }}</p>
        </div>

        <!-- Phone Field -->
        <div class="form-group">
            {!! Form::label('phone', __('customers.phone')) !!}:
            <p>{{ $customer->phone ?: '-' }}</p>
        </div>

        <!-- Billing Address Field -->
        <div class="form-group">
            {!! Form::label('billing_address', __('Billing Address')) !!}:
            <p>{{ $customer->billing_address ?: '-' }}</p>
        </div>

        <!-- Delivery Address Field -->
        <div class="form-group">
            {!! Form::label('delivery_address', __('Delivery Address')) !!}:
            <p>{{ $customer->delivery_address ?: '-' }}</p>
        </div>

        <!-- Status Field -->
        <div class="form-group">
            {!! Form::label('status', __('customers.status')) !!}:
            <p>{{ $customer->status == 1 ? __('customers.active') : __('customers.unactive') }}</p>
        </div>
    </div>

    @einvoice
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong>e-Invoice Required Details</strong>
            </div>
            <div class="card-body">
                <!-- Email Field -->
                <div class="form-group">
                    {!! Form::label('email', 'Email:') !!}:
                    <p>{{ $customer->email ?: '-' }}</p>
                </div>

                <!-- City Field -->
                <div class="form-group">
                    {!! Form::label('city', 'City:') !!}:
                    <p>{{ $customer->city ?: '-' }}</p>
                </div>

                <!-- Postcode Field -->
                <div class="form-group">
                    {!! Form::label('postcode', 'Postcode:') !!}:
                    <p>{{ $customer->postcode ?: '-' }}</p>
                </div>

                <!-- State Field -->
                <div class="form-group">
                    {!! Form::label('state', 'State:') !!}:
                    <p>{{ App\Models\StateCode::where('code', $customer->state)->first()->state ?? '-' }}</p>
                </div>

                <!-- Country Field -->
                <div class="form-group">
                    {!! Form::label('country', 'Country:') !!}:
                    <p>{{ $customer->country ?: '-' }}</p>
                </div>

                <!-- Registration No Field -->
                <div class="form-group">
                    {!! Form::label('registration_no', 'Company Registration No. / IC No.:') !!}:
                    <p>{{ $customer->registration_no ?: '-' }}</p>
                </div>

                <!-- TIN Field -->
                <div class="form-group">
                    {!! Form::label('tin', 'TIN:') !!}:
                    <p>{{ $customer->tin ?: '-' }}</p>
                </div>

                <!-- MSIC Field -->
                <div class="form-group">
                    {!! Form::label('msic', 'MSIC:') !!}:
                    <p>{{ $customer->msic ?: '-' }}</p>
                </div>

                <!-- SST Registration No Field -->
                <div class="form-group">
                    {!! Form::label('sst_registration_no', 'SST Registration No.:') !!}:
                    <p>{{ $customer->sst_registration_no ?: '-' }}</p>
                </div>

                <!-- Tourism Tax Registration Field -->
                <div class="form-group">
                    {!! Form::label('tourism_tax_registration', 'Tourism Tax Registration:') !!}:
                    <p>{{ $customer->tourism_tax_registration ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>


@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.form-group a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush