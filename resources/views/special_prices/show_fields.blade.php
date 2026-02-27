<!-- Product Id Field -->
<div class="form-group">
    {!! Form::label('product_id', __('special_prices.product')) !!}:
    <p>{{ $specialPrice->product->name }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('special_prices.customer')) !!}:
    <p>{{ $specialPrice->customer->company }}</p>
</div>

<!-- Price Field -->
<div class="form-group">
    {!! Form::label('price', __('special_prices.price')) !!}:
    <p>{{ number_format($specialPrice->price, 2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('special_prices.status')) !!}:
    <p>{{ $specialPrice->status == 1 ? __('special_prices.active') : __('special_prices.unactive') }}</p>
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