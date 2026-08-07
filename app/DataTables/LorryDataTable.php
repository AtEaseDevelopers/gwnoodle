<?php

namespace App\DataTables;

use App\Models\Lorry;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class LorryDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'lorries.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Lorry $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Lorry $model)
    {
        return $model->newQuery()
            ->leftJoin('drivers', 'lorrys.driver_id', '=', 'drivers.id')
            ->select(
                'lorrys.id',
                'lorrys.lorryno',
                'lorrys.status',
                'lorrys.remark',
                DB::raw('COALESCE(drivers.name, "-") as driver_name')
            );
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        // Inventory managers get view-only access to Vans, so hide the create button.
        $isInventoryManager = auth()->check() && auth()->user()->isInventoryManager();

        $buttons = [];

        if (!$isInventoryManager) {
            $buttons[] = [
                'extend' => 'create',
                'className' => 'btn btn-default btn-sm no-corner',
                'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
            ];
        }

        $buttons = array_merge($buttons, [
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
                ]);

        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['title' => trans('invoices.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => $buttons,
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
                        'targets' => 3,
                        'render' => 'function(data, type){return data == 1 ? "Active" : "Inactive";}'
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Inactive</option></select>\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                var __searchVal = $(this).val();
                                var __isDateList = /^\d{4}-\d{2}-\d{2}(\|\d{4}-\d{2}-\d{2})*$/.test(__searchVal);
                                column.search(__searchVal, __isDateList, !__isDateList).draw();
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
            'checkbox'=> new \Yajra\DataTables\Html\Column([
                'title' => '<input type="checkbox" id="selectallcheckbox">',
                'data' => 'id',
                'name' => 'id',
                'orderable' => false,
                'searchable' => false
            ]),
            'lorryno'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('Vans'),
                'data' => 'lorryno',
                'name' => 'lorryno'
            ]),
            'driver_name'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('Driver'),
                'data' => 'driver_name',
                // driver_name is only a SELECT alias - search/sort must hit
                // the real joined column.
                'name' => 'drivers.name'
            ]),
            'status'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('lorries.status'),
                'data' => 'status',
                'name' => 'status'
            ]),
            'remark'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('lorries.remark'),
                'data' => 'remark',
                'name' => 'remark'
            ]),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'lorries_datatable_' . time();
    }
}