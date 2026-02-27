@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('assigns.index') !!}">{{ __('assign.assigns') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('assign.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('assign.create_assigns') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'assigns.store']) !!}

                                   @include('assigns.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
