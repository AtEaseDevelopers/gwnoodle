@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('invoiceDetails.index') !!}">Invoice</a>
      </li>
      <li class="breadcrumb-item active">Detail</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Invoice Details</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => ['invoices.adddetail',Crypt::encrypt($id)]]) !!}
                                    @csrf
                                    <!-- Invoice Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('invoice_id', __('invoice_details.invoice')) !!}<span class="asterisk"> *</span>
                                        {!! Form::select('invoice_id', $invoiceItems, $id, ['class' => 'form-control', 'placeholder' => 'Pick a Invoice...','disabled']) !!}
                                    </div>

                                    <!-- Warehouse Selection Field (NEW) -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('warehouse_id', 'Select Warehouse') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('warehouse_id', $warehouseItems, null, ['class' => 'form-control select2', 'placeholder' => 'Select Warehouse...', 'id' => 'warehouse_id']) !!}
                                    </div>

                                    <!-- Product Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('product_id', __('invoice_details.product')) !!}<span class="asterisk"> *</span>
                                        {!! Form::select('product_id', $productItems, null, ['class' => 'form-control select2', 'placeholder' => 'Pick a Product...', 'id' => 'product_id', 'disabled']) !!}
                                    </div>

                                    <!-- Batch Selection Field -->
                                    <div class="form-group col-sm-6" id="batch_selection_group" style="display: none;">
                                        {!! Form::label('product_batch_id', 'Select Batch') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('product_batch_id', [], null, ['class' => 'form-control select2', 'placeholder' => 'First select product', 'id' => 'product_batch_id']) !!}
                                        <small class="text-muted" id="batch_quantity_info"></small>
                                    </div>

                                    <!-- Quantity Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('quantity', __('invoice_details.quantity')) !!}<span class="asterisk"> *</span>
                                        {!! Form::number('quantity', null, ['class' => 'form-control', 'min' => 1, 'step' => 1, 'id' => 'quantity', 'disabled']) !!}
                                    </div>

                                    <!-- Price Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('price', __('invoice_details.price')) !!}<span class="asterisk"> *</span>
                                        {!! Form::text('price', null, ['class' => 'form-control', 'min' => 0, 'step' => 0.01, 'id' => 'price', 'readonly']) !!}
                                    </div>

                                    <!-- Remark Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('remark', __('invoice_details.remark')) !!}
                                        {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
                                    </div>

                                    <!-- Available Stock Info -->
                                    <div class="form-group col-sm-12" id="stock_info" style="display: none;">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> 
                                            <span id="stock_info_text"></span>
                                        </div>
                                    </div>

                                    <!-- Submit Field -->
                                    <div class="form-group col-sm-12">
                                        {!! Form::submit(__('invoice_details.save'), ['class' => 'btn btn-primary', 'id' => 'submitBtn', 'disabled']) !!}
                                        <a href="{{ route('invoiceDetails.index') }}" class="btn btn-secondary">{{ __('invoice_details.cancel') }}</a>
                                    </div>

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function () {
            HideLoad();
            
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            // When warehouse is selected, enable product dropdown
            $("#warehouse_id").change(function() {
                var warehouseId = $(this).val();
                var productSelect = $('#product_id');
                
                if (warehouseId) {
                    productSelect.prop('disabled', false);
                    // Reset and disable dependent fields
                    $('#batch_selection_group').hide();
                    $('#product_batch_id').empty().append('<option value="">{{ __("First select product") }}</option>').trigger('change');
                    $('#quantity').prop('disabled', true).val('');
                    $('#price').val('');
                    $('#stock_info').hide();
                    $('#submitBtn').prop('disabled', true);
                } else {
                    productSelect.prop('disabled', true).val('').trigger('change');
                }
            });

            // When product is selected, load available batches from selected warehouse
            $("#product_id").change(function() {
                getprice();
                loadWarehouseProductBatches();
            });
            
            $("#invoice_id").change(function(){
                getprice();
            });
            
            // When batch is selected, show available quantity
            $("#product_batch_id").change(function() {
                var selected = $(this).find('option:selected');
                var quantity = selected.data('quantity') || 0;
                var maxQty = selected.data('quantity') || 0;
                
                if (quantity > 0) {
                    $("#batch_quantity_info").text('Available: ' + quantity + ' units');
                    $("#quantity").prop('disabled', false).attr('max', maxQty);
                    $("#stock_info_text").html('Selected batch has <strong>' + quantity + '</strong> units available in this warehouse');
                    $("#stock_info").show().removeClass('alert-warning').addClass('alert-info');
                } else {
                    $("#batch_quantity_info").text('');
                    $("#quantity").prop('disabled', true);
                    $("#stock_info").hide();
                }
                
                validateForm();
            });
            
            $("#quantity").on('input', function() {
                validateForm();
            });
            
            // Load product batches from selected warehouse via AJAX
            function loadWarehouseProductBatches() {
                var warehouseId = $('#warehouse_id').val();
                var productId = $('#product_id').val();
                var batchSelect = $('#product_batch_id');
                
                if (warehouseId && productId) {
                    $.ajax({
                        url: '{{ url("warehouses") }}/' + warehouseId + '/products/' + productId + '/batches',
                        type: 'GET',
                        data: {
                            warehouse_id: warehouseId,
                            product_id: productId
                        },
                        success: function(response) {
                            batchSelect.empty().append('<option value="">{{ __("Select Batch") }}</option>');
                            
                            if (response.success && response.batches && response.batches.length > 0) {
                                var hasAvailableBatches = false;
                                
                                $.each(response.batches, function(index, batch) {
                                    if (batch.quantity > 0) {
                                        hasAvailableBatches = true;
                                        batchSelect.append(
                                            '<option value="' + batch.batch_id + '" ' +
                                            'data-quantity="' + batch.quantity + '" ' +
                                            'data-expiry="' + batch.expiry_date + '">' +
                                            batch.batch_code + ' (Exp: ' + batch.expiry_date + ') - ' + 
                                            batch.quantity + ' units' +
                                            '</option>'
                                        );
                                    }
                                });
                                
                                if (hasAvailableBatches) {
                                    $('#batch_selection_group').show();
                                    batchSelect.prop('disabled', false);
                                    $("#stock_info").hide();
                                } else {
                                    batchSelect.empty().append('<option value="">{{ __("No batches with available stock in this warehouse") }}</option>');
                                    $('#batch_selection_group').show();
                                    batchSelect.prop('disabled', true);
                                    $("#stock_info").show().removeClass('alert-info').addClass('alert-warning');
                                    $("#stock_info_text").html('<i class="fa fa-exclamation-triangle"></i> No available stock for this product in selected warehouse');
                                }
                            } else {
                                batchSelect.empty().append('<option value="">{{ __("No batches found for this product in selected warehouse") }}</option>');
                                $('#batch_selection_group').show();
                                batchSelect.prop('disabled', true);
                                $("#stock_info").show().removeClass('alert-info').addClass('alert-warning');
                                $("#stock_info_text").html('<i class="fa fa-exclamation-triangle"></i> This product has no batches in selected warehouse');
                            }
                            
                            batchSelect.trigger('change.select2');
                        },
                        error: function(xhr, status, error) {
                            console.log('Error loading batches:', error);
                            batchSelect.empty().append('<option value="">{{ __("Error loading batches") }}</option>');
                            $('#batch_selection_group').show();
                            batchSelect.prop('disabled', true);
                            $("#stock_info").show().removeClass('alert-info').addClass('alert-danger');
                            $("#stock_info_text").html('<i class="fa fa-exclamation-circle"></i> Error loading batch information');
                        }
                    });
                } else {
                    $('#batch_selection_group').hide();
                    batchSelect.empty().append('<option value="">{{ __("Select warehouse and product first") }}</option>');
                    $("#stock_info").hide();
                }
            }

            // Get price (existing function)
            function getprice() {
                var invoice_id = $('#invoice_id').val();
                var product_id = $('#product_id').val();
                if(invoice_id != '' && product_id != ''){
                    
                    var url = '{{ config("app.url") }}invoiceDetails/getprice/'+invoice_id+'/'+product_id;
                    
                    console.log('Fetching price from:', url);
                    
                    $.ajax({
                        url: url,
                        type: 'GET',
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(data) {
                            console.log('Price response:', data);
                            if(data.status){
                                $('#price').val(data.data);
                            } else {
                                noti('e', 'Please contact your administrator', data.message);
                            }
                            HideLoad();
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', {
                                status: xhr.status,
                                statusText: xhr.statusText,
                                responseText: xhr.responseText,
                                error: error
                            });
                            
                            if (xhr.status === 0) {
                                noti('e', 'Network error - please check your connection', '');
                            } else if (xhr.status === 404) {
                                noti('e', 'API endpoint not found', '');
                            } else if (xhr.status === 500) {
                                noti('e', 'Server error', xhr.responseText);
                            } else {
                                noti('e', 'Please contact your administrator', error);
                            }
                            HideLoad();
                        }
                    }); 
                }
            }
            
            // Validate form before submission
            function validateForm() {
                var warehouseSelected = $('#warehouse_id').val() !== '';
                var productSelected = $('#product_id').val() !== '';
                var batchSelected = $('#product_batch_id').val() !== '';
                var quantity = $('#quantity').val();
                var maxQuantity = $('#quantity').attr('max') || 0;
                var quantityValid = quantity && parseInt(quantity) > 0 && parseInt(quantity) <= parseInt(maxQuantity);
                
                var isValid = warehouseSelected && productSelected && batchSelected && quantityValid;
                
                $('#submitBtn').prop('disabled', !isValid);
                
                if (quantity && !quantityValid && maxQuantity > 0) {
                    $('#stock_info').removeClass('alert-info').addClass('alert-warning');
                    $('#stock_info_text').html('<i class="fa fa-exclamation-triangle"></i> Quantity cannot exceed available stock (' + maxQuantity + ' units)');
                    $('#stock_info').show();
                } else if (batchSelected && quantityValid) {
                    $('#stock_info').removeClass('alert-warning').addClass('alert-info');
                }
            }
        });

        // Escape key functionality
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
    </script>
@endpush