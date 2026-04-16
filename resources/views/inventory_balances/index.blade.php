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
                            <div class="pull-right mr-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="stockin" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title h6">{{ __('Stock In') }} - Move from Warehouse to Lorry</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryBalances.stockin', 'id' => 'stockinForm']) !!}
                    <div class="form-group">
                        <label for="warehouse_id_stockin" class="col-form-label">{{ __('Select Warehouse') }}:</label>
                        <select name="warehouse_id" id="warehouse_id_stockin" class="form-control" required>
                            <option value="">{{ __('Select Warehouse') }}</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->location }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lorry_id_stockin" class="col-form-label">{{ __('Select Lorry') }}:</label>
                        <select name="lorry_id" id="lorry_id_stockin" class="form-control" required>
                            <option value="">{{ __('Select Lorry') }}</option>
                            @foreach($lorryItems as $lorryId => $lorryName)
                                <option value="{{ $lorryId }}">{{ $lorryName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="batch_code_scan" class="col-form-label">{{ __('Scan Product Batch') }}:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="batch_code_scan" placeholder="Scan barcode and press Enter" autocomplete="off" disabled>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="openBatchCameraBtn" disabled>
                                    <i class="fa fa-camera"></i> {{ __('Scan Barcode') }}
                                </button>
                            </div>
                        </div>
                        <small class="d-block text-muted" id="stockinBatchHelp">{{ __('Select warehouse and lorry first. Barcode scanner can input code followed by Enter.') }}</small>
                        <small class="d-none text-danger" id="stockinScanError"></small>
                        <small class="d-none text-success" id="stockinScanSuccess"></small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="stockinItemsTable">
                            <thead>
                                <tr>
                                    <th>{{ __('Batch Code') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-center">{{ __('Available') }}</th>
                                    <th style="width: 160px;">{{ __('Quantity') }}</th>
                                    <th style="width: 90px;" class="text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="stockinEmptyRow">
                                    <td colspan="5" class="text-center text-muted">{{ __('No scanned items yet') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="stockinItemsContainer"></div>

                    <div class="alert alert-info" id="distributionSummary" style="display: none;">
                        <strong>{{ __('Stock In Summary:') }}</strong>
                        <span id="summaryText"></span>
                    </div>

                    <div class="form-group text-right mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-success" id="stockinSubmitBtn" disabled>{{ __('Stock In') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <div id="stockout" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h4 class="modal-title h6">{{ __('Stock Out') }} - Return from Lorry to Warehouse</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryBalances.stockout', 'id' => 'stockoutForm']) !!}
                    <div class="form-group">
                        <label for="lorry_id" class="col-form-label">{{ __('Select Lorry') }}:</label>
                        <select name="lorry_id" id="lorry_id" class="form-control select2" required>
                            <option value="">{{ __('Select Lorry') }}</option>
                            @foreach($lorryItems as $lorryId => $lorryName)
                                <option value="{{ $lorryId }}">{{ $lorryName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="batch_id" class="col-form-label">{{ __('Select Batch to Return') }}:</label>
                        <select name="batch_id" id="batch_id" class="form-control" required disabled>
                            <option value="">{{ __('First select a lorry') }}</option>
                        </select>
                        <small class="text-muted" id="batchQuantityInfo"></small>
                    </div>

                    <div class="form-group">
                        <label for="to_warehouse_id" class="col-form-label">{{ __('Return to Warehouse') }}:</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-control" required disabled>
                            <option value="">{{ __('Select destination warehouse') }}</option>
                        </select>
                        <small class="text-muted" id="warehouseInfo"></small>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class="col-form-label">{{ __('Quantity to Return') }}:</label>
                        <input type="number" min="1" class="form-control" placeholder="Enter quantity" name="quantity" id="stockout_quantity" disabled>
                        <small class="text-danger quantity-error" style="display: none;">{{ __('Please enter a valid quantity') }}</small>
                    </div>

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

    <div class="modal fade" id="stockinScannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title h6">{{ __('Scan Batch Barcode') }}</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="stockinScanTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="stockin-camera-tab" data-toggle="tab" href="#stockin-camera-pane" role="tab">
                                <i class="fa fa-camera"></i> Camera Scan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="stockin-upload-tab" data-toggle="tab" href="#stockin-upload-pane" role="tab">
                                <i class="fa fa-upload"></i> Upload Image
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="stockin-camera-pane" role="tabpanel">
                            <div class="form-group">
                                <label for="stockin-camera-select">{{ __('Select Camera') }}</label>
                                <select id="stockin-camera-select" class="form-control"></select>
                            </div>
                            <div style="background:#000;border-radius:8px; position:relative;">
                                <video id="stockin-barcode-video" autoplay playsinline style="width:100%"></video>
                                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:80%; height:100px; border:3px solid rgba(255,0,0,0.5); border-radius:10px; pointer-events:none;"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="stockin-upload-pane" role="tabpanel">
                            <div class="form-group">
                                <label for="stockin-barcode-image-upload">{{ __('Upload Barcode Image') }}</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="stockin-barcode-image-upload" accept="image/*">
                                    <label class="custom-file-label" for="stockin-barcode-image-upload">Choose file...</label>
                                </div>
                                <small class="form-text text-muted">Supported formats: PNG, JPG, JPEG, GIF, BMP</small>
                            </div>

                            <div id="stockin-image-preview-container" style="display:none; text-align:center; margin-top:15px;">
                                <img id="stockin-image-preview" style="max-width:100%; max-height:300px; border:1px solid #ddd; border-radius:4px; padding:5px;">
                            </div>

                            <button type="button" class="btn btn-primary mt-3" id="stockin-scan-upload-btn" disabled>
                                <i class="fa fa-search"></i> Scan Uploaded Image
                            </button>
                        </div>
                    </div>

                    <div class="alert mt-3 d-none" id="stockin-camera-result"></div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

    <style>
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
        
        /* Enhanced quantity input styling */
        .stockin-item-qty {
            transition: all 0.3s ease;
        }
        
        .stockin-item-qty.is-invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }
        
        .stockin-item-qty.is-warning {
            border-color: #ffc107;
            background-color: #fffbf0;
        }
        
        .stockin-item-qty.valid-feedback {
            border-color: #28a745;
            background-color: #f0fff4;
        }
        
        .stockin-qty-error {
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

        /* Add this to your style section */
        #stockinItemsTable td {
            vertical-align: middle;
        }

        #stockinItemsTable .stockin-item-qty {
            display: block !important;
            visibility: visible !important;
            min-width: 100px;
        }

        /* Debug style - add a border to see if the cell exists */
        #stockinItemsTable td:nth-child(4) {
            /* This will help you see if the 4th column (Quantity) is visible */
            background-color: #f9f9f9;
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

    <script>
    $(document).ready(function () {
        let stockinWarehouseBatches = {};
        let stockinItems = [];
        let stockinCodeReader = null;
        let stockinScanning = false;
        let stockinStream = null;

        // Define updateHiddenQuantities FIRST before it's used
        function updateHiddenQuantities() {
            // Update existing hidden inputs without recreating them
            $.each(stockinItems, function(index, item) {
                $('#stockinItemsContainer input[name="items[' + index + '][quantity]"]').val(item.quantity);
            });
        }

        $('#warehouse_id_stockin').select2({
            width: '100%',
            dropdownParent: $('#stockin')
        });

        $('#lorry_id_stockin').select2({
            width: '100%',
            dropdownParent: $('#stockin')
        });

        $('#lorry_id').select2({
            width: '100%',
            dropdownParent: $('#stockout')
        });

        $('#batch_id').select2({
            width: '100%',
            dropdownParent: $('#stockout')
        });

        $('#to_warehouse_id').select2({
            width: '100%',
            dropdownParent: $('#stockout')
        });

        $('#warehouse_id_stockin, #lorry_id_stockin').on('change', function() {
            if (this.id === 'warehouse_id_stockin') {
                loadWarehouseBatches();
            } else {
                toggleStockInScanner();
                validateStockInForm();
                updateDistributionSummary();
            }
        });

        $('#batch_code_scan').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addScannedBatch($(this).val());
            }
        });

        $('#openBatchCameraBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                $('#stockinScannerModal').modal('show');
            }
        });

        // Enhanced quantity input handler with better validation
        $(document).on('input', '.stockin-item-qty', function() {
            const index = parseInt($(this).data('index'), 10);
            const item = stockinItems[index];
            let rawValue = $(this).val();
            let quantity = parseInt(rawValue, 10);
            
            if (!item) {
                return;
            }
            
            const maxQty = item.available_quantity;
            const errorSpan = $('#stockin-qty-error-' + index);
            
            // Remove any existing warning classes
            $(this).removeClass('is-invalid is-warning valid-feedback');
            
            // Clear any non-numeric characters
            if (rawValue !== '' && isNaN(quantity)) {
                $(this).val('').addClass('is-invalid');
                errorSpan.text('Please enter a valid number').show();
                item.quantity = 0;
                validateStockInForm();
                updateDistributionSummary();
                updateHiddenQuantities();
                return;
            }
            
            // Check if empty
            if (rawValue === '') {
                errorSpan.hide();
                item.quantity = 0;
                $(this).removeClass('is-invalid is-warning');
                validateStockInForm();
                updateDistributionSummary();
                updateHiddenQuantities();
                return;
            }
            
            // Check if less than 1
            if (quantity < 1) {
                $(this).addClass('is-invalid');
                errorSpan.text('Quantity must be at least 1').show();
                item.quantity = 0;
                validateStockInForm();
                updateDistributionSummary();
                updateHiddenQuantities();
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
            
            validateStockInForm();
            updateDistributionSummary();
            updateHiddenQuantities(); // Update hidden inputs
        });

        $(document).on('click', '.remove-stockin-item', function() {
            const index = parseInt($(this).data('index'), 10);
            stockinItems.splice(index, 1);
            renderStockInItems();
        });

        $('#stockinScannerModal').on('shown.bs.modal', function() {
            initStockInScanner();
        });

        $('#stockinScannerModal').on('hidden.bs.modal', function() {
            stopStockInScanner();
            stopStockInCamera();
            hideCameraResult();
        });

        $('#stockin-camera-select').on('change', function() {
            const deviceId = this.value;
            if (deviceId) {
                stopStockInScanner();
                stopStockInCamera();
                setTimeout(function() {
                    startStockInCamera(deviceId);
                }, 300);
            }
        });

        $('#stockin-barcode-image-upload').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                $(this).next('.custom-file-label').html(file.name);
                const reader = new FileReader();
                reader.onload = function(loadEvent) {
                    $('#stockin-image-preview').attr('src', loadEvent.target.result);
                    $('#stockin-image-preview-container').show();
                    $('#stockin-scan-upload-btn').prop('disabled', false);
                };
                reader.readAsDataURL(file);
            } else {
                $(this).next('.custom-file-label').html('Choose file...');
                $('#stockin-image-preview-container').hide();
                $('#stockin-scan-upload-btn').prop('disabled', true);
            }
        });

        $('#stockin-scan-upload-btn').on('click', function() {
            scanStockInUploadedImage();
        });

        function loadWarehouseBatches() {
            const warehouseId = $('#warehouse_id_stockin').val();
            stockinWarehouseBatches = {};
            clearStockInItems();

            if (!warehouseId) {
                setStockInHelp('{{ __("Select warehouse and lorry first. Barcode scanner can input code followed by Enter.") }}', 'muted');
                toggleStockInScanner();
                validateStockInForm();
                updateDistributionSummary();
                return;
            }

            setStockInHelp('{{ __("Loading warehouse batches...") }}', 'muted');

            $.ajax({
                url: '{{ route("warehouses.get-warehouse-batches", "") }}/' + warehouseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const inventory = response.inventory || response || [];

                    $.each(inventory, function(_, item) {
                        const code = (item.batch_code || '').trim();
                        if (!code) {
                            return;
                        }

                        stockinWarehouseBatches[code.toUpperCase()] = {
                            batch_id: parseInt(item.batch_id || item.id, 10),
                            batch_code: code,
                            product_name: item.product_name || 'Unknown',
                            quantity: parseInt(item.quantity || 0, 10),
                            expiry_date: item.expiry_date || 'N/A'
                        };
                    });

                    if (Object.keys(stockinWarehouseBatches).length > 0) {
                        setStockInHelp('{{ __("Ready to scan batches for this warehouse.") }}', 'success');
                    } else {
                        setStockInHelp('{{ __("No batches available in this warehouse.") }}', 'danger');
                    }

                    toggleStockInScanner();
                    validateStockInForm();
                    updateDistributionSummary();
                },
                error: function() {
                    setStockInHelp('{{ __("Error loading warehouse batches.") }}', 'danger');
                    toggleStockInScanner();
                    validateStockInForm();
                    updateDistributionSummary();
                }
            });
        }

        function toggleStockInScanner() {
            const enabled = $('#warehouse_id_stockin').val() !== '' && $('#lorry_id_stockin').val() !== '' && Object.keys(stockinWarehouseBatches).length > 0;
            $('#batch_code_scan').prop('disabled', !enabled).val('');
            $('#openBatchCameraBtn').prop('disabled', !enabled);

            if (!enabled) {
                hideStockInMessages();
            }
        }

        function setStockInHelp(message, type) {
            $('#stockinBatchHelp')
                .removeClass('text-muted text-success text-danger')
                .addClass(type === 'success' ? 'text-success' : (type === 'danger' ? 'text-danger' : 'text-muted'))
                .text(message);
        }

        function hideStockInMessages() {
            $('#stockinScanError').addClass('d-none').text('');
            $('#stockinScanSuccess').addClass('d-none').text('');
        }

        function showStockInError(message) {
            $('#stockinScanSuccess').addClass('d-none').text('');
            $('#stockinScanError').removeClass('d-none').text(message);
        }

        function showStockInSuccess(message) {
            $('#stockinScanError').addClass('d-none').text('');
            $('#stockinScanSuccess').removeClass('d-none').text(message);
        }

        function addScannedBatch(rawCode) {
            const code = (rawCode || '').trim();
            $('#batch_code_scan').val('');

            if (!code) {
                return;
            }

            hideStockInMessages();

            if (!$('#warehouse_id_stockin').val() || !$('#lorry_id_stockin').val()) {
                showStockInError('Please select warehouse and lorry first.');
                return;
            }

            const batch = stockinWarehouseBatches[code.toUpperCase()];
            if (!batch) {
                showStockInError('Batch code not found in the selected warehouse: ' + code);
                return;
            }

            const existingItem = stockinItems.find(function(item) {
                return item.batch_id === batch.batch_id;
            });

            if (existingItem) {
                if (existingItem.quantity >= existingItem.available_quantity) {
                    showStockInError('Batch ' + batch.batch_code + ' already reached available quantity.');
                    return;
                }

                existingItem.quantity += 1;
            } else {
                stockinItems.push({
                    batch_id: batch.batch_id,
                    batch_code: batch.batch_code,
                    product_name: batch.product_name,
                    available_quantity: batch.quantity,
                    expiry_date: batch.expiry_date,
                    quantity: 1
                });
            }

            renderStockInItems();
            showStockInSuccess('Added batch ' + batch.batch_code + ' to stock in list.');
        }

        function clearStockInItems() {
            stockinItems = [];
            renderStockInItems();
        }

        function renderStockInItems() {
            const tbody = $('#stockinItemsTable tbody');
            const hiddenContainer = $('#stockinItemsContainer');

            tbody.empty();
            hiddenContainer.empty();

            if (stockinItems.length === 0) {
                tbody.append('<tr id="stockinEmptyRow"><td colspan="5" class="text-center text-muted">{{ __("No scanned items yet") }}</td></tr>');
            } else {
                $.each(stockinItems, function(index, item) {
                    let quantityStatus = '';
                    let quantityClass = '';
                    
                    if (item.quantity > item.available_quantity) {
                        quantityStatus = '<span class="badge badge-danger quantity-status-badge">Exceeds stock!</span>';
                        quantityClass = 'is-invalid';
                    } else if (item.quantity === item.available_quantity && item.quantity > 0) {
                        quantityStatus = '<span class="badge badge-warning quantity-status-badge">Full quantity</span>';
                        quantityClass = 'is-warning';
                    } else if (item.quantity > 0) {
                        const percentage = Math.round((item.quantity / item.available_quantity) * 100);
                        quantityStatus = '<span class="badge badge-info quantity-status-badge">' + percentage + '% used</span>';
                        quantityClass = 'valid-feedback';
                    }
                    
                    // Build the row HTML
                    var row = '<tr>';
                    row += '<td>' + escapeHtml(item.batch_code) + '<br><small class="text-muted">Exp: ' + escapeHtml(item.expiry_date) + '</small></td>';
                    row += '<td>' + escapeHtml(item.product_name) + ' ' + quantityStatus + '</td>';
                    row += '<td class="text-center">' + item.available_quantity + '</td>';
                    row += '<td>';
                    row += '<input type="text" value="' + item.quantity + '" ';
                    row += 'class="form-control stockin-item-qty ' + quantityClass + '" ';
                    row += 'data-index="' + index + '" ';
                    row += 'data-max="' + item.available_quantity + '" ';
                    row += 'style="width: 100%;">';
                    row += '<small class="text-danger stockin-qty-error" id="stockin-qty-error-' + index + '" style="display: none; font-size: 11px;"></small>';
                    row += '</td>';
                    row += '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-stockin-item" data-index="' + index + '"><i class="fa fa-trash"></i></button></td>';
                    row += '</tr>';
                    
                    tbody.append(row);

                    // Add hidden inputs for form submission
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][product_batch_id]" value="' + item.batch_id + '">');
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '" class="qty-hidden-' + index + '">');
                });
            }

            validateStockInForm();
            updateDistributionSummary();
        }

        function updateDistributionSummary() {
            const warehouseName = $('#warehouse_id_stockin').find('option:selected').text();
            const lorryName = $('#lorry_id_stockin').find('option:selected').text();
            let totalUnits = 0;
            let hasIssues = false;

            $.each(stockinItems, function(_, item) {
                totalUnits += parseInt(item.quantity, 10) || 0;
                if (item.quantity > item.available_quantity) {
                    hasIssues = true;
                }
            });

            if (stockinItems.length > 0 && $('#warehouse_id_stockin').val() && $('#lorry_id_stockin').val()) {
                let summaryHtml = stockinItems.length + ' batch(es), ' +
                    totalUnits + ' unit(s) from <strong>' + escapeHtml(warehouseName) + '</strong> to <strong>' + escapeHtml(lorryName) + '</strong>';
                
                if (hasIssues) {
                    summaryHtml += '<br><span class="text-danger">⚠️ Some quantities exceed available stock!</span>';
                    $('#distributionSummary').removeClass('alert-info').addClass('alert-danger');
                } else {
                    $('#distributionSummary').removeClass('alert-danger').addClass('alert-info');
                }
                
                $('#summaryText').html(summaryHtml);
                $('#distributionSummary').show();
            } else {
                $('#distributionSummary').hide();
            }
        }

        function validateStockInForm() {
            const warehouseSelected = $('#warehouse_id_stockin').val() !== '';
            const lorrySelected = $('#lorry_id_stockin').val() !== '';
            
            let hasQuantityIssues = false;
            let hasValidQuantities = false;
            
            $.each(stockinItems, function(index, item) {
                if (item.quantity > 0) {
                    hasValidQuantities = true;
                    if (item.quantity > item.available_quantity) {
                        hasQuantityIssues = true;
                    }
                }
            });
            
            const canSubmit = warehouseSelected && lorrySelected && hasValidQuantities && !hasQuantityIssues;
            
            $('#stockinSubmitBtn').prop('disabled', !canSubmit);
            
            if (hasValidQuantities && hasQuantityIssues) {
                $('#stockinSubmitBtn')
                    .attr('title', 'Some quantities exceed available stock. Please adjust them before submitting.')
                    .addClass('btn-danger')
                    .removeClass('btn-success');
            } else {
                $('#stockinSubmitBtn')
                    .removeAttr('title')
                    .addClass('btn-success')
                    .removeClass('btn-danger');
            }
        }

        // Form submission validation - ADD THIS TO SEE WHAT'S BEING SUBMITTED
        $('#stockinForm').on('submit', function(e) {
            // Log all form data before submission for debugging
            console.log('Form data before submit:');
            $.each(stockinItems, function(index, item) {
                console.log(`Item ${index}: batch_id=${item.batch_id}, quantity=${item.quantity}`);
            });
            
            let hasExceededItems = false;
            let errorMessage = '';
            
            $.each(stockinItems, function(index, item) {
                if (item.quantity > item.available_quantity) {
                    hasExceededItems = true;
                    errorMessage += `\n• ${item.batch_code}: ${item.quantity} > ${item.available_quantity} (exceeds by ${item.quantity - item.available_quantity})`;
                }
            });
            
            if (hasExceededItems) {
                e.preventDefault();
                showStockInError(`Cannot submit. The following batches exceed available stock:${errorMessage}`);
                return false;
            }
            
            let totalQuantity = 0;
            $.each(stockinItems, function(index, item) {
                totalQuantity += item.quantity;
            });
            
            if (totalQuantity > 100) {
                if (!confirm(`You are moving ${totalQuantity} units total across ${stockinItems.length} batches.\n\nAre you sure you want to proceed?`)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            const submitBtn = $('#stockinSubmitBtn');
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        });

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function initStockInScanner() {
            stopStockInCamera();
            stopStockInScanner();
            hideCameraResult();
            resetStockInScannerModal();

            navigator.mediaDevices.enumerateDevices().then(function(devices) {
                const cameras = devices.filter(function(device) {
                    return device.kind === 'videoinput';
                });
                const select = $('#stockin-camera-select').empty();

                if (cameras.length === 0) {
                    showCameraResult('No camera found on this device.', 'danger');
                    return;
                }

                $.each(cameras, function(index, camera) {
                    select.append('<option value="' + camera.deviceId + '">' + escapeHtml(camera.label || ('Camera ' + (index + 1))) + '</option>');
                });

                startStockInCamera(cameras[0].deviceId);
            }).catch(function(error) {
                showCameraResult('Error accessing cameras: ' + error.message, 'danger');
            });
        }

        function startStockInCamera(deviceId) {
            navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: deviceId ? { exact: deviceId } : undefined,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            }).then(function(stream) {
                stockinStream = stream;
                const video = document.getElementById('stockin-barcode-video');
                video.srcObject = stream;
                video.onloadedmetadata = function() {
                    video.play();
                    startStockInDecode();
                };
            }).catch(function(error) {
                showCameraResult('Camera access denied: ' + error.message, 'danger');
            });
        }

        function startStockInDecode() {
            if (stockinScanning) {
                return;
            }

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
                stockinCodeReader = new ZXing.BrowserMultiFormatReader(hints);

                stockinCodeReader.decodeFromVideoDevice(undefined, document.getElementById('stockin-barcode-video'), function(result, err) {
                    if (result) {
                        const code = result.getText();
                        if (code && code.length >= 4) {
                            showCameraResult('Barcode scanned successfully. Code: ' + code, 'success');
                            if (navigator.vibrate) {
                                navigator.vibrate(200);
                            }
                            addScannedBatch(code);
                            $('#stockinScannerModal').modal('hide');
                        }
                    }

                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error('Scanner error:', err);
                    }
                });

                stockinScanning = true;
                showCameraResult('Scanning for barcode...', 'info');
            } catch (error) {
                showCameraResult('Failed to start scanner: ' + error.message, 'danger');
            }
        }

        function scanStockInUploadedImage() {
            const file = $('#stockin-barcode-image-upload')[0].files[0];
            if (!file) {
                showCameraResult('Please select an image first', 'warning');
                return;
            }

            showCameraResult('Scanning uploaded image...', 'info');

            const reader = new FileReader();
            reader.onload = function(loadEvent) {
                const img = new Image();
                img.src = loadEvent.target.result;

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
                        reader.decodeFromImage(undefined, imageData).then(function(result) {
                            const code = result.getText();
                            if (code && code.length >= 4) {
                                showCameraResult('Barcode scanned successfully. Code: ' + code, 'success');
                                addScannedBatch(code);
                                $('#stockinScannerModal').modal('hide');
                            } else {
                                showCameraResult('No valid barcode found in image', 'danger');
                            }
                        }).catch(function(error) {
                            console.error('Image decode error:', error);
                            showCameraResult('Failed to decode barcode from image', 'danger');
                        });
                    } catch (error) {
                        console.error('Error processing image:', error);
                        showCameraResult('Error processing image: ' + error.message, 'danger');
                    }
                };
            };
            reader.readAsDataURL(file);
        }

        function stopStockInScanner() {
            if (stockinCodeReader && stockinScanning) {
                try {
                    stockinCodeReader.reset();
                } catch (error) {
                    console.error('Error stopping stock in scanner:', error);
                }
            }

            stockinCodeReader = null;
            stockinScanning = false;
        }

        function stopStockInCamera() {
            if (stockinStream) {
                stockinStream.getTracks().forEach(function(track) {
                    track.stop();
                });
                stockinStream = null;
            }
        }

        function showCameraResult(message, type) {
            $('#stockin-camera-result')
                .removeClass('d-none alert-info alert-success alert-danger alert-warning')
                .addClass('alert-' + type)
                .html(message);
        }

        function hideCameraResult() {
            $('#stockin-camera-result')
                .addClass('d-none')
                .removeClass('alert-info alert-success alert-danger alert-warning')
                .text('');
        }

        function resetStockInScannerModal() {
            $('#stockin-camera-tab').tab('show');
            $('#stockin-image-preview-container').hide();
            $('#stockin-barcode-image-upload').val('');
            $('#stockin-barcode-image-upload').next('.custom-file-label').html('Choose file...');
            $('#stockin-scan-upload-btn').prop('disabled', true);
        }

        $('#lorry_id').on('change', function() {
            const lorryId = $(this).val();
            const batchSelect = $('#batch_id');
            const warehouseSelect = $('#to_warehouse_id');

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

                        batchSelect.trigger('change.select2');
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

        $('#batch_id').on('change', function() {
            const selected = $(this).find('option:selected');
            const quantity = selected.data('quantity') || 0;
            const batchId = $(this).val();
            const warehouseSelect = $('#to_warehouse_id');

            if (quantity > 0) {
                $('#batchQuantityInfo').text('Available on lorry: ' + quantity + ' units');
                $('#stockout_quantity').prop('disabled', false).attr('max', quantity).val('');

                $('#batchDetailsText').html(
                    '<strong>Batch:</strong> ' + selected.text() + '<br>' +
                    '<strong>Available on lorry:</strong> ' + quantity + ' units'
                );
                $('#batchDetails').show();

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

                        warehouseSelect.trigger('change.select2');
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

        $('#to_warehouse_id').on('change', function() {
            validateStockOutForm();

            const selected = $(this).find('option:selected');
            const stock = selected.data('stock') || 0;

            if (selected.val()) {
                $('#warehouseInfo').text('Current stock in this warehouse: ' + stock + ' units');
            }
        });

        $('#stockout_quantity').on('input', function() {
            validateStockOutForm();
        });

        function validateStockOutForm() {
            const lorrySelected = $('#lorry_id').val() !== '';
            const batchSelected = $('#batch_id').val() !== '';
            const warehouseSelected = $('#to_warehouse_id').val() !== '';
            const quantity = $('#stockout_quantity').val();
            const maxQuantity = $('#stockout_quantity').attr('max');
            const quantityValid = quantity && parseInt(quantity, 10) > 0 && parseInt(quantity, 10) <= parseInt(maxQuantity, 10);

            $('#stockoutSubmitBtn').prop('disabled', !(lorrySelected && batchSelected && warehouseSelected && quantityValid));

            if (!quantityValid && quantity) {
                $('#stockout').find('.quantity-error').text('Quantity must be between 1 and ' + maxQuantity).show();
            } else {
                $('#stockout').find('.quantity-error').hide();
            }
        }

        $('#stockin').on('hidden.bs.modal', function() {
            $('#warehouse_id_stockin').val('').trigger('change');
            $('#lorry_id_stockin').val('').trigger('change');
            $('#batch_code_scan').val('');
            stockinWarehouseBatches = {};
            clearStockInItems();
            hideStockInMessages();
            setStockInHelp('{{ __("Select warehouse and lorry first. Barcode scanner can input code followed by Enter.") }}', 'muted');
            $('#distributionSummary').hide();
            $('#stockinSubmitBtn').prop('disabled', true).removeClass('btn-danger').addClass('btn-success');
        });

        $('#stockout').on('hidden.bs.modal', function() {
            $(this).find('select').val('').trigger('change');
            $(this).find('input[type="number"]').val('');
            $(this).find('.quantity-error').hide();
            $(this).find('#batchDetails').hide();
            $('#stockoutSubmitBtn').prop('disabled', true);
        });

        $(document).keyup(function(e) {
            if (e.altKey && e.keyCode == 78) {
                $('.card .card-header button').first().click();
            }
        });
    });
    </script>
@endpush