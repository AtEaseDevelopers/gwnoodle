<!-- Loan Id Field -->
<div class="form-group">
    {!! Form::label('loan_id', 'Loan:') !!}
    <p>{{ $loanpayment->loan->driver->name . ' (' . $loanpayment->loan->description . ')' }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $loanpayment->date }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $loanpayment->description }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ number_format($loanpayment->amount,2) }}</p>
</div>

<!-- Source Field -->
<div class="form-group">
    {!! Form::label('source', 'Source:') !!}
    <p>{{ $loanpayment->source }}</p>
</div>

<!-- Payment Field -->
<div class="form-group">
    {!! Form::label('payment', 'Payment:') !!}
    <p>{{ $loanpayment->payment == 1 ? "Paid" : "Unpaid" }}</p>
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