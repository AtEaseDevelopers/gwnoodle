@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('customer_group.index') !!}">{{ __('customer_group.customer_group') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('customer_group.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('customer_group.create_customer_group') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'customer_group.store']) !!}

                                   @include('customer_group.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
