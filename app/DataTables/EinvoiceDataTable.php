<?php

namespace App\DataTables;

use App\Models\Einvoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class EinvoiceDataTable extends DataTable
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

        return $dataTable
            ->addColumn('action', function ($einvoice) {
                return view('einvoices.datatables_actions', ['einvoice' => $einvoice])->render();
            })
            ->editColumn('status', function ($einvoice) {
                $badge = $einvoice->status === 'Valid' ? 'success' : 'danger';
                return '<span class="badge badge-'.$badge.'">'.$einvoice->status.'</span>';
            })
            ->editColumn('submission_date', function ($einvoice) {
                return $einvoice->submission_date ? $einvoice->submission_date->format('d-m-Y H:i:s') : '-';
            })
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Einvoice $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Einvoice $model)
    {
        return $model->newQuery()
            ->with('invoice.customer')
            ->select('einvoices.*');
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
                    ['extend' => 'excelHtml5','text'=>'<i class="fa fa-file-excel-o"></i> Excel','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'einvoice'.date('dmYHis')],
                    ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'LEGAL','text'=>'<i class="fa fa-file-pdf-o"></i> PDF','exportOptions'=> ['columns'=>':visible:not(:last-child)'], 'className' => 'btn btn-default btn-sm no-corner','title'=>null,'filename'=>'einvoice'.date('dmYHis')],
                    ['extend' => 'colvis', 'className' => 'btn btn-default btn-sm no-corner','text'=>'<i class="fa fa-columns"></i> Column',],
                    ['extend' => 'pageLength','className' => 'btn btn-default btn-sm no-corner',],
                ],
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
            'sku'=> new \Yajra\DataTables\Html\Column(['title' => 'SKU',
            'data' => 'sku',
            'name' => 'einvoices.sku']),

            'customer'=> new \Yajra\DataTables\Html\Column(['title' => 'Customer',
            'data' => 'invoice.customer.company',
            'name' => 'customer.company',
            'orderable' => false]),

            'currency'=> new \Yajra\DataTables\Html\Column(['title' => 'Currency',
            'data' => 'currency',
            'name' => 'einvoices.currency']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => 'Status',
            'data' => 'status',
            'name' => 'einvoices.status']),

            'submission_date'=> new \Yajra\DataTables\Html\Column(['title' => 'Submission Date',
            'data' => 'submission_date',
            'name' => 'einvoices.submission_date']),

            'validated_time'=> new \Yajra\DataTables\Html\Column(['title' => 'Validated Time',
            'data' => 'validated_time',
            'name' => 'einvoices.validated_time']),

            'uuid'=> new \Yajra\DataTables\Html\Column(['title' => 'UUID',
            'data' => 'uuid',
            'name' => 'einvoices.uuid',
            'visible' => false]),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'einvoices_datatable_' . time();
    }
}

