@extends('layouts.app')

@section('css')
    @include('layouts.datatables_css')
@endsection

@push('styles')
    <style>
        .dataTables_wrapper{
            display: contents;
        } 
    </style>
@endpush

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('reports.index') }}">Report</a>
            </li>
            <li class="breadcrumb-item">
                <a>Vendor One View Report</a>
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
                                 <strong>Vendor Summary</strong>
                             </div>
                             <div class="card-body">
                                <form>
                                    <div class="row">
                                        <div class="col-sm">

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Billing Date: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('billingdate', $datefrom.' ~ '.$dateto, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Vendor Name: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('vendorname', $vendor->name, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Vendor Code: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('vendorcode', $vendor->code, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Phone: </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('phone', $vendor->phone, ['class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Grand Total Sales (RM): </label>
                                                <div class="col-sm-9">
                                                    {!! Form::text('grandtotalsales', 0.00, ['id' => 'grandtotalsales','class' => 'form-control','disabled'=>'true']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                             </div>
                         </div>
                         <div id="table">
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
                window.open("{{config('app.url')}}/reports/getVendoroneviewPDF/{{ Crypt::encrypt($id) }}/{{ $datefrom }}/{{ $dateto }}/download");
                HideLoad();
            }
            if(e.altKey && e.keyCode == 80){
                ShowLoad();
                window.open("{{config('app.url')}}/reports/getVendoroneviewPDF/{{ Crypt::encrypt($id) }}/{{ $datefrom }}/{{ $dateto }}/view");
                HideLoad();
            }
        });

        $(document).ready(function () {
            loadtable();
            HideLoad();
        });

        function loadtable(){
            var vendorbillings = eval({!! json_encode($vendorbillings) !!});
            var grandtotalsales = 0;
            for (let i = 0; i < vendorbillings.length; i++) {
                grandtotalsales = grandtotalsales + vendorbillings[i].totaldosales;
                $('#table').append(getTableHeader(vendorbillings[i].item_name,vendorbillings[i].source_Name,vendorbillings[i].destination_name,vendorbillings[i].totalshipweight,vendorbillings[i].totaldosales,i));
                genTable(eval(vendorbillings[i].table)[0].DATA,eval(vendorbillings[i].table)[0].COLUMNS,i);
                if(resize == 1){
                    $('#dataTableBuilder_'+i).resizableColumns();
                }
            }
            $('#grandtotalsales').val(grandtotalsales.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,"));
        }
        function getTableHeader(item_name,source_Name,destination_name,totalshipweight,totaldosales,count){
            var header = item_name+' from '+source_Name+' to '+destination_name;
            return '<div class="card"><div class="card-header"><strong>'+header+'</strong></div><div class="card-body"><form> <div class="row"> <div class="col-sm"> <div class="form-group row"> <label class="col-sm-3 col-form-label">Total Weight: </label> <div class="col-sm-9"><input class="form-control" disabled="true" name="totalshipweight" type="text" value="'+totalshipweight+'"> </div></div><div class="form-group row"> <label class="col-sm-3 col-form-label">Total Sales: </label> <div class="col-sm-9"><input class="form-control" disabled="true" name="totaldosales" type="text" value="'+totaldosales.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,")+'"> </div></div></div></div></form><hr><table class="table table-striped table-bordered" id="dataTableBuilder_'+count+'" width="100%"><tfoot><tr></tr></tfoot></table></div></div>';
        }
        function genTable(data,columns,count){
            $('#dataTableBuilder_'+count).dataTable({
                "data": data,
                "columns": columns,
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
                    "filename": 'table_' + (count + 1) + moment().format('DMMYYYYHHMMSS')
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
                    "filename": 'table_' + (count + 1) + moment().format('DMMYYYYHHMMSS')
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
                    $('#dataTableBuilder_'+count+' tfoot th').each(function () {
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
            });
        }
    </script>
@endpush