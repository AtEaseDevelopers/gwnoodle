<!-- Role Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('role_id', 'Role:') !!}<span class="asterisk"> *</span>
    {!! Form::select('role_id', $roleItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Role...','data-live-search'=>'true','autofocus']) !!}
</div>


<!-- Permission Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('permission_id', 'Permission:') !!}<span class="asterisk"> *</span>
    {!! Form::select('permission_id', $permissionItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Permission...','data-live-search'=>'true']) !!}
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('roleHasPermissions.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
            if(e.which == 13) {
                $("form").submit();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
