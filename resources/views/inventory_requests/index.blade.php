@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Stock Requests') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('Stock Requests') }}
                        </div>
                        <div class="card-body">
                            @include('inventory_requests.table')
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
                    <h4 class="modal-title h6">{{ __('Create Stock Request') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryRequests.store', 'enctype' => 'multipart/form-data', 'id' => 'createRequestForm']) !!}
                    
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
                    
                    <!-- Items Table -->
                    <div class="form-group">
                        <label class="col-form-label">{{ __('Items') }} <span class="text-danger">*</span>:</label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Product <span class="text-danger">*</span></th>
                                        <th width="10%">Quantity <span class="text-danger">*</span></th>
                                        <th width="50%">Batch Allocation</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Items will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right">
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
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('Edit Stock Request') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => ['inventoryRequests.update', ':id'], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'id' => 'editRequestForm']) !!}
                    
                    <!-- Add a hidden field to track if we want to save and approve -->
                    <input type="hidden" name="save_and_approve" id="saveAndApprove" value="0">
                    
                    <div class="row">
                        <!-- Driver Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="driver_id_edit" class="col-form-label">{{ __('Driver') }} <span class="text-danger">*</span>:</label>
                                <select name="driver_id" id="driver_id_edit" class="form-control driver-select" required>
                                    <option value="">{{ __('Select Driver') }}</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                                <div class="text-danger" id="driverEditError"></div>
                            </div>
                        </div>
                        
                        <!-- Remarks -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remarks" class="col-form-label">{{ __('Remarks') }} ({{ __('Optional') }}):</label>
                                <textarea class="form-control" name="remarks" id="remarksEdit" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table for Edit -->
                    <div class="form-group">
                        <label class="col-form-label">{{ __('Items') }} <span class="text-danger">*</span>:</label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="editItemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Product <span class="text-danger">*</span></th>
                                        <th width="10%">Quantity <span class="text-danger">*</span></th>
                                        <th width="50%">Batch Allocation</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsBody">
                                    <!-- Items will be populated here -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right">
                                            <button type="button" class="btn btn-success btn-sm" id="addEditItemBtn">
                                                <i class="fa fa-plus"></i> Add Item
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="text-danger" id="editItemsError"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-0" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-0">{{ __('Update Request') }}</button>
                        
                        <!-- Add Save & Approve button for admins -->
                        @if(auth()->user()->hasRole('admin'))
                            <button type="button" class="btn btn-success rounded-0" id="saveAndApproveBtn">
                                {{ __('Save & Approve') }}
                            </button>
                        @endif
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
                    <h4 class="modal-title h6">Stock Request Details <span id="viewRequestId"></span></h4>
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
                        <h5>Requested Items</h5>
                        <div id="viewItemsTable"></div>
                    </div>
                    
                    <!-- Batch Allocations Section -->
                    <div class="mt-4" id="viewBatchAllocationsSection" style="display: none;">
                        <h5>Batch Allocations</h5>
                        <div id="viewBatchAllocationsTable"></div>
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

    <!-- Reject Reason Modal (for reject action) -->
    <div id="rejectReasonModal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
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
    // Auto-open script for inventory requests
    $(document).ready(function() {
        console.log('Inventory Requests page loaded');
        
        // Check URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const viewRequestId = urlParams.get('view_request');
        
        if (viewRequestId) {
            console.log('Found view_request parameter:', viewRequestId);
            localStorage.setItem('pendingInventoryRequestModal', viewRequestId);
            
            // Clean up URL
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        // Hook into DataTable initialization
        if (typeof $.fn.DataTable !== 'undefined') {
            $(document).on('init.dt', function(e, settings) {
                console.log('Inventory Requests DataTable initialized');
                
                // Get the DataTable instance
                const table = $(settings.nTable).DataTable();
                
                // Hook into draw event
                table.on('draw', function() {
                    console.log('Inventory Requests DataTable draw event');
                    
                    // Check for pending modal
                    const pendingId = localStorage.getItem('pendingInventoryRequestModal');
                    if (pendingId) {
                        console.log('Attempting to open modal for request ID:', pendingId);
                        
                        // Try to find and click the button
                        setTimeout(function() {
                            const button = $('.view-request-btn[data-id="' + pendingId + '"]');
                            if (button.length) {
                                console.log('Found view button, clicking...');
                                button.click();
                                localStorage.removeItem('pendingInventoryRequestModal');
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
            const pendingId = localStorage.getItem('pendingInventoryRequestModal');
            if (pendingId) {
                console.log('Page load check - Pending request ID:', pendingId);
                
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
                        localStorage.removeItem('pendingInventoryRequestModal');
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
    
    .small {
        font-size: 80%;
    }
    
    /* Table row styles */
    .item-row td, .edit-item-row td {
        vertical-align: middle !important;
        padding: 0.75rem !important;
    }
    
    /* Batch allocation container */
    .batch-allocation-container {
        max-height: 150px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 8px;
        background-color: #f8f9fa;
    }
    
    .batch-item {
        padding: 5px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }
    
    .batch-item:last-child {
        border-bottom: none;
    }
    
    .batch-input {
        width: 70px;
        display: inline-block;
        padding: 2px 5px;
        font-size: 0.9rem;
        border: 1px solid #ced4da;
        border-radius: 3px;
    }
    
    .expiring-soon {
        color: #dc3545;
        font-weight: bold;
    }
    
    /* Modal styles */
    .modal-body {
        overflow: visible !important;
    }
    
    .modal {
        overflow: hidden !important;
    }
    
    .modal-dialog {
        overflow-y: initial !important;
        max-width: 90% !important;
    }
    
    .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto !important;
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
    
    /* Style for disabled select */
    .select2-container--bootstrap .select2-selection--single[aria-disabled="true"] {
        background-color: #e9ecef;
        opacity: 0.7;
    }
    
    /* Table cell padding adjustment */
    .table td, .table th {
        padding: 0.75rem;
        vertical-align: middle;
    }
    
    /* Product select container */
    .product-select, .edit-product-select {
        width: 100% !important;
    }
    
    /* Ensure the selected value is visible */
    .select2-selection__rendered {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
        
    /* Form validation styles */
    .is-invalid .select2-selection {
        border-color: #dc3545 !important;
    }
    
    .is-valid .select2-selection {
        border-color: #28a745 !important;
    }
    
    /* Current allocations badges */
    .current-allocations .badge {
        font-size: 0.8rem;
        padding: 0.5rem;
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
        display: inline-flex;
        align-items: center;
    }
    
    .current-allocations .badge a {
        color: white;
        text-decoration: none;
        margin-left: 0.5rem;
    }
    
    .current-allocations .badge a:hover {
        opacity: 0.8;
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
    
    /* Batch select dropdown */
    .batch-select, .edit-batch-select {
        width: 100%;
        padding: 0.25rem;
        font-size: 0.875rem;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .batch-qty-input, .edit-batch-qty-input {
        width: 80px;
        padding: 0.25rem;
        font-size: 0.875rem;
        border: 1px solid #ced4da;
        border-radius: 4px 0 0 4px;
    }
    
    .batch-add-btn, .edit-batch-add-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0 4px 4px 0;
    }
    
    /* Input group fixes */
    .input-group-sm > .form-control,
    .input-group-sm > .input-group-append > .btn {
        height: calc(1.5em + 0.5rem + 2px);
        font-size: 0.875rem;
    }
    
    /* Ensure product cells have proper width */
    td:has(.product-select),
    td:has(.edit-product-select) {
        min-width: 250px;
    }
    
    /* Add Item button styling */
    #addItemBtn, #addEditItemBtn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        padding: 0.5rem 1rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    #addItemBtn:hover, #addEditItemBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
    
    /* Remove button styling */
    .remove-item-btn, .remove-edit-item-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        padding: 0.25rem 0.5rem;
    }
    
    .remove-item-btn:hover, .remove-edit-item-btn:hover {
        transform: scale(1.05);
    }
    
    .remove-item-btn:disabled, .remove-edit-item-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    /* DO NOT hide these classes - they are needed for driver dropdown */
    .dropdown-menu.p-3,
    .driver-item {
        display: block !important;
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
    /* Style for Select2 clear/remove button */
    .select2-selection__clear {
        color: #dc3545 !important;
        font-size: 1.2rem !important;
        font-weight: bold !important;
        margin-right: 25px !important;
        transition: all 0.2s ease !important;
        opacity: 0.7 !important;
    }

    .select2-selection__clear:hover {
        color: #c82333 !important;
        opacity: 1 !important;
        transform: scale(1.2) !important;
        background: none !important;
        text-decoration: none !important;
    }

    /* Alternative - if you want a circle background */
    .select2-selection__clear {
        background: #dc3545 !important;
        color: white !important;
        border-radius: 50% !important;
        width: 18px !important;
        height: 18px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        margin-right: 25px !important;
        margin-top: 9px !important;
        transition: all 0.2s ease !important;
        opacity: 0.8 !important;
        border: none !important;
        cursor: pointer !important;
        text-decoration: none !important;
    }

    .select2-selection__clear:hover {
        background: #c82333 !important;
        opacity: 1 !important;
        transform: scale(1.1) !important;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3) !important;
    }

    /* For the "×" character specifically */
    .select2-selection__clear span,
    .select2-selection__clear::after {
        /* If you need to style the actual × character */
        line-height: 1 !important;
        font-weight: bold !important;
    }

    /* Style for when the clear button is focused */
    .select2-selection__clear:focus {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3) !important;
    }

    /* If you want to position it better */
    .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 25px !important;
        top: 1px !important;
    }

    /* For multiple select (if you ever use it) */
    .select2-selection--multiple .select2-selection__clear {
        margin-top: 5px !important;
        margin-right: 5px !important;
    }

    /* Optional: Add a tooltip-like effect on hover */
    .select2-selection__clear:hover::before {
        content: 'Clear selection' !important;
        position: absolute !important;
        background: #333 !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: 4px !important;
        font-size: 12px !important;
        white-space: nowrap !important;
        right: 0 !important;
        top: -25px !important;
        z-index: 1000 !important;
        pointer-events: none !important;
    }

    /* Animation for the clear button */
    @keyframes pulse-red {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
            background-color: #ff0000;
        }
        100% {
            transform: scale(1);
        }
    }

    .select2-selection__clear:active {
        animation: pulse-red 0.3s ease;
    }

    /* If you want a different style for disabled state */
    .select2-selection__clear[aria-disabled="true"] {
        display: none !important;
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
            });
            
            // Force hide loading after DataTable is initialized
            setTimeout(function() {
                HideLoad();
            }, 1000);
        }
        
        // Store current request ID for actions
        var currentRequestId = null;
        var currentRequestStatus = null;
        
        // Items counters for create and edit modals
        var itemCounter = 0;
        var editItemCounter = 0;
        
        // Store batch allocations for each row
        var batchAllocations = {};
        var editBatchAllocations = {};
        
        // Products data from server
        var products = @json($products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code
            ];
        }));
        
        // Create a products lookup object
        var productsLookup = {};
        products.forEach(function(product) {
            productsLookup[product.id] = product;
        });
        
        $('#driver_id_create, #driver_id_edit').select2({
            theme: 'bootstrap',
            placeholder: 'Select Driver',
            allowClear: true,
            dropdownParent: $('body')
        });


        // ============================================
        // CREATE MODAL FUNCTIONS
        // ============================================

        // Initialize create modal items table
        function initializeItemsTable() {
            $('#itemsBody').empty();
            itemCounter = 0;
            batchAllocations = {};
            addItemRow();
        }

        // Add item row to create modal
        function addItemRow() {
            var rowIndex = itemCounter;
            
            var row = `
                <tr class="item-row" data-index="${rowIndex}">
                    <td class="align-middle text-center">${rowIndex + 1}</td>
                    <td>
                        <select class="form-control product-select" id="product_${rowIndex}" data-index="${rowIndex}" style="width: 100%;">
                            <option value="">{{ __('Select Product') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-code="{{ $product->code }}">
                                    {{ $product->name }} ({{ $product->unit_code }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" class="product-id-input" name="items[${rowIndex}][product_id]" value="">
                        <div class="text-danger product-error small"></div>
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control quantity-input" name="items[${rowIndex}][quantity]" placeholder="Enter quantity" value="1">
                        <div class="text-danger quantity-error small"></div>
                    </td>
                    <td>
                        <div class="batch-allocation-container" id="batchContainer${rowIndex}">
                            <div class="text-muted small">Select product first</div>
                        </div>
                        <input type="hidden" class="batch-allocations-input" name="items[${rowIndex}][batch_allocations]" value="">
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
            
            itemCounter++;
            
            // Enable remove buttons if more than one row
            if ($('#itemsBody tr').length > 1) {
                $('#itemsBody tr:first .remove-item-btn').prop('disabled', false);
            }
        }

        // Fix for product dropdown display issue
        $(document).on('show.bs.dropdown', '.dropdown', function() {
            var $this = $(this);
            var $button = $this.find('.dropdown-toggle');
            var $menu = $this.find('.dropdown-menu');
            
            // Reset any inline styles that might be interfering
            $menu.css({
                'position': 'absolute',
                'top': '100%',
                'left': '0',
                'right': 'auto',
                'bottom': 'auto'
            });
            
            // Ensure the product list is visible
            var $productList = $menu.find('.product-list, .edit-product-list');
            if ($productList.length) {
                $productList.find('.product-select-item, .edit-product-select-item').show();
            }
            
            // Reset search input
            $menu.find('.product-search, .edit-product-search').val('');
        });

        $(document).on('click', '.dropdown-menu', function(e) {
            e.stopPropagation();
        });

        // Product search in create modal
        $(document).on('keyup', '.product-search', function(e) {
            e.stopPropagation();
            var searchTerm = $(this).val().toLowerCase();
            var index = $(this).data('index');
            var productList = $(this).siblings('.product-list[data-index="' + index + '"]');
            
            var visibleCount = 0;
            productList.find('.product-select-item').each(function() {
                var productText = $(this).text().toLowerCase();
                if (productText.includes(searchTerm)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            // Show message if no results
            var noResultsMsg = productList.find('.no-results-message');
            if (visibleCount === 0) {
                if (noResultsMsg.length === 0) {
                    productList.append('<div class="text-muted text-center p-2 no-results-message">No products found</div>');
                }
            } else {
                noResultsMsg.remove();
            }
        });

        // Product selection in create modal
        $(document).on('click', '.product-select-item, .edit-product-select-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $this = $(this);
            var index = $this.data('index');
            var productId = $this.data('value');
            var productName = $this.data('name');
            var $dropdown = $this.closest('.dropdown');
            var $button = $dropdown.find('.product-dropdown, .edit-product-dropdown');
            var $row = $this.closest('tr');
            
            // Update the dropdown button
            $button.text(productName).attr('title', productName);
            
            // Close the dropdown
            $button.dropdown('toggle');
            
            // Set the hidden input value
            if ($button.hasClass('product-dropdown')) {
                $row.find('.product-id-input').val(productId);
                $row.find('.product-error').text('');
                loadBatchesForProduct(index, productId);
            } else {
                $row.find('.edit-product-id-input').val(productId);
                $row.find('.edit-product-error').text('');
                loadEditBatchesForProduct(index, productId, []);
            }
            
            // Highlight selected item
            $this.siblings().removeClass('active');
            $this.addClass('active');
        });

        // Function to load batches for a product
        function loadBatchesForProduct(rowIndex, productId) {
            var container = $('#batchContainer' + rowIndex);
            container.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading batches...</div>');
            
            $.ajax({
                url: '{{ route("productBatches.by-product", "") }}/' + productId,
                type: 'GET',
                success: function(response) {
                    if (response && Array.isArray(response) && response.length > 0) {
                        var html = '<select class="form-control form-control-sm batch-select" data-row="' + rowIndex + '">';
                        html += '<option value="">-- Select Batch --</option>';
                        
                        response.forEach(function(batch) {
                            var expiryDate = batch.expiry_date ? formatDate(batch.expiry_date) : 'N/A';
                            html += `<option value="${batch.id}">${batch.batch_code} - Available: ${batch.quantity} (Exp: ${expiryDate})</option>`;
                        });
                        
                        html += '</select>';
                        container.html(html);
                        
                        // When batch is selected, update the hidden input with just the batch ID
                        container.find('.batch-select').on('change', function() {
                            var selectedBatchId = $(this).val();
                            var $row = $('#itemsBody tr[data-index="' + rowIndex + '"]');
                            
                            if (selectedBatchId) {
                                // Store just the batch ID as a simple value
                                $row.find('.batch-allocations-input').val(selectedBatchId);
                                console.log('Selected batch ID:', selectedBatchId);
                            } else {
                                $row.find('.batch-allocations-input').val('');
                            }
                        });
                        
                    } else {
                        container.html('<div class="text-warning small">No active batches available</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading batches:', error);
                    container.html('<div class="text-danger small">Error loading batches</div>');
                }
            });
        }

        // Function to add batch allocation
        function addBatchAllocation(rowIndex) {
            var container = $('#batchContainer' + rowIndex);
            var select = container.find('.batch-select');
            var selectedOption = select.find('option:selected');
            var batchId = select.val();
            var batchCode = selectedOption.data('batch-code') || selectedOption.text().split(' - ')[0];
            var qtyInput = container.find('.batch-qty-input');
            var qty = parseInt(qtyInput.val()) || 0;
            var maxQty = parseInt(selectedOption.data('max'));
            var requestedQty = parseInt($('#itemsBody tr[data-index="' + rowIndex + '"] .quantity-input').val()) || 0;
            
            // Validation
            if (!batchId) {
                showNotification('error', 'Please select a batch');
                return;
            }
            
            if (qty <= 0) {
                showNotification('error', 'Please enter a valid quantity');
                return;
            }
            
            if (qty > maxQty) {
                showNotification('error', 'Quantity cannot exceed available batch quantity (' + maxQty + ')');
                return;
            }
            
            // Get existing allocations
            var allocationsInput = $('#itemsBody tr[data-index="' + rowIndex + '"] .batch-allocations-input');
            var allocations = [];
            try {
                allocations = JSON.parse(allocationsInput.val() || '[]');
            } catch(e) {
                allocations = [];
            }
            
            // Calculate total allocated
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            // Check if this batch is already allocated
            var existingAllocation = allocations.find(function(alloc) {
                return alloc.batch_id == batchId;
            });
            
            var newTotalForBatch = (existingAllocation ? existingAllocation.quantity : 0) + qty;
            
            if (newTotalForBatch > maxQty) {
                showNotification('error', 'Total allocation for this batch (' + newTotalForBatch + ') exceeds available quantity (' + maxQty + ')');
                return;
            }
            
            if (totalAllocated + qty > requestedQty) {
                showNotification('error', 'Total allocation (' + (totalAllocated + qty) + ') cannot exceed requested quantity (' + requestedQty + ')');
                return;
            }
            
            // Update or add allocation
            var existingIndex = allocations.findIndex(function(alloc) {
                return alloc.batch_id == batchId;
            });
            
            if (existingIndex >= 0) {
                allocations[existingIndex].quantity += qty;
            } else {
                allocations.push({
                    batch_id: parseInt(batchId),
                    quantity: qty,
                    batch_code: batchCode
                });
            }
            
            // Save allocations
            allocationsInput.val(JSON.stringify(allocations));
            
            // Reset inputs
            select.val('');
            qtyInput.val('').prop('disabled', true);
            container.find('.batch-add-btn').prop('disabled', true);
            
            // Display updated allocations
            displayCurrentAllocations(rowIndex);
            
            // Update quantity validation
            validateQuantityInput(rowIndex);
            
            showNotification('success', 'Batch allocated successfully');
        }

        // Function to display current allocations
        function displayCurrentAllocations(rowIndex) {
            var container = $('#currentAllocations' + rowIndex);
            var allocationsInput = $('#itemsBody tr[data-index="' + rowIndex + '"] .batch-allocations-input');
            var requestedQty = parseInt($('#itemsBody tr[data-index="' + rowIndex + '"] .quantity-input').val()) || 0;
            
            var allocations = [];
            try {
                allocations = JSON.parse(allocationsInput.val() || '[]');
            } catch(e) {
                allocations = [];
            }
            
            if (allocations.length === 0) {
                container.html('');
                return;
            }
            
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            var html = '<div class="mt-1"><strong>Allocated Batches:</strong></div>';
            allocations.forEach(function(alloc, index) {
                html += '<div class="badge badge-info p-2 mr-1 mb-1 d-inline-flex align-items-center" style="font-size: 0.8rem;">';
                html += 'Batch: ' + (alloc.batch_code || '#' + alloc.batch_id) + ' - ' + alloc.quantity;
                html += ' <a href="#" onclick="removeBatchAllocation(' + rowIndex + ',' + index + ')" class="text-white ml-2"><i class="fa fa-times-circle"></i></a>';
                html += '</div> ';
            });
            
            if (totalAllocated >= requestedQty) {
                html += '<div class="text-success small mt-1"><i class="fa fa-check-circle"></i> Fully allocated</div>';
            } else {
                html += '<div class="text-warning small mt-1">Allocated: ' + totalAllocated + ' / ' + requestedQty + '</div>';
                html += '<div class="text-muted small">Remaining: ' + (requestedQty - totalAllocated) + ' units</div>';
            }
            
            container.html(html);
        }

        // Function to remove batch allocation
        function removeBatchAllocation(rowIndex, allocIndex) {
            var allocationsInput = $('#itemsBody tr[data-index="' + rowIndex + '"] .batch-allocations-input');
            var allocations = JSON.parse(allocationsInput.val() || '[]');
            
            allocations.splice(allocIndex, 1);
            allocationsInput.val(JSON.stringify(allocations));
            
            displayCurrentAllocations(rowIndex);
            validateQuantityInput(rowIndex);
            showNotification('success', 'Batch allocation removed');
        }

        // Function to validate quantity input
        function validateQuantityInput(rowIndex) {
            var row = $('#itemsBody tr[data-index="' + rowIndex + '"]');
            var quantityInput = row.find('.quantity-input');
            var requestedQty = parseInt(quantityInput.val()) || 0;
            var allocationsInput = row.find('.batch-allocations-input');
            
            var allocations = [];
            try {
                allocations = JSON.parse(allocationsInput.val() || '[]');
            } catch(e) {
                allocations = [];
            }
            
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            // If total allocated exceeds requested quantity, adjust allocations
            if (totalAllocated > requestedQty) {
                showNotification('warning', 'Total allocation exceeds requested quantity. Please adjust.');
            }
            
            // Update quantity input styling based on allocation status
            if (totalAllocated === requestedQty && requestedQty > 0) {
                quantityInput.removeClass('is-invalid').addClass('is-valid');
            } else if (totalAllocated < requestedQty && requestedQty > 0) {
                quantityInput.removeClass('is-valid is-invalid');
            }
        }

        // Add item button
        $('#addItemBtn').on('click', function() {
            addItemRow();
        });

        // Remove item button
        $(document).on('click', '.remove-item-btn', function() {
            if ($('#itemsBody tr').length > 1) {
                var row = $(this).closest('tr');
                var rowIndex = parseInt(row.data('index'));
                
                // Destroy Select2 instance
                $('#product_' + rowIndex).select2('destroy');
                row.remove();
                
                // Renumber rows and update indices
                $('#itemsBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    $(this).attr('data-index', index);
                    
                    // Update Select2 IDs
                    var $select = $(this).find('.product-select');
                    $select.attr('id', 'product_' + index);
                    $select.data('index', index);
                    
                    // Reinitialize Select2
                    $select.select2({
                        theme: 'bootstrap',
                        placeholder: 'Search and select a product...',
                        allowClear: true,
                        dropdownParent: $('#createRequest .modal-content'),
                        width: '100%'
                    });
                    
                    // Update input names
                    $(this).find('.product-id-input, .quantity-input, .batch-allocations-input').each(function() {
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
            $('#createRequestForm')[0].reset();
            $('#driver_id_create').val('').trigger('change');  // Changed from old code
            $('#driverError, #itemsError').text('');
            $('#remarks').val('');
        });

        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            var date = new Date(dateString);
            var day = date.getDate().toString().padStart(2, '0');
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var year = date.getFullYear();
            return day + '/' + month + '/' + year;
        }

        // Validate create form
        $('#createRequestForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverError, #itemsError').text('');
            $('.product-error, .quantity-error').text('');
            
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
            
            $('#itemsBody tr').each(function(index) {
                var productId = $(this).find('.product-select').val();  // Get value from Select2
                var quantity = $(this).find('.quantity-input').val();
                var batchAllocationsInput = $(this).find('.batch-allocations-input').val();
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
                
                // Parse and validate batch allocations
                var batchAllocations = [];
                var totalAllocated = 0;
                
                if (batchAllocationsInput) {
                    try {
                        batchAllocations = JSON.parse(batchAllocationsInput);
                        totalAllocated = batchAllocations.reduce(function(sum, alloc) {
                            return sum + alloc.quantity;
                        }, 0);
                    } catch (e) {
                        console.log('Error parsing batch allocations');
                    }
                }
                
                // Validate that total allocated equals requested quantity
                if (totalAllocated !== parseInt(quantity)) {
                    quantityError.text('Total allocated (' + totalAllocated + ') must equal requested quantity (' + quantity + ')');
                    hasErrors = true;
                }
                
                // Add to items array only if valid
                if (productId && quantity && quantity >= 1 && totalAllocated === parseInt(quantity)) {
                    items.push({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity),
                        batch_allocations: batchAllocations
                    });
                }
            });
            
            if (items.length === 0) {
                $('#itemsError').text('Please add at least one valid item with complete batch allocation');
                hasErrors = true;
            }
            
            if (hasErrors) {
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
        // EDIT MODAL FUNCTIONS
        // ============================================

        // Fix for edit product dropdown display issue
        $(document).on('show.bs.dropdown', '.edit-product-dropdown', function() {
            var dropdown = $(this).closest('.dropdown');
            var dropdownMenu = dropdown.find('.dropdown-menu');
            var productList = dropdownMenu.find('.edit-product-list');
            
            // Ensure all items are visible in the list
            productList.find('.edit-product-select-item').show();
            
            // Reset search input
            dropdownMenu.find('.edit-product-search').val('');
        });

        // Initialize edit modal items table
        function initializeEditItemsTable() {
            $('#editItemsBody').empty();
            editItemCounter = 0;
            editBatchAllocations = {};
        }

        // Add item row to edit modal
        function addEditItemRow(productId = '', productName = '', quantity = '', existingBatchId = null) {
            var rowIndex = editItemCounter;
            
            var batchHtml = '<div class="batch-allocation-container" id="editBatchContainer' + rowIndex + '">';
            if (productId) {
                batchHtml += '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading batches...</div>';
            } else {
                batchHtml += '<div class="text-muted small">Select product first</div>';
            }
            batchHtml += '</div>';
            
            var row = `
                <tr class="edit-item-row" data-index="${rowIndex}">
                    <td class="align-middle text-center">${rowIndex + 1}</td>
                    <td>
                        <select class="form-control edit-product-select" id="edit_product_${rowIndex}" data-index="${rowIndex}" style="width: 100%;">
                            <option value="">{{ __('Select Product') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-code="{{ $product->code }}" ${productId == {{ $product->id }} ? 'selected' : ''}>
                                    {{ $product->name }} ({{ $product->unit_code }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" class="edit-product-id-input" name="items[${rowIndex}][product_id]" value="${productId}">
                        <div class="text-danger edit-product-error small"></div>
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control edit-quantity-input" name="items[${rowIndex}][quantity]" placeholder="Enter quantity" value="${quantity}">
                        <div class="text-danger edit-quantity-error small"></div>
                    </td>
                    <td>
                        ${batchHtml}
                        <input type="hidden" class="edit-batch-allocations-input" name="items[${rowIndex}][batch_allocations]" value="${existingBatchId || ''}">
                    </td>
                    <td class="align-middle text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-edit-item-btn" ${rowIndex === 0 ? 'disabled' : ''}>
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#editItemsBody').append(row);
            
            // Initialize Select2 for the new product select
            $('#edit_product_' + rowIndex).select2({
                theme: 'bootstrap',
                placeholder: 'Search and select a product...',
                allowClear: true,
                dropdownParent: $('#editRequest .modal-content'),
                width: '100%'
            });
            
            if (productId) {
                loadEditBatchesForProduct(rowIndex, productId, existingBatchId);
            }
            
            editItemCounter++;
            
            // Enable remove buttons if more than one row
            if ($('#editItemsBody tr').length > 1) {
                $('#editItemsBody tr:first .remove-edit-item-btn').prop('disabled', false);
            }
        }

        $(document).on('shown.bs.dropdown', '.dropdown', function() {
            var $menu = $(this).find('.dropdown-menu');
            var $button = $(this).find('.dropdown-toggle');
            
            // Calculate if dropdown goes outside viewport
            var menuOffset = $menu.offset();
            var menuHeight = $menu.outerHeight();
            var windowHeight = $(window).height();
            
            if (menuOffset.top + menuHeight > windowHeight) {
                // Position dropdown above the button
                $menu.css({
                    'top': 'auto',
                    'bottom': '100%'
                });
            } else {
                $menu.css({
                    'top': '100%',
                    'bottom': 'auto'
                });
            }
        });

        $(document).on('change', '.product-select, .edit-product-select', function(e) {
            var $this = $(this);
            var index = $this.data('index');
            var productId = $this.val();
            var productName = $this.find('option:selected').text();
            var $row = $this.closest('tr');
            
            // Set the hidden input value
            if ($this.hasClass('product-select')) {
                $row.find('.product-id-input').val(productId);
                $row.find('.product-error').text('');
                if (productId) {
                    loadBatchesForProduct(index, productId);
                } else {
                    $('#batchContainer' + index).html('<div class="text-muted small">Select product first</div>');
                }
            } else {
                $row.find('.edit-product-id-input').val(productId);
                $row.find('.edit-product-error').text('');
                if (productId) {
                    var allocations = JSON.parse($row.find('.edit-batch-allocations-input').val() || '[]');
                    loadEditBatchesForProduct(index, productId, allocations);
                } else {
                    $('#editBatchContainer' + index).html('<div class="text-muted small">Select product first</div>');
                }
            }
        });

        // Product search in edit modal
        $(document).on('keyup', '.product-search, .edit-product-search', function(e) {
            e.stopPropagation();
            var searchTerm = $(this).val().toLowerCase();
            var $menu = $(this).closest('.dropdown-menu');
            var index = $(this).data('index');
            var productList = $(this).siblings('.product-list[data-index="' + index + '"], .edit-product-list[data-index="' + index + '"]');
            
            var visibleCount = 0;
            productList.find('.product-select-item, .edit-product-select-item').each(function() {
                var productText = $(this).text().toLowerCase();
                if (productText.includes(searchTerm)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            // Show message if no results
            var noResultsMsg = productList.find('.no-results-message');
            if (visibleCount === 0) {
                if (noResultsMsg.length === 0) {
                    productList.append('<div class="text-muted text-center p-2 no-results-message">No products found</div>');
                }
            } else {
                noResultsMsg.remove();
            }
            
            // Prevent event from bubbling up
            return false;
        });

        // Product selection in edit modal
        $(document).on('click', '.edit-product-select-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var index = $(this).data('index');
            var productId = $(this).data('value');
            var productName = $(this).data('name');
            var dropdown = $(this).closest('.dropdown');
            
            // Update the dropdown button
            var dropdownBtn = dropdown.find('.edit-product-dropdown');
            dropdownBtn.text(productName).attr('title', productName);
            
            // Close the dropdown
            dropdownBtn.dropdown('toggle');
            
            // Set the hidden input value
            $(this).closest('tr').find('.edit-product-id-input').val(productId);
            
            // Clear error
            $(this).closest('tr').find('.edit-product-error').text('');
            
            // Highlight selected item
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            
            // Load batches for this product with empty existing allocations
            loadEditBatchesForProduct(index, productId, []);
        });

        // Function to load batches for edit modal
        function loadEditBatchesForProduct(rowIndex, productId, existingBatchId = null) {
            var container = $('#editBatchContainer' + rowIndex);
            container.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading batches...</div>');
            
            $.ajax({
                url: '{{ route("productBatches.by-product", "") }}/' + productId,
                type: 'GET',
                success: function(response) {
                    if (response && Array.isArray(response) && response.length > 0) {
                        var html = '<select class="form-control form-control-sm edit-batch-select" data-row="' + rowIndex + '">';
                        html += '<option value="">-- Select Batch --</option>';
                        
                        response.forEach(function(batch) {
                            var expiryDate = batch.expiry_date ? formatDate(batch.expiry_date) : 'N/A';
                            var selected = (existingBatchId && batch.id == existingBatchId) ? 'selected' : '';
                            html += `<option value="${batch.id}" ${selected}>${batch.batch_code} - Available: ${batch.quantity} (Exp: ${expiryDate})</option>`;
                        });
                        
                        html += '</select>';
                        container.html(html);
                        
                        // When batch is selected, update the hidden input with just the batch ID
                        container.find('.edit-batch-select').on('change', function() {
                            var selectedBatchId = $(this).val();
                            var $row = $('#editItemsBody tr[data-index="' + rowIndex + '"]');
                            
                            if (selectedBatchId) {
                                $row.find('.edit-batch-allocations-input').val(selectedBatchId);
                                console.log('Edit - Selected batch ID:', selectedBatchId);
                            } else {
                                $row.find('.edit-batch-allocations-input').val('');
                            }
                        });
                        
                        // If there's an existing batch ID, trigger the change to set it
                        if (existingBatchId) {
                            container.find('.edit-batch-select').trigger('change');
                        }
                        
                    } else {
                        container.html('<div class="text-warning small">No active batches available</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading batches:', error);
                    container.html('<div class="text-danger small">Error loading batches</div>');
                }
            });
        }

        // Function to add batch allocation in edit modal
        function addEditBatchAllocation(rowIndex) {
            var container = $('#editBatchContainer' + rowIndex);
            var select = container.find('.edit-batch-select');
            var selectedOption = select.find('option:selected');
            var batchId = select.val();
            var batchCode = selectedOption.data('batch-code') || selectedOption.text().split(' - ')[0];
            var qtyInput = container.find('.edit-batch-qty-input');
            var qty = parseInt(qtyInput.val()) || 0;
            var availableQty = parseInt(selectedOption.data('available'));
            var requestedQty = parseInt($('#editItemsBody tr[data-index="' + rowIndex + '"] .edit-quantity-input').val()) || 0;
            
            // Validation
            if (!batchId) {
                showNotification('error', 'Please select a batch');
                return;
            }
            
            if (qty <= 0) {
                showNotification('error', 'Please enter a valid quantity');
                return;
            }
            
            if (qty > availableQty) {
                showNotification('error', 'Cannot add more than available quantity (' + availableQty + ')');
                return;
            }
            
            // Get existing allocations
            var allocationsInput = $('#editItemsBody tr[data-index="' + rowIndex + '"] .edit-batch-allocations-input');
            var allocations = [];
            try {
                allocations = JSON.parse(allocationsInput.val() || '[]');
            } catch(e) {
                allocations = [];
            }
            
            // Calculate total allocated including existing
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            // Check if total will exceed requested quantity
            if (totalAllocated + qty > requestedQty) {
                showNotification('error', 'Total allocation would exceed requested quantity (' + requestedQty + ')');
                return;
            }
            
            // Update or add allocation
            var existingIndex = allocations.findIndex(function(alloc) {
                return alloc.batch_id == batchId;
            });
            
            if (existingIndex >= 0) {
                allocations[existingIndex].quantity += qty;
            } else {
                allocations.push({
                    batch_id: parseInt(batchId),
                    quantity: qty,
                    batch_code: batchCode
                });
            }
            
            // Save allocations
            allocationsInput.val(JSON.stringify(allocations));
            
            // Update the data-available attribute for this option
            var newAvailable = availableQty - qty;
            selectedOption.attr('data-available', newAvailable);
            
            // Update option text
            var currentTotalForBatch = (existingIndex >= 0 ? allocations[existingIndex].quantity : qty);
            var optionText = selectedOption.text().replace(/\[Allocated: \d+\]/, '');
            if (currentTotalForBatch > 0) {
                selectedOption.text(optionText + ' [Allocated: ' + currentTotalForBatch + ']');
            } else {
                selectedOption.text(optionText);
            }
            
            // Reset inputs
            select.val('');
            qtyInput.val('').prop('disabled', true);
            container.find('.edit-batch-add-btn').prop('disabled', true);
            
            // Display updated allocations
            displayEditCurrentAllocations(rowIndex, allocations);
            
            // Update quantity validation
            validateEditQuantityInput(rowIndex);
            
            showNotification('success', 'Batch allocated successfully');
        }

        // Function to display current allocations in edit modal
        function displayEditCurrentAllocations(rowIndex, allocations) {
            var container = $('#editCurrentAllocations' + rowIndex);
            var requestedQty = parseInt($('#editItemsBody tr[data-index="' + rowIndex + '"] .edit-quantity-input').val()) || 0;
            
            if (allocations.length === 0) {
                container.html('');
                return;
            }
            
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            var html = '<div class="mt-1"><strong>Allocated Batches:</strong></div>';
            allocations.forEach(function(alloc, index) {
                html += '<div class="badge badge-info p-2 mr-1 mb-1 d-inline-flex align-items-center" style="font-size: 0.8rem;">';
                html += 'Batch: ' + (alloc.batch_code || '#' + alloc.batch_id) + ' - ' + alloc.quantity;
                html += ' <a href="#" onclick="removeEditBatchAllocation(' + rowIndex + ',' + index + ')" class="text-white ml-2"><i class="fa fa-times-circle"></i></a>';
                html += '</div> ';
            });
            
            if (totalAllocated >= requestedQty) {
                html += '<div class="text-success small mt-1"><i class="fa fa-check-circle"></i> Fully allocated</div>';
            } else {
                html += '<div class="text-warning small mt-1">Allocated: ' + totalAllocated + ' / ' + requestedQty + '</div>';
                html += '<div class="text-muted small">Remaining: ' + (requestedQty - totalAllocated) + ' units</div>';
            }
            
            container.html(html);
        }

        // Function to remove batch allocation in edit modal
        function removeEditBatchAllocation(rowIndex, allocIndex) {
            var allocationsInput = $('#editItemsBody tr[data-index="' + rowIndex + '"] .edit-batch-allocations-input');
            var allocations = JSON.parse(allocationsInput.val() || '[]');
            
            // Get the removed allocation details
            var removedAlloc = allocations[allocIndex];
            
            // Remove the allocation
            allocations.splice(allocIndex, 1);
            allocationsInput.val(JSON.stringify(allocations));
            
            // Update the batch select option's available quantity
            var container = $('#editBatchContainer' + rowIndex);
            var select = container.find('.edit-batch-select');
            var option = select.find('option[value="' + removedAlloc.batch_id + '"]');
            
            if (option.length) {
                var currentAvailable = parseInt(option.data('available')) || 0;
                var newAvailable = currentAvailable + removedAlloc.quantity;
                option.attr('data-available', newAvailable);
                
                // Update option text
                var optionText = option.text().replace(/\[Allocated: \d+\]/, '');
                var totalAllocated = allocations
                    .filter(a => a.batch_id == removedAlloc.batch_id)
                    .reduce((sum, a) => sum + a.quantity, 0);
                    
                if (totalAllocated > 0) {
                    option.text(optionText + ' [Allocated: ' + totalAllocated + ']');
                } else {
                    option.text(optionText);
                }
            }
            
            displayEditCurrentAllocations(rowIndex, allocations);
            validateEditQuantityInput(rowIndex);
            showNotification('success', 'Batch allocation removed');
        }

        // Function to validate quantity input in edit modal
        function validateEditQuantityInput(rowIndex) {
            var row = $('#editItemsBody tr[data-index="' + rowIndex + '"]');
            var quantityInput = row.find('.edit-quantity-input');
            var requestedQty = parseInt(quantityInput.val()) || 0;
            var allocationsInput = row.find('.edit-batch-allocations-input');
            
            var allocations = [];
            try {
                allocations = JSON.parse(allocationsInput.val() || '[]');
            } catch(e) {
                allocations = [];
            }
            
            var totalAllocated = allocations.reduce(function(sum, alloc) {
                return sum + alloc.quantity;
            }, 0);
            
            // If total allocated exceeds requested quantity, adjust
            if (totalAllocated > requestedQty) {
                showNotification('warning', 'Total allocation exceeds requested quantity. Please adjust.');
            }
            
            // Update quantity input styling based on allocation status
            if (totalAllocated === requestedQty && requestedQty > 0) {
                quantityInput.removeClass('is-invalid').addClass('is-valid');
            } else if (totalAllocated < requestedQty && requestedQty > 0) {
                quantityInput.removeClass('is-valid is-invalid');
            }
        }

        // Handle edit modal opening
        $(document).on('click', '.edit-request-btn', function(e) {
            e.preventDefault();
            var requestId = $(this).data('id');
            var requestData = $(this).data('request');
            
            // Parse JSON string if needed
            if (typeof requestData === 'string') {
                requestData = JSON.parse(requestData);
            }
            
            // Store current request ID
            currentRequestId = requestId;
            
            // Update form action
            var formAction = '{{ route("inventoryRequests.update", ":id") }}'.replace(':id', requestId);
            $('#editRequestForm').attr('action', formAction);
            
            $('#driver_id_edit').val(requestData.driver_id).trigger('change');
            $('#remarksEdit').val(requestData.remarks || '');
            
            // Clear and populate items table
            initializeEditItemsTable();
            
            // Fetch full request data with batch allocations
            $.ajax({
                url: '{{ route("inventoryRequests.withBatches", ":id") }}'.replace(':id', requestId),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#editItemsBody').empty();
                        editItemCounter = 0;
                        
                        data.items.forEach(function(item) {
                            // Extract the batch_id from batch_allocations if it exists
                            var batchId = null;
                            if (item.batch_allocations && item.batch_allocations.length > 0) {
                                batchId = item.batch_allocations[0].batch_id;
                            }
                            
                            addEditItemRow(
                                item.product_id, 
                                item.product_name, 
                                item.requested_quantity,
                                item.batch_id,
                            );
                        });
                    } else {
                        // Fallback to basic items
                        if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
                            requestData.items.forEach(function(item, index) {
                                var productName = getProductName(item.product_id);
                                addEditItemRow(item.product_id, productName, item.quantity, null);
                            });
                        }
                    }
                },
                error: function() {
                    // Fallback to basic items
                    if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
                        requestData.items.forEach(function(item, index) {
                            var productName = getProductName(item.product_id);
                            addEditItemRow(item.product_id, productName, item.quantity, null);
                        });
                    } else if (requestData.product_id && requestData.quantity) {
                        var productName = getProductName(requestData.product_id);
                        addEditItemRow(requestData.product_id, productName, requestData.quantity, null);
                    }
                }
            });
            
            $('#editRequest').modal('show');
        });

        // Add item button for edit modal
        $('#addEditItemBtn').on('click', function() {
            addEditItemRow();
        });

        // Remove item button for edit modal
        $(document).on('click', '.remove-edit-item-btn', function() {
            if ($('#editItemsBody tr').length > 1) {
                var row = $(this).closest('tr');
                var rowIndex = parseInt(row.data('index'));
                
                // Destroy Select2 instance
                $('#edit_product_' + rowIndex).select2('destroy');
                row.remove();
                
                // Renumber rows and update indices
                $('#editItemsBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    $(this).attr('data-index', index);
                    
                    // Update Select2 IDs
                    var $select = $(this).find('.edit-product-select');
                    $select.attr('id', 'edit_product_' + index);
                    $select.data('index', index);
                    
                    // Reinitialize Select2
                    $select.select2({
                        theme: 'bootstrap',
                        placeholder: 'Search and select a product...',
                        allowClear: true,
                        dropdownParent: $('#editRequest .modal-content'),
                        width: '100%'
                    });
                    
                    // Update input names
                    $(this).find('.edit-product-id-input, .edit-quantity-input, .edit-batch-allocations-input').each(function() {
                        var name = $(this).attr('name');
                        if (name && name.includes('items[')) {
                            var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
                
                // Update counter
                editItemCounter = $('#editItemsBody tr').length;
                
                // Disable remove button on first row if only one row left
                if ($('#editItemsBody tr').length === 1) {
                    $('#editItemsBody tr:first .remove-edit-item-btn').prop('disabled', true);
                }
            }
        });

        // Update the edit form validation to match create modal
        $('#editRequestForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverEditError, #editItemsError').text('');
            $('.edit-product-error, .edit-quantity-error').text('');
            
            // Validate driver
            var driverId = $('#driver_id_edit').val();
            if (!driverId) {
                $('#driverEditError').text('Please select a driver');
                return false;
            }
            
            // Validate items
            var hasErrors = false;
            var items = [];
            var productIds = new Set();
            
            $('#editItemsBody tr').each(function(index) {
                var productId = $(this).find('.edit-product-id-input').val();
                var quantity = $(this).find('.edit-quantity-input').val();
                var batchAllocationsInput = $(this).find('.edit-batch-allocations-input').val();
                var productError = $(this).find('.edit-product-error');
                var quantityError = $(this).find('.edit-quantity-error');
                
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
                
                // Parse and validate batch allocations
                var batchAllocations = [];
                var totalAllocated = 0;
                
                if (batchAllocationsInput) {
                    try {
                        batchAllocations = JSON.parse(batchAllocationsInput);
                        totalAllocated = batchAllocations.reduce(function(sum, alloc) {
                            return sum + alloc.quantity;
                        }, 0);
                    } catch (e) {
                        console.log('Error parsing batch allocations');
                    }
                }
                
                // Validate that total allocated equals requested quantity
                if (totalAllocated !== parseInt(quantity)) {
                    quantityError.text('Total allocated (' + totalAllocated + ') must equal requested quantity (' + quantity + ')');
                    hasErrors = true;
                }
                
                // Add to items array only if valid
                if (productId && quantity && quantity >= 1 && totalAllocated === parseInt(quantity)) {
                    items.push({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity),
                        batch_allocations: batchAllocations
                    });
                }
            });
            
            if (items.length === 0) {
                $('#editItemsError').text('Please add at least one valid item with complete batch allocation');
                hasErrors = true;
            }
            
            if (hasErrors) {
                return false;
            }
            
            // Prepare all form data including items
            var formData = {
                driver_id: driverId,
                items: items,
                remarks: $('#remarksEdit').val(),
                save_and_approve: $('#saveAndApprove').val(),
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
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
                        $('#editRequest').modal('hide');
                        showNotification('success', response.message);
                        
                        // Reset the flag
                        $('#saveAndApprove').val('0');
                        
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
        // SAVE AND APPROVE FUNCTIONALITY
        // ============================================
        
        // Handle Save & Approve button click
        $(document).on('click', '#saveAndApproveBtn', function() {
            if (confirm('Are you sure you want to save changes and approve this request? This will immediately add the items to driver inventory.')) {
                // Set the hidden field to indicate we want to save and approve
                $('#saveAndApprove').val('1');
                
                // Submit the form
                $('#editRequestForm').submit();
            }
        });
        
        // Reset the save_and_approve flag when form is submitted normally
        $('#editRequestForm').submit(function(e) {
            // If not triggered by saveAndApproveBtn, ensure flag is 0
            if ($('#saveAndApprove').val() !== '1') {
                $('#saveAndApprove').val('0');
            }
        });
        
        // Reset the flag when modal is closed
        $('#editRequest').on('hidden.bs.modal', function () {
            $('#saveAndApprove').val('0');
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
            $('#viewRequestId').text('(#' + requestId + ')');
            $('#viewRequestIdText').text(requestId);
            
            // Fill view modal with data
            $('#viewDriverName').text(requestData.driver_name || 'N/A');
            $('#viewRemarks').text(requestData.remarks || 'No remarks');
            $('#viewCreatedAt').text(requestData.created_at || 'N/A');
            
            // Set status with badge
            var status = requestData.status;
            var badgeClass = getStatusBadgeClass(status);
            
            $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
            
            // Show/hide sections based on initial status
            updateStatusSections(status, requestData);
            
            // Fetch full request data with batch details
            $.ajax({
                url: '{{ route("inventoryRequests.withBatches", ":id") }}'.replace(':id', requestId),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayViewModalWithBatches(response.data);
                        // Update status sections with the full data
                        updateStatusSections(response.data.status, response.data);
                    } else {
                        displayViewModalBasic(requestData);
                    }
                },
                error: function() {
                    displayViewModalBasic(requestData);
                }
            });
            
            // Show modal
            $('#viewRequest').modal('show');
        });

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
            // Show items table
            var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Batch Code</th><th>Requested Qty</th></tr></thead><tbody>';
            
            var totalRequested = 0;
            
            data.items.forEach(function(item, index) {
                itemsHtml += '<tr>';
                itemsHtml += '<td>' + (index + 1) + '</td>';
                itemsHtml += '<td>' + item.product_name + '</td>';
                itemsHtml += '<td>' + (item.batch_code || 'N/A') + '</td>';
                itemsHtml += '<td class="text-center">' + item.requested_quantity + '</td>';
                itemsHtml += '</tr>';
                
                totalRequested += item.requested_quantity;
            });
            
            itemsHtml += '</tbody>';
            itemsHtml += '<tfoot><tr>';
            itemsHtml += '<td colspan="3" class="text-right"><strong>Total:</strong></td>';
            itemsHtml += '<td class="text-center"><strong>' + totalRequested + '</strong></td>';
            itemsHtml += '</tr></tfoot>';
            itemsHtml += '</table></div>';
            
            $('#viewItemsTable').html(itemsHtml);
            
            // Show batch information if available
            var hasBatchInfo = data.items.some(item => item.batch_code && item.expiry_date);
            if (hasBatchInfo) {
                var batchHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                batchHtml += '<thead><tr><th>Product</th><th>Batch Code</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>';
                
                data.items.forEach(function(item) {
                    if (item.batch_code) {
                        var expiryStatus = item.is_expiring_soon ? 
                            '<span class="badge badge-warning">Expiring Soon</span>' : 
                            '<span class="badge badge-success">Valid</span>';
                            
                        batchHtml += '<tr>';
                        batchHtml += '<td>' + item.product_name + '</td>';
                        batchHtml += '<td>' + item.batch_code + '</td>';
                        batchHtml += '<td>' + (item.expiry_date || 'N/A') + '</td>';
                        batchHtml += '<td>' + expiryStatus + '</td>';
                        batchHtml += '</tr>';
                    }
                });
                
                batchHtml += '</tbody></table></div>';
                $('#viewBatchAllocationsTable').html(batchHtml);
                $('#viewBatchAllocationsSection').show();
            } else {
                $('#viewBatchAllocationsSection').hide();
            }
        }

        function displayViewModalBasic(requestData) {
            // Show items table without batch info
            var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Requested Quantity</th></tr></thead><tbody>';
            
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
            $('#viewBatchAllocationsSection').hide();
        }

        // Helper function to get product name
        function getProductName(productId) {
            var product = productsLookup[productId];
            return product ? product.name : 'Product ' + productId;
        }
        // ============================================
        // BATCH ALLOCATION UPDATE FUNCTIONS
        // ============================================
        
        // Update batch allocation for create modal
        window.updateBatchAllocation = function(rowIndex) {
            var allocations = [];
            var container = $('#batchContainer' + rowIndex);
            var totalAllocated = 0;
            
            container.find('.batch-input').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    allocations.push({
                        batch_id: parseInt($(this).data('batch-id')),
                        quantity: qty
                    });
                    totalAllocated += qty;
                }
            });
            
            // Update hidden input
            var row = $('#itemsBody tr[data-index="' + rowIndex + '"]');
            row.find('.batch-allocations-input').val(JSON.stringify(allocations));
            
            // Show allocation summary
            var requestedQty = parseInt(row.find('.quantity-input').val()) || 0;
            var allocInfo = row.find('.batch-allocation-info');
            if (!allocInfo.length) {
                allocInfo = $('<div class="batch-allocation-info small mt-1"></div>');
                container.append(allocInfo);
            }
            
            if (totalAllocated > 0) {
                var statusClass = totalAllocated >= requestedQty ? 'text-success' : 'text-warning';
                allocInfo.html('<span class="' + statusClass + '">Allocated: ' + totalAllocated + ' / ' + requestedQty + '</span>');
            } else {
                allocInfo.empty();
            }
        };
        
        // Update batch allocation for edit modal
        window.updateEditBatchAllocation = function(rowIndex) {
            var allocations = [];
            var container = $('#editBatchContainer' + rowIndex);
            var totalAllocated = 0;
            
            container.find('.edit-batch-input').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    allocations.push({
                        batch_id: parseInt($(this).data('batch-id')),
                        quantity: qty
                    });
                    totalAllocated += qty;
                }
            });
            
            // Update hidden input
            var row = $('#editItemsBody tr[data-index="' + rowIndex + '"]');
            row.find('.edit-batch-allocations-input').val(JSON.stringify(allocations));
            
            // Show allocation summary
            var requestedQty = parseInt(row.find('.edit-quantity-input').val()) || 0;
            var allocInfo = row.find('.batch-allocation-info');
            if (!allocInfo.length) {
                allocInfo = $('<div class="batch-allocation-info small mt-1"></div>');
                container.append(allocInfo);
            }
            
            if (totalAllocated > 0) {
                var statusClass = totalAllocated >= requestedQty ? 'text-success' : 'text-warning';
                allocInfo.html('<span class="' + statusClass + '">Allocated: ' + totalAllocated + ' / ' + requestedQty + '</span>');
            } else {
                allocInfo.empty();
            }
        };
        
        // ============================================
        // FORM VALIDATION AND SUBMISSION
        // ============================================
        
        // Validate create form
        $('#createRequestForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverError, #itemsError').text('');
            $('.product-error, .quantity-error').text('');
            
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
            
            $('#itemsBody tr').each(function(index) {
                var productId = $(this).find('.product-select').val();
                var quantity = $(this).find('.quantity-input').val();
                var batchId = $(this).find('.batch-allocations-input').val(); // Now just a simple ID
                var productError = $(this).find('.product-error');
                var quantityError = $(this).find('.quantity-error');
                
                console.log('Product:', productId, 'Quantity:', quantity, 'Batch ID:', batchId);
                
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
                
                // Validate batch selection
                if (!batchId) {
                    quantityError.text('Please select a batch'); // Show error on quantity field
                    hasErrors = true;
                }
                
                // Add to items array only if valid
                if (productId && quantity && quantity >= 1 && batchId) {
                    items.push({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity),
                        batch_id: parseInt(batchId) // Send just the batch ID
                    });
                }
            });
            
            if (items.length === 0) {
                $('#itemsError').text('Please add at least one valid item with batch selected');
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
                data: postData, // Send as normal form data, not JSON
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
                    showNotification('error', xhr.responseJSON?.message || 'An error occurred');
                }
            });
        });
        
        // Validate edit form
        $('#editRequestForm').submit(function(e) {
            e.preventDefault();
            
            // Reset errors
            $('#driverEditError, #editItemsError').text('');
            $('.edit-product-error, .edit-quantity-error').text('');
            
            // Validate driver
            var driverId = $('#driver_id_edit').val();
            if (!driverId) {
                $('#driverEditError').text('Please select a driver');
                return false;
            }
            
            // Validate items
            var hasErrors = false;
            var items = [];
            var productIds = new Set();
            
            $('#editItemsBody tr').each(function(index) {
                var productId = $(this).find('.edit-product-select').val();
                var quantity = $(this).find('.edit-quantity-input').val();
                var batchId = $(this).find('.edit-batch-allocations-input').val(); // Simple ID
                var productError = $(this).find('.edit-product-error');
                var quantityError = $(this).find('.edit-quantity-error');
                
                console.log('Edit - Product:', productId, 'Quantity:', quantity, 'Batch ID:', batchId);
                
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
                
                // Validate batch selection
                if (!batchId) {
                    quantityError.text('Please select a batch');
                    hasErrors = true;
                }
                
                // Add to items array only if valid
                if (productId && quantity && quantity >= 1 && batchId) {
                    items.push({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity),
                        batch_id: parseInt(batchId) // Send just the batch ID
                    });
                }
            });
            
            if (items.length === 0) {
                $('#editItemsError').text('Please add at least one valid item with batch selected');
                hasErrors = true;
            }
            
            if (hasErrors) {
                // Highlight problematic rows
                $('#editItemsBody tr').each(function(index) {
                    var productId = $(this).find('.edit-product-select').val();
                    var quantity = $(this).find('.edit-quantity-input').val();
                    
                    if (!productId) {
                        $(this).find('.select2-container').addClass('is-invalid');
                    } else {
                        $(this).find('.select2-container').removeClass('is-invalid');
                    }
                    
                    if (!quantity || quantity < 1 || !$(this).find('.edit-batch-allocations-input').val()) {
                        $(this).find('.edit-quantity-input').addClass('is-invalid');
                    } else {
                        $(this).find('.edit-quantity-input').removeClass('is-invalid');
                    }
                });
                return false;
            }
            
            // Prepare all form data
            var postData = {
                driver_id: driverId,
                items: items,
                remarks: $('#remarksEdit').val(),
                save_and_approve: $('#saveAndApprove').val(),
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
            };
            
            console.log('Sending edit data:', postData);
            
            // Submit via AJAX
            ShowLoad();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: postData, // Send as normal form data
                dataType: 'json',
                success: function(response) {
                    HideLoad();
                    if (response.success) {
                        $('#editRequest').modal('hide');
                        showNotification('success', response.message);
                        
                        // Reset the flag
                        $('#saveAndApprove').val('0');
                        
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
                    console.error('Error response:', xhr.responseJSON);
                }
            });
        });
        
        // ============================================
        // APPROVE/REJECT FUNCTIONS
        // ============================================
        
        // Handle approve action from view modal
        $(document).on('click', '#approveFromViewBtn', function() {
            if (confirm('Are you sure you want to approve this inventory request?')) {
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
            
            if (confirm('Are you sure you want to delete this inventory request?')) {
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
            
            var url = '{{ route("inventoryRequests.approve", ":id") }}';
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
            
            var url = '{{ route("inventoryRequests.reject", ":id") }}';
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
            $('#driver_id_edit').val('').trigger('change');  // Changed from old code

             $('#editItemsBody').empty();
            editItemCounter = 0;
            $('#saveAndApprove').val('0');
            $('#editItemsError, #driverEditError').text('');
        });

        // Clear view modal when closed
        $('#viewRequest').on('hidden.bs.modal', function () {
            // Reset view modal fields
            $('#viewRequestId').text('');
            $('#viewRequestIdText, #viewDriverName, #viewRemarks, #viewCreatedAt, #viewApprovedBy, #viewApprovedAt, #viewRejectedBy, #viewRejectedAt, #viewRejectionReason').text('');
            $('#viewStatusBadge').html('');
            $('#viewActionButtons').html('');
            $('#viewItemsTable').html('');
            $('#viewBatchAllocationsTable').html('');
            $('#viewBatchAllocationsSection').hide();
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