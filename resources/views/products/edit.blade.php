@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('products.index') !!}">{{ __('products.products')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('products.edit')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('products.edit_products')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($product, ['route' => ['products.update', encrypt($product->id)], 'method' => 'patch']) !!}

                              @include('products.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection