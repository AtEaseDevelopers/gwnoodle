<?php

namespace App\DataTables;

use App\Models\ProductBatch;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Crypt;

class ProductBatchDataTable extends DataTable
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
            ->addColumn('action', 'product_batches.datatables_actions')
            ->editColumn('product_id', function($batch) {
                return $batch->product ? $batch->product->name : 'N/A';
            })
            ->editColumn('quantity', function($batch) {
                return number_format($batch->quantity);
            })
            ->editColumn('status', function($batch) {
                $statusLabels = [
                    1 => 'Active',
                    2 => 'Inactive',
                    3 => 'Expired'
                ];
                $statusClasses = [
                    1 => 'success',
                    2 => 'danger',
                    3 => 'warning'
                ];
                $status = $statusLabels[$batch->status] ?? 'Unknown';
                $class = $statusClasses[$batch->status] ?? 'secondary';
                
                return '<span class="badge badge-' . $class . '">' . $status . '</span>';
            })
            ->editColumn('created_at', function($batch) {
                return $batch->created_at->format('d-m-Y H:i');
            })
            ->rawColumns(['action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ProductBatch $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ProductBatch $model)
    {
        return $model->newQuery()->with('product');
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
            ->addAction(['title' => 'Actions', 'width' => '120px', 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[10, 50, 100, 300], ['10 rows', '50 rows', '100 rows', '300 rows']],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> Create',
                    ],
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> Print',
                    ],
                    [
                        'extend' => 'reset',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> Reset',
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> Reload',
                    ],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> Excel',
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Product Batches',
                        'filename' => 'product_batches_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> PDF',
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => 'Product Batches',
                        'filename' => 'product_batches_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> Columns',
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => 'Show rows',
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
                            if(columns[index].title == "Status"){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="1">Active</option><option value="2">Inactive</option><option value="3">Expired</option></select>\';
                            } else if(columns[index].title == "Expiry Date") {
                                var input = \'<input type="date" placeholder="Search">\';
                            } else {
                                var input = \'<input type="text" placeholder="Search">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(),false,true).draw();
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
            'product_id' => ['title' => 'Product', 'data' => 'product_id', 'name' => 'product_id'],
            'batch_code' => ['title' => 'Batch Code', 'data' => 'batch_code', 'name' => 'batch_code'],
            'expiry_date' => ['title' => 'Expiry Date', 'data' => 'expiry_date', 'name' => 'expiry_date'],
            'quantity' => ['title' => 'Current Qty', 'data' => 'quantity', 'name' => 'quantity'],
            'status' => ['title' => 'Status', 'data' => 'status', 'name' => 'status'],
            'created_at' => ['title' => 'Created', 'data' => 'created_at', 'name' => 'created_at', 'exportable' => false],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'product_batches_datatable_' . time();
    }
}