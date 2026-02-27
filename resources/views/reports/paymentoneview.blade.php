@extends('layouts.app')

@section('css')
    @include('layouts.datatables_css')
@endsection

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('reports.index') }}">Report</a>
            </li>
            <li class="breadcrumb-item">
                <a>Payment Detail One View Report</a>
            </li>
            <li class="breadcrumb-item active">Run</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Payment Detail One View Report</strong>
                             </div>
                             <div class="card-body">
                                <form>
                                    <div class="row">
                                        <div class="col-sm">

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Payment Date: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->datefrom.' ~ '.$paymentdetail->dateto, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Driver Name: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->name, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Employee ID: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->employeeid, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Group: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->grouping, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">IC: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->ic, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Phone: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->phone, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Bank Details 1: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->bankdetails1, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Bank Details 2: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->driver->bankdetails2, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-sm">
                                            
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Commission: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->do_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Deduct Amount: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->deduct_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Amount after deduction: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->do_amount - $paymentdetail->deduct_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Claim: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->claim_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Compound: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->comp_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Advance: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->adv_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Loan Pay: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->loanpay_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Bonus: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->bonus_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Final Amount: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('paymentdate', $paymentdetail->final_amount, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <hr>
                                </form>
                                <table class="table table-striped table-bordered" id="dataTableBuilder" width="100%"><tfoot><tr></tr></tfoot></table>
                             </div>
                         </div>
                         <div id="docard">
                            <div class="html2pdf__page-break"></div>
                            <div class="card">
                                <div class="card-header">
                                    <strong>Drivers Commissions Report</strong>
                                </div>
                                <div class="card-body">
                                   <table class="table table-striped table-bordered" id="dataTableBuilder_do" width="100%"><tfoot><tr></tr></tfoot></table>
                                </div>
                            </div>
                         </div>

                         <div id="claimcard">
                            <div class="html2pdf__page-break"></div>
                            <div class="card">
                                <div class="card-header">
                                    <strong>Drivers Claim Report</strong>
                                </div>
                                <div class="card-body">
                                   <table class="table table-striped table-bordered" id="dataTableBuilder_claim" width="100%"><tfoot><tr></tr></tfoot></table>
                                </div>
                            </div>
                        </div>

                        <div id="compoundcard">
                           <div class="html2pdf__page-break"></div>
                           <div class="card">
                               <div class="card-header">
                                   <strong>Drivers Compound Report</strong>
                               </div>
                               <div class="card-body">
                                  <table class="table table-striped table-bordered" id="dataTableBuilder_compound" width="100%"><tfoot><tr></tr></tfoot></table>
                               </div>
                           </div>
                        </div>

                        <div id="advancecard">
                           <div class="html2pdf__page-break"></div>
                           <div class="card">
                               <div class="card-header">
                                   <strong>Drivers Advance Report</strong>
                               </div>
                               <div class="card-body">
                                  <table class="table table-striped table-bordered" id="dataTableBuilder_advance" width="100%"><tfoot><tr></tr></tfoot></table>
                               </div>
                           </div>
                        </div>

                        <div id="loancard">
                           <div class="html2pdf__page-break"></div>
                           <div class="card">
                               <div class="card-header">
                                   <strong>Drivers Loan Payment Report</strong>
                               </div>
                               <div class="card-body">
                                  <table class="table table-striped table-bordered" id="dataTableBuilder_loan" width="100%"><tfoot><tr></tr></tfoot></table>
                               </div>
                           </div>
                        </div>

                        <div id="bonuscard">
                           <div class="html2pdf__page-break"></div>
                           <div class="card">
                               <div class="card-header">
                                   <strong>Drivers Bonus Report</strong>
                               </div>
                               <div class="card-body">
                                  <table class="table table-striped table-bordered" id="dataTableBuilder_bonus" width="100%"><tfoot><tr></tr></tfoot></table>
                               </div>
                           </div>
                        </div>

                        <div id="pdocard">
                           <div class="html2pdf__page-break"></div>
                           <div class="card">
                               <div class="card-header">
                                   <strong>Previous Drivers Commissions Report</strong>
                               </div>
                               <div class="card-body">
                                  <table class="table table-striped table-bordered" id="dataTableBuilder_pdo" width="100%"><tfoot><tr></tr></tfoot></table>
                               </div>
                           </div>
                        </div>

                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    @include('layouts.datatables_js')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
            if(e.altKey && e.keyCode == 83){
                ShowLoad();
                window.open("{{config('app.url')}}/reports/getPaymentoneviewPDF/{{ Crypt::encrypt($paymentdetail->id) }}/download");
                HideLoad();
            }
            if(e.altKey && e.keyCode == 80){
                ShowLoad();
                window.open("{{config('app.url')}}/reports/getPaymentoneviewPDF/{{ Crypt::encrypt($paymentdetail->id) }}/view");
                HideLoad();
            }
        });
        $(document).ready(function () {
            loadReport_do();
            loadReport_claim();
            loadReport_compound();
            loadReport_advance();
            loadReport_loan();
            loadReport_bonus();
            loadReport_pdo();
            HideLoad();
            if(resize == 1){
                $('#dataTableBuilder_do').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_claim').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_compound').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_advance').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_loan').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_bonus').resizableColumns();
            }
            if(resize == 1){
                $('#dataTableBuilder_pdo').resizableColumns();
            }
        });
        $('.form-control.reportdate').datetimepicker({
            format: 'YYYY-MM-DD',
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
        function loadReport_do(){
            var report_name = 'Drivers Commissions Report';
            var dataObject_do = eval({!! json_encode($result_do->data) !!});
            if(dataObject_do[0].DATA == null){
                $('#docard').remove();
                return;
            }
            $.each(dataObject_do[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_do tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_do').dataTable({
                "data": dataObject_do[0].DATA,
                "columns": dataObject_do[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "customize" : function(doc){
                        var colCount = new Array();
                        $('#dataTableBuilder_do').find('tbody tr:first-child td').each(function(){
                            if($(this).attr('colspan')){
                                for(var i=1;i<=$(this).attr('colspan');$i++){
                                    colCount.push('*');
                                }
                            }else{ colCount.push('*'); }
                        });
                        doc.content[1].table.widths = colCount;
                    },
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_do tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                    
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total6 = api
                //         .column(6,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total7 = api
                //         .column(7,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total8 = api
                //         .column(8,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total9 = api
                //         .column(9,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total10 = api
                //         .column(10,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total11 = api
                //         .column(11,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total15 = api
                //         .column(15,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total16 = api
                //         .column(16,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total17 = api
                //         .column(17,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total18 = api
                //         .column(18,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total19 = api
                //         .column(19,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total20 = api
                //         .column(20,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_do .summary').remove();
                //     $(api.column(6).footer()).append('<p class="summary">'+parseFloat(total6).toFixed(2)+'</p>');
                //     $(api.column(7).footer()).append('<p class="summary">'+parseFloat(total7).toFixed(2)+'</p>');
                //     $(api.column(8).footer()).append('<p class="summary">'+parseFloat(total8).toFixed(2)+'</p>');
                //     $(api.column(9).footer()).append('<p class="summary">'+parseFloat(total9).toFixed(2)+'</p>');
                //     $(api.column(10).footer()).append('<p class="summary">'+parseFloat(total10).toFixed(2)+'</p>');
                //     $(api.column(11).footer()).append('<p class="summary">'+parseFloat(total11).toFixed(2)+'</p>');
                //     $(api.column(15).footer()).append('<p class="summary">'+parseFloat(total15).toFixed(2)+'</p>');
                //     $(api.column(16).footer()).append('<p class="summary">'+parseFloat(total16).toFixed(2)+'</p>');
                //     $(api.column(17).footer()).append('<p class="summary">'+parseFloat(total17).toFixed(2)+'</p>');
                //     $(api.column(18).footer()).append('<p class="summary">'+parseFloat(total18).toFixed(2)+'</p>');
                //     $(api.column(19).footer()).append('<p class="summary">'+parseFloat(total19).toFixed(2)+'</p>');
                //     $(api.column(20).footer()).append('<p class="summary">'+parseFloat(total20).toFixed(2)+'</p>');
                // }

            });
        }
        function loadReport_claim(){
            var report_name = 'Drivers Claim Report';
            var dataObject_claim = eval({!! json_encode($result_claim->data) !!});
            if(dataObject_claim[0].DATA == null){
                $('#claimcard').remove();
                return;
            }
            $.each(dataObject_claim[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_claim tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_claim').dataTable({
                "data": dataObject_claim[0].DATA,
                "columns": dataObject_claim[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_claim tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total9 = api
                //         .column(9,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_claim .summary').remove();
                //     $(api.column(9).footer()).append('<p class="summary">'+parseFloat(total9).toFixed(2)+'</p>');
                // }
            });
        }
        function loadReport_compound(){
            var report_name = 'Drivers Compound Report';
            var dataObject_compound = eval({!! json_encode($result_compound->data) !!});
            if(dataObject_compound[0].DATA == null){
                $('#compoundcard').remove();
                return;
            }
            $.each(dataObject_compound[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_compound tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_compound').dataTable({
                "data": dataObject_compound[0].DATA,
                "columns": dataObject_compound[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_compound tfoot th').each(function () {
                        var title = $(this).text();
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total7 = api
                //         .column(7,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_compound .summary').remove();
                //     $(api.column(7).footer()).append('<p class="summary">'+parseFloat(total7).toFixed(2)+'</p>');
                // }
            });
        }
        function loadReport_advance(){
            var report_name = 'Drivers Advance Report';
            var dataObject_advance = eval({!! json_encode($result_advance->data) !!});
            if(dataObject_advance[0].DATA == null){
                $('#advancecard').remove();
                return;
            }
            $.each(dataObject_advance[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_advance tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_advance').dataTable({
                "data": dataObject_advance[0].DATA,
                "columns": dataObject_advance[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_advance tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total7 = api
                //         .column(7,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_advance .summary').remove();
                //     $(api.column(7).footer()).append('<p class="summary">'+parseFloat(total7).toFixed(2)+'</p>');
                // }
            });
        }
        function loadReport_loan(){
            var report_name = 'Drivers Loan Payment Report';
            var dataObject_loan = eval({!! json_encode($result_loan->data) !!});
            if(dataObject_loan[0].DATA == null){
                $('#loancard').remove();
                return;
            }
            $.each(dataObject_loan[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_loan tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_loan').dataTable({
                "data": dataObject_loan[0].DATA,
                "columns": dataObject_loan[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_loan tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total2 = api
                //         .column(2,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_loan .summary').remove();
                //     $(api.column(2).footer()).append('<p class="summary">'+parseFloat(total2).toFixed(2)+'</p>');
                // }
            });
        }
        function loadReport_bonus(){
            var report_name = 'Drivers Bonus Report';
            var dataObject_bonus = eval({!! json_encode($result_bonus->data) !!});
            if(dataObject_bonus[0].DATA == null){
                $('#bonuscard').remove();
                return;
            }
            $.each(dataObject_bonus[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_bonus tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_bonus').dataTable({
                "data": dataObject_bonus[0].DATA,
                "columns": dataObject_bonus[0].COLUMNS,
                "dom": "<'row'B><'row w-100'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_bonus tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total3 = api
                //         .column(3,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total4 = api
                //         .column(4,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total7 = api
                //         .column(7,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_bonus .summary').remove();
                //     $(api.column(3).footer()).append('<p class="summary">'+parseFloat(total3).toFixed(2)+'</p>');
                //     $(api.column(4).footer()).append('<p class="summary">'+parseFloat(total4).toFixed(2)+'</p>');
                //     $(api.column(7).footer()).append('<p class="summary">'+parseFloat(total7).toFixed(2)+'</p>');
                // }
            });
        }
        function loadReport_pdo(){
            var report_name = 'Previous Drivers Commissions Report';
            var dataObject_pdo = eval({!! json_encode($result_pdo->data) !!});
            if(dataObject_pdo[0].DATA == ''){
                $('#pdocard').remove();
                return;
            }
            $.each(dataObject_pdo[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder_pdo tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder_pdo').dataTable({
                "data": dataObject_pdo[0].DATA,
                "columns": dataObject_pdo[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDivFull't>><'row'ip>",
                "stateSave": false,
                "order": [
                    [0, "desc"]
                ],
                "lengthMenu": [
                    [10, 25, 50, -1],
                    ["10 rows", "25 rows", "50 rows", "Show all"]
                ],
                "buttons": [
                    {
                    "extend": "excelHtml5",
                    "text": "<i class=\"fa fa-file-excel-o\"><\/i> Excel",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "pdfHtml5",
                    "orientation": "landscape",
                    "pageSize": "LEGAL",
                    "text": "<i class=\"fa fa-file-pdf-o\"><\/i> PDF",
                    "exportOptions": {
                        "columns": ":visible"
                    },
                    "className": "btn btn-default btn-sm no-corner",
                    "title": null,
                    "filename": report_name + moment().format('DMMYYYYHHMMSS')
                }, {
                    "extend": "colvis",
                    "className": "btn btn-default btn-sm no-corner",
                    "text": "<i class=\"fa fa-columns\"><\/i> Column"
                }, {
                    "extend": "pageLength",
                    "className": "btn btn-default btn-sm no-corner"
                }],
                initComplete: function () {
                    // var table = $("#dataTableBuilder");
                    // table.find('thead').find('tr').find('th').each(function () {
                    //     $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>')
                    // });
                    $('#dataTableBuilder_pdo tfoot th').each(function () {
                        $(this).prepend('<input type="text" placeholder="Search" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value,true,false).draw();
                                }
                            });
                        });
                },
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };
                //     total6 = api
                //         .column(6,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total7 = api
                //         .column(7,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total8 = api
                //         .column(8,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total9 = api
                //         .column(9,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total10 = api
                //         .column(10,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total11 = api
                //         .column(11,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total15 = api
                //         .column(15,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total16 = api
                //         .column(16,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total17 = api
                //         .column(17,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total18 = api
                //         .column(18,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total19 = api
                //         .column(19,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     total20 = api
                //         .column(20,{search: 'applied'})
                //         .data()
                //         .reduce(function (a, b) {
                //             return intVal(a) + intVal(b);
                //         }, 0);
                //     $('#dataTableBuilder_pdo .summary').remove();
                //     $(api.column(6).footer()).append('<p class="summary">'+parseFloat(total6).toFixed(2)+'</p>');
                //     $(api.column(7).footer()).append('<p class="summary">'+parseFloat(total7).toFixed(2)+'</p>');
                //     $(api.column(8).footer()).append('<p class="summary">'+parseFloat(total8).toFixed(2)+'</p>');
                //     $(api.column(9).footer()).append('<p class="summary">'+parseFloat(total9).toFixed(2)+'</p>');
                //     $(api.column(10).footer()).append('<p class="summary">'+parseFloat(total10).toFixed(2)+'</p>');
                //     $(api.column(11).footer()).append('<p class="summary">'+parseFloat(total11).toFixed(2)+'</p>');
                //     $(api.column(15).footer()).append('<p class="summary">'+parseFloat(total15).toFixed(2)+'</p>');
                //     $(api.column(16).footer()).append('<p class="summary">'+parseFloat(total16).toFixed(2)+'</p>');
                //     $(api.column(17).footer()).append('<p class="summary">'+parseFloat(total17).toFixed(2)+'</p>');
                //     $(api.column(18).footer()).append('<p class="summary">'+parseFloat(total18).toFixed(2)+'</p>');
                //     $(api.column(19).footer()).append('<p class="summary">'+parseFloat(total19).toFixed(2)+'</p>');
                //     $(api.column(20).footer()).append('<p class="summary">'+parseFloat(total20).toFixed(2)+'</p>');
                // }

            });
        }
    </script>
@endpush