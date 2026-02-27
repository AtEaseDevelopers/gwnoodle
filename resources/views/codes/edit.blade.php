@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('codes.index') !!}">{{ __('codes.codes') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('codes.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('codes.edit_codes') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($code, ['route' => ['codes.update', Crypt::encrypt($code->id)], 'method' => 'patch']) !!}

                              @include('codes.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection