<!-- Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('code', __('commission.commission_type')) !!}
    {{ Form::select('code', [
        'agent_commission_product_type' => __('commission.agent_commission'),
        'kelindan_commission_product_type' => __('commission.kelindan_commission'),
        'driver_commission_product_type' => __('commission.driver_commission'),
        'operation_commission_product_type' => __('commission.operation_commission')
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
    {!! Form::label('description', __('commission.product_type')) !!}
    {{ Form::select('description', [
        0 => __('commission.ice')
    ], null, ['class' => 'form-control select2-product', 'placeholder' => __('commission.select_product_type')]) }}
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    {!! Form::label('value', __('commission.value')) !!} %<span class="asterisk"> *</span>
    {!! Form::text('value', null, ['class' => 'form-control']) !!}
</div>

<!-- Sequence Field -->
<div class="form-group col-sm-6">
    {!! Form::hidden('sequence', '2', ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('commission.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('commission_group.index') }}" class="btn btn-secondary">{{ __('commission.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            // Initialize Select2 for product type field
            $('.select2-product').select2({
                placeholder: "Search for product type...",
                allowClear: true,
                width: '100%'
            });
            
            HideLoad();
        });
    </script>
    
    <style>
        /* Style the Select2 dropdown to match your form */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: .25rem;
            height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush