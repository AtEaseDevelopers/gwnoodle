@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('invoicePayments.index') !!}">{{ __('invoice_payments.invoice_payments') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('invoice_payments.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('invoice_payments.create_invoice_payments') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'invoicePayments.store' , 'enctype' => 'multipart/form-data']) !!}

                                   @include('invoice_payments.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
