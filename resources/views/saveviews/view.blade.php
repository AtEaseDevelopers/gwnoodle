@extends('layouts.app')

@section('css')
    @include('layouts.datatables_css')
@endsection

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('saveviews.index') }}">Save View</a>
            </li>
            <li class="breadcrumb-item active">{{ $result->view }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ $result->view }} View</strong>
                                  <a href="{{ route('saveviews.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                <table class="table table-striped table-bordered" id="dataTableBuilder" width="100%"><tfoot><tr></tr></tfoot></table>
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
        });
        $(document).ready(function () {
            loadReport();
            HideLoad();
            if(resize == 1){
                $('#dataTableBuilder').resizableColumns();
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
        function loadReport(){
            var report_name = {!! '"'.$result->view.$result->date.'"' !!};
            var dataObject = eval({!! json_encode($result->data) !!});
            $.each(dataObject[0].COLUMNS, function( k, v ) {
                $('#dataTableBuilder tfoot tr').append('<th rowspan="1" colspan="1"></th>');
            });
            var columns = [];
            $('#dataTableBuilder').dataTable({
                "data": dataObject[0].DATA,
                "columns": dataObject[0].COLUMNS,
                "dom": "<'row'B><'row'<'dataTableBuilderDiv't>><'row'ip>",
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
                    "filename": report_name
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
                    "filename": report_name
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
                    $('#dataTableBuilder tfoot th').each(function () {
                        var title = $(this).text();
                        $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                    });
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
        
                            $('input', this.footer()).on('change', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value).draw();
                                }
                            });
                        });
                }
            });
        }
    </script>
@endpush

{{-- {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-bordered'], true) !!}

@push('scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    
    <script>
        $(document).ready(function () {

            $(".buttons-reset").click(function(e){
                $('#dataTableBuilder tfoot th input').val('');
                $('#dataTableBuilder tfoot th select').val(1);
            });
            var table = $('#dataTableBuilder').DataTable();
            table.on( 'draw', function () {
                HideLoad();
            });
            table.on( 'preDraw', function () {
                ShowLoad();
            });
        });
    </script>
@endpush --}}
