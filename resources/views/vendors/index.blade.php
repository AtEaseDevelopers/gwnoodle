@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Vendors</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Vendors
                             <a class="pull-right" href="{{ route('vendors.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <a class="pull-right text-success pr-2" id="massactive" href="#" alt="Mass active"><i class="fa fa-check fa-lg"></i></a>
                             <a class="pull-right text-secondary pr-2" id="masssave" href="#" alt="Save view"><i class="fa fa-save fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                             @include('vendors.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>

    <div class="modal fade" id="vendorprintModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="infoModelLabel">Vendor One View Report</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group col-sm-12">
                    <label for="dono">Vendor Name:</label>
                    <input id="vendorprintmodalname" class="form-control" type="text" disabled value="value">
                </div>
                <div class="form-group col-sm-12">
                    <label for="dono">Vendor Code:</label>
                    <input id="vendorprintmodalcode" class="form-control" type="text" disabled value="value">
                </div>
                <div class="form-group col-sm-12">
                    <input id="idmodel" type="hidden" value="">
                    <input id="datefrommodel" type="hidden" value="">
                    <input id="datetomodel" type="hidden" value="">
                    <label for="datefrommodel">Date:</label>
                    <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success" onclick="viewvendorprint();">View</button>
              <button type="button" class="btn btn-primary" onclick="printvendorprint();">Print</button>
              <button type="button" class="btn btn-primary" onclick="downloadvendorprint();">Download</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
        
        $(document).on("click", "#vendorprint", function(e){
            $('#idmodel').val(this.getAttribute("vendorid"));
            $('#vendorprintmodalname').val(this.getAttribute("vendorname"));
            $('#vendorprintmodalcode').val(this.getAttribute("vendorcode"));
            $('#vendorprintModel').modal('show');
        });

        function viewvendorprint(){
            if($('#datefrommodel').val() == ''){
                noti('i','Date From cannot be empty','Please select the Date From');
                return;
            }
            if($('#datetomodel').val() == ''){
                noti('i','Date To cannot be empty','Please select the Date To');
                return;
            }
            if($('#datetomodel').val() < $('#datefrommodel').val()){
                noti('i','Date From cannot greater than Date To','Please select the Date again');
                return;
            }
            window.open("{{config('app.url')}}/reports/vendoroneview/"+$('#idmodel').val()+"/"+moment($('#datefrommodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"/"+moment($('#datetomodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"");
        }

        function printvendorprint(){
            if($('#datefrommodel').val() == ''){
                noti('i','Date From cannot be empty','Please select the Date From');
                return;
            }
            if($('#datetomodel').val() == ''){
                noti('i','Date To cannot be empty','Please select the Date To');
                return;
            }
            if($('#datetomodel').val() < $('#datefrommodel').val()){
                noti('i','Date From cannot greater than Date To','Please select the Date again');
                return;
            }
            window.open("{{config('app.url')}}/reports/getVendoroneviewPDF/"+$('#idmodel').val()+"/"+moment($('#datefrommodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"/"+moment($('#datetomodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"/view");
        }

        function downloadvendorprint(){
            if($('#datefrommodel').val() == ''){
                noti('i','Date From cannot be empty','Please select the Date From');
                return;
            }
            if($('#datetomodel').val() == ''){
                noti('i','Date To cannot be empty','Please select the Date To');
                return;
            }
            if($('#datetomodel').val() < $('#datefrommodel').val()){
                noti('i','Date From cannot greater than Date To','Please select the Date again');
                return;
            }
            window.open("{{config('app.url')}}/reports/getVendoroneviewPDF/"+$('#idmodel').val()+"/"+moment($('#datefrommodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"/"+moment($('#datetomodel').val(),'DD-MM-YYYY').format('YYYY-MM-DD')+"/download");
        }

        var start = moment();
        var end = moment();

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
            $('#datefrommodel').val(start.format('DD-MM-YYYY'));
            $('#datetomodel').val(end.format('DD-MM-YYYY'));
        }
        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);
        
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
                url: "{{config('app.url')}}/vendors/masssave",
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
                    Active: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    Unactive: function() {
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
                url: "{{config('app.url')}}/vendors/massdestroy",
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
                url: "{{config('app.url')}}/vendors/massupdatestatus",
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

