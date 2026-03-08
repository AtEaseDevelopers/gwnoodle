@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Stock Out') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('Stock Out') }}
                        </div>
                        <div class="card-body">
                            @include('inventory_counts.table')
                            <div class="pull-right mr-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Request Modal -->
<div id="createRequest" class="modal fade">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6">{{ __('Create Stock Out') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'inventoryCounts.store', 'enctype' => 'multipart/form-data', 'id' => 'createCountForm']) !!}
                
                <div class="row">
                    <!-- Driver Selection -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="driver_id" class="col-form-label">{{ __('Driver') }} <span class="text-danger">*</span>:</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownDriverCreate" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ __('Select Driver') }}
                                </button>
                                <div class="dropdown-menu p-3" aria-labelledby="dropdownDriverCreate" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                    <input type="text" class="form-control mb-3" id="driverSearchCreate" placeholder="Search Drivers...">
                                    <div id="driverListCreate" class="list-group">
                                        @foreach($drivers as $driver)
                                            <a href="#" class="list-group-item list-group-item-action driver-item" data-value="{{ $driver->id }}">
                                                {{ $driver->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="driver_id" id="selectedDriverCreate" required>
                            <div class="text-danger" id="driverError"></div>
                        </div>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="remarks" class="col-form-label">{{ __('Remarks') }} ({{ __('Optional') }}):</label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Items Table -->
                <div class="form-group">
                    <label class="col-form-label">{{ __('Items') }} <span class="text-danger">*</span>:</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="45%">Product <span class="text-danger">*</span></th>
                                    <th width="20%">Quantity <span class="text-danger">*</span></th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right">
                                        <button type="button" class="btn btn-success btn-sm" id="addItemBtn">
                                            <i class="fa fa-plus"></i> Add Item
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="text-danger" id="itemsError"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-0">{{ __('Submit Request') }}</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

    <!-- Edit Request Modal -->
    <div id="editRequest" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('Edit Stock Out') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body text-center">
                    {!! Form::open(['route' => ['inventoryCounts.update', ':id'], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'id' => 'editRequestForm']) !!}
                    
                    <!-- Driver Selection -->
                    <div class="form-group">
                        <label for="driver_id" class="col-form-label">{{ __('Driver') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownDriverEdit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select Driver') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownDriverEdit" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="driverSearchEdit" placeholder="Search Drivers...">
                                <div id="driverListEdit" class="list-group">
                                    @foreach($drivers as $driver)
                                        <a href="#" class="list-group-item list-group-item-action driver-item" data-value="{{ $driver->id }}">
                                            {{ $driver->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="driver_id" id="selectedDriverEdit">
                        @error('driver_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Product Single-Select Dropdown -->
                    <div class="form-group">
                        <label for="product_id" class="col-form-label">{{ __('Product') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownProductEdit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select Product') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownProductEdit" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="productSearchEdit" placeholder="Search Products...">
                                <div id="productListEdit" class="list-group">
                                    @foreach($products as $product)
                                        <a href="#" class="list-group-item list-group-item-action product-item" data-value="{{ $product->id }}">
                                            {{ $product->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="product_id" id="selectedProductEdit">
                        @error('product_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Quantity Input -->
                    <div class="form-group">
                        <label for="quantity" class="col-form-label">{{ __('Quantity') }}:</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-cubes"></i></span>
                            </div>
                            <input type="number" min="1" class="form-control" placeholder="Enter quantity" name="quantity" id="quantityEdit" required>
                        </div>
                        @error('quantity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status Dropdown -->
                    <!-- <div class="form-group">
                        <label for="status" class="col-form-label">{{ __('Status') }}:</label>
                        <select class="form-control" name="status" id="statusEdit">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div> -->

                    <!-- Notes/Remarks -->
                    <!-- <div class="form-group">
                        <label for="remarks" class="col-form-label">{{ __('Remarks') }}:</label>
                        <textarea class="form-control" name="remarks" id="remarksEdit" rows="2" placeholder="Any additional notes..."></textarea>
                    </div> -->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-0" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-0">{{ __('Update Request') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <!-- View Request Modal -->
    <div id="viewRequest" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Stock Out Details <span id="viewRequestId"></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="30%">Request ID:</th>
                                        <td id="viewRequestIdText"></td>
                                    </tr>
                                    <tr>
                                        <th>Driver:</th>
                                        <td id="viewDriverName"></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span id="viewStatusBadge"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Remarks:</th>
                                        <td id="viewRemarks"></td>
                                    </tr>
                                    <tr>
                                        <th>Requested At:</th>
                                        <td id="viewCreatedAt"></td>
                                    </tr>
                                    <tr id="viewApprovedSection" style="display: none;">
                                        <th>Approved By:</th>
                                        <td id="viewApprovedBy"></td>
                                    </tr>
                                    <tr id="viewApprovedAtSection" style="display: none;">
                                        <th>Approved At:</th>
                                        <td id="viewApprovedAt"></td>
                                    </tr>
                                    <tr id="viewRejectedSection" style="display: none;">
                                        <th>Rejected By:</th>
                                        <td id="viewRejectedBy"></td>
                                    </tr>
                                    <tr id="viewRejectedAtSection" style="display: none;">
                                        <th>Rejected At:</th>
                                        <td id="viewRejectedAt"></td>
                                    </tr>
                                    <tr id="viewRejectionReasonSection" style="display: none;">
                                        <th>Rejection Reason:</th>
                                        <td id="viewRejectionReason"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Items Table Section -->
                    <div class="mt-4">
                        <h5>Items Count</h5>
                        <div id="viewItemsTable"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div id="viewActionButtons">
                            <!-- Action buttons will be shown here -->
                        </div>
                        <button type="button" class="btn btn-secondary rounded-0" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Count Modal (Admin to fill counted quantities) -->
    <div id="editCountModal" class="modal fade">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Fill Counted Quantities <span id="editCountRequestId"></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => ['inventoryCounts.update', ':id'], 'method' => 'PUT', 'id' => 'editCountForm']) !!}
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label"><strong>Driver:</strong></label>
                                <p id="editCountDriverName" class="form-control-static font-weight-bold"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label"><strong>Requested At:</strong></label>
                                <p id="editCountRequestedAt" class="form-control-static"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCountRemarks" class="col-form-label">{{ __('Remarks') }} ({{ __('Optional') }}):</label>
                        <textarea class="form-control" name="remarks" id="editCountRemarks" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                    
                    <!-- Items Table for Counting -->
                    <div class="form-group">
                        <label class="col-form-label"><strong>Count Items:</strong></label>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Please count the actual quantities and enter them below.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="editCountItemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">Product</th>
                                        <th width="20%">Current Quantity</th>
                                        <th width="20%">Counted Quantity *</th>
                                        <th width="20%">Difference</th>
                                    </tr>
                                </thead>
                                <tbody id="editCountItemsBody">
                                    <!-- Items will be populated here -->
                                </tbody>
                            </table>
                        </div>
                        <div class="text-danger" id="editCountItemsError"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-0" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-0">Save & Close</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <!-- Reject Reason Modal (for reject action) -->
    <div id="rejectReasonModal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Stock Out Reason</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rejectRequestId" value="">
                    <div class="form-group">
                        <label for="rejection_reason_modal">Rejection Reason *</label>
                        <textarea name="rejection_reason" id="rejection_reason_modal" class="form-control" rows="3" required placeholder="Please provide a reason for rejection"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRejectBtn">Confirm Reject</button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // Auto-open script for inventory counts
    $(document).ready(function() {
        console.log('Inventory Counts page loaded');
        
        // Check URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const viewCountId = urlParams.get('view_count');
        
        if (viewCountId) {
            console.log('Found view_count parameter:', viewCountId);
            localStorage.setItem('pendingInventoryCountModal', viewCountId);
            
            // Clean up URL
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        // Hook into DataTable initialization
        if (typeof $.fn.DataTable !== 'undefined') {
            $(document).on('init.dt', function(e, settings) {
                console.log('Inventory Counts DataTable initialized');
                
                // Get the DataTable instance
                const table = $(settings.nTable).DataTable();
                
                // Hook into draw event
                table.on('draw', function() {
                    console.log('Inventory Counts DataTable draw event');
                    
                    // Check for pending modal
                    const pendingId = localStorage.getItem('pendingInventoryCountModal');
                    if (pendingId) {
                        console.log('Attempting to open modal for count ID:', pendingId);
                        
                        // Try to find and click the button
                        setTimeout(function() {
                            const button = $('.view-request-btn[data-id="' + pendingId + '"]');
                            if (button.length) {
                                console.log('Found view button, clicking...');
                                button.click();
                                localStorage.removeItem('pendingInventoryCountModal');
                            } else {
                                console.log('View button not found yet, will retry...');
                            }
                        }, 1000);
                    }
                });
            });
        }
        
        // Also check after page load
        setTimeout(function() {
            const pendingId = localStorage.getItem('pendingInventoryCountModal');
            if (pendingId) {
                console.log('Page load check - Pending count ID:', pendingId);
                
                // Try multiple times
                let attempts = 0;
                const maxAttempts = 5;
                
                function tryOpenModal() {
                    attempts++;
                    console.log(`Attempt ${attempts} to find button for ID: ${pendingId}`);
                    
                    const button = $('.view-request-btn[data-id="' + pendingId + '"]');
                    if (button.length) {
                        console.log('Found button on attempt', attempts);
                        button.click();
                        localStorage.removeItem('pendingInventoryCountModal');
                    } else if (attempts < maxAttempts) {
                        setTimeout(tryOpenModal, 1000);
                    } else {
                        console.log('Could not find button after', maxAttempts, 'attempts');
                    }
                }
                
                tryOpenModal();
            }
        }, 2000);
    });
</script>
@endpush
@endsection

@push('scripts')
    <style>
        .dropdown-menu {
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .dropdown-menu .form-check {
            padding: 0.25rem 0;
        }
        .dropdown-toggle::after {
            margin-left: 10px;
        }
        .dropdown-menu::-webkit-scrollbar {
            width: 8px;
        }
        .dropdown-menu::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .btn-outline-primary {
            border-color: #007bff;
            color: #007bff;
        }
        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
        }
        .product-item:hover, 
        .product-item.active,
        .driver-item:hover,
        .driver-item.active {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .list-group-item {
            border: 1px solid rgba(0,0,0,.125);
            margin-bottom: -1px;
        }
        .badge {
            font-size: 85%;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        .badge-cancelled {
            background-color: #6c757d;
            color: white;
        }
        #quantityDisplay .btn-link {
            text-decoration: none;
        }

        #quantityEditContainer .input-group {
            max-width: 200px;
        }

        #editQuantityBtn:hover {
            color: #007bff;
        }
    </style>

<script>
    $(document).ready(function () {
        // Initialize DataTable
        var table = window.LaravelDataTables["dataTableBuilder"] || $('.data-table').DataTable();
        
        // Store current request ID for actions
        var currentRequestId = null;
        var currentRequestStatus = null;
        
        // Items counter for create modal
        var itemCounter = 0;
        
        // ============================================
        // CREATE MODAL FUNCTIONS
        // ============================================
        
        // Initialize create modal items table
        function initializeItemsTable() {
            $('#itemsBody').empty();
            itemCounter = 0;
            addItemRow();
        }
        
        // Add item row to create modal
        function addItemRow() {
            var row = `
                <tr class="item-row" data-index="${itemCounter}">
                    <td class="align-middle text-center">${itemCounter + 1}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-block dropdown-toggle text-left product-dropdown" type="button" id="productDropdown${itemCounter}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="overflow: hidden; text-overflow: ellipsis;">
                                {{ __('Select Product') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="productDropdown${itemCounter}" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3 product-search" placeholder="Search Products..." data-index="${itemCounter}">
                                <div class="product-list" data-index="${itemCounter}">
                                    @foreach($products as $product)
                                        <a href="#" class="list-group-item list-group-item-action product-select-item" data-index="${itemCounter}" data-value="{{ $product->id }}" data-name="{{ $product->name }}">
                                            {{ $product->name }} ({{ $product->unit_code }})
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" class="product-id-input" name="items[${itemCounter}][product_id]" value="">
                        <div class="text-danger product-error small"></div>
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control quantity-input" name="items[${itemCounter}][quantity]" placeholder="Enter quantity">
                        <div class="text-danger quantity-error small"></div>
                    </td>
                    <td class="align-middle text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item-btn" ${itemCounter === 0 ? 'disabled' : ''}>
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#itemsBody').append(row);
            itemCounter++;
            
            // Enable remove buttons if more than one row
            if ($('#itemsBody tr').length > 1) {
                $('#itemsBody tr:first .remove-item-btn').prop('disabled', false);
            }
        }
        
        // Driver selection for create modal
        $(document).on('click', '#driverListCreate .driver-item', function(e) {
            e.preventDefault();
            var driverName = $(this).text();
            var driverId = $(this).data('value');
            
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            $('#dropdownDriverCreate').text(driverName);
            $('#selectedDriverCreate').val(driverId);
            $('#driverError').text('');
        });
        
        // Product search in create modal
        $(document).on('keyup', '.product-search', function() {
            var searchTerm = $(this).val().toLowerCase();
            var index = $(this).data('index');
            var productList = $(this).siblings('.product-list[data-index="' + index + '"]');
            
            productList.find('.product-select-item').each(function() {
                var productText = $(this).text().toLowerCase();
                if (productText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Product selection in create modal
        $(document).on('click', '.product-select-item', function(e) {
            e.preventDefault();
            var index = $(this).data('index');
            var productId = $(this).data('value');
            var productName = $(this).data('name');
            
            // Update the dropdown button
            $('#productDropdown' + index).text(productName).attr('title', productName);
            
            // Set the hidden input value
            $(this).closest('tr').find('.product-id-input').val(productId);
            
            // Clear error
            $(this).closest('tr').find('.product-error').text('');
            
            // Highlight selected item
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
        });
        
        // Add item button
        $('#addItemBtn').on('click', function() {
            addItemRow();
        });
        
        // Remove item button
        $(document).on('click', '.remove-item-btn', function() {
            if ($('#itemsBody tr').length > 1) {
                var row = $(this).closest('tr');
                var rowIndex = parseInt(row.data('index'));
                
                row.remove();
                
                // Renumber rows and update indices
                $('#itemsBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    $(this).attr('data-index', index);
                    
                    // Update dropdown IDs and attributes
                    var dropdownBtn = $(this).find('.product-dropdown');
                    var dropdownId = 'productDropdown' + index;
                    dropdownBtn.attr('id', dropdownId)
                               .attr('aria-labelledby', dropdownId);
                    
                    // Update search and list indices
                    $(this).find('.product-search, .product-list').attr('data-index', index);
                    $(this).find('.product-select-item').attr('data-index', index);
                    
                    // Update input names
                    $(this).find('.product-id-input, .quantity-input').each(function() {
                        var name = $(this).attr('name');
                        if (name && name.includes('items[')) {
                            var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
                
                // Update counter
                itemCounter = $('#itemsBody tr').length;
                
                // Disable remove button on first row if only one row left
                if ($('#itemsBody tr').length === 1) {
                    $('#itemsBody tr:first .remove-item-btn').prop('disabled', true);
                }
            }
        });
        
        // Clear create modal when opened
        $('#createRequest').on('show.bs.modal', function () {
            initializeItemsTable();
            $('#createCountForm')[0].reset();
            $('#selectedDriverCreate').val('');
            $('#dropdownDriverCreate').text('{{ __('Select Driver') }}');
            $('#driverListCreate .driver-item').removeClass('active');
            $('#driverError, #itemsError').text('');
            $('#remarks').val('');
        });
        var products = @json($products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'unit_code' => $product->unit_code
            ];
        }));
        
        // Create a products lookup object
        var productsLookup = {};
        products.forEach(function(product) {
            productsLookup[product.id] = product;
        });
        
        // ============================================
        // EDIT COUNT MODAL (Admin fills counted quantities)
        // ============================================
        function getProductName(productId) {
            var product = productsLookup[productId];
            return product ? product.name : 'Product ' + productId;
        }

        // Handle edit count modal opening
        $(document).on('click', '.edit-count-btn', function(e) {
            e.preventDefault();
            var requestId = $(this).data('id');
            var requestData = $(this).data('request');
            
            // Parse JSON string if needed
            if (typeof requestData === 'string') {
                requestData = JSON.parse(requestData);
            }
            
            // Store current request ID
            currentRequestId = requestId;
            
            // Update modal title
            $('#editCountRequestId').text('(#' + requestId + ')');
            
            // Fill basic info
            $('#editCountDriverName').text(requestData.driver_name || 'N/A');
            $('#editCountRequestedAt').text(requestData.requested_at || requestData.created_at || 'N/A');
            $('#editCountRemarks').val(requestData.remarks || '');
            
            // Update form action
            var formAction = '{{ route("inventoryCounts.update", ":id") }}';
            formAction = formAction.replace(':id', requestId);
            $('#editCountForm').attr('action', formAction);
            
            // Clear and populate items table
            $('#editCountItemsBody').empty();
            $('#editCountItemsError').text('');
            
            if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
                requestData.items.forEach(function(item, index) {
                    var currentQty = item.current_quantity || item.quantity || 0;
                    var countedQty = item.counted_quantity || null; // Change from '' to null
                    
                    // Determine the value to display in the input field
                    // If countedQty is null/undefined/empty/0, pre-fill with currentQty
                    // Otherwise, use the saved countedQty
                    var displayCountedQty = '';
                    var hasSavedCount = false;
                    
                    if (countedQty !== null && countedQty !== undefined && countedQty !== '' && countedQty !== 0) {
                        // Already has a counted quantity saved in DB
                        displayCountedQty = countedQty;
                        hasSavedCount = true;
                    } else {
                        // No counted quantity saved yet, pre-fill with current quantity
                        displayCountedQty = currentQty;
                        hasSavedCount = false;
                    }
                    
                    // Calculate difference
                    var difference = hasSavedCount ? countedQty - currentQty : 0;
                    var diffClass = difference === 0 ? '' : (difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : '');
                    var diffSymbol = difference > 0 ? '+' : '';
                    
                    var productName = getProductName(item.product_id);

                    var row = `
                        <tr class="${hasSavedCount ? 'has-saved-count' : ''}">
                            <td class="align-middle text-center">${index + 1}</td>
                            <td class="align-middle">
                                <strong>${productName}</strong>
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge badge-info">${currentQty}</span>
                            </td>
                            <td class="align-middle">
                                <input type="number" 
                                    min="0" 
                                    class="form-control counted-quantity" 
                                    name="items[${index}][counted_quantity]" 
                                    value="${displayCountedQty}"
                                    data-current-qty="${currentQty}"
                                    data-original-counted="${countedQty || ''}"
                                    placeholder="Enter counted quantity"
                                    data-has-saved="${hasSavedCount ? 'true' : 'false'}"
                                    style="${hasSavedCount ? '' : 'background-color: #f0f8ff; border-left: 3px solid #007bff;'}">
                                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                                <input type="hidden" name="items[${index}][current_quantity]" value="${currentQty}">
                                <input type="hidden" name="items[${index}][original_counted]" value="${countedQty || ''}">
                            </td>
                            <td class="align-middle text-center">
                                <span class="difference-display ${diffClass}">${hasSavedCount ? diffSymbol + difference : '0'}</span>
                            </td>
                        </tr>
                    `;
                    $('#editCountItemsBody').append(row);
                });
            } else {
                $('#editCountItemsBody').html('<tr><td colspan="5" class="text-center">No items found</td></tr>');
            }
            
            // Add CSS for visual distinction if not already added
            if (!$('#editCountStyles').length) {
                var style = document.createElement('style');
                style.id = 'editCountStyles';
                style.textContent = `
                    .has-saved-count {
                        background-color: rgba(40, 167, 69, 0.05);
                    }
                    .has-saved-count .counted-quantity {
                        border-color: #28a745;
                        border-left: 3px solid #28a745 !important;
                    }
                    .counted-quantity[data-has-saved="true"] {
                        border-left: 3px solid #28a745 !important;
                    }
                    .counted-quantity[data-has-saved="false"] {
                        border-left: 3px solid #007bff !important;
                        background-color: #f8f9fa !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Show modal
            $('#editCountModal').modal('show');
        });

        // Calculate difference when counted quantity changes
        $(document).on('input', '.counted-quantity', function() {
            var currentQty = parseFloat($(this).data('current-qty')) || 0;
            var countedQty = parseFloat($(this).val()) || 0;
            var difference = countedQty - currentQty;
            
            var diffDisplay = $(this).closest('tr').find('.difference-display');
            var diffClass = difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : '';
            var diffSymbol = difference > 0 ? '+' : '';
            
            diffDisplay.removeClass('text-success text-danger').addClass(diffClass);
            diffDisplay.text(diffSymbol + difference);
        });

        // Handle edit count form submission
        $('#editCountForm').submit(function(e) {
            e.preventDefault();
            
            // Reset error
            $('#editCountItemsError').text('');
            
            // Validate counted quantities are not negative
            var hasError = false;
            var hasAnyCountedQty = false;
            
            $('.counted-quantity').each(function() {
                var val = $(this).val();
                
                // Check if at least one counted quantity is provided
                if (val !== '' && parseFloat(val) >= 0) {
                    hasAnyCountedQty = true;
                }
                
                // Validate the value itself
                if (val !== '' && parseFloat(val) < 0) {
                    $(this).addClass('is-invalid');
                    hasError = true;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (hasError) {
                $('#editCountItemsError').text('Counted quantities cannot be negative.');
                return false;
            }
            
            if (!hasAnyCountedQty) {
                $('#editCountItemsError').text('Please enter at least one counted quantity.');
                return false;
            }
            
            // Prepare form data
            var formData = $(this).serializeArray();
            
            // We need to convert items to proper format for Laravel
            var items = [];
            $('.counted-quantity').each(function(index) {
                var productId = $(this).closest('tr').find('input[name*="[product_id]"]').val();
                var currentQty = $(this).data('current-qty');
                var countedQty = $(this).val();
                var hasSaved = $(this).data('has-saved') === 'true';
                var originalCounted = $(this).data('original-counted');
                
                // Create item data
                var itemData = {
                    product_id: productId,
                    current_quantity: currentQty
                };
                
                // Only include counted_quantity if it has a value
                if (countedQty !== '') {
                    itemData.counted_quantity = countedQty;
                }
                
                items.push(itemData);
            });
            
            // Add items to form data
            formData = formData.filter(function(item) {
                return !item.name.startsWith('items[');
            });
            
            formData.push({
                name: 'items',
                value: JSON.stringify(items)
            });
            
            // Convert to object
            var formDataObj = {};
            $.each(formData, function() {
                formDataObj[this.name] = this.value;
            });
            
            // Submit via AJAX
            ShowLoad();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formDataObj,
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                },
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        $('#editCountModal').modal('hide');
                        showNotification('success', response.message || 'Stock out updated successfully.');
                        
                        // Refresh DataTable
                        if (table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        }
                    } else {
                        showNotification('error', response.message || 'An error occurred');
                        if (response.errors) {
                            for (var field in response.errors) {
                                if (response.errors.hasOwnProperty(field)) {
                                    showNotification('error', response.errors[field][0]);
                                }
                            }
                        }
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    var errorMessage = 'An error occurred';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    errorMessage = errors[field][0];
                                    break;
                                }
                            }
                        }
                    }
                    
                    showNotification('error', errorMessage);
                }
            });
        });

        // Clear edit count modal when closed
        $('#editCountModal').on('hidden.bs.modal', function () {
            $('#editCountForm')[0].reset();
            $('#editCountItemsBody').empty();
            $('#editCountRequestId').text('');
            $('#editCountItemsError').text('');
        });

        // Add reset button functionality (optional)
        $(document).on('click', '.reset-to-current-btn', function() {
            var input = $(this).closest('tr').find('.counted-quantity');
            var currentQty = input.data('current-qty');
            var hasSaved = input.data('has-saved') === 'true';
            
            // Only reset if there's no saved counted quantity
            if (!hasSaved) {
                input.val(currentQty);
                input.trigger('input'); // Trigger difference calculation
                showNotification('info', 'Reset to current quantity');
            } else {
                showNotification('warning', 'Cannot reset already saved counted quantity');
            }
        });

        // Add clear button functionality (optional)
        $(document).on('click', '.clear-count-btn', function() {
            var input = $(this).closest('tr').find('.counted-quantity');
            var hasSaved = input.data('has-saved') === 'true';
            
            // Only clear if there's no saved counted quantity
            if (!hasSaved) {
                input.val('');
                input.trigger('input'); // Trigger difference calculation
                showNotification('info', 'Cleared counted quantity');
            } else {
                showNotification('warning', 'Cannot clear already saved counted quantity');
            }
        });
        
        // ============================================
        // VIEW MODAL FUNCTIONS
        // ============================================
        
        // Handle view modal opening
        $(document).on('click', '.view-request-btn', function(e) {
            e.preventDefault();
            var requestId = $(this).data('id');
            var requestData = $(this).data('request');
            
            // Parse JSON string if needed
            if (typeof requestData === 'string') {
                requestData = JSON.parse(requestData);
            }
            
            // Store current request info
            currentRequestId = requestId;
            currentRequestStatus = requestData.status;
            
            // Update modal title with request ID
            $('#viewRequestId').text('(# ' + requestId + ')');
            $('#viewRequestIdText').text(requestId);
            
            // Fill view modal with data
            $('#viewDriverName').text(requestData.driver_name || 'N/A');
            $('#viewRemarks').text(requestData.remarks || 'No remarks');
            $('#viewCreatedAt').text(requestData.created_at || 'N/A');
            
            // Set status with badge
            var status = requestData.status;
            var badgeClass = getStatusBadgeClass(status);
            
            $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
            
            // Show items table
            if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
                var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Current Qty</th><th>Counted Qty</th><th>Difference</th></tr></thead><tbody>';
                
                var totalCurrent = 0;
                var totalCounted = 0;
                
                requestData.items.forEach(function(item, index) {
                    var currentQty = item.current_quantity || item.quantity || 0;
                    var countedQty = item.counted_quantity || null;
                    var difference = countedQty !== null ? countedQty - currentQty : null;
                    var diffClass = difference === null ? '' : (difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : '');
                    var diffSymbol = difference > 0 ? '+' : '';
                    var productName = item.product_name;
                    if (!productName && item.product_id) {
                        var product = productsLookup[item.product_id];
                        productName = product ? product.name : 'Product ' + item.product_id;
                    } else if (!productName) {
                        productName = 'Unknown Product';
                    }
                    
                    itemsHtml += '<tr>';
                    itemsHtml += '<td>' + (index + 1) + '</td>';
                    itemsHtml += '<td>' + productName + '</td>'; 
                    itemsHtml += '<td class="text-center">' + currentQty + '</td>';
                    itemsHtml += '<td class="text-center">' + (countedQty !== null ? countedQty : '-') + '</td>';
                    itemsHtml += '<td class="text-center ' + diffClass + '">' + (difference !== null ? diffSymbol + difference : '-') + '</td>';
                    itemsHtml += '</tr>';
                    
                    totalCurrent += parseInt(currentQty);
                    if (countedQty !== null) {
                        totalCounted += parseInt(countedQty);
                    }
                });
                
                var totalDifference = totalCounted - totalCurrent;
                var totalDiffClass = totalDifference > 0 ? 'text-success' : totalDifference < 0 ? 'text-danger' : '';
                var totalDiffSymbol = totalDifference > 0 ? '+' : '';
                
                itemsHtml += '</tbody>';
                itemsHtml += '<tfoot><tr>';
                itemsHtml += '<td colspan="2" class="text-right"><strong>Total:</strong></td>';
                itemsHtml += '<td class="text-center"><strong>' + totalCurrent + '</strong></td>';
                itemsHtml += '<td class="text-center"><strong>' + (totalCounted > 0 ? totalCounted : '-') + '</strong></td>';
                itemsHtml += '<td class="text-center ' + totalDiffClass + '"><strong>' + (totalCounted > 0 ? totalDiffSymbol + totalDifference : '-') + '</strong></td>';
                itemsHtml += '</tr></tfoot>';
                itemsHtml += '</table></div>';
                
                $('#viewItemsTable').html(itemsHtml);
            } else {
                $('#viewItemsTable').html('<div class="alert alert-info">No items found</div>');
            }
            
            // Show/hide approval section - ONLY show when status is approved
            if (status === 'approved') {
                $('#viewApprovedSection').show();
                $('#viewApprovedAtSection').show();
                $('#viewApprovedBy').text(requestData.approved_by || 'N/A');
                $('#viewApprovedAt').text(requestData.approved_at || 'N/A');
            } else {
                $('#viewApprovedSection').hide();
                $('#viewApprovedAtSection').hide();
            }
            
            // Show/hide rejection section - ONLY show when status is rejected
            if (status === 'rejected') {
                $('#viewRejectedSection').show();
                $('#viewRejectedAtSection').show();
                $('#viewRejectionReasonSection').show();
                $('#viewRejectedBy').text(requestData.rejected_by || 'N/A');
                $('#viewRejectedAt').text(requestData.rejected_at || 'N/A');
                $('#viewRejectionReason').text(requestData.rejection_reason || 'No reason provided');
            } else {
                $('#viewRejectedSection').hide();
                $('#viewRejectedAtSection').hide();
                $('#viewRejectionReasonSection').hide();
            }
            
            // Show/hide action buttons based on status and permissions
            var actionButtonsHtml = '';
            if (status === 'pending') {
                actionButtonsHtml = `
                    <button type="button" class="btn btn-success mr-2" id="approveFromViewBtn">
                        <i class="fa fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" id="rejectFromViewBtn">
                        <i class="fa fa-times"></i> Reject
                    </button>
                `;
            }
            $('#viewActionButtons').html(actionButtonsHtml);
            
            // Show modal
            $('#viewRequest').modal('show');
        });
        
        
        // ============================================
        // CREATE FORM VALIDATION AND SUBMISSION
        // ============================================
        
        // Validate create form
        $('#createCountForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverError, #itemsError').text('');
            $('.product-error, .quantity-error').text('');
            
            // Validate driver
            var driverId = $('#selectedDriverCreate').val();
            if (!driverId) {
                $('#driverError').text('Please select a driver');
                return false;
            }
            
            // Validate items
            var hasErrors = false;
            var items = [];
            var productIds = new Set(); // To check for duplicate products
            
            $('#itemsBody tr').each(function(index) {
                var productId = $(this).find('.product-id-input').val();
                var quantity = $(this).find('.quantity-input').val();
                var productError = $(this).find('.product-error');
                var quantityError = $(this).find('.quantity-error');
                
                // Reset errors
                productError.text('');
                quantityError.text('');
                
                // Validate product
                if (!productId) {
                    productError.text('Please select a product');
                    hasErrors = true;
                } else if (productIds.has(productId)) {
                    productError.text('Duplicate product selected');
                    hasErrors = true;
                } else {
                    productIds.add(productId);
                }
                
                // Validate quantity
                if (!quantity || quantity < 1) {
                    quantityError.text('Please enter a valid quantity (minimum 1)');
                    hasErrors = true;
                }
                
                // Add to items array only if valid
                if (productId && quantity && quantity >= 1) {
                    items.push({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity)
                    });
                }
            });
            
            if (items.length === 0) {
                $('#itemsError').text('Please add at least one valid item with product selected and quantity entered');
                hasErrors = true;
            }
            
            if (hasErrors) {
                // Highlight problematic rows
                $('#itemsBody tr').each(function(index) {
                    var productId = $(this).find('.product-id-input').val();
                    var quantity = $(this).find('.quantity-input').val();
                    
                    if (!productId) {
                        $(this).find('.product-dropdown').addClass('is-invalid');
                    } else {
                        $(this).find('.product-dropdown').removeClass('is-invalid');
                    }
                    
                    if (!quantity || quantity < 1) {
                        $(this).find('.quantity-input').addClass('is-invalid');
                    } else {
                        $(this).find('.quantity-input').removeClass('is-invalid');
                    }
                });
                return false;
            }
            
            // Prepare all form data including items
            var formData = {
                driver_id: driverId,
                items: items,
                remarks: $('#remarks').val(),
                _token: '{{ csrf_token() }}'
            };
            
            // Submit via AJAX
            ShowLoad();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        $('#createRequest').modal('hide');
                        showNotification('success', response.message);
                        
                        // Refresh DataTable
                        if (table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else if (table && typeof table.draw !== 'undefined') {
                            table.draw(false);
                        }
                    } else {
                        showNotification('error', response.message || 'An error occurred');
                        if (response.errors) {
                            for (var field in response.errors) {
                                if (response.errors.hasOwnProperty(field)) {
                                    showNotification('error', response.errors[field][0]);
                                }
                            }
                        }
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    var errorMessage = 'An error occurred';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    errorMessage = errors[field][0];
                                    break;
                                }
                            }
                        }
                    }
                    
                    showNotification('error', errorMessage);
                }
            });
        });
        
        // ============================================
        // APPROVE/REJECT FUNCTIONS
        // ============================================
        
        // Handle approve action from view modal
        $(document).on('click', '#approveFromViewBtn', function() {
            if (confirm('Are you sure you want to approve this inventory count request?')) {
                approveRequest(currentRequestId);
            }
        });

        // Handle reject action from view modal
        $(document).on('click', '#rejectFromViewBtn', function() {
            $('#rejectRequestId').val(currentRequestId);
            $('#rejection_reason_modal').val('');
            $('#rejectReasonModal').modal('show');
        });

        // Handle confirm reject from reject modal
        $('#confirmRejectBtn').on('click', function() {
            var rejectReason = $('#rejection_reason_modal').val();
            if (!rejectReason.trim()) {
                alert('Please provide a rejection reason');
                return;
            }
            
            rejectRequest($('#rejectRequestId').val(), rejectReason);
        });
        
        // ============================================
        // DELETE FUNCTIONALITY
        // ============================================
        
        // Handle delete action via AJAX
        $(document).on('submit', 'form[action*="destroy"]', function(e) {
            e.preventDefault();
            var form = $(this);
            
            if (confirm('Are you sure you want to delete this inventory count request?')) {
                ShowLoad();
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-HTTP-Method-Override': 'DELETE'
                    },
                    success: function(response) {
                        HideLoad();
                        if (response.success) {
                            showNotification('success', response.message);
                            
                            // Refresh the DataTable
                            if (table && typeof table.ajax !== 'undefined') {
                                table.ajax.reload(null, false);
                            } else if (table && typeof table.draw !== 'undefined') {
                                table.draw(false);
                            } else {
                                location.reload();
                            }
                        } else {
                            showNotification('error', response.message || 'An error occurred');
                        }
                    },
                    error: function(xhr) {
                        HideLoad();
                        showNotification('error', xhr.responseJSON?.message || 'An error occurred');
                    }
                });
            }
        });
        
        // ============================================
        // HELPER FUNCTIONS
        // ============================================
        
        // Helper function to approve request
        function approveRequest(requestId) {
            ShowLoad();
            
            var url = '{{ route("inventoryCounts.approve", ":id") }}';
            url = url.replace(':id', requestId);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        showNotification('success', response.message);
                        
                        // Close all modals
                        $('#viewRequest, #rejectReasonModal').modal('hide');
                        
                        // Refresh the DataTable
                        if (table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else if (table && typeof table.draw !== 'undefined') {
                            table.draw(false);
                        } else {
                            location.reload();
                        }
                        updateNotificationBadges();
                    } else {
                        showNotification('error', response.message || 'Failed to approve request');
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    var errorMessage = 'An error occurred while approving';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    errorMessage = errors[field][0];
                                    break;
                                }
                            }
                        }
                    }
                    
                    showNotification('error', errorMessage);
                }
            });
        }

        function updateNotificationBadges() {
            $.ajax({
                url: '{{ route("notification.counts") }}',
                type: 'GET',
                success: function(data) {
                    // Update Stock Requests badge
                    var stockRequestBadge = $('#stockRequestBadge');
                    if (data.pendingStockRequests > 0) {
                        stockRequestBadge.text(data.pendingStockRequests).show();
                    } else {
                        stockRequestBadge.hide();
                    }
                    
                    // Update Stock Counts badge (in case you want to update both)
                    var stockCountBadge = $('#stockCountBadge');
                    if (data.pendingStockCounts > 0) {
                        stockCountBadge.text(data.pendingStockCounts).show();
                    } else {
                        stockCountBadge.hide();
                    }
                }
            });
        }

        // Helper function to reject request
        function rejectRequest(requestId, rejectReason, modal = null) {
            ShowLoad();
            
            var url = '{{ route("inventoryCounts.reject", ":id") }}';
            url = url.replace(':id', requestId);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    rejection_reason: rejectReason
                },
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        showNotification('success', response.message);
                        
                        // Close all modals
                        if (modal) modal.modal('hide');
                        $('#viewRequest, #rejectReasonModal').modal('hide');
                        
                        // Refresh the DataTable
                        if (table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else if (table && typeof table.draw !== 'undefined') {
                            table.draw(false);
                        } else {
                            location.reload();
                        }
                    } else {
                        showNotification('error', response.message || 'Failed to reject request');
                        if (response.errors) {
                            for (var field in response.errors) {
                                if (response.errors.hasOwnProperty(field)) {
                                    showNotification('error', response.errors[field][0]);
                                }
                            }
                        }
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    var errorMessage = 'An error occurred while rejecting';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    errorMessage = errors[field][0];
                                    break;
                                }
                            }
                        }
                    }
                    
                    showNotification('error', errorMessage);
                }
            });
        }

        // Helper function for status badge class
        function getStatusBadgeClass(status) {
            switch (status) {
                case 'pending': return 'badge-pending';
                case 'approved': return 'badge-approved';
                case 'rejected': return 'badge-rejected';
                case 'cancelled': return 'badge-cancelled';
                default: return 'badge-info';
            }
        }

        // Helper function for notifications
        function showNotification(type, message) {
            // Check if toastr is available
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
            } 
            // Check if Swal (SweetAlert) is available
            else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
            // Fallback to regular alert
            else {
                alert(message);
            }
        }
        
        // Optional: Add better error handling for AJAX requests
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            if (jqxhr.status === 419) { // CSRF token mismatch
                showNotification('error', 'Your session has expired. Please refresh the page.');
            } else if (jqxhr.status === 500) {
                showNotification('error', 'Server error occurred. Please try again.');
            }
        });
        
        // ============================================
        // CLEAR MODALS ON CLOSE
        // ============================================
        
        // Clear edit modal when closed
        $('#editRequest').on('hidden.bs.modal', function () {
            $('#editRequestForm')[0].reset();
            $('#selectedDriverEdit').val('');
            $('#selectedProductEdit').val('');
            $('#dropdownDriverEdit').text('Select Driver');
            $('#dropdownProductEdit').text('Select Product');
            $('#driverListEdit .driver-item, #productListEdit .product-item').removeClass('active');
        });

        // Clear view modal when closed
        $('#viewRequest').on('hidden.bs.modal', function () {
            // Reset view modal fields
            $('#viewRequestId').text('');
            $('#viewRequestIdText, #viewDriverName, #viewRemarks, #viewCreatedAt, #viewApprovedBy, #viewApprovedAt, #viewRejectedBy, #viewRejectedAt, #viewRejectionReason').text('');
            $('#viewStatusBadge').html('');
            $('#viewActionButtons').html('');
            $('#viewItemsTable').html('');
            currentRequestId = null;
            currentRequestStatus = null;
        });

        // Clear reject modal when closed
        $('#rejectReasonModal').on('hidden.bs.modal', function () {
            $('#rejectRequestId').val('');
            $('#rejection_reason_modal').val('');
        });
    });

    // Keyboard shortcut for creating new request
    $(document).keyup(function(e) {
        if(e.altKey && e.keyCode == 78 && ($('#createRequest').length > 0)) {
            $('#createRequest').modal('show');
        }
    });
</script>

@endpush