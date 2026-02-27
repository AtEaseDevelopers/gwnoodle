@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('tasks.index') }}">{{ __('tasks.tasks') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('tasks.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('tasks.detail') }}</strong>
                                  <a href="{{ route('tasks.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('tasks.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
