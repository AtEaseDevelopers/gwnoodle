<!-- Unit Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('unit_code', __('Unit Code')) !!}
    {!! Form::text('unit_code', null, [
        'class' => 'form-control',
        'id' => 'unit_code',
        'maxlength' => 255,
        'placeholder' => __('Enter unit code')
    ]) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('products.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Price Field (Selling Price) -->
<div class="form-group col-sm-6">
    {!! Form::label('price', __('products.price') . ' (Selling Price)') !!}<span class="asterisk"> *</span>
    {!! Form::number('price', null, ['class' => 'form-control', 'step' => '0.01', 'id' => 'price']) !!}
</div>

<!-- NEW: Cost Field (Purchase/Cost Price) - REQUIRED -->
<div class="form-group col-sm-6">
    {!! Form::label('cost', 'Cost Price') !!}<span class="asterisk"> *</span>
    {!! Form::number('cost', null, ['class' => 'form-control', 'step' => '0.01', 'id' => 'cost', 'min' => 0, 'required' => true]) !!}
    <small class="form-text text-muted">The purchase/cost price of this product</small>
</div>

<!-- Type Field -->
<!-- <div class="form-group col-sm-6">
    {!! Form::label('type', __('products.type')) !!}
    {{ Form::select('type', [
        0 => __('products.type_ice'),
        1 => 'Coffee',
        2 => 'Tea',
        3 => 'Cocoa',
    ], null, ['class' => 'form-control']) }}
</div> -->

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('products.status')) !!}
    {{ Form::select('status', [
        1 => __('products.active'),
        0 => __('products.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Carton Enabled Field -->
<div class="form-group col-sm-6">
    {!! Form::label('carton_enabled', 'Carton Enabled') !!}
    {{ Form::select('carton_enabled', [
        0 => 'No',
        1 => 'Yes',
    ], null, ['class' => 'form-control', 'id' => 'carton_enabled']) }}
</div>

<!-- Units Per Carton Field (hidden by default) -->
<div class="form-group col-sm-6" id="units_per_carton_field" style="display: none;">
    {!! Form::label('units_per_carton', 'Units Per Carton') !!}
    {!! Form::number('units_per_carton', null, ['class' => 'form-control', 'min' => 1, 'step' => 1]) !!}
    <small class="form-text text-muted">How many units in 1 carton?</small>
</div>

@einvoice
<!-- Classification Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('classification_code', 'Classification Code:') !!}<span class="asterisk"> *</span>
    {!! Form::select('classification_code', $classificationOptions ?? [], null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-size' => '10', 'data-dropup-auto' => 'false', 'id' => 'classification_code', 'placeholder' => 'Select Classification Code...']) !!}
</div>
@endeinvoice

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('products.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.cancel') }}</a>
</div>

@push('scripts')
   <style>
        #classification_code + .dropdown-menu,
        select[name="classification_code"] + .dropdown-menu,
        .bootstrap-select.show .dropdown-menu {
            max-height: 400px !important;
            overflow-y: auto !important;
            max-width: 400px !important;
            width: auto !important;
        }
        .bootstrap-select .dropdown-menu.inner {
            max-height: 350px !important;
            overflow-y: auto !important;
        }
        .bootstrap-select button {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Carton field styling */
        #units_per_carton_field {
            transition: all 0.3s ease;
        }
        
        /* Profit display styling */
        #profit_display {
            transition: all 0.3s ease;
        }
        #profit_percentage {
            min-width: 80px;
            text-align: center;
        }
        .bg-info {
            background-color: #17a2b8 !important;
        }
    </style>

    <script>
        $(document).ready(function () {
            // Carton enabled toggle logic
            function toggleUnitsPerCarton() {
                if ($('#carton_enabled').val() == '1') {
                    $('#units_per_carton_field').show();
                    $('#units_per_carton').prop('required', true);
                } else {
                    $('#units_per_carton_field').hide();
                    $('#units_per_carton').prop('required', false);
                    $('#units_per_carton').val(''); // Clear value when disabled
                }
            }
         
            // Initial state
            toggleUnitsPerCarton();
            
            // On change events
            $('#carton_enabled').change(function() {
                toggleUnitsPerCarton();
            });
            
          

            // Handle escape key
            HideLoad();
        });

        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
    </script>
@endpush
