<?php

namespace App\DataTables;

use App\Models\StockInRequest;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class StockInRequestDataTable extends DataTable
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
            ->addColumn('checkbox', function ($stockInRequest) {
                // Only pending rows can be actioned in bulk, and only by an approver.
                if ($this->isApprover() && $stockInRequest->isPending()) {
                    return '<input type="checkbox" class="checkboxselect" checkboxid="' . $stockInRequest->id . '"/>';
                }
                return '';
            })
            ->addColumn('action', function ($stockInRequest) {
                return view('stock_in_requests.datatables_actions', ['request' => $stockInRequest])->render();
            })
            ->editColumn('created_at', function ($stockInRequest) {
                return $stockInRequest->created_at ? $stockInRequest->created_at->format('d-m-Y H:i:s') : '-';
            })
            ->addColumn('product_name', function ($stockInRequest) {
                return $stockInRequest->product ? e($stockInRequest->product->name) : '-';
            })
            ->addColumn('batch_code', function ($stockInRequest) {
                return $stockInRequest->batch ? e($stockInRequest->batch->batch_code) : '-';
            })
            ->addColumn('warehouse_name', function ($stockInRequest) {
                if ($stockInRequest->warehouse) {
                    return '<span class="badge badge-info">' . e($stockInRequest->warehouse->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->editColumn('quantity', function ($stockInRequest) {
                $qty = '<span class="font-weight-bold">' . number_format($stockInRequest->quantity) . '</span>';
                if ((int) $stockInRequest->quantity !== (int) $stockInRequest->requested_quantity) {
                    $qty .= ' <small class="text-muted"><del>' . number_format($stockInRequest->requested_quantity) . '</del></small>';
                }
                return $qty;
            })
            ->editColumn('status', function ($stockInRequest) {
                switch ((int) $stockInRequest->status) {
                    case StockInRequest::STATUS_APPROVED:
                        return '<span class="badge badge-success">Approved</span>';
                    case StockInRequest::STATUS_REJECTED:
                        $badge = '<span class="badge badge-danger">Rejected</span>';
                        if ($stockInRequest->approval_remark) {
                            $badge .= '<br><small class="text-muted">' . e($stockInRequest->approval_remark) . '</small>';
                        }
                        return $badge;
                    default:
                        return '<span class="badge badge-warning">Pending</span>';
                }
            })
            ->rawColumns(['checkbox', 'action', 'warehouse_name', 'quantity', 'status']);
    }

    /**
     * Whether the current user may approve/reject (drives the bulk checkbox column).
     */
    protected function isApprover()
    {
        return Auth::check() && Auth::user()->hasRole('admin');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\StockInRequest $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(StockInRequest $model)
    {
        return $model->newQuery()
            ->with([
                'product:id,name',
                'batch:id,batch_code',
                'warehouse:id,name',
            ])
            ->select('stock_in_requests.*')
            ->orderBy('stock_in_requests.created_at', 'desc');
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
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                // Order by Date; its index shifts by one when the checkbox column is shown.
                'order'     => [[$this->isApprover() ? 1 : 0, 'desc']],
                'lengthMenu' => [[10, 50, 100, 300], ['10 rows', '50 rows', '100 rows', '300 rows']],
                'buttons' => [
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
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
                        'className' => 'text-center',
                        'orderable' => false,
                        'searchable' => false,
                    ],
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
        $columns = [];

        // Bulk-selection column, shown only to approvers.
        if ($this->isApprover()) {
            $columns['checkbox'] = new \Yajra\DataTables\Html\Column([
                'title' => '<input type="checkbox" id="selectallcheckbox">',
                'data' => 'checkbox',
                'name' => 'checkbox',
                'width' => '30px',
                'className' => 'text-center',
                'orderable' => false,
                'searchable' => false,
            ]);
        }

        return $columns + [
            'created_at' => new \Yajra\DataTables\Html\Column([
                'title' => 'Date',
                'data' => 'created_at',
                'name' => 'created_at',
                'width' => '150px',
                'className' => 'text-left'
            ]),

            'product_name' => new \Yajra\DataTables\Html\Column([
                'title' => 'Product',
                'data' => 'product_name',
                'name' => 'product.name',
                'defaultContent' => '-',
                'width' => '200px',
                'className' => 'text-left'
            ]),

            'batch_code' => new \Yajra\DataTables\Html\Column([
                'title' => 'Batch Code',
                'data' => 'batch_code',
                'name' => 'batch.batch_code',
                'defaultContent' => '-',
                'width' => '150px',
                'className' => 'text-left'
            ]),

            'warehouse_name' => new \Yajra\DataTables\Html\Column([
                'title' => 'Warehouse',
                'data' => 'warehouse_name',
                'name' => 'warehouse.name',
                'defaultContent' => '-',
                'width' => '150px',
                'className' => 'text-center'
            ]),

            'quantity' => new \Yajra\DataTables\Html\Column([
                'title' => 'Quantity',
                'data' => 'quantity',
                'name' => 'quantity',
                'width' => '110px',
                'className' => 'text-center'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'width' => '120px',
                'className' => 'text-center'
            ]),

            'requested_by' => new \Yajra\DataTables\Html\Column([
                'title' => 'Requested By',
                'data' => 'requested_by',
                'name' => 'requested_by',
                'defaultContent' => '-',
                'width' => '130px',
                'className' => 'text-left'
            ]),

            'reviewed_by' => new \Yajra\DataTables\Html\Column([
                'title' => 'Reviewed By',
                'data' => 'reviewed_by',
                'name' => 'reviewed_by',
                'defaultContent' => '-',
                'width' => '130px',
                'className' => 'text-left'
            ]),

            'action' => new \Yajra\DataTables\Html\Column([
                'title' => 'Actions',
                'data' => 'action',
                'name' => 'action',
                'width' => '120px',
                'className' => 'text-center',
                'orderable' => false,
                'searchable' => false
            ])
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'stock_in_requests_' . date('Y-m-d_H-i-s');
    }
}
