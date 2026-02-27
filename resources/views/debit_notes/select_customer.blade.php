@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('debit-notes.index') }}">Debit Notes</a></li>
        <li class="breadcrumb-item active">Step 2: Customer Selection</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Step 2: Customer Selection
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('debit-notes.select-currency') }}">
                                @csrf
                                <input type="hidden" name="note_type" value="{{ $noteType }}">
                                
                                <div class="form-group">
                                    <label for="customer_id">Select Customer:</label>
                                    <select name="customer_id" id="customer_id" class="form-control" required>
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->company }} ({{ $customer->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Next: Select Currency</button>
                                    <a href="{{ route('debit-notes.index') }}" class="btn btn-secondary">Back</a>
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

