<!-- From Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('from_driver_id', __('task_transfers.from_driver_id'))  !!}
    {!! Form::select('from_driver_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a From Driver Id...']) !!}
</div>


<!-- To Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('to_driver_id', __('task_transfers.to_driver_id')) !!}
    {!! Form::select('to_driver_id', $lorryItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a To Driver Id...']) !!}
</div>


<!-- Task Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('task_id', __('task_transfers.task_id')) !!}
    {!! Form::text('task_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('task_transfers.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('taskTransfers.index') }}" class="btn btn-secondary">{{__('task_transfers.cancel')}}</a>
</div>
