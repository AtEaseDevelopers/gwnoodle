<?php

namespace App\DataTables;

use App\Models\Product;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class ProductDataTable extends DataTable
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
            ->addColumn('action', 'products.datatables_actions')
            // Add total quantity column
            ->addColumn('total_quantity', function($product) {
                return $product->total_quantity;
            })
            // Add active batches count
            ->addColumn('active_batches', function($product) {
                return $product->active_batches_count;
            })
            // Add total batches
            ->addColumn('total_batches', function($product) {
                return $product->total_batches_count;
            })

            // Add carton display
            ->addColumn('carton_display', function($product) {
                if ($product->carton_enabled && $product->units_per_carton && $product->units_per_carton > 0) {
                    $cartons = floor($product->total_quantity / $product->units_per_carton);
                    $loose = $product->total_quantity % $product->units_per_carton;
                    
                    $display = [];
                    if ($cartons > 0) {
                        $display[] = $cartons . 'C';
                    }
                    if ($loose > 0) {
                        $display[] = $loose . 'U';
                    }
                    
                    return !empty($display) ? implode(' + ', $display) : '0U';
                }
                return '-';
            })
            // Raw columns for HTML rendering
            ->rawColumns(['action', 'status', 'carton_enabled']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Product $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Product $model)
    {
        return $model->newQuery()->with('batches');
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
            ->addAction(['title' => trans('products.action'), 'printable' => false, 'width' => '120px'])
            ->parameters([
                'dom'       => '<"row"B><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => true,
                'serverSide' => true,
                'order'     => [[2, 'asc']], // Order by name
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
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
                        'title' => 'Products_' . date('dmYHis'),
                        'filename' => 'products_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'A4',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Products_' . date('dmYHis'),
                        'filename' => 'products_' . date('dmYHis')
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
                        'targets' => 0, // checkbox column
                        'orderable' => false,
                        'searchable' => false,
                        'width' => '30px',
                        'className' => 'text-center',
                        'render' => 'function(data, type, row, meta){
                            return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";
                        }'
                    ],
                    [
                        'targets' => 1, // unit_code
                        'width' => '80px',
                    ],
                    [
                        'targets' => 2, // name
                        'width' => '200px',
                    ],
                    [
                        'targets' => 3, // price
                        'className' => 'text-right',
                        'width' => '100px',
                        'render' => 'function(data, type){
                            return type === "export" ? data : "RM " + parseFloat(data).toFixed(2);
                        }'
                    ],
                    [
                        'targets' => 4, // status
                        'width' => '80px',
                        'className' => 'text-center',
                        'render' => 'function(data, type){
                            if (type === "export") {
                                return data == 1 ? "Active" : "Inactive";
                            }
                            if (data == 1) {
                                return "<span class=\'badge badge-success\' style=\'padding: 5px 10px; min-width: 60px; display: inline-block; text-align: center;\'>Active</span>";
                            } else {
                                return "<span class=\'badge badge-danger\' style=\'padding: 5px 10px; min-width: 60px; display: inline-block; text-align: center;\'>Inactive</span>";
                            }
                        }'
                    ],
                    [
                        'targets' => 5, // total_quantity
                        'className' => 'text-right',
                        'width' => '100px',
                        'render' => 'function(data, type, row){
                            if (type === "export") return data;
                            if (row[11] == 1) { // if carton_enabled is true
                                return data + " (" + row[12] + ")";
                            }
                            return data;
                        }'
                    ],
                    [
                        'targets' => 6, // active_batches
                        'className' => 'text-center',
                        'width' => '80px',
                        'render' => 'function(data, type, row){
                            if (type === "export") {
                                return data || 0;
                            }
                            return "<span class=\'badge badge-info\'>" + (data || 0) + "</span>";
                        }'
                    ],
                    [
                        'targets' => 7, // total_batches
                        'visible' => false, // Hide from view but keep in export
                    ],
                    
                    [
                        'targets' => 8, // action column
                        'width' => '120px',
                        'orderable' => false,
                        'searchable' => false,
                        'className' => 'text-center'
                    ]
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        var title = $(this.header()).text();
                        
                        if(columns[index].searchable && title !== "Actions" && title !== "") {
                            if(title == "Status"){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option></select>\';
                            } else if(title == "Carton Enabled"){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="1">Yes</option><option value="0">No</option></select>\';
                            } else if(title == "Total Qty" || title == "Active Batches" || title == "Stock Value") {
                                var input = \'<input type="text"  class="border-0" style="width: 100%;" placeholder="Min - Max" title="Enter min-max (e.g. 10-100)">\';
                            } else {
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            
                            $(input).appendTo($(column.footer()).empty())
                                .on(\'keyup change\', function(){
                                    var val = $(this).val();
                                    if (title == "Total Qty" || title == "Active Batches" || title == "Stock Value") {
                                        if (val.includes("-")) {
                                            var range = val.split("-");
                                            var min = range[0] ? parseInt(range[0]) : 0;
                                            var max = range[1] ? parseInt(range[1]) : 999999;
                                            column.draw();
                                        } else {
                                            column.search(val).draw();
                                        }
                                    } else {
                                        column.search(val).draw();
                                    }
                                });
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
            'checkbox' => new \Yajra\DataTables\Html\Column([
                'title' => '<input type="checkbox" id="selectallcheckbox">',
                'data' => 'id',
                'name' => 'id',
                'orderable' => false,
                'searchable' => false,
                'exportable' => false,
                'printable' => false
            ]),

            'unit_code' => [
                'title' => 'Unit Code',
                'data' => 'unit_code',
                'name' => 'unit_code'
            ],
            
            'name' => [
                'title' => 'Product Name',
                'data' => 'name',
                'name' => 'name'
            ],
            
            'price' => [
                'title' => 'Price (RM)',
                'data' => 'price',
                'name' => 'price'
            ],
            
            'status' => [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status'
            ],
            
            'total_quantity' => [
                'title' => 'Total Qty',
                'data' => 'total_quantity',
                'name' => 'total_quantity',
                'searchable' => true,
                'orderable' => true
            ],
            
            'active_batches' => [
                'title' => 'Active Batches',
                'data' => 'active_batches',
                'name' => 'active_batches',
                'searchable' => true,
                'orderable' => true
            ],
            
            'total_batches' => [
                'title' => 'Total Batches',
                'data' => 'total_batches',
                'name' => 'total_batches',
                'searchable' => false,
                'visible' => false
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'products_' . date('Y-m-d_His');
    }
}