<?php

namespace App\DataTables;

use App\Models\InvoicePayment;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use App\Models\Code;

class InvoicePaymentDataTable extends DataTable
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
        $dataTable
        ->addColumn('payment_no', function ($row) {
            return 'PR' . str_pad($row->id, 5, '0', STR_PAD_LEFT); // Assuming 'PR00001' format
        })
        ->addColumn('action', 'invoice_payments.datatables_actions')
        ->filter(function ($query) {
            
            $searchValue = request('columns')[4]['search']['value'] ?? '';

            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    // Filter by custom field 'payment_no'
                    $q->whereRaw("LOWER(CONCAT('PR', LPAD(invoice_payments.id, 5, '0'))) LIKE LOWER(?)", ["%{$searchValue}%"]);
                });
            }
        });
        return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\InvoicePayment $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(InvoicePayment $model)
    {
        return $model->newQuery()
        ->with('invoice:id,invoiceno')
        ->with('customer')
        ->selectRaw('invoice_payments.*, CONCAT(\'PR\', LPAD(invoice_payments.id, 5, \'0\')) AS payment_no');
        
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
            ->addAction(['title' => trans('invoice_payments.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
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
                        'visible' => true,
                        'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 3,
                        'render' => 'function(data, type, row){
                                var paymentTerms = {
                                    1: \'Cash\',
                                    3: \'Online BankIn\',
                                    4: \'E-wallet\',
                                    5: \'Cheque\'
                                };
                                return paymentTerms[data] || \'Unknown\';
                            }'
                    ],
                    [
                        'targets' => 8,
                        'render' => 'function(data, type){ if(data != null){return "<a target=\'_blank\' href=\''.config('app.url').'/"+data+"\'>view</a>";}else{return "";}}'
                    ],
                    [
                        'targets' => 7,
                         'render' => 'function(data, type){
                            if (data == 0) {
                                return "New";
                            } else if (data == 1) {
                                return "Completed";
                            } else if (data == 2) {
                                return "Canceled";
                            } else {
                                return "Unknown";
                            }
                        }'
                    ],
                    [
                    'targets' => 11,
                    'render' => 'function(data, type){
                        if(data == 1) {
                            return "Synced";
                        } else if (data == 2) {
                            return "Voided";
                        }
                        
                    }'
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Completed</option><option value="0">New</option><option value="2">Canceled</option></select>\';
                            }else if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Cash</option><option value="3">Online BankIn</option><option value="4">E-wallet</option><option value="5">Cheque</option></select>\';
                            }else if(columns[index].title == \'Approve At\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
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
                    var groupItems = '.json_encode(Code::where('code','customer_group')->pluck('description','value')->toArray()).';
                    var x = document.getElementById("group");
                    $.each(groupItems, function( index, value ) {
                        var option = document.createElement("option");
                        option.text = value;
                        option.value = index;
                        x.add(option);
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
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'id',
            'name' => 'id',
            'orderable' => false,
            'searchable' => false]),

            'created_at'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.date'),
            'data' => 'created_at',
            'name' => 'created_at']),

            'customer_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.customer'),
            'data' => 'customer.company',
            'name' => 'customer.company']),
            
            'type'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.type'),
            'data' => 'type',
            'name' => 'type']),

            'payment_no'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.payment_no'),
            'data' => 'payment_no',
            'name' => 'payment_no']),
            
            'invoice_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.invoice_no'),
            'data' => 'invoice_id',
            'name' => 'invoice.invoice_no']),

            'amount'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.amount'),
            'data' => 'amount',
            'name' => 'invoice.amount']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.status'),
            'data' => 'status',
            'name' => 'status']),

            'attachment'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.attachment'),
            'data' => 'attachment',
            'name' => 'attachment']),

            'approve_by'=> new \Yajra\DataTables\Html\Column(['title' =>  trans('invoice_payments.approve_by'),
            'data' => 'approve_by',
            'name' => 'approve_by']),

            'approve_at'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.approve_at'),
            'data' => 'approve_at',
            'name' => 'approve_at']),
            
            // 'xero_status'=> new \Yajra\DataTables\Html\Column(['title' => 'Xero Status',
            // 'data' => 'xero_status',
            // 'name' => 'xero_status']),

            // 'remark',

            'group'=> new \Yajra\DataTables\Html\Column(['title' => trans('invoice_payments.group'),
            'data' => 'customer.GroupDescription',
            'name' => 'customer.group',
            'orderable' => false]),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'invoice_payments_datatable_' . time();
    }
}
