@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
            <a href="{{ route('drivers.index') }}">Driver</a>
      </li>
      <li class="breadcrumb-item active">Assign</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
            @include('flash::message')
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Assign</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => ['drivers.addassign',Crypt::encrypt($id)]]) !!}<!-- Driver Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('driver_id', 'Driver:') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('driver_id', $driverItems, $id, ['class' => 'form-control', 'placeholder' => 'Pick a Driver...', 'disabled']) !!}
                                    </div>


                                    <!-- Customer Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('customer_id', 'Customer:') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('customer_id', $customerItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Customer...','autofocus']) !!}
                                    </div>

                                    <!-- Sequence Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('sequence', 'Sequence:') !!}<span class="asterisk"> *</span>
                                        {!! Form::number('sequence', null, ['class' => 'form-control', 'min' => 0]) !!}
                                    </div>

                                    <!-- Submit Field -->
                                    <div class="form-group col-sm-12">
                                        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                        <a href="{{ route('drivers.show',Crypt::encrypt($id)) }}" class="btn btn-secondary">Cancel</a>
                                    </div>

                                    @push('scripts')
                                        <script>
                                            $(document).keyup(function(e) {
                                                if (e.key === "Escape") {
                                                    $('form a.btn-secondary')[0].click();
                                                }
                                            });
                                            $(document).ready(function () {
                                                HideLoad();
                                            });
                                        </script>
                                    @endpush

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
