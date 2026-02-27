@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoicePayments.index') }}">{{ __('invoice_payments.invoice_payments') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoice_payments.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('invoice_payments.detail') }}</strong>
                                  <a href="{{ route('invoicePayments.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('invoice_payments.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
