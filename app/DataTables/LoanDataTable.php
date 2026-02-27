<?php

namespace App\DataTables;

use App\Models\Loan;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class LoanDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'loans.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Loan $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function query(Loan $model)
    // {
    //     return $model->newQuery()->with('driver:id,name')->leftJoin('loanpayments', function($join)
    //     {
    //         $join->on('loans.id', '=', 'loanpayments.loan_id');
    //         $join->on('loanpayments.payment','=',DB::raw("'1'"));
    //     })
    //     ->select('loans.id','loans.date','loans.driver_id','loans.description','loans.amount','loans.period','loans.rate'
    //     ,'loans.totalamount','loans.monthlyamount','loans.status',DB::raw('ROUND((loans.totalamount - loans.amount),2) as totalinterest')
    //     ,DB::raw('COALESCE(ROUND(SUM(loanpayments.amount),2),0) as totalpaid'),DB::raw('ROUND(loans.totalamount - COALESCE(SUM(loanpayments.amount),0),2) as outstanding'))
    //     // ->where('loanpayments.payment',1)
    //     ->groupby('loans.id','loans.date','loans.driver_id','loans.description','loans.amount','loans.period','loans.rate'
    //     ,'loans.totalamount','loans.monthlyamount','loans.status');
    // }
    public function query(Loan $model)
    {
        return $model->newQuery()->with('driver:id,name')->leftJoin('loanpayments', function($join)
        {
            $join->on('loans.id', '=', 'loanpayments.loan_id');
            $join->on('loanpayments.payment','=',DB::raw("'1'"));
        })
        ->select('loans.*'
        ,DB::raw('ROUND((loans.totalamount - loans.amount),2) as totalinterest')
        ,DB::raw('concat(loans.id,":",COALESCE(ROUND(SUM(loanpayments.amount),2),0)) as totalpaid')
        ,DB::raw('ROUND(loans.totalamount - COALESCE(SUM(loanpayments.amount),0),2) as outstanding'))
        ->groupby('loans.id','loans.date','loans.driver_id','loans.description','loans.amount','loans.period','loans.rate','loans.totalamount','loans.monthlyamount','loans.status','loans.STR_UDF1','loans.STR_UDF2','loans.STR_UDF3','loans.INT_UDF1','loans.INT_UDF2','loans.INT_UDF3','loans.created_at','loans.updated_at','loans.deleted_at');
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
            ->addAction(['width' => '120px', 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
                // 'order'     => [[0, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons'   => [
                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'Loan'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'Loan'.date('dmYHis')],
                    ['extend' => 'colvis', 'className' => 'btn btn-default btn-sm no-corner','text'=>'<i class="fa fa-columns"></i> Column',],
                    ['extend' => 'pageLength','className' => 'btn btn-default btn-sm no-corner',],
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
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 7,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 8,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 9,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 10,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'PaymentDetails\' loanid=\'"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 11,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                    'targets' => 12,
                    'render' => 'function(data, type){if(data == 1) {return "Active"}else if(data == 9) {return "Closed"}else{return "Unactive"};}'],
                    // [
                    //     'targets' => 3,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 5,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 6,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 7,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 8,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 9,
                    //     'render' => 'function(data, type){return "<a href=\'#\' class=\'PaymentDetails\' loanid=\'"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 10,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    // 'targets' => 11,
                    // 'render' => 'function(data, type){if(data == 1) {return "Active"}else if(data == 9) {return "Closed"}else{return "Unactive"};}'],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option><option value="9">Closed</option></select>\';
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(), true, false).draw();
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
            'date'=> new \Yajra\DataTables\Html\Column(['title' => 'Date',
            'data' => 'date',
            'name' => 'loans.date']),
            'driver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver',
            'data' => 'driver.name',
            'name' => 'driver.name']),
            'description'=> new \Yajra\DataTables\Html\Column(['title' => 'Description',
            'data' => 'description',
            'name' => 'loans.description']),
            'amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Amount',
            'data' => 'amount',
            'name' => 'loans.amount']),
            'period'=> new \Yajra\DataTables\Html\Column(['title' => 'Period (Month)',
            'data' => 'period',
            'name' => 'period']),
            'rate'=> new \Yajra\DataTables\Html\Column(['title' => 'Rate (%)',
            'data' => 'rate',
            'name' => 'rate']),
            'totalinterest'=> new \Yajra\DataTables\Html\Column(['title' => 'Total Interest',
            'data' => 'totalinterest',
            'name' => 'totalinterest',
            'searchable' => false]),
            'totalamount'=> new \Yajra\DataTables\Html\Column(['title' => 'Total Amount with Interest',
            'data' => 'totalamount',
            'name' => 'totalamount',
            'searchable' => false]),
            'monthlyamount'=> new \Yajra\DataTables\Html\Column(['title' => 'Monthly Repayment',
            'data' => 'monthlyamount',
            'name' => 'monthlyamount',
            'searchable' => false]),
            'totalpaid'=> new \Yajra\DataTables\Html\Column(['title' => 'Total Paid',
            'data' => 'totalpaid',
            'name' => 'totalpaid',
            'searchable' => false]),
            'outstanding'=> new \Yajra\DataTables\Html\Column(['title' => 'Outstanding',
            'data' => 'outstanding',
            'name' => 'outstanding',
            'searchable' => false]),
            'status'=> new \Yajra\DataTables\Html\Column(['title' => 'Status',
            'data' => 'status',
            'name' => 'loans.status']),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'loans_datatable_' . time();
    }
}
