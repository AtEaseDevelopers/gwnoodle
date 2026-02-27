@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Debit Notes</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Debit Notes
                             <a class="pull-right" href="{{ route('debit-notes.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
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
                                     @forelse($debitNotes as $debitNote)
                                         <tr>
                                             <td>{{ $debitNote->sku }}</td>
                                             <td>{{ $debitNote->currency }}</td>
                                             <td>
                                                 @if($debitNote->status === 'Valid')
                                                     <span class="badge badge-success">{{ $debitNote->status }}</span>
                                                 @elseif($debitNote->status === 'Cancelled')
                                                     <span class="badge badge-warning">{{ $debitNote->status }}</span>
                                                 @else
                                                     <span class="badge badge-danger">{{ $debitNote->status }}</span>
                                                 @endif
                                             </td>
                                             <td>{{ $debitNote->einvoices->count() }}</td>
                                             <td>{{ $debitNote->submission_date ? $debitNote->submission_date->format('Y-m-d H:i:s') : '-' }}</td>
                                             <td>
                                                 @include('debit_notes.datatables_actions', ['debitNote' => $debitNote])
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="6" class="text-center">No debit notes found.</td>
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
            
            $(document).on('click', '[data-action="cancel-debit-note"]', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (id) {
                    cancelDebitNote(id);
                }
            });
        });

        function cancelDebitNote(id){
            if (typeof Swal === 'undefined') {
                alert('SweetAlert is not loaded. Please refresh the page.');
                return;
            }
            
            Swal.fire({
                title: 'Cancel Debit Note',
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
                        url: "{{ url('/debit-notes') }}/" + id + "/cancel",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            reason: result.value
                        },
                        success: function(response){
                            HideLoad();
                            if(response.success){
                                location.reload();
                                noti('s','Success', 'Debit note cancelled successfully!');
                            } else {
                                noti('e','Error', response.message);
                            }
                        },
                        error: function(error) {
                            HideLoad();
                            noti('e','Error', error.responseJSON?.message || 'Failed to cancel debit note');
                        }
                    });
                }
            });
        }
    </script>
@endpush

