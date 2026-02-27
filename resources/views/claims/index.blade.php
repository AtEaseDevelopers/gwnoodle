@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Claims</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Claims
                             <a class="pull-right" href="{{ route('claims.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <a class="pull-right text-success pr-2" id="massactive" href="#" alt="Mass active"><i class="fa fa-check fa-lg"></i></a>
                             <a class="pull-right text-secondary pr-2" id="masssave" href="#" alt="Save view"><i class="fa fa-save fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                             @include('claims.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
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
                $('.card .card-header a')[0].click();
            } 
        });
        
        $(document).on("click", "#masssave", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to save 1 row"
            }else{
                m = "Confirm to save " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Save View',
                content: m,
                buttons: {
                    Yes: function() {
                        masssave(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
            
        });

        function masssave(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/claims/masssave",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    toastr.success('Please find Save View ID: '+response, 'Save Successfully', {showEasing: "swing", hideEasing: "linear", showMethod: "fadeIn", hideMethod: "fadeOut", positionClass: "toast-bottom-right", timeOut: 0, allowHtml: true });
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
        
        $(document).on("click", "#massdelete", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to delete 1 row!"
            }else{
                m = "Confirm to delete " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Delete',
                content: m,
                buttons: {
                    Yes: function() {
                        massdelete(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
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
                    Paid: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    Unpaid: function() {
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

        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/claims/massdestroy",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Delete Successfully',response+' row(s) had been deleted.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
        function massupdatestatus(ids,status){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/claims/massupdatestatus",
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
