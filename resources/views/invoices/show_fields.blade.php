<!-- Invoiceno Field -->
<div class="form-group">
    {!! Form::label('invoiceno', __('invoices.invoice_no')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->invoiceno }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('invoices.date')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->date }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('invoices.customer')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->customer->company ?? '' }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('invoices.driver')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->driver->name ?? '' }}</p>
</div>

<!-- Kelindan Id Field -->
<div class="form-group">
    {!! Form::label('kelindan_id', __('invoices.kelindan')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->kelindan->name ?? '' }}</p>
</div>

<!-- Agent Id Field -->
<div class="form-group">
    {!! Form::label('agent_id', __('invoices.agent')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->agent->name ?? '' }}</p>
</div>

<!-- Supervisor Id Field -->
<div class="form-group">
    {!! Form::label('supervisor_id', __('invoices.supervisor')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->supervisor->name ?? '' }}</p>
</div>

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('invoices.payment_term')) !!}:<span class="asterisk"> *</span>
    @if($invoice->paymentterm == 1)
         <p>Cash</p>
    @elseif($invoice->paymentterm == 2)
        <p>Credit</p>
    @elseif($invoice->paymentterm == 3)
        <p>Online BankIn</p>
    @elseif($invoice->paymentterm == 4)
        <p>E-wallet</p>
    @elseif($invoice->paymentterm == 5)
        <p>Cheque {{ '-' . $invoice->chequeno}}</p>
    @else
        <p>Payment Term: Unknown</p>
    @endif
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('invoices.status')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->status == 1 ? "Completed" : "New" }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('invoices.remark')) !!}:<span class="asterisk"> *</span>
    <p>{{ $invoice->remark }}</p>
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