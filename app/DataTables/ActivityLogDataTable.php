<?php

namespace App\DataTables;

use App\Models\ActivityLog;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Crypt;

class ActivityLogDataTable extends DataTable
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

        return $dataTable
            ->addColumn('action', function ($row) {
                return '<div class="btn-group">
                    <a href="' . route('activity-logs.show', Crypt::encrypt($row->id)) . '" class="btn btn-ghost-success">
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="#" onclick="viewChanges(' . $row->id . ')" class="btn btn-ghost-info" data-toggle="modal" data-target="#changesModal">
                        <i class="fa fa-code-fork"></i>
                    </a>
                </div>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i:s');
            })
            ->editColumn('action', function ($row) {
                $badgeClass = match($row->action) {
                    'create' => 'success',
                    'update' => 'info',
                    'delete' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->action) . '</span>';
            })
            
            ->editColumn('old_data', function ($row) {
                if ($row->old_data) {
                    $count = count((array)$row->old_data);
                    return '<span class="badge badge-warning">' . $count . ' fields</span>';
                }
                return '-';
            })
            ->editColumn('new_data', function ($row) {
                if ($row->new_data) {
                    $count = count((array)$row->new_data);
                    return '<span class="badge badge-success">' . $count . ' fields</span>';
                }
                return '-';
            })
            ->rawColumns(['action', 'action_badge', 'old_data', 'new_data']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ActivityLog $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ActivityLog $model)
    {
        return $model->newQuery()
            ->with('user')
            ->select('activity_logs.*')
            ->latest();
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
                'dom'       => '<"row"B><"row"<"col-sm-12"tr>><"row"<"col-sm-4"l><"col-sm-4 text-center"i><"col-sm-4"p>>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => true,
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 300], ['10 rows', '25 rows', '50 rows', '100 rows', '300 rows']],
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
                        'title' => 'Activity_Logs_' . date('YmdHis'),
                        'filename' => 'activity_logs_' . date('YmdHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'A4',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Activity_Logs_' . date('YmdHis'),
                        'filename' => 'activity_logs_' . date('YmdHis')
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> ' . trans('table_buttons.column')
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => trans('Show Rows')
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    
                    // Add search inputs to footer
                    this.api().columns().every(function (index) {
                        var column = this;
                        var title = $(column.header()).text();
                        
                        if (column.searchable()) {
                            var input = \'<input type="text" class="form-control form-control-sm" placeholder="Search \' + title + \'" />\';
                            
                            $(input).appendTo($(column.footer()).empty())
                                .on(\'keyup change clear\', function() {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                        ShowLoad();
                                    }
                                });
                        }
                    });
                    
                    // Add filter dropdowns for specific columns
                    var actionColumn = this.api().column(2); // Action column index
                    var moduleColumn = this.api().column(3); // Module column index
                    
                    var actionSelect = \'<select class="form-control form-control-sm"><option value="">All Actions</option><option value="create">Create</option><option value="update">Update</option><option value="delete">Delete</option></select>\';
                    $(actionSelect).appendTo($(actionColumn.footer()).empty())
                        .on(\'change\', function() {
                            actionColumn.search($(this).val()).draw();
                            ShowLoad();
                        });
                    
                    var moduleSelect = \'<select class="form-control form-control-sm"><option value="">All Modules</option>\';
                    // Get unique modules from the table
                    var modules = [];
                    this.api().rows().data().each(function(row) {
                        if (row.module && modules.indexOf(row.module) === -1) {
                            modules.push(row.module);
                            moduleSelect += \'<option value="\' + row.module + \'">\' + row.module.charAt(0).toUpperCase() + row.module.slice(1) + \'</option>\';
                        }
                    });
                    moduleSelect += \'</select>\';
                    
                    $(moduleSelect).appendTo($(moduleColumn.footer()).empty())
                        .on(\'change\', function() {
                            moduleColumn.search($(this).val()).draw();
                            ShowLoad();
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
            'created_at' => new \Yajra\DataTables\Html\Column([
                'title' => trans('Date Time'),
                'data' => 'created_at',
                'name' => 'created_at',
                'width' => '180px'
            ]),
        
            
            'action' => new \Yajra\DataTables\Html\Column([
                'title' => trans('Action'),
                'data' => 'action',
                'name' => 'action',
                'width' => '100px'
            ]),
            
            'module' => new \Yajra\DataTables\Html\Column([
                'title' => trans('Module'),
                'data' => 'module',
                'name' => 'module',
                'width' => '120px'
            ]),

            'old_data' => new \Yajra\DataTables\Html\Column([
                'title' => trans('Old Data'),
                'data' => 'old_data',
                'name' => 'old_data',
                'width' => '100px',
                'searchable' => false,
                'orderable' => false
            ]),
            
            'new_data' => new \Yajra\DataTables\Html\Column([
                'title' => trans('New Data'),
                'data' => 'new_data',
                'name' => 'new_data',
                'width' => '100px',
                'searchable' => false,
                'orderable' => false
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
        return 'activity_logs_' . time();
    }
}