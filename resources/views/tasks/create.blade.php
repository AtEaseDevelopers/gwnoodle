@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('tasks.index') !!}">{{ __('tasks.tasks') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('tasks.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('tasks.create_task') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'tasks.store']) !!}

                                   @include('tasks.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
