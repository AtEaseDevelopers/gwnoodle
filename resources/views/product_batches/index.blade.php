@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Product Batch')}}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('Product Batches')}}
                             
                             <!-- Action Buttons -->
                             <div class="pull-right">
                           
                                <a href="{{ route('productBatches.create') }}" class="btn btn-primary btn-sm mr-2">
                                    <i class="fa fa-plus-square"></i> New Batch
                                </a>
                                <a class="btn btn-success btn-sm mr-2 btn-scan-barcode">
                                    <i class="fa fa-barcode"></i> Scan Barcode
                                </a>
                               
                             </div>
                         </div>
                         
                         <div class="card-body">
                             @include('product_batches.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
<!-- Scanner Modal -->
<div class="modal fade" id="barcodeScannerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa fa-camera"></i> Scan Barcode
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" id="scanTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="camera-tab" data-toggle="tab" href="#camera" role="tab">
                            <i class="fa fa-camera"></i> Camera Scan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="upload-tab" data-toggle="tab" href="#upload" role="tab">
                            <i class="fa fa-upload"></i> Upload Image
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="scanTabContent">
                    <!-- Camera Tab -->
                    <div class="tab-pane fade show active" id="camera" role="tabpanel">
                        <div class="form-group">
                            <label>Select Camera</label>
                            <select id="camera-select" class="form-control"></select>
                        </div>

                        <div style="background:#000;border-radius:8px; position:relative;">
                            <video id="barcode-video" autoplay playsinline style="width:100%"></video>
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:80%; height:100px; border:3px solid rgba(255,0,0,0.5); border-radius:10px; pointer-events:none;"></div>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div class="tab-pane fade" id="upload" role="tabpanel">
                        <div class="form-group">
                            <label>Upload Barcode Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="barcode-image-upload" accept="image/*">
                                <label class="custom-file-label" for="barcode-image-upload">Choose file...</label>
                            </div>
                            <small class="form-text text-muted">Supported formats: PNG, JPG, JPEG, GIF, BMP</small>
                        </div>

                        <div id="image-preview-container" style="display:none; text-align:center; margin-top:15px;">
                            <img id="image-preview" style="max-width:100%; max-height:300px; border:1px solid #ddd; border-radius:4px; padding:5px;">
                        </div>

                        <button type="button" class="btn btn-primary mt-3" id="scan-upload-btn" disabled>
                            <i class="fa fa-search"></i> Scan Uploaded Image
                        </button>
                    </div>
                </div>

                <div id="scan-result" class="alert mt-3" style="display:none"></div>

                <!-- Cancel Button -->
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Bulk Stock In Modal -->
<div class="modal fade" id="batchStockInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title">
                    <i class="fa fa-plus-circle"></i> Stock In Product Batches
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-2">
                <form id="batch-stockin-form">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-2">
                                <label for="stockin-batch-scan-input" class="mb-1">Scan Product Batch <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fa fa-barcode"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="stockin-batch-scan-input" autocomplete="off" placeholder="Scan barcode and press Enter">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="mb-1 d-block">&nbsp;</label>
                                <button type="button" class="btn btn-info btn-block" id="open-stockin-camera-btn">
                                    <i class="fa fa-camera"></i> Scan Barcode
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-2">
                                <label for="stockin-warehouse-id" class="mb-1">Warehouse <span class="text-danger">*</span></label>
                                <select class="form-control" id="stockin-warehouse-id" name="warehouse_id" required>
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses ?? [] as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->location ? ' (' . $warehouse->location . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="mb-1 d-block">&nbsp;</label>
                                <div class="alert alert-light border mb-0 py-2">
                                    <strong>Total Batches:</strong> <span id="stockin-total-batches">0</span><br>
                                    <strong>Total Units:</strong> <span id="stockin-total-units">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-2">
                                <label for="stockin-remark" class="mb-1">Remark (Optional)</label>
                                <textarea class="form-control" id="stockin-remark" name="remark" rows="1"
                                        placeholder="Enter any notes"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-2 d-none" id="stockin-scan-message"></div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Batch Code</th>
                                    <th>Product</th>
                                    <th>Expiry Date</th>
                                    <th style="width: 140px;">Quantity</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="stockin-items-table-body">
                                <tr id="stockin-empty-row">
                                    <td colspan="5" class="text-center text-muted">No scanned batches yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success btn-sm" id="confirm-stockin-btn" disabled>
                    <i class="fa fa-check"></i> Process Stock In
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    
    <script>
        let codeReader = null;
        let isScanning = false;
        let activeStream = null;
        let stockInItems = [];
        let stockInBatchCache = {};

        $(document).ready(function() {
            HideLoad();
            
            $(document).on('click', '.btn-scan-barcode', function(e) {
                e.preventDefault();
                $('#batchStockInModal').modal('show');
                setTimeout(() => $('#stockin-batch-scan-input').trigger('focus'), 250);
            });

            $('#open-stockin-camera-btn').on('click', function() {
                $('#barcodeScannerModal').modal('show');
            });

            $('#stockin-batch-scan-input').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const code = $(this).val().trim();
                    $(this).val('');

                    if (code) {
                        lookupBatchAndAdd(code);
                    }
                }
            });

            $('#barcodeScannerModal').on('shown.bs.modal', function() {
                const baseZIndex = 1060;
                $(this).css('z-index', baseZIndex);
                $('.modal-backdrop').last().css('z-index', baseZIndex - 10);
                initCameraAndScanner();
            });

            $('#barcodeScannerModal').on('hidden.bs.modal', function() {
                stopScanner();
                stopCamera();
                resetScannerModal();
                $(this).css('z-index', '');
                $('body').addClass('modal-open');
                setTimeout(() => $('#stockin-batch-scan-input').trigger('focus'), 150);
            });

            $('#camera-select').on('change', function () {
                const deviceId = this.value;
                if (deviceId) {
                    stopScanner();
                    stopCamera();
                    setTimeout(() => startCamera(deviceId), 500);
                }
            });

            $('#barcode-image-upload').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    $(this).next('.custom-file-label').html(file.name);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image-preview').attr('src', e.target.result);
                        $('#image-preview-container').show();
                        $('#scan-upload-btn').prop('disabled', false);
                    }
                    reader.readAsDataURL(file);
                } else {
                    $(this).next('.custom-file-label').html('Choose file...');
                    $('#image-preview-container').hide();
                    $('#scan-upload-btn').prop('disabled', true);
                }
            });

            $('#scan-upload-btn').on('click', function() {
                scanUploadedImage();
            });

           $(document).on('input', '.stockin-item-qty', function() {
                const index = parseInt($(this).data('index'), 10);
                const item = stockInItems[index];
                let rawValue = $(this).val();
                let quantity = parseInt(rawValue, 10);
                
                if (!item) {
                    return;
                }
                
                const errorSpan = $('#stockin-qty-error-' + index);
                
                // Remove any existing warning classes
                $(this).removeClass('is-invalid is-warning valid-feedback');
                
                // Clear any non-numeric characters
                if (rawValue !== '' && isNaN(quantity)) {
                    $(this).val('').addClass('is-invalid');
                    errorSpan.text('Please enter a valid number').show();
                    item.quantity = 0;
                    updateTotalsOnly(); // Only update totals, not full re-render
                    return;
                }
                
                // Check if empty
                if (rawValue === '') {
                    errorSpan.hide();
                    item.quantity = 0;
                    $(this).removeClass('is-invalid is-warning');
                    updateTotalsOnly();
                    return;
                }
                
                // Check if less than 1
                if (quantity < 1) {
                    $(this).addClass('is-invalid');
                    errorSpan.text('Quantity must be at least 1').show();
                    item.quantity = 0;
                    updateTotalsOnly();
                    return;
                }
                
                // Valid quantity
                $(this).addClass('valid-feedback');
                errorSpan.hide();
                item.quantity = quantity;
                
                // Update totals only (no full re-render)
                updateTotalsOnly();
            });

            $(document).on('click', '.remove-stockin-item', function() {
                const index = parseInt($(this).data('index'), 10);
                stockInItems.splice(index, 1);
                renderStockInItems();
            });

            $('#confirm-stockin-btn').on('click', function() {
                const warehouseId = $('#stockin-warehouse-id').val();
                const remark = $('#stockin-remark').val();

                if (!warehouseId) {
                    showStockInMessage('danger', 'Please select a warehouse');
                    return;
                }

                if (stockInItems.length === 0) {
                    showStockInMessage('danger', 'Please scan at least one product batch');
                    return;
                }

                $(this).html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

                $.ajax({
                    url: "{{ route('productBatches.stock-in-bulk') }}",
                    type: "POST",
                    data: {
                        warehouse_id: warehouseId,
                        remark: remark,
                        items: stockInItems.map(function(item) {
                            return {
                                batch_id: item.id,
                                quantity: item.quantity
                            };
                        }),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#batchStockInModal').modal('hide');
                        toastr.success(response.message, 'Success');
                        setTimeout(() => {
                            location.reload();
                        }, 1200);
                    },
                    error: function(xhr) {
                        let message = 'Error processing stock in';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        showStockInMessage('danger', message);
                        $('#confirm-stockin-btn').html('<i class="fa fa-check"></i> Process Stock In').prop('disabled', false);
                    }
                });
            });

            $('#batchStockInModal').on('hidden.bs.modal', function() {
                stockInItems = [];
                stockInBatchCache = {};
                $('#stockin-batch-scan-input').val('');
                $('#stockin-warehouse-id').val('');
                $('#stockin-remark').val('');
                hideStockInMessage();
                renderStockInItems();
                $('#confirm-stockin-btn').html('<i class="fa fa-check"></i> Process Stock In').prop('disabled', true);
            });
        });

        function updateTotalsOnly() {
            let totalUnits = 0;
            stockInItems.forEach(function(item) {
                totalUnits += parseInt(item.quantity, 10) || 0;
            });
            
            $('#stockin-total-batches').text(stockInItems.length);
            $('#stockin-total-units').text(totalUnits);
            $('#confirm-stockin-btn').prop('disabled', stockInItems.length === 0);
        }

        function initCameraAndScanner() {
            stopCamera();
            stopScanner();

            navigator.mediaDevices.enumerateDevices().then(devices => {
                const cameras = devices.filter(d => d.kind === 'videoinput');
                const select = $('#camera-select').empty();

                if (cameras.length === 0) {
                    showScanResult('❌ No camera found on this device', 'danger');
                    return;
                }

                cameras.forEach((cam, i) => {
                    select.append(`<option value="${cam.deviceId}">${cam.label || 'Camera ' + (i+1)}</option>`);
                });

                startCamera(cameras[0].deviceId);
            }).catch(err => {
                console.error('Error enumerating devices:', err);
                showScanResult('❌ Error accessing cameras: ' + err.message, 'danger');
            });
        }

        function startCamera(deviceId) {
            stopCamera();

            navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: deviceId ? { exact: deviceId } : undefined,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            }).then(stream => {
                activeStream = stream;
                const video = document.getElementById('barcode-video');
                video.srcObject = stream;
                
                video.onloadedmetadata = function() {
                    video.play();
                    startZXingScanner();
                };
            }).catch(err => {
                console.error('Camera error:', err);
                showScanResult('❌ Camera access denied: ' + err.message, 'danger');
            });
        }

        function stopCamera() {
            if (activeStream) {
                activeStream.getTracks().forEach(t => t.stop());
                activeStream = null;
            }
        }

        function startZXingScanner() {
            if (isScanning) {
                return;
            }

            showScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning for barcode...', 'info');

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
                
                codeReader = new ZXing.BrowserMultiFormatReader(hints);
                
                const videoElement = document.getElementById('barcode-video');
                
                codeReader.decodeFromVideoDevice(undefined, videoElement, (result, err) => {
                    if (result) {
                        const text = result.getText();

                        if (text && text.length >= 4) {
                            handleSuccessfulScan(text);
                        }
                    }
                    
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error('Scanning error:', err);
                    }
                });
                
                isScanning = true;
                
            } catch (error) {
                console.error('Error starting scanner:', error);
                showScanResult('❌ Failed to start scanner: ' + error.message, 'danger');
            }
        }

        function scanUploadedImage() {
            const file = $('#barcode-image-upload')[0].files[0];
            if (!file) {
                showScanResult('Please select an image first', 'warning');
                return;
            }

            showScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning uploaded image...', 'info');

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
                                handleSuccessfulScan(text);
                            } else {
                                showScanResult('❌ No valid barcode found in image', 'danger');
                            }
                        }).catch(error => {
                            console.error('Image decode error:', error);
                            showScanResult('❌ Failed to decode barcode from image', 'danger');
                        });
                    } catch (error) {
                        console.error('Error:', error);
                        showScanResult('❌ Error processing image: ' + error.message, 'danger');
                    }
                };
            };
            reader.readAsDataURL(file);
        }

        function showScanResult(message, type) {
            $('#scan-result')
                .removeClass('alert-success alert-info alert-danger alert-warning')
                .addClass('alert-' + type)
                .html(message)
                .show();
        }

        function stopScanner() {
            if (codeReader && isScanning) {
                try {
                    codeReader.reset();
                    codeReader = null;
                    isScanning = false;
                    console.log('Scanner stopped');
                } catch (error) {
                    console.error('Error stopping scanner:', error);
                }
            }
        }

        function resetScannerModal() {
            $('#scan-result').hide().removeClass().html('');
            $('#image-preview-container').hide();
            $('#barcode-image-upload').val('');
            $('#barcode-image-upload').next('.custom-file-label').html('Choose file...');
            $('#scan-upload-btn').prop('disabled', true);
        }

        function handleSuccessfulScan(code) {
            stopScanner();
            stopCamera();
            showScanResult('✅ Barcode scanned successfully!<br>Code: <b>' + code + '</b><br><i class="fa fa-spinner fa-spin"></i> Searching...', 'success');

            if (navigator.vibrate) {
                navigator.vibrate(200);
            }

            lookupBatchAndAdd(code, true);
        }

        function lookupBatchAndAdd(batchCode, fromScanner = false) {
            if (!batchCode) {
                if (fromScanner) {
                    showScanResult('❌ No batch code to search', 'warning');
                } else {
                    showStockInMessage('danger', 'No batch code to search');
                }
                return;
            }

            const normalizedCode = batchCode.toUpperCase();
            if (stockInBatchCache[normalizedCode]) {
                addBatchToStockIn(stockInBatchCache[normalizedCode]);
                if (fromScanner) {
                    $('#barcodeScannerModal').modal('hide');
                }
                return;
            }

            $.ajax({
                url: "{{ route('productBatches.search-by-code', '') }}/" + encodeURIComponent(batchCode),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        stockInBatchCache[normalizedCode] = response;
                        addBatchToStockIn(response);

                        if (fromScanner) {
                            $('#barcodeScannerModal').modal('hide');
                        }
                    } else {
                        if (fromScanner) {
                            showScanResult('❌ ' + response.message, 'warning');
                        } else {
                            showStockInMessage('danger', response.message);
                        }
                    }
                },
                error: function(xhr) {
                    let message = 'Error searching for batch';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        message = 'Batch not found in database';
                    }

                    if (fromScanner) {
                        showScanResult('❌ ' + message, 'danger');
                    } else {
                        showStockInMessage('danger', message);
                    }
                    console.error('AJAX error:', xhr);
                }
            });
        }

        function addBatchToStockIn(batchData) {
            const existingItem = stockInItems.find(function(item) {
                return parseInt(item.id, 10) === parseInt(batchData.id, 10);
            });

            if (existingItem) {
                existingItem.quantity += 1;
                // Update the existing input value directly
                let $row = $('#stockin-items-table-body').find('tr[data-index="' + stockInItems.indexOf(existingItem) + '"]');
                if ($row.length) {
                    $row.find('.stockin-item-qty').val(existingItem.quantity);
                }
            } else {
                stockInItems.push({
                    id: batchData.id,
                    batch_code: batchData.batch_code,
                    product_name: batchData.product_name,
                    product_code: batchData.product_code,
                    expiry_date: batchData.expiry_date,
                    current_stock: batchData.quantity,
                    quantity: 1
                });
            }

            renderStockInItems(); // This will now only create missing rows
            showStockInMessage('success', 'Added batch <strong>' + escapeHtml(batchData.batch_code) + '</strong> to stock in list.');
            $('#stockin-batch-scan-input').trigger('focus');
        }

        function renderStockInItems() {
            const tbody = $('#stockin-items-table-body');
            let totalUnits = 0;

            // If no items, just show empty message
            if (stockInItems.length === 0) {
                tbody.empty();
                tbody.append('<tr id="stockin-empty-row"><td colspan="5" class="text-center text-muted">No scanned batches yet</td></tr>');
                $('#stockin-total-batches').text(0);
                $('#stockin-total-units').text(0);
                $('#confirm-stockin-btn').prop('disabled', true);
                return;
            }

            // Update existing rows instead of rebuilding
            stockInItems.forEach(function(item, index) {
                totalUnits += parseInt(item.quantity, 10) || 0;
                
                // Check if row exists
                let $row = tbody.find('tr[data-index="' + index + '"]');
                
                if ($row.length === 0) {
                    // Create new row if it doesn't exist
                    let quantityClass = item.quantity > 0 ? 'valid-feedback' : '';
                    
                    let row = `
                        <tr data-index="${index}">
                            <td><strong>${escapeHtml(item.batch_code)}</strong><br><small class="text-muted">${escapeHtml(item.product_code || '')}</small></td>
                            <td>${escapeHtml(item.product_name || '')}</td>
                            <td>${escapeHtml(item.expiry_date || '')}</td>
                            <td>
                                <input type="text" value="${item.quantity}" 
                                    class="form-control stockin-item-qty ${quantityClass}" 
                                    data-index="${index}" 
                                    style="width: 100%;">
                                <small class="text-danger stockin-qty-error" id="stockin-qty-error-${index}" style="display: none; font-size: 11px;"></small>
                            </td>
                            <td><button type="button" class="btn btn-danger btn-sm remove-stockin-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>
                        </tr>
                    `;
                    tbody.append(row);
                } else {
                    // Update existing row's quantity value
                    let $qtyInput = $row.find('.stockin-item-qty');
                    if ($qtyInput.val() != item.quantity) {
                        $qtyInput.val(item.quantity);
                    }
                    
                    // Update quantity class
                    let quantityClass = item.quantity > 0 ? 'valid-feedback' : '';
                    $qtyInput.removeClass('valid-feedback is-invalid is-warning').addClass(quantityClass);
                    
                    // Update batch code and product info in case they changed
                    $row.find('td:first').html('<strong>' + escapeHtml(item.batch_code) + '</strong><br><small class="text-muted">' + escapeHtml(item.product_code || '') + '</small>');
                    $row.find('td:eq(1)').text(escapeHtml(item.product_name || ''));
                    $row.find('td:eq(2)').text(escapeHtml(item.expiry_date || ''));
                }
            });
            
            // Remove rows that no longer exist
            tbody.find('tr[data-index]').each(function() {
                let index = parseInt($(this).data('index'));
                if (!stockInItems[index]) {
                    $(this).remove();
                }
            });

            $('#stockin-total-batches').text(stockInItems.length);
            $('#stockin-total-units').text(totalUnits);
            $('#confirm-stockin-btn').prop('disabled', stockInItems.length === 0);
        }

        function showStockInMessage(type, message) {
            $('#stockin-scan-message')
                .removeClass('d-none alert-success alert-info alert-danger alert-warning')
                .addClass('alert-' + type)
                .html(message);
        }

        function hideStockInMessage() {
            $('#stockin-scan-message')
                .addClass('d-none')
                .removeClass('alert-success alert-info alert-danger alert-warning')
                .html('');
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            } 
        });
        

    </script>
    <style>
        /* Force quantity inputs to be visible */
        .stockin-item-qty {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            min-width: 100px;
        }

        #stockin-items-table-body input {
            display: block !important;
            visibility: visible !important;
        }

        #stockin-items-table-body td {
            vertical-align: middle;
        }

        #stockin-items-table-body td:nth-child(4) {
            min-width: 150px;
        }

    </style>
@endpush
