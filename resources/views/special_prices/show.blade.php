@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('specialPrices.index') }}">{{ __('special_prices.special_price')}}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('special_prices.detail')}}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('special_prices.detail')}}</strong>
                                  <a href="{{ route('specialPrices.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('special_prices.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
