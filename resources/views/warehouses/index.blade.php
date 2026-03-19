@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Warehouses') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('Warehouses') }}
                            <button class="border-0 bg-transparent pull-right text-info" data-toggle="modal" data-target="#transferStock"><i class="fa fa-exchange fa-lg"></i></button>
                            <a href="{{ route('warehouses.create') }}" class="pull-right text-success pr-2"><i class="fa fa-plus-square fa-lg"></i></a>
                        </div>
                        <div class="card-body">
                            @include('warehouses.table')
                            <div class="pull-right mr-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Transfer Modal -->
    <div id="transferStock" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title h6">{{ __('Transfer Stock Between Warehouses') }}</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'warehouses.transfer', 'id' => 'transferForm']) !!}
                    
                    <!-- From Warehouse Selection -->
                    <div class="form-group">
                        <label for="from_warehouse_id" class="col-form-label">{{ __('From Warehouse') }} <span class="text-danger">*</span>:</label>
                        <select name="from_warehouse_id" id="from_warehouse_id" class="form-control select2" required>
                            <option value="">{{ __('Select Source Warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- To Warehouse Selection -->
                    <div class="form-group">
                        <label for="to_warehouse_id" class="col-form-label">{{ __('To Warehouse') }} <span class="text-danger">*</span>:</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-control select2" required>
                            <option value="">{{ __('Select Destination Warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product/Batch Selection (populated via AJAX) -->
                    <div class="form-group">
                        <label for="transfer_batch_id" class="col-form-label">{{ __('Select Batch to Transfer') }} <span class="text-danger">*</span>:</label>
                        <select name="batch_id" id="transfer_batch_id" class="form-control select2-batch" required disabled>
                            <option value="">{{ __('First select source warehouse') }}</option>
                        </select>
                        <small class="text-muted" id="transferBatchInfo"></small>
                    </div>

                    <!-- Quantity to Transfer -->
                    <div class="form-group">
                        <label for="transfer_quantity" class="col-form-label">{{ __('Quantity to Transfer') }} <span class="text-danger">*</span>:</label>
                        <input type="number" min="1" class="form-control" placeholder="Enter quantity" name="quantity" id="transfer_quantity" disabled>
                        <small class="text-danger quantity-error" style="display: none;">{{ __('Please enter a valid quantity') }}</small>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group">
                        <label for="transfer_remarks" class="col-form-label">{{ __('Remarks') }} ({{ __('Optional') }}):</label>
                        <textarea class="form-control" name="remarks" id="transfer_remarks" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                    
                    <!-- Transfer Details Display -->
                    <div class="alert alert-info" id="transferDetails" style="display: none;">
                        <strong>{{ __('Transfer Summary:') }}</strong>
                        <div id="transferDetailsText"></div>
                    </div>
                    
                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-info" id="transferSubmitBtn" disabled>{{ __('Transfer Stock') }}</button>
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
        .select2-container {
            width: 100% !important;
        }
        .bg-info {
            background-color: #17a2b8 !important;
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
                dropdownParent: $('#transferStock')
            });
            
            $('.select2-batch').select2({
                width: '100%',
                dropdownParent: $('#transferStock')
            });

            // ==================== STOCK TRANSFER FUNCTIONALITY ====================
            
            // From warehouse selection change - load available batches
            $('#from_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                var toWarehouseId = $('#to_warehouse_id').val();
                var batchSelect = $('#transfer_batch_id');
                
                // Check if same warehouse selected
                if (toWarehouseId && warehouseId === toWarehouseId) {
                    alert('Source and destination warehouses cannot be the same!');
                    $(this).val('').trigger('change');
                    return;
                }
                
                if (warehouseId) {
                    $.ajax({
                        url: '{{ route("warehouses.get-warehouse-batches", "") }}/' + warehouseId,
                        type: 'GET',
                        success: function(response) {
                            batchSelect.empty().append('<option value="">{{ __("Select Batch") }}</option>');
                            
                            if (response.inventory && response.inventory.length > 0) {
                                $.each(response.inventory, function(index, item) {
                                    var expiringClass = item.is_expiring_soon ? ' (Expiring Soon!)' : '';
                                    batchSelect.append(
                                        '<option value="' + item.batch_id + '" ' +
                                        'data-quantity="' + item.quantity + '" ' +
                                        'data-product="' + item.product_name + '" ' +
                                        'data-expiry="' + item.expiry_date + '">' +
                                        item.batch_code + ' - ' + item.product_name + 
                                        ' (' + item.quantity + ' units | Exp: ' + item.expiry_date + ')' +
                                        expiringClass +
                                        '</option>'
                                    );
                                });
                                batchSelect.prop('disabled', false);
                            } else {
                                batchSelect.append('<option value="">{{ __("No batches available in this warehouse") }}</option>');
                                batchSelect.prop('disabled', true);
                                $('#transfer_quantity').prop('disabled', true);
                                $('#transferDetails').hide();
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
                    batchSelect.empty().append('<option value="">{{ __("First select source warehouse") }}</option>');
                    batchSelect.prop('disabled', true);
                    $('#transfer_quantity').prop('disabled', true);
                    $('#transferDetails').hide();
                }
                
                validateTransferForm();
            });

            // To warehouse selection change
            $('#to_warehouse_id').on('change', function() {
                var toWarehouseId = $(this).val();
                var fromWarehouseId = $('#from_warehouse_id').val();
                
                // Check if same warehouse selected
                if (fromWarehouseId && toWarehouseId === fromWarehouseId) {
                    alert('Source and destination warehouses cannot be the same!');
                    $(this).val('').trigger('change');
                }
                
                validateTransferForm();
                updateTransferDetails();
            });

            // Batch selection change
            $('#transfer_batch_id').on('change', function() {
                var selected = $(this).find('option:selected');
                var quantity = selected.data('quantity') || 0;
                var product = selected.data('product') || '';
                var expiry = selected.data('expiry') || '';
                
                if (quantity > 0) {
                    $('#transferBatchInfo').text('Available: ' + quantity + ' units | Product: ' + product + ' | Expiry: ' + expiry);
                    $('#transfer_quantity').prop('disabled', false).attr('max', quantity).val('');
                } else {
                    $('#transferBatchInfo').text('');
                    $('#transfer_quantity').prop('disabled', true);
                }
                
                validateTransferForm();
                updateTransferDetails();
            });

            // Quantity input change
            $('#transfer_quantity').on('input', function() {
                validateTransferForm();
                updateTransferDetails();
            });

            // Update transfer details
            function updateTransferDetails() {
                var fromWarehouse = $('#from_warehouse_id').find('option:selected').text();
                var toWarehouse = $('#to_warehouse_id').find('option:selected').text();
                var selectedBatch = $('#transfer_batch_id').find('option:selected');
                var quantity = parseInt($('#transfer_quantity').val()) || 0;
                var available = selectedBatch.data('quantity') || 0;
                
                if (fromWarehouse && toWarehouse && selectedBatch.val() && quantity > 0) {
                    var summary = 'Transferring <strong>' + quantity + '</strong> units of <strong>' + 
                                 selectedBatch.text().split(' - ')[0] + '</strong> from <strong>' + 
                                 fromWarehouse + '</strong> to <strong>' + toWarehouse + '</strong>';
                    
                    if (quantity > available) {
                        summary += ' <span class="text-danger">(Insufficient stock! Available: ' + available + ')</span>';
                    }
                    
                    $('#transferDetailsText').html(summary);
                    $('#transferDetails').show();
                } else {
                    $('#transferDetails').hide();
                }
            }

            // Validate transfer form
            function validateTransferForm() {
                var fromSelected = $('#from_warehouse_id').val() !== '';
                var toSelected = $('#to_warehouse_id').val() !== '';
                var batchSelected = $('#transfer_batch_id').val() !== '';
                var quantity = $('#transfer_quantity').val();
                var maxQuantity = $('#transfer_quantity').attr('max');
                var quantityValid = quantity && parseInt(quantity) > 0 && parseInt(quantity) <= parseInt(maxQuantity);
                
                var isValid = fromSelected && toSelected && batchSelected && quantityValid;
                
                $('#transferSubmitBtn').prop('disabled', !isValid);
                
                if (!quantityValid && quantity) {
                    $('.quantity-error').text('Quantity must be between 1 and ' + maxQuantity).show();
                } else if (!quantity && batchSelected) {
                    $('.quantity-error').hide();
                } else {
                    $('.quantity-error').hide();
                }
            }

            // Reset transfer modal
            $('#transferStock').on('hidden.bs.modal', function() {
                $(this).find('select').val('').trigger('change');
                $(this).find('input[type="number"], textarea').val('');
                $(this).find('.quantity-error').hide();
                $(this).find('#transferDetails').hide();
                $('#transferSubmitBtn').prop('disabled', true);
            });

            // Form submission
            $('#transferForm').on('submit', function(e) {
                e.preventDefault();
                
                var fromWarehouse = $('#from_warehouse_id').val();
                var toWarehouse = $('#to_warehouse_id').val();
                var batchId = $('#transfer_batch_id').val();
                var quantity = $('#transfer_quantity').val();
                
                if (fromWarehouse === toWarehouse) {
                    alert('Source and destination warehouses cannot be the same!');
                    return false;
                }
                
                if (confirm('Are you sure you want to transfer ' + quantity + ' units?')) {
                    this.submit();
                }
            });

            // Keyboard shortcut
            $(document).keyup(function(e) {
                if(e.altKey && e.keyCode == 84){ // Alt + T
                    $('#transferStock').modal('show');
                }
            });
        });
    </script>
@endpush