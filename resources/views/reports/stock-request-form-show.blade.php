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
                            <a href="{{ route('reports.index') }}" class="btn btn-light float-right">Back</a>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.run') }}" accept-charset="UTF-8" id="reportForm" target="_blank">
                                @csrf
                                <input type="hidden" name="_report_id" value="{{ $report->id }}">

                                {{-- Header fields: two columns --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <input type="text" class="form-control" name="description" placeholder="Description">
                                        </div>
                                        <div class="form-group">
                                            <label>Ref. Doc. No.</label>
                                            <input type="text" class="form-control" name="ref_doc_no" placeholder="Reference Document No.">
                                        </div>
                                        <div class="form-group">
                                            <label>Reason</label>
                                            <input type="text" class="form-control" name="reason" placeholder="Reason">
                                        </div>
                                        <div class="form-group">
                                            <label>Authorised By</label>
                                            <input type="text" class="form-control" name="authorised_by" placeholder="Authorised By">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>No.</label>
                                            <input type="text" class="form-control" name="doc_no" placeholder="Document No.">
                                        </div>
                                        <div class="form-group">
                                            <label>Date</label>
                                            <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="form-group">
                                            <label>From Location</label>
                                            <input type="text" class="form-control" name="from_location" placeholder="From Location">
                                        </div>
                                        <div class="form-group">
                                            <label>To Location</label>
                                            <input type="text" class="form-control" name="to_location" placeholder="To Location">
                                        </div>
                                    </div>
                                </div>

                                {{-- Product rows --}}
                                <hr>
                                <h6><strong>Items</strong></h6>
                                <table class="table table-bordered table-sm" id="itemsTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width:40px;">No.</th>
                                            <th style="width:200px;">Product</th>
                                            <th style="width:100px;">Item Code</th>
                                            <th>Description</th>
                                            <th style="width:70px;">Qty</th>
                                            <th style="width:110px;">UOM</th>
                                            <th style="width:110px;">Amount (RM)</th>
                                            <th style="width:50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr class="item-row">
                                            <td class="row-no">1</td>
                                            <td>
                                                <select class="form-control form-control-sm product-select" name="product_id[]">
                                                    <option value="">-- Select --</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-name="{{ $product->name }}"
                                                            data-price="{{ $product->price ?? 0 }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm item-code-field" name="item_code[]" placeholder="Code">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm description-field" name="item_description[]" readonly placeholder="Auto-filled">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm qty-field" name="quantity[]" min="0" step="any" value="1">
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm" name="uom[]">
                                                    <option value="Unit">Unit</option>
                                                    <option value="Pack">Pack</option>
                                                    <option value="Bottle">Bottle</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm amount-field" name="amount[]" placeholder="0.00" value="0.00">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-secondary btn-sm mb-3" id="addRow">
                                    <i class="fa fa-plus"></i> Add Row
                                </button>

                                <hr>
                                <div class="form-group">
                                    <label>Note</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Note..."></textarea>
                                </div>

                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </button>
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
    });

    var productOptions = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price ?? 0]));

    var uomOptions = '<option value="Unit">Unit</option><option value="Pack">Pack</option><option value="Bottle">Bottle</option>';

    function buildProductOptions() {
        var html = '<option value="">-- Select --</option>';
        productOptions.forEach(function(p) {
            html += '<option value="' + p.id + '" data-name="' + p.name.replace(/"/g, '&quot;') + '" data-price="' + p.price + '">' + p.name + '</option>';
        });
        return html;
    }

    function calcAmount(row) {
        var selected = row.find('.product-select option:selected');
        var priceAttr = selected.attr('data-price');
        var price = (priceAttr !== undefined && priceAttr !== '' && priceAttr !== 'null') ? parseFloat(priceAttr) : 0;
        if (isNaN(price)) price = 0;
        var qty = parseFloat(row.find('.qty-field').val()) || 0;
        row.find('.amount-field').val((price * qty).toFixed(2));
    }

    function updateRowNumbers() {
        $('#itemsBody .item-row').each(function(i) {
            $(this).find('.row-no').text(i + 1);
        });
    }

    $(document).on('change', '.product-select', function() {
        var selected = $(this).find('option:selected');
        var row = $(this).closest('tr');
        var name = selected.data('name') || '';
        row.find('.item-code-field').val(name);
        row.find('.description-field').val(name);
        calcAmount(row);
    });

    $(document).on('input change keyup', '.qty-field', function() {
        calcAmount($(this).closest('tr'));
    });

    $('#addRow').on('click', function() {
        var rowCount = $('#itemsBody .item-row').length + 1;
        var newRow = '<tr class="item-row">' +
            '<td class="row-no">' + rowCount + '</td>' +
            '<td><select class="form-control form-control-sm product-select" name="product_id[]">' + buildProductOptions() + '</select></td>' +
            '<td><input type="text" class="form-control form-control-sm item-code-field" name="item_code[]" placeholder="Code"></td>' +
            '<td><input type="text" class="form-control form-control-sm description-field" name="item_description[]" readonly placeholder="Auto-filled"></td>' +
            '<td><input type="number" class="form-control form-control-sm qty-field" name="quantity[]" min="0" step="any" value="1"></td>' +
            '<td><select class="form-control form-control-sm" name="uom[]">' + uomOptions + '</select></td>' +
            '<td><input type="text" class="form-control form-control-sm amount-field" name="amount[]" value="0.00"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button></td>' +
            '</tr>';
        $('#itemsBody').append(newRow);
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#itemsBody .item-row').length > 1) {
            $(this).closest('tr').remove();
            updateRowNumbers();
        }
    });

    $('#reportForm').on('submit', function() {
        ShowLoad();
        setTimeout(function() {
            HideLoad();
        }, 4000);
    });

</script>
@endpush
