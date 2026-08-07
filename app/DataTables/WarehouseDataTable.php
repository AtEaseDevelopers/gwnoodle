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
            // Exact match: status stores 'active'/'inactive' strings, and a
            // LIKE '%active%' search would match 'inactive' rows too.
            ->filterColumn('status', function ($query, $keyword) {
                $query->where('warehouses.status', $keyword);
            })
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
                        'customize' => $this->excelBorderCustomizer(),
                        // Without this, the html5 export writer silently
                        // omits <c> elements for empty-string cells (our
                        // blanked-out continuation-row columns) entirely,
                        // leaving nothing there for the border customizer
                        // above to attach a style to.
                        'createEmptyCells' => true,
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
                    
                    // Add text search for other columns (skip non-searchable
                    // ones - a box on those would silently do nothing)
                    var columnDefs = table.init().columns;
                    table.columns().every(function (index) {
                        var column = this;
                        var columnName = column.header().textContent.trim();

                        if(columnDefs[index] && columnDefs[index].searchable && columnName !== "Status" && columnName !== "Stock Out" && columnName !== "Actions") {
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
    try {
        var MARKER = "__INV_SUMMARY__";
        var invIdx = -1;
        for (var i = 0; i < data.header.length; i++) {
            if (data.header[i] === 'Inventory Summary') { invIdx = i; break; }
        }
        if (invIdx === -1) { return; }

        var headerLen = data.header.length;

        // The table's <tfoot> (per-column filter inputs) makes exportData()
        // always populate data.footer, sized to the ORIGINAL column count,
        // even though this button never sets footer:true so it's never
        // actually written to the sheet. Once we expand the header from 8
        // to 10 columns below, the XLSX column-width calculator still reads
        // data.footer[b] for every b up to the new header length - indexes
        // 8/9 don't exist in the stale 8-element footer array, so it throws
        // "Cannot read properties of undefined (reading 'length')". Since
        // it's never rendered anyway, just drop it instead of resizing it.
        data.footer = null;

        // Mutate header in place (rather than reassigning data.header to a
        // new array) since some DataTables Buttons internals read this
        // array by the reference captured before this callback runs.
        var newHeaderVals = ['Product', 'Batch', 'Qty'];
        data.header.splice.apply(data.header, [invIdx, 1].concat(newHeaderVals));
        // Guard against any hole ending up in the header array (the XLSX
        // column-width calculator indexes every slot up to header.length
        // with no undefined-check, so a single gap throws there).
        for (var hi = 0; hi < data.header.length; hi++) {
            if (data.header[hi] === undefined || data.header[hi] === null) {
                data.header[hi] = '';
            }
        }

        var newBody = [];
        for (var r = 0; r < data.body.length; r++) {
            var row = data.body[r];
            // Defend against a row that's shorter than the header (would
            // otherwise silently misalign columns after the splice below).
            while (row.length < headerLen) { row.push(''); }

            var raw = row[invIdx];
            var items = [];
            if (typeof raw === 'string' && raw.indexOf(MARKER) === 0) {
                try {
                    var parsed = JSON.parse(raw.substring(MARKER.length));
                    if (Array.isArray(parsed)) { items = parsed; }
                } catch (e) { items = []; }
            }

            var before = row.slice(0, invIdx);
            var after = row.slice(invIdx + 1);
            var blankBefore = before.map(function () { return ''; });
            var blankAfter = after.map(function () { return ''; });

            if (items.length === 0) {
                newBody.push(before.concat(['No inventory', '', ''], after));
            } else {
                for (var ii = 0; ii < items.length; ii++) {
                    var item = items[ii] || {};
                    var rowBefore = ii === 0 ? before : blankBefore;
                    var rowAfter = ii === 0 ? after : blankAfter;
                    newBody.push(rowBefore.concat([item.p || '', item.b || '', item.q || ''], rowAfter));
                }
            }
        }

        data.body.splice.apply(data.body, [0, data.body.length].concat(newBody));
    } catch (e) {
        // If anything here goes wrong, leave data untouched rather than
        // letting the export crash outright - worst case the Inventory
        // Summary column falls back to its flattened marker text.
    }
}
JS;
    }

    /**
     * JS callback (as a string) for the Excel button\'s `customize(xlsx)`
     * hook. Runs after customizeData/the sheet XML is built, and gives
     * every data cell (header row onward) a thin border so the exported
     * rows read like the on-screen bordered table, and warehouse groups
     * (marked by a filled-in Warehouse Name cell followed by blank
     * continuation rows) are visually easy to tell apart.
     *
     * @return string
     */
    private function excelBorderCustomizer(): string
    {
        return <<<'JS'
function(xlsx) {
    try {
        var sheet = xlsx.xl.worksheets["sheet1.xml"];
        var styles = xlsx.xl["styles.xml"];
        var cellXfs = $("cellXfs", styles);

        // borderId 1 is the "thin line on all 4 sides" border definition
        // DataTables Buttons already ships in every generated workbook -
        // reuse it instead of declaring a new <border>.
        var BORDER_ID = 1;
        var styleCache = {};

        function borderedStyleFor(existingIndex) {
            if (styleCache[existingIndex] !== undefined) { return styleCache[existingIndex]; }
            var xfs = cellXfs.children();
            var base = xfs.eq(existingIndex);
            var clone = base.length ? base.clone() : $("<xf/>");
            clone.attr("borderId", BORDER_ID);
            clone.attr("applyBorder", "1");
            cellXfs.append(clone);
            var newIndex = cellXfs.children().length - 1;
            cellXfs.attr("count", cellXfs.children().length);
            styleCache[existingIndex] = newIndex;
            return newIndex;
        }

        // Border every cell from row 2 (the column header row) onward -
        // row 1 is the merged "GW-NOODLE" title cell and is left as-is.
        $("row", sheet).each(function() {
            var rowNum = parseInt($(this).attr("r"), 10);
            if (!rowNum || rowNum < 2) { return; }
            $(this).find("c").each(function() {
                var existing = $(this).attr("s");
                var existingIndex = existing ? parseInt(existing, 10) : 0;
                $(this).attr("s", borderedStyleFor(existingIndex));
            });
        });
    } catch (e) {
        // Leave the file unbordered rather than let a styling error break
        // the export outright.
    }
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