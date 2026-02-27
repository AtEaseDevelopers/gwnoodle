<!-- Unit Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('unit_code', __('Unit Code')) !!}
    <div class="input-group">
        {!! Form::select('unit_code', $unitCodeOptions ?? [], null, [
            'class' => 'form-control select2-unit-code',
            'id' => 'unit_code',
            'placeholder' => __('Select or type new unit code...'),
            'style' => 'width: 100%;'
        ]) !!}
    </div>
    <small class="form-text text-muted">You can select from existing or type a new unit code</small>
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

<!-- Profit Display (shows real-time profit calculation) -->
<div class="form-group col-sm-6" id="profit_display" style="display: none;">
    <label>Estimated Profit</label>
    <div class="input-group">
        <span class="form-control bg-light" id="profit_amount" readonly>0.00</span>
        <span class="input-group-text bg-info text-white" id="profit_percentage">0%</span>
    </div>
    <small class="form-text text-muted">Based on selling price - cost price</small>
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', __('products.type')) !!}
    {{ Form::select('type', [
        0 => __('products.type_ice'),
        1 => 'Coffee',
        2 => 'Tea',
        3 => 'Cocoa',
    ], null, ['class' => 'form-control']) }}
</div>

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
        /* Select2 custom styling */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        
        /* Style for newly created tags */
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection__choice {
            background-color: #e21212;
            color: white;
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for unit code with tags enabled
            $('.select2-unit-code').select2({
                placeholder: '{{ __("Select or type new unit code...") }}',
                allowClear: true,
                tags: true,  // This enables creating new tags
                createTag: function(params) {
                    var term = $.trim(params.term);
                    
                    // Don't create tag if empty
                    if (term === '') {
                        return null;
                    }
                    
                    // Check if the term already exists in the dropdown
                    var exists = false;
                    $('.select2-unit-code option').each(function() {
                        if ($(this).val() === term) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    // If it doesn't exist, create a new tag
                    if (!exists) {
                        return {
                            id: term,
                            text: term + ' (new)',
                            newTag: true // Add a flag for new tags
                        };
                    }
                    
                    return null;
                },
                templateResult: function(result) {
                    if (result.newTag) {
                        return $('<span class="text-warning"><i class="fas fa-plus-circle"></i> ' + result.text + '</span>');
                    }
                    return result.text;
                },
                templateSelection: function(result) {
                    return result.text.replace(' (new)', '');
                },
                width: '100%'
            });

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
            
            // NEW: Calculate and display profit in real-time
            function calculateProfit() {
                var price = parseFloat($('#price').val()) || 0;
                var cost = parseFloat($('#cost').val()) || 0;
                
                if (price > 0 && cost > 0) {
                    var profit = price - cost;
                    var profitPercentage = (profit / price) * 100;
                    
                    $('#profit_amount').val(profit.toFixed(2));
                    $('#profit_percentage').text(profitPercentage.toFixed(2) + '%');
                    
                    // Change color based on profit margin
                    if (profitPercentage < 0) {
                        $('#profit_percentage').removeClass('bg-info').addClass('bg-danger');
                    } else if (profitPercentage < 10) {
                        $('#profit_percentage').removeClass('bg-info').addClass('bg-warning');
                    } else {
                        $('#profit_percentage').removeClass('bg-danger bg-warning').addClass('bg-info');
                    }
                    
                    $('#profit_display').show();
                } else if (price > 0 && cost === 0) {
                    // Cost is zero, profit = price
                    $('#profit_amount').val(price.toFixed(2));
                    $('#profit_percentage').text('100%');
                    $('#profit_percentage').removeClass('bg-danger bg-warning').addClass('bg-info');
                    $('#profit_display').show();
                } else {
                    $('#profit_display').hide();
                }
            }
            
            // Initial state
            toggleUnitsPerCarton();
            calculateProfit(); // Calculate initial profit if values exist
            
            // On change events
            $('#carton_enabled').change(function() {
                toggleUnitsPerCarton();
            });
            
            // NEW: Listen to price and cost changes
            $('#price, #cost').on('keyup change', function() {
                calculateProfit();
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