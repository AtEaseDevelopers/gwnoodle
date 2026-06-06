@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.5rem;">Batch Details: {{ $productBatch->batch_code }}</h3>
            <div class="card-tools">
                <a href="{{ route('productBatches.edit', Crypt::encrypt($productBatch->id)) }}" 
                class="btn btn-info btn-sm" style="font-size: 0.9rem;">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('productBatches.print-label', Crypt::encrypt($productBatch->id)) }}" 
                   class="btn btn-primary btn-sm" style="font-size: 0.9rem;">
                    <i class="fa fa-print"></i> Download Barcode
                </a>
            </div>
        </div>
        <div class="card-body" style="font-size: 1rem;">
            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="info-box bg-info border border-dark">
                        <span class="info-box-icon" style="font-size: 2rem;"><i class="fa fa-cube"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text" style="font-size: 1rem;">Product</span>
                            <span class="info-box-number" style="font-size: 1.4rem; font-weight: bold;">{{ $productBatch->product->name ?? 'N/A' }}</span>
                            <small style="font-size: 0.9rem;">{{ $productBatch->product->unit_code ?? '' }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-success border border-dark">
                        <span class="info-box-icon" style="font-size: 2rem;"><i class="fa fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text" style="font-size: 1rem;">Expiry Date</span>
                            <span class="info-box-number" style="font-size: 1.4rem; font-weight: bold;">{{ $productBatch->expiry_date }}</span>
                            @if($productBatch->isExpired())
                                <span class="badge badge-danger" style="font-size: 0.9rem;">Expired</span>
                            @else
                                @php
                                    $daysToExpiry = now()->diffInDays($productBatch->expiry_date, false);
                                @endphp
                                @if($daysToExpiry <= 30)
                                    <span class="badge badge-warning" style="font-size: 0.9rem;">{{ $daysToExpiry }} days left</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-danger border border-dark">
                        <span class="info-box-icon" style="font-size: 2rem;"><i class="fa fa-bar-chart"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text" style="font-size: 1rem;">Current Stock</span>
                            <span class="info-box-number" style="font-size: 1.4rem; font-weight: bold;">{{ number_format($productBatch->quantity) }}</span>
                            @if($productBatch->quantity == 0)
                                <span class="badge badge-secondary" style="font-size: 0.9rem;">Inactive</span>
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
                            <h4 class="card-title" style="font-size: 1.3rem;">Batch Information</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered" style="font-size: 1rem;">
                                <tr>
                                    <th width="40%" style="font-size: 1rem;">Batch Code</th>
                                    <td style="font-size: 1rem; font-weight: 500;">{{ $productBatch->batch_code }}</td>
                                </tr>
                                <tr>
                                    <th style="font-size: 1rem;">Product</th>
                                    <td style="font-size: 1rem;">
                                        <span style="font-size: 1.1rem;">{{ $productBatch->product->name ?? 'N/A' }}</span>
                                        <br>
                                        <small style="font-size: 0.9rem;">Unit Code : {{ $productBatch->product->unit_code ?? 'N/A' }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="font-size: 1rem;">Expiry Date</th>
                                    <td style="font-size: 1rem;">
                                        <span style="font-size: 1.1rem;">{{ $productBatch->expiry_date }}</span>
                                        @if($productBatch->isExpired())
                                            <span class="badge badge-danger ml-2" style="font-size: 0.9rem;">Expired</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th style="font-size: 1rem;">Status</th>
                                    <td style="font-size: 1rem;">
                                        @switch($productBatch->status)
                                            @case(1)
                                                <span class="badge badge-success" style="font-size: 1rem;">Active</span>
                                                @break
                                            @case(2)
                                                <span class="badge badge-danger" style="font-size: 1rem;">Expired</span>
                                                @break
                                            @case(3)
                                                <span class="badge badge-warning" style="font-size: 1rem;">Inactive</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary" style="font-size: 1rem;">Unknown</span>
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
                            <h4 class="card-title" style="font-size: 1.3rem;">Stock Information</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered" style="font-size: 1rem;">
                                
                                <tr>
                                    <th style="font-size: 1rem;">Current Quantity</th>
                                    <td style="font-size: 1.1rem; font-weight: 500;">
                                        @if(auth()->user() && auth()->user()->hasRole('admin'))
                                            <span id="batchQtyEditable"
                                                  data-id="{{ Crypt::encrypt($productBatch->id) }}"
                                                  data-qty="{{ (int) $productBatch->quantity }}">
                                                <span class="qty-value">{{ number_format($productBatch->quantity) }}</span> units
                                                <a href="#" id="batchQtyEditBtn" class="ml-2 text-info" title="Edit quantity">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </span>
                                        @else
                                            {{ number_format($productBatch->quantity) }} units
                                        @endif
                                    </td>
                                </tr>
                                
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Transactions History -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title" style="font-size: 1.3rem;">Transaction History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="font-size: 1.2rem;">
                                    <thead>
                                        <tr>
                                            <th style="font-size: 1rem;">Date</th>
                                            <th style="font-size: 1rem;">Type</th>
                                            <th style="font-size: 1rem;">Quantity</th>
                                            <th style="font-size: 1rem;">Remark</th>
                                            <th style="font-size: 1rem;">User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions ?? [] as $transaction)
                                        <tr>
                                            <td style="font-size: 1.2rem;">{{ $transaction->date->format('d-m-Y H:i:s') }}</td>
                                            <td>
                                                @if($transaction->type == 1)
                                                    <span class="badge badge-success" style="font-size: 0.9rem;">Stock In</span>
                                                @elseif($transaction->type == 2)
                                                    <span class="badge badge-warning" style="font-size: 0.9rem;">Stock Out</span>
                                                @elseif($transaction->type == 5)
                                                    <span class="badge badge-info" style="font-size: 0.9rem;">Adjustment</span>
                                                @else
                                                    <span class="badge badge-secondary" style="font-size: 0.9rem;">Other</span>
                                                @endif
                                            </td>
                                            <td class="{{ $transaction->quantity >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.2rem; font-weight: 500;">
                                                {{ $transaction->quantity >= 0 ? '+' : '' }}{{ number_format($transaction->quantity) }}
                                            </td>
                                            <td style="font-size: 1.2rem;">{{ $transaction->remark ?? '-' }}</td>
                                            <td style="font-size: 1.2rem;">{{ $transaction->user ?? 'system' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center" style="font-size: 1rem;">No transactions found</td>
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

            @if(auth()->user() && auth()->user()->hasRole('admin'))
            (function () {
                var $cell = $('#batchQtyEditable');
                if (!$cell.length) return;

                var adjustUrl = '{{ url('productBatches/adjust-stock') }}/' + encodeURIComponent($cell.attr('data-id'));
                var csrfToken = '{{ csrf_token() }}';
                var editing = false;

                function startEdit() {
                    if (editing) return;
                    editing = true;

                    var current = parseInt($cell.attr('data-qty'), 10) || 0;

                    var $input = $('<input type="number" min="0" step="1" class="form-control form-control-sm d-inline-block">')
                        .css({ width: '120px' })
                        .val(current);

                    $cell.html('').append($input).append(' units');
                    $input.trigger('focus').trigger('select');

                    var committed = false;

                    function restore(displayQty) {
                        var qty = (typeof displayQty === 'number') ? displayQty : current;
                        $cell.attr('data-qty', qty);
                        $cell.html(
                            '<span class="qty-value">' + Number(qty).toLocaleString() + '</span> units'
                            + ' <a href="#" id="batchQtyEditBtn" class="ml-2 text-info" title="Edit quantity">'
                            + '<i class="fa fa-pencil"></i></a>'
                        );
                        editing = false;
                    }

                    function commit() {
                        if (committed) return;
                        committed = true;

                        var raw = $input.val();
                        var newQty = parseInt(raw, 10);

                        if (raw === '' || isNaN(newQty) || newQty < 0) {
                            toastr.error('Enter a valid non-negative quantity.', 'Invalid quantity');
                            restore(current);
                            return;
                        }
                        if (newQty === current) {
                            restore(current);
                            return;
                        }

                        $input.prop('disabled', true);

                        $.ajax({
                            url: adjustUrl,
                            method: 'POST',
                            data: { _token: csrfToken, new_quantity: newQty },
                            dataType: 'json'
                        }).done(function (resp) {
                            if (resp && resp.success) {
                                toastr.success(resp.message || 'Stock updated.');
                                if (resp.no_change) {
                                    restore(current);
                                } else {
                                    setTimeout(function () { location.reload(); }, 400);
                                }
                            } else {
                                toastr.error((resp && resp.message) || 'Update failed.');
                                restore(current);
                            }
                        }).fail(function (xhr) {
                            var msg = 'Update failed.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            toastr.error(msg);
                            restore(current);
                        });
                    }

                    $input.on('keydown', function (ev) {
                        if (ev.key === 'Enter') {
                            ev.preventDefault();
                            commit();
                        } else if (ev.key === 'Escape') {
                            ev.preventDefault();
                            committed = true;
                            restore(current);
                        }
                    });

                    $input.on('blur', function () {
                        setTimeout(commit, 50);
                    });
                }

                $(document).on('click', '#batchQtyEditBtn', function (e) {
                    e.preventDefault();
                    startEdit();
                });
            })();
            @endif
        });
    </script>
@endpush

<style>
    /* Additional global font adjustments */
    .info-box {
        min-height: 100px;
    }
    .info-box-icon {
        line-height: 100px;
    }
    .badge {
        padding: 5px 8px;
    }
    .table td, .table th {
        padding: 12px;
    }
    .progress {
        margin-bottom: 0;
    }
    .card-title {
        margin-bottom: 0;
    }
</style>