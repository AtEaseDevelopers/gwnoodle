<?php

namespace App\DataTables;

use App\Models\Code;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class CommissionGroupDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'commission_group.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Code $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Code $model)
    {
        return $model->newQuery()->where("code","LIKE","%commission%");
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
            ->addAction(['title' => trans('commission.action'), 'printable' => false])
            ->parameters([
                'dom'       => 'Bfrtip',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[0, 'desc']],
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
                        'targets' => 1,
                        'render' => 'function(data, type, row){
                                var product_type = {
                                    0: \'Ice\'
                                };
                                return product_type[data] || \'Unknown\';
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option></select>\';
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
            'code'=> new \Yajra\DataTables\Html\Column(['title' => trans('commission.commission'),
            'data' => 'code',
            'name' => 'code']),

            'description'=> new \Yajra\DataTables\Html\Column(['title' => trans('commission.product_type'),
            'data' => 'description',
            'name' => 'description']),
            
            'value'

            //'sequence',
            // 'STR_UDF1'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF1',
            // 'data' => 'STR_UDF1',
            // 'name' => 'STR_UDF1']),
            // 'STR_UDF2'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF2',
            // 'data' => 'STR_UDF2',
            // 'name' => 'STR_UDF2']),
            // 'STR_UDF3'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF3',
            // 'data' => 'STR_UDF3',
            // 'name' => 'STR_UDF3']),
            // 'INT_UDF1'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF1',
            // 'data' => 'INT_UDF1',
            // 'name' => 'INT_UDF1']),
            // 'INT_UDF2'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF2',
            // 'data' => 'INT_UDF2',
            // 'name' => 'INT_UDF2']),
            // 'INT_UDF3'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF3',
            // 'data' => 'INT_UDF3',
            // 'name' => 'INT_UDF3']),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'commission_group_datatable_' . time();
    }
}
