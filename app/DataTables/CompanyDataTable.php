<?php

namespace App\DataTables;

use App\Models\Company;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class CompanyDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'companies.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Company $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Company $model)
    {
        return $model->newQuery()
        ->with('group')
        ->select('companies.*');
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
        ->addAction(['title' => trans('companies.action'), 'printable' => false])
        ->parameters([
            'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
            'stateSave' => true,
            'stateDuration' => 0,
            'processing' => false,
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
                        }else if(columns[index].title == \'Payment Term\'){
                            var input = \'<select class="border-0" style="width: 100%;"><option value="1">Cash</option><option value="2">Bankin</option><option value="3">Credit Note</option></select>\';
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
            'code',
            'name',
            'ssm',
            'address1',
            'address2',
            'address3',
            'address4',

            'group_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Group',
            'data' => 'group.description',
            'name' => 'group.description'])
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'companies_datatable_' . time();
    }
}
