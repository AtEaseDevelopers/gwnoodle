@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('debit-notes.index') }}">Debit Notes</a></li>
        <li class="breadcrumb-item active">Debit Note Details</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Debit Note: {{ $debitNote->sku }}
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>SKU:</th>
                                    <td>{{ $debitNote->sku }}</td>
                                </tr>
                                <tr>
                                    <th>Currency:</th>
                                    <td>{{ $debitNote->currency }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($debitNote->status === 'Valid')
                                            <span class="badge badge-success">{{ $debitNote->status }}</span>
                                        @elseif($debitNote->status === 'Cancelled')
                                            <span class="badge badge-warning">{{ $debitNote->status }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $debitNote->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>UUID:</th>
                                    <td>{{ $debitNote->uuid ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Long ID:</th>
                                    <td>{{ $debitNote->longId ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Submission Date:</th>
                                    <td>{{ $debitNote->submission_date ? $debitNote->submission_date->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Validated Time:</th>
                                    <td>{{ $debitNote->validated_time ? $debitNote->validated_time->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <h5 class="mt-4">Related E-Invoices:</h5>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Invoice No</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debitNote->einvoices as $einvoice)
                                        <tr>
                                            <td>{{ $einvoice->sku }}</td>
                                            <td>{{ $einvoice->invoice->invoiceno ?? 'N/A' }}</td>
                                            <td>{{ $einvoice->invoice->customer->company ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $einvoice->status === 'Valid' ? 'success' : 'danger' }}">
                                                    {{ $einvoice->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            <div class="mt-3">
                                <a href="{{ route('debit-notes.index') }}" class="btn btn-secondary">Back to List</a>
                                @if($debitNote->uuid && $debitNote->status === 'Valid' && $debitNote->submission_date && $debitNote->submission_date->diffInHours(now()) <= 72)
                                    <button type="button" class="btn btn-danger" data-action="cancel-debit-note" data-id="{{ Crypt::encrypt($debitNote->id) }}">
                                        <i class="fa fa-times"></i> Cancel Debit Note
                                    </button>
                                @endif
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
                                window.location.href = "{{ route('debit-notes.index') }}";
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

