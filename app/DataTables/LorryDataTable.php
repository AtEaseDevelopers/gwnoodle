<?php

namespace App\DataTables;

use App\Models\Lorry;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class LorryDataTable extends DataTable
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

        return $dataTable->addColumn('action', 'lorries.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Lorry $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Lorry $model)
    {
        return $model->newQuery()
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "tyrenextdate" from servicedetails sd where sd.type = "Tyre" order by nextdate desc limit 1)tyre'), function($join)
        {
            $join->on('lorrys.id', '=', 'tyre.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "insurancenextdate" from servicedetails sd where sd.type = "Insurance" order by nextdate desc limit 1)insurance'), function($join)
        {
            $join->on('lorrys.id', '=', 'insurance.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "permitnextdate" from servicedetails sd where sd.type = "Permit" order by nextdate desc limit 1)permit'), function($join)
        {
            $join->on('lorrys.id', '=', 'permit.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "roadtaxnextdate" from servicedetails sd where sd.type = "Road Tax" order by nextdate desc limit 1)roadtax'), function($join)
        {
            $join->on('lorrys.id', '=', 'roadtax.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "inspectionnextdate" from servicedetails sd where sd.type = "Inspection" order by nextdate desc limit 1)inspection'), function($join)
        {
            $join->on('lorrys.id', '=', 'inspection.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "othernextdate" from servicedetails sd where sd.type = "Other" order by nextdate desc limit 1)other'), function($join)
        {
            $join->on('lorrys.id', '=', 'other.lorry_id');
        })
        ->leftJoin(DB::raw('(select sd.lorry_id, DATE_FORMAT(sd.nextdate,"%d-%m-%Y") as "fireextinguishernextdate" from servicedetails sd where sd.type = "Fire Extinguisher" order by nextdate desc limit 1)fireextinguisher'), function($join)
        {
            $join->on('lorrys.id', '=', 'fireextinguisher.lorry_id');
        })
        ->select('lorrys.id','lorrys.lorryno','lorrys.status','lorrys.remark'
        ,DB::raw('concat(lorrys.id,":",coalesce(tyre.tyrenextdate,"NAN")) as tyrenextdateshow'),DB::raw('tyre.tyrenextdate as tyrenextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(insurance.insurancenextdate,"NAN")) as insurancenextdateshow'),DB::raw('insurance.insurancenextdate as insurancenextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(permit.permitnextdate,"NAN")) as permitnextdateshow'),DB::raw('permit.permitnextdate as permitnextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(roadtax.roadtaxnextdate,"NAN")) as roadtaxnextdateshow'),DB::raw('roadtax.roadtaxnextdate as roadtaxnextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(inspection.inspectionnextdate,"NAN")) as inspectionnextdateshow'),DB::raw('inspection.inspectionnextdate as inspectionnextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(other.othernextdate,"NAN")) as othernextdateshow'),DB::raw('other.othernextdate as othernextdate')
        ,DB::raw('concat(lorrys.id,":",coalesce(fireextinguisher.fireextinguishernextdate,"NAN")) as fireextinguishernextdateshow'),DB::raw('fireextinguisher.fireextinguishernextdate as fireextinguishernextdate'))
        ->groupby('lorrys.id','lorrys.lorryno','lorrys.status','lorrys.remark'
        ,'tyre.tyrenextdate','insurance.insurancenextdate','permit.permitnextdate','roadtax.roadtaxnextdate','inspection.inspectionnextdate','other.othernextdate','fireextinguisher.fireextinguishernextdate');
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
                    // [
                    //     'targets' => 3,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 4,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    // [
                    //     'targets' => 5,
                    //     'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                    //     ,'className' => 'dt-body-right'
                    // ],
                    [
                        'targets' => 2,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'TyreService\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 3,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'InsuranceList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 4,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'PermitList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 5,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'RoadTaxList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'InspectionList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 7,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'OtherList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                        'targets' => 8,
                        'render' => 'function(data, type){return "<a href=\'#\' class=\'FireExtinguisherList\' lorrykey=\'"+data.split(":")[0]+"\'>"+data.split(":")[1]+"</a>";}'
                    ],
                    [
                    'targets' => 9,
                    'render' => 'function(data, type){return data == 1 ? "Active" : "Inactive";}'],
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

            'lorryno'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.lorry_no'),
            'data' => 'lorryno',
            'name' => 'lorryno']),

            // 'type'=> new \Yajra\DataTables\Html\Column(['title' => 'Group',
            // 'data' => 'type',
            // 'name' => 'type']),

            // 'weightagelimit'=> new \Yajra\DataTables\Html\Column(['title' => 'Weightage Limit (TON)',
            // 'data' => 'weightagelimit',
            // 'name' => 'weightagelimit']),

            // 'commissionlimit'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission Limit (TON)',
            // 'data' => 'commissionlimit',
            // 'name' => 'commissionlimit']),

            // 'commissionpercentage'=> new \Yajra\DataTables\Html\Column(['title' => 'Commission %',
            // 'data' => 'commissionpercentage',
            // 'name' => 'commissionpercentage']),

            // 'permitholder'=> new \Yajra\DataTables\Html\Column(['title' => 'Permit Holder',
            // 'data' => 'permitholder',
            // 'name' => 'permitholder']),

            'tyre'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.tyre_next_date'),
            'data' => 'tyrenextdateshow',
            'name' => 'tyrenextdate']),

            'insurance'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.insurance_next_date'),
            'data' => 'insurancenextdateshow',
            'name' => 'insurancenextdate']),

            'permit'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.permit_next_date'),
            'data' => 'permitnextdateshow',
            'name' => 'permitnextdate']),

            'roadtax'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.road_tax_next_date'),
            'data' => 'roadtaxnextdateshow',
            'name' => 'roadtaxnextdate']),

            'inspection'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.inspection_next_date'),
            'data' => 'inspectionnextdateshow',
            'name' => 'inspectionnextdate']),

            'other'=> new \Yajra\DataTables\Html\Column(['title' =>  trans('lorries.other_next_date'),
            'data' => 'othernextdateshow',
            'name' => 'othernextdate']),

            'fireextinguisher'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.fire_extinguisher'),
            'data' => 'fireextinguishernextdateshow',
            'name' => 'fireextinguisher']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.status'),
            'data' => 'status',
            'name' => 'status']),

            'remark'=> new \Yajra\DataTables\Html\Column(['title' => trans('lorries.remark'),
            'data' => 'remark',
            'name' => 'remark']),

            
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
        return 'lorries_datatable_' . time();
    }
}
