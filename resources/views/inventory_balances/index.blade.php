@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('inventory_balances.inventory_balances') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('inventory_balances.inventory_balances') }}
                            <button class="border-0 bg-transparent pull-right text-danger" data-toggle="modal" data-target="#stockout"><i class="fa fa-cart-arrow-down fa-lg"></i></button>
                            <button class="border-0 bg-transparent pull-right text-success pr-2" data-toggle="modal" data-target="#stockin"><i class="fa fa-cart-plus fa-lg"></i></button>
                        </div>
                        <div class="card-body">
                            @include('inventory_balances.table')
                            <div class="pull-right mr-3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock In Modal -->
    <div id="stockin" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('inventory_balances.stock_in') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    {!! Form::open(['route' => 'inventoryBalances.stockin', 'enctype' => 'multipart/form-data', 'id' => 'stockinForm']) !!}
                    
                    <!-- Lorry Multi-Select Dropdown -->
                    <div class="form-group">
                        <label for="lorry_id" class="col-form-label">{{ __('inventory_balances.lorry') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownLorryStockIn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('inventory_balances.select_lorry') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownLorryStockIn" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="lorrySearchStockIn" placeholder="Search Lorries...">
                                <div id="lorryListStockIn">
                                    @foreach($lorryItems as $lorryId => $lorryName)
                                        <div class="form-check ml-3">
                                            <input class="form-check-input lorry-checkbox" type="checkbox" name="lorry_id[]" value="{{ $lorryId }}" id="lorry_stockin_{{ $lorryId }}">
                                            <label class="form-check-label" for="lorry_stockin_{{ $lorryId }}">
                                                {{ $lorryName }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <small class="text-danger lorry-error" style="display: none;">Please select at least one lorry</small>
                    </div>

                    <!-- Product Single-Select Dropdown -->
                    <div class="form-group">
                        <label for="product_id" class="col-form-label">{{ __('inventory_balances.product') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownProductStockIn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select Product') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownProductStockIn" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="productSearchStockIn" placeholder="Search Products...">
                                <div id="productListStockIn" class="list-group">
                                    @foreach($productItems as $productId => $productName)
                                        <a href="#" class="list-group-item list-group-item-action product-item" data-value="{{ $productId }}">
                                            {{ $productName }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="product_id" id="selectedProductStockIn">
                        <small class="text-danger product-error" style="display: none;">Please select a product</small>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class="col-form-label">{{ __('inventory_balances.transfer_quantity') }}:</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1">+</span>
                            </div>
                            <input type="number" min="0" class="form-control" placeholder="Transfer Quantity" name="quantity" id="stockin_quantity">
                        </div>
                        <small class="text-danger quantity-error" style="display: none;">Please enter a quantity</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-secondary rounded-0 mt-2" data-dismiss="modal">{{ __('inventory_balances.cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-primary rounded-0 mt-2" id="stockinSubmitBtn" disabled>{{ __('inventory_balances.update') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div id="stockout" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ __('inventory_balances.stock_out') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    {!! Form::open(['route' => 'inventoryBalances.stockout', 'enctype' => 'multipart/form-data', 'id' => 'stockoutForm']) !!}
                    
                    <!-- Lorry Multi-Select Dropdown -->
                    <div class="form-group">
                        <label for="lorry_id" class="col-form-label">{{ __('inventory_balances.lorry') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownLorryStockOut" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('inventory_balances.select_lorry') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownLorryStockOut" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="lorrySearchStockOut" placeholder="Search Lorries...">
                                <div id="lorryListStockOut">
                                    @foreach($lorryItems as $lorryId => $lorryName)
                                        <div class="form-check ml-3">
                                            <input class="form-check-input lorry-checkbox" type="checkbox" name="lorry_id[]" value="{{ $lorryId }}" id="lorry_stockout_{{ $lorryId }}">
                                            <label class="form-check-label" for="lorry_stockout_{{ $lorryId }}">
                                                {{ $lorryName }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <small class="text-danger lorry-error" style="display: none;">Please select at least one lorry</small>
                    </div>

                    <!-- Product Single-Select Dropdown -->
                    <div class="form-group">
                        <label for="product_id" class="col-form-label">{{ __('inventory_balances.product') }}:</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-block dropdown-toggle" type="button" id="dropdownProductStockOut" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select Product') }}
                            </button>
                            <div class="dropdown-menu p-3" aria-labelledby="dropdownProductStockOut" style="width: 100%; max-height: 300px; overflow-y: auto;">
                                <input type="text" class="form-control mb-3" id="productSearchStockOut" placeholder="Search Products...">
                                <div id="productListStockOut" class="list-group">
                                    @foreach($productItems as $productId => $productName)
                                        <a href="#" class="list-group-item list-group-item-action product-item" data-value="{{ $productId }}">
                                            {{ $productName }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="product_id" id="selectedProductStockOut">
                        <small class="text-danger product-error" style="display: none;">Please select a product</small>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class="col-form-label">{{ __('inventory_balances.transfer_quantity') }}:</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1">-</span>
                            </div>
                            <input type="number" min="0" class="form-control" placeholder="Transfer Quantity" name="quantity" id="stockout_quantity" disabled>
                        </div>
                        <small class="text-danger quantity-error" style="display: none;">Please enter a valid quantity</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-secondary rounded-0 mt-2" data-dismiss="modal">{{ __('inventory_balances.cancel') }}</button>
                        <button type="submit" name="button" class="btn btn-primary rounded-0 mt-2" id="stockoutSubmitBtn" disabled>{{ __('inventory_balances.update') }}</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .dropdown-menu {
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .dropdown-menu .form-check {
            padding: 0.25rem 0;
        }
        .dropdown-toggle::after {
            margin-left: 10px;
        }
        .dropdown-menu::-webkit-scrollbar {
            width: 8px;
        }
        .dropdown-menu::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .btn-outline-primary {
            border-color: #007bff;
            color: #007bff;
        }
        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
        }
        .product-item:hover, 
        .product-item.active {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .list-group-item {
            border: 1px solid rgba(0,0,0,.125);
            margin-bottom: -1px;
        }
        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .modal-body {
            padding-bottom: 1rem;
        }
    </style>

    <script>
        $(document).ready(function () {
            // Lorry checkbox functionality for both modals
            $('.lorry-checkbox').change(function () {
                var dropdownButton = $(this).closest('.dropdown-menu').prev('.dropdown-toggle');
                var selected = [];
                var modal = $(this).closest('.modal');
                
                $(this).closest('.dropdown-menu').find('.lorry-checkbox:checked').each(function () {
                    selected.push($(this).next('label').text());
                });

                if (selected.length > 0) {
                    dropdownButton.text(selected.join(', '));
                    modal.find('.lorry-error').hide();
                } else {
                    dropdownButton.text('Select Lorries');
                    modal.find('.lorry-error').show();
                }
                
                // Validate form after lorry selection changes
                if (modal.attr('id') === 'stockin') {
                    validateStockInForm();
                } else {
                    validateStockOutForm();
                }
            });

            // Lorry search functionality for both modals
            $('#lorrySearchStockIn, #lorrySearchStockOut').on('keyup', function () {
                var searchTerm = $(this).val().toLowerCase();
                var targetList = $(this).closest('.dropdown-menu').find('.form-check');
                
                targetList.each(function () {
                    var lorryName = $(this).find('label').text().toLowerCase();
                    if (lorryName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Product selection for both modals
            $('.product-item').on('click', function(e) {
                e.preventDefault();
                var productName = $(this).text();
                var productId = $(this).data('value');
                var dropdown = $(this).closest('.dropdown-menu');
                var modal = $(this).closest('.modal');
                
                // Update UI
                $(this).siblings().removeClass('active');
                $(this).addClass('active');
                dropdown.prev('.dropdown-toggle').text(productName);
                
                // Set hidden input value
                var modalId = modal.attr('id');
                if (modalId === 'stockin') {
                    $('#selectedProductStockIn').val(productId);
                    modal.find('.product-error').hide();
                    validateStockInForm();
                } else {
                    $('#selectedProductStockOut').val(productId);
                    modal.find('.product-error').hide();
                    getstock(); // Trigger stock calculation for stockout
                    validateStockOutForm();
                }
            });

            // Product search functionality for both modals
            $('#productSearchStockIn, #productSearchStockOut').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                var productList = $(this).siblings('.list-group');
                
                productList.find('.product-item').each(function() {
                    var productText = $(this).text().toLowerCase();
                    if (productText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Quantity input validation for stock in
            $('#stockin_quantity').on('input', function() {
                var quantity = $(this).val();
                var modal = $(this).closest('.modal');
                
                if (quantity && parseFloat(quantity) > 0) {
                    modal.find('.quantity-error').hide();
                } else {
                    modal.find('.quantity-error').show();
                }
                
                validateStockInForm();
            });

            // Quantity input validation for stock out
            $('#stockout_quantity').on('input', function() {
                var quantity = $(this).val();
                var modal = $(this).closest('.modal');
                
                if (quantity && parseFloat(quantity) > 0) {
                    modal.find('.quantity-error').hide();
                } else {
                    modal.find('.quantity-error').show();
                }
                
                validateStockOutForm();
            });

            // Initialize dropdown buttons with default text
            $('.dropdown-toggle').each(function() {
                if (!$(this).text().trim()) {
                    if ($(this).closest('.dropdown-menu').find('.form-check').length) {
                        $(this).text('Select Lorries');
                    } else {
                        $(this).text('Select Product');
                    }
                }
            });

            // Reset form when modal is closed
            $('#stockin, #stockout').on('hidden.bs.modal', function () {
                var modal = $(this);
                modal.find('.lorry-checkbox').prop('checked', false);
                modal.find('.dropdown-toggle').text('Select Lorries');
                modal.find('.product-item.active').removeClass('active');
                modal.find('.dropdown-toggle[data-toggle="dropdown"]').first().text('Select Product');
                modal.find('input[type="number"]').val('');
                modal.find('input[type="hidden"]').val('');
                modal.find('.lorry-error, .product-error, .quantity-error').hide();
                modal.find('#stockout_quantity').prop('disabled', true);
                
                if (modal.attr('id') === 'stockin') {
                    $('#stockinSubmitBtn').prop('disabled', true);
                } else {
                    $('#stockoutSubmitBtn').prop('disabled', true);
                }
            });
        });

        // Stock out specific functionality
        $('#stockout').on('change', '.lorry-checkbox', function(){
            if ($('#selectedProductStockOut').val()) {
                getstock();
            }
            validateStockOutForm();
        });

        function getstock() {
            // Get selected lorry IDs from checkboxes
            var lorryIds = [];
            $('#stockout').find('.lorry-checkbox:checked').each(function() {
                lorryIds.push($(this).val());
            });
            
            // Get selected product ID
            var productId = $('#selectedProductStockOut').val();

            if (lorryIds.length > 0 && productId) {
                ShowLoad();
                $.ajax({
                    url: '{{ ENV("APP_URL") }}' + '/inventoryBalances/getstock/' + lorryIds.join(',') + '/' + productId,
                    type: 'GET',
                    success: function(data) {
                        if (data.status) {
                            $('#stockout').find('#stockout_quantity').prop('disabled', false);
                            $('#stockout').find('#stockout_quantity').val(data.quantity);
                            $('#stockout').find('.quantity-error').hide();
                        } else {
                            $('#stockout').find('#stockout_quantity').prop('disabled', true);
                            $('#stockout').find('#stockout_quantity').val(data.quantity);
                            $('#stockout').find('.quantity-error').show();
                            noti('e', 'Warning', data.message);
                        }
                        validateStockOutForm();
                        HideLoad();
                    },
                    error: function(error) {
                        noti('e', 'Please contact your administrator', error.responseJSON.message);
                        HideLoad();
                    }
                });
            } else {
                $('#stockout').find('#stockout_quantity').prop('disabled', true);
                $('#stockout').find('#stockout_quantity').val('');
                validateStockOutForm();
            }
        }

        // Validation function for Stock In form
        function validateStockInForm() {
            var lorrySelected = $('#stockin').find('.lorry-checkbox:checked').length > 0;
            var productSelected = $('#selectedProductStockIn').val() !== '';
            var quantity = $('#stockin_quantity').val();
            var quantityValid = quantity && parseFloat(quantity) > 0;
            
            var isValid = lorrySelected && productSelected && quantityValid;
            
            $('#stockinSubmitBtn').prop('disabled', !isValid);
            
            // Show/hide error messages
            if (!lorrySelected) {
                $('#stockin').find('.lorry-error').show();
            } else {
                $('#stockin').find('.lorry-error').hide();
            }
            
            if (!productSelected) {
                $('#stockin').find('.product-error').show();
            } else {
                $('#stockin').find('.product-error').hide();
            }
            
            if (!quantityValid) {
                $('#stockin').find('.quantity-error').show();
            } else {
                $('#stockin').find('.quantity-error').hide();
            }
        }

        // Validation function for Stock Out form
        function validateStockOutForm() {
            var lorrySelected = $('#stockout').find('.lorry-checkbox:checked').length > 0;
            var productSelected = $('#selectedProductStockOut').val() !== '';
            var quantity = $('#stockout_quantity').val();
            var quantityValid = quantity && parseFloat(quantity) > 0 && !$('#stockout_quantity').prop('disabled');
            
            var isValid = lorrySelected && productSelected && quantityValid;
            
            $('#stockoutSubmitBtn').prop('disabled', !isValid);
            
            // Show/hide error messages
            if (!lorrySelected) {
                $('#stockout').find('.lorry-error').show();
            } else {
                $('#stockout').find('.lorry-error').hide();
            }
            
            if (!productSelected) {
                $('#stockout').find('.product-error').show();
            } else {
                $('#stockout').find('.product-error').hide();
            }
            
            if (!quantityValid) {
                $('#stockout').find('.quantity-error').show();
            } else {
                $('#stockout').find('.quantity-error').hide();
            }
        }

        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            }
        });
    </script>
@endpush