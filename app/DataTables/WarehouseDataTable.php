<?php

namespace App\DataTables;

use App\Models\Warehouse;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class WarehouseDataTable extends DataTable
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
            ->addColumn('action', 'warehouses.datatables_actions')
            ->editColumn('status', function($warehouse) {
                $badgeClass = $warehouse->status_badge_class;
                $status = ucfirst($warehouse->status);
                return '<span class="badge ' . $badgeClass . '">' . $status . '</span>';
            })
            ->editColumn('stock_out_enabled', function($warehouse) {
                // Add data attribute with raw value for filtering
                if ($warehouse->stock_out_enabled) {
                    return '<span class="badge badge-success" data-stock-out="1"><i class="fa fa-check-circle"></i> Enabled</span>';
                } else {
                    return '<span class="badge badge-secondary" data-stock-out="0"><i class="fa fa-ban"></i> Disabled</span>';
                }
            })
            ->addColumn('total_batches', function($warehouse) {
                $count = $warehouse->inventoryBalances->count();
                return '<span class="badge badge-info">' . $count . ' batches</span>';
            })
            ->addColumn('total_quantity', function($warehouse) {
                $total = $warehouse->inventoryBalances->sum('quantity');
                return '<strong>' . number_format($total) . '</strong>';
            })
            ->addColumn('products_count', function($warehouse) {
                $count = $warehouse->inventoryBalances->groupBy('product_id')->count();
                return '<span class="badge badge-secondary">' . $count . ' products</span>';
            })
            ->addColumn('inventory_summary', function($warehouse) {
                    $nonZeroBalances = $warehouse->inventoryBalances->filter(function($balance) {
                        return $balance->quantity > 0;
                    });

                    if ($nonZeroBalances->isEmpty()) {
                        return '<span class="text-muted">No inventory</span>';
                    }

                    $summary = '<div class="inventory-summary-wrap">';
                    $summary .= '<table class="table table-sm table-bordered mb-0">';
                    $summary .= '<thead><tr><th>Product</th><th>Batch</th><th>Qty</th></tr></thead><tbody>';

                    $nonZeroBalances->each(function($item) use (&$summary) {
                        $expiringClass = ($item->batch && $item->batch->isExpiringSoon()) ? 'expiring-soon' : '';
                        $summary .= '<tr>';
                        $summary .= '<td>' . ($item->product ? e($item->product->unit_code . ' (' . $item->product->name . ')') : 'Unknown') . '</td>';
                        $summary .= '<td class="' . $expiringClass . '">' . ($item->batch ? $item->batch->batch_code : 'Unknown') . '</td>';
                        $summary .= '<td class="text-center">' . $item->quantity . '</td>';
                        $summary .= '</tr>';
                    });

                    $summary .= '</tbody></table>';
                    $summary .= '</div>';

                    return $summary;
                })
            ->rawColumns(['action', 'status', 'stock_out_enabled', 'total_batches', 'total_quantity', 'products_count', 'inventory_summary']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Warehouse $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Warehouse $model)
    {
        return $model->newQuery()
            ->with(['inventoryBalances.product', 'inventoryBalances.batch'])
            ->select('warehouses.*');
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
            ->addAction(['width' => '120px', 'title' => 'Actions', 'printable' => false, 'exportable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => true,
                'order'     => [[0, 'asc']],
                'lengthMenu' => [[10, 50, 100, 300], ['10 rows', '50 rows', '100 rows', '300 rows']],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> Create',
                        'action' => 'function(e, dt, node, config) {
                            window.location.href = "' . route('warehouses.create') . '";
                        }'
                    ],
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner', 'text' => '<i class="fa fa-print"></i> Print'],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner', 'text' => '<i class="fa fa-undo"></i> Reset'],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner', 'text' => '<i class="fa fa-refresh"></i> Reload'],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> Excel',
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'filename' => 'warehouses_' . date('YmdHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> PDF',
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'filename' => 'warehouses_' . date('YmdHis')
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> Columns'
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => 'Show 10 rows'
                    ],
                ],
                'initComplete' => 'function(){
                    var table = this.api();
                    
                    // Add filter for Stock Out column
                    var stockOutColumn = table.column(\'stock_out_enabled:name\');
                    var stockOutSelect = \'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="1">Enabled</option><option value="0">Disabled</option></select>\';
                    $(stockOutSelect).appendTo($(stockOutColumn.footer()).empty()).on(\'change\', function(){
                        var val = $(this).val();
                        stockOutColumn.search(val).draw();
                    });
                    
                    // Add filter for Status column
                    var statusColumn = table.column(\'status:name\');
                    var statusSelect = \'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="active">Active</option><option value="inactive">Inactive</option></select>\';
                    $(statusSelect).appendTo($(statusColumn.footer()).empty()).on(\'change\', function(){
                        var val = $(this).val();
                        statusColumn.search(val).draw();
                    });
                    
                    // Add text search for other columns
                    table.columns().every(function (index) {
                        var column = this;
                        var columnName = column.header().textContent.trim();
                        
                        if(columnName !== "Status" && columnName !== "Stock Out" && columnName !== "Actions") {
                            var input = \'<input type="text" placeholder="Search" style="width: 100%;">\';
                            $(input).appendTo($(column.footer()).empty()).on(\'keyup change\', function(){
                                column.search($(this).val(), true, false).draw();
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
            [
                'title' => 'Warehouse Name',
                'data' => 'name',
                'name' => 'name',
                'searchable' => true,
                'orderable' => true
            ],
            [
                'title' => 'Location',
                'data' => 'location',
                'name' => 'location',
                'searchable' => true,
                'orderable' => true,
                'defaultContent' => '-'
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => true,
                'orderable' => true,
                'width' => '100px',
                'className' => 'text-center'
            ],
            [
                'title' => 'Stock Out',
                'data' => 'stock_out_enabled',
                'name' => 'stock_out_enabled',
                'searchable' => true,
                'orderable' => true,
                'width' => '110px',
                'className' => 'text-center'
            ],
            [
                'title' => 'Products',
                'data' => 'products_count',
                'name' => 'products_count',
                'searchable' => false,
                'orderable' => false,
                'width' => '100px',
                'className' => 'text-center'
            ],
            [
                'title' => 'Total Batches',
                'data' => 'total_batches',
                'name' => 'total_batches',
                'searchable' => false,
                'orderable' => false,
                'width' => '120px',
                'className' => 'text-center'
            ],
            [
                'title' => 'Total Quantity',
                'data' => 'total_quantity',
                'name' => 'total_quantity',
                'searchable' => false,
                'orderable' => true,
                'width' => '120px',
                'className' => 'text-center'
            ],
            [
                'title' => 'Inventory Summary',
                'data' => 'inventory_summary',
                'name' => 'inventory_summary',
                'searchable' => false,
                'orderable' => false,
                'width' => '300px'
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
        return 'warehouses_' . date('YmdHis');
    }
}