<?php

namespace App\DataTables;

use App\Models\Invoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\Models\Code;

class InvoiceDataTable extends DataTable
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

        // Add e-invoice column conditionally
        if (config('services.e_invoice.enabled', false)) {
            $dataTable->addColumn('einvoice_submit_type', function ($invoice) {
                if ($invoice->einvoice && $invoice->einvoice->uuid) {
                    return 'E-Invoice';
                } elseif ($invoice->consolidatedEinvoices) {
                    $hasSubmitted = $invoice->consolidatedEinvoices->contains(function ($consolidated) {
                        return $consolidated->uuid !== null;
                    });
                    return $hasSubmitted ? 'Consolidated E-Invoice' : '';
                }
                return '';
            });
        }

        $dataTable->addColumn('created_by', function ($invoice) {
            if ($invoice->trip_uuid) {
                return $invoice->driver->name ?? 'Driver';
            }

            return $invoice->creator->name ?? 'Admin';
        });

        // 'created_by' isn't a real column (see addColumn() above - it's
        // derived from trip_uuid + driver name), so Yajra can't build a
        // WHERE clause against it on its own; this tells it how to. The
        // filter dropdown (see html()'s initComplete) submits "admin" or a
        // driver id, matching the two branches below.
        $dataTable->filterColumn('created_by', function ($query, $keyword) {
            if ($keyword === 'admin') {
                $query->whereNull('invoices.trip_uuid');
            } elseif (is_numeric($keyword)) {
                $query->whereNotNull('invoices.trip_uuid')->where('invoices.driver_id', $keyword);
            }
        });

        // The 'date' column sorts by id (see getColumns()) rather than its
        // own value, so the most recently created invoice always shows
        // first. Yajra resolves search AND sort against the same column
        // key though, so without this override, searching the Date column
        // would silently run its WHERE/REGEXP against `id` instead of
        // `date` - which is exactly what was happening.
        $dataTable->orderColumn('date', 'id $1');

        return $dataTable->addColumn('action', 'invoices.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Invoice $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Invoice $model)
    {
        return $model->newQuery()
        ->with('customer')
        ->with('driver:id,name')
        ->with('creator:id,name')
        ->with('kelindan:id,name')
        ->with('agent:id,name')
        ->with('supervisor:id,name')
        ->with('invoicedetail')
        ->select('invoices.*');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $driverOptions = '<option value=""></option><option value="admin">Admin</option>';
        foreach (\App\Models\Driver::where('status', '!=', \App\Models\Driver::STATUS_DELETED)->orderBy('name')->pluck('name', 'id') as $driverId => $driverName) {
            $driverOptions .= '<option value="' . $driverId . '">' . e($driverName) . '</option>';
        }

        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['title' => trans('invoices.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[2, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
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
                        'filename' => 'invoice' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'invoice' . date('dmYHis')
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
                        'targets' => -1,
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 4,
                        'visible' => true,
                        'render' => 'function(data, type){var totalprice = 0; $.each(data,function(index,value){ totalprice=totalprice+parseFloat(value.totalprice) }); return totalprice.toFixed(2);}'
                    ],
                    [
                        'targets' => 5, // Payment term column index
                        'render' => 'function(data, type, row){
                            var paymentTerms = {
                                1: "Cash",
                                2: "Credit",
                                3: "Online Payment",
                                4: "Touch n Go",
                                5: "Cheque"
                            };
                            return paymentTerms[data] || "Unknown";
                        }'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){return data == 1 ? "Completed" : "New";}'
                    ],
                    [
                        'targets' => 7,
                        'visible' => true,
                        'render' => 'function(data, type, row){
                            if (type !== "display" || !data) {
                                return data ? data : "";
                            }

                            var label = data.charAt(0).toUpperCase() + data.slice(1);

                            if (row.autocount_message) {
                                var rawMsg = row.autocount_message;
                                var formattedMsg = rawMsg;

                                // Try to format JSON or Array beautifully
                                try {
                                    if (typeof rawMsg === "string" && (rawMsg.trim().startsWith("{") || rawMsg.trim().startsWith("["))) {
                                        formattedMsg = JSON.stringify(JSON.parse(rawMsg), null, 2);
                                    } else if (typeof rawMsg === "object") {
                                        formattedMsg = JSON.stringify(rawMsg, null, 2);
                                    }
                                } catch (e) {
                                    // If it fails, it is just a normal string. Keep it as is.
                                }

                                // Safely escape HTML characters so it does not break the span attribute
                                var safeMsg = String(formattedMsg)
                                    .replace(/&/g, "&amp;")
                                    .replace(/"/g, "&quot;")
                                    .replace(/\'/g, "&#39;")
                                    .replace(/</g, "&lt;")
                                    .replace(/>/g, "&gt;");

                                return "<span class=\'autocount-error-popover\' data-msg=\'" + safeMsg + "\' style=\'cursor:pointer; display:block; width:100%;\'>" + label + "</span>";
                            }

                            return label;
                        }',
                        'createdCell' => 'function(td, cellData, rowData, row, col){
                            if (!cellData) {
                                return;
                            }

                            var bgColor = "#e2e3e5"; // default/other - light gray
                            var textColor = "#383d41";
                            if (cellData === "success") {
                                bgColor = "#d4edda"; // light green
                                textColor = "#155724";
                            } else if (cellData === "failed") {
                                bgColor = "#f8d7da"; // light red
                                textColor = "#721c24";
                            } else if (cellData === "pending") {
                                bgColor = "#fff3cd"; // light amber
                                textColor = "#856404";
                            }

                            $(td).css({
                                "background-color": bgColor,
                                "color": textColor,
                                "font-weight": "500",
                                "text-align": "center"
                            });
                        }'
                    ],
                  
                ],
                'drawCallback' => 'function(settings) {
                    if(typeof $.fn.popover !== "undefined") {
                        $(".autocount-error-popover").each(function() {
                            var $el = $(this);
                            
                            // Initialize Bootstrap Popover
                            $el.popover({
                                trigger: "manual",
                                placement: "left",
                                html: true,
                                title: "AutoCount Message <small style=\'float:right; font-weight:normal; color:#6c757d;\'>Click to pin</small>",
                                // Use .attr("data-msg") to prevent jQuery from re-parsing the JSON string
                                content: "<div style=\'max-height: 250px; overflow-y: auto; user-select: text; font-family: monospace; font-size:12px; white-space: pre-wrap; word-wrap: break-word;\'>" + $el.attr("data-msg") + "</div>"
                            });

                            // 1. Hover to Peek
                            $el.on("mouseenter", function() {
                                if (!$el.data("pinned")) {
                                    $el.popover("show");
                                }
                            });

                            // 2. Mouse leave to hide (unless pinned)
                            $el.on("mouseleave", function() {
                                if (!$el.data("pinned")) {
                                    $el.popover("hide");
                                }
                            });

                            // 3. Click to Pin / Unpin
                            $el.on("click", function(e) {
                                e.stopPropagation();
                                if ($el.data("pinned")) {
                                    $el.data("pinned", false);
                                    $el.popover("hide");
                                } else {
                                    $(".autocount-error-popover").data("pinned", false).popover("hide");
                                    $el.data("pinned", true);
                                    $el.popover("show");
                                }
                            });
                        });
                        
                        // 4. Click anywhere outside the popover to close it
                        $(document).off("click.closePopover").on("click.closePopover", function(e) {
                            if (!$(e.target).closest(".popover").length && !$(e.target).hasClass("autocount-error-popover")) {
                                $(".autocount-error-popover").data("pinned", false).popover("hide");
                            }
                        });
                    }
                }',
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Completed</option><option value="0">New</option></select>\';
                            }else if(columns[index].title == \'Payment Term\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Cash</option><option value="2">Credit</option><option value="3">Online Payment</option><option value="4">Touch n Go</option><option value="5">Cheque</option></select>\';
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else if(columns[index].title == \'Group\'){
                                var input = \'<select id="group" class="border-0" style="width: 100%;"><option value=""></option></select>\';
                            }else if(columns[index].title == \'Created By\'){
                                var input = \'<select class="border-0" style="width: 100%;">' . $driverOptions . '</select>\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                var __searchVal = $(this).val();
                                var __isDateList = /^\d{4}-\d{2}-\d{2}(\|\d{4}-\d{2}-\d{2})*$/.test(__searchVal);
                                column.search(__searchVal, __isDateList, !__isDateList).draw();
                                ShowLoad();
                            })
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
        $columns = [
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'id',
            'name' => 'id',
            'orderable' => false,
            'searchable' => false
            ]),

            'invoiceno' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.invoice_no'),
                'data' => 'invoiceno',
                'name' => 'invoiceno'
            ]),

            'date' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.date'),
                'data' => 'date',
                // Deliberately no 'name' override here: Yajra resolves both
                // search AND sort against a column's 'name' (falling back to
                // 'data' only if 'name' is unset). Sorting by creation order
                // instead of the date value is handled separately via
                // orderColumn('date', 'id $1') in dataTable() below, so this
                // column's search still runs against the actual date data.
                'name' => 'date'
            ]),

            'customer_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.customer'),
                'data' => 'customer.company',
                'name' => 'customer.company'
            ]),

            // 'driver_id' => new \Yajra\DataTables\Html\Column([
            //     'title' => trans('invoices.driver'),
            //     'data' => 'driver.name',
            //     'name' => 'driver.name'
            // ]),

            // 'kelindan_id' => new \Yajra\DataTables\Html\Column([
            //     'title' => trans('invoices.kelindan'),
            //     'data' => 'kelindan.name',
            //     'name' => 'kelindan.name'
            // ]),

            // 'agent_id' => new \Yajra\DataTables\Html\Column([
            //     'title' => trans('invoices.agent'),
            //     'data' => 'agent.name',
            //     'name' => 'agent.name'
            // ]),

            // 'supervisor_id' => new \Yajra\DataTables\Html\Column([
            //     'title' => trans('invoices.supervisor'),
            //     'data' => 'supervisor.name',
            //     'name' => 'supervisor.name'
            // ]),

            'total' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.total_price'),
                'data' => 'invoicedetail',
                'name' => 'invoicedetail',
                'searchable' => false
            ]),

            'paymentterm' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.payment_term'),
                'data' => 'paymentterm',
                'name' => 'invoices.paymentterm'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.status'),
                'data' => 'status',
                'name' => 'invoices.status'
            ]),

            'autocount_status' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.autocount_status'),
                'data' => 'autocount_status',
                'name' => 'invoices.autocount_status',
                'searchable' => false
            ]),

            'created_by' => new \Yajra\DataTables\Html\Column([
                'title' => 'Created By',
                'data' => 'created_by',
                'name' => 'created_by',
                'orderable' => false,
                'searchable' => true
            ]),
        ];

        if (config('services.e_invoice.enabled', false)) {
            $columns['einvoice_submit_type'] = new \Yajra\DataTables\Html\Column([
                'title' => 'E-Invoice Submit Type',
                'data' => 'einvoice_submit_type',
                'name' => 'einvoice_submit_type',
                'orderable' => false,
                'searchable' => false
            ]);
        }
        return $columns;

    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'invoices_datatable_' . time();
    }
}