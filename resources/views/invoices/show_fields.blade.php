<!-- Invoiceno Field -->
<div class="form-group">
    {!! Form::label('invoiceno', __('invoices.invoice_no')) !!}:
    <p>{{ $invoice->invoiceno }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('invoices.date')) !!}:
    <p>{{ $invoice->date }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('invoices.customer')) !!}:
    <p>{{ $invoice->customer->company ?? '' }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('invoices.driver')) !!}:
    <p>{{ $invoice->driver->name ?? '' }}</p>
</div>

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('invoices.payment_term')) !!}:
    @if($invoice->paymentterm == 1)
        <p>Cash</p>
    @elseif($invoice->paymentterm == 2)
        <p>Credit</p>
    @elseif($invoice->paymentterm == 3)
        <p>Online Payment</p>
    @elseif($invoice->paymentterm == 4)
        <p>Touch n Go</p>
    @elseif($invoice->paymentterm == 5)
        <p>Cheque @if($invoice->chequeno) - {{ $invoice->chequeno }} @endif</p>
    @else
        <p>Payment Term: Unknown</p>
    @endif
</div>

<!-- ChequeNo Field (only show if payment term is Cheque) -->
@if($invoice->paymentterm == 5 && $invoice->chequeno)
<div class="form-group">
    {!! Form::label('chequeno', __('invoices.cheque_no')) !!}:
    <p>{{ $invoice->chequeno }}</p>
</div>
@endif

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('invoices.status')) !!}:
    <p>{{ $invoice->status == 1 ? "Completed" : "New" }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('invoices.remark')) !!}:
    <p>{{ $invoice->remark ?: '-' }}</p>
</div>

<!-- Submit Field -->
<div class="form-group">
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">{{ __('invoices.back') }}</a>
    @if(isset($invoice) && $invoice->status != 1)
    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-primary">{{ __('invoices.edit') }}</a>
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