<!-- Role Id Field -->
<div class="form-group">
    {!! Form::label('role_id', 'Role:') !!}
    <p>{{ $roleHasPermission->role->name }}</p>
</div>


<!-- Permission Id Field -->
<div class="form-group">
    {!! Form::label('permission_id', 'Permission:') !!}
    <p>{{ $roleHasPermission->permission->name }}</p>
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
