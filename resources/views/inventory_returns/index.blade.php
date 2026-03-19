@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Stock Return') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('Stock Return') }}
                        </div>
                        <div class="card-body">
                            @include('inventory_returns.table')
                            <div class="pull-right mr-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Return Modal -->
    <div id="createRequest" class="modal fade">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('Create Stock Return') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => 'inventoryReturns.store', 'enctype' => 'multipart/form-data', 'id' => 'createReturnForm']) !!}
                    
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
                                        <th width="50%">Batch Selection</th>
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
                        <button type="submit" class="btn btn-primary rounded-0">{{ __('Submit Return') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Return Modal -->
    <div id="editRequest" class="modal fade">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('Edit Stock Return') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => ['inventoryReturns.update', ':id'], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'id' => 'editReturnForm']) !!}
                    
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
                                        <th width="50%">Batch Selection</th>
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
                        <button type="submit" class="btn btn-primary rounded-0">{{ __('Update Return') }}</button>
                        
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
    
    <!-- View Return Modal -->
    <div id="viewRequest" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Stock Return Details <span id="viewRequestId"></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="30%">Return ID:</th>
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
                                        <th>Returned At:</th>
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
                        <h5>Returned Items</h5>
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
                            <!-- Action buttons will be shown here for pending returns only -->
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
                    <h5 class="modal-title">Reject Return</h5>
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
    // Auto-open script for inventory returns
    $(document).ready(function() {
        console.log('Inventory Returns page loaded');
        
        // Check URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const viewRequestId = urlParams.get('view_return');
        
        if (viewRequestId) {
            console.log('Found view_return parameter:', viewRequestId);
            localStorage.setItem('pendingInventoryReturnModal', viewRequestId);
            
            // Clean up URL
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        // Hook into DataTable initialization
        if (typeof $.fn.DataTable !== 'undefined') {
            $(document).on('init.dt', function(e, settings) {
                console.log('Inventory Returns DataTable initialized');
                
                // Get the DataTable instance
                const table = $(settings.nTable).DataTable();
                
                // Hook into draw event
                table.on('draw', function() {
                    console.log('Inventory Returns DataTable draw event');
                    
                    // Check for pending modal
                    const pendingId = localStorage.getItem('pendingInventoryReturnModal');
                    if (pendingId) {
                        console.log('Attempting to open modal for return ID:', pendingId);
                        
                        // Try to find and click the button
                        setTimeout(function() {
                            const button = $('.view-return-btn[data-id="' + pendingId + '"]');
                            if (button.length) {
                                console.log('Found view button, clicking...');
                                button.click();
                                localStorage.removeItem('pendingInventoryReturnModal');
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
            const pendingId = localStorage.getItem('pendingInventoryReturnModal');
            if (pendingId) {
                console.log('Page load check - Pending return ID:', pendingId);
                
                // Try multiple times
                let attempts = 0;
                const maxAttempts = 5;
                
                function tryOpenModal() {
                    attempts++;
                    console.log(`Attempt ${attempts} to find button for ID: ${pendingId}`);
                    
                    const button = $('.view-return-btn[data-id="' + pendingId + '"]');
                    if (button.length) {
                        console.log('Found button on attempt', attempts);
                        button.click();
                        localStorage.removeItem('pendingInventoryReturnModal');
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
    
    // Store driver inventory data - FIXED: Initialize as array, not object
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
    
    $('#driver_id_create, #driver_id_edit').select2({
        theme: 'bootstrap',
        placeholder: 'Select Driver',
        allowClear: true,
        dropdownParent: $('body')
    });

    // ============================================
    // HELPER FUNCTIONS (MUST BE DEFINED BEFORE USE)
    // ============================================
    
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

    // Helper function to get product name
    function getProductName(productId) {
        var product = productsLookup[productId];
        return product ? product.name : 'Product ' + productId;
    }

    // ============================================
    // VIEW MODAL HANDLER - FETCH FROM SERVER
    // ============================================
    $(document).on('click', '.view-return-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('View button clicked'); // Debug log
        
        var requestId = $(this).data('id');
        var basicRequestData = $(this).data('request');
        
        // Parse basic data for immediate display (optional)
        if (typeof basicRequestData === 'string') {
            try {
                basicRequestData = JSON.parse(basicRequestData);
            } catch (e) {
                console.error('Error parsing request data:', e);
            }
        }
        
        // Store current request info
        currentRequestId = requestId;
        currentRequestStatus = basicRequestData ? basicRequestData.status : null;
        
        // Update modal title with request ID
        $('#viewRequestId').text('(#' + requestId + ')');
        $('#viewRequestIdText').text(requestId);
        
        // Show loading state
        $('#viewDriverName').text('Loading...');
        $('#viewRemarks').text('Loading...');
        $('#viewCreatedAt').text('Loading...');
        $('#viewStatusBadge').html('<span class="badge badge-info">Loading...</span>');
        $('#viewItemsTable').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading items...</div>');
        $('#viewBatchAllocationsSection').hide();
        
        // Show modal immediately with loading state
        $('#viewRequest').modal('show');
        
        // Fetch detailed data from server with batch information
        ShowLoad();
        $.ajax({
            url: '{{ route("inventoryReturns.withBatches", "") }}/' + requestId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                HideLoad();
                
                if (response.success && response.data) {
                    console.log('Received detailed data:', response.data);
                    
                    var data = response.data;
                    
                    // Update current status
                    currentRequestStatus = data.status;
                    
                    // Fill view modal with detailed data
                    $('#viewDriverName').text(data.driver_name || 'N/A');
                    $('#viewRemarks').text(data.remarks || 'No remarks');
                    $('#viewCreatedAt').text(data.created_at || 'N/A');
                    
                    // Set status with badge
                    var status = data.status;
                    var badgeClass = getStatusBadgeClass(status);
                    $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + 
                        (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'N/A') + '</span>');
                    
                    // Show/hide sections based on status
                    updateStatusSections(status, data);
                    
                    // Display items with batch information
                    displayViewModalItems(data);
                    
                } else {
                    // Fallback to basic data if available
                    if (basicRequestData) {
                        console.log('Falling back to basic data:', basicRequestData);
                        
                        $('#viewDriverName').text(basicRequestData.driver_name || 'N/A');
                        $('#viewRemarks').text(basicRequestData.remarks || 'No remarks');
                        $('#viewCreatedAt').text(basicRequestData.created_at || 'N/A');
                        
                        var status = basicRequestData.status;
                        var badgeClass = getStatusBadgeClass(status);
                        $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + 
                            (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'N/A') + '</span>');
                        
                        updateStatusSections(status, basicRequestData);
                        displayViewModalItems(basicRequestData);
                    } else {
                        $('#viewItemsTable').html('<div class="alert alert-danger">Failed to load return details</div>');
                    }
                }
            },
            error: function(xhr) {
                HideLoad();
                console.error('Error fetching detailed data:', xhr);
                
                // Fallback to basic data
                if (basicRequestData) {
                    console.log('Error, falling back to basic data:', basicRequestData);
                    
                    $('#viewDriverName').text(basicRequestData.driver_name || 'N/A');
                    $('#viewRemarks').text(basicRequestData.remarks || 'No remarks');
                    $('#viewCreatedAt').text(basicRequestData.created_at || 'N/A');
                    
                    var status = basicRequestData.status;
                    var badgeClass = getStatusBadgeClass(status);
                    $('#viewStatusBadge').html('<span class="badge ' + badgeClass + '">' + 
                        (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'N/A') + '</span>');
                    
                    updateStatusSections(status, basicRequestData);
                    displayViewModalItems(basicRequestData);
                } else {
                    $('#viewItemsTable').html('<div class="alert alert-danger">Error loading return details</div>');
                }
            }
        });
    });

    // Updated function to display items in view modal - handles both formats
    function displayViewModalItems(data) {
        console.log('Displaying items with data:', data);
        
        var itemsHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        itemsHtml += '<thead><tr><th>#</th><th>Product</th><th>Batch Code</th><th>Expiry Date</th><th>Returned Quantity</th></tr></thead><tbody>';
        
        var totalQuantity = 0;
        
        // Check if we have items array
        if (data.items && Array.isArray(data.items) && data.items.length > 0) {
            data.items.forEach(function(item, index) {
                // Handle different possible property names
                var quantity = item.returned_quantity || item.quantity || 0;
                var productName = item.product_name || getProductName(item.product_id) || 'Unknown Product';
                var batchCode = item.batch_code || 'N/A';
                var expiryDate = item.expiry_date || 'N/A';
                var expiringClass = item.is_expiring_soon ? ' class="expiring-soon"' : '';
                var expiringWarning = item.is_expiring_soon ? ' ⚠️' : '';
                
                itemsHtml += '<tr>';
                itemsHtml += '<td>' + (index + 1) + '</td>';
                itemsHtml += '<td>' + productName + '</td>';
                itemsHtml += '<td' + expiringClass + '>' + batchCode + expiringWarning + '</td>';
                itemsHtml += '<td>' + expiryDate + '</td>';
                itemsHtml += '<td class="text-center">' + quantity + '</td>';
                itemsHtml += '</tr>';
                
                totalQuantity += parseInt(quantity) || 0;
            });
        } else if (data.product_id && data.quantity) {
            // For backward compatibility with old single-item returns
            var productName = getProductName(data.product_id);
            itemsHtml += '<tr>';
            itemsHtml += '<td>1</td>';
            itemsHtml += '<td>' + (data.product_name || productName) + '</td>';
            itemsHtml += '<td>N/A</td>';
            itemsHtml += '<td>N/A</td>';
            itemsHtml += '<td class="text-center">' + data.quantity + '</td>';
            itemsHtml += '</tr>';
            totalQuantity = data.quantity;
        } else {
            itemsHtml += '<tr><td colspan="5" class="text-center">No items found</td></tr>';
        }
        
        itemsHtml += '</tbody>';
        
        if (totalQuantity > 0) {
            itemsHtml += '<tfoot><tr>';
            itemsHtml += '<td colspan="4" class="text-right"><strong>Total:</strong></td>';
            itemsHtml += '<td class="text-center"><strong>' + totalQuantity + '</strong></td>';
            itemsHtml += '</tr></tfoot>';
        }
        
        itemsHtml += '</table></div>';
        
        $('#viewItemsTable').html(itemsHtml);
        
        // Check if we have batch information to show in the separate section
        var hasBatchInfo = data.items && data.items.some(item => item.batch_code && item.expiry_date);
        if (hasBatchInfo) {
            $('#viewBatchAllocationsSection').show();
            
            var batchHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            batchHtml += '<thead><tr><th>Product</th><th>Batch Code</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>';
            
            data.items.forEach(function(item) {
                if (item.batch_code) {
                    var expiryStatus = item.is_expiring_soon ? 
                        '<span class="badge badge-warning">Expiring Soon</span>' : 
                        '<span class="badge badge-success">Valid</span>';
                        
                    batchHtml += '<tr>';
                    batchHtml += '<td>' + (item.product_name || getProductName(item.product_id)) + '</td>';
                    batchHtml += '<td>' + item.batch_code + '</td>';
                    batchHtml += '<td>' + (item.expiry_date || 'N/A') + '</td>';
                    batchHtml += '<td>' + expiryStatus + '</td>';
                    batchHtml += '</tr>';
                }
            });
            
            batchHtml += '</tbody></table></div>';
            $('#viewBatchAllocationsTable').html(batchHtml);
            
        } else {
            $('#viewBatchAllocationsSection').hide();
        }
    }
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
        
        // Show/hide action buttons based on status
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
                    <td colspan="5" class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading driver inventory...
                    </td>
                </tr>
            `;
            $('#itemsBody').append(loadingRow);
            itemCounter = 0;
            
            // Load driver inventory
            loadDriverInventory(driverId, 'create');
        } else {
            // Clear product selects and show message
            $('#itemsBody').empty();
            itemCounter = 0;
            initializeItemsTable('create');
        }
    });

    // When driver is selected in edit modal
    $('#driver_id_edit').on('change', function() {
        var driverId = $(this).val();
        
        if (driverId) {
            loadDriverInventory(driverId, 'edit');
        }
    });

    // Function to load driver inventory
    function loadDriverInventory(driverId, modalType = 'create') {
        ShowLoad();
        
        $.ajax({
            url: '{{ route("inventoryReturns.getDriverInventory") }}',
            type: 'GET',
            data: { driver_id: driverId },
            dataType: 'json',
            success: function(response) {
                HideLoad();
                
                if (response.success) {
                    // Store inventory data
                    driverInventory = response.inventory || [];
                    
                    console.log('Driver inventory loaded:', driverInventory); // Debug log
                    
                    // Show trip info if available
                    if (response.trip_info) {
                        console.log('Driver trip info:', response.trip_info);
                    }
                    
                    if (driverInventory.length === 0) {
                        showNotification('info', 'This driver has no inventory to return');
                        // Clear the table and show message
                        $('#itemsBody').empty();
                        var emptyRow = `
                            <tr>
                                <td colspan="5" class="text-center text-warning">
                                    <i class="fa fa-exclamation-triangle"></i> No inventory available for this driver
                                </td>
                            </tr>
                        `;
                        $('#itemsBody').append(emptyRow);
                    } else {
                        // Initialize the table with first row
                        initializeItemsTable(modalType);
                    }
                    
                    // Update product dropdowns to only show products the driver has
                    updateProductDropdowns(modalType);
                } else {
                    showNotification('error', response.message || 'Failed to load driver inventory');
                    driverInventory = [];
                    // Clear the table and show error message
                    $('#itemsBody').empty();
                    var errorRow = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
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
                // Clear the table and show error message
                $('#itemsBody').empty();
                var errorRow = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="fa fa-exclamation-circle"></i> Error loading inventory
                        </td>
                    </tr>
                `;
                $('#itemsBody').append(errorRow);
            }
        });
    }

    // Function to update product dropdowns based on driver inventory
    function updateProductDropdowns(modalType) {
        var selector = modalType === 'create' ? '.product-select' : '.edit-product-select';
        
        console.log('Updating dropdowns with driverInventory:', driverInventory); // Debug log
        
        // If no driver inventory, clear all product selects
        if (!driverInventory || driverInventory.length === 0) {
            $(selector).each(function() {
                var $select = $(this);
                var currentValue = $select.val();
                
                // Clear all options except the first one
                $select.find('option:not(:first)').remove();
                
                // Add a disabled option indicating no products
                var option = new Option('No products available', '', true, true);
                $(option).prop('disabled', true);
                $select.append(option);
                
                // Refresh Select2
                $select.trigger('change.select2');
            });
            return;
        }
        
        $(selector).each(function() {
            var $select = $(this);
            var currentValue = $select.val();
            
            // Clear all options except the first one
            $select.find('option:not(:first)').remove();
            
            // Get unique products from inventory
            var uniqueProducts = [];
            var productIds = [];
            
            driverInventory.forEach(function(item) {
                if (item && item.product_id && !productIds.includes(item.product_id)) {
                    productIds.push(item.product_id);
                    uniqueProducts.push(item);
                }
            });
            
            console.log('Unique products for dropdown:', uniqueProducts); // Debug log
            
            // Add options for products the driver has
            uniqueProducts.forEach(function(item) {
                var option = new Option(
                    item.product_name + ' (Total: ' + item.total_quantity + ')', 
                    item.product_id,
                    false,
                    item.product_id == currentValue
                );
                $(option).data('total-quantity', item.total_quantity);
                $(option).data('batches', item.batches);
                $select.append(option);
            });
            
            // If no products available, add a disabled option
            if (uniqueProducts.length === 0) {
                var option = new Option('No products available', '', true, true);
                $(option).prop('disabled', true);
                $select.append(option);
            }
            
            // Refresh Select2
            $select.trigger('change.select2');
        });
    }

    // ============================================
    // CREATE MODAL FUNCTIONS
    // ============================================

    // Initialize create modal items table
    function initializeItemsTable(modalType = 'create') {
        $('#itemsBody').empty();
        itemCounter = 0;
        
        // Always add the first row when driver is selected and has inventory
        if (selectedDriverId && driverInventory && driverInventory.length > 0) {
            addItemRow();
        } else if (selectedDriverId) {
            // Driver selected but no inventory
            var emptyRow = `
                <tr>
                    <td colspan="5" class="text-center text-warning">
                        <i class="fa fa-exclamation-triangle"></i> No inventory available for this driver
                    </td>
                </tr>
            `;
            $('#itemsBody').append(emptyRow);
        } else {
            // No driver selected
            var emptyRow = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fa fa-info-circle"></i> Please select a driver first
                    </td>
                </tr>
            `;
            $('#itemsBody').append(emptyRow);
        }
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
                    <input type="hidden" class="batch-allocations-input" name="items[${rowIndex}][batch_id]" value="">
                    <input type="hidden" class="max-quantity-input" value="0">
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
        
        // If we have driver inventory, filter the products
        if (driverInventory && driverInventory.length > 0) {
            var $select = $('#product_' + rowIndex);
            var currentValue = $select.val();
            
            // Clear all options except the first one
            $select.find('option:not(:first)').remove();
            
            // Get unique products from inventory
            var uniqueProducts = [];
            var productIds = [];
            
            driverInventory.forEach(function(item) {
                if (item && item.product_id && !productIds.includes(item.product_id)) {
                    productIds.push(item.product_id);
                    uniqueProducts.push(item);
                }
            });
            
            // Add options for products the driver has
            uniqueProducts.forEach(function(item) {
                var option = new Option(
                    item.product_name + ' (Total: ' + item.total_quantity + ')', 
                    item.product_id,
                    false,
                    item.product_id == currentValue
                );
                $(option).data('total-quantity', item.total_quantity);
                $(option).data('batches', item.batches);
                $select.append(option);
            });
            
            // If no products available, add a disabled option
            if (uniqueProducts.length === 0) {
                var option = new Option('No products available', '', true, true);
                $(option).prop('disabled', true);
                $select.append(option);
            }
            
            // Refresh Select2
            $select.trigger('change.select2');
        }
        
        itemCounter++;
        
        // Enable remove buttons if more than one row
        if ($('#itemsBody tr').length > 1) {
            $('#itemsBody tr:first .remove-item-btn').prop('disabled', false);
        }
    }

    // Product change event - load batches from driver's inventory
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
            
            console.log('Selected product:', productId, 'Inventory:', productInventory); // Debug log
            
            if (productInventory && productInventory.batches && productInventory.batches.length > 0) {
                // Display batches from driver's inventory
                displayDriverBatches(index, productInventory.batches);
                
                // Set max quantity based on total available
                $row.find('.max-quantity-input').val(productInventory.total_quantity);
                
                // Add quantity validation
                validateQuantityAgainstInventory(index, productInventory.total_quantity);
            } else {
                $('#batchContainer' + index).html('<div class="text-warning small">No batches available for this product</div>');
                $row.find('.max-quantity-input').val(0);
            }
        } else {
            $('#batchContainer' + index).html('<div class="text-muted small">' + 
                (selectedDriverId ? 'Select product first' : 'Please select a driver first') + 
                '</div>');
            $row.find('.max-quantity-input').val(0);
        }
    });

    // Function to display batches from driver's inventory
    function displayDriverBatches(rowIndex, batches) {
        var container = $('#batchContainer' + rowIndex);
        
        var html = '<select class="form-control form-control-sm batch-select" data-row="' + rowIndex + '">';
        html += '<option value="">-- Select Batch --</option>';
        
        batches.forEach(function(batch) {
            var expiryInfo = batch.formatted_expiry_date ? ' (Exp: ' + batch.formatted_expiry_date + ')' : '';
            var expiringClass = batch.is_expiring_soon ? ' class="expiring-soon"' : '';
            
            html += `<option value="${batch.batch_id}" data-quantity="${batch.quantity}"${expiringClass}>
                ${batch.batch_code} - Available: ${batch.quantity}${expiryInfo}
                ${batch.is_expiring_soon ? ' ⚠️ Expiring Soon' : ''}
            </option>`;
        });
        
        html += '</select>';
        
        // Remove the quantity input and button - just the select dropdown
        container.html(html);
        
        // Handle batch selection - directly set the batch ID when selected
        container.find('.batch-select').on('change', function() {
            var selectedBatchId = $(this).val();
            var $row = $('#itemsBody tr[data-index="' + rowIndex + '"]');
            
            if (selectedBatchId) {
                $row.find('.batch-allocations-input').val(selectedBatchId);
                
                // Optional: Show which batch is selected with green border
                $(this).css('border-color', '#28a745');
            } else {
                $row.find('.batch-allocations-input').val('');
                $(this).css('border-color', '#ced4da');
            }
        });
    }

    // Function to validate quantity against available inventory
    function validateQuantityAgainstInventory(rowIndex, maxQuantity) {
        var $quantityInput = $('#itemsBody tr[data-index="' + rowIndex + '"] .quantity-input');
        
        $quantityInput.off('input').on('input', function() {
            var val = parseInt($(this).val()) || 0;
            
            if (val > maxQuantity) {
                $(this).addClass('is-invalid');
                $(this).closest('tr').find('.quantity-error').text('Cannot exceed available stock: ' + maxQuantity);
            } else {
                $(this).removeClass('is-invalid');
                $(this).closest('tr').find('.quantity-error').text('');
            }
        });
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

    // Add item button
    $('#addItemBtn').on('click', function() {
        if (!selectedDriverId) {
            showNotification('error', 'Please select a driver first');
            return;
        }
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
                $(this).find('.product-id-input, .quantity-input, .batch-allocations-input, .max-quantity-input').each(function() {
                    var name = $(this).attr('name');
                    if (name && name.includes('items[')) {
                        var newName = name.replace(/items\[\d+\]/, 'items[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
                
                // Update batch container ID
                $(this).find('.batch-allocation-container').attr('id', 'batchContainer' + index);
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
        $('#createReturnForm')[0].reset();
        $('#driver_id_create').val('').trigger('change');
        $('#driverError, #itemsError').text('');
        $('#remarks').val('');
        
        // Initialize with empty table showing message
        initializeItemsTable('create');
    });

    // Validate create form
    $('#createReturnForm').submit(function(e) {
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
        var batchCombinations = new Set(); // To check duplicate product+batch
        
        $('#itemsBody tr.item-row').each(function(index) {
            var productId = $(this).find('.product-select').val();
            var quantity = $(this).find('.quantity-input').val();
            var batchId = $(this).find('.batch-allocations-input').val();
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
            } else {
                // Check against max quantity
                var maxQty = parseInt($(this).find('.max-quantity-input').val()) || 0;
                if (parseInt(quantity) > maxQty) {
                    quantityError.text('Quantity exceeds available stock (' + maxQty + ')');
                    hasErrors = true;
                }
            }
            
            // Validate batch selection
            if (!batchId) {
                quantityError.text('Please select a batch');
                hasErrors = true;
            } else {
                var comboKey = productId + '_' + batchId;
                if (batchCombinations.has(comboKey)) {
                    quantityError.text('Duplicate batch selection');
                    hasErrors = true;
                } else {
                    batchCombinations.add(comboKey);
                }
            }
            
            // Add to items array only if valid
            if (productId && quantity && quantity >= 1 && batchId) {
                items.push({
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity),
                    batch_id: parseInt(batchId)
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
    // EDIT MODAL FUNCTIONS
    // ============================================
    
    // Handle edit modal opening
    $(document).on('click', '.edit-return-btn', function(e) {
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
        var formAction = '{{ route("inventoryReturns.update", ":id") }}'.replace(':id', requestId);
        $('#editReturnForm').attr('action', formAction);
        
        // Set driver
        $('#driver_id_edit').val(requestData.driver_id).trigger('change');
        $('#remarksEdit').val(requestData.remarks || '');
        
        // Store selected driver
        selectedDriverId = requestData.driver_id;
        
        // Load driver inventory first, then populate items
        loadDriverInventory(requestData.driver_id, 'edit', function() {
            populateEditItems(requestData);
        });
        
        $('#editRequest').modal('show');
    });

    // Function to populate edit items after inventory is loaded
    function populateEditItems(requestData) {
        $('#editItemsBody').empty();
        editItemCounter = 0;
        
        if (requestData.items && Array.isArray(requestData.items) && requestData.items.length > 0) {
            requestData.items.forEach(function(item) {
                addEditItemRow(
                    item.product_id,
                    getProductName(item.product_id),
                    item.quantity,
                    item.batch_id || null
                );
            });
        }
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
                    <input type="hidden" class="edit-batch-allocations-input" name="items[${rowIndex}][batch_id]" value="${existingBatchId || ''}">
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
        
        // If we have driver inventory, filter the products
        if (driverInventory && driverInventory.length > 0) {
            var $select = $('#edit_product_' + rowIndex);
            filterProductDropdown($select);
        }
        
        if (productId) {
            loadEditBatchesForProduct(rowIndex, productId, existingBatchId);
        }
        
        editItemCounter++;
        
        // Enable remove buttons if more than one row
        if ($('#editItemsBody tr').length > 1) {
            $('#editItemsBody tr:first .remove-edit-item-btn').prop('disabled', false);
        }
    }

    // Helper function to filter product dropdown based on driver inventory
    function filterProductDropdown($select) {
        var currentValue = $select.val();
        
        // Clear all options except the first one
        $select.find('option:not(:first)').remove();
        
        // Get unique products from inventory
        var uniqueProducts = [];
        var productIds = [];
        
        driverInventory.forEach(function(item) {
            if (item && item.product_id && !productIds.includes(item.product_id)) {
                productIds.push(item.product_id);
                uniqueProducts.push(item);
            }
        });
        
        // Add options for products the driver has
        uniqueProducts.forEach(function(item) {
            var option = new Option(
                item.product_name + ' (Total: ' + item.total_quantity + ')', 
                item.product_id,
                false,
                item.product_id == currentValue
            );
            $(option).data('total-quantity', item.total_quantity);
            $(option).data('batches', item.batches);
            $select.append(option);
        });
        
        // If no products available, add a disabled option
        if (uniqueProducts.length === 0) {
            var option = new Option('No products available', '', true, true);
            $(option).prop('disabled', true);
            $select.append(option);
        }
        
        // Refresh Select2
        $select.trigger('change.select2');
    }

    // Add edit item button
    $('#addEditItemBtn').on('click', function() {
        if (!selectedDriverId) {
            showNotification('error', 'Please select a driver first');
            return;
        }
        addEditItemRow();
    });

    // Product change event for edit modal
    $(document).on('change', '.edit-product-select', function(e) {
        var $this = $(this);
        var index = $this.data('index');
        var productId = $this.val();
        var $row = $this.closest('tr');
        
        // Set the hidden input value
        $row.find('.edit-product-id-input').val(productId);
        $row.find('.edit-product-error').text('');
        
        if (productId && selectedDriverId) {
            var existingBatchId = $row.find('.edit-batch-allocations-input').val();
            loadEditBatchesForProduct(index, productId, existingBatchId);
        } else {
            $('#editBatchContainer' + index).html('<div class="text-muted small">' + 
                (selectedDriverId ? 'Select product first' : 'Please select a driver first') + 
                '</div>');
        }
    });

    // Function to load edit batches for a product from driver's inventory
    function loadEditBatchesForProduct(rowIndex, productId, existingBatchId = null) {
        if (!selectedDriverId) {
            selectedDriverId = $('#driver_id_edit').val();
        }
        
        if (!selectedDriverId) {
            $('#editBatchContainer' + rowIndex).html('<div class="text-danger small">Please select a driver first</div>');
            return;
        }
        
        var container = $('#editBatchContainer' + rowIndex);
        container.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading batches...</div>');
        
        // Find product in driver inventory
        var productInventory = driverInventory.find(p => p.product_id == productId);
        
        if (productInventory && productInventory.batches && productInventory.batches.length > 0) {
            var html = '<select class="form-control form-control-sm edit-batch-select" data-row="' + rowIndex + '">';
            html += '<option value="">-- Select Batch --</option>';
            
            productInventory.batches.forEach(function(batch) {
                var selected = (existingBatchId && batch.batch_id == existingBatchId) ? 'selected' : '';
                var expiryInfo = batch.formatted_expiry_date ? ' (Exp: ' + batch.formatted_expiry_date + ')' : '';
                var expiringClass = batch.is_expiring_soon ? ' class="expiring-soon"' : '';
                
                html += `<option value="${batch.batch_id}" data-quantity="${batch.quantity}" ${selected}${expiringClass}>
                    ${batch.batch_code} - Available: ${batch.quantity}${expiryInfo}
                    ${batch.is_expiring_soon ? ' ⚠️ Expiring Soon' : ''}
                </option>`;
            });
            
            html += '</select>';
            container.html(html);
            
            // Set max quantity for the row
            var $row = $('#editItemsBody tr[data-index="' + rowIndex + '"]');
            $row.data('max-quantity', productInventory.total_quantity);
            
            // Add batch selection handling for edit modal - SIMPLIFIED
            container.find('.edit-batch-select').on('change', function() {
                var selectedBatchId = $(this).val();
                var $editRow = $('#editItemsBody tr[data-index="' + rowIndex + '"]');
                
                if (selectedBatchId) {
                    $editRow.find('.edit-batch-allocations-input').val(selectedBatchId);
                    $(this).css('border-color', '#28a745');
                } else {
                    $editRow.find('.edit-batch-allocations-input').val('');
                    $(this).css('border-color', '#ced4da');
                }
            });
            
            // If there's an existing batch ID, make sure it's selected and update the hidden input
            if (existingBatchId) {
                container.find('.edit-batch-select').val(existingBatchId).trigger('change');
            }
            
        } else {
            container.html('<div class="text-warning small">No batches available for this product</div>');
        }
    }

    // Remove edit item button
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

    // Validate edit form
    $('#editReturnForm').submit(function(e) {
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
        var batchCombinations = new Set();
        
        $('#editItemsBody tr.edit-item-row').each(function(index) {
            var productId = $(this).find('.edit-product-select').val();
            var quantity = $(this).find('.edit-quantity-input').val();
            var batchId = $(this).find('.edit-batch-allocations-input').val();
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
            } else {
                // Check against max quantity from inventory
                var productInventory = driverInventory.find(p => p.product_id == productId);
                var maxQty = productInventory ? productInventory.total_quantity : 0;
                if (parseInt(quantity) > maxQty) {
                    quantityError.text('Quantity exceeds available stock (' + maxQty + ')');
                    hasErrors = true;
                }
            }
            
            // Validate batch selection
            if (!batchId) {
                quantityError.text('Please select a batch');
                hasErrors = true;
            } else {
                var comboKey = productId + '_' + batchId;
                if (batchCombinations.has(comboKey)) {
                    quantityError.text('Duplicate batch selection');
                    hasErrors = true;
                } else {
                    batchCombinations.add(comboKey);
                }
            }
            
            // Add to items array only if valid
            if (productId && quantity && quantity >= 1 && batchId) {
                items.push({
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity),
                    batch_id: parseInt(batchId)
                });
            }
        });
        
        if (items.length === 0) {
            $('#editItemsError').text('Please add at least one valid item with batch selected');
            hasErrors = true;
        }
        
        if (hasErrors) {
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
    // SAVE AND APPROVE FUNCTIONALITY
    // ============================================
    
    // Handle Save & Approve button click
    $(document).on('click', '#saveAndApproveBtn', function() {
        if (confirm('Are you sure you want to save changes and approve this return? This will immediately add the items back to main inventory.')) {
            // Set the hidden field to indicate we want to save and approve
            $('#saveAndApprove').val('1');
            
            // Submit the form
            $('#editReturnForm').submit();
        }
    });
    
    // Reset the save_and_approve flag when form is submitted normally
    $('#editReturnForm').submit(function(e) {
        // If not triggered by saveAndApproveBtn, ensure flag is 0
        if ($('#saveAndApprove').val() !== '1') {
            $('#saveAndApprove').val('0');
        }
    });
    
    // ============================================
    // APPROVE/REJECT FUNCTIONS
    // ============================================
    
    // Handle approve action from view modal
    $(document).on('click', '#approveFromViewBtn', function() {
        if (confirm('Are you sure you want to approve this inventory return?')) {
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
        
        if (confirm('Are you sure you want to delete this inventory return?')) {
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
    // APPROVE/REJECT HELPER FUNCTIONS
    // ============================================
    
    // Helper function to approve request
    function approveRequest(requestId) {
        ShowLoad();
        
        var url = '{{ route("inventoryReturns.approve", ":id") }}';
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
                // Update Stock Returns badge
                var stockReturnBadge = $('#stockReturnBadge');
                if (data.pendingStockReturns > 0) {
                    stockReturnBadge.text(data.pendingStockReturns).show();
                } else {
                    stockReturnBadge.hide();
                }
            }
        });
    }

    // Helper function to reject request
    function rejectRequest(requestId, rejectReason) {
        ShowLoad();
        
        var url = '{{ route("inventoryReturns.reject", ":id") }}';
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
    
    // ============================================
    // CLEAR MODALS ON CLOSE
    // ============================================
    
    // Clear edit modal when closed
    $('#editRequest').on('hidden.bs.modal', function () {
        $('#editReturnForm')[0].reset();
        $('#driver_id_edit').val('').trigger('change');
        $('#editItemsBody').empty();
        editItemCounter = 0;
        $('#saveAndApprove').val('0');
        $('#editItemsError, #driverEditError').text('');
        driverInventory = [];
        selectedDriverId = null;
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

// Keyboard shortcut for creating new return
$(document).keyup(function(e) {
    if(e.altKey && e.keyCode == 78 && ($('#createRequest').length > 0)) {
        $('#createRequest').modal('show');
    }
});
</script>
@endpush