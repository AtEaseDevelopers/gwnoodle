@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('kelindans.index') }}">{{ __('kelindans.kelindans') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('kelindans.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('kelindans.detail') }}</strong>
                                  <a href="{{ route('kelindans.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('kelindans.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
