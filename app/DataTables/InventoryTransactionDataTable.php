<?php

namespace App\DataTables;

use App\Models\InventoryTransaction;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class InventoryTransactionDataTable extends DataTable
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
            ->addColumn('action', function($transaction) {
                return view('inventory_transactions.datatables_actions', ['transaction' => $transaction])->render();
            })
            ->editColumn('type', function($transaction) {
                switch($transaction->type) {
                    case 1:
                        return '<span class="badge badge-success">Stock In</span>';
                    case 2:
                        return '<span class="badge badge-danger">Stock Out</span>';
                    case 3:
                        return '<span class="badge badge-warning">Stock Return</span>';
                    case 4:
                        return '<span class="badge badge-secondary">Stock Transfer</span>';
                    default:
                        return '<span class="badge badge-secondary">Unknown</span>';
                }
            })
            ->editColumn('quantity', function($transaction) {
                $quantity = $transaction->quantity;
                $formatted = number_format(abs($quantity));
                
                if ($quantity > 0) {
                    return '<span class="text-success font-weight-bold">+' . $formatted . '</span>';
                } elseif ($quantity < 0) {
                    return '<span class="text-danger font-weight-bold">-' . $formatted . '</span>';
                } else {
                    return '<span class="text-muted">0</span>';
                }
            })
            ->editColumn('date', function($transaction) {
                return $transaction->date ? $transaction->date->format('d-m-Y H:i:s') : '-';
            })
            ->editColumn('batch.batch_code', function($transaction) {
                return $transaction->batch ? $transaction->batch->batch_code : '-';
            })
            // Add warehouse column
            ->addColumn('warehouse_name', function($transaction) {
                if ($transaction->warehouse) {
                    return '<span class="badge badge-info">' . e($transaction->warehouse->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->rawColumns(['action', 'type', 'quantity', 'warehouse_name']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\InventoryTransaction $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InventoryTransaction $model)
    {
        return $model->newQuery()
            ->with([
                'lorry:id,lorryno', 
                'product:id,name', 
                'batch:id,batch_code',
                'warehouse:id,name',
            ])
            ->select('inventory_transactions.*')
            ->orderBy('inventory_transactions.date','desc');
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
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[10, 50, 100, 300], ['10 rows', '50 rows', '100 rows', '300 rows']],
                'buttons' => [
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> ' . trans('table_buttons.print'),
                        'exportOptions' => [
                            'columns' => ':visible',
                            'modifier' => ['search' => 'applied', 'order' => 'applied']
                        ]
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
                        'exportOptions' => [
                            'columns' => ':visible',
                            'modifier' => ['search' => 'applied', 'order' => 'applied']
                        ],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Inventory Transactions',
                        'filename' => 'inventory_transactions_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => [
                            'columns' => ':visible',
                            'modifier' => ['search' => 'applied', 'order' => 'applied']
                        ],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Inventory Transactions',
                        'filename' => 'inventory_transactions_' . date('dmYHis')
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
                        'targets' => 1, // Type column index
                        'className' => 'text-center',
                    ],
                    [
                        'targets' => 5, // Batch code column index
                        'className' => 'text-left',
                    ],
                    [
                        'targets' => 6, // Quantity column index
                        'className' => 'text-center',
                    ],
                    [
                        'targets' => 7, // Warehouse column index
                        'className' => 'text-center',
                    ],
                    [
                        'targets' => -1, // Action column
                        'className' => 'text-center',
                        'orderable' => false,
                        'searchable' => false,
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable && columns[index].title != "Action"){
                            if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0 form-control-sm" style="width: 100%;"><option value=""></option><option value="1">Stock In</option><option value="2">Stock Out</option><option value="3">Stock Return</option><option value="4">Stock Transfer</option></select>\';
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" class="form-control-sm" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search">\';
                            }else{
                                var input = \'<input type="text" class="form-control-sm" placeholder="Search">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'keyup change\', function(){
                                column.search($(this).val(),true,false).draw();
                                ShowLoad();
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
            'date' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.date'),
                'data' => 'date',
                'name' => 'date',
                'width' => '150px',
                'className' => 'text-left'
            ]),

            'type' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.type'),
                'data' => 'type',
                'name' => 'inventory_transactions.type',
                'width' => '100px',
                'className' => 'text-center'
            ]),

            'lorry_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.lorry'),
                'data' => 'lorry.lorryno',
                'name' => 'lorry.lorryno',
                'defaultContent' => '-',
                'width' => '100px',
                'className' => 'text-left'
            ]),

            'product_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.product'),
                'data' => 'product.name',
                'name' => 'product.name',
                'defaultContent' => '-',
                'width' => '200px',
                'className' => 'text-left'
            ]),

            'batch_id' => new \Yajra\DataTables\Html\Column([
                'title' => 'Batch Code',
                'data' => 'batch.batch_code',
                'name' => 'batch.batch_code',
                'defaultContent' => '-',
                'width' => '150px',
                'className' => 'text-left'
            ]),

            'quantity' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.quantity'),
                'data' => 'quantity',
                'name' => 'quantity',
                'width' => '100px',
                'className' => 'text-center'
            ]),

            'warehouse_id' => new \Yajra\DataTables\Html\Column([
                'title' => 'Warehouse',
                'data' => 'warehouse_name',
                'name' => 'warehouse.name',
                'defaultContent' => '-',
                'width' => '150px',
                'className' => 'text-center',
                'searchable' => true,
                'orderable' => true
            ]),

            'remark' => new \Yajra\DataTables\Html\Column([
                'title' => trans('inventory_transactions.remark'),
                'data' => 'remark',
                'name' => 'remark',
                'defaultContent' => '-',
                'width' => '200px',
                'className' => 'text-left'
            ]),

            'action' => new \Yajra\DataTables\Html\Column([
                'title' => 'Actions',
                'data' => 'action',
                'name' => 'action',
                'width' => '80px',
                'className' => 'text-center',
                'orderable' => false,
                'searchable' => false
            ])
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'inventory_transactions_' . date('Y-m-d_H-i-s');
    }
}