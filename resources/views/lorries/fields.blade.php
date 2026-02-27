<!-- Lorryno Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorryno', __('lorries.lorry_no')) !!}<span class="asterisk"> *</span>
    {!! Form::text('lorryno', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255,'autofocus']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">

    {!! Form::label('status', __('lorries.status'))!!}<span class="asterisk"> *</span>
    {{ Form::select('status', array(1 => 'Active', 0 => 'Unactive'), null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', __('lorries.remark')) !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('lorries.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('lorries.index') }}" class="btn btn-secondary">{{__('lorries.cancel')}}</a>
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
