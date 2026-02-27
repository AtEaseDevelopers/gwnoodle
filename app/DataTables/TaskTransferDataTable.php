<?php

namespace App\DataTables;

use App\Models\TaskTransfer;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class TaskTransferDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'task_transfers.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\TaskTransfer $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(TaskTransfer $model)
    {
        return $model->newQuery()
        ->with('fromdriver:id,name')
        ->with('todriver:id,name')
        ->with('task.customer')
        ->select('task_transfers.*');
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
            // ->addAction(['width' => '120px', 'printable' => false])
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
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="8">Completed</option><option value="0">New</option><option value="1">In-Progress</option><option value="9">Cancelled</option></select>\';
                            }else if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Cash</option><option value="2">Bankin</option></select>\';
                            }else if(columns[index].title == \'Transfer Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else if(columns[index].title == \'Task Date\'){
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

            'from_datedriver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Transfer Date',
            'data' => 'date',
            'name' => 'date']),

            'from_driver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'From Driver',
            'data' => 'fromdriver.name',
            'name' => 'fromdriver.name']),

            'to_driver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'To Driver',
            'data' => 'todriver.name',
            'name' => 'todriver.name']),

            'task_id.date'=> new \Yajra\DataTables\Html\Column(['title' => 'Task Date',
            'data' => 'task.date',
            'name' => 'task.date']),

            'task_id.customer.company'=> new \Yajra\DataTables\Html\Column(['title' => 'Task Customer',
            'data' => 'task.customer.company',
            'name' => 'task.customer.company']),

        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'task_transfers_datatable_' . time();
    }
}
