@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product Details: {{ $product->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('products.edit', ['product' => Crypt::encrypt($product->id)]) }}"
                   class="btn btn-info btn-sm">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-list"></i> Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Basic Information Row -->
            <div class="row">
                <div class="col-md-12">
                    <h4>Basic Information</h4>
                    <hr>
                </div>
            </div>
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">{{ __('products.code') }}</th>
                            <td>{{ $product->unit_code }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('products.name') }}</th>
                            <td>{{ $product->name }}</td>
                        </tr>
                        <tr>
                            <th>Unit Code</th>
                            <td>{{ $product->unit_code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('products.type') }}</th>
                            <td>
                                @switch($product->type)
                                    @case(1)
                                        {{ __('products.type_coffee') }}
                                        @break
                                    @case(2)
                                        {{ __('products.type_tea') }}
                                        @break
                                    @case(3)
                                        {{ __('products.type_cocoa') }}
                                        @break
                                    @case(4)
                                        {{ __('products.type_ice') }}
                                        @break
                                    @default
                                        {{ __('products.type_other') }}
                                @endswitch
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Right Column -->
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">{{ __('products.status') }}</th>
                            <td>
                                @if($product->status == 1)
                                    <span class="badge badge-success">{{ __('products.active') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('products.unactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Selling Price</th>
                            <td><strong class="text-primary">{{ number_format($product->price, 3) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Cost Price</th>
                            <td>
                                <strong class="text-info">{{ number_format($product->cost, 2) }}</strong>
                                @if($product->cost)
                                    <small class="text-muted">(Profit: {{ number_format($product->profit_amount, 2) }} | {{ number_format($product->profit_margin, 2) }}%)</small>
                                @endif
                            </td>
                        </tr>
                        @einvoice
                        <tr>
                            <th>Classification Code</th>
                            <td>{{ $product->classification_code ? $product->classification_code . ' - ' . (App\Models\ClassificationCode::getDescription($product->classification_code) ?? '') : '-' }}</td>
                        </tr>
                        @endeinvoice
                    </table>
                </div>
            </div>

            <!-- Carton Information (if enabled) -->
            @if($product->carton_enabled)
            <div class="row mt-3">
                <div class="col-md-12">
                    <h4>Carton Configuration</h4>
                    <hr>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Carton Enabled</th>
                            <td><span class="badge badge-success">Yes</span></td>
                        </tr>
                        <tr>
                            <th>Units Per Carton</th>
                            <td>{{ $product->units_per_carton }} units</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            <!-- Inventory Summary Cards -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Inventory Summary</h4>
                    <hr>
                </div>
                
                <div class="col-md-3">
                    <div class="info-box bg-info border border-dark">
                        <span class="info-box-icon"><i class="fa fa-cubes"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Stock</span>
                            <span class="info-box-number">{{ number_format($product->total_quantity) }}</span>
                            @if($product->carton_enabled)
                                <small>{{ $product->total_quantity_in_cartons['display'] }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-box bg-success border border-dark">
                        <span class="info-box-icon"><i class="fa fa-tags"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Active Batches</span>
                            <span class="info-box-number">{{ $product->active_batches_count }}</span>
                            <small>Total: {{ $product->total_batches_count }}</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-box bg-warning border border-dark">
                        <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Expiring Soon</span>
                            <span class="info-box-number">{{ number_format($product->expiring_soon_quantity) }}</span>
                            <small>Within 30 days</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-box bg-danger border border-dark">
                        <span class="info-box-icon"><i class="fa fa-dollar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Stock Value</span>
                            <span class="info-box-number">{{ number_format($product->current_stock_value, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Movement Summary -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="small-box bg-green border border-dark">
                        <div class="inner">
                            <h3>{{ number_format($product->total_stock_in) }}</h3>
                            <p>Total Stock In (All Time)
                                <i class="fa fa-arrow-down"></i>
                            </p>
                            
                        </div>
                        
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small-box bg-yellow border border-dark">
                        <div class="inner">
                            <h3>{{ number_format($product->total_stock_out) }}</h3>
                            <p>Total Stock Out (All Time)
                               <i class="fa fa-arrow-up"></i>
                            </p>
                        </div>
                       
                    </div>
                </div>
            </div>

            <!-- Batch Details Table -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Batch Details</h4>
                    <hr>
                    
                    @if($product->batches->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Batch Code</th>
                                        <th>Expiry Date</th>
                                        <th>Current Qty</th>
                                        <th>Usage</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->batch_summary as $batch)
                                    <tr>
                                        <td>
                                            <strong>{{ $batch['batch_code'] }}</strong>
                                        </td>
                                        <td>
                                            {{ $batch['expiry_date'] }}
                                            @php
                                                $daysToExpiry = $batch['days_to_expiry'];
                                            @endphp
                                            @if($daysToExpiry <= 30 && !$batch['is_expired'] && $daysToExpiry > 0)
                                                <span class="badge badge-warning">{{ $daysToExpiry }} days left</span>
                                            @elseif($batch['is_expired'])
                                                <span class="badge badge-danger">Expired</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($batch['quantity']) }}</td>
                                        
                                        <td>
                                            @if($batch['is_expired'])
                                                <span class="badge badge-danger">Expired</span>
                                            @else
                                                @switch($batch['status'])
                                                    @case(1)
                                                        <span class="badge badge-success">Active</span>
                                                        @break
                                                    @case(2)
                                                        <span class="badge badge-danger">Expired</span>
                                                        @break
                                                    @case(3)
                                                        <span class="badge badge-warning">Inactive</span>
                                                        @break
                                                    
                                                @endswitch
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('productBatches.show', ['id' => Crypt::encrypt($batch['id'])]) }}" 
                                               class="btn btn-sm btn-info" title="View Batch">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="2" class="text-right">Totals:</th>
                                        <th class="text-right">{{ number_format($product->total_quantity) }}</th>
                                        <th colspan="4"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> No batches found for this product.
                            <a href="{{ route('productBatches.create') }}?product_id={{ $product->id }}" class="alert-link">Create a new batch</a>.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Available Batches for Sales -->
            @if($product->available_batches->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Available Batches (FEFO - First Expiry First Out)</h4>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Batch Code</th>
                                    <th>Expiry Date</th>
                                    <th>Available Quantity</th>
                                    <th>Days to Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->available_batches as $batch)
                                <tr>
                                    <td>{{ $batch['batch_code'] }}</td>
                                    <td>{{ $batch['expiry_date'] }}</td>
                                    <td class="text-right">{{ number_format($batch['available_quantity']) }}</td>
                                    <td>
                                        @if($batch['days_to_expiry'] <= 7)
                                            <span class="badge badge-danger">{{ $batch['days_to_expiry'] }} days</span>
                                        @elseif($batch['days_to_expiry'] <= 30)
                                            <span class="badge badge-warning">{{ $batch['days_to_expiry'] }} days</span>
                                        @else
                                            <span class="badge badge-success">{{ $batch['days_to_expiry'] }} days</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Cost History -->
            @if(isset($costHistory) && $costHistory->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Cost History</h4>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Old Cost</th>
                                    <th>New Cost</th>
                                    <th>Changed By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costHistory as $history)
                                <tr>
                                    <td>{{ $history->created_at->format('d-m-Y H:i:s') }}</td>
                                    <td>{{ $history->old_cost ? number_format($history->old_cost, 2) : '-' }}</td>
                                    <td><strong>{{ number_format($history->cost, 2) }}</strong></td>
                                    <td>{{ $history->changer->name ?? 'System' }}</td>
                                    <td>{{ $history->remarks ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Transactions (if available) -->
            @if($product->inventoryTransactions()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Recent Transactions</h4>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Batch</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->inventoryTransactions()->with('batch')->latest()->take(10)->get() as $transaction)
                                <tr>
                                    <td>{{ $transaction->date->format('d-m-Y H:i') }}</td>
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
                                    <td>{{ $transaction->batch->batch_code ?? 'N/A' }}</td>
                                    <td>{{ $transaction->remark ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Created: {{ $product->created_at->format('d-m-Y H:i:s') }}</small>
                </div>
                <div class="col-md-6 text-right">
                    <small class="text-muted">Last Updated: {{ $product->updated_at->format('d-m-Y H:i:s') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                window.location.href = '{{ route("products.index") }}';
            }
        });
        
        $(document).ready(function () {
            HideLoad();
            
            // Add tooltips
            $('[title]').tooltip();
        });
    </script>
@endpush