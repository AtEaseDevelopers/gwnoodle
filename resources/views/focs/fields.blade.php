<!-- Product Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('product_id', __('focs.product')) !!}<span class="asterisk"> *</span>
    {!! Form::select('product_id', $productItems, null, ['class' => 'form-control select2-product', 'placeholder' => 'Pick a Product...','autofocus']) !!}
</div>

<!-- Customer Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('customer_id', __('invoices.customer')) !!}<span class="asterisk"> *</span>
    {!! Form::select('customer_id', $customerItems, null, ['class' => 'form-control select2-customer', 'placeholder' => 'Pick a Customer...']) !!}
</div>

<!-- Quantity Field -->
<div class="form-group col-sm-6">
    {!! Form::label('quantity', __('focs.quantity')) !!}<span class="asterisk"> *</span>
    {!! Form::number('quantity', null, ['class' => 'form-control', 'min' => 0]) !!}
</div>

<!-- Free Product Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('free_product_id', __('focs.free_product')) !!}<span class="asterisk"> *</span>
    {!! Form::select('free_product_id', $productItems, null, ['class' => 'form-control select2-free-product', 'placeholder' => 'Pick a Free Product...']) !!}
</div>

<!-- Free Quantity Field -->
<div class="form-group col-sm-6">
    {!! Form::label('free_quantity', __('focs.free_quantity')) !!}<span class="asterisk"> *</span>
    {!! Form::text('free_quantity', null, ['class' => 'form-control']) !!}
</div>

<!-- Startdate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('startdate', __('focs.start_date')) !!}<span class="asterisk"> *</span>
    {!! Form::text('startdate', null, ['class' => 'form-control', 'id' => 'startdate']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#startdate').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                up: "icon-arrow-up-circle icons font-2xl",
                down: "icon-arrow-down-circle icons font-2xl"
            },
            sideBySide: true
        });
    </script>
@endpush

<!-- Enddate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('enddate', __('focs.end_date')) !!}<span class="asterisk"> *</span>
    {!! Form::text('enddate', null, ['class' => 'form-control', 'id' => 'enddate']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#enddate').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                up: "icon-arrow-up-circle icons font-2xl",
                down: "icon-arrow-down-circle icons font-2xl"
            },
            sideBySide: true
        });
    </script>
@endpush

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('focs.status')) !!}
    {{ Form::select('status', [
        1 => __('focs.active'),
        0 => __('focs.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('focs.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('focs.index') }}" class="btn btn-secondary">{{ __('focs.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            // Initialize Select2 for customer field
            $('.select2-customer').select2({
                placeholder: "Search for a customer...",
                allowClear: true,
                width: '100%'
            });
            
            // Initialize Select2 for product field
            $('.select2-product').select2({
                placeholder: "Search for a product...",
                allowClear: true,
                width: '100%'
            });
            
            // Initialize Select2 for free product field
            $('.select2-free-product').select2({
                placeholder: "Search for a free product...",
                allowClear: true,
                width: '100%'
            });
            
            HideLoad();
        });
    </script>
    
    <style>
        /* Optional: Style the Select2 dropdowns to match your theme */
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