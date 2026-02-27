@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('focs.index') !!}">{{ __('focs.foc') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('focs.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('focs.edit_focs') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($foc, ['route' => ['focs.update', encrypt($foc->id)], 'method' => 'patch']) !!}

                              @include('focs.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection