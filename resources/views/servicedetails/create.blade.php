@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('servicedetails.index') !!}">{{ __('lorry_service.lorry_service') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('lorry_service.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('lorry_service.create_lorry_service') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'servicedetails.store']) !!}

                                   @include('servicedetails.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
