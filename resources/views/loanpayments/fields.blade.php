<!-- Loan Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('loan_id', 'Loan:') !!}<span class="asterisk"> *</span>
    {!! Form::select('loan_id', $loanItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Loan...','data-live-search'=>'true','autofocus']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}<span class="asterisk"> *</span>
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Description Field -->
<div class="form-group col-sm-6">
    {!! Form::label('description', 'Description:') !!}<span class="asterisk"> *</span>
    {!! Form::text('description', null, ['class' => 'form-control','maxlength' => 65535,'maxlength' => 65535]) !!}
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:') !!}<span class="asterisk"> *</span>
    {!! Form::number('amount', null, ['class' => 'form-control','step'=>'0.01','min'=>'0.01']) !!}
</div>

<!-- Source Field -->
<div class="form-group col-sm-6">
    {!! Form::label('source', 'Source:') !!}<span class="asterisk"> *</span>
    {!! Form::text('source', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Payment Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment', 'Payment:') !!}<span class="asterisk"> *</span>
    {{ Form::select('payment', array(1 => 'Paid', 0 => 'Unpaid'), null, ['class' => 'form-control']) }}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('loanpayments.index') }}" class="btn btn-secondary">Cancel</a>
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