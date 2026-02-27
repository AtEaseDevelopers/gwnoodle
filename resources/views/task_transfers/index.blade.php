@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a class="breadcrumb_back" href="{{ route('tasks.index') }}">
                Task
            </a>
        </li>
        <li class="breadcrumb-item">Transfers</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Task Transfers
                             {{-- <a class="pull-right" href="{{ route('taskTransfers.create') }}"><i class="fa fa-plus-square fa-lg"></i></a> --}}
                         </div>
                         <div class="card-body">
                             @include('task_transfers.table')
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
            if (e.key === "Escape") {
                $('.breadcrumb_back')[0].click();
            }
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            } 
        });
    </script>
@endpush