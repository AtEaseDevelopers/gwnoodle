<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('role.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!}
</div>

<!-- Permissions Field -->
<div class="form-group col-sm-6">
    {!! Form::label('permission_id[]', __('role.permissions')) !!}<span class="asterisk"> *</span>
    {!! Form::select(
        'permission_id[]',
        $permissionItems,
        isset($role) ? $role->permissions : [],
        [
            'class' => 'form-control selectpicker',
            'multiple' => true,
            'data-live-search' => 'true'
        ]
    ) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('role.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('roles.index') }}" class="btn btn-secondary">{{ __('role.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush