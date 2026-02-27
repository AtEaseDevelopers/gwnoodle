@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('agents.index') !!}">{{ __('agents.agents')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('agents.edit')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('agents.edit_agent')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($agent, ['route' => ['agents.update', encrypt($agent->id)], 'method' => 'patch']) !!}

                              @include('agents.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection