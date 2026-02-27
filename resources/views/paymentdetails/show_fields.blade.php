<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', 'Driver Id:') !!}
    <p>{{ $paymentdetail->driver_id }}</p>
</div>

<!-- Datefrom Field -->
<div class="form-group">
    {!! Form::label('datefrom', 'Datefrom:') !!}
    <p>{{ $paymentdetail->datefrom }}</p>
</div>

<!-- Dateto Field -->
<div class="form-group">
    {!! Form::label('dateto', 'Dateto:') !!}
    <p>{{ $paymentdetail->dateto }}</p>
</div>

<!-- Month Field -->
<div class="form-group">
    {!! Form::label('month', 'Month:') !!}
    <p>{{ $paymentdetail->month }}</p>
</div>

<!-- Do Amount Field -->
<div class="form-group">
    {!! Form::label('do_amount', 'Do Amount:') !!}
    <p>{{ $paymentdetail->do_amount }}</p>
</div>

<!-- Do List Field -->
<div class="form-group">
    {!! Form::label('do_list', 'Do List:') !!}
    <p>{{ $paymentdetail->do_list }}</p>
</div>

<!-- Claim Amount Field -->
<div class="form-group">
    {!! Form::label('claim_amount', 'Claim Amount:') !!}
    <p>{{ $paymentdetail->claim_amount }}</p>
</div>

<!-- Claim List Field -->
<div class="form-group">
    {!! Form::label('claim_list', 'Claim List:') !!}
    <p>{{ $paymentdetail->claim_list }}</p>
</div>

<!-- Comp Amount Field -->
<div class="form-group">
    {!! Form::label('comp_amount', 'Comp Amount:') !!}
    <p>{{ $paymentdetail->comp_amount }}</p>
</div>

<!-- Comp List Field -->
<div class="form-group">
    {!! Form::label('comp_list', 'Comp List:') !!}
    <p>{{ $paymentdetail->comp_list }}</p>
</div>

<!-- Adv Amount Field -->
<div class="form-group">
    {!! Form::label('adv_amount', 'Adv Amount:') !!}
    <p>{{ $paymentdetail->adv_amount }}</p>
</div>

<!-- Adv List Field -->
<div class="form-group">
    {!! Form::label('adv_list', 'Adv List:') !!}
    <p>{{ $paymentdetail->adv_list }}</p>
</div>

<!-- Loanpay Amount Field -->
<div class="form-group">
    {!! Form::label('loanpay_amount', 'Loanpay Amount:') !!}
    <p>{{ $paymentdetail->loanpay_amount }}</p>
</div>

<!-- Loanpay List Field -->
<div class="form-group">
    {!! Form::label('loanpay_list', 'Loanpay List:') !!}
    <p>{{ $paymentdetail->loanpay_list }}</p>
</div>

<!-- Bonus Amount Field -->
<div class="form-group">
    {!! Form::label('bonus_amount', 'Bonus Amount:') !!}
    <p>{{ $paymentdetail->bonus_amount }}</p>
</div>

<!-- Bonus List Field -->
<div class="form-group">
    {!! Form::label('bonus_list', 'Bonus List:') !!}
    <p>{{ $paymentdetail->bonus_list }}</p>
</div>

<!-- Deduct Amount Field -->
<div class="form-group">
    {!! Form::label('deduct_amount', 'Deduct Amount:') !!}
    <p>{{ $paymentdetail->deduct_amount }}</p>
</div>

<!-- Final Amount Field -->
<div class="form-group">
    {!! Form::label('final_amount', 'Final Amount:') !!}
    <p>{{ $paymentdetail->final_amount }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $paymentdetail->status }}</p>
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