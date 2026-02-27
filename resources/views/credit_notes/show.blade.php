@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('credit-notes.index') }}">Credit Notes</a></li>
        <li class="breadcrumb-item active">Credit Note Details</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Credit Note: {{ $creditNote->sku }}
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>SKU:</th>
                                    <td>{{ $creditNote->sku }}</td>
                                </tr>
                                <tr>
                                    <th>Currency:</th>
                                    <td>{{ $creditNote->currency }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($creditNote->status === 'Valid')
                                            <span class="badge badge-success">{{ $creditNote->status }}</span>
                                        @elseif($creditNote->status === 'Cancelled')
                                            <span class="badge badge-warning">{{ $creditNote->status }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $creditNote->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>UUID:</th>
                                    <td>{{ $creditNote->uuid ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Long ID:</th>
                                    <td>{{ $creditNote->longId ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Submission Date:</th>
                                    <td>{{ $creditNote->submission_date ? $creditNote->submission_date->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Validated Time:</th>
                                    <td>{{ $creditNote->validated_time ? $creditNote->validated_time->format('Y-m-d H:i:s') : 'N/A' }}</td>
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
                                    @foreach($creditNote->einvoices as $einvoice)
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
                                <a href="{{ route('credit-notes.index') }}" class="btn btn-secondary">Back to List</a>
                                @if($creditNote->uuid && $creditNote->status === 'Valid' && $creditNote->submission_date && $creditNote->submission_date->diffInHours(now()) <= 72)
                                    <button type="button" class="btn btn-danger" data-action="cancel-credit-note" data-id="{{ Crypt::encrypt($creditNote->id) }}">
                                        <i class="fa fa-times"></i> Cancel Credit Note
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
                                window.location.href = "{{ route('credit-notes.index') }}";
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

