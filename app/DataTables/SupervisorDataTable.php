<?php

namespace App\DataTables;

use App\Models\Supervisor;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class SupervisorDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->addColumn('action', 'supervisors.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Supervisor $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Supervisor $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['title' => trans('operations.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
                    ],
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> ' . trans('table_buttons.print'),
                    ],
                    [
                        'extend' => 'reset',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reset'),
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
                    ],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> ' . trans('table_buttons.excel'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'invoice' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'invoice' . date('dmYHis')
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> ' . trans('table_buttons.column')
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => trans('table_buttons.show_10_rows')
                    ],
                ],
                'columnDefs' => [
                    [
                        'targets' => -1,
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                    'targets' => 5,
                    'render' => 'function(data, type){return data == 1 ? "Active" : "Unactive";}'],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option></select>\';
                            }else if(columns[index].title == \'1st Vaccine Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else if(columns[index].title == \'2nd Vaccine Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(),true,false).draw();
                                ShowLoad();
                            })
                        }
                    });
                }'
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'id',
            'name' => 'id',
            'orderable' => false,
            'searchable' => false]),

            'employeeid'=> new \Yajra\DataTables\Html\Column(['title' => trans('operations.employee_id'),
            'data' => 'employeeid',
            'name' => 'supervisors.employeeid']),

            'name'=> new \Yajra\DataTables\Html\Column(['title' => trans('operations.name'),
            'data' => 'name',
            'name' => 'supervisors.name']),
            trans('operations.ic'),
            trans('operations.phone'),
            // 'commissionrate'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission Rate',
            // 'data' => 'commissionrate',
            // 'name' => 'commissionrate']),

            /*'bankdetails1'=> new \Yajra\DataTables\Html\Column(['title' => 'Bank Details 1',
            'data' => 'bankdetails1',
            'name' => 'bankdetails1']),

            'bankdetails2'=> new \Yajra\DataTables\Html\Column(['title' => 'Bank Details 2',
            'data' => 'bankdetails2',
            'name' => 'bankdetails2']),

            'firstvaccine'=> new \Yajra\DataTables\Html\Column(['title' => '1st Vaccine Date',
            'data' => 'firstvaccine',
            'name' => 'firstvaccine']),

            'secondvaccine'=> new \Yajra\DataTables\Html\Column(['title' => '2nd Vaccine Date',
            'data' => 'secondvaccine',
            'name' => 'secondvaccine']),

            'temperature'=> new \Yajra\DataTables\Html\Column(['title' => 'Body Temperature',
            'data' => 'temperature',
            'name' => 'temperature']),*/

            'status'=> new \Yajra\DataTables\Html\Column(['title' => trans('operations.status'),
            'data' => 'status',
            'name' => 'supervisors.status']),

            'remark'=> new \Yajra\DataTables\Html\Column(['title' => trans('operations.remark'),
            'data' => 'remark',
            'name' => 'supervisors.remark'])
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'supervisors_datatable_' . time();
    }
}
