@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('deliveryOrders.index') !!}">Delivery Order</a>
          </li>
          <li class="breadcrumb-item active">Edit</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>Edit Delivery Order</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($deliveryOrder, ['route' => ['deliveryOrders.update', Crypt::encrypt($deliveryOrder->id)], 'method' => 'patch']) !!}

                              @include('delivery_orders.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection