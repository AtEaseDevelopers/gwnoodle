@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">E-Invoices</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            E-Invoices
                            <a class="pull-right" href="#" onclick="showNoteTypeSelection(); return false;" title="Create Note">
                                <i class="fa fa-file-text-o fa-lg"></i> Create Note
                            </a>
                        </div>
                         <div class="card-body">
                             @include('einvoices.table')
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
            $(document).on('click', '[data-action="cancel-einvoice"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    cancelDocument(id);
                }
            });

            $(document).on('click', '[data-action="refresh-einvoice"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    refreshStatus(id);
                }
            });

            $(document).on('click', '[data-action="details-einvoice"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    getFullDetails(id);
                }
            });
        });

        function refreshStatus(id){
            ShowLoad();
            $.ajax({
                url: "{{ url('/einvoices') }}/" + id + "/refresh-status",
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

        function cancelDocument(id){
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
                        url: "{{ url('/einvoices') }}/" + id + "/cancel",
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

        function getFullDetails(id){
            ShowLoad();
            $.ajax({
                url: "{{ url('/einvoices') }}/" + id + "/details",
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

        function showNoteTypeSelection(){
            if (typeof Swal === 'undefined') {
                // Fallback to simple redirect if SweetAlert is not available
                window.location.href = "{{ route('credit-notes.create') }}";
                return;
            }
            
            Swal.fire({
                title: 'Create Note',
                html: `
                    <div style="text-align: center; padding: 20px;">
                        <p style="margin-bottom: 20px;">Select the type of note you want to create:</p>
                        <div style="display: flex; gap: 20px; justify-content: center;">
                            <div style="flex: 1; max-width: 200px;">
                                <div style="border: 2px solid #007bff; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.3s;" 
                                     id="credit-note-option" 
                                     onmouseover="this.style.backgroundColor='#f0f8ff'" 
                                     onmouseout="this.style.backgroundColor='white'">
                                    <h5 style="color: #007bff; margin-bottom: 10px;">Credit Note</h5>
                                    <p style="font-size: 14px; color: #666;">Create a credit note for submitted e-invoices</p>
                                </div>
                            </div>
                            <div style="flex: 1; max-width: 200px;">
                                <div style="border: 2px solid #28a745; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.3s;" 
                                     id="debit-note-option" 
                                     onmouseover="this.style.backgroundColor='#f0fff4'" 
                                     onmouseout="this.style.backgroundColor='white'">
                                    <h5 style="color: #28a745; margin-bottom: 10px;">Debit Note</h5>
                                    <p style="font-size: 14px; color: #666;">Create a debit note for submitted e-invoices</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                showConfirmButton: false,
                cancelButtonText: 'Cancel',
                didOpen: () => {
                    document.getElementById('credit-note-option').addEventListener('click', function() {
                        window.location.href = "{{ route('credit-notes.create') }}";
                    });
                    document.getElementById('debit-note-option').addEventListener('click', function() {
                        window.location.href = "{{ route('debit-notes.create') }}";
                    });
                }
            });
        }
    </script>
@endpush

