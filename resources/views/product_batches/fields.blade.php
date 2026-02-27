@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ isset($productBatch) ? 'Edit' : 'Create' }} Product Batch</h3>
        </div>
        <div class="card-body">
            {!! Form::open(['route' => isset($productBatch) ? ['productBatches.update', Crypt::encrypt($productBatch->id)] : 'productBatches.store', 'method' => isset($productBatch) ? 'PUT' : 'POST']) !!}

            <div class="row">
                <!-- Product Field (Select) -->
                <div class="form-group col-sm-6">
                    {!! Form::label('product_id', 'Product') !!}<span class="asterisk"> *</span>
                    <div class="input-group">
                        {!! Form::select('product_id', $products ?? [], isset($productBatch) ? $productBatch->product_id : null, [
                            'class' => 'form-control select2-product',
                            'id' => 'product_id',
                            'placeholder' => __('Select product...'),
                            'style' => 'width: 100%;',
                            'required'
                        ]) !!}
                    </div>
                    <small class="form-text text-muted">Select the product for this batch</small>
                </div>

                <!-- Batch Code Field -->
                <div class="form-group col-sm-6">
                    {!! Form::label('batch_code', 'Batch Code') !!}<span class="asterisk"> *</span>
                    {!! Form::text('batch_code', null, ['class' => 'form-control', 'maxlength' => 255, 'required', 'placeholder' => 'e.g. BATCH-2024-001']) !!}
                    <small class="form-text text-muted">Unique identifier for this batch</small>
                </div>

                <!-- Manufacturing Date Field -->
                <div class="form-group col-sm-6">
                    {!! Form::label('manufacturing_date', 'Manufacturing Date') !!}
                    {!! Form::date('manufacturing_date', null, ['class' => 'form-control', 'id' => 'manufacturing_date']) !!}
                    <small class="form-text text-muted">When this batch was produced</small>
                </div>

                <!-- Expiry Date Field -->
                <div class="form-group col-sm-6">
                    {!! Form::label('expiry_date', 'Expiry Date') !!}<span class="asterisk"> *</span>
                    {!! Form::date('expiry_date', null, ['class' => 'form-control', 'id' => 'expiry_date', 'required']) !!}
                    <small class="form-text text-muted">When this batch expires</small>
                </div>

                <!-- Quantity Field (Initial Stock) -->
                <div class="form-group col-sm-6">
                    {!! Form::label('quantity', 'Initial Quantity') !!}<span class="asterisk"> *</span>
                    {!! Form::number('quantity', null, ['class' => 'form-control', 'min' => 1, 'step' => 1, 'id' => 'quantity', 'required']) !!}
                    <small class="form-text text-muted">Number of units in this batch</small>
                </div>

                <!-- QR Code Field -->
                <div class="form-group col-sm-6">
                    {!! Form::label('qr_code', 'QR Code') !!}
                    <div class="input-group">
                        {!! Form::text('qr_code', null, ['class' => 'form-control', 'id' => 'qr_code', 'placeholder' => 'Scan or enter QR code']) !!}
                        <div class="input-group-append">
                            <button type="button" class="btn btn-info" id="generate_qr">
                                <i class="fa fa-qrcode"></i> Generate
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Optional: QR code for this batch</small>
                </div>

                <!-- Barcode Data Field -->
                <div class="form-group col-sm-12">
                    {!! Form::label('barcode_data', 'Barcode Data') !!}
                    {!! Form::textarea('barcode_data', null, ['class' => 'form-control', 'rows' => 2, 'id' => 'barcode_data', 'placeholder' => 'Raw barcode scan data if available']) !!}
                    <small class="form-text text-muted">Optional: Store raw barcode data from scanner</small>
                </div>

                <!-- Status Field (for edit only) -->
                @if(isset($productBatch))
                <div class="form-group col-sm-6">
                    {!! Form::label('status', 'Status') !!}
                    {{ Form::select('status', [
                        1 => 'Active',
                        2 => 'Expired',
                        3 => 'Depleted',
                    ], null, ['class' => 'form-control', 'id' => 'status']) }}
                </div>
                @endif

                <!-- Expiry Warning Display (read-only) -->
                <div class="form-group col-sm-6" id="expiry_warning" style="display: none;">
                    <label>Expiry Status</label>
                    <div class="alert alert-warning" id="expiry_message"></div>
                </div>
            </div>

            <!-- Submit Field -->
            <div class="form-group col-sm-12">
                {!! Form::submit(isset($productBatch) ? 'Update' : 'Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('productBatches.index') }}" class="btn btn-secondary">Cancel</a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
   <style>
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
        
        /* Expiry warning styling */
        #expiry_warning {
            transition: all 0.3s ease;
        }
    </style>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for product
            $('.select2-product').select2({
                placeholder: '{{ __("Select product...") }}',
                allowClear: true,
                width: '100%'
            });

            // Calculate days until expiry
            function checkExpiry() {
                var expiryDate = $('#expiry_date').val();
                if (expiryDate) {
                    var today = new Date();
                    var expiry = new Date(expiryDate);
                    var diffTime = expiry - today;
                    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays < 0) {
                        $('#expiry_message').text('⚠️ This batch is already expired!');
                        $('#expiry_warning').show().removeClass('alert-warning').addClass('alert-danger');
                    } else if (diffDays <= 30) {
                        $('#expiry_message').text('⚠️ Warning: This batch expires in ' + diffDays + ' days');
                        $('#expiry_warning').show().removeClass('alert-danger').addClass('alert-warning');
                    } else {
                        $('#expiry_warning').hide();
                    }
                }
            }

            // Check expiry on date change
            $('#expiry_date').on('change', function() {
                checkExpiry();
            });

            // Generate random QR code
            $('#generate_qr').on('click', function() {
                var productId = $('#product_id').val();
                var batchCode = $('#batch_code').val();
                var timestamp = Date.now();
                var random = Math.floor(Math.random() * 1000);
                
                if (batchCode) {
                    var qrCode = 'QR-' + batchCode + '-' + random;
                } else if (productId) {
                    var qrCode = 'QR-PROD-' + productId + '-' + timestamp;
                } else {
                    var qrCode = 'QR-' + timestamp + '-' + random;
                }
                
                $('#qr_code').val(qrCode);
            });

            // Auto-generate batch code suggestion (optional)
            $('#product_id').on('change', function() {
                var productId = $(this).val();
                if (productId && !$('#batch_code').val()) {
                    var productText = $(this).select2('data')[0].text;
                    var productCode = productText.substring(0, 3).toUpperCase();
                    var date = new Date();
                    var year = date.getFullYear();
                    var month = String(date.getMonth() + 1).padStart(2, '0');
                    var day = String(date.getDate()).padStart(2, '0');
                    
                    $('#batch_code').val(productCode + '-' + year + month + day + '-001');
                }
            });

            // Initial check if editing
            checkExpiry();

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