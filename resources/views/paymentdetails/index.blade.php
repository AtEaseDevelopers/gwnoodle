@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Payment Details</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Payment Details
                             {{-- <a class="pull-right" href="{{ route('paymentdetails.create') }}"><i class="fa fa-plus-square fa-lg"></i></a> --}}
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <a class="pull-right text-primary pr-2" id="massDownload" href="#" alt="Mass Download"><i class="fa fa-download fa-lg"></i></a>
                             <a class="pull-right text-secondary pr-2" id="masssave" href="#" alt="Save view"><i class="fa fa-save fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                            <div class="row pb-3 px-3 border-bottom">
                                <button type="button" class="btn btn-primary mr-1" data-toggle="modal" data-target="#massgenerateModel">Mass Generate</button>
                                <button type="button" class="btn btn-primary mr-1" data-toggle="modal" data-target="#generateModel">Generate</button>
                                </div>
                            <div class="row px-0">
                                <div class="col px-0">
                                    @include('paymentdetails.table')
                                </div>
                                <div class="pull-right mr-3">
                                       
                                </div>
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
    </script>
@endpush

<div class="modal fade" id="massgenerateModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="infoModelLabel">Mass Generate</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group col-sm-12">
                {!! Form::label('datefrom', 'Date From:') !!}<span class="asterisk"> *</span>
                {!! Form::text('datefrom', null, ['class' => 'form-control','id'=>'datefrom','autocomplete'=>'off']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('dateto', 'Date To:') !!}<span class="asterisk"> *</span>
                {!! Form::text('dateto', null, ['class' => 'form-control','id'=>'dateto','autocomplete'=>'off']) !!}
            </div>
            {{-- <div class="form-group col-sm-12 month">
                {!! Form::label('month', 'Month:') !!}<span class="asterisk"> *</span>
                {!! Form::select('month', ['1'=>'January','2'=>'Febraury','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'], null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Month...']) !!}
            </div> --}}
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Generate</button>
        </div>
      </div>
    </div>
</div>

<div class="modal fade" id="generateModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="infoModelLabel">Generate</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group col-sm-12 driver">
                {!! Form::label('driver_id', 'Driver:') !!} <span class="asterisk"> *</span>
                {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control selectpicker','data-live-search'=>'true','placeholder'=>'Pick a Driver...']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('datefrom', 'Date From:') !!}<span class="asterisk"> *</span>
                {!! Form::text('datefrom', null, ['class' => 'form-control','id'=>'datefrom','autocomplete'=>'off']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('dateto', 'Date To:') !!}<span class="asterisk"> *</span>
                {!! Form::text('dateto', null, ['class' => 'form-control','id'=>'dateto','autocomplete'=>'off']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('commission', 'Commission Amount:') !!}
                {!! Form::text('commission', '0.00', ['class' => 'form-control','id'=>'commission','disabled']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('claim', 'Claim Amount:') !!}
                {!! Form::text('claim', '0.00', ['class' => 'form-control','id'=>'claim','disabled']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('bonus', 'Bonus Amount:') !!}
                {!! Form::text('bonus', '0.00', ['class' => 'form-control','id'=>'bonus','disabled']) !!}
            </div>
            <hr>
            <div class="form-group col-sm-12">
                {!! Form::label('compound', 'Compound:') !!}
                <select id="compound" name="compound[]" class="form-control selectpicker" data-live-search="true" multiple disabled>
                </select>
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('advance', 'Advance:') !!}
                <select id="advance" name="advance[]" class="form-control selectpicker" data-live-search="true" multiple disabled>
                </select>
            </div>
            <hr>
            <div id="loandetails"></div>
            {{-- <div class="form-group col-sm-12">
                {!! Form::label('oustandingloan', 'Loan Outstanding Amount by Selected Month:') !!}<span class="asterisk"> *</span>
                {!! Form::text('oustandingloan', '0.00', ['class' => 'form-control','id'=>'oustandingloan','disabled']) !!}
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('loanpayment', 'Loan Payment:') !!}
                {!! Form::number('loanpayment', null, ['class' => 'form-control','id'=>'loanpayment','step'=>'0.01','min'=>'0']) !!}
            </div> --}}
            {{-- <div class="form-group col-sm-12 month">
                {!! Form::label('month', 'Month:') !!}<span class="asterisk"> *</span>
                {!! Form::select('month', ['1'=>'January','2'=>'Febraury','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'], null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Month...']) !!}
            </div> --}}
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="get btn btn-warning">Get</button>
        <button type="submit" class="btn btn-primary">Generate</button>
        </div>
      </div>
    </div>
</div>

@push('scripts')
   <script type="text/javascript">
        
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
                url: "{{config('app.url')}}/paymentdetails/masssave",
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

        $(document).on("click", "#massDownload", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to download 1 row!"
            }else{
                m = "Confirm to download " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Download',
                content: m,
                buttons: {
                    Yes: function() {
                        massDownload(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        
        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/paymentdetails/massdestroy",
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
        
        function massDownload(ids){
            ShowLoad();
            window.open("{{config('app.url')}}/report/massDownloadPaymentoneviewPDF/"+ids.toString());
            window.checkboxid = [];
            $('.buttons-reload').click();
        }

        $(document).ready(function () {
            // $("#massgenerateModel #datefrom").on('dp.change', function(e){ 
            //     selectmont();
            // })
            // $("#massgenerateModel #dateto").on('dp.change', function(e){ 
            //     selectmont();
            // })
            // $("#driver_id").bind("change", function() {
            //     getGenerateDetails();
            // });
            // $("#generateModel #datefrom").on('dp.change', function(e){ 
            //     getGenerateDetails();
            // })
            // $("#generateModel #dateto").on('dp.change', function(e){ 
            //     getGenerateDetails();
            // })
            $("#massgenerateModel button[type=submit]").on('click', function(e){ 
                massgenerate();
            })
            $("#generateModel button[type=submit]").on('click', function(e){ 
                generate();
            })
            $("#generateModel .get").on('click', function(e){
                getGenerateDetails();
            })
        });
        function getGenerateDetails(){
            if($('#generateModel #driver_id').val().length == 0){
                noti('i','Driver not select','Please select the driver and try again...');
                return;
            }
            if($('#generateModel #datefrom').val() == ''){
                noti('i','Date From not select','Please select the date from and try again...');
                return;
            }
            if($('#generateModel #dateto').val() == ''){
                noti('i','Date To not select','Please select the date to and try again...');
                return;
            }
            if(moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY') > moment($('#generateModel #dateto').val(), 'DD-MM-YYYY')){
                noti('e','Error','Date From cannot earlier than Date To');
                return;
            }
            if(moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY').format('M') != moment($('#generateModel #dateto').val(), 'DD-MM-YYYY').format('M')){
                noti('e','Error','Date Range must be within the same month');
                return;
            }
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/paymentdetails/getGenerateDetails",
                type:"POST",
                data:{
                driver_id: $('#generateModel #driver_id').val()
                ,datefrom: $('#generateModel #datefrom').val()
                ,dateto: $('#generateModel #dateto').val()
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    console.log(response);
                    var GenerateDetails = response;
                    window.PaymentGenerateDetails = GenerateDetails;
                    $('#generateModel #commission').val($.number(GenerateDetails.do_amount,2));                    
                    // $('#generateModel #oustandingloan').val(GenerateDetails.loanpay_amount);  
                    $('#generateModel #claim').val($.number(GenerateDetails.claim_data,2)); 
                    $('#generateModel #bonus').val($.number(GenerateDetails.bonus_amount,2)); 

                    var advance_data = GenerateDetails.advance_data
                    if(advance_data.length > 0){
                        var advance_option = '';
                        for (let i = 0; i < advance_data.length; ++i) {
                            advance_option = advance_option + '<option value='+advance_data[i].id+'>'+advance_data[i].date+' | '+advance_data[i].no+' | '+$.number(advance_data[i].amount,2)+'</option>';
                        }
                        $('#generateModel #advance').html(advance_option);
                        $("#generateModel #advance").removeAttr("disabled");
                        $("#generateModel #advance").selectpicker("refresh");
                    }else{
                        $('#generateModel #advance').html('');
                        $("#generateModel #advance").attr("disabled","true");
                        $("#generateModel #advance").selectpicker("refresh");
                    }
                    var compound_data = GenerateDetails.compound_data
                    if(compound_data.length > 0){
                        var compound_option = '';
                        for (let i = 0; i < compound_data.length; ++i) {
                            compound_option = compound_option + '<option value='+compound_data[i].id+'>'+compound_data[i].date+' | '+compound_data[i].no+' | '+$.number(compound_data[i].amount,2)+'</option>';
                        }
                        $('#generateModel #compound').html(compound_option);
                        $('#generateModel #compound').removeAttr("disabled");
                        $("#generateModel #compound").selectpicker("refresh");
                    }else{
                        $('#generateModel #compound').html('');
                        $('#generateModel #compound').attr("disabled","true");
                        $("#generateModel #compound").selectpicker("refresh");
                    }

                    var loan_data = GenerateDetails.loan_data
                    var loan_code = '';
                    $('#generateModel #loandetails').html('');
                    for (let i = 0; i < loan_data.length; ++i) {
                        var start = '<div class="form-group col-sm-12">';
                        var label = '<label for="'+loan_data[i].description+'">Loan Amount for '+loan_data[i].description+':</label>';
                        var textbox1 = '<div class="form-row"><div class="col"><div class="input-group mb-2"><div class="input-group-prepend"><div class="input-group-text">Min</div></div><input type="text" class="form-control" disabled="disabled" value="'+$.number(loan_data[i].totalmonthlyamount,2)+'"></div></div><div class="col"><div class="input-group mb-2"><div class="input-group-prepend"><div class="input-group-text">Max</div></div><input type="text" class="form-control" disabled="disabled" value="'+$.number(loan_data[i].totaloutstanding,2)+'"></div></div></div>';
                        var textbox2 = '<input type="number" class="form-control" placeholder="Pay amount" id="'+loan_data[i].id+'" name="'+loan_data[i].description+'" value="0.00" type="number" step="0.01" min="0">'
                        var end = '</div>';
                        var out = start + label + textbox1 + textbox2 + end;
                        $('#generateModel #loandetails').append(out);
                    }
                    // noti('i','Generate Successfully',response);
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message);
                    HideLoad();
                }
            });

        }
        // function selectmont(){
        //     if($('#massgenerateModel #datefrom').val()!='' && $('#massgenerateModel #dateto').val()!=''){
        //         var datefromm = moment($('#massgenerateModel #datefrom').val(), 'DD-MM-YYYY').format('M');
        //         var datefrommm = moment($('#massgenerateModel #datefrom').val(), 'DD-MM-YYYY').format('MMMM');
        //         var datetom = moment($('#massgenerateModel #dateto').val(), 'DD-MM-YYYY').format('M');
        //         var datetomm = moment($('#massgenerateModel #dateto').val(), 'DD-MM-YYYY').format('MMMM');
        //         if(datefromm == datetom){
        //             $('#massgenerateModel #month').val(datefromm);
        //             $('#massgenerateModel .month .bootstrap-select .filter-option-inner-inner').html(datefrommm);
        //             $('#massgenerateModel .month .bootstrap-select .dropdown-toggle').removeClass('bs-placeholder');
        //         }else{
        //             $('#massgenerateModel #month').val('');
        //             $('#massgenerateModel .month .bootstrap-select .filter-option-inner-inner').html('Pick a Month...');
        //             $('#massgenerateModel .month .bootstrap-select .dropdown-toggle').addClass('bs-placeholder');
        //         }
        //     }
        //     if($('#generateModel #datefrom').val()!='' && $('#generateModel #dateto').val()!=''){
        //         var datefromm = moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY').format('M');
        //         var datefrommm = moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY').format('MMMM');
        //         var datetom = moment($('#generateModel #dateto').val(), 'DD-MM-YYYY').format('M');
        //         var datetomm = moment($('#generateModel #dateto').val(), 'DD-MM-YYYY').format('MMMM');
        //         if(datefromm == datetom){
        //             $('#generateModel #month').val(datefromm);
        //             $('#generateModel .month .bootstrap-select .filter-option-inner-inner').html(datefrommm);
        //             $('#generateModel .month .bootstrap-select .dropdown-toggle').removeClass('bs-placeholder');
        //         }else{
        //             $('#generateModel #month').val('');
        //             $('#generateModel .month .bootstrap-select .filter-option-inner-inner').html('Pick a Month...');
        //             $('#generateModel .month .bootstrap-select .dropdown-toggle').addClass('bs-placeholder');
        //         }
        //     }
        // }
        function massgenerate(){
            if($('#massgenerateModel #datefrom').val() == ''){
                noti('i','Date From not select','Please select the date from and try again...');
                return;
            }
            if($('#massgenerateModel #dateto').val() == ''){
                noti('i','Date To not select','Please select the date to and try again...');
                return;
            }
            if(moment($('#massgenerateModel #datefrom').val(), 'DD-MM-YYYY') > moment($('#massgenerateModel #dateto').val(), 'DD-MM-YYYY')){
                noti('e','Error','Date From cannot earlier than Date To');
                return;
            }
            if(moment($('#massgenerateModel #datefrom').val(), 'DD-MM-YYYY').format('M') != moment($('#massgenerateModel #dateto').val(), 'DD-MM-YYYY').format('M')){
                noti('e','Error','Date Range must be within the same month');
                return;
            }
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/paymentdetails/massgenerate",
                type:"POST",
                data:{
                datefrom: $('#massgenerateModel #datefrom').val()
                ,dateto: $('#massgenerateModel #dateto').val()
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    $('#dataTableBuilder').DataTable().draw();
                    $('#massgenerateModel').modal('hide');
                    resetmassgenerateModel();
                    toastr.info(response, 'Information', {showEasing: "swing", hideEasing: "linear", showMethod: "fadeIn", hideMethod: "fadeOut", positionClass: "toast-bottom-right", timeOut: 0, allowHtml: true });
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message);
                    HideLoad();
                }
            });

        }
        function generate(){
            if($('#generateModel #driver_id').val().length == 0){
                noti('i','Driver not select','Please select the driver and try again...');
                return;
            }
            if($('#generateModel #datefrom').val() == ''){
                noti('i','Date From not select','Please select the date from and try again...');
                return;
            }
            if($('#generateModel #dateto').val() == ''){
                noti('i','Date To not select','Please select the date to and try again...');
                return;
            }
            if(moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY') > moment($('#generateModel #dateto').val(), 'DD-MM-YYYY')){
                noti('e','Error','Date From cannot earlier than Date To');
                return;
            }
            if(moment($('#generateModel #datefrom').val(), 'DD-MM-YYYY').format('M') != moment($('#generateModel #dateto').val(), 'DD-MM-YYYY').format('M')){
                noti('e','Error','Date Range must be within the same month');
                return;
            }
            if (window.PaymentGenerateDetails == null){
                noti('e','Error','Please select click the Get button');
                return;
            }
            ShowLoad();
            var data = {};
            data.driver_id = window.PaymentGenerateDetails.driver_id;
            data.datefrom = window.PaymentGenerateDetails.datefrom;
            data.dateto = window.PaymentGenerateDetails.dateto;
            do_amount = window.PaymentGenerateDetails.do_amount;
            claim_amount = window.PaymentGenerateDetails.claim_data;
            bonus_amount = window.PaymentGenerateDetails.bonus_amount;
            data.compound_data = $('#generateModel #compound').val();
            data.advance_data = $('#generateModel #advance').val();
            var total_loan_amount = 0;
            var loan_data = window.PaymentGenerateDetails.loan_data;
            var loan_lists = [];
            for (let i = 0; i < loan_data.length; ++i) {
                var loan_text_id = '#generateModel #loandetails #'+loan_data[i].id;
                var loan_amount = $(loan_text_id).val();
                if(loan_amount == ''){
                    HideLoad();
                    noti('e','Error','Please enter loan pay amount for Loan '+loan_data[i].description);
                    return;
                }
                for (let x = 0; x < window.PaymentGenerateDetails.loan_data.length; ++x) {
                    if(window.PaymentGenerateDetails.loan_data[x].id == loan_data[i].id){
                        if(window.PaymentGenerateDetails.loan_data[x].totaloutstanding<parseFloat(loan_amount)){
                            HideLoad();
                            noti('e','Error','Loan pay amount cannot more then '+ $.number(window.PaymentGenerateDetails.loan_data[x].totaloutstanding,2) +' for Loan '+loan_data[i].description);
                            return;
                        }
                    }
                }
                var loan_list = '{"id":'+loan_data[i].id+',"amount":'+parseFloat(loan_amount)+'}';
                loan_lists.push(loan_list);
                total_loan_amount = total_loan_amount + parseFloat(loan_amount);
            }
            data.loan_data = loan_lists;
            var compound_amount = 0;
            for (let i = 0; i < window.PaymentGenerateDetails.compound_data.length; ++i) {
                for (let x = 0; x < data.compound_data.length; ++x) {
                    if(data.compound_data[x] == window.PaymentGenerateDetails.compound_data[i].id){
                        compound_amount = compound_amount + window.PaymentGenerateDetails.compound_data[i].amount;
                    }
                }
            }
            var advance_amount = 0;
            for (let i = 0; i < window.PaymentGenerateDetails.advance_data.length; ++i) {
                for (let x = 0; x < data.advance_data.length; ++x) {
                    if(data.advance_data[x] == window.PaymentGenerateDetails.advance_data[i].id){
                        advance_amount = advance_amount + window.PaymentGenerateDetails.advance_data[i].amount;
                    }
                }
            }
            var generateconfirm = window.confirm('Are you sure to generate?\nCommission: '+$.number(do_amount,2)+'\nClaim: '+$.number(claim_amount,2)+'\nBonus: '+$.number(bonus_amount,2)+'\nCompound: -'+$.number(compound_amount,2)+'\nAdvance: -'+$.number(advance_amount,2)+'\nLoan: -'+$.number(total_loan_amount,2)+'\nFinal: '+$.number((do_amount + claim_amount + bonus_amount - compound_amount - advance_amount - total_loan_amount),2));
            if (!generateconfirm) { 
                HideLoad();
                return;
            }
            if((do_amount + claim_amount + bonus_amount - compound_amount - advance_amount - total_loan_amount) < 0){
                HideLoad();
                noti('e','Final amount was negative','Commission: '+$.number(do_amount,2)+'</br>Claim: '+$.number(claim_amount,2)+'\nBonus: '+$.number(bonus_amount,2)+'</br>Compound: -'+$.number(compound_amount,2)+'</br>Advance: -'+$.number(advance_amount,2)+'</br>Loan: -'+$.number(total_loan_amount,2)+'</br>Final: '+$.number((do_amount + claim_amount + bonus_amount - compound_amount - advance_amount - total_loan_amount),2));
                return;
            }
            $.ajax({
                url: "{{config('app.url')}}/paymentdetails/generate",
                type:"POST",
                data:{
                data: data
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    $('#dataTableBuilder').DataTable().draw();
                    $('#generateModel').modal('hide');
                    resetgenerateModel();
                    toastr.info(response, 'Information', {showEasing: "swing", hideEasing: "linear", showMethod: "fadeIn", hideMethod: "fadeOut", positionClass: "toast-bottom-right", timeOut: 0, allowHtml: true });
                    // noti('i','Generate Successfully',response);
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message);
                    HideLoad();
                }
            });

        }
        function resetgenerateModel(){
            $('#generateModel #driver_id').val('');
            $('#generateModel #driver_id').selectpicker("refresh");
            $('#generateModel #datefrom').val('');
            $('#generateModel #dateto').val('');
            $('#generateModel #commission').val('0.00');
            $('#generateModel #claim').val('0.00');
            $('#generateModel #compound').html('');
            $('#generateModel #compound').attr("disabled","true");
            $("#generateModel #compound").selectpicker("refresh");
            $('#generateModel #advance').html('');
            $("#generateModel #advance").attr("disabled","true");
            $("#generateModel #advance").selectpicker("refresh");
            $('#generateModel #loandetails').html('');
            window.PaymentGenerateDetails = null;
        }
        function resetmassgenerateModel(){
            $('#massgenerateModel #datefrom').val('');
            $('#massgenerateModel #dateto').val('');
        }
        $('#massgenerateModel #datefrom').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            }
        })
        $('#massgenerateModel #dateto').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            },
            sideBySide: true
        })
        $('#generateModel #datefrom').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            }
        })
        $('#generateModel #dateto').datetimepicker({
            format: 'DD-MM-YYYY',
            useCurrent: true,
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            },
            sideBySide: true
        })
    </script>
@endpush

