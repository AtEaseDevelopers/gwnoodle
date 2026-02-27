<?php

namespace App\DataTables;

use App\Models\CommissionByVendors;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class CommissionByVendorsDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'commission_by_vendors.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CommissionByVendors $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CommissionByVendors $model)
    {
        return $model->newQuery()
        ->with('lorry:id,lorryno')
        ->with('vendor:id,name')
        ->select('commissionbyvendors.*');
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
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons'   => [
                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'CommissionByVendor'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'CommissionByVendor'.date('dmYHis')],
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
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                        'targets' => 5,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                        ,'className' => 'dt-body-right'
                    ],
                    [
                    'targets' => 6,
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
            'lorry_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Lorry',
            'data' => 'lorry.lorryno',
            'name' => 'lorry.lorryno']),
            'vendor_id'=> new \Yajra\DataTables\Html\Column(['title' => 'Vendor',
            'data' => 'vendor.name',
            'name' => 'vendor.name']),
            'description',
            'commissionlimit'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission Limit (TON)',
            'data' => 'commissionlimit',
            'name' => 'commissionbyvendors.commissionlimit']),
            'commissionpercentage'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission %',
            'data' => 'commissionpercentage',
            'name' => 'commissionbyvendors.commissionpercentage']),
            'status',
            'remark'=> new \Yajra\DataTables\Html\Column(['title' => 'Remark',
            'data' => 'remark',
            'name' => 'commissionbyvendors.remark']),
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
        return 'commission_by_vendors_datatable_' . time();
    }
}
