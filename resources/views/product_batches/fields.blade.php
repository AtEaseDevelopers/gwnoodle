@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Product Batch</h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {!! Form::open([
                'route' => 'productBatches.store',
                'method' => 'POST',
                'id' => 'productBatchForm'
            ]) !!}

            <div class="row">
                <!-- Left Column - Generate Barcode -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-qrcode"></i> Step 1: Generate Barcode
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Product Selection -->
                            <div class="form-group">
                                <label for="barcode_product_id">Select Product <span class="text-danger">*</span></label>
                                <select class="form-control select2-barcode" id="barcode_product_id" style="width: 100%;" required>
                                    <option value="">-- Select Product --</option>
                                    @foreach(App\Models\Product::where('status', 1)->orderBy('name')->get() as $product)
                                        <option value="{{ $product->id }}" data-unitcode="{{ $product->unit_code }}">
                                            {{ $product->name }} ({{ $product->unit_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Group -->
                            <div class="form-group">
                                <label for="barcode_group">Group <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="barcode_group" 
                                       placeholder="Enter group letter (e.g. A, B, C, D)" 
                                       maxlength="1" style="text-transform: uppercase;" required>
                                <small class="text-muted">Single letter group identifier</small>
                            </div>

                            <!-- Unit Code (Auto-filled) -->
                            <div class="form-group">
                                <label for="barcode_product_code">Unit Code</label>
                                <input type="text" class="form-control" id="barcode_product_code" readonly 
                                       placeholder="Will auto-fill from selected product">
                            </div>

                            <!-- Auto Info Display -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>User ID</label>
                                        <input type="text" class="form-control" value="{{ str_pad(Auth::id(), 2, '0', STR_PAD_LEFT) }}" readonly disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date Info</label>
                                        <input type="text" class="form-control" 
                                               value="{{ date('y') . '8' . date('d') . date('m') }}" readonly disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- Generate Button -->
                            <div class="form-group text-center">
                                <button type="button" class="btn btn-primary btn-lg" id="generate_barcode_btn" disabled>
                                    <i class="fa fa-qrcode"></i> Generate Barcode
                                </button>
                            </div>

                            <!-- Generated Barcode Preview -->
                            <div id="barcode_preview_section" style="display: none;" class="mt-3">
                                <hr>
                                <h6>Generated Barcode Preview:</h6>
                                <div class="text-center" id="barcode_preview_container">
                                    <div id="barcode_image_placeholder" class="text-muted py-3">
                                        <i class="fa fa-barcode fa-3x"></i>
                                    </div>
                                    <div id="barcode_image_result" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Batch Details -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-cube"></i> Step 2: Batch Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Generated Batch Code -->
                            <div class="form-group">
                                <label>Generated Batch Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="generated_batch_code_display" readonly 
                                           placeholder="Generate barcode first">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="copy_batch_code" title="Copy to clipboard">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="batch_code" id="batch_code_hidden">
                            <input type="hidden" name="product_id" id="product_id_hidden">

                            <!-- Product Display -->
                            <div class="form-group">
                                <label>Product</label>
                                <input type="text" class="form-control" id="product_display" readonly 
                                       placeholder="Product will appear here" style="background-color: #f8f9fa;">
                            </div>

                            <!-- Expiry Date -->
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="expiry_date" id="expiry_date" required disabled>
                            </div>

                            <!-- Quantity -->
                            <div class="form-group">
                                <label for="quantity">Initial Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantity" id="quantity" 
                                       min="1" required disabled>
                            </div>

                            <!-- Status (Hidden - set to active) -->
                            <input type="hidden" name="status" value="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                        <i class="fa fa-save"></i> Create Product Batch
                    </button>
                    <a href="{{ route('productBatches.index') }}" class="btn btn-secondary btn-lg">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .card-header {
        font-weight: bold;
    }
    #barcode_preview_container {
        min-height: 100px;
        border: 1px dashed #ccc;
        border-radius: 4px;
        padding: 10px;
    }
    #barcode_image_result img {
        max-width: 100%;
        height: auto;
    }
</style>

<script>
$(function () {
    HideLoad();

    // Initialize Select2
    $('.select2-barcode').select2({
        placeholder: '-- Select Product --',
        width: '100%'
    });

    // Auto-fill unit code when product is selected
    $('#barcode_product_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var unitCode = selectedOption.data('unitcode') || '';
        $('#barcode_product_code').val(unitCode);
        checkGenerateButton();
    });

    // Validate group input
    $('#barcode_group').on('input', function() {
        $(this).val($(this).val().toUpperCase().replace(/[^A-Z]/g, ''));
        checkGenerateButton();
    });

    // Check if generate button should be enabled
    function checkGenerateButton() {
        var productId = $('#barcode_product_id').val();
        var group = $('#barcode_group').val();
        var productCode = $('#barcode_product_code').val();
        
        if (productId && group && group.length === 1 && productCode) {
            $('#generate_barcode_btn').prop('disabled', false);
        } else {
            $('#generate_barcode_btn').prop('disabled', true);
        }
    }

    // Generate barcode
    $('#generate_barcode_btn').click(function() {
        var productId = $('#barcode_product_id').val();
        var group = $('#barcode_group').val();
        var productCode = $('#barcode_product_code').val();
        
        if (!productId || !group || group.length !== 1 || !productCode) {
            alert('Please select a product and enter a group letter');
            return;
        }
        
        ShowLoad();
        
        $.ajax({
            url: "{{ route('productBatches.generate-barcode-preview') }}",
            type: "POST",
            data: {
                product_id: productId,
                group: group,
                product_code: productCode,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                HideLoad();
                
                if (response.success) {
                    // Show preview section
                    $('#barcode_preview_section').show();
                    
                    // Display barcode image
                    $('#barcode_image_placeholder').hide();
                    $('#barcode_image_result').html(
                        '<img src="data:image/png;base64,' + response.barcode_image + 
                        '" class="img-fluid">'
                    ).show();
                    
                    // Set generated batch code
                    $('#generated_batch_code_display').val(response.batch_code);
                    $('#batch_code_hidden').val(response.batch_code);
                    
                    // Set product info
                    $('#product_id_hidden').val(productId);
                    $('#product_display').val(response.product_name + ' (' + productCode + ')');
                    
                    // Enable batch details fields
                    $('#expiry_date, #quantity').prop('disabled', false);
                    $('#submitBtn').prop('disabled', false);
                    
                    // Show success message
                    toastr.success('Barcode generated successfully!', 'Success');
                }
            },
            error: function(error) {
                HideLoad();
                var errorMessage = 'Error generating barcode';
                if (error.responseJSON?.errors) {
                    errorMessage = Object.values(error.responseJSON.errors).join('\n');
                } else if (error.responseJSON?.message) {
                    errorMessage = error.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Copy batch code to clipboard
    $('#copy_batch_code').click(function() {
        var batchCode = $('#generated_batch_code_display').val();
        if (!batchCode || batchCode === 'Generate barcode first') {
            alert('No batch code to copy');
            return;
        }
        
        navigator.clipboard.writeText(batchCode).then(function() {
            var $btn = $('#copy_batch_code');
            var originalHtml = $btn.html();
            $btn.html('<i class="fa fa-check"></i>');
            setTimeout(function() {
                $btn.html(originalHtml);
            }, 1000);
            toastr.success('Batch code copied to clipboard!');
        });
    });

});
</script>
@endpush