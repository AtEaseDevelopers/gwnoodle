@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{!! route('Managerusers.index') !!}">Inventory Manager</a>
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
                                  <a href="{!! route('Managerusers.index') !!}" class="btn btn-ghost-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('manager.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
