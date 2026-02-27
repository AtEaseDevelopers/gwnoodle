<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('user.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'autofocus']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', __('user.email')) !!}<span class="asterisk"> *</span>
    {!! Form::email('email', null, ['class' => 'form-control']) !!}
</div>

<!-- Password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password', __('user.password')) !!}<span class="asterisk"> *</span>
    {!! Form::password('password', ['class' => 'form-control']) !!}
</div>

<!-- Confirmation Password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password_confirmation', __('user.password_confirmation')) !!}<span class="asterisk"> *</span>
    {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
</div>

<!-- Role Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('role_id', __('user.role')) !!}<span class="asterisk"> *</span>
    {!! Form::select('role_id', $roleItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Role...','data-live-search'=>'true']) !!}

</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('user.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{!! route('users.index') !!}" class="btn btn-secondary">{{ __('user.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush