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
                        'exportOptions' => [
                            'columns' => ':visible:not(:last-child)',
                            'format' => [
                                'body' => $this->inventorySummaryExportFormatter()
                            ]
                        ],
                        'customizeData' => $this->excelRowExpansionCustomizer(),
                        'className' => 'btn btn-default btn-sm no-corner',
                        'filename' => 'warehouses_' . date('YmdHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> PDF',
                        'exportOptions' => [
                            'columns' => ':visible:not(:last-child)',
                            'format' => [
                                'body' => $this->inventorySummaryExportFormatter()
                            ]
                        ],
                        'customize' => $this->pdfNestedTableCustomizer(),
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
     * JS callback (as a string) used by the Excel/PDF export buttons for the
     * "Inventory Summary" column. That column's cell is a nested HTML table;
     * this extracts its rows into a JSON array (marker-prefixed so it can be
     * told apart from a plain string) so the PDF and Excel customize hooks
     * below can rebuild it as a real nested table / real separate rows,
     * matching what's shown on screen instead of flattening it to text.
     *
     * @return string
     */
    private function inventorySummaryExportFormatter(): string
    {
        return 'function(data, row, column, node) {
            if (column === 7) {
                var items = [];
                $(node).find("tbody tr").each(function() {
                    var cells = $(this).find("td");
                    var product = $(cells[0]).text().trim();
                    var batch = $(cells[1]).text().trim();
                    var qty = $(cells[2]).text().trim();
                    if (product || batch || qty) {
                        items.push({ p: product, b: batch, q: qty });
                    }
                });
                return "__INV_SUMMARY__" + JSON.stringify(items);
            }
            // Every other column: strip HTML tags (badges, <strong>, etc.)
            // and use the plain visible text instead of the raw markup,
            // since providing a custom format.body callback replaces the
            // export button\'s own default tag-stripping for ALL columns.
            return $(node).text().trim();
        }';
    }

    /**
     * JS callback (as a string) for the PDF button\'s `customize(doc)` hook.
     * Parses the marker-prefixed JSON built by inventorySummaryExportFormatter()
     * and replaces that cell in the generated pdfmake document with a real
     * nested table (Product/Batch/Qty, bordered), instead of flattened text.
     *
     * @return string
     */
    private function pdfNestedTableCustomizer(): string
    {
        return <<<'JS'
function(doc) {
    var MARKER = "__INV_SUMMARY__";
    var tableIndex = doc.content.findIndex(function(item) { return item.table; });
    if (tableIndex === -1) { return; }

    var body = doc.content[tableIndex].table.body;
    for (var r = 1; r < body.length; r++) {
        var row = body[r];
        for (var c = 0; c < row.length; c++) {
            var cell = row[c];
            var cellText = typeof cell === 'string' ? cell : (cell && cell.text ? cell.text : '');
            if (cellText.indexOf(MARKER) !== 0) { continue; }

            var items = [];
            try { items = JSON.parse(cellText.substring(MARKER.length)); } catch (e) { items = []; }

            if (items.length === 0) {
                row[c] = { text: 'No inventory', italics: true, color: '#888888', fontSize: 8 };
                break;
            }

            var nestedBody = [[
                { text: 'Product', bold: true, fontSize: 8 },
                { text: 'Batch', bold: true, fontSize: 8 },
                { text: 'Qty', bold: true, fontSize: 8 }
            ]];
            items.forEach(function(item) {
                nestedBody.push([
                    { text: item.p, fontSize: 8 },
                    { text: item.b, fontSize: 8 },
                    { text: item.q, fontSize: 8, alignment: 'center' }
                ]);
            });

            row[c] = {
                table: {
                    headerRows: 1,
                    widths: ['*', '*', 'auto'],
                    body: nestedBody
                },
                layout: {
                    hLineWidth: function() { return 0.5; },
                    vLineWidth: function() { return 0.5; },
                    hLineColor: function() { return '#cccccc'; },
                    vLineColor: function() { return '#cccccc'; },
                    paddingLeft: function() { return 3; },
                    paddingRight: function() { return 3; },
                    paddingTop: function() { return 1; },
                    paddingBottom: function() { return 1; }
                },
                margin: [0, 0, 0, 0]
            };
            break;
        }
    }

    doc.content[tableIndex].layout = {
        hLineWidth: function() { return 0.5; },
        vLineWidth: function() { return 0.5; },
        hLineColor: function() { return '#999999'; },
        vLineColor: function() { return '#999999'; },
        paddingLeft: function() { return 4; },
        paddingRight: function() { return 4; },
        paddingTop: function() { return 2; },
        paddingBottom: function() { return 2; }
    };
}
JS;
    }

    /**
     * JS callback (as a string) for the Excel button\'s `customizeData(data)`
     * hook. Parses the marker-prefixed JSON built by
     * inventorySummaryExportFormatter() and expands the single "Inventory
     * Summary" column into real Product/Batch/Qty columns, exploding each
     * warehouse row into one exported row per inventory item - matching the
     * row-by-row layout shown in the on-screen DataTable, instead of
     * cramming everything into one cell.
     *
     * @return string
     */
    private function excelRowExpansionCustomizer(): string
    {
        return <<<'JS'
function(data) {
    var MARKER = "__INV_SUMMARY__";
    var invIdx = data.header.indexOf('Inventory Summary');
    if (invIdx === -1) { return; }

    data.header.splice(invIdx, 1, 'Product', 'Batch', 'Qty');

    var newBody = [];
    data.body.forEach(function(row) {
        var raw = row[invIdx];
        var items = [];
        if (typeof raw === 'string' && raw.indexOf(MARKER) === 0) {
            try { items = JSON.parse(raw.substring(MARKER.length)); } catch (e) { items = []; }
        }

        var before = row.slice(0, invIdx);
        var after = row.slice(invIdx + 1);

        if (items.length === 0) {
            newBody.push(before.concat(['No inventory', '', ''], after));
        } else {
            items.forEach(function(item) {
                newBody.push(before.concat([item.p, item.b, item.q], after));
            });
        }
    });
    data.body = newBody;
}
JS;
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