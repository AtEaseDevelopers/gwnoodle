@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('productBatches.index') !!}">{{ __('Product Batch')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('Edit Product Batch')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>{{ __('Edit Product Batch')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($product, ['route' => ['productBatches.update', encrypt($product->id)], 'method' => 'patch']) !!}

                              @include('product_batches.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection