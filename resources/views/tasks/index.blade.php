@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('tasks.tasks') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('tasks.tasks') }}
                             <a class="pull-right text-secondary" href="{{ route('taskTransfers.index') }}"><i class="fa fa-history fa-lg"></i></a>
                             <a class="pull-right" href="{{ route('tasks.create') }}"><i class="fa fa-plus-square fa-lg pr-2"></i></a>
                         </div>
                         <div class="card-body">
                             @include('tasks.table')
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