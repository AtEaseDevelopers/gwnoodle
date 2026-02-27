@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('supervisors.index') !!}">{{ __('operations.operation')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('operations.edit')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('operations.edit_operation')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($supervisor, ['route' => ['supervisors.update', encrypt($supervisor->id)], 'method' => 'patch']) !!}

                              @include('supervisors.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection
