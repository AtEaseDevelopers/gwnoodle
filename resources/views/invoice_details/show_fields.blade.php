<!-- Invoice Id Field -->
<div class="form-group">
    {!! Form::label('invoice_id', __('invoice_details.invoice')) !!}
    <p>{{ $invoiceDetail->invoice->invoiceno }}</p>
</div>

<!-- Product Id Field -->
<div class="form-group">
    {!! Form::label('product_id', __('invoice_details.product')) !!}
    <p>{{ $invoiceDetail->product->name }}</p>
</div>

<!-- Quantity Field -->
<div class="form-group">
    {!! Form::label('quantity', __('invoice_details.quantity')) !!}
    <p>{{ $invoiceDetail->quantity }}</p>
</div>

<!-- Price Field -->
<div class="form-group">
    {!! Form::label('price', __('invoice_details.price')) !!}
    <p>{{ number_format($invoiceDetail->price, 2) }}</p>
</div>

<!-- TotalPrice Field -->
<div class="form-group">
    {!! Form::label('totalprice', __('invoice_details.total_price')) !!}
    <p>{{ number_format($invoiceDetail->totalprice, 2) }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('invoice_details.remark')) !!}
    <p>{{ $invoiceDetail->remark }}</p>
</div>

@push('scripts')
    <script>
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