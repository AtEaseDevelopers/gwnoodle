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
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-qrcode"></i> Create Product Batch & Generate Barcode
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Left Column -->
                                <div class="col-md-6">
                                    <!-- Product Selection -->
                                    <div class="form-group">
                                        <label for="barcode_product_id">Select Product <span class="text-danger">*</span></label>
                                        <select class="form-control select2-barcode" id="barcode_product_id" name="product_id" style="width: 100%;" required>
                                            <option value="">-- Select Product --</option>
                                            @foreach(App\Models\Product::where('status', 1)->orderBy('name')->get() as $product)
                                                <option value="{{ $product->id }}" 
                                                    data-unitcode="{{ $product->unit_code }}"
                                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->unit_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Group -->
                                    <div class="form-group">
                                        <label for="barcode_group">Group <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="barcode_group" name="group" 
                                               placeholder="Enter group letter (e.g. A, B, C, D)" 
                                               maxlength="1" style="text-transform: uppercase;" 
                                               value="{{ old('group') }}" required>
                                        <small class="text-muted">Single letter group identifier</small>
                                    </div>

                                    <!-- Unit Code (Auto-filled) -->
                                    <div class="form-group">
                                        <label for="barcode_product_code">Unit Code</label>
                                        <input type="text" class="form-control" id="barcode_product_code" readonly 
                                               placeholder="Will auto-fill from selected product"
                                               value="{{ old('unit_code') }}">
                                        <small class="text-muted">Auto-filled from selected product</small>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <!-- User ID -->
                                    <div class="form-group">
                                        <label for="user_id_input">User ID <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="user_id_input" 
                                               name="user_id" 
                                               maxlength="2" placeholder="Enter 2-digit user ID" 
                                               value="{{ old('user_id') }}" required>
                                        <small class="text-muted">Enter 2-digit user ID (e.g., 01, 02, 10)</small>
                                    </div>

                                    <!-- Expiry Date Selection -->
                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date <span class="text-muted">(Optional)</span></label>
                                        <input type="date" class="form-control" id="expiry_date"
                                               name="expiry_date"
                                               value="{{ old('expiry_date') }}">
                                        <small class="text-muted">Used to auto-build the barcode below. Leave blank if this product doesn't expire - you can still type the batch code manually.</small>
                                    </div>

                                    <!-- Add-on Fields (Optional) -->
                                    <div class="form-group">
                                        <label for="add_on_fields">Add-on Fields <span class="text-muted">(Optional)</span></label>
                                        <input type="text" class="form-control" id="add_on_fields" 
                                               name="add_on_fields" 
                                               placeholder="Enter add-on value (e.g., 001, ABC, EXTRA)" 
                                               value="{{ old('add_on_fields') }}">
                                        <small class="text-muted">Optional: If provided, will append "/value" to the barcode</small>
                                    </div>

                                    <!-- Hidden display fields for JS (not shown to user) -->
                                    <input type="hidden" id="year_display" value="">
                                    <input type="hidden" id="month_display" value="">
                                    <input type="hidden" id="day_display" value="">
                                    <input type="hidden" id="preview_group" value="-">
                                    <input type="hidden" id="user_id_display" value="{{ old('user_id') }}">
                                    <input type="hidden" id="product_code_preview" value="">
                                </div>
                            </div>

                            <!-- Barcode Format Builder -->
                            <div class="card bg-light mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Barcode Format Builder</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Complete Barcode Preview - auto-filled as you type above, but
                                         editable directly if you need to override or type it manually
                                         (e.g. no expiry date given) -->
                                    <div class="alert alert-info mt-3">
                                        <div class="mt-2 p-2 bg-white border rounded">
                                            @php
                                                $initialBatchCode = old('batch_code');
                                                if (!$initialBatchCode && old('group') && old('user_id') && old('expiry_date') && old('product_id')) {
                                                    $product = App\Models\Product::find(old('product_id'));
                                                    $unitCode = $product ? $product->unit_code : '';
                                                    $date = new DateTime(old('expiry_date'));
                                                    $initialBatchCode = old('group') . old('user_id') . $date->format('y') . '8' . $date->format('d') . $date->format('m') . $unitCode;
                                                    if (old('add_on_fields')) {
                                                        $initialBatchCode .= old('add_on_fields');
                                                    }
                                                }
                                            @endphp
                                            <input type="text" id="complete_barcode_preview" name="batch_code"
                                                   class="form-control text-center"
                                                   style="font-size: 1.5em; font-family: monospace; letter-spacing: 2px; font-weight: bold; border: none; background: transparent;"
                                                   placeholder="---"
                                                   value="{{ $initialBatchCode }}"
                                                   autocomplete="off">
                                        </div>
                                        <small class="text-muted d-block mt-2">Format: Group(1) + UserID(2) + Year(2) + Fixed(1) + Day(2) + Month(2) + ProductCode + <span class="text-primary">[Optional: /AddOnFields]</span> - auto-filled above, or type/edit it directly.
                                            <button type="button" id="resetBarcodeBtn" class="btn btn-link btn-sm p-0 align-baseline"><i class="fa fa-refresh"></i> Reset to auto-generated</button>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                        <i class="fa fa-save"></i> Create Product Batch & Generate Barcode
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

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .card-header {
        font-weight: bold;
    }
    #complete_barcode_preview {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        font-weight: bold;
    }
</style>

<script>
$(function () {
    HideLoad();

    // Initialize Select2 with old value
    $('.select2-barcode').select2({
        placeholder: '-- Select Product --',
        width: '100%'
    });

    // Set initial values from old inputs
    function setInitialValues() {
        var oldUserId = "{{ old('user_id') }}";
        var oldGroup = "{{ old('group') }}";
        var oldProductId = "{{ old('product_id') }}";
        var oldAddOnFields = "{{ old('add_on_fields') }}";

        if (oldUserId) {
            $('#user_id_input').val(oldUserId);
            $('#user_id_display').val(oldUserId);
        }

        if (oldGroup) {
            $('#barcode_group').val(oldGroup);
            $('#preview_group').text(oldGroup);
        }

        if (oldProductId) {
            var selectedOption = $('#barcode_product_id').find('option:selected');
            var unitCode = selectedOption.data('unitcode') || '';
            $('#barcode_product_code').val(unitCode);
            $('#product_code_preview').val(unitCode);
        }

        if (oldAddOnFields) {
            $('#add_on_fields').val(oldAddOnFields);
        }

        // Derive the Y/M/D fields + barcode from the (re)populated expiry date.
        syncExpiryToBarcode();
    }

    // Auto-fill unit code and update preview when product is selected
    $('#barcode_product_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var unitCode = selectedOption.data('unitcode') || '';
        $('#barcode_product_code').val(unitCode);
        $('#product_code_preview').val(unitCode);
        updateBarcodePreview();
    });

    // Update group preview and validate
    $('#barcode_group').on('input', function() {
        var group = $(this).val().toUpperCase().replace(/[^A-Z]/g, '');
        $(this).val(group);
        $('#preview_group').text(group || '-');
        updateBarcodePreview();
    });

    // Validate user ID input
    $('#user_id_input').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, '').substring(0, 2));
        $('#user_id_display').val($(this).val());
        updateBarcodePreview();
    });

    // Sync the hidden Y/M/D fields (which drive the barcode) from the expiry
    // date. Parsed from the raw YYYY-MM-DD string, not `new Date()`, so it
    // cannot drift by a day/year through timezone quirks.
    function syncExpiryToBarcode() {
        var expiryDate = $('#expiry_date').val();
        var parts = /^(\d{4})-(\d{2})-(\d{2})$/.exec(expiryDate);
        if (parts) {
            $('#year_display').val(parts[1].slice(-2));
            $('#month_display').val(parts[2]);
            $('#day_display').val(parts[3]);
        } else {
            $('#year_display').val('');
            $('#month_display').val('');
            $('#day_display').val('');
        }
        updateBarcodePreview();
    }

    // Handle expiry date change
    $('#expiry_date').on('change input', function() {
        syncExpiryToBarcode();
    });

    // Handle add-on fields change
    $('#add_on_fields').on('input', function() {
        // Optional: Add validation for add-on fields if needed
        // For example, allow alphanumeric and specific special characters
        var value = $(this).val();
        // You can add validation here if needed
        // Example: Allow only alphanumeric and underscore
        // value = value.replace(/[^a-zA-Z0-9_]/g, '');
        // $(this).val(value);
        
        updateBarcodePreview();
    });

    // Once the user directly types into the barcode field, stop
    // auto-overwriting it - they've taken manual control (e.g. no expiry
    // date, so there's nothing to auto-derive it from).
    var barcodeManuallyEdited = false;
    $('#complete_barcode_preview').on('input', function() {
        barcodeManuallyEdited = true;
    });
    $('#resetBarcodeBtn').on('click', function() {
        barcodeManuallyEdited = false;
        updateBarcodePreview();
    });

    // Update barcode preview
    function updateBarcodePreview() {
        if (barcodeManuallyEdited) {
            return;
        }

        var group = $('#barcode_group').val() || '';
        var userId = $('#user_id_input').val() || '';
        var year = $('#year_display').val() || '';
        var fixed = '8';
        var day = $('#day_display').val() || '';
        var month = $('#month_display').val() || '';
        var productCode = $('#barcode_product_code').val() || '';
        var addOnFields = $('#add_on_fields').val() || '';

        // Only auto-fill once everything needed to derive it is present -
        // expiry date (year/day/month) included, since it's optional.
        if (group && userId && userId.length === 2 && year && day && month && productCode) {
            var barcodeText = group + userId + year + fixed + day + month + productCode;

            // Append add-on fields if provided
            if (addOnFields && addOnFields.trim() !== '') {
                barcodeText += addOnFields;
            }

            $('#complete_barcode_preview').val(barcodeText);
        } else {
            $('#complete_barcode_preview').val('');
        }
    }

    // Form validation before submit
    $('#productBatchForm').on('submit', function(e) {
        // Rebuild the barcode from the CURRENT expiry so a stale cached value
        // (e.g. expiry changed via autofill without firing 'change') can never
        // be submitted out of sync with the expiry date - unless the user has
        // manually edited it, in which case their value stands.
        syncExpiryToBarcode();

        var group = $('#barcode_group').val();
        var userId = $('#user_id_input').val();
        var productId = $('#barcode_product_id').val();

        if (!group || group.length !== 1) {
            e.preventDefault();
            alert('Please enter a valid group letter (A-Z)');
            return false;
        }

        if (!userId || userId.length !== 2) {
            e.preventDefault();
            alert('Please enter a valid 2-digit user ID');
            return false;
        }

        if (!productId) {
            e.preventDefault();
            alert('Please select a product');
            return false;
        }

        // Ensure batch_code is set (either auto-generated or manually typed -
        // expiry date is optional, so this is the only hard requirement left)
        if (!$('#complete_barcode_preview').val()) {
            e.preventDefault();
            alert('Please provide a batch code (select an expiry date to auto-generate one, or type it manually)');
            return false;
        }

        ShowLoad();
        return true;
    });

    // Initialize preview on page load with old values
    setTimeout(function() {
        setInitialValues();
    }, 500);

});
</script>
@endpush