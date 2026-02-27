@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('agents.index') !!}">{{ __('agents.agents')}}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('agents.create')}}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('agents.create_agent')}}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'agents.store']) !!}

                                   @include('agents.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
