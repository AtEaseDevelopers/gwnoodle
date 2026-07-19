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
                                        @unless($isDriverInvoice)
                                            <button class="border-0 bg-transparent text-primary" data-toggle="modal" data-target="#addInvoiceItemModal">
                                                Scan Barcode <i class="fa fa-barcode fa-lg"></i>
                                            </button>
                                        @endunless
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
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete', 'class' => 'invoice-detail-delete-form']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'data-needs-warehouse-return' => ($invoicedetail['warehouse_id'] === null && $needsReturnWarehouseSelection) ? '1' : '0',
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
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete', 'class' => 'invoice-detail-delete-form']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'data-needs-warehouse-return' => ($invoicedetail['warehouse_id'] === null && $needsReturnWarehouseSelection) ? '1' : '0',
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
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title h6">Add Invoice Items</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                {!! Form::open(['route' => ['invoices.adddetail', Crypt::encrypt($id)], 'id' => 'addInvoiceItemForm']) !!}
                    <div class="modal-body">
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
                            <small class="text-muted" id="modalWarehouseInfo">Select warehouse to load available batches</small>
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
                                    <small class="d-none text-danger" id="modalScanError"></small>
                                    <small class="d-none text-success" id="modalScanSuccess"></small>
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

                        <!-- Items Table -->
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-sm" id="invoiceItemsTable">
                                <thead>
                                    <tr>
                                        <th width="20%">{{ __('Batch Code') }}</th>
                                        <th width="25%">{{ __('Product') }}</th>
                                        <th width="10%" class="text-center">{{ __('Available') }}</th>
                                        <th width="15%">{{ __('Quantity') }}</th>
                                        <th width="15%">{{ __('Price (RM)') }}</th>
                                        <th width="15%">{{ __('Total (RM)') }}</th>
                                        <th width="10%" class="text-center">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="emptyRow">
                                        <td colspan="7" class="text-center text-muted">
                                            {{ __('No items added yet. Scan barcode to add items.') }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f8f9fa;">
                                        <td colspan="5" class="text-right"><strong>{{ __('Total Items:') }}</strong></td>
                                        <td colspan="2">
                                            <strong id="totalItemsCount">0</strong> items
                                        </td>
                                    </tr>
                                    <tr style="background-color: #e9ecef;">
                                        <td colspan="5" class="text-right"><strong>{{ __('Grand Total:') }}</strong></td>
                                        <td colspan="2">
                                            <strong id="grandTotal">RM 0.00</strong>
                                            <input type="hidden" name="grand_total" id="grandTotalInput" value="0">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Hidden Container for Form Submission -->
                        <div id="itemsHiddenContainer"></div>

                        <!-- Remark Field -->
                        <div class="form-group">
                            {!! Form::label('remark', __('invoice_details.remark')) !!}
                            {!! Form::text('remark', null, ['class' => 'form-control', 'maxlength' => 255, 'id' => 'modal_remark', 'placeholder' => 'Optional remark for all items']) !!}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="button" class="btn btn-info" id="modalSubmitBtn" disabled>
                            <i class="fa fa-save"></i> Add Items
                        </button>
                    </div>
                {!! Form::close() !!}
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

    <!-- Return Stock To Warehouse Modal (driver not currently on a trip) -->
    <div id="returnWarehouseModal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title h6">Select Warehouse to Return Stock</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <p>This driver is not currently on a trip, so this stock can't be returned to their lorry. Please choose a warehouse to return it to instead.</p>
                    <div class="form-group">
                        {!! Form::label('return_warehouse_id', 'Select Warehouse') !!}<span class="asterisk"> *</span>
                        {!! Form::select('return_warehouse_id', $warehouseItems, null, ['class' => 'form-control select2', 'placeholder' => 'Select Warehouse...', 'id' => 'return_warehouse_id']) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmReturnWarehouseBtn">Confirm Delete</button>
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

        #invoiceItemsTable .item-qty,
        #invoiceItemsTable .item-price {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            border-radius: 0.25rem !important;
        }
        
        #invoiceItemsTable .item-qty:focus,
        #invoiceItemsTable .item-price:focus {
            border-color: #80bdff !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }
        
        /* Ensure the quantity cell is properly sized */
        #invoiceItemsTable td.quantity-cell,
        #invoiceItemsTable td.price-cell {
            padding: 8px !important;
            vertical-align: middle !important;
        }
        
        /* Debug - add a border to see if cells are there */
        #invoiceItemsTable td.quantity-cell {
            background-color: #f9f9f9;
        }
        
    </style>

    <script>
        $(document).ready(function () {
            let warehouseBatches = {};
            let invoiceItems = [];
            let invoiceCodeReader = null;
            let invoiceIsScanning = false;
            let invoiceActiveStream = null;
            
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#addInvoiceItemModal')
            });

            $('#return_warehouse_id').select2({
                width: '100%',
                dropdownParent: $('#returnWarehouseModal')
            });

            // Delete invoice detail - some rows (driver-sourced item, driver not currently
            // on a trip) need the admin to pick a warehouse to return stock to first.
            var pendingDeleteForm = null;

            $('.invoice-detail-delete-form').on('submit', function(e) {
                var needsWarehouseReturn = $(this).find('button[type=submit]').data('needs-warehouse-return');

                if (needsWarehouseReturn == '1' || needsWarehouseReturn === 1) {
                    e.preventDefault();
                    pendingDeleteForm = this;
                    $('#return_warehouse_id').val('').trigger('change');
                    $('#returnWarehouseModal').modal('show');
                    return false;
                }

                return confirm('Are you sure to delete the Invoice Detail?');
            });

            $('#confirmReturnWarehouseBtn').on('click', function() {
                var warehouseId = $('#return_warehouse_id').val();

                if (!warehouseId) {
                    noti('e', 'Please select a warehouse', '');
                    return;
                }

                if (pendingDeleteForm) {
                    $(pendingDeleteForm).append(
                        '<input type="hidden" name="return_warehouse_id" value="' + warehouseId + '">'
                    );
                    $('#returnWarehouseModal').modal('hide');
                    pendingDeleteForm.submit();
                }
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
                                $('#modalWarehouseInfo').html('<span class="text-success">✓ Warehouse loaded with ' + Object.keys(warehouseBatches).length + ' batches available</span>');
                            } else {
                                $('#modalScanInfo').text('No available batches in this warehouse');
                                $('#modal_scan_input').prop('disabled', true);
                                $('#openModalCameraBtn').prop('disabled', true);
                                $('#modalWarehouseInfo').html('<span class="text-warning">⚠ No batches available in this warehouse</span>');
                            }
                            clearAllItems();
                            resetModalMessages();
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            $('#modalScanInfo').text('Error loading batches');
                            $('#modalWarehouseInfo').html('<span class="text-danger">✗ Error loading batches</span>');
                        }
                    });
                } else {
                    warehouseBatches = {};
                    $('#modalScanInfo').text('Select warehouse first, then scan product batches.');
                    $('#modal_scan_input').prop('disabled', true);
                    $('#openModalCameraBtn').prop('disabled', true);
                    $('#modalWarehouseInfo').text('Select warehouse to load available batches');
                    clearAllItems();
                }
            });
            
            // Scan input handler
            $('#modal_scan_input').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var code = $(this).val().trim();
                    $(this).val('');
                    if (code) {
                        addScannedBatch(code);
                    }
                }
            });
            
            // Add scanned batch to items list
            function addScannedBatch(rawCode) {
                var code = (rawCode || '').trim().toUpperCase();
                if (!code) {
                    showModalError('Please enter a batch code');
                    return;
                }
                
                hideModalMessages();
                
                if (!$('#modal_warehouse_id').val()) {
                    showModalError('Please select warehouse first');
                    return;
                }
                
                var batch = warehouseBatches[code];
                if (!batch) {
                    showModalError('Batch code not found in selected warehouse: ' + rawCode);
                    return;
                }
                
                // Get price for this product
                var invoice_id = $('#invoice_id').val();
                var product_id = batch.product_id;
                
                $.ajax({
                    url: '{{ config("app.url") }}/invoiceDetails/getprice/' + invoice_id + '/' + product_id,
                    type: 'GET',
                    dataType: 'json',
                    async: false,
                    success: function(data) {
                        var price = data.status ? parseFloat(data.data) : 0;
                        
                        // Check if item already exists in the list
                        var existingItem = invoiceItems.find(function(item) {
                            return item.batch_id === batch.batch_id;
                        });
                        
                        if (existingItem) {
                            // Check if can add more quantity
                            if (existingItem.quantity + 1 > existingItem.available_quantity) {
                                showModalError('Batch ' + batch.batch_code + ' cannot add more. Maximum is ' + existingItem.available_quantity);
                                return;
                            }
                            existingItem.quantity += 1;
                            existingItem.total = existingItem.quantity * existingItem.price;
                            // Update the specific row without re-rendering everything
                            updateExistingItemRow(existingItem);
                        } else {
                            invoiceItems.push({
                                batch_id: batch.batch_id,
                                batch_code: batch.batch_code,
                                product_id: batch.product_id,
                                product_name: batch.product_name,
                                available_quantity: batch.quantity,
                                price: price,
                                quantity: 1,
                                total: price,
                                expiry_date: batch.expiry_date
                            });
                            // Add new row to table
                            addNewItemRow(invoiceItems[invoiceItems.length - 1], invoiceItems.length - 1);
                        }
                        
                        updateTotalsAndSubmitButton();
                        showModalSuccess('Added batch ' + batch.batch_code + ' to invoice.');
                    },
                    error: function(xhr, status, error) {
                        showModalError('Error loading price information');
                    }
                });
            }
            
            // Update existing item row without re-rendering all rows
            function updateExistingItemRow(item, existingIndex) {
                // Find the row by batch_id or use the index
                let targetRow = null;
                let rowIndex = -1;
                
                // First try to find by index
                const rows = $('#invoiceItemsTable tbody tr');
                
                if (existingIndex !== undefined && rows.length > existingIndex) {
                    targetRow = $(rows[existingIndex]);
                    rowIndex = existingIndex;
                } else {
                    // Fallback: find by batch code
                    rows.each(function(index, row) {
                        const batchCode = $(row).find('td:first-child').text().trim().split('\n')[0];
                        if (batchCode === item.batch_code) {
                            targetRow = $(row);
                            rowIndex = index;
                            return false;
                        }
                    });
                }
                
                if (targetRow && targetRow.length) {
                    // Update quantity input
                    const qtyInput = targetRow.find('.item-qty');
                    qtyInput.val(item.quantity);
                    
                    // Update total cell
                    targetRow.find('.item-total').text('RM ' + item.total.toFixed(2));
                    
                    // Update hidden inputs
                    $(`.qty-hidden-${rowIndex}`).val(item.quantity);
                    
                    // Update quantity status badge
                    let quantityStatus = '';
                    let quantityClass = '';
                    
                    if (item.quantity === item.available_quantity && item.quantity > 0) {
                        quantityStatus = '<span class="badge badge-warning quantity-status-badge">Full quantity</span>';
                        quantityClass = 'is-warning';
                    } else if (item.quantity > 0) {
                        const percentage = Math.round((item.quantity / item.available_quantity) * 100);
                        quantityStatus = '<span class="badge badge-info quantity-status-badge">' + percentage + '% used</span>';
                        quantityClass = 'valid-feedback';
                    } else {
                        quantityStatus = '';
                        quantityClass = '';
                    }
                    
                    // Update product cell with status badge
                    const productCell = targetRow.find('td:nth-child(2)');
                    productCell.html(escapeHtml(item.product_name) + ' ' + quantityStatus);
                    
                    // Update quantity input class
                    qtyInput.removeClass('is-invalid is-warning valid-feedback').addClass(quantityClass);
                    
                    // Trigger change event to ensure any listeners are notified
                    qtyInput.trigger('change');
                    
                    console.log('Updated existing item:', item.batch_code, 'new quantity:', item.quantity);
                } else {
                    console.error('Could not find row for item:', item.batch_code);
                    // If row not found, re-render all items
                    fullRenderItems();
                }
            }

            // Add scanned batch to items list - FIXED
            function addScannedBatch(rawCode) {
                var code = (rawCode || '').trim().toUpperCase();
                if (!code) {
                    showModalError('Please enter a batch code');
                    return;
                }
                
                hideModalMessages();
                
                if (!$('#modal_warehouse_id').val()) {
                    showModalError('Please select warehouse first');
                    return;
                }
                
                var batch = warehouseBatches[code];
                if (!batch) {
                    showModalError('Batch code not found in selected warehouse: ' + rawCode);
                    return;
                }
                
                // Get price for this product
                var invoice_id = $('#invoice_id').val();
                var product_id = batch.product_id;
                
                $.ajax({
                    url: '{{ config("app.url") }}/invoiceDetails/getprice/' + invoice_id + '/' + product_id,
                    type: 'GET',
                    dataType: 'json',
                    async: false,
                    success: function(data) {
                        var price = data.status ? parseFloat(data.data) : 0;
                        
                        // Check if item already exists in the list
                        var existingIndex = -1;
                        var existingItem = null;
                        
                        for (var i = 0; i < invoiceItems.length; i++) {
                            if (invoiceItems[i].batch_id === batch.batch_id) {
                                existingIndex = i;
                                existingItem = invoiceItems[i];
                                break;
                            }
                        }
                        
                        if (existingItem) {
                            // Check if can add more quantity
                            if (existingItem.quantity + 1 > existingItem.available_quantity) {
                                showModalError('Batch ' + batch.batch_code + ' cannot add more. Maximum is ' + existingItem.available_quantity);
                                return;
                            }
                            
                            // Update the existing item
                            existingItem.quantity += 1;
                            existingItem.total = existingItem.quantity * existingItem.price;
                            
                            // Update the UI
                            updateExistingItemRow(existingItem, existingIndex);
                            
                            showModalSuccess('Added 1 more to ' + batch.batch_code + '. New quantity: ' + existingItem.quantity);
                        } else {
                            // Add new item
                            var newItem = {
                                batch_id: batch.batch_id,
                                batch_code: batch.batch_code,
                                product_id: batch.product_id,
                                product_name: batch.product_name,
                                available_quantity: batch.quantity,
                                price: price,
                                quantity: 1,
                                total: price,
                                expiry_date: batch.expiry_date
                            };
                            
                            invoiceItems.push(newItem);
                            // Add new row to table
                            addNewItemRow(newItem, invoiceItems.length - 1);
                            
                            showModalSuccess('Added new batch ' + batch.batch_code + ' to invoice.');
                        }
                        
                        updateTotalsAndSubmitButton();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error getting price:', error);
                        showModalError('Error loading price information');
                    }
                });
            }
            
            // Add new item row
            function addNewItemRow(item, index) {
                const tbody = $('#invoiceItemsTable tbody');
                
                // Remove empty row if exists
                if ($('#emptyRow').length) {
                    $('#emptyRow').remove();
                }
                
                let quantityStatus = '';
                let quantityClass = '';
                
                if (item.quantity === item.available_quantity && item.quantity > 0) {
                    quantityStatus = '<span class="badge badge-warning quantity-status-badge">Full quantity</span>';
                    quantityClass = 'is-warning';
                } else if (item.quantity > 0) {
                    const percentage = Math.round((item.quantity / item.available_quantity) * 100);
                    quantityStatus = '<span class="badge badge-info quantity-status-badge">' + percentage + '% used</span>';
                    quantityClass = 'valid-feedback';
                }
                
                // Debug: Log what we're creating
                console.log('Creating row for item:', item.batch_code, 'quantity:', item.quantity);
                
                var row = $('<tr>');
                row.html(`
                    <td>${escapeHtml(item.batch_code)}<br><small class="text-muted">Exp: ${escapeHtml(item.expiry_date)}</small></td>
                    <td>${escapeHtml(item.product_name)} ${quantityStatus}</td>
                    <td class="text-center">${item.available_quantity}</td>
                    <td class="quantity-cell">
                        <input type="number" value="${item.quantity}" 
                            class="form-control item-qty ${quantityClass}" 
                            data-index="${index}" 
                            data-max="${item.available_quantity}" 
                            style="width: 100%; min-width: 80px; display: block !important; visibility: visible !important;">
                        <small class="text-danger qty-error" id="qty-error-${index}" style="display: none; font-size: 11px;"></small>
                    </td>
                    <td class="price-cell">
                        <input type="number" step="0.01" value="${item.price.toFixed(2)}" 
                            class="form-control item-price" 
                            data-index="${index}" 
                            style="width: 100%; min-width: 100px; display: block !important; visibility: visible !important;">
                    </td>
                    <td class="text-right item-total">RM ${item.total.toFixed(2)}</td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>
                `);
                
                tbody.append(row);
                
                // Add hidden inputs for form submission
                const hiddenContainer = $('#itemsHiddenContainer');
                hiddenContainer.append('<input type="hidden" name="items[' + index + '][product_batch_id]" value="' + item.batch_id + '">');
                hiddenContainer.append('<input type="hidden" name="items[' + index + '][product_id]" value="' + item.product_id + '">');
                hiddenContainer.append('<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '" class="qty-hidden-' + index + '">');
                hiddenContainer.append('<input type="hidden" name="items[' + index + '][price]" value="' + item.price + '" class="price-hidden-' + index + '">');
                
                // Verify the input was created
                console.log('Input created with value:', row.find('.item-qty').val());
            }
            
            // Update totals and submit button
            function updateTotalsAndSubmitButton() {
                let grandTotal = 0;
                let totalItems = 0;
                let hasValidItems = true;
            
                console.log('Calculating totals for', invoiceItems.length, 'items');
                
                $.each(invoiceItems, function(index, item) {
                    grandTotal += item.total;
                    totalItems += item.quantity;
                    
                    console.log(`Item ${index}: ${item.batch_code} - Qty: ${item.quantity}, Total: ${item.total}`);
                    
                    if (item.quantity <= 0 || item.quantity > item.available_quantity || item.price <= 0) {
                        hasValidItems = false;
                    }
                });
                
                $('#totalItemsCount').text(totalItems);
                $('#grandTotal').text('RM ' + grandTotal.toFixed(2));
                $('#grandTotalInput').val(grandTotal);
                $('#modalSubmitBtn').prop('disabled', !hasValidItems || invoiceItems.length === 0);
                
                console.log('Totals updated - Total Items:', totalItems, 'Grand Total:', grandTotal);
            }
            
            // Handle quantity change - direct update without re-rendering
            $(document).on('input', '.item-qty', function() {
                const index = parseInt($(this).data('index'), 10);
                const item = invoiceItems[index];
                let quantity = parseInt($(this).val(), 10);
                
                if (!item) return;
                
                const maxQty = item.available_quantity;
                const errorSpan = $('#qty-error-' + index);
                const qtyInput = $(this);
                
                qtyInput.removeClass('is-invalid is-warning valid-feedback');
                
                if (isNaN(quantity) || quantity === '') {
                    errorSpan.hide();
                    item.quantity = 0;
                    item.total = 0;
                } else if (quantity < 1) {
                    qtyInput.addClass('is-invalid');
                    errorSpan.text('Quantity must be at least 1').show();
                    item.quantity = 0;
                    item.total = 0;
                } else if (quantity > maxQty) {
                    qtyInput.addClass('is-invalid');
                    errorSpan.html(`⚠️ Exceeds available stock (${maxQty} units)`).show();
                    item.quantity = quantity;
                    item.total = quantity * item.price;
                    
                    qtyInput.addClass('exceed-warning');
                    setTimeout(() => qtyInput.removeClass('exceed-warning'), 500);
                } else if (quantity === maxQty) {
                    qtyInput.addClass('is-warning');
                    errorSpan.html(`✓ Maximum quantity (${maxQty} units)`).show();
                    item.quantity = quantity;
                    item.total = quantity * item.price;
                } else {
                    qtyInput.addClass('valid-feedback');
                    errorSpan.hide();
                    item.quantity = quantity;
                    item.total = quantity * item.price;
                }
                
                // Update total cell
                const totalCell = $(this).closest('tr').find('.item-total');
                totalCell.text('RM ' + item.total.toFixed(2));
                
                // Update hidden input
                $(`.qty-hidden-${index}`).val(item.quantity);
                
                // Update quantity status badge
                const productCell = $(this).closest('tr').find('td:nth-child(2)');
                let quantityStatus = '';
                let quantityClass = '';
                
                if (item.quantity === item.available_quantity && item.quantity > 0) {
                    quantityStatus = '<span class="badge badge-warning quantity-status-badge">Full quantity</span>';
                    quantityClass = 'is-warning';
                } else if (item.quantity > 0) {
                    const percentage = Math.round((item.quantity / item.available_quantity) * 100);
                    quantityStatus = '<span class="badge badge-info quantity-status-badge">' + percentage + '% used</span>';
                    quantityClass = 'valid-feedback';
                } else {
                    quantityStatus = '';
                    quantityClass = '';
                }
                
                // Update product cell content while preserving the product name
                const productName = item.product_name;
                productCell.html(escapeHtml(productName) + ' ' + quantityStatus);
                
                // Update quantity input class
                qtyInput.removeClass('is-invalid is-warning valid-feedback').addClass(quantityClass);
                
                updateTotalsAndSubmitButton();
            });
            
            // Handle price change
            $(document).on('input', '.item-price', function() {
                const index = parseInt($(this).data('index'), 10);
                const item = invoiceItems[index];
                let price = parseFloat($(this).val());
                
                if (!item) return;
                
                if (isNaN(price) || price < 0) {
                    price = 0;
                }
                
                item.price = price;
                item.total = item.quantity * price;
                
                // Update total cell
                const totalCell = $(this).closest('tr').find('.item-total');
                totalCell.text('RM ' + item.total.toFixed(2));
                
                // Update hidden input
                $(`.price-hidden-${index}`).val(price.toFixed(2));
                
                updateTotalsAndSubmitButton();
            });
            
            // Remove item
            $(document).on('click', '.remove-item', function() {
                const index = parseInt($(this).data('index'), 10);
                invoiceItems.splice(index, 1);
                
                // Re-render all items to update indices
                fullRenderItems();
            });
            
            // Full re-render (used when removing items or clearing)
            function fullRenderItems() {
                const tbody = $('#invoiceItemsTable tbody');
                const hiddenContainer = $('#itemsHiddenContainer');
                
                tbody.empty();
                hiddenContainer.empty();
                
                if (invoiceItems.length === 0) {
                    tbody.append('<tr id="emptyRow"><td colspan="7" class="text-center text-muted">{{ __("No items added yet. Scan barcode to add items.") }}</td></tr>');
                    updateTotalsAndSubmitButton();
                    return;
                }
                
                // Re-render each item with new indices
                $.each(invoiceItems, function(index, item) {
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
                    
                    var row = $('<tr>');
                    row.html(`
                        <td>${escapeHtml(item.batch_code)}<br><small class="text-muted">Exp: ${escapeHtml(item.expiry_date)}</small></td>
                        <td>${escapeHtml(item.product_name)} ${quantityStatus}</td>
                        <td class="text-center">${item.available_quantity}</td>
                        <td>
                            <input type="number" value="${item.quantity}" 
                                class="form-control item-qty ${quantityClass}" 
                                data-index="${index}" 
                                data-max="${item.available_quantity}" 
                                style="width: 100%; min-width: 80px;">
                            <small class="text-danger qty-error" id="qty-error-${index}" style="display: none; font-size: 11px;"></small>
                        </td>
                        <td>
                            <input type="number" step="0.01" value="${item.price.toFixed(2)}" 
                                class="form-control item-price" 
                                data-index="${index}" 
                                style="width: 100%; min-width: 100px;">
                        </td>
                        <td class="text-right item-total">RM ${item.total.toFixed(2)}</td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>
                    `);
                    
                    tbody.append(row);
                    
                    // Add hidden inputs for form submission
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][product_batch_id]" value="' + item.batch_id + '">');
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][product_id]" value="' + item.product_id + '">');
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '" class="qty-hidden-' + index + '">');
                    hiddenContainer.append('<input type="hidden" name="items[' + index + '][price]" value="' + item.price + '" class="price-hidden-' + index + '">');
                });
                
                updateTotalsAndSubmitButton();
            }
            
            function clearAllItems() {
                invoiceItems = [];
                fullRenderItems();
            }
            
            // Form submission
            $('#addInvoiceItemForm').on('submit', function(e) {
                let hasExceededItems = false;
                let errorMessage = '';
                
                $.each(invoiceItems, function(index, item) {
                    if (item.quantity > item.available_quantity) {
                        hasExceededItems = true;
                        errorMessage += `\n• ${item.batch_code}: ${item.quantity} > ${item.available_quantity}`;
                    }
                    if (item.quantity <= 0) {
                        hasExceededItems = true;
                        errorMessage += `\n• ${item.batch_code}: Quantity must be at least 1`;
                    }
                    if (item.price <= 0) {
                        hasExceededItems = true;
                        errorMessage += `\n• ${item.batch_code}: Price must be greater than 0`;
                    }
                });
                
                if (hasExceededItems) {
                    e.preventDefault();
                    showModalError(`Cannot submit. Please fix the following issues:${errorMessage}`);
                    return false;
                }
                
                if (invoiceItems.length === 0) {
                    e.preventDefault();
                    showModalError('Please add at least one item');
                    return false;
                }
                
                const submitBtn = $('#modalSubmitBtn');
                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            });
            
            // Helper functions
            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }
            
            function showModalError(message) {
                $('#modalScanError').removeClass('d-none').text(message);
                $('#modalScanSuccess').addClass('d-none');
                setTimeout(() => $('#modalScanError').addClass('d-none'), 3000);
            }
            
            function showModalSuccess(message) {
                $('#modalScanSuccess').removeClass('d-none').text(message);
                $('#modalScanError').addClass('d-none');
                setTimeout(() => $('#modalScanSuccess').addClass('d-none'), 2000);
            }
            
            function hideModalMessages() {
                $('#modalScanError').addClass('d-none');
                $('#modalScanSuccess').addClass('d-none');
            }
            
            function resetModalMessages() {
                $('#modalScanError').addClass('d-none').text('');
                $('#modalScanSuccess').addClass('d-none').text('');
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
                                addScannedBatch(text);
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
                                    addScannedBatch(text);
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
            
            // Modal reset when closed
            $('#addInvoiceItemModal').on('hidden.bs.modal', function() {
                clearAllItems();
                $('#modal_warehouse_id').val('').trigger('change');
                $('#modal_scan_input').val('');
                $('#modal_remark').val('');
                resetModalMessages();
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