<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('role.name')) !!}
    <p>{{ $role->name }}</p>
</div>

<div class="form-group">
    {!! Form::label('permission_id',  __('role.permissions')) !!}
    <p>{{ $role->permission_name }}</p>
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
