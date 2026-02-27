@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('servicedetails.index') !!}">{{ __('lorry_service.lorry_service') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('lorry_service.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('lorry_service.edit_lorry_service') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($servicedetails, ['route' => ['servicedetails.update', Crypt::encrypt($servicedetails->id)], 'method' => 'patch']) !!}

                              @include('servicedetails.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection