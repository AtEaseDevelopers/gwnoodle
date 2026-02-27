@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('focs.index') }}">{{ __('focs.foc') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('focs.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('focs.detail') }}</strong>
                                  <a href="{{ route('focs.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('focs.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
