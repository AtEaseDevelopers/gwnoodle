<?php

namespace App\DataTables;

use App\Models\Trip;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class TripDataTable extends DataTable
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
        
        // Add action column
        $dataTable->addColumn('action', function($row) {
            // Only show view report button for end trips (type = 0)
            if ($row->type == Trip::END_TRIP) {
                $reportUrl = route('trips.viewReport', [
                    'trip_uuid' => $row->uuid,
                    'driver_id' => $row->driver_id
                ]);
                
                return '<a href="'.$reportUrl.'" class="btn btn-info btn-sm view-report-btn" target="_blank">
                            <i class="fa fa-file-pdf-o"></i> View Report
                        </a>';
            }
            
            // For start trips, return empty string or disabled button
            return '<span class="text-muted no-report">-</span>';
        });
        
        return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Trip $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Trip $model)
    {
        return $model->newQuery()
        ->with('driver:id,name')
        ->with('kelindan:id,name')
        ->with('lorry:id,lorryno')
        ->select('trips.*');
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
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
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
                        'filename' => 'trips_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'trips_' . date('dmYHis')
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
                        'targets' => 4, // Index for 'type' column (adjust based on your column order)
                        'render' => 'function(data, type){return data == 1 ? "Start Trip" : "End Trip";}'
                    ],
                    [
                        'targets' => -1, // Last column (action)
                        'orderable' => false,
                        'searchable' => false,
                        'className' => 'dt-body-left', // Change to left alignment
                        'width' => '120px', // Set fixed width for action column
                    ],
                    [
                        'targets' => '_all',
                        'className' => 'dt-body-left', // Left align all columns by default
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="1">Start Trip</option><option value="0">End Trip</option></select>\';
                                $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                    var __searchVal = $(this).val();
                                    var __isDateList = /^\d{4}-\d{2}-\d{2}(\|\d{4}-\d{2}-\d{2})*$/.test(__searchVal);
                                    column.search(__searchVal, __isDateList, !__isDateList).draw();
                                    ShowLoad();
                                });
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                                $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                    var __searchVal = $(this).val();
                                    var __isDateList = /^\d{4}-\d{2}-\d{2}(\|\d{4}-\d{2}-\d{2})*$/.test(__searchVal);
                                    column.search(__searchVal, __isDateList, !__isDateList).draw();
                                    ShowLoad();
                                });
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                                $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                    var __searchVal = $(this).val();
                                    var __isDateList = /^\d{4}-\d{2}-\d{2}(\|\d{4}-\d{2}-\d{2})*$/.test(__searchVal);
                                    column.search(__searchVal, __isDateList, !__isDateList).draw();
                                    ShowLoad();
                                });
                            }
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
            'id'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('trips.trip_id'),
                'data' => 'id',
                'name' => 'trips.id',
                'width' => '80px', // Set width
                'className' => 'dt-body-left',
            ]),
            'date'=> new \Yajra\DataTables\Html\Column([
                'title' => 'Date',
                'data' => 'date',
                'name' => 'trips.date',
                'width' => '150px', // Set width
                'className' => 'dt-body-left',
            ]),
            'driver_id'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('trips.driver'),
                'data' => 'driver.name',
                'name' => 'driver.name',
                'width' => '200px', // Set width
                'className' => 'dt-body-left',
            ]),
            'lorry_id'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('trips.lorry'),
                'data' => 'lorry.lorryno',
                'name' => 'lorry.lorryno',
                'width' => '120px', // Set width
                'className' => 'dt-body-left',
            ]),
            'type'=> new \Yajra\DataTables\Html\Column([
                'title' => trans('trips.type'),
                'data' => 'type',
                'name' => 'trips.type',
                'width' => '100px', // Set width
                'className' => 'dt-body-left',
            ]),
            'action'=> new \Yajra\DataTables\Html\Column([
                'title' => 'Actions',
                'data' => 'action',
                'name' => 'action',
                'orderable' => false,
                'searchable' => false,
                'exportable' => false,
                'printable' => false,
                'width' => '100px', // Fixed smaller width for action column
                'className' => 'dt-body-left', // Left align
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
        return 'trips_datatable_' . time();
    }
}