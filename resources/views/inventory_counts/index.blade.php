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

    <!-- Create Count Modal -->
    <div id="createRequest" class="modal fade">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('Create Stock Count') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryCounts.store', 'enctype' => 'multipart/form-data', 'id' => 'createCountForm']) !!}
                    
                    <div class="row">
                        <!-- Driver Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="driver_id_create" class="col-form-label">{{ __('Driver') }} <span class="text-danger">*</span>:</label>
                                <select name="driver_id" id="driver_id_create" class="form-control driver-select" required>
                                    <option value="">{{ __('Select Driver') }}</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
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
                    
                    <!-- Warehouse Selection Info -->
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Note:</strong> After approval, stock will be returned to the selected warehouse. You can select a different warehouse for each product.
                    </div>
                    
                    <!-- Items Table -->
                    <div class="form-group">
                        <label class="col-form-label">{{ __('Items') }} <span class="text-danger">*</span>:</label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">Product <span class="text-danger">*</span></th>
                                        <th width="15%">Current Quantity</th>
                                        <th width="15%">Counted Qty <span class="text-danger">*</span></th>
                                        <th width="20%">Return Warehouse <span class="text-danger">*</span></th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Items will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-right">
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
                        <button type="submit" class="btn btn-primary rounded-0">{{ __('Submit Count Request') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Count Modal (Admin to fill counted quantities with batches) -->
    <div id="editCountModal" class="modal fade">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Fill Counted Quantities by Batch <span id="editCountRequestId"></span></h4>
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
                    
                    <!-- Warehouse Selection -->
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Note:</strong> After approval, stock will be returned to the selected warehouse. You can select a different warehouse for each batch.
                    </div>
                    
                    <!-- Items Table for Counting by Batch -->
                    <div class="form-group">
                        <label class="col-form-label"><strong>Count Items by Batch:</strong></label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="editCountItemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%">Product</th>
                                        <th width="15%">Batch Code</th>
                                        <th width="8%">Current Qty</th>
                                        <th width="12%">Counted Qty *</th>
                                        <th width="8%">Difference</th>
                                        <th width="20%">Return Warehouse *</th>
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
                        <button type="submit" class="btn btn-primary rounded-0">Save Counts</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- View Count Modal -->
    <div id="viewRequest" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Stock Count Details <span id="viewRequestId"></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="30%">Count ID:</th>
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
                        <h5>Counted Items by Batch</h5>
                        <div id="viewItemsTable"></div>
                    </div>
                    
                    <!-- Summary Section -->
                    <div class="mt-4" id="viewSummarySection" style="display: none;">
                        <h5>Count Summary</h5>
                        <div id="viewSummaryTable"></div>
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

    <!-- Reject Reason Modal -->
    <div id="rejectReasonModal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Stock Count</h5>
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
@endsection

@push('scripts')
<style>
    /* Dropdown base styles - ONLY for driver dropdown */
    .driver-dropdown {
        position: relative;
    }
    
    .driver-dropdown .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1060 !important;
        float: left;
        min-width: 100%;
        max-height: 300px !important;
        overflow-y: auto !important;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        font-size: 1rem;
        color: #212529;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 0.25rem;
    }
    
    .driver-dropdown .dropdown-toggle::after {
        margin-left: 10px;
    }
    
    .driver-dropdown .dropdown-menu::-webkit-scrollbar {
        width: 8px;
    }
    
    .driver-dropdown .dropdown-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .driver-dropdown .dropdown-menu::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .driver-dropdown .dropdown-menu::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .driver-dropdown .dropdown-menu .driver-item {
        cursor: pointer;
        padding: 8px 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .driver-dropdown .dropdown-menu .driver-item:hover,
    .driver-dropdown .dropdown-menu .driver-item.active {
        background-color: #007bff !important;
        color: white !important;
    }
    
    .driver-dropdown .dropdown-menu .driver-item:last-child {
        border-bottom: none;
    }
    
    /* Button styles */
    .btn-outline-primary {
        border-color: #007bff;
        color: #007bff;
    }
    
    .btn-outline-primary:hover {
        background-color: #007bff;
        color: #fff;
    }
    
    .btn-outline-secondary {
        border-color: #6c757d;
        color: #6c757d;
    }
    
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: #fff;
    }
    
    /* Badge styles */
    .badge {
        font-size: 85%;
    }
    
    .badge-pending {
        background-color: #ffc107;
        color: #212529;
        padding: 5px 10px;
    }
    
    .badge-approved {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
    }
    
    .badge-rejected {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
    }
    
    .badge-cancelled {
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
        padding: 5px 10px;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
    }
    
    .badge-danger {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
    }
    
    .small {
        font-size: 80%;
    }
    
    /* Table row styles */
    .item-row td, .edit-item-row td {
        vertical-align: middle !important;
        padding: 0.75rem !important;
    }
    
    /* Modal styles */
    .modal-body {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        padding: 1.5rem;
        flex: 1 1 auto;
        max-height: calc(100vh - 200px);
    }
    
    .modal {
        overflow: hidden !important;
    }
    
    .modal-dialog {
        overflow-y: initial !important;
        max-width: 90% !important;
    }

    .modal-content {
        border: none;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        border-bottom: 1px solid #dee2e6;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px 8px 0 0;
        padding: 1rem 1.5rem;
    }
    
    .modal-header .close {
        color: white;
        opacity: 0.8;
    }
    
    .modal-header .close:hover {
        opacity: 1;
    }
    
    .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .modal-footer {
        border-top: 1px solid #dee2e6;
        padding: 1rem 1.5rem;
    }
    
    /* Select2 Custom Styles - ONLY for product selects */
    .select2-container {
        width: 100% !important;
        display: block !important;
    }
    
    .select2-container--bootstrap .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 4px;
        min-height: 38px;
        background-color: #fff;
        display: flex;
        align-items: center;
    }
    
    .select2-container--bootstrap .select2-selection--single {
        height: 38px;
        line-height: 1.5;
    }
    
    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 36px;
        padding-left: 12px;
        padding-right: 30px;
    }
    
    .select2-container--bootstrap .select2-selection--single .select2-selection__arrow {
        height: 36px;
        position: absolute;
        right: 1px;
        top: 1px;
        width: 20px;
    }
    
    .select2-container--bootstrap .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }
    
    .select2-dropdown {
        border: 1px solid #80bdff;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 9999 !important;
    }
    
    .select2-results__option {
        padding: 8px 12px;
        font-size: 0.95rem;
    }
    
    .select2-results__option--highlighted {
        background-color: #007bff !important;
        color: white !important;
    }
    
    .select2-results__option[aria-selected="true"] {
        background-color: #f8f9fa;
        font-weight: 500;
    }
    
    .select2-search__field {
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        padding: 6px 12px !important;
        margin: 4px !important;
        width: calc(100% - 8px) !important;
        font-size: 0.95rem !important;
    }
    
    .select2-search__field:focus {
        border-color: #80bdff !important;
        outline: 0 !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25) !important;
    }
    
    /* Fix for Select2 inside modal */
    .modal .select2-container {
        z-index: 9999 !important;
    }
    
    .modal .select2-dropdown {
        z-index: 10000 !important;
    }
    
    /* Ensure Select2 container fits properly in table cell */
    td .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Fix for Select2 height consistency */
    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Table cell padding adjustment */
    .table td, .table th {
        padding: 0.75rem;
        vertical-align: middle;
    }
    
    /* Product select container */
    .product-select {
        width: 100% !important;
    }
    
    /* Form validation styles */
    .is-invalid .select2-selection {
        border-color: #dc3545 !important;
    }
    
    .is-valid .select2-selection {
        border-color: #28a745 !important;
    }
    
    /* Loading spinner */
    .fa-spinner {
        margin-right: 0.5rem;
    }
    
    /* Table responsive fixes */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Counted quantity input styling */
    .counted-quantity-input {
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .counted-quantity-input:focus {
        color: #495057;
        background-color: #fff;
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    .counted-quantity-input.is-invalid {
        border-color: #dc3545;
    }

    /* Make the table columns adjust properly */
    #itemsTable th:nth-child(4),
    #itemsTable td:nth-child(4) {
        min-width: 120px;
    }
    /* Difference display styling */
    .difference-positive {
        color: #28a745;
        font-weight: bold;
    }
    
    .difference-negative {
        color: #dc3545;
        font-weight: bold;
    }
    
    .difference-zero {
        color: #6c757d;
    }
    
    /* Add Item button styling */
    #addItemBtn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        padding: 0.5rem 1rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    #addItemBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
    
    /* Remove button styling */
    .remove-item-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        padding: 0.25rem 0.5rem;
    }
    
    .remove-item-btn:hover {
        transform: scale(1.05);
    }
    
    .remove-item-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Driver Select2 styles */
    .driver-select + .select2-container {
        width: 100% !important;
    }

    .driver-select + .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .driver-select + .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }

    .driver-select + .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    /* Batch information styling */
    .batch-info {
        background-color: #f8f9fa;
        border-left: 3px solid #17a2b8;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        border-radius: 4px;
    }
    
    .batch-code {
        font-weight: bold;
        color: #17a2b8;
    }
    
    .expiry-date {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    /* Summary table styling */
    .summary-table {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .summary-row:last-child {
        border-bottom: none;
    }
    
    .summary-label {
        font-weight: 600;
    }
    
    .summary-value {
        font-weight: 500;
    }

    /* Responsive fixes */
    @media (max-width: 768px) {
        .modal-dialog.modal-xl {
            max-width: 100% !important;
            margin: 0.5rem;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .table th,
        .table td {
            white-space: nowrap;
        }
    }

</style>

<script>
    $(document).ready(function () {
        // Initialize DataTable
        var table = window.LaravelDataTables["dataTableBuilder"] || $('.data-table').DataTable();
    
        if (table) {
            // Hide loading when DataTable initializes
            $(document).on('init.dt', function (e, settings) {
                if (e.namespace === 'dt') {
                    setTimeout(function() {
                        HideLoad();
                    }, 100);
                }
            });
            
            // Also hide loading on draw (for filters, pagination, etc.)
            table.on('draw', function () {
                setTimeout(function() {
                    HideLoad();
                }, 100);
                
                // Re-attach click handlers after table redraw
                attachModalHandlers();
            });
            
            // Force hide loading after DataTable is initialized
            setTimeout(function() {
                HideLoad();
            }, 1000);
        }
        
        // Function to attach modal handlers
        function attachModalHandlers() {
            console.log('Attaching modal handlers');
            
            // Remove any existing handlers to prevent duplicates
            $(document).off('click', '.view-request-btn');
            $(document).off('click', '.edit-count-btn');
            
            // Re-attach view modal handler
            $(document).on('click', '.view-request-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('View button clicked');
                
                var requestId = $(this).data('id');
                var requestData = $(this).data('request');
                
                console.log('View request ID:', requestId);
                console.log('View request data:', requestData);
                
                // Parse JSON string if needed
                if (typeof requestData === 'string') {
                    try {
                        requestData = JSON.parse(requestData);
                    } catch(e) {
                        console.error('Error parsing request data:', e);
                    }
                }
                
                // Store current request info
                currentRequestId = requestId;
                currentRequestStatus = requestData ? requestData.status : 'pending';
                
                // Update modal title with request ID
                $('#viewRequestId').text('(#' + requestId + ')');
                $('#viewRequestIdText').text(requestId);
                
                // Fill view modal with data
                $('#viewDriverName').text(requestData ? (requestData.driver_name || 'N/A') : 'N/A');
                $('#viewRemarks').text(requestData ? (requestData.remarks || 'No remarks') : 'No remarks');
                $('#viewCreatedAt').text(requestData ? (requestData.created_at || 'N/A') : 'N/A');
                
                // Set status with badge
                var status = requestData ? requestData.status : 'pending';
                var badgeClass = getStatusBadgeClass(status);
                
                $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
                
                // Show/hide sections based on initial status
                updateStatusSections(status, requestData || {});
                
                // Show loading in items table
                $('#viewItemsTable').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading transaction details...</p></div>');
                
                // Fetch full request data with batch details
                $.ajax({
                    url: '{{ route("inventoryCounts.withBatches", "") }}/' + requestId,
                    type: 'GET',
                    success: function(response) {
                        console.log('View data response:', response);
                        if (response.success) {
                            displayViewModalWithBatches(response.data);
                            // Update status sections with the full data
                            updateStatusSections(response.data.status, response.data);
                        } else {
                            displayViewModalBasic(requestData);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading view data:', error);
                        $('#viewItemsTable').html('<div class="text-center text-danger">Error loading transaction details. Please try again.</div>');
                        displayViewModalBasic(requestData);
                    }
                });
                
                // Show modal
                $('#viewRequest').modal('show');
            });
            
            // Re-attach edit modal handler
            $(document).on('click', '.edit-count-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Edit button clicked');
                
                var requestId = $(this).data('id');
                var requestData = $(this).data('request');
                
                console.log('Edit request ID:', requestId);
                console.log('Edit request data:', requestData);
                
                // Parse JSON string if needed
                if (typeof requestData === 'string') {
                    try {
                        requestData = JSON.parse(requestData);
                    } catch(e) {
                        console.error('Error parsing request data:', e);
                    }
                }
                
                // Store current request ID
                currentRequestId = requestId;
                
                // Update modal title
                $('#editCountRequestId').text('(#' + requestId + ')');
                
                // Fill basic info
                $('#editCountDriverName').text(requestData ? (requestData.driver_name || 'N/A') : 'N/A');
                $('#editCountRequestedAt').text(requestData ? (requestData.requested_at || requestData.created_at || 'N/A') : 'N/A');
                $('#editCountRemarks').val(requestData ? (requestData.remarks || '') : '');
                
                // Update form action
                var formAction = '{{ route("inventoryCounts.update", "") }}/' + requestId;
                $('#editCountForm').attr('action', formAction);
                
                // Clear and populate items table
                $('#editCountItemsBody').empty();
                $('#editCountItemsBody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading items...</td></tr>');
                $('#editCountItemsError').text('');
                
                // Fetch full request data with batch details
                $.ajax({
                    url: '{{ route("inventoryCounts.withBatches", "") }}/' + requestId,
                    type: 'GET',
                    success: function(response) {
                        console.log('Edit data response:', response);
                        if (response.success && response.data && response.data.items) {
                            var data = response.data;
                            var itemIndex = 0;
                            
                            $('#editCountItemsBody').empty();
                            
                            data.items.forEach(function(item) {
                                if (item.batches && Array.isArray(item.batches) && item.batches.length > 0) {
                                    // Display each batch as a separate row
                                    item.batches.forEach(function(batch) {
                                        var countedQty = batch.counted_quantity !== null ? batch.counted_quantity : '';
                                        var currentQty = batch.current_quantity || 0;
                                        var difference = countedQty !== '' ? countedQty - currentQty : 0;
                                        var diffClass = difference > 0 ? 'difference-positive' : (difference < 0 ? 'difference-negative' : 'difference-zero');
                                        var diffSymbol = difference > 0 ? '+' : '';
                                        
                                        var expiryInfo = batch.expiry_date ? 
                                            `<small class="text-muted d-block">Exp: ${formatDate(batch.expiry_date)}</small>` : '';
                                        
                                        var expiringClass = batch.is_expiring_soon ? 'expiring-soon' : '';
                                        
                                        // Get warehouse ID if already set
                                        var warehouseId = batch.warehouse_id || '';
                                        var warehouseSelectHtml = generateWarehouseDropdown(itemIndex, warehouseId);

                                        var row = `
                                            <tr>
                                                <td class="align-middle text-center">${itemIndex + 1}</td>
                                                <td class="align-middle">
                                                    <strong>${getProductName(item.product_id)}</strong>
                                                    ${expiryInfo}
                                                </td>
                                                <td class="align-middle">
                                                    <span class="badge badge-info ${expiringClass}">${batch.batch_code}</span>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="badge badge-secondary">${currentQty}</span>
                                                </td>
                                                <td class="align-middle">
                                                    <input type="number" 
                                                        min="0" 
                                                        class="form-control counted-quantity-input" 
                                                        name="batches[${itemIndex}][counted_quantity]" 
                                                        value="${countedQty}"
                                                        data-current-qty="${currentQty}"
                                                        data-batch-id="${batch.batch_id}"
                                                        placeholder="Enter counted qty"
                                                        required>
                                                    <input type="hidden" name="batches[${itemIndex}][batch_id]" value="${batch.batch_id}">
                                                    <input type="hidden" name="batches[${itemIndex}][product_id]" value="${item.product_id}">
                                                    <input type="hidden" name="batches[${itemIndex}][current_quantity]" value="${currentQty}">
                                                    <input type="hidden" name="batches[${itemIndex}][batch_code]" value="${batch.batch_code}">
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="difference-display ${diffClass}">${countedQty !== '' ? diffSymbol + difference : '-'}</span>
                                                </td>
                                                <td class="align-middle">
                                                    ${generateWarehouseDropdown(itemIndex, warehouseId)}
                                                </td>
                                            </tr>
                                        `;
                                        $('#editCountItemsBody').append(row);
                                        itemIndex++;
                                    });
                                } else {
                                    // If no batches found, create a placeholder row
                                    var row = `
                                        <tr>
                                            <td class="align-middle text-center">${itemIndex + 1}</td>
                                            <td class="align-middle">
                                                <strong>${getProductName(item.product_id)}</strong>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-warning">No batches found</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge badge-secondary">${item.current_quantity || 0}</span>
                                            </td>
                                            <td class="align-middle" colspan="3">
                                                <div class="text-muted">No batches available to count</div>
                                            </td>
                                        </tr>
                                    `;
                                    $('#editCountItemsBody').append(row);
                                    itemIndex++;
                                }
                            });
                            
                            // Initialize any Select2 for warehouse dropdowns if needed
                            if ($('.warehouse-select').length > 0) {
                                $('.warehouse-select').select2({
                                    theme: 'bootstrap',
                                    placeholder: 'Select Warehouse',
                                    allowClear: true,
                                    dropdownParent: $('#editCountModal .modal-content'),
                                    width: '100%'
                                });
                            }
                            
                        } else {
                            $('#editCountItemsBody').html('<tr><td colspan="7" class="text-center">No items found</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading edit data:', error);
                        $('#editCountItemsBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
                
                // Show modal
                $('#editCountModal').modal('show');
            });
        }
        
        // Call attachModalHandlers initially
        attachModalHandlers();
        
        var warehouses = {!! json_encode($warehouses ?? []) !!};
        var warehousesLookup = {};

        warehouses.forEach(function(warehouse) {
            warehousesLookup[warehouse.id] = warehouse;
        });

        function generateWarehouseDropdown(index, selectedWarehouseId = '') {
            var options = '<option value="">Select Warehouse</option>';
            warehouses.forEach(function(warehouse) {
                var selected = (warehouse.id == selectedWarehouseId) ? 'selected' : '';
                options += `<option value="${warehouse.id}" ${selected}>${warehouse.name}</option>`;
            });
            
            return `<select class="form-control warehouse-select" name="batches[${index}][warehouse_id]" data-index="${index}" required>
                ${options}
            </select>`;
        }

        // Store current request ID for actions
        var currentRequestId = null;
        var currentRequestStatus = null;
        
        // Items counter for create modal
        var itemCounter = 0;
        
        // Store driver inventory data
        var driverInventory = [];
        var selectedDriverId = null;
        
        // Products data from server
        var products = {!! json_encode($products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'unit_code' => $product->unit_code
            ];
        })) !!};

        // Create a products lookup object
        var productsLookup = {};
        products.forEach(function(product) {
            productsLookup[product.id] = product;
        });
        
        $('#driver_id_create').select2({
            theme: 'bootstrap',
            placeholder: 'Select Driver',
            allowClear: true,
            dropdownParent: $('body')
        });

        // ============================================
        // DRIVER SELECTION HANDLERS
        // ============================================
        
        // When driver is selected in create modal
        $('#driver_id_create').on('change', function() {
            var driverId = $(this).val();
            selectedDriverId = driverId;
            
            if (driverId) {
                // Clear existing items and show loading message
                $('#itemsBody').empty();
                var loadingRow = `
                    <tr>
                        <td colspan="4" class="text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading driver inventory...
                        </td>
                    </tr>
                `;
                $('#itemsBody').append(loadingRow);
                itemCounter = 0;
                
                // Load driver inventory
                loadDriverInventory(driverId);
            } else {
                // Clear product selects and show message
                $('#itemsBody').empty();
                itemCounter = 0;
                initializeItemsTable();
            }
        });

        // Function to load driver inventory
        function loadDriverInventory(driverId) {
            ShowLoad();
            
            $.ajax({
                url: '{{ route("inventoryCounts.getDriverInventory") }}',
                type: 'GET',
                data: { driver_id: driverId },
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    
                    if (response.success) {
                        // Store inventory data
                        driverInventory = response.inventory || [];
                        
                        console.log('Driver inventory loaded:', driverInventory); // Debug log
                        
                        if (driverInventory.length === 0) {
                            showNotification('info', 'This driver has no inventory to count');
                            // Clear the table and show message
                            $('#itemsBody').empty();
                            var emptyRow = `
                                <tr>
                                    <td colspan="4" class="text-center text-warning">
                                        <i class="fa fa-exclamation-triangle"></i> No inventory available for this driver
                                    </td>
                                </tr>
                            `;
                            $('#itemsBody').append(emptyRow);
                        } else {
                            // Initialize the table with products from driver's inventory
                            initializeItemsFromInventory();
                        }
                    } else {
                        showNotification('error', response.message || 'Failed to load driver inventory');
                        driverInventory = [];
                        $('#itemsBody').empty();
                        var errorRow = `
                            <tr>
                                <td colspan="4" class="text-center text-danger">
                                    <i class="fa fa-exclamation-circle"></i> Failed to load inventory
                                </td>
                            </tr>
                        `;
                        $('#itemsBody').append(errorRow);
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    showNotification('error', 'Error loading driver inventory');
                    console.error('Error:', xhr.responseJSON);
                    driverInventory = [];
                    $('#itemsBody').empty();
                    var errorRow = `
                        <tr>
                            <td colspan="4" class="text-center text-danger">
                                <i class="fa fa-exclamation-circle"></i> Error loading inventory
                            </td>
                        </tr>
                    `;
                    $('#itemsBody').append(errorRow);
                }
            });
        }

        // Initialize items from driver inventory
        function initializeItemsFromInventory() {
            $('#itemsBody').empty();
            itemCounter = 0;
            
            // Add a row for each product in driver's inventory
            driverInventory.forEach(function(product) {
                addItemRow(product);
            });
        }

        // ============================================
        // CREATE MODAL FUNCTIONS
        // ============================================

        // Initialize create modal items table
        function initializeItemsTable() {
            $('#itemsBody').empty();
            itemCounter = 0;
            
            if (selectedDriverId && driverInventory.length > 0) {
                initializeItemsFromInventory();
            } else if (selectedDriverId) {
                var emptyRow = `
                    <tr>
                        <td colspan="4" class="text-center text-warning">
                            <i class="fa fa-exclamation-triangle"></i> No inventory available for this driver
                        </td>
                    </tr>
                `;
                $('#itemsBody').append(emptyRow);
            } else {
                var emptyRow = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            <i class="fa fa-info-circle"></i> Please select a driver first
                        </td>
                    </tr>
                `;
                $('#itemsBody').append(emptyRow);
            }
        }
        function generateCreateWarehouseDropdown(index, selectedWarehouseId = '') {
            var options = '<option value="">Select Warehouse</option>';
            warehouses.forEach(function(warehouse) {
                var selected = (warehouse.id == selectedWarehouseId) ? 'selected' : '';
                options += `<option value="${warehouse.id}" ${selected}>${warehouse.name}</option>`;
            });
            
            return `<select class="form-control warehouse-select-create" name="items[${index}][warehouse_id]" data-index="${index}" required>
                ${options}
            </select>`;
        }

        function addItemRow(productData = null) {
            var rowIndex = itemCounter;
            var productId = productData ? productData.product_id : '';
            var productName = productData ? productData.product_name : '';
            var totalQuantity = productData ? productData.total_quantity : 0;
            var batchCount = productData && productData.batches ? productData.batches.length : 0;
            
            var productOptions = '<option value="">Select Product</option>';
            
            // Generate product options
            products.forEach(function(product) {
                var selected = (product.id == productId) ? 'selected' : '';
                productOptions += `<option value="${product.id}" data-name="${product.name}" data-code="${product.code}" ${selected}>${product.name} (${product.unit_code})</option>`;
            });
            
            var batchInfo = '';
            if (productData && productData.batches && productData.batches.length > 0) {
                batchInfo = '<div class="batch-info mt-1">';
                productData.batches.forEach(function(batch) {
                    var expiringClass = batch.is_expiring_soon ? 'expiring-soon' : '';
                    batchInfo += `<div class="batch-item"><span class="batch-code ${expiringClass}">${batch.batch_code}</span> - Qty: ${batch.quantity}`;
                    if (batch.formatted_expiry_date) {
                        batchInfo += ` <span class="expiry-date">(Exp: ${batch.formatted_expiry_date})</span>`;
                    }
                    batchInfo += '</div>';
                });
                batchInfo += '</div>';
            }
            
            var row = `
                <tr class="item-row" data-index="${rowIndex}">
                    <td class="align-middle text-center">${rowIndex + 1}</td>
                    <td>
                        <select class="form-control product-select" id="product_${rowIndex}" data-index="${rowIndex}" style="width: 100%;">
                            ${productOptions}
                        </select>
                        <input type="hidden" class="product-id-input" name="items[${rowIndex}][product_id]" value="${productId}">
                        <div class="text-danger product-error small"></div>
                        <div class="batch-details-container" id="batchDetails_${rowIndex}">
                            ${batchInfo}
                        </div>
                    </td>
                    <td class="align-middle text-center">
                        <span class="current-quantity" id="currentQuantity${rowIndex}">${totalQuantity}</span>
                        <div class="text-muted small" id="batchCount${rowIndex}">${batchCount} batch(es)</div>
                    </td>
                    <td class="align-middle">
                        <input type="number" 
                            min="0" 
                            class="form-control counted-quantity-input" 
                            name="items[${rowIndex}][counted_quantity]" 
                            id="countedQuantity${rowIndex}"
                            value=""
                            placeholder="Enter counted qty"
                            required>
                    </td>
                    <td class="align-middle">
                        ${generateCreateWarehouseDropdown(rowIndex, '')}
                    </td>
                    <td class="align-middle text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item-btn" ${rowIndex === 0 ? 'disabled' : ''}>
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#itemsBody').append(row);
            
            // Initialize Select2 for the new product select
            $('#product_' + rowIndex).select2({
                theme: 'bootstrap',
                placeholder: 'Search and select a product...',
                allowClear: true,
                dropdownParent: $('#createRequest .modal-content'),
                width: '100%'
            });
            
            // Initialize Select2 for warehouse dropdown
            $('.warehouse-select-create').select2({
                theme: 'bootstrap',
                placeholder: 'Select Warehouse',
                allowClear: true,
                dropdownParent: $('#createRequest .modal-content'),
                width: '100%'
            });
            
            itemCounter++;
            
            // Enable remove buttons if more than one row
            if ($('#itemsBody tr').length > 1) {
                $('#itemsBody tr:first .remove-item-btn').prop('disabled', false);
            }
        }

        // Product change event - fetch current inventory
        $(document).on('change', '.product-select', function(e) {
            var $this = $(this);
            var index = $this.data('index');
            var productId = $this.val();
            var $row = $this.closest('tr');
            
            // Set the hidden input value
            $row.find('.product-id-input').val(productId);
            $row.find('.product-error').text('');
            
            if (productId && selectedDriverId) {
                // Find the product in driver inventory
                var productInventory = driverInventory.find(p => p.product_id == productId);
                
                if (productInventory) {
                    // Update current quantity
                    $('#currentQuantity' + index).text(productInventory.total_quantity);
                    $('#batchCount' + index).text((productInventory.batches ? productInventory.batches.length : 0) + ' batch(es)');
                    
                    // Display batch information
                    var batchInfo = '';
                    if (productInventory.batches && productInventory.batches.length > 0) {
                        batchInfo = '<div class="batch-info mt-1">';
                        productInventory.batches.forEach(function(batch) {
                            var expiringClass = batch.is_expiring_soon ? 'expiring-soon' : '';
                            batchInfo += `<div class="batch-item"><span class="batch-code ${expiringClass}">${batch.batch_code}</span> - Qty: ${batch.quantity}`;
                            if (batch.formatted_expiry_date) {
                                batchInfo += ` <span class="expiry-date">(Exp: ${batch.formatted_expiry_date})</span>`;
                            }
                            batchInfo += '</div>';
                        });
                        batchInfo += '</div>';
                    } else {
                        batchInfo = '<div class="text-muted small mt-1">No batch details available</div>';
                    }
                    
                    $('#batchDetails_' + index).html(batchInfo);
                } else {
                    $('#currentQuantity' + index).text('0');
                    $('#batchCount' + index).text('0 batch(es)');
                    $('#batchDetails_' + index).html('<div class="text-muted small mt-1">Product not found in driver\'s inventory</div>');
                }
            } else {
                $('#currentQuantity' + index).text('-');
                $('#batchCount' + index).text('');
                $('#batchDetails_' + index).html('');
            }
        });

        // Add item button
        $('#addItemBtn').on('click', function() {
            if (!selectedDriverId) {
                showNotification('error', 'Please select a driver first');
                return;
            }
            
            // Add empty row
            addItemRow();
        });

        // Remove item button
        $(document).on('click', '.remove-item-btn', function() {
            if ($('#itemsBody tr').length > 1) {
                var row = $(this).closest('tr');
                var rowIndex = parseInt(row.data('index'));
                
                // Destroy Select2 instances
                $('#product_' + rowIndex).select2('destroy');
                row.find('.warehouse-select-create').select2('destroy');
                row.remove();
                
                // Renumber rows and update indices
                $('#itemsBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    $(this).attr('data-index', index);
                    
                    // Update Select2 IDs for product
                    var $productSelect = $(this).find('.product-select');
                    $productSelect.attr('id', 'product_' + index);
                    $productSelect.data('index', index);
                    
                    // Reinitialize Select2 for product
                    $productSelect.select2({
                        theme: 'bootstrap',
                        placeholder: 'Search and select a product...',
                        allowClear: true,
                        dropdownParent: $('#createRequest .modal-content'),
                        width: '100%'
                    });
                    
                    // Update warehouse select name
                    var $warehouseSelect = $(this).find('.warehouse-select-create');
                    var oldName = $warehouseSelect.attr('name');
                    if (oldName) {
                        var newName = oldName.replace(/items\[\d+\]/, 'items[' + index + ']');
                        $warehouseSelect.attr('name', newName);
                    }
                    $warehouseSelect.attr('data-index', index);
                    
                    // Reinitialize Select2 for warehouse
                    $warehouseSelect.select2({
                        theme: 'bootstrap',
                        placeholder: 'Select Warehouse',
                        allowClear: true,
                        dropdownParent: $('#createRequest .modal-content'),
                        width: '100%'
                    });
                    
                    // Update input names for product_id
                    $(this).find('.product-id-input').each(function() {
                        var name = $(this).attr('name');
                        if (name && name.includes('items[')) {
                            var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                    
                    // Update counted quantity ID
                    $(this).find('.counted-quantity-input').attr('id', 'countedQuantity' + index);
                    
                    // Update current quantity ID
                    $(this).find('.current-quantity').attr('id', 'currentQuantity' + index);
                    
                    // Update batch count ID
                    $(this).find('#batchCount' + rowIndex).attr('id', 'batchCount' + index);
                    
                    // Update batch details container ID
                    $(this).find('#batchDetails_' + rowIndex).attr('id', 'batchDetails_' + index);
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
            selectedDriverId = null;
            driverInventory = [];
            $('#createCountForm')[0].reset();
            $('#driver_id_create').val('').trigger('change');
            $('#driverError, #itemsError').text('');
            $('#remarks').val('');
            
            // Initialize with empty table showing message
            initializeItemsTable();
        });

        // Validate create form
        $('#createCountForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverError, #itemsError').text('');
            $('.product-error').text('');
            $('.counted-quantity-input').removeClass('is-invalid');
            $('.warehouse-select-create').removeClass('is-invalid');
            
            // Validate driver
            var driverId = $('#driver_id_create').val();
            if (!driverId) {
                $('#driverError').text('Please select a driver');
                return false;
            }
            
            // Validate items
            var hasErrors = false;
            var items = [];
            var productIds = new Set();
            var hasAnyCountedQty = false;
            
            $('#itemsBody tr.item-row').each(function(index) {
                var productId = $(this).find('.product-select').val();
                var warehouseId = $(this).find('.warehouse-select-create').val();
                var countedQty = $(this).find('.counted-quantity-input').val();
                var $row = $(this);
                var productError = $(this).find('.product-error');
                
                // Reset errors
                productError.text('');
                
                // Validate product
                if (!productId) {
                    productError.text('Please select a product');
                    hasErrors = true;
                    return;
                }
                
                if (productIds.has(productId)) {
                    productError.text('Duplicate product selected');
                    hasErrors = true;
                    return;
                }
                
                productIds.add(productId);
                
                // Validate warehouse
                if (!warehouseId) {
                    $(this).find('.warehouse-select-create').addClass('is-invalid');
                    hasErrors = true;
                    return;
                }
                
                // Validate counted quantity
                if (countedQty === '' || parseFloat(countedQty) < 0) {
                    $(this).find('.counted-quantity-input').addClass('is-invalid');
                    hasErrors = true;
                    return;
                }
                
                if (parseFloat(countedQty) >= 0) {
                    hasAnyCountedQty = true;
                }
                
                // Get batch information from the batch details container
                var batchItems = [];
                var batchContainer = $('#batchDetails_' + index);
                
                if (batchContainer.length && batchContainer.find('.batch-item').length > 0) {
                    // Find the product in driver inventory to get batch information
                    var productInventory = driverInventory.find(p => p.product_id == productId);
                    
                    if (productInventory && productInventory.batches && productInventory.batches.length > 0) {
                        // Create batch entries
                        productInventory.batches.forEach(function(batch) {
                            batchItems.push({
                                batch_id: batch.batch_id,
                                batch_code: batch.batch_code,
                                current_quantity: batch.quantity,
                                counted_quantity: parseFloat(countedQty)
                            });
                        });
                    }
                }
                
                // Add to items array with warehouse_id
                var itemData = {
                    product_id: parseInt(productId),
                    counted_quantity: parseFloat(countedQty),
                    warehouse_id: parseInt(warehouseId) // Add warehouse_id to each product
                };
                
                // Include batch information if available
                if (batchItems.length > 0) {
                    itemData.batches = batchItems;
                }
                
                items.push(itemData);
            });
            
            if (items.length === 0) {
                $('#itemsError').text('Please add at least one product to count');
                hasErrors = true;
            }
            
            if (!hasAnyCountedQty) {
                $('#itemsError').text('Please enter at least one counted quantity.');
                hasErrors = true;
            }
            
            if (hasErrors) {
                return false;
            }
            
            // Prepare all form data
            var postData = {
                driver_id: driverId,
                items: items,
                remarks: $('#remarks').val(),
                _token: '{{ csrf_token() }}'
            };
            
            console.log('Sending data:', postData);
            
            // Submit via AJAX
            ShowLoad();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        $('#createRequest').modal('hide');
                        showNotification('success', response.message);
                        if (table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        }
                    } else {
                        showNotification('error', response.message || 'An error occurred');
                    }
                },
                error: function(xhr) {
                    HideLoad();
                    var errorMsg = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showNotification('error', errorMsg);
                    console.error('Error:', xhr.responseJSON);
                }
            });
        });
        
        // ============================================
        // EDIT COUNT MODAL (Admin fills counted quantities by batch)
        // ============================================
        
        // Helper function to get product name
        function getProductName(productId) {
            var product = productsLookup[productId];
            return product ? product.name : 'Product ' + productId;
        }

        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            var date = new Date(dateString);
            var day = date.getDate().toString().padStart(2, '0');
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var year = date.getFullYear();
            return day + '/' + month + '/' + year;
        }

        // Calculate difference when counted quantity changes
        $(document).on('input', '.counted-quantity-input', function() {
            var currentQty = parseFloat($(this).data('current-qty')) || 0;
            var countedQty = parseFloat($(this).val()) || 0;
            var difference = countedQty - currentQty;
            
            var diffDisplay = $(this).closest('tr').find('.difference-display');
            diffDisplay.removeClass('difference-positive difference-negative difference-zero');
            
            if (countedQty === 0 && $(this).val() === '') {
                diffDisplay.text('-');
            } else {
                var diffClass = difference > 0 ? 'difference-positive' : (difference < 0 ? 'difference-negative' : 'difference-zero');
                var diffSymbol = difference > 0 ? '+' : '';
                diffDisplay.addClass(diffClass);
                diffDisplay.text(diffSymbol + difference);
            }
        });

        // Handle edit count form submission
        $('#editCountForm').submit(function(e) {
            e.preventDefault();
            
            // Reset error
            $('#editCountItemsError').text('');
            
            // Validate counted quantities and warehouse selections
            var hasError = false;
            var hasAnyCountedQty = false;
            var batches = [];
            
            $('.counted-quantity-input').each(function() {
                var val = $(this).val();
                var batchId = $(this).data('batch-id');
                var productId = $(this).closest('tr').find('input[name*="[product_id]"]').val();
                var currentQty = $(this).data('current-qty');
                var batchCode = $(this).closest('tr').find('input[name*="[batch_code]"]').val();
                
                // Get warehouse selection
                var $row = $(this).closest('tr');
                var warehouseSelect = $row.find('.warehouse-select');
                var warehouseId = warehouseSelect.val();
                
                // Validate warehouse selection if quantity is provided
                if (val !== '' && parseFloat(val) >= 0) {
                    hasAnyCountedQty = true;
                    
                    if (!warehouseId) {
                        warehouseSelect.addClass('is-invalid');
                        hasError = true;
                    } else {
                        warehouseSelect.removeClass('is-invalid');
                        
                        // Add to batches array with warehouse_id
                        batches.push({
                            batch_id: batchId,
                            product_id: parseInt(productId),
                            counted_quantity: parseFloat(val),
                            current_quantity: currentQty,
                            warehouse_id: parseInt(warehouseId),
                            batch_code: batchCode
                        });
                    }
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
                $('#editCountItemsError').text('Please select a warehouse for each counted batch.');
                return false;
            }
            
            if (!hasAnyCountedQty) {
                $('#editCountItemsError').text('Please enter at least one counted quantity.');
                return false;
            }
            
            // Prepare form data
            var formData = {
                batches: batches,
                remarks: $('#editCountRemarks').val(),
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
            };
            
            console.log('Sending count data:', formData);
            
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
                        $('#editCountModal').modal('hide');
                        showNotification('success', response.message || 'Stock count updated successfully.');
                        
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
        
        // ============================================
        // VIEW MODAL FUNCTIONS
        // ============================================
        
        // Helper function to update status sections
        function updateStatusSections(status, data) {
            // Show/hide approval section
            if (status === 'approved') {
                $('#viewApprovedSection').show();
                $('#viewApprovedAtSection').show();
                $('#viewRejectedSection').hide();
                $('#viewRejectedAtSection').hide();
                $('#viewRejectionReasonSection').hide();
                
                $('#viewApprovedBy').text(data.approved_by || 'N/A');
                $('#viewApprovedAt').text(data.approved_at || 'N/A');
            } 
            // Show/hide rejection section
            else if (status === 'rejected') {
                $('#viewApprovedSection').hide();
                $('#viewApprovedAtSection').hide();
                $('#viewRejectedSection').show();
                $('#viewRejectedAtSection').show();
                $('#viewRejectionReasonSection').show();
                
                $('#viewRejectedBy').text(data.rejected_by || 'N/A');
                $('#viewRejectedAt').text(data.rejected_at || 'N/A');
                $('#viewRejectionReason').text(data.rejection_reason || 'No reason provided');
            } 
            // For pending or other statuses
            else {
                $('#viewApprovedSection').hide();
                $('#viewApprovedAtSection').hide();
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
        }

        function displayViewModalWithBatches(data) {
            // Show items table with batch details including warehouse
            var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Batch Code</th><th>Expiry Date</th><th>Current Qty</th><th>Counted Qty</th><th>Return Warehouse</th><th>Difference</th></tr></thead><tbody>';
            
            var totalCurrent = 0;
            var totalCounted = 0;
            var rowCounter = 1;
            var productsWithVariance = 0;
            var batchesCounted = 0;
            
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(function(item) {
                    var itemHasVariance = false;
                    
                    if (item.batches && Array.isArray(item.batches) && item.batches.length > 0) {
                        item.batches.forEach(function(batch) {
                            // Ensure we're working with numbers
                            var currentQty = parseFloat(batch.current_quantity) || 0;
                            var countedQty = batch.counted_quantity !== null && batch.counted_quantity !== undefined ? parseFloat(batch.counted_quantity) : null;
                            console.log('batch item :', batch);
                            // Get warehouse info
                            var warehouseName = batch.warehouse;
                          
                            
                            // Track if batch has been counted
                            if (countedQty !== null && !isNaN(countedQty)) {
                                batchesCounted++;
                            }
                            
                            // Calculate difference only if counted quantity exists
                            var difference = null;
                            var diffClass = '';
                            var diffDisplay = '-';
                            
                            if (countedQty !== null && !isNaN(countedQty)) {
                                difference = countedQty - currentQty;
                                
                                if (difference > 0) {
                                    diffClass = 'difference-positive';
                                    diffDisplay = '+' + difference;
                                } else if (difference < 0) {
                                    diffClass = 'difference-negative';
                                    diffDisplay = difference;
                                } else {
                                    diffClass = 'difference-zero';
                                    diffDisplay = '0';
                                }
                                
                                // Check if this batch has variance
                                if (difference !== 0) {
                                    itemHasVariance = true;
                                }
                                
                                // Add to totals
                                totalCounted += countedQty;
                            }
                            
                            // Always add current quantity to total
                            totalCurrent += currentQty;
                            
                            var expiryDate = batch.expiry_date ? formatDate(batch.expiry_date) : 'N/A';
                            var expiringClass = batch.is_expiring_soon ? 'expiring-soon' : '';
                            
                            itemsHtml += '<tr>';
                            itemsHtml += '<td class="text-center">' + (rowCounter++) + '</td>';
                            itemsHtml += '<td>' + (item.product_name || 'Unknown Product') + '</td>';
                            itemsHtml += '<td><span class="badge badge-info ' + expiringClass + '">' + (batch.batch_code || 'N/A') + '</span></td>';
                            itemsHtml += '<td>' + expiryDate + '</td>';
                            itemsHtml += '<td class="text-center">' + currentQty + '</td>';
                            itemsHtml += '<td class="text-center">' + (countedQty !== null && !isNaN(countedQty) ? countedQty : '-') + '</td>';
                            itemsHtml += '<td><span class="badge badge-primary">' + warehouseName + '</span></td>';
                            itemsHtml += '<td class="text-center ' + diffClass + '">' + diffDisplay + '</td>';
                            itemsHtml += '</tr>';
                        });
                        
                        // If any batch in this product has variance, count it
                        if (itemHasVariance) {
                            productsWithVariance++;
                        }
                    } else {
                        // If no batches, show product without batch info
                        var currentQty = parseFloat(item.current_quantity) || 0;
                        
                        itemsHtml += '<tr>';
                        itemsHtml += '<td class="text-center">' + (rowCounter++) + '</td>';
                        itemsHtml += '<td>' + (item.product_name || 'Unknown Product') + '</td>';
                        itemsHtml += '<td colspan="2" class="text-center text-muted">No batch information</td>';
                        itemsHtml += '<td class="text-center">' + currentQty + '</td>';
                        itemsHtml += '<td class="text-center">-</td>';
                        itemsHtml += '<td class="text-center">-</td>';
                        itemsHtml += '<td class="text-center">-</td>';
                        itemsHtml += '</tr>';
                        
                        totalCurrent += currentQty;
                    }
                });
            }
            
            itemsHtml += '</tbody>';
            
            // Calculate total difference
            var totalDifference = totalCounted - totalCurrent;
            var totalDiffClass = '';
            var totalDiffDisplay = '-';
            
            if (totalCounted > 0) {
                if (totalDifference > 0) {
                    totalDiffClass = 'difference-positive';
                    totalDiffDisplay = '+' + totalDifference;
                } else if (totalDifference < 0) {
                    totalDiffClass = 'difference-negative';
                    totalDiffDisplay = totalDifference;
                } else {
                    totalDiffClass = 'difference-zero';
                    totalDiffDisplay = '0';
                }
            }
            
            // Add footer with totals
            itemsHtml += '<tfoot>';
            itemsHtml += '<tr class="table-info">';
            itemsHtml += '<td colspan="5" class="text-right"><strong>Total:</strong></td>';
            itemsHtml += '<td class="text-center"><strong>' + (totalCounted > 0 ? totalCounted : '-') + '</strong></td>';
            itemsHtml += '<td></td>';
            itemsHtml += '<td class="text-center ' + totalDiffClass + '"><strong>' + totalDiffDisplay + '</strong></td>';
            itemsHtml += '</tr>';
            itemsHtml += '</tfoot>';
            itemsHtml += '</table></div>';
            
            $('#viewItemsTable').html(itemsHtml);
            
            // Show summary section
            $('#viewSummarySection').show();
            
            // Update summary table to include warehouse distribution
            var warehouseSummary = {};
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(function(item) {
                    if (item.batches && Array.isArray(item.batches)) {
                        item.batches.forEach(function(batch) {
                            if (batch.counted_quantity && batch.warehouse_id) {
                                var warehouseId = batch.warehouse_id;
                                var warehouseName = warehousesLookup[warehouseId] ? warehousesLookup[warehouseId].name : 'Warehouse ' + warehouseId;
                                
                                if (!warehouseSummary[warehouseName]) {
                                    warehouseSummary[warehouseName] = 0;
                                }
                                warehouseSummary[warehouseName] += batch.counted_quantity;
                            }
                        });
                    }
                });
            }
            
            var summaryHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            summaryHtml += '<thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';
            summaryHtml += '<tr><td>Total Products</td><td>' + (data.items ? data.items.length : 0) + '</td></tr>';
            summaryHtml += '<tr><td>Batches Counted</td><td>' + batchesCounted + '</td></tr>';
            
            // Add warehouse distribution
            if (Object.keys(warehouseSummary).length > 0) {
                summaryHtml += '<tr><td colspan="2"><strong>Warehouse Distribution:</strong></td></tr>';
                for (var warehouse in warehouseSummary) {
                    summaryHtml += '<tr><td class="pl-4">' + warehouse + '</td><td>' + warehouseSummary[warehouse] + ' units</td></tr>';
                }
            }
            
            // Add variance percentage
            var variancePercentage = totalCurrent > 0 ? ((totalDifference / totalCurrent) * 100).toFixed(2) : 0;
            var percentageClass = totalDifference > 0 ? 'difference-positive' : (totalDifference < 0 ? 'difference-negative' : '');
            
            summaryHtml += '<tr><td>Variance Percentage</td><td class="' + percentageClass + '">' + 
                        (totalDifference !== 0 ? (totalDifference > 0 ? '+' : '') + variancePercentage + '%' : '0%') + '</td></tr>';
            
            // Add status summary
            var statusSummary = '';
            if (totalDifference === 0) {
                statusSummary = '<span class="badge badge-success">Perfect Match</span>';
            } else if (totalDifference > 0) {
                statusSummary = '<span class="badge badge-warning">Surplus (+' + totalDifference + ')</span>';
            } else {
                statusSummary = '<span class="badge badge-danger">Shortage (' + totalDifference + ')</span>';
            }
            
            summaryHtml += '<tr><td>Overall Status</td><td>' + statusSummary + '</td></tr>';
            summaryHtml += '<tr><td>Total Current</td><td>' + totalCurrent + '</td></tr>';
            summaryHtml += '<tr><td>Total Counted</td><td>' + (totalCounted > 0 ? totalCounted : '-') + '</td></tr>';
            summaryHtml += '<tr><td>Net Difference</td><td class="' + totalDiffClass + '"><strong>' + totalDiffDisplay + '</strong></td></tr>';
            summaryHtml += '</tbody></table></div>';
            
            $('#viewSummaryTable').html(summaryHtml);
        }

        function displayViewModalBasic(requestData) {
            // Show items table without batch info
            var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Quantity</th></tr></thead><tbody>';
            
            var totalQuantity = 0;
            
            if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
                requestData.items.forEach(function(item, index) {
                    var quantity = item.quantity || 0;
                    var productName = item.product_name;
                    if (!productName && item.product_id) {
                        productName = getProductName(item.product_id);
                    } else if (!productName) {
                        productName = 'Unknown Product';
                    }
                    
                    itemsHtml += '<tr>';
                    itemsHtml += '<td>' + (index + 1) + '</td>';
                    itemsHtml += '<td>' + productName + '</td>'; 
                    itemsHtml += '<td class="text-center">' + quantity + '</td>';
                    itemsHtml += '</tr>';
                    
                    totalQuantity += parseInt(quantity);
                });
            } else if (requestData.product_id && requestData.quantity) {
                var productName = getProductName(requestData.product_id);
                itemsHtml += '<tr>';
                itemsHtml += '<td>1</td>';
                itemsHtml += '<td>' + (requestData.product_name || productName) + '</td>'; 
                itemsHtml += '<td class="text-center">' + requestData.quantity + '</td>';
                itemsHtml += '</tr>';
                totalQuantity = requestData.quantity;
            }
            
            itemsHtml += '</tbody>';
            itemsHtml += '<tfoot><tr>';
            itemsHtml += '<td colspan="2" class="text-right"><strong>Total:</strong></td>';
            itemsHtml += '<td class="text-center"><strong>' + totalQuantity + '</strong></td>';
            itemsHtml += '</tr></tfoot>';
            itemsHtml += '</table></div>';
            
            $('#viewItemsTable').html(itemsHtml);
            $('#viewSummarySection').hide();
        }
        
        // ============================================
        // APPROVE/REJECT FUNCTIONS
        // ============================================
        
        // Handle approve action from view modal
        $(document).on('click', '#approveFromViewBtn', function() {
            if (confirm('Are you sure you want to approve this stock count?')) {
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
            
            if (confirm('Are you sure you want to delete this stock count request?')) {
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
                    
                    // Update Stock Counts badge
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
        function rejectRequest(requestId, rejectReason) {
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
        
        // Clear view modal when closed
        $('#viewRequest').on('hidden.bs.modal', function () {
            // Reset view modal fields
            $('#viewRequestId').text('');
            $('#viewRequestIdText, #viewDriverName, #viewRemarks, #viewCreatedAt, #viewApprovedBy, #viewApprovedAt, #viewRejectedBy, #viewRejectedAt, #viewRejectionReason').text('');
            $('#viewStatusBadge').html('');
            $('#viewActionButtons').html('');
            $('#viewItemsTable').html('');
            $('#viewSummaryTable').html('');
            $('#viewSummarySection').hide();
            currentRequestId = null;
            currentRequestStatus = null;
        });

        // Clear reject modal when closed
        $('#rejectReasonModal').on('hidden.bs.modal', function () {
            $('#rejectRequestId').val('');
            $('#rejection_reason_modal').val('');
        });
    });

    // Keyboard shortcut for creating new count
    $(document).keyup(function(e) {
        if(e.altKey && e.keyCode == 78 && ($('#createRequest').length > 0)) {
            $('#createRequest').modal('show');
        }
    });
</script>
@endpush