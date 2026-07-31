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
                            @if(auth()->user()->hasRole('admin'))
                            <button class="border-0 bg-transparent pull-right text-danger" data-toggle="modal" data-target="#stockadjustment" title="{{ __('Stock Adjustment') }}"><i class="fa fa-cart-arrow-down fa-lg"></i></button>
                            @endif
                            <button class="border-0 bg-transparent pull-right text-warning" data-toggle="modal" data-target="#stockout" title="{{ __('Stock Out') }}"><i class="fa fa-minus-square fa-lg"></i></button>
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

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="transfer_scan_input" class="col-form-label">{{ __('Scan Batch to Transfer') }} <span class="text-danger">*</span>:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fa fa-barcode"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="transfer_scan_input" placeholder="Scan barcode and press Enter" autocomplete="off" disabled>
                                </div>
                                <small class="text-muted" id="transferBatchInfo">{{ __('Select source warehouse first, then scan product batches.') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-form-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-info btn-block" id="openTransferCameraBtn" disabled>
                                    <i class="fa fa-camera"></i> {{ __('Scan Barcode') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="transferItemsContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Batch Code') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Expiry Date') }}</th>
                                    <th style="width: 140px;">{{ __('Quantity') }}</th>
                                    <th style="width: 80px;">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="transferItemsTableBody">
                                <tr id="transferEmptyRow">
                                    <td colspan="5" class="text-center text-muted">{{ __('No scanned batches yet') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group">
                        <label for="transfer_remarks" class="col-form-label">{{ __('Remarks') }} ({{ __('Optional') }}):</label>
                        <textarea class="form-control" name="remarks" id="transfer_remarks" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                    
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

    <div class="modal fade" id="transferScannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-camera"></i> {{ __('Scan Barcode') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#transfer-camera-pane" role="tab">
                                <i class="fa fa-camera"></i> Camera Scan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#transfer-upload-pane" role="tab">
                                <i class="fa fa-upload"></i> Upload Image
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="transfer-camera-pane" role="tabpanel">
                            <div class="form-group">
                                <label>{{ __('Select Camera') }}</label>
                                <select id="transfer-camera-select" class="form-control"></select>
                            </div>
                            <div style="background:#000;border-radius:8px; position:relative;">
                                <video id="transfer-barcode-video" autoplay playsinline style="width:100%"></video>
                                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:80%; height:100px; border:3px solid rgba(255,0,0,0.5); border-radius:10px; pointer-events:none;"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="transfer-upload-pane" role="tabpanel">
                            <div class="form-group">
                                <label>{{ __('Upload Barcode Image') }}</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="transfer-barcode-image-upload" accept="image/*">
                                    <label class="custom-file-label" for="transfer-barcode-image-upload">Choose file...</label>
                                </div>
                            </div>
                            <div id="transfer-image-preview-container" style="display:none; text-align:center; margin-top:15px;">
                                <img id="transfer-image-preview" style="max-width:100%; max-height:300px; border:1px solid #ddd; border-radius:4px; padding:5px;">
                            </div>
                            <button type="button" class="btn btn-primary mt-3" id="transfer-scan-upload-btn" disabled>
                                <i class="fa fa-search"></i> {{ __('Scan Uploaded Image') }}
                            </button>
                        </div>
                    </div>

                    <div id="transfer-scan-result" class="alert mt-3" style="display:none"></div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Stock Adjustment Modal -->
    <div id="stockadjustment" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h4 class="modal-title h6">{{ __('Stock Adjustment') }}</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'warehouses.stock-adjustment', 'id' => 'adjustmentForm']) !!}
                    
                    <!-- Warehouse Selection -->
                    <div class="form-group">
                        <label for="adjustment_warehouse_id" class="col-form-label">{{ __('Select Warehouse') }} <span class="text-danger">*</span>:</label>
                        <select name="warehouse_id" id="adjustment_warehouse_id" class="form-control select2" required>
                            <option value="">{{ __('Select Warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Selection -->
                    <div class="form-group">
                        <label for="adjustment_product_id" class="col-form-label">{{ __('Select Product') }} <span class="text-danger">*</span>:</label>
                        <select name="product_id" id="adjustment_product_id" class="form-control select2" required disabled>
                            <option value="">{{ __('First select warehouse') }}</option>
                        </select>
                    </div>

                    <!-- Batch Selection -->
                    <div class="form-group">
                        <label for="adjustment_batch_id" class="col-form-label">{{ __('Select Batch') }} <span class="text-danger">*</span>:</label>
                        <select name="batch_id" id="adjustment_batch_id" class="form-control select2" required disabled>
                            <option value="">{{ __('First select product') }}</option>
                        </select>
                    </div>

                    <!-- Current Stock Display -->
                    <div class="alert alert-info" id="currentStockInfo" style="display: none;">
                        <strong>{{ __('Current Stock:') }}</strong> 
                        <span id="currentStockValue">0</span> {{ __('units') }}
                    </div>

                    <!-- New Quantity Input -->
                    <div class="form-group">
                        <label for="adjustment_new_quantity" class="col-form-label">{{ __('New Quantity') }} <span class="text-danger">*</span>:</label>
                        <input type="number" min="0" class="form-control" id="adjustment_new_quantity" name="new_quantity" placeholder="Enter new quantity" disabled required>
                        <small class="text-muted">{{ __('Enter the new quantity after adjustment. The system will calculate the difference automatically.') }}</small>
                    </div>

                    <!-- Adjustment Difference Display -->
                    <div class="alert alert-warning" id="adjustmentDifferenceInfo" style="display: none;">
                        <strong>{{ __('Adjustment Summary:') }}</strong>
                        <div id="adjustmentDifferenceText"></div>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group">
                        <label for="adjustment_remarks" class="col-form-label">{{ __('Remarks') }} <span class="text-danger">*</span>:</label>
                        <textarea class="form-control" name="remarks" id="adjustment_remarks" rows="3" placeholder="Reason for stock adjustment..." required></textarea>
                        <small class="text-muted">{{ __('Please provide a reason for this stock adjustment') }}</small>
                    </div>
                    
                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-danger" id="adjustmentSubmitBtn" disabled>{{ __('Apply Adjustment') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div id="stockout" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h4 class="modal-title h6">{{ __('Stock Out') }}</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'warehouses.stock-out', 'id' => 'stockOutForm']) !!}

                    <!-- Warehouse Selection -->
                    <div class="form-group">
                        <label for="stockout_warehouse_id" class="col-form-label">{{ __('Select Warehouse') }} <span class="text-danger">*</span>:</label>
                        <select name="warehouse_id" id="stockout_warehouse_id" class="form-control select2" required>
                            <option value="">{{ __('Select Warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Selection -->
                    <div class="form-group">
                        <label for="stockout_product_id" class="col-form-label">{{ __('Select Product') }} <span class="text-danger">*</span>:</label>
                        <select name="product_id" id="stockout_product_id" class="form-control select2" required disabled>
                            <option value="">{{ __('First select warehouse') }}</option>
                        </select>
                    </div>

                    <!-- Batch Selection -->
                    <div class="form-group">
                        <label for="stockout_batch_id" class="col-form-label">{{ __('Select Batch') }} <span class="text-danger">*</span>:</label>
                        <select name="batch_id" id="stockout_batch_id" class="form-control select2" required disabled>
                            <option value="">{{ __('First select product') }}</option>
                        </select>
                    </div>

                    <!-- Current Stock Display -->
                    <div class="alert alert-info" id="stockoutCurrentStockInfo" style="display: none;">
                        <strong>{{ __('Current Stock:') }}</strong>
                        <span id="stockoutCurrentStockValue">0</span> {{ __('units') }}
                    </div>

                    <!-- Quantity to Deduct -->
                    <div class="form-group">
                        <label for="stockout_quantity" class="col-form-label">{{ __('Quantity to Deduct') }} <span class="text-danger">*</span>:</label>
                        <input type="number" min="1" class="form-control" id="stockout_quantity" name="quantity" placeholder="Enter quantity to deduct" disabled required>
                        <small class="text-danger" id="stockoutQuantityError" style="display: none;">{{ __('Quantity cannot exceed current stock') }}</small>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group">
                        <label for="stockout_remarks" class="col-form-label">{{ __('Remarks') }} <span class="text-danger">*</span>:</label>
                        <textarea class="form-control" name="remarks" id="stockout_remarks" rows="3" placeholder="Reason for stock out..." required></textarea>
                        <small class="text-muted">{{ __('Please provide a reason for this stock out') }}</small>
                    </div>

                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-warning" id="stockoutSubmitBtn" disabled>{{ __('Apply Stock Out') }}</button>
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

        /* Force input fields to be visible */
        .transfer-item-qty {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            min-width: 100px;
        }

        #transferItemsTableBody input {
            display: block !important;
            visibility: visible !important;
        }

        #transferItemsTableBody td {
            vertical-align: middle;
        }

        /* Ensure the table cell containing the input is visible */
        #transferItemsTableBody td:nth-child(4) {
            min-width: 150px;
        }

        .select2-container {
            width: 100% !important;
        }
        .bg-info {
            background-color: #17a2b8 !important;
        }
        .text-white {
            color: #fff !important;
        }
        /* Add to your style section */
        #adjustmentDifferenceInfo {
            transition: all 0.3s ease;
        }

        #currentStockInfo {
            background-color: #e7f3ff;
            border-color: #b8daff;
        }

        .stock-adjustment-badge {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .badge-increase {
            background-color: #28a745;
            color: white;
        }

        .badge-decrease {
            background-color: #dc3545;
            color: white;
        }
        /* Transfer quantity input styling */
        .transfer-item-qty {
            transition: all 0.3s ease;
        }

        .transfer-item-qty.is-invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .transfer-item-qty.is-warning {
            border-color: #ffc107;
            background-color: #fffbf0;
        }

        .transfer-item-qty.valid-feedback {
            border-color: #28a745;
            background-color: #f0fff4;
        }

        .transfer-qty-error {
            font-size: 11px;
            margin-top: 4px;
            display: block;
        }

        .quantity-status-badge {
            font-size: 10px;
            padding: 2px 6px;
            margin-left: 5px;
        }

        .exceed-warning {
            animation: shake 0.5s ease-in-out;
        }

        /* Improved modal scrolling */
        .modal-dialog-scrollable {
            max-height: calc(100vh - 20px);
        }
        
        .modal-dialog-scrollable .modal-body {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            padding-bottom: 1rem;
        }
        
        /* For very small screens */
        @media (max-height: 600px) {
            .modal-dialog-scrollable .modal-body {
                max-height: calc(100vh - 160px);
            }
        }
        
        /* For landscape mode on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .modal-dialog-scrollable .modal-body {
                max-height: calc(100vh - 140px);
                }
                
                .modal-body .form-group {
                    margin-bottom: 0.5rem;
                }
            }
            
            /* Ensure Select2 dropdown doesn't overflow */
            .select2-dropdown {
                z-index: 1061 !important;
            }
            
            /* Make modal footer stick to bottom */
            .modal-footer {
                flex-shrink: 0;
                background-color: #f8f9fa;
                border-top: 1px solid #dee2e6;
            }
            
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>

    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script>
        $(document).ready(function () {
            let transferWarehouseBatches = {};
            let transferItems = [];
            let transferCodeReader = null;
            let transferIsScanning = false;
            let transferActiveStream = null;
            let currentBatchStock = 0;
            let currentBatchId = null;

            // ========== HELPER FUNCTIONS (Same as stockin modal) ==========
            
            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function updateTransferHiddenQuantities() {
                $.each(transferItems, function(index, item) {
                    $('#transferItemsContainer input[name="items[' + index + '][quantity]"]').val(item.quantity);
                });
            }

            // Initialize select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#transferStock')
            });

            // Initialize select2 for adjustment modal
            $('#adjustment_warehouse_id').select2({
                width: '100%',
                dropdownParent: $('#stockadjustment')
            });

            $('#adjustment_product_id').select2({
                width: '100%',
                dropdownParent: $('#stockadjustment')
            });

            $('#adjustment_batch_id').select2({
                width: '100%',
                dropdownParent: $('#stockadjustment')
            });

            // Initialize select2 for stock out modal
            let stockoutCurrentBatchStock = 0;

            $('#stockout_warehouse_id').select2({
                width: '100%',
                dropdownParent: $('#stockout')
            });

            $('#stockout_product_id').select2({
                width: '100%',
                dropdownParent: $('#stockout')
            });

            $('#stockout_batch_id').select2({
                width: '100%',
                dropdownParent: $('#stockout')
            });

            // ========== STOCK TRANSFER MODAL FUNCTIONS (Mirroring stockin modal pattern) ==========
            
            $('#from_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                var toWarehouseId = $('#to_warehouse_id').val();
                
                if (toWarehouseId && warehouseId === toWarehouseId) {
                    alert('Source and destination warehouses cannot be the same!');
                    $(this).val('').trigger('change');
                    return;
                }
                
                // Clear existing items (same as stockin modal)
                transferItems = [];
                transferWarehouseBatches = {};
                renderTransferItems();

                if (warehouseId) {
                    $('#transferBatchInfo').text('Loading warehouse batches...');
                    $.ajax({
                        url: '{{ route("warehouses.get-warehouse-batches", "") }}/' + warehouseId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            var inventory = response.inventory || response || [];
                            
                            if (inventory.length > 0) {
                                $.each(inventory, function(index, item) {
                                    var batchCode = (item.batch_code || '').trim().toUpperCase();
                                    if (batchCode) {
                                        transferWarehouseBatches[batchCode] = {
                                            batch_id: item.batch_id || item.id,
                                            batch_code: item.batch_code,
                                            product_name: item.product_name,
                                            quantity: item.quantity,
                                            expiry_date: item.expiry_date
                                        };
                                    }
                                });
                                $('#transferBatchInfo').text('Warehouse loaded. Scan product batches to transfer. (' + Object.keys(transferWarehouseBatches).length + ' batches available)');
                            } else {
                                $('#transferBatchInfo').text('No batches available in this warehouse');
                            }
                            toggleTransferScanner();
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            $('#transferBatchInfo').text('Error loading batches');
                            toggleTransferScanner();
                        }
                    });
                } else {
                    $('#transferBatchInfo').text('Select source warehouse first, then scan product batches.');
                    toggleTransferScanner();
                }
                
                validateTransferForm();
            });

            $('#to_warehouse_id').on('change', function() {
                var toWarehouseId = $(this).val();
                var fromWarehouseId = $('#from_warehouse_id').val();
                
                if (fromWarehouseId && toWarehouseId === fromWarehouseId) {
                    alert('Source and destination warehouses cannot be the same!');
                    $(this).val('').trigger('change');
                }
                
                validateTransferForm();
                updateTransferDetails();
            });

            $('#transfer_scan_input').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var code = $(this).val().trim();
                    $(this).val('');
                    if (code) {
                        addTransferBatchByCode(code);
                    }
                }
            });

            $('#openTransferCameraBtn').on('click', function() {
                $('#transferScannerModal').modal('show');
            });

            $('#transferScannerModal').on('shown.bs.modal', function() {
                $(this).css('z-index', 1060);
                $('.modal-backdrop').last().css('z-index', 1050);
                initTransferCameraAndScanner();
            });

            $('#transferScannerModal').on('hidden.bs.modal', function() {
                stopTransferScanner();
                stopTransferCamera();
                resetTransferScannerModal();
                $(this).css('z-index', '');
                $('body').addClass('modal-open');
                setTimeout(() => $('#transfer_scan_input').trigger('focus'), 150);
            });

            $('#transfer-camera-select').on('change', function () {
                const deviceId = this.value;
                if (deviceId) {
                    stopTransferScanner();
                    stopTransferCamera();
                    setTimeout(() => startTransferCamera(deviceId), 500);
                }
            });

            $('#transfer-barcode-image-upload').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    $(this).next('.custom-file-label').html(file.name);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#transfer-image-preview').attr('src', e.target.result);
                        $('#transfer-image-preview-container').show();
                        $('#transfer-scan-upload-btn').prop('disabled', false);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $(this).next('.custom-file-label').html('Choose file...');
                    $('#transfer-image-preview-container').hide();
                    $('#transfer-scan-upload-btn').prop('disabled', true);
                }
            });

            $('#transfer-scan-upload-btn').on('click', function() {
                scanTransferUploadedImage();
            });

            // ========== QUANTITY INPUT HANDLER (Same as stockin modal) ==========
            $(document).on('input', '.transfer-item-qty', function() {
                const index = parseInt($(this).data('index'), 10);
                const item = transferItems[index];
                let rawValue = $(this).val();
                let quantity = parseInt(rawValue, 10);
                
                if (!item) {
                    return;
                }
                
                const maxQty = item.available_quantity;
                const errorSpan = $('#transfer-qty-error-' + index);
                
                // Remove any existing warning classes
                $(this).removeClass('is-invalid is-warning valid-feedback');
                
                // Clear any non-numeric characters
                if (rawValue !== '' && isNaN(quantity)) {
                    $(this).val('').addClass('is-invalid');
                    errorSpan.text('Please enter a valid number').show();
                    item.quantity = 0;
                    validateTransferForm();
                    updateTransferDetails();
                    updateTransferHiddenQuantities();
                    return;
                }
                
                // Check if empty
                if (rawValue === '') {
                    errorSpan.hide();
                    item.quantity = 0;
                    $(this).removeClass('is-invalid is-warning');
                    validateTransferForm();
                    updateTransferDetails();
                    updateTransferHiddenQuantities();
                    return;
                }
                
                // Check if less than 1
                if (quantity < 1) {
                    $(this).addClass('is-invalid');
                    errorSpan.text('Quantity must be at least 1').show();
                    item.quantity = 0;
                    validateTransferForm();
                    updateTransferDetails();
                    updateTransferHiddenQuantities();
                    return;
                }
                
                // Check if exceeds available quantity
                if (quantity > maxQty) {
                    $(this).addClass('is-invalid');
                    const excessAmount = quantity - maxQty;
                    errorSpan.html(`⚠️ Warning: Quantity (${quantity}) exceeds available stock (${maxQty} units) by ${excessAmount} units. Please reduce to ${maxQty} or less.`).show();
                    item.quantity = quantity;
                    $(this).val(quantity);
                    
                    // Add shake animation for visual feedback
                    $(this).addClass('exceed-warning');
                    setTimeout(() => {
                        $(this).removeClass('exceed-warning');
                    }, 500);
                } else if (quantity === maxQty) {
                    $(this).addClass('is-warning');
                    errorSpan.html(`✓ Maximum available quantity (${maxQty} units)`).show();
                    item.quantity = quantity;
                } else if (quantity > maxQty * 0.8) {
                    $(this).addClass('is-warning');
                    const remaining = maxQty - quantity;
                    errorSpan.html(`⚠️ Only ${remaining} units remaining in stock`).show();
                    item.quantity = quantity;
                } else {
                    $(this).addClass('valid-feedback');
                    errorSpan.hide();
                    item.quantity = quantity;
                }
                
                validateTransferForm();
                updateTransferDetails();
                updateTransferHiddenQuantities();
            });

            $(document).on('click', '.remove-transfer-item', function() {
                const index = parseInt($(this).data('index'), 10);
                transferItems.splice(index, 1);
                renderTransferItems();
            });

            function updateTransferDetails() {
                var fromWarehouse = $('#from_warehouse_id').find('option:selected').text();
                var toWarehouse = $('#to_warehouse_id').find('option:selected').text();
                var totalUnits = 0;
                var hasIssues = false;
                
                transferItems.forEach(function(item) {
                    totalUnits += parseInt(item.quantity, 10) || 0;
                    if (item.quantity > item.available_quantity) {
                        hasIssues = true;
                    }
                });

                if ($('#from_warehouse_id').val() && $('#to_warehouse_id').val() && transferItems.length > 0) {
                    var summary = 'Transferring <strong>' + totalUnits + '</strong> units across <strong>' +
                                transferItems.length + '</strong> batch(es) from <strong>' +
                                fromWarehouse + '</strong> to <strong>' + toWarehouse + '</strong>';
                    
                    if (hasIssues) {
                        summary += '<br><span class="text-danger">⚠️ Some quantities exceed available stock!</span>';
                        $('#transferDetails').removeClass('alert-info').addClass('alert-danger');
                    } else {
                        $('#transferDetails').removeClass('alert-danger').addClass('alert-info');
                    }
                    
                    $('#transferDetailsText').html(summary);
                    $('#transferDetails').show();
                } else {
                    $('#transferDetails').hide();
                }
            }

            function validateTransferForm() {
                var fromSelected = $('#from_warehouse_id').val() !== '';
                var toSelected = $('#to_warehouse_id').val() !== '';
                var hasValidQuantities = false;
                var hasQuantityIssues = false;
                
                $.each(transferItems, function(index, item) {
                    if (item.quantity > 0) {
                        hasValidQuantities = true;
                        if (item.quantity > item.available_quantity) {
                            hasQuantityIssues = true;
                        }
                    }
                });
                
                var isValid = fromSelected && toSelected && hasValidQuantities && !hasQuantityIssues;
                
                $('#transferSubmitBtn').prop('disabled', !isValid);
                
                // Update button styling
                if (hasValidQuantities && hasQuantityIssues) {
                    $('#transferSubmitBtn')
                        .attr('title', 'Some quantities exceed available stock. Please adjust them before submitting.')
                        .addClass('btn-danger')
                        .removeClass('btn-info');
                } else {
                    $('#transferSubmitBtn')
                        .removeAttr('title')
                        .addClass('btn-info')
                        .removeClass('btn-danger');
                }
            }

            function toggleTransferScanner() {
                var enabled = $('#from_warehouse_id').val() !== '' && Object.keys(transferWarehouseBatches).length > 0;
                $('#transfer_scan_input').prop('disabled', !enabled);
                $('#openTransferCameraBtn').prop('disabled', !enabled);
            }

            function addTransferBatchByCode(rawCode) {
                var code = (rawCode || '').trim().toUpperCase();
                if (!code) {
                    return;
                }

                if (!$('#from_warehouse_id').val()) {
                    $('#transferBatchInfo').text('Please select source warehouse first');
                    return;
                }

                var batch = transferWarehouseBatches[code];
                if (!batch) {
                    $('#transferBatchInfo').text('Batch code not found in selected source warehouse: ' + rawCode);
                    return;
                }

                var existing = transferItems.find(function(item) {
                    return parseInt(item.batch_id, 10) === parseInt(batch.batch_id, 10);
                });

                if (existing) {
                    if (existing.quantity >= existing.available_quantity) {
                        $('#transferBatchInfo').text('Batch ' + batch.batch_code + ' already reached available quantity');
                        return;
                    }
                    existing.quantity += 1;
                } else {
                    transferItems.push({
                        batch_id: batch.batch_id,
                        batch_code: batch.batch_code,
                        product_name: batch.product_name,
                        expiry_date: batch.expiry_date,
                        available_quantity: parseInt(batch.quantity || 0, 10),
                        quantity: 1
                    });
                }

                $('#transferBatchInfo').text('Added batch ' + batch.batch_code + ' to transfer list');
                renderTransferItems();
            }

            // ========== RENDER TRANSFER ITEMS (Same pattern as stockin modal) ==========
            function renderTransferItems() {
                var tbody = $('#transferItemsTableBody');
                var hiddenContainer = $('#transferItemsContainer');
                tbody.empty();
                hiddenContainer.empty();

                console.log('Rendering transfer items, count:', transferItems.length);

                if (transferItems.length === 0) {
                    tbody.append('<tr id="transferEmptyRow"><td colspan="5" class="text-center text-muted">No scanned batches yet</td></tr>');
                } else {
                    for (var i = 0; i < transferItems.length; i++) {
                        var item = transferItems[i];
                        var index = i;
                        
                        var quantityStatus = '';
                        var quantityClass = '';
                        var inputValue = item.quantity;
                        var maxQty = item.available_quantity;
                        
                        if (item.quantity > item.available_quantity) {
                            quantityStatus = '<span class="badge badge-danger" style="font-size: 10px; margin-left: 5px;">Exceeds stock!</span>';
                            quantityClass = 'is-invalid';
                        } else if (item.quantity === item.available_quantity && item.quantity > 0) {
                            quantityStatus = '<span class="badge badge-warning" style="font-size: 10px; margin-left: 5px;">Full quantity</span>';
                            quantityClass = 'is-warning';
                        } else if (item.quantity > 0) {
                            var percentage = Math.round((item.quantity / item.available_quantity) * 100);
                            quantityStatus = '<span class="badge badge-info" style="font-size: 10px; margin-left: 5px;">' + percentage + '% used</span>';
                            quantityClass = 'valid-feedback';
                        }
                        
                        // Create row HTML - SIMPLE AND CLEAR
                        var row = '<tr>';
                        row += '<td>' + escapeHtml(item.batch_code) + '<br><small class="text-muted">Available: ' + item.available_quantity + '</small></td>';
                        row += '<td>' + escapeHtml(item.product_name) + ' ' + quantityStatus + '</td>';
                        row += '<td>' + escapeHtml(item.expiry_date) + '</td>';
                        row += '<td>';
                        row += '<input type="text" value="' + inputValue + '" ';
                        row += 'class="form-control transfer-item-qty ' + quantityClass + '" ';
                        row += 'data-index="' + index + '" ';
                        row += 'data-max="' + maxQty + '" ';
                        row += 'style="width: 100%; display: block !important; visibility: visible !important;">';
                        row += '<small class="text-danger transfer-qty-error" id="transfer-qty-error-' + index + '" style="display: none; font-size: 11px;"></small>';
                        row += '</td>';
                        row += '<td><button type="button" class="btn btn-danger btn-sm remove-transfer-item" data-index="' + index + '"><i class="fa fa-trash"></i></button></td>';
                        row += '</tr>';
                        
                        tbody.append(row);
                        
                        // Add hidden inputs
                        hiddenContainer.append('<input type="hidden" name="items[' + index + '][batch_id]" value="' + item.batch_id + '">');
                        hiddenContainer.append('<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '">');
                    }
                }

                validateTransferForm();
                updateTransferDetails();
            }

            $('#transferStock').on('hidden.bs.modal', function() {
                $(this).find('select').val('').trigger('change');
                $(this).find('textarea').val('');
                $('#transfer_scan_input').val('');
                transferWarehouseBatches = {};
                transferItems = [];
                renderTransferItems();
                toggleTransferScanner();
                $(this).find('#transferDetails').hide();
                $('#transferSubmitBtn').prop('disabled', true).removeClass('btn-danger').addClass('btn-info');
            });

            $('#transferForm').on('submit', function(e) {
                var fromWarehouse = $('#from_warehouse_id').val();
                var toWarehouse = $('#to_warehouse_id').val();
                
                if (fromWarehouse === toWarehouse) {
                    e.preventDefault();
                    alert('Source and destination warehouses cannot be the same!');
                    return false;
                }

                if (!transferItems.length) {
                    e.preventDefault();
                    alert('Please scan at least one batch to transfer');
                    return false;
                }
                
                // Check for exceeded quantities
                var hasExceededItems = false;
                var errorMessage = '';
                
                $.each(transferItems, function(index, item) {
                    if (item.quantity > item.available_quantity) {
                        hasExceededItems = true;
                        errorMessage += `\n• ${item.batch_code}: ${item.quantity} > ${item.available_quantity} (exceeds by ${item.quantity - item.available_quantity})`;
                    }
                });
                
                if (hasExceededItems) {
                    e.preventDefault();
                    alert(`Cannot submit. The following batches exceed available stock:${errorMessage}`);
                    return false;
                }

                var totalUnits = transferItems.reduce(function(sum, item) {
                    return sum + (parseInt(item.quantity, 10) || 0);
                }, 0);

                if (!confirm('Are you sure you want to transfer ' + totalUnits + ' units across ' + transferItems.length + ' batch(es)?')) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                var submitBtn = $('#transferSubmitBtn');
                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            });

            // ========== STOCK ADJUSTMENT MODAL FUNCTIONS ==========
            
            $('#adjustment_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                var productSelect = $('#adjustment_product_id');
                var batchSelect = $('#adjustment_batch_id');
                
                productSelect.val('').trigger('change');
                batchSelect.val('').trigger('change');
                batchSelect.prop('disabled', true);
                $('#adjustment_new_quantity').prop('disabled', true).val('');
                $('#currentStockInfo').hide();
                $('#adjustmentDifferenceInfo').hide();
                
                if (warehouseId) {
                    $.ajax({
                        url: '{{ route("warehouses.get-warehouse-products", "") }}/' + warehouseId,
                        type: 'GET',
                        success: function(response) {
                            productSelect.empty().append('<option value="">{{ __("Select Product") }}</option>');
                            
                            if (response.products && response.products.length > 0) {
                                $.each(response.products, function(index, product) {
                                    productSelect.append('<option value="' + product.product_id + '">' + product.product_name + ' (Total: ' + product.total_quantity + ' units)</option>');
                                });
                                productSelect.prop('disabled', false);
                            } else {
                                productSelect.append('<option value="">{{ __("No products found in this warehouse") }}</option>');
                                productSelect.prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading products:', xhr);
                            productSelect.empty().append('<option value="">{{ __("Error loading products") }}</option>');
                            productSelect.prop('disabled', true);
                        }
                    });
                } else {
                    productSelect.empty().append('<option value="">{{ __("Select Warehouse First") }}</option>');
                    productSelect.prop('disabled', true);
                }
                
                validateAdjustmentForm();
            });

            $('#adjustment_product_id').on('change', function() {
                var warehouseId = $('#adjustment_warehouse_id').val();
                var productId = $(this).val();
                var batchSelect = $('#adjustment_batch_id');
                
                batchSelect.val('').trigger('change');
                $('#adjustment_new_quantity').prop('disabled', true).val('');
                $('#currentStockInfo').hide();
                $('#adjustmentDifferenceInfo').hide();
                
                if (warehouseId && productId) {
                    var url = '/warehouses/' + warehouseId + '/products/' + productId + '/batches';
                    
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            batchSelect.empty().append('<option value="">{{ __("Select Batch") }}</option>');
                            
                            if (response.success && response.batches && response.batches.length > 0) {
                                $.each(response.batches, function(index, batch) {
                                    batchSelect.append('<option value="' + batch.batch_id + '" data-quantity="' + batch.quantity + '" data-expiry="' + batch.expiry_date + '">' + 
                                        batch.batch_code + ' (Stock: ' + batch.quantity + ' units | Exp: ' + batch.expiry_date + ')' +
                                        '</option>');
                                });
                                batchSelect.prop('disabled', false);
                            } else {
                                batchSelect.append('<option value="">{{ __("No batches found for this product in warehouse") }}</option>');
                                batchSelect.prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            batchSelect.empty().append('<option value="">{{ __("Error loading batches") }}</option>');
                            batchSelect.prop('disabled', true);
                        }
                    });
                } else {
                    batchSelect.empty().append('<option value="">{{ __("Select Product First") }}</option>');
                    batchSelect.prop('disabled', true);
                }
                
                validateAdjustmentForm();
            });

            $('#adjustment_batch_id').on('change', function() {
                var selected = $(this).find('option:selected');
                var quantity = selected.data('quantity') || 0;
                var batchId = $(this).val();
                
                currentBatchStock = quantity;
                currentBatchId = batchId;
                
                if (batchId) {
                    $('#currentStockValue').text(quantity);
                    $('#currentStockInfo').show();
                    $('#adjustment_new_quantity').prop('disabled', false);
                    $('#adjustment_new_quantity').val(quantity);
                    $('#adjustmentDifferenceInfo').hide();
                } else {
                    $('#currentStockInfo').hide();
                    $('#adjustment_new_quantity').prop('disabled', true);
                    $('#adjustment_new_quantity').val('');
                    $('#adjustmentDifferenceInfo').hide();
                }
                
                validateAdjustmentForm();
            });

            $('#adjustment_new_quantity').on('input', function() {
                var newQuantity = parseInt($(this).val(), 10) || 0;
                var currentQuantity = currentBatchStock;
                var difference = newQuantity - currentQuantity;
                
                if (newQuantity >= 0) {
                    if (difference > 0) {
                        $('#adjustmentDifferenceText').html(
                            '<span class="text-success">▲ Stock INCREASE by ' + difference + ' units</span><br>' +
                            '<small>Current: ' + currentQuantity + ' units → New: ' + newQuantity + ' units</small>'
                        );
                        $('#adjustmentDifferenceInfo').removeClass('alert-warning').addClass('alert-success').show();
                    } else if (difference < 0) {
                        $('#adjustmentDifferenceText').html(
                            '<span class="text-danger">▼ Stock DECREASE by ' + Math.abs(difference) + ' units</span><br>' +
                            '<small>Current: ' + currentQuantity + ' units → New: ' + newQuantity + ' units</small>'
                        );
                        $('#adjustmentDifferenceInfo').removeClass('alert-success').addClass('alert-warning').show();
                    } else {
                        $('#adjustmentDifferenceInfo').hide();
                    }
                } else {
                    $('#adjustmentDifferenceInfo').hide();
                }
                
                validateAdjustmentForm();
            });

            function validateAdjustmentForm() {
                var warehouseSelected = $('#adjustment_warehouse_id').val() !== '';
                var productSelected = $('#adjustment_product_id').val() !== '';
                var batchSelected = $('#adjustment_batch_id').val() !== '';
                var newQuantity = $('#adjustment_new_quantity').val();
                var remarks = $('#adjustment_remarks').val().trim();
                
                var isValid = warehouseSelected && productSelected && batchSelected && 
                            newQuantity !== '' && parseInt(newQuantity, 10) >= 0 && 
                            remarks !== '';
                
                $('#adjustmentSubmitBtn').prop('disabled', !isValid);
            }

            $('#adjustment_remarks').on('input', function() {
                validateAdjustmentForm();
            });

            $('#stockadjustment').on('hidden.bs.modal', function() {
                $(this).find('select').val('').trigger('change');
                $(this).find('input[type="number"]').val('');
                $(this).find('textarea').val('');
                $('#currentStockInfo').hide();
                $('#adjustmentDifferenceInfo').hide();
                $('#adjustmentSubmitBtn').prop('disabled', true);
                currentBatchStock = 0;
                currentBatchId = null;
            });

            $('#adjustmentForm').on('submit', function(e) {
                var newQuantity = parseInt($('#adjustment_new_quantity').val(), 10);
                var currentQuantity = currentBatchStock;
                var difference = newQuantity - currentQuantity;
                
                var confirmMessage = '';
                if (difference > 0) {
                    confirmMessage = 'You are INCREASING stock by ' + difference + ' units.\n' +
                                    'Current: ' + currentQuantity + ' units\n' +
                                    'New: ' + newQuantity + ' units\n\n' +
                                    'Reason: ' + $('#adjustment_remarks').val() + '\n\n' +
                                    'Are you sure you want to proceed?';
                } else if (difference < 0) {
                    confirmMessage = 'You are DECREASING stock by ' + Math.abs(difference) + ' units.\n' +
                                    'Current: ' + currentQuantity + ' units\n' +
                                    'New: ' + newQuantity + ' units\n\n' +
                                    'Reason: ' + $('#adjustment_remarks').val() + '\n\n' +
                                    'Are you sure you want to proceed?';
                } else {
                    alert('No changes detected. Please adjust the quantity to a different value.');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
                
                var submitBtn = $('#adjustmentSubmitBtn');
                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            });

            // ========== STOCK OUT MODAL FUNCTIONS ==========

            $('#stockout_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                var productSelect = $('#stockout_product_id');
                var batchSelect = $('#stockout_batch_id');

                productSelect.val('').trigger('change');
                batchSelect.val('').trigger('change');
                batchSelect.prop('disabled', true);
                $('#stockout_quantity').prop('disabled', true).val('');
                $('#stockoutCurrentStockInfo').hide();
                $('#stockoutQuantityError').hide();

                if (warehouseId) {
                    $.ajax({
                        url: '{{ route("warehouses.get-warehouse-products", "") }}/' + warehouseId,
                        type: 'GET',
                        success: function(response) {
                            productSelect.empty().append('<option value="">{{ __("Select Product") }}</option>');

                            if (response.products && response.products.length > 0) {
                                $.each(response.products, function(index, product) {
                                    productSelect.append('<option value="' + product.product_id + '">' + product.product_name + ' (Total: ' + product.total_quantity + ' units)</option>');
                                });
                                productSelect.prop('disabled', false);
                            } else {
                                productSelect.append('<option value="">{{ __("No products found in this warehouse") }}</option>');
                                productSelect.prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading products:', xhr);
                            productSelect.empty().append('<option value="">{{ __("Error loading products") }}</option>');
                            productSelect.prop('disabled', true);
                        }
                    });
                } else {
                    productSelect.empty().append('<option value="">{{ __("Select Warehouse First") }}</option>');
                    productSelect.prop('disabled', true);
                }

                validateStockOutForm();
            });

            $('#stockout_product_id').on('change', function() {
                var warehouseId = $('#stockout_warehouse_id').val();
                var productId = $(this).val();
                var batchSelect = $('#stockout_batch_id');

                batchSelect.val('').trigger('change');
                $('#stockout_quantity').prop('disabled', true).val('');
                $('#stockoutCurrentStockInfo').hide();
                $('#stockoutQuantityError').hide();

                if (warehouseId && productId) {
                    var url = '/warehouses/' + warehouseId + '/products/' + productId + '/batches';

                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            batchSelect.empty().append('<option value="">{{ __("Select Batch") }}</option>');

                            if (response.success && response.batches && response.batches.length > 0) {
                                $.each(response.batches, function(index, batch) {
                                    batchSelect.append('<option value="' + batch.batch_id + '" data-quantity="' + batch.quantity + '" data-expiry="' + batch.expiry_date + '">' +
                                        batch.batch_code + ' (Stock: ' + batch.quantity + ' units | Exp: ' + batch.expiry_date + ')' +
                                        '</option>');
                                });
                                batchSelect.prop('disabled', false);
                            } else {
                                batchSelect.append('<option value="">{{ __("No batches found for this product in warehouse") }}</option>');
                                batchSelect.prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            batchSelect.empty().append('<option value="">{{ __("Error loading batches") }}</option>');
                            batchSelect.prop('disabled', true);
                        }
                    });
                } else {
                    batchSelect.empty().append('<option value="">{{ __("Select Product First") }}</option>');
                    batchSelect.prop('disabled', true);
                }

                validateStockOutForm();
            });

            $('#stockout_batch_id').on('change', function() {
                var selected = $(this).find('option:selected');
                var quantity = selected.data('quantity') || 0;
                var batchId = $(this).val();

                stockoutCurrentBatchStock = quantity;

                if (batchId) {
                    $('#stockoutCurrentStockValue').text(quantity);
                    $('#stockoutCurrentStockInfo').show();
                    $('#stockout_quantity').prop('disabled', false).attr('max', quantity).val('');
                } else {
                    $('#stockoutCurrentStockInfo').hide();
                    $('#stockout_quantity').prop('disabled', true).val('');
                }

                $('#stockoutQuantityError').hide();
                validateStockOutForm();
            });

            $('#stockout_quantity').on('input', function() {
                var quantity = parseInt($(this).val(), 10) || 0;

                if (quantity > stockoutCurrentBatchStock) {
                    $('#stockoutQuantityError').show();
                } else {
                    $('#stockoutQuantityError').hide();
                }

                validateStockOutForm();
            });

            $('#stockout_remarks').on('input', function() {
                validateStockOutForm();
            });

            function validateStockOutForm() {
                var warehouseSelected = $('#stockout_warehouse_id').val() !== '';
                var productSelected = $('#stockout_product_id').val() !== '';
                var batchSelected = $('#stockout_batch_id').val() !== '';
                var quantity = parseInt($('#stockout_quantity').val(), 10);
                var remarks = $('#stockout_remarks').val().trim();

                var isValid = warehouseSelected && productSelected && batchSelected &&
                            !isNaN(quantity) && quantity > 0 && quantity <= stockoutCurrentBatchStock &&
                            remarks !== '';

                $('#stockoutSubmitBtn').prop('disabled', !isValid);
            }

            $('#stockout').on('hidden.bs.modal', function() {
                $(this).find('select').val('').trigger('change');
                $(this).find('input[type="number"]').val('');
                $(this).find('textarea').val('');
                $('#stockoutCurrentStockInfo').hide();
                $('#stockoutQuantityError').hide();
                $('#stockoutSubmitBtn').prop('disabled', true);
                stockoutCurrentBatchStock = 0;
            });

            $('#stockOutForm').on('submit', function(e) {
                var quantity = parseInt($('#stockout_quantity').val(), 10);

                if (quantity > stockoutCurrentBatchStock) {
                    alert('Quantity to deduct cannot exceed current stock of ' + stockoutCurrentBatchStock + ' units.');
                    e.preventDefault();
                    return false;
                }

                var confirmMessage = 'You are DEDUCTING ' + quantity + ' units from stock.\n' +
                                    'Current: ' + stockoutCurrentBatchStock + ' units\n' +
                                    'Remaining after: ' + (stockoutCurrentBatchStock - quantity) + ' units\n\n' +
                                    'Reason: ' + $('#stockout_remarks').val() + '\n\n' +
                                    'Are you sure you want to proceed?';

                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }

                var submitBtn = $('#stockoutSubmitBtn');
                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            });

            // ========== CAMERA SCANNER FUNCTIONS ==========
            
            function initTransferCameraAndScanner() {
                stopTransferCamera();
                stopTransferScanner();

                navigator.mediaDevices.enumerateDevices().then(devices => {
                    const cameras = devices.filter(d => d.kind === 'videoinput');
                    const select = $('#transfer-camera-select').empty();

                    if (cameras.length === 0) {
                        showTransferScanResult('No camera found on this device', 'danger');
                        return;
                    }

                    cameras.forEach((cam, i) => {
                        select.append(`<option value="${cam.deviceId}">${cam.label || 'Camera ' + (i+1)}</option>`);
                    });

                    startTransferCamera(cameras[0].deviceId);
                }).catch(err => {
                    console.error('Error enumerating devices:', err);
                    showTransferScanResult('Error accessing cameras: ' + err.message, 'danger');
                });
            }

            function startTransferCamera(deviceId) {
                stopTransferCamera();

                navigator.mediaDevices.getUserMedia({
                    video: {
                        deviceId: deviceId ? { exact: deviceId } : undefined,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                }).then(stream => {
                    transferActiveStream = stream;
                    const video = document.getElementById('transfer-barcode-video');
                    video.srcObject = stream;
                    video.onloadedmetadata = function() {
                        video.play();
                        startTransferZXingScanner();
                    };
                }).catch(err => {
                    showTransferScanResult('Camera access denied: ' + err.message, 'danger');
                });
            }

            function startTransferZXingScanner() {
                if (transferIsScanning) {
                    return;
                }

                showTransferScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning for barcode...', 'info');

                try {
                    const hints = new Map();
                    const formats = [
                        ZXing.BarcodeFormat.CODE_128,
                        ZXing.BarcodeFormat.CODE_39,
                        ZXing.BarcodeFormat.CODE_93,
                        ZXing.BarcodeFormat.EAN_13,
                        ZXing.BarcodeFormat.EAN_8,
                        ZXing.BarcodeFormat.UPC_A,
                        ZXing.BarcodeFormat.UPC_E
                    ];
                    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, formats);
                    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                    hints.set(ZXing.DecodeHintType.CHARACTER_SET, 'UTF-8');

                    transferCodeReader = new ZXing.BrowserMultiFormatReader(hints);
                    const videoElement = document.getElementById('transfer-barcode-video');

                    transferCodeReader.decodeFromVideoDevice(undefined, videoElement, (result, err) => {
                        if (result) {
                            const text = result.getText();
                            if (text && text.length >= 4) {
                                stopTransferScanner();
                                stopTransferCamera();
                                showTransferScanResult('Barcode scanned successfully!<br>Code: <b>' + text + '</b>', 'success');
                                if (navigator.vibrate) {
                                    navigator.vibrate(200);
                                }
                                addTransferBatchByCode(text);
                                $('#transferScannerModal').modal('hide');
                            }
                        }

                        if (err && !(err instanceof ZXing.NotFoundException)) {
                            console.error('Scanning error:', err);
                        }
                    });

                    transferIsScanning = true;
                } catch (error) {
                    showTransferScanResult('Failed to start scanner: ' + error.message, 'danger');
                }
            }

            function scanTransferUploadedImage() {
                const file = $('#transfer-barcode-image-upload')[0].files[0];
                if (!file) {
                    showTransferScanResult('Please select an image first', 'warning');
                    return;
                }

                showTransferScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning uploaded image...', 'info');
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, img.width, img.height);
                        const imageData = canvas.toDataURL('image/png');

                        try {
                            const hints = new Map();
                            const formats = [
                                ZXing.BarcodeFormat.CODE_128,
                                ZXing.BarcodeFormat.CODE_39,
                                ZXing.BarcodeFormat.CODE_93,
                                ZXing.BarcodeFormat.EAN_13,
                                ZXing.BarcodeFormat.EAN_8,
                                ZXing.BarcodeFormat.UPC_A,
                                ZXing.BarcodeFormat.UPC_E
                            ];
                            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, formats);
                            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);

                            const reader = new ZXing.BrowserMultiFormatReader(hints);
                            reader.decodeFromImage(undefined, imageData).then(result => {
                                const text = result.getText();
                                if (text && text.length >= 4) {
                                    addTransferBatchByCode(text);
                                    $('#transferScannerModal').modal('hide');
                                } else {
                                    showTransferScanResult('No valid barcode found in image', 'danger');
                                }
                            }).catch(error => {
                                showTransferScanResult('Failed to decode barcode from image', 'danger');
                                console.error(error);
                            });
                        } catch (error) {
                            showTransferScanResult('Error processing image: ' + error.message, 'danger');
                        }
                    };
                };
                reader.readAsDataURL(file);
            }

            function stopTransferScanner() {
                if (transferCodeReader && transferIsScanning) {
                    try {
                        transferCodeReader.reset();
                    } catch (error) {
                        console.error('Error stopping scanner:', error);
                    }
                }
                transferCodeReader = null;
                transferIsScanning = false;
            }

            function stopTransferCamera() {
                if (transferActiveStream) {
                    transferActiveStream.getTracks().forEach(track => track.stop());
                    transferActiveStream = null;
                }
            }

            function resetTransferScannerModal() {
                $('#transfer-scan-result').hide().removeClass().html('');
                $('#transfer-image-preview-container').hide();
                $('#transfer-barcode-image-upload').val('');
                $('#transfer-barcode-image-upload').next('.custom-file-label').html('Choose file...');
                $('#transfer-scan-upload-btn').prop('disabled', true);
            }

            function showTransferScanResult(message, type) {
                $('#transfer-scan-result')
                    .removeClass('alert-success alert-info alert-danger alert-warning')
                    .addClass('alert-' + type)
                    .html(message)
                    .show();
            }

            $(document).keyup(function(e) {
                if(e.altKey && e.keyCode == 84){
                    $('#transferStock').modal('show');
                }
            });
        });
    </script>
@endpush
