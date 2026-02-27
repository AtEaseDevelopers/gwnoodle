@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('taskTransfers.index') !!}">{{ __('task_transfers.task_transfers') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('task_transfers.create') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('task_transfers.create_task_transfer') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'taskTransfers.store']) !!}

                                   @include('task_transfers.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection


@push('scripts')
<script>
    $(document).ready(function() {
        HideLoad();
    });
</script>
@endpush