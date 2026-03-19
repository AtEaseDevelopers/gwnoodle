<!-- Id Field -->
<div class="form-group">
    {!! Form::label('id', 'ID:') !!}
    <p>{{ $warehouse->id }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Warehouse Name:') !!}
    <p>{{ $warehouse->name }}</p>
</div>

<!-- Location Field -->
<div class="form-group">
    {!! Form::label('location', 'Location:') !!}
    <p>{{ $warehouse->location ?? 'N/A' }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>
        <span class="badge {{ $warehouse->status_badge_class }}">{{ ucfirst($warehouse->status) }}</span>
    </p>
</div>

<!-- Created At Field -->
<div class="form-group">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $warehouse->created_at ? $warehouse->created_at->format('d-m-Y H:i:s') : 'N/A' }}</p>
</div>

<!-- Updated At Field -->
<div class="form-group">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $warehouse->updated_at ? $warehouse->updated_at->format('d-m-Y H:i:s') : 'N/A' }}</p>
</div>


@push('scripts')
    <script>
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush

