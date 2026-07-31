@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('warehouses.index') }}">Warehouses</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Warehouse Details</strong>
                            <a href="{{ route('warehouses.index') }}" class="btn btn-light float-right">Back</a>
                        </div>
                        <div class="card-body">
                            @include('warehouses.show_fields')
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Section -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Inventory Summary</strong>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fa fa-cubes"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Products</span>
                                            <span class="info-box-number">{{ $totalProducts }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fa fa-tags"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Batches</span>
                                            <span class="info-box-number">{{ $totalBatches }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fa fa-calculator"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Quantity</span>
                                            <span class="info-box-number">{{ number_format($totalQuantity) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($warehouse->inventoryBalances->count() > 0)
                                <h5>Inventory Details</h5>
                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <input type="text" id="inventoryFilter" class="form-control form-control-sm" placeholder="Filter by product or batch code...">
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table id="inventoryTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Batch Code</th>
                                                <th>Quantity</th>
                                                <th>Expiry Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($warehouse->inventoryBalances as $index => $inventory)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        @if($inventory->product)
                                                            {{ $inventory->product->unit_code }} ({{ $inventory->product->name }})
                                                        @else
                                                            Unknown
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>{{ $inventory->batch->batch_code ?? 'Unknown' }}</strong>
                                                    </td>
                                                    <td class="text-center">{{ number_format($inventory->quantity) }}</td>
                                                    <td>
                                                        @if($inventory->batch && $inventory->batch->expiry_date)
                                                            <span class="{{ $inventory->batch->isExpiringSoon() ? 'text-danger' : '' }}">
                                                                {{ $inventory->batch->formatted_expiry_date }}
                                                                @if($inventory->batch->isExpiringSoon())
                                                                    <span class="badge badge-warning">Expiring Soon</span>
                                                                @endif
                                                            </span>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($inventory->quantity > 0)
                                                            <span class="badge badge-success">In Stock</span>
                                                        @else
                                                            <span class="badge badge-secondary">Out of Stock</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <th colspan="3" class="text-right">Total:</th>
                                                <th class="text-center">{{ number_format($totalQuantity) }}</th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No inventory found for this warehouse.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .info-box {
            display: flex;
            align-items: center;
            min-height: 100px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .info-box-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 100%;
            font-size: 30px;
            color: #fff;
            border-radius: 5px 0 0 5px;
        }
        .bg-info .info-box-icon { background-color: #17a2b8; }
        .bg-success .info-box-icon { background-color: #28a745; }
        .bg-warning .info-box-icon { background-color: #ffc107; }
        .info-box-content {
            padding: 15px;
            flex: 1;
        }
        .info-box-text {
            display: block;
            font-size: 14px;
            color: #6c757d;
        }
        .info-box-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
@endpush

@push('scripts')
<script>
    document.getElementById('inventoryFilter').addEventListener('input', function () {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll('#inventoryTable tbody tr');
        rows.forEach(function (row) {
            var product = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            var batch = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
            row.style.display = (product.includes(filter) || batch.includes(filter)) ? '' : 'none';
        });
    });
</script>
@endpush