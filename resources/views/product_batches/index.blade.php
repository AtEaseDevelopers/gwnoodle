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

<!-- Batch Confirmation Modal -->
<div class="modal fade" id="batchConfirmationModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <!-- Changed to modal-lg for wider modal -->
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2"> <!-- Reduced padding -->
                <h5 class="modal-title">
                    <i class="fa fa-check-circle"></i> Batch Found
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-2"> <!-- Reduced padding -->
                <!-- Batch Details in a more compact format -->
                <div class="alert alert-info py-2 mb-2" id="batch-details">
                    Loading batch details...
                </div>
                
                <form id="batch-quantity-form">
                    @csrf
                    <input type="hidden" id="confirmed_batch_id" name="batch_id">
                    
                    <!-- First Row: Barcode and Quantity -->
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group mb-2">
                                <label for="confirmed_barcode_display" class="mb-1">Scanned Barcode <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fa fa-barcode"></i></span>
                                    </div>
                                    <input type="text" class="form-control bg-light" 
                                           id="confirmed_barcode_display" name="barcode_display" 
                                           readonly placeholder="Scanned barcode will appear here">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group mb-2">
                                <label for="quantity" class="mb-1">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" 
                                       name="quantity" min="0" value="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Row: Warehouse and Transaction Type -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label for="warehouse_id" class="mb-1">Warehouse <span class="text-danger">*</span></label>
                                <select class="form-control" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">Select Warehouse</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label for="transaction_type" class="mb-1">Transaction Type</label>
                                <select class="form-control" id="transaction_type" name="transaction_type">
                                    <option value="stock_in">Stock In (Add)</option>
                                    <option value="stock_out">Stock Out (Remove)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Third Row: Remark -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-2">
                                <label for="remark" class="mb-1">Remark (Optional)</label>
                                <textarea class="form-control" id="remark" name="remark" rows="1" 
                                        placeholder="Enter any notes"></textarea>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="confirmed_batch_code" name="batch_code">
                    <!-- Help text in a single line -->
                    <small class="text-muted d-block mt-1">
                        <i class="fa fa-info-circle"></i> Current stock: <span id="current-stock-display">0</span> units
                    </small>
                </form>
            </div>
            <div class="modal-footer py-2"> <!-- Reduced padding -->
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success btn-sm" id="confirm-batch-btn">
                    <i class="fa fa-check"></i> Process Batch
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
        let scannedBatchCode = null;

        $(document).ready(function() {
            HideLoad();
            
            $(document).on('click', '.btn-scan-barcode', function(e) {
                e.preventDefault(); 
                $('#barcodeScannerModal').modal('show');
            });

            // Initialize scanner when modal is shown
            $('#barcodeScannerModal').on('shown.bs.modal', function() {
                initCameraAndScanner();
            });

            // Clean up when modal is hidden
            $('#barcodeScannerModal').on('hidden.bs.modal', function() {
                stopScanner();
                stopCamera();
                resetScannerModal();
            });

            // Camera change handler
            $('#camera-select').on('change', function () {
                const deviceId = this.value;
                if (deviceId) {
                    stopScanner();
                    stopCamera();
                    setTimeout(() => startCamera(deviceId), 500);
                }
            });

            // Handle file upload
            $('#barcode-image-upload').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Update file input label
                    $(this).next('.custom-file-label').html(file.name);
                    
                    // Show image preview
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

            // Handle scan uploaded image button
            $('#scan-upload-btn').on('click', function() {
                scanUploadedImage();
            });
        });

        function initCameraAndScanner() {
            stopCamera();
            stopScanner();

            // Get available cameras
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

                // Start with first camera
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
                
                // Wait for video to be ready then start scanning
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
                        console.log('Camera scan - Text:', text);
                        
                        // Only accept codes with reasonable length
                        if (text && text.length >= 4) {
                            scannedBatchCode = text;
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
                    // Create canvas from image
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, img.width, img.height);
                    
                    // Convert to data URL
                    const imageData = canvas.toDataURL('image/png');
                    
                    // Use ZXing to decode
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
                            console.log('Image scan - Text:', text);
                            
                            if (text && text.length >= 4) {
                                scannedBatchCode = text;
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

        function searchBatchByCode(batchCode) {
            // and redirect to its show page if found
            console.log('Searching for batch:', batchCode);
                        
            $.ajax({
                url: "{{ route('productBatches.search-by-code', '') }}/" + encodeURIComponent(batchCode),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        handleSuccessfulScan(response.batch_code);
                    } else {
                        showScanResult('❌ Batch not found in database', 'warning');
                    }
                },
                error: function() {
                    showScanResult('❌ Error searching for batch', 'danger');
                }
            });
            
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
            scannedBatchCode = null;
        }

        function handleSuccessfulScan(code) {
            // Stop camera scanning
            stopScanner();
            
            // Stop camera
            stopCamera();

            // Show success message in scanner modal
            showScanResult('✅ Barcode scanned successfully!<br>Code: <b>' + code + '</b><br><i class="fa fa-spinner fa-spin"></i> Searching...', 'success');

            // Vibrate if supported
            if (navigator.vibrate) {
                navigator.vibrate(200);
            }
            
            // Store the scanned code
            $('#last-scanned-barcode').remove();
            $('<input>').attr({
                type: 'hidden',
                id: 'last-scanned-barcode',
                name: 'last_scanned_barcode',
                value: code
            }).appendTo('form');
            
            // Search for the batch using the scanned code
            searchBatchByCode(code);
        }

        function searchBatchByCode(batchCode) {
            console.log('Searching for batch:', batchCode);
            
            if (!batchCode) {
                showScanResult('❌ No batch code to search', 'warning');
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
                        // Close scanner modal
                        $('#barcodeScannerModal').modal('hide');
                        
                        // Populate and show confirmation modal
                        showBatchConfirmationModal(response);
                    } else {
                        showScanResult('❌ ' + response.message, 'warning');
                    }
                },
                error: function(xhr) {
                    let message = 'Error searching for batch';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        message = 'Batch not found in database';
                    }
                    showScanResult('❌ ' + message, 'danger');
                    console.error('AJAX error:', xhr);
                }
            });
        }

        function showBatchConfirmationModal(batchData) {
            // Populate batch details
            const batchDetails = `
                <strong>Batch Code:</strong> ${batchData.batch_code}<br>
                <strong>Product:</strong> ${batchData.product_name}<br>
                <strong>Product Code:</strong> ${batchData.product_code}<br>
                <strong>Expiry Date:</strong> ${batchData.expiry_date}<br>
                <strong>Current Stock:</strong> ${batchData.quantity} units<br>
                <strong>Status:</strong> ${getStatusText(batchData.status)}
            `;
            
            $('#batch-details').html(batchDetails);
            $('#confirmed_batch_id').val(batchData.id);
            $('#confirmed_batch_code').val(batchData.batch_code);

            // Set the scanned barcode in the read-only field
            $('#confirmed_barcode_display').val(batchData.batch_code);
            
            // Populate warehouse dropdown
            const warehouseSelect = $('#warehouse_id');
            warehouseSelect.empty().append('<option value="">Select Warehouse</option>');
            
            if (batchData.warehouses && batchData.warehouses.length > 0) {
                $.each(batchData.warehouses, function(index, warehouse) {
                    warehouseSelect.append(
                        $('<option>', {
                            value: warehouse.id,
                            text: warehouse.name + (warehouse.location ? ' (' + warehouse.location + ')' : '')
                        })
                    );
                });
                warehouseSelect.prop('disabled', false);
            } else {
                warehouseSelect.append('<option value="" disabled>No active warehouses found</option>');
                warehouseSelect.prop('disabled', true);
            }
            
            // Set max quantity for stock out
            $('#quantity').attr('max', batchData.quantity);
            
            // Update transaction type based on current stock
            if (batchData.quantity <= 0) {
                $('#transaction_type').val('stock_in');
                $('#transaction_type option[value="stock_out"]').prop('disabled', true);
            } else {
                $('#transaction_type option[value="stock_out"]').prop('disabled', false);
            }
            
            // Show the modal
            $('#batchConfirmationModal').modal('show');
        }

        function getStatusText(status) {
            switch(status) {
                case 1: return '<span class="badge badge-success">Active</span>';
                case 2: return '<span class="badge badge-danger">Inactive</span>';
                case 3: return '<span class="badge badge-warning">Expired</span>';
                default: return '<span class="badge badge-secondary">Unknown</span>';
            }
        }

        // Handle confirm button click
        $(document).on('click', '#confirm-batch-btn', function() {
            const batchId = $('#confirmed_batch_id').val();
            const batchCode = $('#confirmed_batch_code').val();
            const quantity = $('#quantity').val();
            const warehouseId = $('#warehouse_id').val();
            const transactionType = $('#transaction_type').val();
            const remark = $('#remark').val();
            
            if (!warehouseId) {
                alert('Please select a warehouse');
                return;
            }
            
            if (!quantity || quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }
            
            // Show loading
            $(this).html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            
            // Determine which endpoint to call based on transaction type
            let url = '';
            if (transactionType === 'stock_in') {
                url = "{{ url('productBatches/stock-in') }}/" + batchId;
            } else {
                url = "{{ url('productBatches/stock-out') }}/" + batchId;
            }
            
            // Send AJAX request
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    quantity: quantity,
                    warehouse_id: warehouseId,
                    remark: remark,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#batchConfirmationModal').modal('hide');
                    
                    // Show success message
                    toastr.success(response.message, 'Success');
                    
                    // Reload the page or update the table
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    let message = 'Error processing transaction';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    
                    alert('Error: ' + message);
                    
                    // Reset button
                    $('#confirm-batch-btn').html('<i class="fa fa-check"></i> Process Batch').prop('disabled', false);
                }
            });
        });

        // Reset confirmation modal when hidden
        $('#batchConfirmationModal').on('hidden.bs.modal', function() {
            $('#confirmed_barcode_display').val('');
            $('#quantity').val(1);
            $('#warehouse_id').val('').trigger('change');
            $('#remark').val('');
            $('#transaction_type').val('stock_in');
            $('#confirm-batch-btn').html('<i class="fa fa-check"></i> Process Batch').prop('disabled', false);
        });

        // Validate quantity based on transaction type
        $('#transaction_type').on('change', function() {
            const type = $(this).val();
            const currentStock = parseInt($('#batch-details').text().match(/Current Stock: (\d+)/)?.[1] || 0);
            
            if (type === 'stock_out') {
                $('#quantity').attr('max', currentStock);
                if (parseInt($('#quantity').val()) > currentStock) {
                    $('#quantity').val(currentStock);
                }
            } else {
                $('#quantity').removeAttr('max');
            }
        });

        // Keep your existing functions (mass actions, etc.)
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            } 
        });
        

    </script>
@endpush