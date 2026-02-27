@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('servicedetails.index') }}">{{ __('lorry_service.lorry_service') }} </a>
            </li>
            <li class="breadcrumb-item active">{{ __('lorry_service.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('lorry_service.detail') }}</strong>
                                  <a href="{{ route('servicedetails.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('servicedetails.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
