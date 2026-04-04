@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('productBatches.index') !!}">{{ __('Product Batch')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('Edit Product Batch')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>{{ __('Edit Product Batch')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($productBatch, ['route' => ['productBatches.update', encrypt($productBatch->id)], 'method' => 'patch']) !!}

                            <div class="container-fluid">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Edit Product Batch: {{ $productBatch->batch_code }}</h3>
                                    </div>
                                    <div class="card-body">
                                        @if ($errors->any())
                                            <div class="alert alert-danger">
                                                <ul class="mb-0">
                                                    @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @include('flash::message')

                                        {!! Form::model($productBatch, [
                                            'route' => ['productBatches.update', Crypt::encrypt($productBatch->id)],
                                            'method' => 'PATCH',
                                            'id' => 'productBatchEditForm'
                                        ]) !!}

                                        <div class="row">
                                            <!-- Left Column - Batch Information -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-info text-white">
                                                        <h5 class="card-title mb-0">
                                                            <i class="fa fa-info-circle"></i> Batch Information
                                                        </h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <!-- Product Selection -->
                                                        <div class="form-group">
                                                            {!! Form::label('product_id', 'Product') !!} <span class="text-danger">*</span>
                                                            {!! Form::select('product_id', $products, null, [
                                                                'class' => 'form-control select2',
                                                                'placeholder' => 'Select Product',
                                                                'required',
                                                                'id' => 'product_id'
                                                            ]) !!}
                                                        </div>

                                                        <!-- Batch Code (Read-only) -->
                                                        <div class="form-group">
                                                            {!! Form::label('batch_code', 'Batch Code') !!} <span class="text-danger">*</span>
                                                            {!! Form::text('batch_code', null, [
                                                                'class' => 'form-control',
                                                                'id' => 'batch_code'
                                                            ]) !!}
                                                        </div>

                                                        <!-- Expiry Date -->
                                                        <div class="form-group">
                                                            {!! Form::label('expiry_date', 'Expiry Date') !!} <span class="text-danger">*</span>
                                                            {!! Form::date('expiry_date', null, [
                                                                'class' => 'form-control',
                                                                'required',
                                                                'id' => 'expiry_date'
                                                            ]) !!}
                                                        </div>

                                                        <!-- Status -->
                                                        <div class="form-group">
                                                            {!! Form::label('status', 'Status') !!}
                                                            {!! Form::select('status', [
                                                                1 => 'Active',
                                                                2 => 'Expired',
                                                                3 => 'Inactive'
                                                            ], null, [
                                                                'class' => 'form-control',
                                                                'id' => 'status'
                                                            ]) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right Column - Stock Information (Read-only) -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-success text-white">
                                                        <h5 class="card-title mb-0">
                                                            <i class="fa fa-line-chart"></i> Stock Information
                                                        </h5>
                                                    </div>
                                                    <div class="card-body">
                                                    

                                                        <!-- Current Quantity (Read-only) -->
                                                        <div class="form-group">
                                                            {!! Form::label('current_quantity_display', 'Current Quantity') !!}
                                                            <input type="text" class="form-control" value="{{ number_format($productBatch->quantity) }} units" readonly disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Submit Buttons -->
                                        <div class="row mt-3">
                                            <div class="col-md-12 text-center">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fa fa-save"></i> Update Batch
                                                </button>
                                
                                                <a href="{{ route('productBatches.index') }}" class="btn btn-secondary btn-lg">
                                                    Back
                                                </a>
                                            </div>
                                        </div>

                                        {!! Form::close() !!}
                                    </div>
                                </div>
                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .card-header {
        font-weight: bold;
    }
    .progress {
        height: 25px;
    }
    .progress-bar {
        line-height: 25px;
        font-size: 12px;
    }
    .form-group label {
        font-weight: 600;
    }
    .alert {
        border-radius: 4px;
    }
</style>

<script>
$(function () {
    HideLoad();

    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select Product',
        width: '100%'
    });

    // Form validation
    $('#productBatchEditForm').on('submit', function(e) {
        var expiryDate = $('#expiry_date').val();
        if (!expiryDate) {
            e.preventDefault();
            toastr.error('Please select an expiry date', 'Validation Error');
            return false;
        }
    });

    // Keyboard shortcut for cancel
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            window.location.href = '{{ route("productBatches.show", Crypt::encrypt($productBatch->id)) }}';
        }
    });
});
</script>
@endpush
