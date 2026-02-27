@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('customers.index') !!}">{{ __('customers.customers')}}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('customers.create')}}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('customers.create_customer')}}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'customers.store']) !!}

                                   @include('customers.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
