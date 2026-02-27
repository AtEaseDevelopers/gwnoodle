@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Credit Notes</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Credit Notes
                             <a class="pull-right" href="{{ route('credit-notes.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                             <table class="table table-striped table-bordered">
                                 <thead>
                                     <tr>
                                         <th>SKU</th>
                                         <th>Currency</th>
                                         <th>Status</th>
                                         <th>E-Invoices</th>
                                         <th>Submission Date</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($creditNotes as $creditNote)
                                         <tr>
                                             <td>{{ $creditNote->sku }}</td>
                                             <td>{{ $creditNote->currency }}</td>
                                             <td>
                                                 @if($creditNote->status === 'Valid')
                                                     <span class="badge badge-success">{{ $creditNote->status }}</span>
                                                 @elseif($creditNote->status === 'Cancelled')
                                                     <span class="badge badge-warning">{{ $creditNote->status }}</span>
                                                 @else
                                                     <span class="badge badge-danger">{{ $creditNote->status }}</span>
                                                 @endif
                                             </td>
                                             <td>{{ $creditNote->einvoices->count() }}</td>
                                             <td>{{ $creditNote->submission_date ? $creditNote->submission_date->format('Y-m-d H:i:s') : '-' }}</td>
                                             <td>
                                                 @include('credit_notes.datatables_actions', ['creditNote' => $creditNote])
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="6" class="text-center">No credit notes found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            HideLoad();
            
            $(document).on('click', '[data-action="cancel-credit-note"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    cancelCreditNote(id);
                }
            });
        });

        function cancelCreditNote(id){
            if (typeof Swal === 'undefined') {
                alert('SweetAlert is not loaded. Please refresh the page.');
                return;
            }
            
            Swal.fire({
                title: 'Cancel Credit Note',
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
                        url: "{{ url('/credit-notes') }}/" + id + "/cancel",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            reason: result.value
                        },
                        success: function(response){
                            HideLoad();
                            if(response.success){
                                location.reload();
                                noti('s','Success', 'Credit note cancelled successfully!');
                            } else {
                                noti('e','Error', response.message);
                            }
                        },
                        error: function(error) {
                            HideLoad();
                            noti('e','Error', error.responseJSON?.message || 'Failed to cancel credit note');
                        }
                    });
                }
            });
        }
    </script>
@endpush

