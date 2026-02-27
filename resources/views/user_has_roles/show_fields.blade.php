<!-- Model Id Field -->
<div class="form-group">
    {!! Form::label('model_id', 'User:') !!}
    <p>{{ $userHasRole->user->name }}</p>
</div>


<!-- Role Id Field -->
<div class="form-group">
    {!! Form::label('role_id', 'Role:') !!}
    <p>{{ $userHasRole->role->name }}</p>
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
