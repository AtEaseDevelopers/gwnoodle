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
    {!! Form::number('driver_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Kelindan Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('kelindan_id', 'Kelindan Id:') !!}
    {!! Form::number('kelindan_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorry_id', 'Lorry Id:') !!}
    {!! Form::number('lorry_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Latitude Field -->
<div class="form-group col-sm-6">
    {!! Form::label('latitude', 'Latitude:') !!}
    {!! Form::text('latitude', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Longitude Field -->
<div class="form-group col-sm-6">
    {!! Form::label('longitude', 'Longitude:') !!}
    {!! Form::text('longitude', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('driverLocations.index') }}" class="btn btn-secondary">Cancel</a>
</div>
