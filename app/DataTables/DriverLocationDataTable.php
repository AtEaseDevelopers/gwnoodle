<?php

namespace App\DataTables;

use App\Models\DriverLocation;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class DriverLocationDataTable extends DataTable
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
    
        return $dataTable->addColumn('check_id', function ($row) {
            return '<input type="checkbox" class="checkbox-select" 
                        value="' . $row->driver->id . '"
                        data-driver-name="' . htmlspecialchars($row->driver->name) . '"
                        data-latitude="' . $row->latitude . '"
                        data-longitude="' . $row->longitude . '"
                        data-kelindan-name="' . htmlspecialchars(optional($row->kelindan)->name) . '"
                        data-lorryno="' . htmlspecialchars($row->lorry->lorryno) . '">';
        })
        ->rawColumns(['check_id']) // Ensure HTML is rendered correctly for this column
        ->addColumn('action', 'driver_locations.datatables_actions');
    }


    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\DriverLocation $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(DriverLocation $model)
    {
        return $model->newQuery()
        ->with('driver:id,name')
        ->with('kelindan:id,name')
        ->with('lorry:id,lorryno')
        ->select('driver_location.*');
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
                'lengthMenu' => [[ 10, 25, 50,200,300 ],[ '10 rows', '25 rows', '50 rows', '200 rows', '300 rows' ]],
                'buttons' => [
                    // [
                    //     'extend' => 'create',
                    //     'className' => 'btn btn-default btn-sm no-corner',
                    //     'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
                    // ],
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
                    // [
                    //     'targets' => -1,
                    //     'visible' => true,
                    //     'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 0,
                    //     'visible' => true,
                    //     'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    // ],
                    // [
                    //     'targets' => 4,
                    //     'render' => 'function(data, type){return data == 1 ? "Start Trip" : "End Trip";}'
                    // ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Start Trip</option><option value="2">End Trip</option></select>\';
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
                'check_id' => new \Yajra\DataTables\Html\Column([
                    'title' => '<input type="checkbox" id="selectAll">',
                    'data' => 'check_id', // Reference the custom column
                    'name' => 'check_id',
                    'orderable' => false,
                    'searchable' => false,
                    'exportable' => false,
                    'printable' => false,
                    'width' => '10px',
                ]),
            trans('driver_locations.date'),
            'driver_id' => new \Yajra\DataTables\Html\Column([
                'title' =>  trans('driver_locations.driver'),
                'data' => 'driver.name',
                'name' => 'driver.name',
            ]),
            'kelindan_id' => new \Yajra\DataTables\Html\Column([
                'title' =>  trans('driver_locations.kelindan'),
                'data' => 'kelindan.name',
                'name' => 'kelindan.name',
            ]),
            'lorry_id' => new \Yajra\DataTables\Html\Column([
                'title' =>  trans('driver_locations.lorry'),
                'data' => 'lorry.lorryno',
                'name' => 'lorry.lorryno',
            ]),
             trans('driver_locations.latitude'),
             trans('driver_locations.longitude'),
        ];
    }



    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'driver_locations_datatable_' . time();
    }
}


?>