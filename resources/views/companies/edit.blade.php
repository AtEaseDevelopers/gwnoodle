@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('companies.index') !!}">{{ __('companies.companies')}}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('companies.edit')}}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('companies.edit_company')}}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($company, ['route' => ['companies.update', Crypt::encrypt($company->id)], 'method' => 'patch']) !!}

                              @include('companies.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection