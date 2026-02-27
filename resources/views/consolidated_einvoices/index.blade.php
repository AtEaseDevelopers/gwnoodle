@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Consolidated E-Invoices</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Consolidated E-Invoices
                        </div>
                        <div class="card-body">
                            @include('consolidated_einvoices.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('click', '[data-action="cancel-consolidated"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    cancelConsolidatedDocument(id);
                }
            });

            $(document).on('click', '[data-action="refresh-consolidated"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    refreshConsolidatedStatus(id);
                }
            });

            $(document).on('click', '[data-action="details-consolidated"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    getConsolidatedFullDetails(id);
                }
            });
        });

        function refreshStatus(id){
            ShowLoad();
            $.ajax({
                url: "{{ url('/consolidated-einvoices') }}/" + id + "/refresh-status",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response){
                    HideLoad();
                    if(response.success){
                        $('.buttons-reload').click();
                        noti('s','Success', 'Status refreshed successfully!');
                    } else {
                        noti('e','Error', response.message);
                    }
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Error', error.responseJSON?.message || 'Failed to refresh status');
                }
            });
        }

        function refreshConsolidatedStatus(id){
            refreshStatus(id);
        }

        function cancelConsolidatedDocument(id){
            if (typeof Swal === 'undefined') {
                alert('SweetAlert is not loaded. Please refresh the page.');
                return;
            }
            
            Swal.fire({
                title: 'Cancel Document',
                html: '<input id="cancel-reason" class="swal2-input" placeholder="Cancellation reason" required>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cancel Document',
                cancelButtonText: 'Close',
                preConfirm: () => {
                    const reason = document.getElementById('cancel-reason').value;
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a cancellation reason');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    ShowLoad();
                    $.ajax({
                        url: "{{ url('/consolidated-einvoices') }}/" + id + "/cancel",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            reason: result.value
                        },
                        success: function(response){
                            HideLoad();
                            if(response.success){
                                $('.buttons-reload').click();
                                noti('s','Success', 'Document cancelled successfully!');
                            } else {
                                noti('e','Error', response.message);
                            }
                        },
                        error: function(error) {
                            HideLoad();
                            noti('e','Error', error.responseJSON?.message || 'Failed to cancel document');
                        }
                    });
                }
            });
        }

        function getConsolidatedFullDetails(id){
            ShowLoad();
            $.ajax({
                url: "{{ url('/consolidated-einvoices') }}/" + id + "/details",
                type: "GET",
                success: function(response){
                    HideLoad();
                    if(response.success){
                        let detailsHtml = '<div style="text-align: left; max-height: 500px; overflow-y: auto;"><pre style="white-space: pre-wrap; word-wrap: break-word;">' + JSON.stringify(response.details, null, 2) + '</pre></div>';
                        Swal.fire({
                            title: 'Document Details',
                            html: detailsHtml,
                            width: '80%',
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    } else {
                        noti('e','Error', response.message);
                    }
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Error', error.responseJSON?.message || 'Failed to get document details');
                }
            });
        }
    </script>
@endpush

