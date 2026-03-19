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
        
        $categoryMap = Customer::$categoryOptions;

        $paymentTermMap = Customer::$paymentTerm;

        return $dataTable
            ->addColumn('action', 'customers.datatables_actions')
            ->editColumn('paymentterm', function($customer) use ($paymentTermMap) {
                // Find the key that matches the paymentterm value
                $paymentTermValue = $customer->paymentterm;
                
                // If it's numeric (1,2,3 etc), map to the corresponding key
                if (is_numeric($paymentTermValue)) {
                    $keys = array_keys($paymentTermMap);
                    $index = (int)$paymentTermValue - 1;
                    return $paymentTermMap[$keys[$index] ?? 'cash'] ?? $paymentTermValue;
                }
                
                // If it's already a key (cash, credit_note, cod)
                return $paymentTermMap[$paymentTermValue] ?? $paymentTermValue;
            })
            ->editColumn('category', function($customer) use ($categoryMap) {
                return $categoryMap[$customer->category] ?? $customer->category;
            });

            // If you want to keep the original values for exporting, you can add raw columns
            // ->addColumn('raw_paymentterm', function($customer) {
            //     return $customer->paymentterm;
            // })
            // ->addColumn('raw_category', function($customer) {
            //     return $customer->category;
            // });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Customer $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Customer $model)
    {
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
                ->with('driver:id,name')
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
                    'customers.billing_address',
                    'customers.delivery_address',
                    'customers.status',
                    'customers.created_at',
                    'customers.updated_at',
                    'customers.deleted_at',
                    'customers.agent_id',
                    'customers.driver_id',
                    'customers.group',
                    'customers.category',
                    'customers.postcode',
                    'customers.area',
                    'customers.email',
                    'customers.state',
                    'customers.country',
                    'customers.registration_no',
                    'customers.msic',
                    'customers.sst_registration_no',
                    'customers.tourism_tax_registration',
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
                        'filename' => 'customers_' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'customers_' . date('dmYHis')
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
                        'targets' => 7, // Status column index (adjust as needed)
                        'render' => 'function(data, type){return data == 1 ? "Active" : "Inactive";}'
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Inactive</option></select>\';
                            }else if(columns[index].title == \'Payment Term\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="cash">Cash</option><option value="credit_note">Credit Note</option><option value="cod">COD</option></select>\';
                            }else if(columns[index].title == \'Category\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="business">Business</option><option value="individual">Individual</option><option value="government">Government</option></select>\';
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

            'paymentterm'=> new \Yajra\DataTables\Html\Column(['title' => trans('Payment Term'),
            'data' => 'paymentterm',
            'name' => 'paymentterm']),

            'driver_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('Driver'),
            'data' => 'driver.name',
            'name' => 'driver.name']),

            'agent_id'=> new \Yajra\DataTables\Html\Column(['title' => trans('customers.agent'),
            'data' => 'agent.name',
            'name' => 'agent.name']),

            'phone',
            'status',
            'credit',
            
            'category'=> new \Yajra\DataTables\Html\Column(['title' => trans('Category'),
            'data' => 'category',
            'name' => 'category']),

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