<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $loan->date }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', 'Driver Id:') !!}
    <p>{{ $loan->driver->name }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $loan->description }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ number_format($loan->amount,2) }}</p>
</div>

<!-- Period Field -->
<div class="form-group">
    {!! Form::label('period', 'Period (Month):') !!}
    <p>{{ $loan->period }}</p>
</div>

<!-- Rate Field -->
<div class="form-group">
    {!! Form::label('rate', 'Rate (%):') !!}
    <p>{{ sprintf('%0.2f',$loan->rate) }}</p>
</div>

<!-- TotalInterest Field -->
<div class="form-group">
    {!! Form::label('TotalInterest', 'Total Interest:') !!}
    <p>{{ number_format($loan->totalamount-$loan->amount,2) }}</p>
</div>

<!-- Totalamount Field -->
<div class="form-group">
    {!! Form::label('totalamount', 'Total Amount with Interest:') !!}
    <p>{{ number_format($loan->totalamount,2) }}</p>
</div>

<!-- Monthlyamount Field -->
<div class="form-group">
    {!! Form::label('monthlyamount', 'Monthly Repayment:') !!}
    <p>{{ number_format($loan->monthlyamount,2) }}</p>
</div>

<!-- totalpaid Field -->
<div class="form-group">
    {!! Form::label('totalpaid', 'Total Paid:') !!}
    <p>{{ number_format($loan->loanpayments->SUM('amount'),2) }}</p>
</div>

<!-- outstanding Field -->
<div class="form-group">
    {!! Form::label('outstanding', 'Outstanding:') !!}
    <p>{{ number_format($loan->totalamount-($loan->loanpayments->SUM('amount')),2) }}</p>
</div>
@php
    $statusarray = ["Unactive","Active","","","","","","","","Closed"];
@endphp
<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $statusarray[$loan->status] }}</p>
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