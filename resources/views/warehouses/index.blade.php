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
        <div class="modal-dialog modal-lg">
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

    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script>
        $(document).ready(function () {
            let transferWarehouseBatches = {};
            let transferItems = [];
            let transferCodeReader = null;
            let transferIsScanning = false;
            let transferActiveStream = null;

            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#transferStock')
            });

            $('#from_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                var toWarehouseId = $('#to_warehouse_id').val();
                
                if (toWarehouseId && warehouseId === toWarehouseId) {
                    alert('Source and destination warehouses cannot be the same!');
                    $(this).val('').trigger('change');
                    return;
                }
                
                transferItems = [];
                transferWarehouseBatches = {};
                renderTransferItems();

                if (warehouseId) {
                    $.ajax({
                        url: '{{ route("warehouses.get-warehouse-batches", "") }}/' + warehouseId,
                        type: 'GET',
                        success: function(response) {
                            if (response.inventory && response.inventory.length > 0) {
                                $.each(response.inventory, function(index, item) {
                                    transferWarehouseBatches[(item.batch_code || '').toUpperCase()] = item;
                                });
                                $('#transferBatchInfo').text('Warehouse loaded. Scan product batches to transfer.');
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

            $(document).on('input', '.transfer-item-qty', function() {
                const index = parseInt($(this).data('index'), 10);
                const item = transferItems[index];
                let qty = parseInt($(this).val(), 10);

                if (!item) {
                    return;
                }

                if (!qty || qty < 1) {
                    qty = 1;
                }

                if (qty > item.available_quantity) {
                    qty = item.available_quantity;
                }

                item.quantity = qty;
                $(this).val(qty);
                renderTransferItems();
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
                
                transferItems.forEach(function(item) {
                    totalUnits += parseInt(item.quantity, 10) || 0;
                });

                if ($('#from_warehouse_id').val() && $('#to_warehouse_id').val() && transferItems.length > 0) {
                    var summary = 'Transferring <strong>' + totalUnits + '</strong> units across <strong>' +
                                 transferItems.length + '</strong> batch(es) from <strong>' +
                                 fromWarehouse + '</strong> to <strong>' + toWarehouse + '</strong>';
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
                var itemsValid = transferItems.length > 0 && transferItems.every(function(item) {
                    return item.quantity > 0 && item.quantity <= item.available_quantity;
                });
                var isValid = fromSelected && toSelected && itemsValid;
                
                $('#transferSubmitBtn').prop('disabled', !isValid);
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

            function renderTransferItems() {
                var tbody = $('#transferItemsTableBody');
                var hiddenContainer = $('#transferItemsContainer');
                tbody.empty();
                hiddenContainer.empty();

                if (transferItems.length === 0) {
                    tbody.append('<tr id="transferEmptyRow"><td colspan="5" class="text-center text-muted">{{ __("No scanned batches yet") }}</td></tr>');
                } else {
                    transferItems.forEach(function(item, index) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + escapeHtml(item.batch_code) + '<br><small class="text-muted">Available: ' + item.available_quantity + '</small></td>' +
                                '<td>' + escapeHtml(item.product_name) + '</td>' +
                                '<td>' + escapeHtml(item.expiry_date) + '</td>' +
                                '<td><input type="number" min="1" max="' + item.available_quantity + '" value="' + item.quantity + '" class="form-control transfer-item-qty" data-index="' + index + '"></td>' +
                                '<td><button type="button" class="btn btn-danger btn-sm remove-transfer-item" data-index="' + index + '"><i class="fa fa-trash"></i></button></td>' +
                            '</tr>'
                        );
                        hiddenContainer.append('<input type="hidden" name="items[' + index + '][batch_id]" value="' + item.batch_id + '">');
                        hiddenContainer.append('<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '">');
                    });
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
                $('#transferSubmitBtn').prop('disabled', true);
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

                var totalUnits = transferItems.reduce(function(sum, item) {
                    return sum + (parseInt(item.quantity, 10) || 0);
                }, 0);

                if (!confirm('Are you sure you want to transfer ' + totalUnits + ' units across ' + transferItems.length + ' batch(es)?')) {
                    e.preventDefault();
                    return false;
                }
            });

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

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            $(document).keyup(function(e) {
                if(e.altKey && e.keyCode == 84){
                    $('#transferStock').modal('show');
                }
            });
        });
    </script>
@endpush
