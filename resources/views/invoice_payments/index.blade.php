@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('invoice_payments.invoice_payments') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('invoice_payments.invoice_payments') }}
                             <a class="pull-right" href="{{ route('invoicePayments.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             @can('paymentapprove')
                             <!--<a class="pull-right text-success pr-2" id="massactive" href="#" alt="Mass active"><i class="fa fa-check fa-lg"></i></a>-->
                             @endcan
                         </div>
                         <div class="card-body">
                             @include('invoice_payments.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>

    <div id="check-modal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('invoice_payments.payment_approval') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">               
                    <form id="approve_form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <iframe src="" style="width:100%;height:400px;" frameborder="0" border="0" scrolling="yes"></iframe>
                        </div>
                        <div class="form-group">
                            <label for="amount" class="col-form-label">{{ __('invoice_payments.amount') }}:</label>
                            <input type="text" class="form-control" name="amount" placeholder="Amount" value="" id="amount" disabled>
                        </div>
                        <div class="form-group">
                            <label for="status" class="col-form-label">{{ __('invoice_payments.status') }}:</label>
                            {{ Form::select('status', array(1 => 'Completed',0 => 'New'), null, ['class' => 'form-control']) }}
                        </div>
                        <div class="form-group">
                            <label for="remark" class="col-form-label">{{ __('invoice_payments.remark') }}:</label>
                            <input type="text" class="form-control" name="remark" placeholder="Remark" value="">
                        </div>
                        <button type="button" class="btn btn-secondary rounded-0 mt-2" data-dismiss="modal">{{ __('invoice_payments.cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-primary rounded-0 mt-2">{{ __('invoice_payments.update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            } 
        });
        
        $(document).on("click", "#massactive", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to update 1 row"
            }else{
                m = "Confirm to update " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Update',
                content: m,
                buttons: {
                    Completed: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    New: function() {
                        massupdatestatus(window.checkboxid,0);
                    },
                    somethingElse: {
                        text: 'Cancel',
                        btnClass: 'btn-gray',
                        keys: ['enter', 'shift']
                    }
                }
            });
            
        });
        function massupdatestatus(ids,status){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/invoicePayments/massupdatestatus",
                type:"POST",
                data:{
                ids: ids,
                status: status
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Update Successfully',response+' row(s) had been updated.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
    </script>
@endpush