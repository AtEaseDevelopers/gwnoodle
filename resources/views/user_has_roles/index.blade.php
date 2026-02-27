@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">User Roles</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             User Roles
                             <a class="pull-right" href="{{ route('userHasRoles.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                             @include('user_has_roles.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            } 
        });
    </script>
@endpush

