<?php

namespace App\DataTables;

use App\Models\DeliveryOrder;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class DeliveryOrderDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'delivery_orders.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\DeliveryOrder $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(DeliveryOrder $model)
    {
        return $model->newQuery()
        ->with('driver:id,name')
        ->with('lorry:id,lorryno')
        ->with('item:id,code')
        ->with('vendor:id,code')
        ->with('source:id,code')
        ->with('destinate:id,code')
        ->leftJoin('claims', function($join)
        {
            $join->on('deliveryorders.id', '=', 'claims.deliveryorder_id');
        })
        ->select('deliveryorders.id','deliveryorders.dono','deliveryorders.date','deliveryorders.driver_id','deliveryorders.lorry_id','deliveryorders.vendor_id','deliveryorders.source_id','deliveryorders.remark',
        'deliveryorders.destinate_id','deliveryorders.item_id','deliveryorders.weight','deliveryorders.shipweight','deliveryorders.fees','deliveryorders.tol','deliveryorders.billingrate','deliveryorders.commissionrate','deliveryorders.status',
        DB::raw('CONCAT(deliveryorders.id,":",deliveryorders.billingrate) as billingrate_data'),
        DB::raw('CONCAT(deliveryorders.id,":",deliveryorders.commissionrate) as commissionrate_data'),
        DB::raw('CONCAT(deliveryorders.id,":",COUNT(claims.id)) as claims'))
        ->groupby('deliveryorders.id','deliveryorders.dono','deliveryorders.date','deliveryorders.driver_id','deliveryorders.lorry_id','deliveryorders.vendor_id','deliveryorders.source_id','deliveryorders.remark',
        'deliveryorders.destinate_id','deliveryorders.item_id','deliveryorders.weight','deliveryorders.shipweight','deliveryorders.fees','deliveryorders.tol','deliveryorders.billingrate','deliveryorders.commissionrate','deliveryorders.status');
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
            // ->addCheckbox([
            //     'defaultContent' => '<input type="checkbox" />',
            //     'title'          => '',
            //     'data'           => 'checkbox',
            //     'name'           => 'checkbox',
            //     'orderable'      => false,
            //     'searchable'     => false,
            //     'exportable'     => false,
            //     'printable'      => true,
            //     'width'          => '10px',
            // ])
            ->addAction(['width' => '120px', 'printable' => false])
            ->parameters([
                // 'dom'       => 'Bfrtip',
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[1, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons'   => [
                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'DO'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'DO'.date('dmYHis')],
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
                        'targets' => 9,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 10,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 11,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 12,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data).toFixed(2).split(".")[1] ? "." + parseFloat(data).toFixed(2).split(".")[1] : "");}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 13,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'BillingRate\' dokey=\'"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 14,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'CommissionRate\' dokey=\'"+data.split(":")[0]+"\'>"+parseFloat(data.split(":")[1]).toFixed(2).split(".")[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + (parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] ? "." + parseFloat(data.split(":")[1]).toFixed(2).split(".")[1] : "")+"</a>";}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 15,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'CountClaim\' dokey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                        ,'className' => 'dt-body-center'
                    ],
                    [
                        'targets' => 16,
                        'render' => 'function(data, type){return data == 1 ? "Active" : "Unactive";}'
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
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Active</option><option value="0">Unactive</option></select>\';
                            }else if(columns[index].title == \'Date\'){
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
            'date'=> new \Yajra\DataTables\Html\Column(['title' => 'Date',
            'data' => 'date',
            'name' => 'deliveryorders.date']),
            'dono'=> new \Yajra\DataTables\Html\Column(['title' => 'DO Number',
            'data' => 'dono',
            'name' => 'dono']),
            'driver_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver',
            'data' => 'driver.name',
            'name' => 'driver.name']),
            'lorry_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Lorry',
            'data' => 'lorry.lorryno',
            'name' => 'lorry.lorryno']),
            'vendor_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Vendor',
            'data' => 'vendor.code',
            'name' => 'vendor.code']),
            'source_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Source',
            'data' => 'source.code',
            'name' => 'source.code']),
            'destinate_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Destination',
            'data' => 'destinate.code',
            'name' => 'destinate.code']),
            'item_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Product',
            'data' => 'item.code',
            'name' => 'item.code']),
            'weight'=> new \Yajra\DataTables\Html\Column(['title' => 'Source Weight',
            'data' => 'weight',
            'name' => 'deliveryorders.weight']),
            'shipweight'=> new \Yajra\DataTables\Html\Column(['title' => 'Destination Weight',
            'data' => 'shipweight',
            'name' => 'deliveryorders.shipweight']),
            'fees'=> new \Yajra\DataTables\Html\Column(['title' => 'Loading/Unloading Fees',
            'data' => 'fees',
            'name' => 'fees']),
            'tol'=> new \Yajra\DataTables\Html\Column(['title' => 'Tol',
            'data' => 'tol',
            'name' => 'tol']),
            'billingrate'=> new \Yajra\DataTables\Html\Column(['title' => 'Billing Rate',
            'data' => 'billingrate_data',
            'name' => 'deliveryorders.billingrate']),
            'commissionrate'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission Rate',
            'data' => 'commissionrate_data',
            'name' => 'deliveryorders.commissionrate']),
            'claims'=> new \Yajra\DataTables\Html\Column(['title' => 'Claims',
            'data' => 'claims',
            'name' => 'claims',
            'searchable' => false]),
            'status',
            'remark'=> new \Yajra\DataTables\Html\Column(['title' => 'Remark',
            'data' => 'remark',
            'name' => 'deliveryorders.remark']),
            // 'STR_UDF1'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF1',
            // 'data' => 'STR_UDF1',
            // 'name' => 'STR_UDF1']),
            // 'STR_UDF2'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF2',
            // 'data' => 'STR_UDF2',
            // 'name' => 'STR_UDF2']),
            // 'STR_UDF3'=> new \Yajra\DataTables\Html\Column(['title' => 'String UDF3',
            // 'data' => 'STR_UDF3',
            // 'name' => 'STR_UDF3']),
            // 'INT_UDF1'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF1',
            // 'data' => 'INT_UDF1',
            // 'name' => 'INT_UDF1']),
            // 'INT_UDF2'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF2',
            // 'data' => 'INT_UDF2',
            // 'name' => 'INT_UDF2']),
            // 'INT_UDF3'=> new \Yajra\DataTables\Html\Column(['title' => 'Integer UDF3',
            // 'data' => 'INT_UDF3',
            // 'name' => 'INT_UDF3']),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'delivery_orders_datatable_' . time();
    }
}
