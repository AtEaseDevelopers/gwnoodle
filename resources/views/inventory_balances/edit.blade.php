@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('inventoryBalances.index') !!}">Inventory Balance</a>
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
                              <strong>Edit Inventory Balance</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($inventoryBalance, ['route' => ['inventoryBalances.update', $inventoryBalance->id], 'method' => 'patch']) !!}

                              @include('inventory_balances.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection