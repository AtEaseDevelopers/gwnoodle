<?php

namespace App\DataTables;

use App\Models\InventoryBalance;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class InventoryBalanceDataTable extends DataTable
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
            ->addColumn('action', 'inventory_balances.datatables_actions')
            ->editColumn('batches', function($inventoryBalance) {
                if (empty($inventoryBalance->batches)) {
                    return '<span class="text-muted">No batches</span>';
                }
                
                $batches = $inventoryBalance->batches_with_details;
                $html = '<div style="max-height: 200px; overflow-y: auto;">';
                $html .= '<table class="table table-sm table-bordered mb-0">';
                $html .= '<thead><tr><th>Batch Code</th><th>Product</th><th>Qty</th><th>Expiry</th></tr></thead><tbody>';
                
                foreach ($batches as $batch) {
                    $expiryClass = '';
                    $daysToExpiry = now()->diffInDays(\Carbon\Carbon::parse($batch['expiry_date']), false);
                    
                    if ($daysToExpiry < 0) {
                        $expiryClass = 'text-danger';
                    } elseif ($daysToExpiry <= 30) {
                        $expiryClass = 'text-warning';
                    } else {
                        $expiryClass = 'text-success';
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td><strong>' . $batch['batch_code'] . '</strong></td>';
                    $html .= '<td>' . $batch['product_name'] . '<br><small>' . '</small></td>';
                    $html .= '<td class="text-center">' . number_format($batch['quantity']) . '</td>';
                    $html .= '<td class="text-center ' . $expiryClass . '">' . $batch['expiry_date'] . '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
                $html .= '</div>';
                
                return $html;
            })
            ->editColumn('total_quantity', function($inventoryBalance) {
                $total = array_sum($inventoryBalance->batches ?? []);
                return '<strong>' . number_format($total) . '</strong>';
            })
            ->editColumn('total_batches', function($inventoryBalance) {
                $count = count($inventoryBalance->batches ?? []);
                return '<span class="badge badge-info">' . $count . ' batches</span>';
            })
            ->rawColumns(['action', 'batches', 'total_quantity', 'total_batches']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\InventoryBalance $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InventoryBalance $model)
    {
        return $model->newQuery()
            ->with('lorry:id,lorryno')
            ->select('inventory_balances.*');
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
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner', 'text' => trans('table_buttons.print')],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner', 'text' => trans('table_buttons.reset')],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner', 'text' => trans('table_buttons.reload')],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> ' . trans('table_buttons.excel'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'inventory_balances_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'inventory_balances_' . date('dmYHis')
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
                        'targets' => 3, // Batches column
                        'render' => 'function(data, type, row) {
                            if (type === "export") {
                                // For export, create a text summary
                                if (!row.batches || Object.keys(row.batches).length === 0) {
                                    return "No batches";
                                }
                                var summary = [];
                                $.each(row.batches, function(batchId, quantity) {
                                    summary.push(batchId + ":" + quantity);
                                });
                                return summary.join(", ");
                            }
                            return data;
                        }'
                    ]
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            var input = \'<input type="text" placeholder="Search " style="width: 100%;">\';
                            $(input).appendTo($(column.footer()).empty()).on(\'keyup change\', function(){
                                column.search($(this).val(), true, false).draw();
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
            'lorry_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('Van'),
                'data' => 'lorry.lorryno',
                'name' => 'lorry.lorryno',
                'searchable' => true,
                'orderable' => true
            ]),

            'total_batches' => new \Yajra\DataTables\Html\Column([
                'title' => 'Total Batches',
                'data' => 'total_batches',
                'name' => 'total_batches',
                'searchable' => false,
                'orderable' => false,
                'exportable' => true
            ]),

            'total_quantity' => new \Yajra\DataTables\Html\Column([
                'title' => 'Total Units',
                'data' => 'total_quantity',
                'name' => 'total_quantity',
                'searchable' => false,
                'orderable' => true,
                'exportable' => true
            ]),

            'batches' => new \Yajra\DataTables\Html\Column([
                'title' => 'Batch Details',
                'data' => 'batches',
                'name' => 'batches',
                'searchable' => false,
                'orderable' => false,
                'exportable' => true
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
        return 'inventory_balances_' . date('Y-m-d_H-i-s');
    }
}