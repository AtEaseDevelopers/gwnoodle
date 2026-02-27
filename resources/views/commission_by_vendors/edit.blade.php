@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('commissionByVendors.index') !!}">Commission By Vendors</a>
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
                              <strong>Edit Commission By Vendors</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($commissionByVendors, ['route' => ['commissionByVendors.update', Crypt::encrypt($commissionByVendors->id)], 'method' => 'patch']) !!}

                              @include('commission_by_vendors.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection