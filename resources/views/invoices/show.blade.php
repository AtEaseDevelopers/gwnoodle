@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('invoices.index') }}">{{ __('invoices.invoices') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('invoices.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                                  <a href="{{ route('invoices.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('invoices.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('invoices.invoice_detail') }}</strong>
                                <a class="pull-right" href="{{ route('invoices.detail', Crypt::encrypt($id)) }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             </div>
                             <div class="card-body">
                                <table class="table table-striped table-bordered dataTable" width="100%" role="grid" style="width: 100%;">
                                    <thead>
                                        <tr role="row">
                                            <th>{{ __('invoices.product') }}</th>
                                            <th>{{ __('invoices.quantity') }}</th>
                                            <th>{{ __('invoices.price') }}</th>
                                            <th>{{ __('invoices.total_price') }}</th>
                                            <th>{{ __('invoices.remark') }}</th>
                                            <th>{{ __('invoices.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($invoicedetails) == 0)
                                            <tr class="odd">
                                                <td valign="top" colspan="10" class="dataTables_empty">No matching records found</td>
                                            </tr>
                                        @endif
                                        @foreach($invoicedetails as $i=>$invoicedetail)
                                            @if( ($i+1) % 2 == 0 )

                                                <tr class="even">
                                                    <td>{{ $invoicedetail['product']['name'] }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ $invoicedetail['price'] }}</td>
                                                    <td>{{ $invoicedetail['totalprice'] }}</td>
                                                    <td>{{ $invoicedetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr class="odd">
                                                    <td>{{ $invoicedetail['product']['name'] }}</td>
                                                    <td>{{ $invoicedetail['quantity'] }}</td>
                                                    <td>{{ $invoicedetail['price'] }}</td>
                                                    <td>{{ $invoicedetail['totalprice'] }}</td>
                                                    <td>{{ $invoicedetail['remark'] }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['invoices.deletedetail', Crypt::encrypt($invoicedetail['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Invoice Detail?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>

                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[1].click();
            }
        });
    </script>
@endpush
