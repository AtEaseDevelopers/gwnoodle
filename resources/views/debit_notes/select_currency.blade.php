@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('debit-notes.index') }}">Debit Notes</a></li>
        <li class="breadcrumb-item active">Step 3: Currency Selection</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Step 3: Currency Selection
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('debit-notes.select-einvoice') }}">
                                @csrf
                                <input type="hidden" name="note_type" value="{{ $noteType }}">
                                <input type="hidden" name="customer_id" value="{{ $customerId }}">
                                
                                <div class="form-group">
                                    <label for="currency">Select Currency:</label>
                                    <select name="currency" id="currency" class="form-control" required>
                                        <option value="MYR">MYR (Malaysian Ringgit)</option>
                                        <option value="SGD">SGD (Singapore Dollar)</option>
                                        <option value="USD">USD (US Dollar)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Next: Select E-Invoice</button>
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

