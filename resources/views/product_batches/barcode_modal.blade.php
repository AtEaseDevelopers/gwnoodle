@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ isset($productBatch) ? 'Edit' : 'Create' }} Product Batch</h3>
            <button type="button" class="btn btn-success" id="generate_barcode_btn" data-toggle="modal" data-target="#generateBarcodeModal">
                <i class="fa fa-qrcode"></i> Generate
            </button>
        </div>
        <div class="card-body">

            {!! Form::open([
                'route' => isset($productBatch)
                    ? ['productBatches.update', Crypt::encrypt($productBatch->id)]
                    : 'productBatches.store',
                'method' => isset($productBatch) ? 'PUT' : 'POST',
                'id' => 'productBatchForm'
            ]) !!}

            <div class="row">

                <!-- Batch Code (Primary Field) -->
                <div class="form-group col-sm-12">
                    {!! Form::label('batch_code', 'Batch Code') !!} <span class="asterisk">*</span>
                    <div class="input-group">
                        {!! Form::text('batch_code', null, [
                            'class' => 'form-control',
                            'id' => 'batch_code_input',
                            'required',
                            'placeholder' => 'Scan or enter batch code',
                            'autofocus' => true
                        ]) !!}
                        <div class="input-group-append">
                            <button type="button" class="btn btn-info" id="scan_barcode_btn">
                                <i class="fa fa-camera"></i> Scan
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted" id="batchCodeHelp">
                        <i class="fa fa-info-circle"></i> Scan, generate, or enter batch code
                    </small>
                </div>

                <!-- Product (Hidden for auto-fill) -->
                <input type="hidden" name="product_id" id="product_id_hidden">

                <!-- Product Display (Read-only when auto-filled) -->
                <div class="form-group col-sm-6">
                    {!! Form::label('product_display', 'Product') !!} <span class="asterisk">*</span>
                    <div class="input-group">
                        <input type="text" class="form-control" id="product_display" readonly 
                               placeholder="Product will auto-populate from batch code" 
                               style="background-color: #f8f9fa;">
                        <div class="input-group-append">
                            <span class="input-group-text" id="product_status_icon">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Batch Status Display -->
                <div class="form-group col-sm-6" id="batch_status_display" style="display: none;">
                    <div class="alert" id="batch_status_message"></div>
                </div>

                <!-- Expiry Date -->
                <div class="form-group col-sm-6">
                    {!! Form::label('expiry_date', 'Expiry Date') !!} <span class="asterisk">*</span>
                    {!! Form::date('expiry_date', null, [
                        'class' => 'form-control', 
                        'required',
                        'id' => 'expiry_date',
                        'disabled' => true
                    ]) !!}
                </div>

                <!-- Quantity -->
                <div class="form-group col-sm-6">
                    {!! Form::label('quantity', 'Initial Quantity') !!} <span class="asterisk">*</span>
                    {!! Form::number('quantity', null, [
                        'class' => 'form-control', 
                        'min' => 1, 
                        'required',
                        'id' => 'quantity',
                        'disabled' => true
                    ]) !!}
                </div>

            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <i class="fa fa-save"></i> Save
                </button>
                <a href="{{ route('productBatches.index') }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>

<!-- Include Generate Barcode Modal -->
@include('product_batches.barcode_modal')

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
@endsection

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

<script>
$(function () {
    HideLoad();

    // ==================== BATCH CODE AUTO-DETECTION ====================
    let batchCheckTimeout = null;

    $('#batch_code_input').on('input', function() {
        const batchCode = $(this).val().trim();
        
        // Clear previous timeout
        if (batchCheckTimeout) {
            clearTimeout(batchCheckTimeout);
        }

        if (batchCode.length >= 8) {
            $('#batchCodeHelp').html('<i class="fa fa-spinner fa-spin"></i> Checking batch code...');
            
            batchCheckTimeout = setTimeout(function() {
                checkBatchCode(batchCode);
            }, 500);
        } else {
            resetForm();
            $('#batchCodeHelp').html('<i class="fa fa-info-circle"></i> Enter at least 8 characters to check batch');
        }
    });

    function checkBatchCode(batchCode) {
        $.ajax({
            url: '{{ route("productBatches.check-batch") }}',
            type: 'POST',
            data: {
                batch_code: batchCode,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.exists) {
                        // Batch exists - show details
                        $('#product_display').val(response.product.name + ' (' + response.product.unit_code + ')');
                        $('#product_id_hidden').val(response.product.id);
                        
                        // Show batch exists message
                        $('#batch_status_display').show();
                        $('#batch_status_message')
                            .removeClass('alert-success alert-danger')
                            .addClass('alert-warning')
                            .html('<i class="fa fa-exclamation-triangle"></i> This batch already exists! ' +
                                  'Created: ' + response.batch.created_at + ', ' +
                                  'Current Quantity: ' + response.batch.quantity);
                        
                        // Disable expiry and quantity (can't modify existing batch)
                        $('#expiry_date, #quantity').prop('disabled', true);
                        $('#submitBtn').prop('disabled', true);
                        $('#batchCodeHelp').html('<i class="fa fa-exclamation-circle text-warning"></i> Batch exists - cannot create duplicate');
                        
                    } else {
                        // New batch - enable fields
                        $('#product_display').val(response.product.name + ' (' + response.product.unit_code + ')');
                        $('#product_id_hidden').val(response.product.id);
                        
                        $('#batch_status_display').show();
                        $('#batch_status_message')
                            .removeClass('alert-warning alert-danger')
                            .addClass('alert-success')
                            .html('<i class="fa fa-check-circle"></i> New batch - ready to add quantity and expiry');
                        
                        // Enable expiry and quantity for new batch
                        $('#expiry_date, #quantity').prop('disabled', false);
                        $('#submitBtn').prop('disabled', false);
                        $('#batchCodeHelp').html('<i class="fa fa-check-circle text-success"></i> Product: ' + response.product.name);
                    }
                    
                    // Show parsed info if available
                    if (response.parsed) {
                        console.log('Batch parsed:', response.parsed);
                    }
                } else {
                    resetForm();
                    $('#batch_status_display').show();
                    $('#batch_status_message')
                        .removeClass('alert-success alert-warning')
                        .addClass('alert-danger')
                        .html('<i class="fa fa-times-circle"></i> ' + response.message);
                    $('#batchCodeHelp').html('<i class="fa fa-exclamation-circle text-danger"></i> ' + response.message);
                }
            },
            error: function() {
                resetForm();
                $('#batch_status_display').show();
                $('#batch_status_message')
                    .removeClass('alert-success alert-warning')
                    .addClass('alert-danger')
                    .html('<i class="fa fa-times-circle"></i> Error checking batch code');
                $('#batchCodeHelp').html('<i class="fa fa-exclamation-circle text-danger"></i> Error checking batch');
            }
        });
    }

    function resetForm() {
        $('#product_display').val('');
        $('#product_id_hidden').val('');
        $('#batch_status_display').hide();
        $('#expiry_date, #quantity').prop('disabled', true);
        $('#submitBtn').prop('disabled', true);
    }

    // ==================== HANDLE GENERATED BARCODE ====================
    $(document).on('batchCodeGenerated', function(e, batchCode) {
        $('#batch_code_input').val(batchCode).trigger('input');
    });

    // ==================== SCANNER FUNCTIONALITY ====================
     $('.select2-product').select2({ width: '100%' });

    let activeStream = null;
    let codeReader = null;
    let isScanning = false;

    /* Open modal */
    $('#scan_barcode_btn').on('click', function () {
        $('#barcodeScannerModal').modal('show');
    });

    /* Modal shown */
    $('#barcodeScannerModal').on('shown.bs.modal', function () {
        // Only initialize camera if camera tab is active
        if ($('#camera-tab').hasClass('active')) {
            initCameraAndScanner();
        }
    });

    /* Tab change handler */
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('id') === 'camera-tab') {
            // Camera tab selected
            stopScanner();
            $('#scan-result').hide();
            initCameraAndScanner();
        } else {
            // Upload tab selected
            stopCamera();
            stopScanner();
            $('#scan-result').hide();
        }
    });

    /* Modal hidden - stop everything */
    $('#barcodeScannerModal').on('hidden.bs.modal', function () {
        stopCamera();
        stopScanner();
        $('#scan-result').hide().removeClass('alert-success alert-danger alert-info');
        // Reset file input
        $('#barcode-image-upload').val('');
        $('.custom-file-label').html('Choose file...');
        $('#image-preview-container').hide();
        $('#scan-upload-btn').prop('disabled', true);
    });

    // ==================== CAMERA SCANNING ====================
    function initCameraAndScanner() {
        stopCamera();
        stopScanner();

        // Get available cameras
        navigator.mediaDevices.enumerateDevices().then(devices => {
            const cameras = devices.filter(d => d.kind === 'videoinput');
            const select = $('#camera-select').empty();

            if (cameras.length === 0) {
                $('#scan-result')
                    .removeClass('alert-success alert-info')
                    .addClass('alert-danger')
                    .html('❌ No camera found on this device')
                    .show();
                return;
            }

            cameras.forEach((cam, i) => {
                select.append(`<option value="${cam.deviceId}">${cam.label || 'Camera ' + (i+1)}</option>`);
            });

            // Start with first camera
            startCamera(cameras[0].deviceId);
        });

        // Camera change handler
        $('#camera-select').off().on('change', function () {
            const deviceId = this.value;
            if (deviceId) {
                stopScanner();
                startCamera(deviceId);
            }
        });
    }

    function startCamera(deviceId) {
        stopCamera();

        navigator.mediaDevices.getUserMedia({
            video: {
                deviceId: { exact: deviceId },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        }).then(stream => {
            activeStream = stream;
            const video = document.getElementById('barcode-video');
            video.srcObject = stream;
            
            // Wait for video to be ready then start scanning
            video.onloadedmetadata = function() {
                startZXingScanner();
            };
        }).catch(err => {
            console.error('Camera error:', err);
            $('#scan-result')
                .removeClass('alert-success alert-info')
                .addClass('alert-danger')
                .html('❌ Camera access denied: ' + err.message)
                .show();
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

        $('#scan-result')
            .removeClass('alert-success alert-danger')
            .addClass('alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Scanning for barcode...')
            .show();

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
                    const format = result.getBarcodeFormat();
                    
                    console.log('Camera scan - Text:', text, 'Format:', format);
                    
                    // Only accept codes with reasonable length
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
            $('#scan-result')
                .removeClass('alert-info alert-success')
                .addClass('alert-danger')
                .html('❌ Failed to start scanner: ' + error.message)
                .show();
        }
    }

    // ==================== IMAGE UPLOAD SCANNING ====================
    $('#barcode-image-upload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Update file input label
            $('.custom-file-label').html(file.name);
            
            // Show image preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result);
                $('#image-preview-container').show();
                $('#scan-upload-btn').prop('disabled', false);
            }
            reader.readAsDataURL(file);
        } else {
            $('.custom-file-label').html('Choose file...');
            $('#image-preview-container').hide();
            $('#scan-upload-btn').prop('disabled', true);
        }
    });

    $('#scan-upload-btn').on('click', function() {
        const file = $('#barcode-image-upload')[0].files[0];
        if (!file) {
            alert('Please select an image first');
            return;
        }

        $('#scan-result')
            .removeClass('alert-success alert-danger')
            .addClass('alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Scanning uploaded image...')
            .show();

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
                            handleSuccessfulScan(text);
                        } else {
                            $('#scan-result')
                                .removeClass('alert-info')
                                .addClass('alert-danger')
                                .html('❌ No valid barcode found in image')
                                .show();
                        }
                    }).catch(error => {
                        console.error('Image decode error:', error);
                        $('#scan-result')
                            .removeClass('alert-info')
                            .addClass('alert-danger')
                            .html('❌ Failed to decode barcode from image')
                            .show();
                    });
                } catch (error) {
                    console.error('Error:', error);
                    $('#scan-result')
                        .removeClass('alert-info')
                        .addClass('alert-danger')
                        .html('❌ Error processing image: ' + error.message)
                        .show();
                }
            };
        };
        reader.readAsDataURL(file);
    });

    // ==================== COMMON FUNCTIONS ====================
    function handleSuccessfulScan(code) {
        // Stop camera scanning
        stopScanner();
        
        // Stop camera
        stopCamera();
        
        // Set the value
        $('#batch_code_input').val(code);

        // Show success message
        $('#scan-result')
            .removeClass('alert-info alert-danger')
            .addClass('alert-success')
            .html('✅ Scanned: <b style="font-size:1.2em;">' + code + '</b>')
            .show();

        // Vibrate if supported
        if (navigator.vibrate) {
            navigator.vibrate(200);
        }

        // Close modal after short delay
        setTimeout(() => {
            $('#barcodeScannerModal').modal('hide');
        }, 800);
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

    // ==================== GENERATE BARCODE BUTTON ====================
    $('#generate_barcode_btn').on('click', function() {
        // Just opens the modal - no extra action needed
    });

});
</script>
@endpush