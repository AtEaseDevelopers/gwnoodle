<?php

namespace App\DataTables;

use App\Models\paymentdetail;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class paymentdetailDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'paymentdetails.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\paymentdetail $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(paymentdetail $model)
    {
        return $model->newQuery()
        ->select('paymentdetails.id','paymentdetails.driver_id','paymentdetails.month','paymentdetails.datefrom','paymentdetails.dateto','paymentdetails.deduct_amount','paymentdetails.final_amount','paymentdetails.status',
        'paymentdetails.do_report','paymentdetails.do_amount',
        'paymentdetails.claim_report','paymentdetails.claim_amount',
        'paymentdetails.comp_report','paymentdetails.comp_amount',
        'paymentdetails.adv_report','paymentdetails.adv_amount',
        'paymentdetails.loanpay_report','paymentdetails.loanpay_amount',
        'paymentdetails.bonus_report','paymentdetails.bonus_amount',
        // 'drivers.bankdetails1','drivers.bankdetails2','drivers.remark',
        DB::raw('CONCAT(coalesce(paymentdetails.do_report,0),":",coalesce(paymentdetails.do_amount,0)) as do'),
        DB::raw('CONCAT(coalesce(paymentdetails.claim_report,0),":",coalesce(paymentdetails.claim_amount,0)) as claim'),
        DB::raw('CONCAT(coalesce(paymentdetails.comp_report,0),":",coalesce(paymentdetails.comp_amount,0)) as comp'),
        DB::raw('CONCAT(coalesce(paymentdetails.adv_report,0),":",coalesce(paymentdetails.adv_amount,0)) as adv'),
        DB::raw('CONCAT(coalesce(paymentdetails.loanpay_report,0),":",coalesce(paymentdetails.loanpay_amount,0)) as loanpay'),
        DB::raw('CONCAT(coalesce(paymentdetails.bonus_report,0),":",coalesce(paymentdetails.bonus_amount,0)) as bonus'),
        DB::raw('(coalesce(paymentdetails.do_amount,0) - coalesce(paymentdetails.deduct_amount,0)) as aftdct_amount'))
        ->groupby('paymentdetails.id','paymentdetails.driver_id','paymentdetails.month','paymentdetails.datefrom','paymentdetails.dateto','paymentdetails.deduct_amount','paymentdetails.final_amount','paymentdetails.status',
        'paymentdetails.do_report','paymentdetails.do_amount',
        'paymentdetails.claim_report','paymentdetails.claim_amount',
        'paymentdetails.comp_report','paymentdetails.comp_amount',
        'paymentdetails.adv_report','paymentdetails.adv_amount',
        'paymentdetails.loanpay_report','paymentdetails.loanpay_amount',
        'paymentdetails.bonus_report','paymentdetails.bonus_amount',
        // 'drivers.bankdetails1','drivers.bankdetails2','drivers.remark',
        'paymentdetails.created_at','paymentdetails.updated_at','paymentdetails.deleted_at')
        ->with('driver:id,name,employeeid,bankdetails1,bankdetails2,remark');
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
                'dom'       => '<"col"<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons'   => [
                    // ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'DO'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'PaymentDetail'.date('dmYHis')],
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
                        'targets' => 5,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 7,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 8,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 9,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 10,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 11,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 12,
                        'render' => 'function(data, type){return "<a target=\'_blank\' href=\''.config("app.url").'/showreport/"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 13,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ]
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
                            }else if(columns[index].title == \'Date From\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else if(columns[index].title == \'Date To\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }
                            else{
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
            'employeeid'=> new \Yajra\DataTables\Html\Column(['title' => 'Employee ID',
            'data' => 'driver.employeeid',
            'name' => 'driver.employeeid']),
            'driver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver',
            'data' => 'driver.name',
            'name' => 'driver.name']),
            'datefrom'=> new \Yajra\DataTables\Html\Column(['title' => 'Date From',
            'data' => 'datefrom',
            'name' => 'datefrom']),
            'dateto'=> new \Yajra\DataTables\Html\Column(['title' => 'Date To',
            'data' => 'dateto',
            'name' => 'dateto']),
            // 'month',
            'do_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission',
            'data' => 'do',
            'name' => 'do_amount']),
            'deduct_amount',
            'aftdct_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'After Deduct Amount',
            'data' => 'aftdct_amount',
            'name' => 'aftdct_amount',
            'searchable' => false]),
            'claim_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Claim',
            'data' => 'claim',
            'name' => 'claim_amount']),
            'comp_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Compound',
            'data' => 'comp',
            'name' => 'comp_amount']),
            'adv_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Advance',
            'data' => 'adv',
            'name' => 'adv_amount']),
            'loanpay_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Loan Pay',
            'data' => 'loanpay',
            'name' => 'loanpay_amount']),
            'bonus_amount'=> new \Yajra\DataTables\Html\Column(['title' => 'Bonus',
            'data' => 'bonus',
            'name' => 'bonus_amount']),
            'final_amount',
            'driver_bankdetails1'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver Bank Details 1',
            'data' => 'driver.bankdetails1',
            'name' => 'driver.bankdetails1']),
            'driver_bankdetails2'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver Bank Details 2',
            'data' => 'driver.bankdetails2',
            'name' => 'driver.bankdetails2']),
            'driver_remark'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver Remark',
            'data' => 'driver.remark',
            'name' => 'driver.remark']),
            // 'status'
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'paymentdetails_datatable_' . time();
    }
}
