@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Batch Details: {{ $productBatch->batch_code }}</h3>
            <div class="card-tools">
                <a href="{{ route('productBatches.edit', ['id' => Crypt::encrypt($productBatch->id)]) }}" 
                   class="btn btn-info btn-sm">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('productBatches.stock-in-form', ['id' => Crypt::encrypt($productBatch->id)]) }}" 
                   class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Stock In
                </a>
                <a href="{{ route('productBatches.stock-out-form', ['id' => Crypt::encrypt($productBatch->id)]) }}" 
                   class="btn btn-warning btn-sm">
                    <i class="fa fa-minus"></i> Stock Out
                </a>
                <a href="{{ route('productBatches.print-label', ['id' => Crypt::encrypt($productBatch->id)]) }}" 
                   class="btn btn-primary btn-sm" target="_blank">
                    <i class="fa fa-print"></i> Print Label
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fa fa-cube"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Product</span>
                            <span class="info-box-number">{{ $productBatch->product->name ?? 'N/A' }}</span>
                            <small>{{ $productBatch->product->unit_code ?? '' }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fa fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Expiry Date</span>
                            <span class="info-box-number">{{ $productBatch->expiry_date->format('d-m-Y') }}</span>
                            @if($productBatch->isExpired())
                                <span class="badge badge-danger">Expired</span>
                            @else
                                @php
                                    $daysToExpiry = now()->diffInDays($productBatch->expiry_date, false);
                                @endphp
                                @if($daysToExpiry <= 30)
                                    <span class="badge badge-warning">{{ $daysToExpiry }} days left</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fa fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Initial Stock</span>
                            <span class="info-box-number">{{ number_format($productBatch->initial_quantity) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fa fa-bar-chart"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Current Stock</span>
                            <span class="info-box-number">{{ number_format($productBatch->quantity) }}</span>
                            @if($productBatch->quantity == 0)
                                <span class="badge badge-secondary">Depleted</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch Information Table -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Batch Information</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Batch Code</th>
                                    <td>{{ $productBatch->batch_code }}</td>
                                </tr>
                                <tr>
                                    <th>Product</th>
                                    <td>
                                        {{ $productBatch->product->name ?? 'N/A' }}
                                        <br>
                                        <small>Code: {{ $productBatch->product->code ?? 'N/A' }}</small>
                                        <br>
                                        <small>Unit: {{ $productBatch->product->unit_code ?? 'N/A' }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Manufacturing Date</th>
                                    <td>{{ $productBatch->manufacturing_date ? $productBatch->manufacturing_date->format('d-m-Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Expiry Date</th>
                                    <td>
                                        {{ $productBatch->expiry_date->format('d-m-Y') }}
                                        @if($productBatch->isExpired())
                                            <span class="badge badge-danger ml-2">Expired</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @switch($productBatch->status)
                                            @case(1)
                                                <span class="badge badge-success">Active</span>
                                                @break
                                            @case(2)
                                                <span class="badge badge-danger">Expired</span>
                                                @break
                                            @case(3)
                                                <span class="badge badge-warning">Depleted</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">Unknown</span>
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Stock Information</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Initial Quantity</th>
                                    <td>{{ number_format($productBatch->initial_quantity) }} units</td>
                                </tr>
                                <tr>
                                    <th>Current Quantity</th>
                                    <td>{{ number_format($productBatch->quantity) }} units</td>
                                </tr>
                                <tr>
                                    <th>Sold Quantity</th>
                                    <td>{{ number_format($productBatch->initial_quantity - $productBatch->quantity) }} units</td>
                                </tr>
                                <tr>
                                    <th>Usage Rate</th>
                                    <td>
                                        @if($productBatch->initial_quantity > 0)
                                            @php
                                                $usageRate = (($productBatch->initial_quantity - $productBatch->quantity) / $productBatch->initial_quantity) * 100;
                                            @endphp
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $usageRate }}%" 
                                                     aria-valuenow="{{ $usageRate }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ number_format($usageRate, 1) }}%
                                                </div>
                                            </div>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>QR Code</th>
                                    <td>
                                        @if($productBatch->qr_code)
                                            <code>{{ $productBatch->qr_code }}</code>
                                            <button class="btn btn-sm btn-link" onclick="copyToClipboard('{{ $productBatch->qr_code }}')">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code Display (if exists) -->
            @if($productBatch->qr_code)
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">QR Code</h4>
                        </div>
                        <div class="card-body text-center">
                            {!! QrCode::size(200)->generate($productBatch->qr_code) !!}
                            <p class="mt-2"><code>{{ $productBatch->qr_code }}</code></p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Transactions History -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Transaction History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Remark</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions ?? [] as $transaction)
                                        <tr>
                                            <td>{{ $transaction->date->format('d-m-Y H:i:s') }}</td>
                                            <td>
                                                @if($transaction->type == 1)
                                                    <span class="badge badge-success">Stock In</span>
                                                @else
                                                    <span class="badge badge-warning">Stock Out</span>
                                                @endif
                                            </td>
                                            <td class="{{ $transaction->type == 1 ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction->type == 1 ? '+' : '-' }}{{ number_format(abs($transaction->quantity)) }}
                                            </td>
                                            <td>{{ $transaction->remark ?? '-' }}</td>
                                            <td>{{ $transaction->user ?? 'system' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No transactions found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('QR Code copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });

        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush