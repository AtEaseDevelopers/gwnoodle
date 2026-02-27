@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('roleHasPermissions.index') !!}">Role Permission</a>
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
                              <strong>Edit Role Permission</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($roleHasPermission, ['route' => ['roleHasPermissions.update', Crypt::encrypt($roleHasPermission->id)], 'method' => 'patch']) !!}

                              @include('role_has_permissions.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection