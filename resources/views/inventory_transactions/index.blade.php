@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('inventory_transactions.inventory_transactions') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('inventory_transactions.inventory_transactions') }}
                         </div>
                         <div class="card-body">
                             @include('inventory_transactions.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>

    <!-- View Transaction Modal -->
    <div class="modal fade" id="viewTransactionModal" tabindex="-1" role="dialog" aria-labelledby="viewTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewTransactionModalLabel">
                        <i class="fa fa-eye"></i> Transaction Details
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row" id="transaction-details">
                        <div class="col-md-12 text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading transaction details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Handle view button click
            $(document).on('click', '.view-transaction', function() {
                var transactionId = $(this).data('id');
                
                // Show loading spinner
                $('#transaction-details').html(`
                    <div class="col-md-12 text-center">
                        <i class="fa fa-spinner fa-spin fa-3x"></i>
                        <p>Loading transaction details...</p>
                    </div>
                `);
                
                // Fetch transaction details via AJAX
                $.ajax({
                    url: '{{ route("inventoryTransactions.show", "") }}/' + transactionId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        displayTransactionDetails(response);
                    },
                    error: function(xhr, status, error) {
                        $('#transaction-details').html(`
                            <div class="col-md-12 text-center text-danger">
                                <i class="fa fa-exclamation-triangle fa-3x"></i>
                                <p>Error loading transaction details. Please try again.</p>
                                <p class="small">${error}</p>
                            </div>
                        `);
                    }
                });
            });
            
            function displayTransactionDetails(transaction) {
                // Format type
                var typeBadge = '';
                switch(transaction.type) {
                    case 1:
                        typeBadge = '<span class="badge badge-success">Stock In</span>';
                        break;
                    case 2:
                        typeBadge = '<span class="badge badge-danger">Stock Out</span>';
                        break;
                    case 3:
                        typeBadge = '<span class="badge badge-warning">Stock Return</span>';
                        break;
                    case 4:
                        typeBadge = '<span class="badge badge-secondary">Stock Transfer</span>';
                        break;
                    default:
                        typeBadge = '<span class="badge badge-secondary">Unknown</span>';
                }
                
                // Format quantity
                var quantityClass = transaction.quantity > 0 ? 'text-success' : (transaction.quantity < 0 ? 'text-danger' : 'text-muted');
                var quantitySign = transaction.quantity > 0 ? '+' : '';
                
                var html = `
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Basic Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th>Date:</th>
                                        <td>${transaction.date_formatted || transaction.date}</td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td>${typeBadge}</td>
                                    </tr>
                                    <tr>
                                        <th>Quantity:</th>
                                        <td class="${quantityClass} font-weight-bold">${quantitySign}${Math.abs(transaction.quantity).toLocaleString()}</td>
                                    </tr>
                                    <tr>
                                        <th>User:</th>
                                        <td>${transaction.user || '-'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Product Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th style="width: 40%">Product:</th>
                                        <td>${transaction.product ? transaction.product.name : '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Batch Code:</th>
                                        <td>${transaction.batch ? transaction.batch.batch_code : '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Van Plate:</th>
                                        <td>${transaction.lorry ? transaction.lorry.lorryno : '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Warehouse:</th>
                                        <td>${transaction.warehouse ? transaction.warehouse.name : '-'}</td>
                                    </tr>
                                 
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Additional Information</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th style="width: 15%">Remark:</th>
                                        <td>${transaction.remark || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Created At:</th>
                                        <td>${transaction.created_at ? new Date(transaction.created_at).toLocaleString() : '-'}</td>
                                    </tr>
                                 
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#transaction-details').html(html);
            }
        });

        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            }
        });
    </script>
@endpush