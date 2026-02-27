@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('lorries.index') !!}">{{ __('lorries.lorry') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('lorries.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('lorries.edit_lorry') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($lorry, ['route' => ['lorries.update', Crypt::encrypt($lorry->id)], 'method' => 'patch']) !!}

                              @include('lorries.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection