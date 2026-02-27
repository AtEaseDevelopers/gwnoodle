@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{!! route('users.index') !!}">{{ __('user.user') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('user.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('user.detail') }}</strong>
                                  <a href="{!! route('users.index') !!}" class="btn btn-ghost-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('users.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
