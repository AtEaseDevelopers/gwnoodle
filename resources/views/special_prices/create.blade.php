@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('specialPrices.index') !!}">{{ __('special_prices.special_price')}}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('special_prices.create')}}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('special_prices.create_special_price')}}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'specialPrices.store']) !!}

                                   @include('special_prices.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
