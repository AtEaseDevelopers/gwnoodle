@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('kelindans.index') !!}">{{ __('kelindans.kelindans') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('kelindans.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('kelindans.create_kelindan') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'kelindans.store']) !!}

                                   @include('kelindans.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
