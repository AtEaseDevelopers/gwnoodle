<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('user.name')) !!}
    <p>{!! $user->name !!}</p>
</div>

<!-- Email Field -->
<div class="form-group">
    {!! Form::label('email', __('user.email')) !!}
    <p>{!! $user->email !!}</p>
</div>

<!-- Role Id Field -->
<div class="form-group">
    {!! Form::label('role_id', __('user.role')) !!}
    <p>{!! $user->role_name !!}</p>
</div>

<!-- Created At Field -->
<div class="form-group">
    {!! Form::label('created_at', __('user.created_at')) !!}
    <p>{!! $user->created_at !!}</p>
</div>

<!-- Updated At Field -->
<div class="form-group">
    {!! Form::label('updated_at', __('user.updated_at')) !!}
    <p>{!! $user->updated_at !!}</p>
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