<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('user.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'autofocus']) !!}
</div>

<!-- Username Field -->
<div class="form-group col-sm-6">
    {!! Form::label('username', __('Username')) !!}<span class="asterisk"> *</span>
    {!! Form::text('username', null, ['class' => 'form-control']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', __('user.email')) !!}<span class="asterisk"> *</span>
    {!! Form::email('email', null, ['class' => 'form-control']) !!}
</div>

<!-- Invoice Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('invoice_code', __('Invoice Code')) !!}
    {!! Form::text('invoice_code', null, ['class' => 'form-control']) !!}
    <small class="form-text text-muted">{{ __('If set, this manager will be able to access Invoices in the sidebar.') }}</small>
</div>

@if(Route::currentRouteName() == 'Managerusers.edit' || Route::currentRouteName() == 'Managerusers.update')
    <!-- EDIT MODE: Show checkbox to update password -->
    
    <!-- Update Password Checkbox (Only for edit) -->
    <div class="form-group col-sm-6">
        <div class="form-check">
            {!! Form::checkbox('update_password', 1, false, [
                'class' => 'form-check-input',
                'id' => 'update_password'
            ]) !!}
            {!! Form::label('update_password', __('Update Password'), ['class' => 'form-check-label']) !!}
        </div>
    </div>

    <!-- Password Field (Hidden by default for edit) -->
    <div class="form-group col-sm-6 password-fields" style="display: none;">
        {!! Form::label('password', __('user.password')) !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>

    <!-- Confirmation Password Field (Hidden by default for edit) -->
    <div class="form-group col-sm-6 password-fields" style="display: none;">
        {!! Form::label('password_confirmation', __('user.password_confirmation')) !!}
        {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
    </div>
@else
    <!-- CREATE MODE: Always show password fields -->
    
    <!-- Password Field (Always show for create) -->
    <div class="form-group col-sm-6">
        {!! Form::label('password', __('user.password')) !!}<span class="asterisk"> *</span>
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>

    <!-- Confirmation Password Field (Always show for create) -->
    <div class="form-group col-sm-6">
        {!! Form::label('password_confirmation', __('user.password_confirmation')) !!}<span class="asterisk"> *</span>
        {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
    </div>
@endif

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
            
            // Only run password toggle logic if we're in edit mode and checkbox exists
            if ($('#update_password').length > 0) {
                // Toggle password fields based on checkbox (only for edit)
                $('#update_password').change(function() {
                    if ($(this).is(':checked')) {
                        $('.password-fields').show();
                        // Add required attribute to password fields
                        $('input[name="password"]').attr('required', 'required');
                        $('input[name="password_confirmation"]').attr('required', 'required');
                    } else {
                        $('.password-fields').hide();
                        // Remove required attribute and clear values
                        $('input[name="password"]').removeAttr('required').val('');
                        $('input[name="password_confirmation"]').removeAttr('required').val('');
                    }
                });
                
                // Trigger change on page load if checkbox is already checked
                $('#update_password').trigger('change');
            }
        });
    </script>
@endpush