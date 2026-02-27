<!-- From Driver Id Field -->
<div class="form-group">
    {!! Form::label('from_driver_id', 'From Driver Id:') !!}
    <p>{{ $taskTransfer->from_driver_id }}</p>
</div>

<!-- To Driver Id Field -->
<div class="form-group">
    {!! Form::label('to_driver_id', 'To Driver Id:') !!}
    <p>{{ $taskTransfer->to_driver_id }}</p>
</div>

<!-- Task Id Field -->
<div class="form-group">
    {!! Form::label('task_id', 'Task Id:') !!}
    <p>{{ $taskTransfer->task_id }}</p>
</div>

