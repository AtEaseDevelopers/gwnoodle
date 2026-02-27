<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
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


<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', 'Driver Id:') !!}
    {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Driver Id...']) !!}
</div>


<!-- Kelindan Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('kelindan_id', 'Kelindan Id:') !!}
    {!! Form::select('kelindan_id', $kelindanItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Kelindan Id...']) !!}
</div>


<!-- Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorry_id', 'Lorry Id:') !!}
    {!! Form::select('lorry_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Lorry Id...']) !!}
</div>


<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::text('type', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('trips.index') }}" class="btn btn-secondary">Cancel</a>
</div>
