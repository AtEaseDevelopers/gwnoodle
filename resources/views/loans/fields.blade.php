<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}<span class="asterisk"> *</span>
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date','autofocus']) !!}
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


<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', 'Driver:') !!}<span class="asterisk"> *</span>
    {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Driver...','data-live-search'=>'true']) !!}
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
    {!! Form::label('description', 'Description:') !!}<span class="asterisk"> *</span>
    {!! Form::text('description', null, ['class' => 'form-control','maxlength' => 65535,'maxlength' => 65535]) !!}
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:') !!}<span class="asterisk"> *</span>
    {!! Form::number('amount', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Period Field -->
<div class="form-group col-sm-6">
    {!! Form::label('period', 'Period (Month):') !!}<span class="asterisk"> *</span>
    {!! Form::number('period', null, ['class' => 'form-control','step'=>'1','min'=>'1']) !!}
</div>

<!-- Rate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rate', 'Rate (%):') !!}<span class="asterisk"> *</span>
    {!! Form::number('rate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Totalamount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('interest', 'Total Interest:') !!}
    {!! Form::number('interest', null, ['class' => 'form-control','disabled']) !!}
</div>

<!-- Totalamount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('totalamount', 'Total Amount with Interest:') !!}
    {!! Form::number('totalamount', null, ['class' => 'form-control','disabled']) !!}
</div>

<!-- Monthlyamount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('monthlyamount', 'Monthly Repayment:') !!}
    {!! Form::number('monthlyamount', null, ['class' => 'form-control','disabled']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('loans.index') }}" class="btn btn-secondary">Cancel</a>
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