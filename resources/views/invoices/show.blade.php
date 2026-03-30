@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoices.index') }}">{{ __('invoices.invoices') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoices.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                                  <a href="{{ route('invoices.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('invoices.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('invoices.invoice_detail') }}</strong>

                                    <div class="pull-right">
                                        <a href="{{ route('invoices.detail', Crypt::encrypt($id)) }}" >Add Item <i class="fa fa-plus-square fa-lg"></i></a>
                                        <button class="border-0 bg-transparent text-primary" data-toggle="modal" data-target="#addInvoiceItemModal">
                                            Scan Barcode <i class="fa fa-barcode fa-lg"></i>
                                        </button>
                                    </div>
                             </div>
                             <div class="card-body">
                                <table class="table table-striped table-bordered dataTable" width="100%" role="grid" style="width: 100%;">
                                    <thead>
                                        <tr role="row">
                                            <th>{{ __('invoices.product') }}</th>
                                            <th>{{ __('Batch Code') }}</th>
                                            <th>{{ __('invoices.quantity') }}</th>
                                            <th>{{ __('invoices.price') }}</th>
                                            <th>{{ __('invoices.total_price') }}</th>
                                            <th>{{ __('invoices.action') }}</th>
                                         </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($invoicedetails) == 0)
                                            <tr class="odd">
                                                <td valign="top" colspan="10" class="dataTables_empty">No matching records found</td>
                                            </tr>
                                        @endif
                                        @foreach($invoicedetails as $i=>$invoicedetail)
                                            @if( ($i+1) % 2 == 0 )
                                                <tr class="even">
                                                    <td>{{ $invoicedetail['product']['name'] }}</td>
                                                    <td>{{ $invoicedetail['batch']['batch_code'] }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ number_format($invoicedetail['price'], 2) }}</td>
                                                    <td>{{ number_format($invoicedetail['totalprice'], 2) }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr class="odd">
                                                    <td>{{ $invoicedetail['product']['name'] }}</td>
                                                    <td>{{ $invoicedetail['batch']['batch_code'] }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ number_format($invoicedetail['price'], 2) }}</td>
                                                    <td>{{ number_format($invoicedetail['totalprice'], 2) }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>

    <!-- Add Invoice Item Modal -->
    <div id="addInvoiceItemModal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title h6">Add Invoice Item</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => ['invoices.adddetail', Crypt::encrypt($id)], 'id' => 'addInvoiceItemForm']) !!}
                        @csrf
                        
                        <!-- Invoice Id Field -->
                        <div class="form-group">
                            {!! Form::label('invoice_id', __('invoice_details.invoice')) !!}<span class="asterisk"> *</span>
                            {!! Form::select('invoice_id', $invoiceItems, $id, ['class' => 'form-control', 'placeholder' => 'Pick a Invoice...', 'disabled']) !!}
                        </div>

                        <!-- Warehouse Selection Field -->
                        <div class="form-group">
                            {!! Form::label('warehouse_id', 'Select Warehouse') !!}<span class="asterisk"> *</span>
                            {!! Form::select('warehouse_id', $warehouseItems, null, ['class' => 'form-control select2', 'placeholder' => 'Select Warehouse...', 'id' => 'modal_warehouse_id']) !!}
                        </div>

                        <!-- Scan Barcode Section -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    {!! Form::label('scan_input', 'Scan Barcode') !!}<span class="asterisk"> *</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="fa fa-barcode"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="modal_scan_input" placeholder="Scan barcode and press Enter" autocomplete="off" disabled>
                                    </div>
                                    <small class="text-muted" id="modalScanInfo">Select warehouse first, then scan product batches.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="col-form-label d-block">&nbsp;</label>
                                    <button type="button" class="btn btn-info btn-block" id="openModalCameraBtn" disabled>
                                        <i class="fa fa-camera"></i> Scan Barcode
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Batch Information Display -->
                        <div id="modalBatchInfo" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Batch Details:</strong>
                                <div id="modalBatchDetails"></div>
                            </div>
                        </div>

                        <!-- Quantity Field -->
                        <div class="form-group" id="modalQuantityGroup" style="display: none;">
                            {!! Form::label('quantity', __('invoice_details.quantity')) !!}<span class="asterisk"> *</span>
                            {!! Form::number('quantity', null, ['class' => 'form-control', 'min' => 1, 'step' => 1, 'id' => 'modal_quantity', 'placeholder' => 'Enter quantity']) !!}
                            <small class="text-muted" id="modalAvailableStock"></small>
                        </div>

                        <!-- Price Field -->
                        <div class="form-group" id="modalPriceGroup" style="display: none;">
                            {!! Form::label('price', __('invoice_details.price')) !!}<span class="asterisk"> *</span>
                            {!! Form::text('price', null, ['class' => 'form-control', 'id' => 'modal_price', 'readonly']) !!}
                        </div>

                        <!-- Remark Field -->
                        <div class="form-group">
                            {!! Form::label('remark', __('invoice_details.remark')) !!}
                            {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255, 'id' => 'modal_remark']) !!}
                        </div>
                        
                        <div class="form-group text-right mt-3">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="button" class="btn btn-info" id="modalSubmitBtn" disabled>Add Item</button>
                        </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Modal -->
    <div class="modal fade" id="invoiceScannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-camera"></i> Scan Barcode
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#invoice-camera-pane" role="tab">
                                <i class="fa fa-camera"></i> Camera Scan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#invoice-upload-pane" role="tab">
                                <i class="fa fa-upload"></i> Upload Image
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="invoice-camera-pane" role="tabpanel">
                            <div class="form-group">
                                <label>Select Camera</label>
                                <select id="invoice-camera-select" class="form-control"></select>
                            </div>
                            <div style="background:#000;border-radius:8px; position:relative;">
                                <video id="invoice-barcode-video" autoplay playsinline style="width:100%"></video>
                                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:80%; height:100px; border:3px solid rgba(255,0,0,0.5); border-radius:10px; pointer-events:none;"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="invoice-upload-pane" role="tabpanel">
                            <div class="form-group">
                                <label>Upload Barcode Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="invoice-barcode-image-upload" accept="image/*">
                                    <label class="custom-file-label" for="invoice-barcode-image-upload">Choose file...</label>
                                </div>
                            </div>
                            <div id="invoice-image-preview-container" style="display:none; text-align:center; margin-top:15px;">
                                <img id="invoice-image-preview" style="max-width:100%; max-height:300px; border:1px solid #ddd; border-radius:4px; padding:5px;">
                            </div>
                            <button type="button" class="btn btn-primary mt-3" id="invoice-scan-upload-btn" disabled>
                                <i class="fa fa-search"></i> Scan Uploaded Image
                            </button>
                        </div>
                    </div>

                    <div id="invoice-scan-result" class="alert mt-3" style="display:none"></div>
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
        .bg-info {
            background-color: #17a2b8 !important;
        }
        .text-white {
            color: #fff !important;
        }
        .asterisk {
            color: red;
            margin-left: 3px;
        }
    </style>

    <script>
        $(document).ready(function () {
            let warehouseBatches = {};
            let currentScannedBatch = null;
            let invoiceCodeReader = null;
            let invoiceIsScanning = false;
            let invoiceActiveStream = null;
            
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#addInvoiceItemModal')
            });
            
            // Warehouse selection change
            $('#modal_warehouse_id').on('change', function() {
                var warehouseId = $(this).val();
                
                if (warehouseId) {
                    // Load batches from selected warehouse
                    $.ajax({
                        url: '{{ url("warehouses") }}/' + warehouseId + '/all-batches',
                        type: 'GET',
                        success: function(response) {
                            if (response.success && response.batches && response.batches.length > 0) {
                                warehouseBatches = {};
                                $.each(response.batches, function(index, batch) {
                                    if (batch.quantity > 0) {
                                        warehouseBatches[(batch.batch_code || '').toUpperCase()] = batch;
                                    }
                                });
                                $('#modalScanInfo').text('Warehouse loaded. Scan product batches to add.');
                                $('#modal_scan_input').prop('disabled', false);
                                $('#openModalCameraBtn').prop('disabled', false);
                            } else {
                                $('#modalScanInfo').text('No available batches in this warehouse');
                                $('#modal_scan_input').prop('disabled', true);
                                $('#openModalCameraBtn').prop('disabled', true);
                            }
                            resetModalForm();
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            $('#modalScanInfo').text('Error loading batches');
                        }
                    });
                } else {
                    warehouseBatches = {};
                    $('#modalScanInfo').text('Select warehouse first, then scan product batches.');
                    $('#modal_scan_input').prop('disabled', true);
                    $('#openModalCameraBtn').prop('disabled', true);
                    resetModalForm();
                }
            });
            
            // Scan input handler
            $('#modal_scan_input').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var code = $(this).val().trim();
                    $(this).val('');
                    if (code) {
                        processScannedBatch(code);
                    }
                }
            });
            
            // Process scanned batch
            function processScannedBatch(rawCode) {
                var code = (rawCode || '').trim().toUpperCase();
                if (!code) {
                    return;
                }
                
                if (!$('#modal_warehouse_id').val()) {
                    $('#modalScanInfo').text('Please select warehouse first');
                    return;
                }
                
                var batch = warehouseBatches[code];
                if (!batch) {
                    $('#modalScanInfo').text('Batch code not found in selected warehouse: ' + rawCode);
                    resetModalForm();
                    return;
                }
                
                currentScannedBatch = batch;
                
                // Get price for this product
                var invoice_id = $('#invoice_id').val();
                var product_id = batch.product_id;
                
                $.ajax({
                    url: '{{ config("app.url") }}/invoiceDetails/getprice/' + invoice_id + '/' + product_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        var price = data.status ? data.data : 0;
                        
                        // Display batch information
                        $('#modalBatchDetails').html(`
                            <strong>Product:</strong> ${batch.product_name}<br>
                            <strong>Batch Code:</strong> ${batch.batch_code}<br>
                            <strong>Expiry Date:</strong> ${batch.expiry_date}<br>
                            <strong>Available Stock:</strong> ${batch.quantity} units<br>
                            <strong>Price:</strong> ${formatCurrency(price)}
                        `);
                        $('#modalBatchInfo').show();
                        $('#modalPriceGroup').show();
                        $('#modal_price').val(price);
                        $('#modalQuantityGroup').show();
                        $('#modal_quantity').attr('max', batch.quantity);
                        $('#modalAvailableStock').text('Available stock: ' + batch.quantity + ' units');
                        $('#modal_quantity').val(1);
                        $('#modalSubmitBtn').prop('disabled', false);
                        
                        $('#modalScanInfo').text('Batch ' + batch.batch_code + ' loaded. Enter quantity.');
                    },
                    error: function(xhr, status, error) {
                        console.log('Error getting price:', error);
                        $('#modalScanInfo').text('Error loading price information');
                    }
                });
            }
            
            // Quantity validation
            $('#modal_quantity').on('input', function() {
                var quantity = parseInt($(this).val(), 10);
                var maxQuantity = parseInt($(this).attr('max'), 10);
                var isValid = quantity && quantity > 0 && quantity <= maxQuantity;
                
                $('#modalSubmitBtn').prop('disabled', !isValid);
                
                if (quantity && quantity > maxQuantity) {
                    $('#modalAvailableStock').html('<span class="text-danger">Quantity cannot exceed available stock (' + maxQuantity + ' units)</span>');
                } else if (quantity) {
                    $('#modalAvailableStock').html('<span class="text-success">Valid quantity</span>');
                }
            });
            
            // Form submission
            $('#addInvoiceItemForm').on('submit', function(e) {
                if (!currentScannedBatch) {
                    e.preventDefault();
                    alert('Please scan a batch first');
                    return false;
                }
                
                var quantity = parseInt($('#modal_quantity').val(), 10);
                if (!quantity || quantity < 1 || quantity > currentScannedBatch.quantity) {
                    e.preventDefault();
                    alert('Please enter a valid quantity');
                    return false;
                }
                
                // Add hidden fields for batch and product
                var form = $(this);
                form.append('<input type="hidden" name="product_batch_id" value="' + currentScannedBatch.batch_id + '">');
                form.append('<input type="hidden" name="product_id" value="' + currentScannedBatch.product_id + '">');
            });
            
            // Reset modal form
            function resetModalForm() {
                currentScannedBatch = null;
                $('#modalBatchInfo').hide();
                $('#modalQuantityGroup').hide();
                $('#modalPriceGroup').hide();
                $('#modal_quantity').val('');
                $('#modal_price').val('');
                $('#modal_remark').val('');
                $('#modalSubmitBtn').prop('disabled', true);
            }
            
            // Camera scanning functionality
            $('#openModalCameraBtn').on('click', function() {
                $('#invoiceScannerModal').modal('show');
            });
            
            $('#invoiceScannerModal').on('shown.bs.modal', function() {
                $(this).css('z-index', 1060);
                $('.modal-backdrop').last().css('z-index', 1050);
                initInvoiceCameraAndScanner();
            });
            
            $('#invoiceScannerModal').on('hidden.bs.modal', function() {
                stopInvoiceScanner();
                stopInvoiceCamera();
                resetInvoiceScannerModal();
                $(this).css('z-index', '');
                $('body').addClass('modal-open');
                setTimeout(() => $('#modal_scan_input').trigger('focus'), 150);
            });
            
            $('#invoice-camera-select').on('change', function () {
                const deviceId = this.value;
                if (deviceId) {
                    stopInvoiceScanner();
                    stopInvoiceCamera();
                    setTimeout(() => startInvoiceCamera(deviceId), 500);
                }
            });
            
            $('#invoice-barcode-image-upload').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    $(this).next('.custom-file-label').html(file.name);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#invoice-image-preview').attr('src', e.target.result);
                        $('#invoice-image-preview-container').show();
                        $('#invoice-scan-upload-btn').prop('disabled', false);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $(this).next('.custom-file-label').html('Choose file...');
                    $('#invoice-image-preview-container').hide();
                    $('#invoice-scan-upload-btn').prop('disabled', true);
                }
            });
            
            $('#invoice-scan-upload-btn').on('click', function() {
                scanInvoiceUploadedImage();
            });
            
            function initInvoiceCameraAndScanner() {
                stopInvoiceCamera();
                stopInvoiceScanner();
                
                navigator.mediaDevices.enumerateDevices().then(devices => {
                    const cameras = devices.filter(d => d.kind === 'videoinput');
                    const select = $('#invoice-camera-select').empty();
                    
                    if (cameras.length === 0) {
                        showInvoiceScanResult('No camera found on this device', 'danger');
                        return;
                    }
                    
                    cameras.forEach((cam, i) => {
                        select.append(`<option value="${cam.deviceId}">${cam.label || 'Camera ' + (i+1)}</option>`);
                    });
                    
                    startInvoiceCamera(cameras[0].deviceId);
                }).catch(err => {
                    console.error('Error enumerating devices:', err);
                    showInvoiceScanResult('Error accessing cameras: ' + err.message, 'danger');
                });
            }
            
            function startInvoiceCamera(deviceId) {
                stopInvoiceCamera();
                
                navigator.mediaDevices.getUserMedia({
                    video: {
                        deviceId: deviceId ? { exact: deviceId } : undefined,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                }).then(stream => {
                    invoiceActiveStream = stream;
                    const video = document.getElementById('invoice-barcode-video');
                    video.srcObject = stream;
                    video.onloadedmetadata = function() {
                        video.play();
                        startInvoiceZXingScanner();
                    };
                }).catch(err => {
                    showInvoiceScanResult('Camera access denied: ' + err.message, 'danger');
                });
            }
            
            function startInvoiceZXingScanner() {
                if (invoiceIsScanning) {
                    return;
                }
                
                showInvoiceScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning for barcode...', 'info');
                
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
                    
                    invoiceCodeReader = new ZXing.BrowserMultiFormatReader(hints);
                    const videoElement = document.getElementById('invoice-barcode-video');
                    
                    invoiceCodeReader.decodeFromVideoDevice(undefined, videoElement, (result, err) => {
                        if (result) {
                            const text = result.getText();
                            if (text && text.length >= 4) {
                                stopInvoiceScanner();
                                stopInvoiceCamera();
                                showInvoiceScanResult('Barcode scanned successfully!<br>Code: <b>' + text + '</b>', 'success');
                                if (navigator.vibrate) {
                                    navigator.vibrate(200);
                                }
                                processScannedBatch(text);
                                $('#invoiceScannerModal').modal('hide');
                            }
                        }
                        
                        if (err && !(err instanceof ZXing.NotFoundException)) {
                            console.error('Scanning error:', err);
                        }
                    });
                    
                    invoiceIsScanning = true;
                } catch (error) {
                    showInvoiceScanResult('Failed to start scanner: ' + error.message, 'danger');
                }
            }
            
            function scanInvoiceUploadedImage() {
                const file = $('#invoice-barcode-image-upload')[0].files[0];
                if (!file) {
                    showInvoiceScanResult('Please select an image first', 'warning');
                    return;
                }
                
                showInvoiceScanResult('<i class="fa fa-spinner fa-spin"></i> Scanning uploaded image...', 'info');
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
                                    processScannedBatch(text);
                                    $('#invoiceScannerModal').modal('hide');
                                } else {
                                    showInvoiceScanResult('No valid barcode found in image', 'danger');
                                }
                            }).catch(error => {
                                showInvoiceScanResult('Failed to decode barcode from image', 'danger');
                                console.error(error);
                            });
                        } catch (error) {
                            showInvoiceScanResult('Error processing image: ' + error.message, 'danger');
                        }
                    };
                };
                reader.readAsDataURL(file);
            }
            
            function stopInvoiceScanner() {
                if (invoiceCodeReader && invoiceIsScanning) {
                    try {
                        invoiceCodeReader.reset();
                    } catch (error) {
                        console.error('Error stopping scanner:', error);
                    }
                }
                invoiceCodeReader = null;
                invoiceIsScanning = false;
            }
            
            function stopInvoiceCamera() {
                if (invoiceActiveStream) {
                    invoiceActiveStream.getTracks().forEach(track => track.stop());
                    invoiceActiveStream = null;
                }
            }
            
            function resetInvoiceScannerModal() {
                $('#invoice-scan-result').hide().removeClass().html('');
                $('#invoice-image-preview-container').hide();
                $('#invoice-barcode-image-upload').val('');
                $('#invoice-barcode-image-upload').next('.custom-file-label').html('Choose file...');
                $('#invoice-scan-upload-btn').prop('disabled', true);
            }
            
            function showInvoiceScanResult(message, type) {
                $('#invoice-scan-result')
                    .removeClass('alert-success alert-info alert-danger alert-warning')
                    .addClass('alert-' + type)
                    .html(message)
                    .show();
            }
            
            function formatCurrency(value) {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                }).format(value);
            }
            
            // Modal reset when closed
            $('#addInvoiceItemModal').on('hidden.bs.modal', function() {
                resetModalForm();
                $('#modal_warehouse_id').val('').trigger('change');
                $('#modal_scan_input').val('');
                $('#modal_remark').val('');
            });
            
            // Keyboard shortcut
            $(document).keyup(function(e) {
                if(e.altKey && e.keyCode == 78){
                    $('#addInvoiceItemModal').modal('show');
                }
            });
        });
    </script>
@endpush