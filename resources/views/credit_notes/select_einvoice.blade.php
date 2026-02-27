@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('credit-notes.index') }}">Credit Notes</a></li>
        <li class="breadcrumb-item active">Step 4: E-Invoice Selection</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Step 4: E-Invoice Selection
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('credit-notes.update-einvoice') }}">
                                @csrf
                                <input type="hidden" name="note_type" value="{{ $noteType }}">
                                <input type="hidden" name="customer_id" value="{{ $customerId }}">
                                <input type="hidden" name="currency" value="{{ $currency }}">
                                
                                <div class="form-group">
                                    <label>Select E-Invoices:</label>
                                    @forelse($einvoices as $einvoice)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="einvoice_ids[]" value="{{ $einvoice->id }}" id="einvoice_{{ $einvoice->id }}">
                                            <label class="form-check-label" for="einvoice_{{ $einvoice->id }}">
                                                <strong>{{ $einvoice->sku }}</strong> - 
                                                Invoice: {{ $einvoice->invoice->invoiceno ?? 'N/A' }} - 
                                                Customer: {{ $einvoice->invoice->customer->company ?? 'N/A' }}
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-danger">No valid e-invoices found for this customer and currency.</p>
                                    @endforelse
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" {{ $einvoices->isEmpty() ? 'disabled' : '' }}>Next: Enter Credit Note Details</button>
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
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush

