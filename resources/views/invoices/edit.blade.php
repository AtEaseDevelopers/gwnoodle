@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('invoices.index') !!}">{{ __('invoices.invoices') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('invoices.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('invoices.edit_invoice') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($invoice, ['route' => ['invoices.update', encrypt($invoice->id)], 'method' => 'patch']) !!}

                              @include('invoices.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection