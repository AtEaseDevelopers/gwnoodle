@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('credit-notes.index') }}">Credit Notes</a></li>
        <li class="breadcrumb-item active">Create Note</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            Create Credit Note - Step 1: Note Type Selection
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <h4 class="mb-4">Select Credit or Debit Note to proceed</h4>
                                    
                                    <form method="POST" action="{{ route('credit-notes.select-customer') }}">
                                        @csrf
                                        <input type="hidden" name="note_type" value="credit">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title">Credit Note</h5>
                                                        <p class="card-text">Create a credit note for submitted e-invoices</p>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fa fa-arrow-right"></i> Select Credit Note
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-success">
                                                    <div class="card-body text-center">
                                                        <h5 class="card-title">Debit Note</h5>
                                                        <p class="card-text">Create a debit note for submitted e-invoices</p>
                                                        <a href="{{ route('debit-notes.create') }}" class="btn btn-success">
                                                            <i class="fa fa-arrow-right"></i> Select Debit Note
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
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
        });
    </script>
@endpush

