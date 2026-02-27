@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('inventoryTransfers.index') !!}">Inventory Transfer</a>
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
                              <strong>Edit Inventory Transfer</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($inventoryTransfer, ['route' => ['inventoryTransfers.update', $inventoryTransfer->id], 'method' => 'patch']) !!}

                              @include('inventory_transfers.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection