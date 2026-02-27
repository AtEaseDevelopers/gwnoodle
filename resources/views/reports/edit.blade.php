@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('reports.index') !!}">{{ __('report.reports') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('report.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('report.edit_report') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($report, ['route' => ['reports.update', $report->id], 'method' => 'patch']) !!}

                              @include('reports.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection