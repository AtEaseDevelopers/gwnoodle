<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', 'Driver Id:') !!}
    {!! Form::number('driver_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Datefrom Field -->
<div class="form-group col-sm-6">
    {!! Form::label('datefrom', 'Datefrom:') !!}
    {!! Form::text('datefrom', null, ['class' => 'form-control','id'=>'datefrom']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#datefrom').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Dateto Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dateto', 'Dateto:') !!}
    {!! Form::text('dateto', null, ['class' => 'form-control','id'=>'dateto']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#dateto').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('month', 'Month:') !!}
    {!! Form::number('month', null, ['class' => 'form-control']) !!}
</div>

<!-- Do Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('do_amount', 'Do Amount:') !!}
    {!! Form::number('do_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Do List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('do_list', 'Do List:') !!}
    {!! Form::textarea('do_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Claim Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('claim_amount', 'Claim Amount:') !!}
    {!! Form::number('claim_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Claim List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('claim_list', 'Claim List:') !!}
    {!! Form::textarea('claim_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Comp Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('comp_amount', 'Comp Amount:') !!}
    {!! Form::number('comp_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Comp List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('comp_list', 'Comp List:') !!}
    {!! Form::textarea('comp_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Adv Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('adv_amount', 'Adv Amount:') !!}
    {!! Form::number('adv_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Adv List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('adv_list', 'Adv List:') !!}
    {!! Form::textarea('adv_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Loanpay Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('loanpay_amount', 'Loanpay Amount:') !!}
    {!! Form::number('loanpay_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Loanpay List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('loanpay_list', 'Loanpay List:') !!}
    {!! Form::textarea('loanpay_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Bonus Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bonus_amount', 'Bonus Amount:') !!}
    {!! Form::number('bonus_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Bonus List Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('bonus_list', 'Bonus List:') !!}
    {!! Form::textarea('bonus_list', null, ['class' => 'form-control']) !!}
</div>

<!-- Deduct Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('deduct_amount', 'Deduct Amount:') !!}
    {!! Form::number('deduct_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Final Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('final_amount', 'Final Amount:') !!}
    {!! Form::number('final_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::number('status', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('paymentdetails.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush