<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('tasks.date')) !!}:
    <p>{{ $task->date }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('tasks.driver')) !!}:
    <p>{{ $task->driver->name }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('tasks.customer')) !!}:
    <p>{{ $task->customer->company }}</p>
</div>

<!-- Sequence Field -->
<div class="form-group">
    {!! Form::label('sequence', __('tasks.sequence')) !!}:
    <p>{{ $task->sequence }}</p>
</div>

<!-- Invoice Id Field -->
<div class="form-group">
    {!! Form::label('invoice_id', __('tasks.invoice')) !!}:
    <p>{{ $task->invoice->invoiceno ?? "" }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('tasks.status')) !!}:
    @if($task->status == 0)
        <p>{{ __('tasks.new') }}</p>
    @elseif($task->status == 1)
        <p>{{ __('tasks.in_progress') }}</p>
    @elseif($task->status == 8)
        <p>{{ __('tasks.completed') }}</p>
    @elseif($task->status == 9)
        <p>{{ __('tasks.cancelled') }}</p>
    @endif
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