@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('commission_group.index') !!}">{{ __('commission.commission') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('commission.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong> {{ __('commission.edit_commission') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($code, ['route' => ['commission_group.update', Crypt::encrypt($code->id)], 'method' => 'patch']) !!}

                              @include('commission_group.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection