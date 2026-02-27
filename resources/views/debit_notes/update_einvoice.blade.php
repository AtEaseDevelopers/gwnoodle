@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('debit-notes.index') }}">Debit Notes</a></li>
        <li class="breadcrumb-item active">Step 5: Enter Debit Note Details</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Step 5: Enter Debit Note Details
                        </div>
                        <div class="card-body">
                            <form id="debitNoteForm">
                                @csrf
                                <input type="hidden" name="note_type" value="{{ $noteType }}">
                                <input type="hidden" name="customer_id" value="{{ $customerId }}">
                                <input type="hidden" name="currency" value="{{ $currency }}">
                                @foreach($einvoices as $einvoice)
                                    <input type="hidden" name="einvoice_ids[]" value="{{ $einvoice->id }}">
                                @endforeach
                                
                                <h5>Selected E-Invoices:</h5>
                                <ul>
                                    @foreach($einvoices as $einvoice)
                                        <li>{{ $einvoice->sku }} - {{ $einvoice->invoice->invoiceno ?? 'N/A' }}</li>
                                    @endforeach
                                </ul>
                                
                                <hr>
                                
                                <div id="changesContainer">
                                    <h5>Debit Note Items:</h5>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addChangeItem()">
                                            <i class="fa fa-plus"></i> Add Item
                                        </button>
                                    </div>
                                    
                                    <table class="table table-bordered" id="changesTable">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th>Amount ({{ $currency }})</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" name="changes[0][description]" class="form-control" required></td>
                                                <td><input type="number" step="0.01" name="changes[0][changes]" class="form-control" required></td>
                                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeChangeItem(this)" disabled>Remove</button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Submit Debit Note</button>
                                    <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let changeIndex = 1;
    
    function addChangeItem() {
        const tbody = document.querySelector('#changesTable tbody');
        const row = tbody.insertRow();
        row.innerHTML = `
            <td><input type="text" name="changes[${changeIndex}][description]" class="form-control" required></td>
            <td><input type="number" step="0.01" name="changes[${changeIndex}][changes]" class="form-control" required></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeChangeItem(this)">Remove</button></td>
        `;
        changeIndex++;
        updateRemoveButtons();
    }
    
    function removeChangeItem(button) {
        button.closest('tr').remove();
        updateRemoveButtons();
    }
    
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#changesTable tbody tr');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('button');
            removeBtn.disabled = rows.length === 1;
        });
    }
    
    $(document).ready(function () {
        HideLoad();
    });
    
    $('#debitNoteForm').on('submit', function(e) {
        e.preventDefault();
        ShowLoad();
        
        $.ajax({
            url: '{{ route("debit-notes.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                HideLoad();
                if (response.success) {
                    noti('s', 'Success', 'Debit note submitted successfully!');
                    setTimeout(function() {
                        window.location.href = '{{ route("debit-notes.index") }}';
                    }, 1500);
                } else {
                    noti('e', 'Error', response.message);
                }
            },
            error: function(xhr) {
                HideLoad();
                const message = xhr.responseJSON?.message || 'Failed to submit debit note';
                noti('e', 'Error', message);
            }
        });
    });
</script>
@endpush

