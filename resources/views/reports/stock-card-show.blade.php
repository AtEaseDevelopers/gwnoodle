@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ $report->name }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>{{ $report->name }}</strong>
                            <a href="{{ route('reports.index') }}" class="btn btn-light">Back</a>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.run') }}" accept-charset="UTF-8" id="reportForm">
                                @csrf
                                <input type="hidden" name="_report_id" value="{{ $report->id }}">
                                <input type="hidden" name="_report_type" value="STOCK_CARD_REPORT">
                                
                                <div class="row">
                                    <!-- Product Selection (Single Select) with unit_code -->
                                    <div class="form-group col-sm-6">
                                        <label for="product_id">Product <span class="asterisk">*</span>:</label>
                                        <select required class="form-control selectpicker" id="product_id" name="product_id" tabindex="null" data-live-search="true">
                                            <option value="">Select a product...</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->unit_code }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Product Batch Selection (Multiselect) - Loads based on selected product -->
                                    <div class="form-group col-sm-6">
                                        <label for="batch_id">Product Batch <span class="asterisk">*</span>:</label>
                                        <select class="form-control selectpicker" id="batch_id" name="batch_id[]" tabindex="null" data-live-search="true" multiple disabled>
                                            <option value="">Please select a product first</option>
                                        </select>
                                        <small class="form-text text-muted">Select specific batches to filter. Leave empty to show all batches.</small>
                                    </div>

                                    <!-- Date From -->
                                    <div class="form-group col-sm-6">
                                        <label for="datefrom">Date From <span class="asterisk">*</span>:</label>
                                        <input required class="form-control reportdate" id="datefrom" name="datefrom" type="text" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                    </div>

                                    <!-- Date To -->
                                    <div class="form-group col-sm-6">
                                        <label for="dateto">Date To <span class="asterisk">*</span>:</label>
                                        <input required class="form-control reportdate" id="dateto" name="dateto" type="text" value="{{ date('Y-m-d') }}">
                                    </div>

                                    
                                </div>

                                <div class="form-group col-sm-12">
                                    <input class="btn btn-primary" type="submit" value="Run Report" formtarget="_blank">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            HideLoad();
            // Initialize date pickers
            $('.reportdate').datetimepicker({
                format: 'YYYY-MM-DD',
                useCurrent: true,
                icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
                },
                sideBySide: true
            });

            // Initialize selectpickers
            $('.selectpicker').selectpicker({
                liveSearch: true,
                noneSelectedText: 'Nothing selected'
            });

            // When product is selected, load batches via AJAX
            $('#product_id').on('change', function() {
                var productId = $(this).val();
                var $batchSelect = $('#batch_id');
                
                if (productId) {
                    // Show loading state
                    $batchSelect.prop('disabled', true);
                    $batchSelect.html('<option value="">Loading batches...</option>');
                    $batchSelect.selectpicker('refresh');
                    
                    // Make AJAX request to get batches
                    $.ajax({
                        url: '{{ route("reports.getProductBatches") }}',
                        type: 'GET',
                        data: { product_id: productId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.batches.length > 0) {
                                var options = '<option value="">All Batches</option>';
                                $.each(response.batches, function(index, batch) {
                                    var expiryText = batch.expiry_date ? ' (Exp: ' + batch.expiry_date + ')' : '';
                                    var quantityText = ' - Qty: ' + batch.quantity;
                                    options += '<option value="' + batch.id + '">' + batch.batch_code + expiryText + quantityText + '</option>';
                                });
                                $batchSelect.html(options);
                                $batchSelect.prop('disabled', false);
                                $batchSelect.selectpicker('refresh');
                            } else {
                                $batchSelect.html('<option value="">No batches available for this product</option>');
                                $batchSelect.prop('disabled', false);
                                $batchSelect.selectpicker('refresh');
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading batches:', xhr);
                            $batchSelect.html('<option value="">Error loading batches</option>');
                            $batchSelect.prop('disabled', false);
                            $batchSelect.selectpicker('refresh');
                        }
                    });
                } else {
                    // Reset batch select if no product selected
                    $batchSelect.html('<option value="">Please select a product first</option>');
                    $batchSelect.prop('disabled', true);
                    $batchSelect.selectpicker('refresh');
                }
            });
        });

        // Form submission handler
        $('#reportForm').on('submit', function() {
            // Ensure empty multiselect sends empty value
            var $batchSelect = $('#batch_id');
            if ($batchSelect.val() === null || $batchSelect.val().length === 0) {
                $batchSelect.prop('disabled', true);
                $(this).append('<input type="hidden" name="batch_id" value="">');
            }
            
            // Handle warehouse multiselect
            var $warehouseSelect = $('#warehouse_id');
            if ($warehouseSelect.val() === null || $warehouseSelect.val().length === 0 || $warehouseSelect.val() === '') {
                $warehouseSelect.prop('disabled', true);
                $(this).append('<input type="hidden" name="warehouse_id" value="">');
            }
        });
    </script>
@endpush