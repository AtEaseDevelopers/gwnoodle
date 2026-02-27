<?php

namespace App\DataTables;

use App\Models\InventoryTransfer;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class InventoryTransferDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'inventory_transfers.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\InventoryTransfer $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InventoryTransfer $model)
    {
        return $model->newQuery()
        ->with('fromdriver:id,name')
        ->with('fromlorry:id,lorryno')
        ->with('todriver:id,name')
        ->with('tolorry:id,lorryno')
        ->with('product:id,name')
        ->select('inventory_transfers.*');
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
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
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
                        'targets' => 7,
                        'render' => 'function(data, type){
                                                            if(data == 1){
                                                                return "Pending Accept";
                                                            }
                                                            if(data == 2){
                                                                return "Accepted";
                                                            }
                                                            if(data == 3){
                                                                return "Rejected";
                                                            }
                                                        }'
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Pending Accept</option><option value="2">Accepted</option><option value="3">Rejected</option></select>\';
                            }else if(columns[index].title == \'Date\'){
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
            'date'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.date'),
            'data' => 'date',
            'name' => 'date']),

            'from_driver_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.from_driver'),
            'data' => 'fromdriver.name',
            'name' => 'fromdriver.name']),

            'from_lorry_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.from_lorry'),
            'data' => 'fromlorry.lorryno',
            'name' => 'fromlorry.lorryno']),

            'to_driver_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.to_driver'),
            'data' => 'todriver.name',
            'name' => 'todriver.name']),

            'to_lorry_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.to_lorry'),
            'data' => 'tolorry.lorryno',
            'name' => 'tolorry.lorryno']),

            'product_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.product'),
            'data' => 'product.name',
            'name' => 'product.name']),


            'quantity'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.quantity'),
            'data' => 'quantity',
            'name' => 'quantity']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.status'),
            'data' => 'status',
            'name' => 'status']),

            'remark'=> new \Yajra\DataTables\Html\Column(['title' => trans('inventory_transfers.remark'),
            'data' => 'remark',
            'name' => 'remark']),

        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'inventory_transfers_datatable_' . time();
    }
}
