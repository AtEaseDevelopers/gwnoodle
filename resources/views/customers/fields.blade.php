<div class="row">
    <div class="col-md-6">
        <!-- Code Field -->
        <div class="form-group">
            {!! Form::label('code', __('customers.code')) !!}:<span class="asterisk"> *</span>
            {!! Form::text('code', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!} <!-- Removed duplicate maxlength -->
        </div>

        <!-- Company Field -->
        <div class="form-group">
            {!! Form::label('company', __('customers.company')) !!}:<span class="asterisk"> *</span>
            {!! Form::text('company', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
        </div>

        <!-- Paymentterm Field -->
        <div class="form-group">
            {!! Form::label('paymentterm', __('customers.payment_term')) !!}:<span class="asterisk"> *</span>
            {{ Form::select('paymentterm', [
                1 => __('customers.payment_term_cash'),
                2 => __('customers.payment_term_credit_note'),
            ], null, ['class' => 'form-control']) }}
        </div>

        <!-- Group Field -->
        <div class="form-group">
            {!! Form::label('group', __('customers.group')) !!}:
            {!! Form::select('group[]', $groups, explode(",", $customer->group ?? ""), ['class' => 'selectpicker form-control', 'multiple' => true]) !!}
            {{-- {!! Form::select('group[]', $groups, explode(",", $customer->group ?? ""), ['class' => 'selectpicker form-control', 'placeholder' => 'Select Group']) !!} --}}
        </div>

        <!-- Agent Id Field -->
        <div class="form-group">
            {!! Form::label('agent_id', __('customers.agent')) !!}:
            {!! Form::select('agent_id', $agentItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Agent...']) !!}
        </div>

        <!-- Supervisor Id Field -->
        <div class="form-group">
            {!! Form::label('supervisor_id', __('customers.operation')) !!}:
            {!! Form::select('supervisor_id', $supervisorItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Operation...']) !!}
        </div>

        <!-- Phone Field -->
        <div class="form-group ">
            {!! Form::label('phone', __('customers.phone')) !!}:
            {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 20]) !!} <!-- Removed duplicate maxlength -->
        </div>

        <!-- Address Field -->
        <div class="form-group ">
            {!! Form::label('address', __('customers.address')) !!}:
            {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 65535]) !!} <!-- Removed duplicate maxlength -->
        </div>

        <!-- Status Field -->
        <div class="form-group ">
            {!! Form::label('status', __('customers.status')) !!}:<span class="asterisk"> *</span>
            {{ Form::select('status', [
                1 => __('customers.active'),
                0 => __('customers.unactive'),
            ], null, ['class' => 'form-control']) }}
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
                    {!! Form::label('email', 'Email:') !!}
                    {!! Form::email('email', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- City Field -->
                <div class="form-group">
                    {!! Form::label('city', 'City:') !!}
                    {!! Form::text('city', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- Postcode Field -->
                <div class="form-group">
                    {!! Form::label('postcode', 'Postcode:') !!}
                    {!! Form::text('postcode', null, ['class' => 'form-control','maxlength' => 20]) !!}
                </div>

                <!-- State Field -->
                <div class="form-group">
                    {!! Form::label('state', 'State:') !!}
                    {!! Form::select('state', $stateOptions ?? [], null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-size' => '10', 'data-dropup-auto' => 'false', 'id' => 'state_code', 'placeholder' => 'Select State...']) !!}
                </div>

                <!-- Country Field -->
                <div class="form-group">
                    {!! Form::label('country', 'Country:') !!}
                    {!! Form::select('country', $countryOptions ?? [], null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-size' => '10', 'data-dropup-auto' => 'false', 'id' => 'country_code', 'placeholder' => 'Select Country...']) !!}
                </div>

                <!-- Registration No Field -->
                <div class="form-group">
                    {!! Form::label('registration_no', 'Company Registration No. / IC No.:') !!}
                    {!! Form::text('registration_no', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- TIN Field -->
                <div class="form-group">
                    {!! Form::label('tin', 'TIN:') !!}
                    {!! Form::text('tin', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- MSIC Field -->
                <div class="form-group">
                    {!! Form::label('msic', 'MSIC:') !!}
                    {!! Form::text('msic', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- SST Registration No Field -->
                <div class="form-group">
                    {!! Form::label('sst_registration_no', 'SST Registration No.:') !!}
                    {!! Form::text('sst_registration_no', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>

                <!-- Tourism Tax Registration Field -->
                <div class="form-group">
                    {!! Form::label('tourism_tax_registration', 'Tourism Tax Registration:') !!}
                    {!! Form::text('tourism_tax_registration', null, ['class' => 'form-control','maxlength' => 255]) !!}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('customers.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">{{ __('customers.cancel') }}</a>
</div>

@push('styles')
    <style>
        #state_code + .dropdown-menu,
        #country_code + .dropdown-menu,
        select[name="state"] + .dropdown-menu,
        select[name="country"] + .dropdown-menu,
        .bootstrap-select.show .dropdown-menu {
            max-height: 400px !important;
            overflow-y: auto !important;
            max-width: 400px !important;
            width: auto !important;
        }
        .bootstrap-select .dropdown-menu.inner {
            max-height: 350px !important;
            overflow-y: auto !important;
        }
        .bootstrap-select button {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            $('.selectpicker').selectpicker({
                size: 10
            });
            $('#state_code, #country_code').on('shown.bs.select', function () {
                $('.bootstrap-select .dropdown-menu').css({
                    'max-height': '400px',
                    'max-width': '400px',
                    'width': 'auto'
                });
            });
            HideLoad();
        });
    </script>
@endpush
