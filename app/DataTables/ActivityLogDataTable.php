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
            ->addColumn('checkbox', function($row) {
                return $row->id;
            })
            ->editColumn('created_at', function($row) {
                return $row->created_at->format('Y-m-d H:i:s');
            })
            ->editColumn('user_name', function($row) {
                return $row->user_name ?? ($row->user->name ?? 'System');
            })
            ->editColumn('action', function($row) {
                return $row->action;
            })
            ->editColumn('module', function($row) {
                return ucwords(str_replace('_', ' ', $row->module));
            })
            ->editColumn('old_data', function($row) {
                if ($row->old_data) {
                    $count = is_array($row->old_data) ? count($row->old_data) : count((array)$row->old_data);
                    return $count . ' fields';
                }
                return '-';
            })
            ->editColumn('new_data', function($row) {
                if ($row->new_data) {
                    $count = is_array($row->new_data) ? count($row->new_data) : count((array)$row->new_data);
                    return $count . ' fields';
                }
                return '-';
            })
            ->addColumn('action_buttons', 'activity_logs.datatables_actions')
            ->rawColumns(['action_buttons']);
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
            ->where('user_name', '!=', 'System')
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
                'dom'       => '<"row"B><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'serverSide' => true,
                'order'     => [[1, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'buttons' => [
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> ' . trans('table_buttons.print'),
                        'exportOptions' => ['columns' => ':visible']
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
                        'exportOptions' => ['columns' => ':visible'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Activity_Logs_' . date('dmYHis'),
                        'filename' => 'activity_logs_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'A4',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Activity_Logs_' . date('dmYHis'),
                        'filename' => 'activity_logs_' . date('dmYHis')
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
                        'targets' => 0,
                        'orderable' => false,
                        'searchable' => false,
                        'width' => '30px',
                        'className' => 'text-center',
                        'render' => 'function(data, type, row, meta){
                            return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";
                        }'
                    ],
                    [
                        'targets' => 1,
                        'width' => '160px',
                    ],
                    [
                        'targets' => 2,
                        'width' => '150px',
                    ],
                    [
                        'targets' => 3,
                        'width' => '100px',
                        'className' => 'text-center',
                        'render' => 'function(data, type){
                            if (type === "export" || type === "filter") return data;
                            var badgeClass = "secondary";
                            if (data === "create") badgeClass = "success";
                            else if (data === "update") badgeClass = "info";
                            else if (data === "delete") badgeClass = "danger";
                            
                            return "<span class=\'badge badge-" + badgeClass + "\' style=\'padding: 5px 10px; min-width: 60px; display: inline-block; text-align: center;\'>" + 
                                   data.charAt(0).toUpperCase() + data.slice(1) + "</span>";
                        }'
                    ],
                    [
                        'targets' => 4,
                        'width' => '120px',
                        'className' => 'text-center',
                    ],
                    [
                        'targets' => 5,
                        'width' => '90px',
                        'className' => 'text-center',
                        'render' => 'function(data, type){
                            if (type === "export" || type === "filter") return data;
                            if (data && data !== "-") {
                                return "<span class=\'badge badge-warning\'>" + data + "</span>";
                            }
                            return "-";
                        }'
                    ],
                    [
                        'targets' => 6,
                        'width' => '90px',
                        'className' => 'text-center',
                        'render' => 'function(data, type){
                            if (type === "export" || type === "filter") return data;
                            if (data && data !== "-") {
                                return "<span class=\'badge badge-success\'>" + data + "</span>";
                            }
                            return "-";
                        }'
                    ],
                    [
                        'targets' => 7,
                        'width' => '100px',
                        'orderable' => false,
                        'searchable' => false,
                        'className' => 'text-center'
                    ]
                ],
                
                'initComplete' => 'function(){
                    var api = this.api();
                    
                    // Add individual column filters
                    api.columns().every(function(index) {
                        var column = this;
                        var columnIndex = index;
                        var $header = $(column.header());
                        var title = $header.text().trim();
                        
                        // Skip checkbox and actions columns
                        if(title === "" || title === "Actions") {
                            return;
                        }
                        
                        // Create filter input based on column type
                        var $filterInput;
                        
                        if(title === "Action") {
                            $filterInput = $(\'<select class="form-control form-control-sm"><option value="">All</option><option value="create">Create</option><option value="update">Update</option><option value="delete">Delete</option></select>\');
                        } 
                        else if(title === "Module") {
                            // Get unique modules from the data
                            var modules = [];
                            api.rows().data().each(function(row) {
                                var module = row.module;
                                if(module && modules.indexOf(module) === -1) {
                                    modules.push(module);
                                }
                            });
                            
                            var selectHtml = \'<select class="form-control form-control-sm"><option value="">All</option>\';
                            modules.sort().forEach(function(module) {
                                selectHtml += \'<option value="\' + module + \'">\' + 
                                              module.charAt(0).toUpperCase() + module.slice(1) + \'</option>\';
                            });
                            selectHtml += \'</select>\';
                            $filterInput = $(selectHtml);
                        }
                        else {
                            $filterInput = $(\'<input type="text" class="form-control form-control-sm" placeholder="Search \' + title + \'">\');
                        }
                        
                        // Append to footer
                        $filterInput.appendTo($(column.footer()).empty());
                        
                        // Apply filter on change
                        $filterInput.on(\'keyup change\', function() {
                            var val = $(this).val();
                            column.search(val).draw();
                        });
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
            'checkbox' => new \Yajra\DataTables\Html\Column([
                'title' => '<input type="checkbox" id="selectallcheckbox">',
                'data' => 'id',
                'name' => 'id',
                'orderable' => false,
                'searchable' => false,
                'exportable' => false,
                'printable' => false
            ]),

            'created_at' => [
                'title' => 'Date Time',
                'data' => 'created_at',
                'name' => 'created_at',
                'searchable' => true,
                'orderable' => true
            ],
            
            'user_name' => [
                'title' => 'User',
                'data' => 'user_name',
                'name' => 'user_name',
                'searchable' => true,
                'orderable' => true,
                'defaultContent' => 'System'
            ],
            
            'action' => [
                'title' => 'Action',
                'data' => 'action',
                'name' => 'action',
                'searchable' => true,
                'orderable' => true
            ],
            
            'module' => [
                'title' => 'Module',
                'data' => 'module',
                'name' => 'module',
                'searchable' => true,
                'orderable' => true
            ],
            
            'old_data' => [
                'title' => 'Old Data',
                'data' => 'old_data',
                'name' => 'old_data',
                'searchable' => true, // Changed to true to allow searching by field count
                'orderable' => false
            ],
            
            'new_data' => [
                'title' => 'New Data',
                'data' => 'new_data',
                'name' => 'new_data',
                'searchable' => true, // Changed to true to allow searching by field count
                'orderable' => false
            ],
            
            'action_buttons' => new \Yajra\DataTables\Html\Column([
                'title' => 'Actions',
                'data' => 'action_buttons',
                'name' => 'action_buttons',
                'orderable' => false,
                'searchable' => false,
                'exportable' => false,
                'printable' => false,
                'width' => '100px'
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
        return 'activity_logs_' . date('Y-m-d_His');
    }
}