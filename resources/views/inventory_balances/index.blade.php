@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('inventory_balances.inventory_balances') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('inventory_balances.inventory_balances') }}
                            <button class="border-0 bg-transparent pull-right text-danger" data-toggle="modal" data-target="#stockout"><i class="fa fa-cart-arrow-down fa-lg"></i></button>
                            <button class="border-0 bg-transparent pull-right text-success pr-2" data-toggle="modal" data-target="#stockin"><i class="fa fa-cart-plus fa-lg"></i></button>
                        </div>
                        <div class="card-body">
                            @include('inventory_balances.table')
                            <div class="pull-right mr-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock In Modal - Distribute Batch from Warehouse to Lorries -->
    <div id="stockin" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title h6">{{ __('Stock In') }} - Distribute from Warehouse to Lorries</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryBalances.stockin', 'id' => 'stockinForm']) !!}
                    
                    <!-- Warehouse Selection First -->
                    <div class="form-group">
                        <label for="warehouse_id_stockin" class="col-form-label">{{ __('Select Warehouse') }}:</label>
                        <select name="warehouse_id" id="warehouse_id_stockin" class="form-control select2-warehouse" required>
                            <option value="">{{ __('Select Warehouse') }}</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Batch Selection (populated based on warehouse) -->
                    <div class="form-group">
                        <label for="product_batch_id" class="col-form-label">{{ __('Select Product Batch') }}:</label>
                        <select name="product_batch_id" id="product_batch_id" class="form-control select2-batch" required disabled>
                            <option value="">{{ __('First select a warehouse') }}</option>
                        </select>
                        <small class="text-muted batch-info" id="batchInfo"></small>
                    </div>

                    <!-- Lorry Multi-Select with Search -->
                    <div class="form-group">
                        <label for="lorry_ids" class="col-form-label">{{ __('Select Lorries') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownLorryStockIn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select Lorries') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownLorryStockIn" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="lorrySearchStockIn" placeholder="Search Lorries...">
                                <div id="lorryListStockIn">
                                    @foreach($lorryItems as $lorryId => $lorryName)
                                        <div class="form-check ml-3">
                                            <input class="form-check-input lorry-checkbox" type="checkbox" name="lorry_ids[]" value="{{ $lorryId }}" id="lorry_stockin_{{ $lorryId }}">
                                            <label class="form-check-label" for="lorry_stockin_{{ $lorryId }}">
                                                {{ $lorryName }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <small class="text-danger lorry-error" style="display: none;">{{ __('Please select at least one lorry') }}</small>
                    </div>

                    <!-- Quantity per Lorry -->
                    <div class="form-group">
                        <label for="quantity_per_lorry" class="col-form-label">{{ __('Quantity per Lorry') }}:</label>
                        <input type="number" min="1" class="form-control" placeholder="Enter quantity per lorry" name="quantity_per_lorry" id="quantity_per_lorry" disabled>
                        <small class="text-muted" id="totalDistributionInfo"></small>
                        <small class="text-danger quantity-error" style="display: none;">{{ __('Please enter a valid quantity') }}</small>
                    </div>
                    
                    <!-- Summary -->
                    <div class="alert alert-info" id="distributionSummary" style="display: none;">
                        <strong>{{ __('Distribution Summary:') }}</strong>
                        <span id="summaryText"></span>
                    </div>
                    
                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-success" id="stockinSubmitBtn" disabled>{{ __('Distribute') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal - Return Batch from Lorry to Warehouse -->
    <div id="stockout" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h4 class="modal-title h6">{{ __('Stock Out') }} - Return from Lorry to Warehouse</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryBalances.stockout', 'id' => 'stockoutForm']) !!}
                    
                    <!-- Lorry Selection -->
                    <div class="form-group">
                        <label for="lorry_id" class="col-form-label">{{ __('Select Lorry') }}:</label>
                        <select name="lorry_id" id="lorry_id" class="form-control select2" required>
                            <option value="">{{ __('Select Lorry') }}</option>
                            @foreach($lorryItems as $lorryId => $lorryName)
                                <option value="{{ $lorryId }}">{{ $lorryName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Batch Selection (populated based on lorry) -->
                    <div class="form-group">
                        <label for="batch_id" class="col-form-label">{{ __('Select Batch to Return') }}:</label>
                        <select name="batch_id" id="batch_id" class="form-control select2-batch" required disabled>
                            <option value="">{{ __('First select a lorry') }}</option>
                        </select>
                        <small class="text-muted" id="batchQuantityInfo"></small>
                    </div>

                    <!-- Destination Warehouse (populated based on batch) -->
                    <div class="form-group">
                        <label for="to_warehouse_id" class="col-form-label">{{ __('Return to Warehouse') }}:</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-control select2-warehouse" required disabled>
                            <option value="">{{ __('Select destination warehouse') }}</option>
                        </select>
                        <small class="text-muted" id="warehouseInfo"></small>
                    </div>

                    <!-- Quantity to Return -->
                    <div class="form-group">
                        <label for="quantity" class="col-form-label">{{ __('Quantity to Return') }}:</label>
                        <input type="number" min="1" class="form-control" placeholder="Enter quantity" name="quantity" id="stockout_quantity" disabled>
                        <small class="text-danger quantity-error" style="display: none;">{{ __('Please enter a valid quantity') }}</small>
                    </div>
                    
                    <!-- Batch Details Display -->
                    <div class="alert alert-info" id="batchDetails" style="display: none;">
                        <strong>{{ __('Return Details:') }}</strong>
                        <div id="batchDetailsText"></div>
                    </div>
                    
                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-warning" id="stockoutSubmitBtn" disabled>{{ __('Return Stock') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    
    <style>
        .dropdown-menu {
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .dropdown-menu .form-check {
            padding: 0.25rem 0;
        }
        .dropdown-toggle::after {
            margin-left: 10px;
        }
        .dropdown-menu::-webkit-scrollbar {
            width: 8px;
        }
        .dropdown-menu::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .select2-container {
            width: 100% !important;
        }
        .bg-success {
            background-color: #28a745 !important;
        }
        .bg-warning {
            background-color: #ffc107 !important;
        }
        .text-white {
            color: #fff !important;
        }
    </style>

    <script>
    $(document).ready(function () {
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('#stockout')
        });
        
        $('.select2-batch').select2({
            width: '100%',
            dropdownParent: $('#stockout')
        });

        // ==================== STOCK IN FUNCTIONALITY ====================

        // Warehouse selection change - load batches from warehouse
        $('#warehouse_id_stockin').on('change', function() {
            var warehouseId = $(this).val();
            var batchSelect = $('#product_batch_id');
            
            if (warehouseId) {
                // Show loading state
                batchSelect.empty().append('<option value="">{{ __("Loading batches...") }}</option>');
                batchSelect.prop('disabled', true);
                
                // If using Select2, destroy and reinitialize after adding options
                if (batchSelect.hasClass('select2-hidden-accessible')) {
                    batchSelect.select2('destroy');
                }
                
                $.ajax({
                    url: '{{ route("warehouses.get-warehouse-batches", "") }}/' + warehouseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Warehouse batches response:', response);
                        
                        // Clear the select
                        batchSelect.empty();
                        
                        // Add default option
                        batchSelect.append($('<option>', {
                            value: '',
                            text: '{{ __("Select Batch") }}'
                        }));
                        
                        // Check if we have inventory data
                        var inventory = response.inventory || response;
                        
                        if (inventory && Array.isArray(inventory) && inventory.length > 0) {
                            console.log('Found', inventory.length, 'batches');
                            
                            // Loop through each batch
                            $.each(inventory, function(index, item) {
                                var batchId = item.batch_id || item.id;
                                var batchCode = item.batch_code || 'Unknown';
                                var productName = item.product_name || 'Unknown';
                                var quantity = item.quantity || 0;
                                var expiryDate = item.expiry_date || 'N/A';
                                
                                var optionText = batchCode + ' - ' + productName + 
                                                ' (' + quantity + ' units';
                                
                                if (expiryDate && expiryDate !== 'N/A') {
                                    optionText += ' | Exp: ' + expiryDate;
                                }
                                
                                optionText += ')';
                                
                                if (item.is_expiring_soon) {
                                    optionText += ' ⚠️ Expiring Soon';
                                }
                                
                                // Create option element
                                var option = $('<option>', {
                                    value: batchId,
                                    text: optionText,
                                    'data-quantity': quantity,
                                    'data-product': productName,
                                    'data-expiry': expiryDate
                                });
                                
                                batchSelect.append(option);
                            });
                            
                            // Enable the select
                            batchSelect.prop('disabled', false);
                            
                        } else {
                            console.log('No batches found');
                            batchSelect.append($('<option>', {
                                value: '',
                                text: '{{ __("No batches available in this warehouse") }}',
                                disabled: true
                            }));
                            batchSelect.prop('disabled', true);
                            $('#quantity_per_lorry').prop('disabled', true);
                            $('#distributionSummary').hide();
                        }
                        
                        // Reinitialize Select2
                        batchSelect.select2({
                            width: '100%',
                            dropdownParent: $('#stockin')
                        });
                        
                        // Trigger change to update any dependent elements
                        batchSelect.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading warehouse batches:', error);
                        console.error('Response:', xhr.responseJSON);
                        
                        batchSelect.empty().append($('<option>', {
                            value: '',
                            text: '{{ __("Error loading batches") }}',
                            disabled: true
                        }));
                        batchSelect.prop('disabled', true);
                        $('#quantity_per_lorry').prop('disabled', true);
                        $('#distributionSummary').hide();
                        
                        // Reinitialize Select2 even on error
                        batchSelect.select2({
                            width: '100%',
                            dropdownParent: $('#stockin')
                        });
                    }
                });
            } else {
                // If using Select2, destroy before changing
                if (batchSelect.hasClass('select2-hidden-accessible')) {
                    batchSelect.select2('destroy');
                }
                
                batchSelect.empty().append($('<option>', {
                    value: '',
                    text: '{{ __("First select a warehouse") }}'
                }));
                batchSelect.prop('disabled', true);
                $('#quantity_per_lorry').prop('disabled', true);
                $('#distributionSummary').hide();
                
                // Reinitialize Select2
                batchSelect.select2({
                    width: '100%',
                    dropdownParent: $('#stockin')
                });
            }
            
            validateStockInForm();
        });

        // Batch selection change
        $('#product_batch_id').on('change', function() {
            var selected = $(this).find('option:selected');
            var quantity = selected.data('quantity') || 0;
            
            if (quantity > 0) {
                $('#batchInfo').text('Available: ' + quantity + ' units | Expiry: ' + selected.data('expiry'));
                $('#quantity_per_lorry').prop('disabled', false);
            } else {
                $('#batchInfo').text('No stock available');
                $('#quantity_per_lorry').prop('disabled', true);
            }
            validateStockInForm();
            updateDistributionSummary();
        });

        // Quantity per lorry change
        $('#quantity_per_lorry').on('input', function() {
            validateStockInForm();
            updateDistributionSummary();
        });

        // Lorry checkbox change
        $('.lorry-checkbox').change(function() {
            var selectedCount = $('#stockin').find('.lorry-checkbox:checked').length;
            var dropdownButton = $('#dropdownLorryStockIn');
            
            if (selectedCount > 0) {
                dropdownButton.text(selectedCount + ' lorry(s) selected');
                $('#stockin').find('.lorry-error').hide();
            } else {
                dropdownButton.text('Select Lorries');
                $('#stockin').find('.lorry-error').show();
            }
            
            validateStockInForm();
            updateDistributionSummary();
        });

        // Lorry search
        $('#lorrySearchStockIn').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('#lorryListStockIn .form-check').each(function() {
                var lorryName = $(this).find('label').text().toLowerCase();
                $(this).toggle(lorryName.includes(searchTerm));
            });
        });

        // Update distribution summary
        function updateDistributionSummary() {
            var perLorry = parseInt($('#quantity_per_lorry').val()) || 0;
            var lorryCount = $('#stockin').find('.lorry-checkbox:checked').length;
            var total = perLorry * lorryCount;
            var available = $('#product_batch_id').find('option:selected').data('quantity') || 0;
            var warehouseName = $('#warehouse_id_stockin').find('option:selected').text();
            
            if (perLorry > 0 && lorryCount > 0) {
                var summary = total + ' units from <strong>' + warehouseName + '</strong> to be distributed to ' + lorryCount + ' lorry(s)';
                
                if (total > available) {
                    summary += ' <span class="text-danger">(Insufficient stock! Available: ' + available + ')</span>';
                    $('#stockinSubmitBtn').prop('disabled', true);
                } else {
                    summary += ' <span class="text-success">(Available: ' + available + ')</span>';
                }
                
                $('#summaryText').html(summary);
                $('#distributionSummary').show();
            } else {
                $('#distributionSummary').hide();
            }
        }

        // Validate stock in form
        function validateStockInForm() {
            var warehouseSelected = $('#warehouse_id_stockin').val() !== '';
            var batchSelected = $('#product_batch_id').val() !== '';
            var lorrySelected = $('#stockin').find('.lorry-checkbox:checked').length > 0;
            var quantity = $('#quantity_per_lorry').val();
            var quantityValid = quantity && parseInt(quantity) > 0;
            
            var isValid = warehouseSelected && batchSelected && lorrySelected && quantityValid;
            
            $('#stockinSubmitBtn').prop('disabled', !isValid);
            
            // Show/hide error messages
            $('#stockin').find('.lorry-error').toggle(!lorrySelected);
            
            if (!quantityValid && quantity) {
                $('#stockin').find('.quantity-error').show();
            } else {
                $('#stockin').find('.quantity-error').hide();
            }
        }

        // ==================== STOCK OUT FUNCTIONALITY ====================

        // Lorry selection change - load batches from lorry
        $('#lorry_id').on('change', function() {
            var lorryId = $(this).val();
            var batchSelect = $('#batch_id');
            var warehouseSelect = $('#to_warehouse_id');
            
            if (lorryId) {
                $.ajax({
                    url: '{{ route("inventoryBalances.get-lorry-batches", "") }}/' + lorryId,
                    type: 'GET',
                    success: function(response) {
                        batchSelect.empty().append('<option value="">{{ __("Select Batch") }}</option>');
                        
                        if (response.batches && response.batches.length > 0) {
                            $.each(response.batches, function(index, batch) {
                                batchSelect.append(
                                    '<option value="' + batch.batch_id + '" ' +
                                    'data-quantity="' + batch.quantity + '" ' +
                                    'data-expiry="' + batch.expiry_date + '" ' +
                                    'data-product-id="' + batch.product_id + '">' +
                                    batch.batch_code + ' - ' + batch.product_name + 
                                    ' (' + batch.quantity + ' units | Exp: ' + batch.expiry_date + ')' +
                                    '</option>'
                                );
                            });
                            batchSelect.prop('disabled', false);
                        } else {
                            batchSelect.append('<option value="">{{ __("No batches found") }}</option>');
                            batchSelect.prop('disabled', true);
                            warehouseSelect.empty().append('<option value="">{{ __("Select batch first") }}</option>');
                            warehouseSelect.prop('disabled', true);
                            $('#stockout_quantity').prop('disabled', true);
                            $('#batchDetails').hide();
                        }
                        
                        if (batchSelect.hasClass('select2-hidden-accessible')) {
                            batchSelect.trigger('change.select2');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading batches:', xhr);
                        batchSelect.empty().append('<option value="">{{ __("Error loading batches") }}</option>');
                        batchSelect.prop('disabled', true);
                    }
                });
            } else {
                batchSelect.empty().append('<option value="">{{ __("First select a lorry") }}</option>');
                batchSelect.prop('disabled', true);
                warehouseSelect.empty().append('<option value="">{{ __("Select batch first") }}</option>');
                warehouseSelect.prop('disabled', true);
                $('#stockout_quantity').prop('disabled', true);
                $('#batchDetails').hide();
            }
            
            validateStockOutForm();
        });

        // Batch selection change - load warehouses that have this batch
        $('#batch_id').on('change', function() {
            var selected = $(this).find('option:selected');
            var quantity = selected.data('quantity') || 0;
            var batchId = $(this).val();
            var warehouseSelect = $('#to_warehouse_id');
            
            if (quantity > 0) {
                $('#batchQuantityInfo').text('Available on lorry: ' + quantity + ' units');
                $('#stockout_quantity').prop('disabled', false).attr('max', quantity).val('');
                
                $('#batchDetailsText').html(
                    '<strong>Batch:</strong> ' + selected.text() + '<br>' +
                    '<strong>Available on lorry:</strong> ' + quantity + ' units'
                );
                $('#batchDetails').show();
                
                // Load warehouses that have this batch
                $.ajax({
                    url: '{{ route("warehouses.get-warehouses-with-batch", "") }}/' + batchId,
                    type: 'GET',
                    success: function(response) {
                        warehouseSelect.empty().append('<option value="">{{ __("Select Warehouse") }}</option>');
                        
                        if (response.warehouses && response.warehouses.length > 0) {
                            $.each(response.warehouses, function(index, warehouse) {
                                warehouseSelect.append(
                                    '<option value="' + warehouse.id + '" ' +
                                    'data-stock="' + warehouse.stock + '">' +
                                    warehouse.name + (warehouse.location ? ' (' + warehouse.location + ')' : '') + 
                                    ' - Stock: ' + warehouse.stock + ' units' +
                                    '</option>'
                                );
                            });
                            warehouseSelect.prop('disabled', false);
                            $('#warehouseInfo').text('Select warehouse to return the stock');
                        } else {
                            warehouseSelect.append('<option value="">{{ __("No warehouses found with this batch") }}</option>');
                            warehouseSelect.prop('disabled', true);
                            $('#warehouseInfo').text('No warehouse currently has this batch');
                        }
                        
                        if (warehouseSelect.hasClass('select2-hidden-accessible')) {
                            warehouseSelect.trigger('change.select2');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading warehouses:', xhr);
                        warehouseSelect.empty().append('<option value="">{{ __("Error loading warehouses") }}</option>');
                        warehouseSelect.prop('disabled', true);
                    }
                });
                
            } else {
                $('#batchQuantityInfo').text('');
                $('#stockout_quantity').prop('disabled', true);
                $('#batchDetails').hide();
                warehouseSelect.empty().append('<option value="">{{ __("Select batch first") }}</option>');
                warehouseSelect.prop('disabled', true);
            }
            
            validateStockOutForm();
        });

        // Warehouse selection change
        $('#to_warehouse_id').on('change', function() {
            validateStockOutForm();
            
            var selected = $(this).find('option:selected');
            var stock = selected.data('stock') || 0;
            
            if (selected.val()) {
                $('#warehouseInfo').text('Current stock in this warehouse: ' + stock + ' units');
            }
        });

        // Quantity input change
        $('#stockout_quantity').on('input', function() {
            validateStockOutForm();
        });

        // Validate stock out form (SINGLE FUNCTION)
        function validateStockOutForm() {
            var lorrySelected = $('#lorry_id').val() !== '';
            var batchSelected = $('#batch_id').val() !== '';
            var warehouseSelected = $('#to_warehouse_id').val() !== '';
            var quantity = $('#stockout_quantity').val();
            var maxQuantity = $('#stockout_quantity').attr('max');
            var quantityValid = quantity && parseInt(quantity) > 0 && parseInt(quantity) <= parseInt(maxQuantity);
            
            var isValid = lorrySelected && batchSelected && warehouseSelected && quantityValid;
            
            $('#stockoutSubmitBtn').prop('disabled', !isValid);
            
            if (!quantityValid && quantity) {
                $('#stockout').find('.quantity-error').text('Quantity must be between 1 and ' + maxQuantity).show();
            } else if (!quantity && batchSelected) {
                $('#stockout').find('.quantity-error').hide();
            } else {
                $('#stockout').find('.quantity-error').hide();
            }
        }

        // ==================== MODAL RESET ====================
        
        // Reset stock in modal
        $('#stockin').on('hidden.bs.modal', function() {
            $(this).find('select').val('').trigger('change');
            $(this).find('.lorry-checkbox').prop('checked', false);
            $(this).find('input[type="number"]').val('');
            $(this).find('.dropdown-toggle').text('Select Lorries');
            $(this).find('.lorry-error, .quantity-error').hide();
            $(this).find('#distributionSummary').hide();
            $('#stockinSubmitBtn').prop('disabled', true);
        });

        // Reset stock out modal
        $('#stockout').on('hidden.bs.modal', function() {
            $(this).find('select').val('').trigger('change');
            $(this).find('input[type="number"]').val('');
            $(this).find('.quantity-error').hide();
            $(this).find('#batchDetails').hide();
            $('#stockoutSubmitBtn').prop('disabled', true);
        });

        // Keyboard shortcut
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header button').first().click();
            }
        });
    });
    </script>
@endpush