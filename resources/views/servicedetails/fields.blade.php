<!-- Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorry_id', __('lorry_service.lorry')) !!}<span class="asterisk"> *</span>
    {!! Form::select('lorry_id', $lorryItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Lorry...', 'data-live-search' => 'true', 'autofocus']) !!}
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', __('lorry_service.type')) !!}
    {!! Form::select('type', [
        'Other' => __('lorry_service.type_other'),
        'Tyre' => __('lorry_service.type_tyre'),
        'Insurance' => __('lorry_service.type_insurance'),
        'Permit' => __('lorry_service.type_permit'),
        'Road Tax' => __('lorry_service.type_road_tax'),
        'Inspection' => __('lorry_service.type_inspection'),
        'Fire Extinguisher' => __('lorry_service.type_fire_extinguisher'),
    ], null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Type...', 'data-live-search' => 'true']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', __('lorry_service.date')) !!}
    {!! Form::text('date', null, ['class' => 'form-control', 'id' => 'date']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush

<!-- Nextdate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nextdate', __('lorry_service.next_date')) !!}
    {!! Form::text('nextdate', null, ['class' => 'form-control', 'id' => 'nextdate']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#nextdate').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', __('lorry_service.amount')) !!}
    {!! Form::number('amount', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) !!}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', __('lorry_service.remark')) !!}
    {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!} <!-- Removed duplicate maxlength -->
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('lorry_service.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('servicedetails.index') }}" class="btn btn-secondary">{{ __('lorry_service.cancel') }}</a>
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