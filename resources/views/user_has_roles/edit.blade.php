@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('userHasRoles.index') !!}">User Role</a>
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
                              <strong>Edit User Role</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($userHasRole, ['route' => ['userHasRoles.update', Crypt::encrypt($userHasRole->id)], 'method' => 'patch']) !!}

                              @include('user_has_roles.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection