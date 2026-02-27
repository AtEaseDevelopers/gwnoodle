<?php

namespace App\DataTables;

use App\Models\ConsolidatedEinvoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Crypt;

class ConsolidatedEinvoiceDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', function ($consolidatedEinvoice) {
                return view('consolidated_einvoices.datatables_actions', ['consolidatedEinvoice' => $consolidatedEinvoice])->render();
            })
            ->editColumn('status', function ($consolidatedEinvoice) {
                $badge = $consolidatedEinvoice->status === 'Valid' ? 'success' : 'danger';
                return '<span class="badge badge-'.$badge.'">'.$consolidatedEinvoice->status.'</span>';
            })
            ->editColumn('submission_date', function ($consolidatedEinvoice) {
                return $consolidatedEinvoice->submission_date ? $consolidatedEinvoice->submission_date->format('d-m-Y H:i:s') : '-';
            })
            ->rawColumns(['status', 'action']);
    }

    public function query(ConsolidatedEinvoice $model)
    {
        return $model->newQuery()
            ->select('consolidated_einvoices.*');
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '200px', 'printable' => false, 'exportable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[ 25, 50, -1 ],[ '25 rows', '50 rows', 'Show all' ]],
                'pageLength' => 25,
                'buttons'   => [
                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'consolidated_einvoice'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'consolidated_einvoice'.date('dmYHis')],
                    ['extend' => 'colvis', 'className' => 'btn btn-default btn-sm no-corner','text'=>'<i class="fa fa-columns"></i> Column',],
                    ['extend' => 'pageLength','className' => 'btn btn-default btn-sm no-corner',],
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            'sku'=> new \Yajra\DataTables\Html\Column(['title' => 'SKU',
            'data' => 'sku',
            'name' => 'consolidated_einvoices.sku']),

            'currency'=> new \Yajra\DataTables\Html\Column(['title' => 'Currency',
            'data' => 'currency',
            'name' => 'consolidated_einvoices.currency']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => 'Status',
            'data' => 'status',
            'name' => 'consolidated_einvoices.status']),

            'submission_date'=> new \Yajra\DataTables\Html\Column(['title' => 'Submission Date',
            'data' => 'submission_date',
            'name' => 'consolidated_einvoices.submission_date']),

            'validated_time'=> new \Yajra\DataTables\Html\Column(['title' => 'Validated Time',
            'data' => 'validated_time',
            'name' => 'consolidated_einvoices.validated_time']),

            'uuid'=> new \Yajra\DataTables\Html\Column(['title' => 'UUID',
            'data' => 'uuid',
            'name' => 'consolidated_einvoices.uuid',
            'visible' => false]),
        ];
    }

    protected function filename()
    {
        return 'consolidated_einvoices_datatable_' . time();
    }
}

