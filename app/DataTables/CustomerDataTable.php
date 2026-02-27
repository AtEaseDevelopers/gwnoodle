<?php

namespace App\DataTables;

use App\Models\Customer;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class CustomerDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'customers.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Customer $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Customer $model)
    {
        // return $model->newQuery()
        // ->with('agent:id,name')
        // ->with('supervisor:id,name')
        // // ->with('groups:value,description')
        // ->leftJoin(DB::raw('(select invoices.customer_id, sum(invoice_details.totalprice) as totalprice, COALESCE(paymentsummary.amount,0) as paid, ( sum(invoice_details.totalprice) - COALESCE(paymentsummary.amount,0) ) as credit from invoices left join invoice_details on invoices.id = invoice_details.invoice_id left join ( select invoice_payments.customer_id, sum(COALESCE(invoice_payments.amount,0)) as amount from invoice_payments where invoice_payments.status = 1 group by invoice_payments.customer_id ) as paymentsummary on invoices.customer_id = paymentsummary.customer_id where invoices.status = 1 group by invoices.customer_id, paymentsummary.customer_id, paymentsummary.amount) invoicesummary'),
        // function($join)
        // {
        //    $join->on('customers.id', '=', 'invoicesummary.customer_id');
        // })
        // ->leftJoin('codes', function($join)
        // {
        //     $join->whereRaw('find_in_set(codes.value, customers.group)');
        //     $join->where('codes.code', '=', 'customer_group');
        // })
        // ->select('customers.*',DB::raw("COALESCE(invoicesummary.credit,0) as credit"),DB::raw("GROUP_CONCAT(codes.description) as group_descr"))
        // ->distinct()
        // ->groupby('customers.id','customers.code','customers.company','customers.paymentterm','customers.phone','customers.address',
        // 'customers.status','customers.created_at','customers.updated_at','customers.deleted_at','customers.supervisor_id','customers.agent_id', 'customers.sst', 'customers.tin',
        // 'customers.group','customers.group','invoicesummary.customer_id','invoicesummary.totalprice','invoicesummary.paid','invoicesummary.credit',)
        // ;

        $invoicesSubquery = "
        SELECT
            invoices.customer_id,
            SUM(invoice_details.totalprice) AS totalprice
        FROM
            invoices
            LEFT JOIN invoice_details ON invoices.id = invoice_details.invoice_id
        WHERE
            invoices.status = 1
        GROUP BY
            invoices.customer_id
    ";
    
    $paymentsSubquery = "
        SELECT
            invoice_payments.customer_id,
            SUM(COALESCE(invoice_payments.amount, 0)) AS amount
        FROM
            invoice_payments
        WHERE
            invoice_payments.status = 1
        GROUP BY
            invoice_payments.customer_id
    ";
    
            $query = $model->newQuery()
    
                ->with('agent:id,name')
    
                ->with('supervisor:id,name')
                ->leftJoin(DB::raw("
                (
                 SELECT
                    customers.id AS customer_id,
                    COALESCE(total_invoiced.totalprice, 0) AS totalprice,
                    COALESCE(paymentsummary.amount, 0) AS paid,
                    (COALESCE(total_invoiced.totalprice, 0) - COALESCE(paymentsummary.amount, 0)) AS credit
                FROM
                    customers
                    LEFT JOIN (
                        {$invoicesSubquery}
                    ) AS total_invoiced ON customers.id = total_invoiced.customer_id
                    LEFT JOIN (
                        {$paymentsSubquery}
                    ) AS paymentsummary ON customers.id = paymentsummary.customer_id
                
                ) as invoicesummary
            "), function ($join) {
                $join->on('customers.id', '=', 'invoicesummary.customer_id');
            })
            ->leftJoin('codes', function ($join) {
                $join->on('customers.group', '=', 'codes.value')
                    ->where('codes.code', '=', 'customer_group');
            })
            ->select(
                'customers.*',
                DB::raw("COALESCE(invoicesummary.totalprice, 0) as totalprice"),
                DB::raw("COALESCE(invoicesummary.paid, 0) as paid"),
                DB::raw("
                 CASE
                WHEN (COALESCE(invoicesummary.totalprice, 0) - COALESCE(invoicesummary.paid, 0)) >= 0 THEN
                    ABS(COALESCE(invoicesummary.totalprice, 0) - COALESCE(invoicesummary.paid, 0))
                ELSE
                    CONCAT('(', COALESCE(invoicesummary.totalprice, 0) - COALESCE(invoicesummary.paid, 0), ')')
            END as credit
    
                ")
            )
    
                ->groupBy(
                    'customers.id',
                    'customers.code',
                    'customers.company',
                    'customers.paymentterm',
                    'customers.phone',
                    'customers.address',
                    'customers.status',
                    'customers.created_at',
                    'customers.updated_at',
                    'customers.deleted_at',
                    'customers.supervisor_id',
                    'customers.agent_id',
                    'customers.group',
                    'customers.tin',
                    'customers.sst',
                    'customers.customer_type', 
                    'customers.chinese_name',
                    'invoicesummary.customer_id',
                    'invoicesummary.totalprice',
                    'invoicesummary.paid',
                    'invoicesummary.credit'
                );
                
            if ($this->request()->has('group_id') && $this->request()->input('group_id') != -1) {
    
                $query->whereRaw('FIND_IN_SET(?, customers.group)', [$this->request()->input('group_id')]);
    
            }
            
            return $query;
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
            ->addAction(['title' => trans('customers.action'), 'printable' => false])
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
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 3,
                        'visible' => true,
                        'render' => 'function(data, type){
                                                            if(data == 1){
                                                                return "Cash";
                                                            }
                                                            if(data == 2){
                                                                return "Credit Note";
                                                            }
                                                        }'
                    ],
                    [
                        'targets' => 7,
                        'className' => "truncate"
                    ],
                    [
                    'targets' => 8,
                    'render' => 'function(data, type){return data == 1 ? "Active" : "Unactive";}'],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option></select>\';
                            }else if(columns[index].title == \'Payment Term\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Cash</option><option value="2">Credit Note</option></select>\';
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
        return [
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'id',
            'name' => 'id',
            'orderable' => false,
            'searchable' => false]),

            'code',
            'company'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.company'),
            'data' => 'company',
            'name' => 'company']),

            'paymentterm'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.paymentterm'),
            'data' => 'paymentterm',
            'name' => 'paymentterm']),

            // 'group'=> new \Yajra\DataTables\Html\Column(['title' => 'Group',
            // 'data' => 'groups.description',
            // 'name' => 'groups.description']),

            'agent_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.agent'),
            'data' => 'agent.name',
            'name' => 'agent.name']),

            'supervisor_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.operation'),
            'data' => 'supervisor.name',
            'name' => 'supervisor.name']),

            'phone',
            'address',
            'status',
            'credit',
            
            'sst'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.ssm'),
            'data' => 'sst',
            'name' => 'sst']),
            
            'tin',

            'group_descr'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.group'),
            'data' => 'GroupDescription',
            'name' => 'group',
            'searchable' => false]),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'customers_datatable_' . time();
    }
}
