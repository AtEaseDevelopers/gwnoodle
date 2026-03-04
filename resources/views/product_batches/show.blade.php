@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('productBatches.index') }}">{{ __('Product Batch')}}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('Product Batch Detail')}}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('Product Batch Detail')}}</strong>
                                  <a href="{{ route('productBatches.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('product_batches.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
