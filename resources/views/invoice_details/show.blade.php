@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoiceDetails.index') }}">{{ __('invoice_details.invoice_details') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoice_details.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('invoice_details.detail') }}</strong>
                                  <a href="{{ route('invoiceDetails.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('invoice_details.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
