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
                        'targets' => 8,
                        'visible' => true,
                        'render' => 'function(data, type){var totalprice = 0; $.each(data,function(index,value){ totalprice=totalprice+parseFloat(value.totalprice) }); return totalprice.toFixed(2);}'
                    ],
                    [
                    'targets' => 9,
                    'render' => 'function(data, type, row){
                            var paymentTerms = {
                                1: \'Cash\',
                                2: \'Credit\',
                                3: \'Online BankIn\',
                                4: \'E-wallet\',
                                5: \'Cheque\'
                            };
                            return paymentTerms[data] || \'Unknown\';
                        }'
                    ],
                    [
                    'targets' => 10,
                    'render' => 'function(data, type){return data == 1 ? "Completed" : "New";}'
                    ],
                  
                ],
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Cash</option><option value="2">Credit</option><option value="3">Online BankIn</option><option value="4">E-wallet</option><option value="5">Cheque</option></select>\';
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else if(columns[index].title == \'Group\'){
                                var input = \'<select id="group" class="border-0" style="width: 100%;"><option value=""></option></select>\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(),true,false).draw();
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
                'name' => 'date'
            ]),

            'customer_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.customer'),
                'data' => 'customer.company',
                'name' => 'customer.company'
            ]),

            'driver_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.driver'),
                'data' => 'driver.name',
                'name' => 'driver.name'
            ]),

            'kelindan_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.kelindan'),
                'data' => 'kelindan.name',
                'name' => 'kelindan.name'
            ]),

            'agent_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.agent'),
                'data' => 'agent.name',
                'name' => 'agent.name'
            ]),

            'supervisor_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.supervisor'),
                'data' => 'supervisor.name',
                'name' => 'supervisor.name'
            ]),

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
