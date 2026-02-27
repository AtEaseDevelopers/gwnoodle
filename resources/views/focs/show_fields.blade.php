<!-- Product Id Field -->
<div class="form-group">
    {!! Form::label('product_id', __('focs.product')) !!}:
    <p>{{ $foc->product->name }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('focs.customer')) !!}:
    <p>{{ $foc->customer->company }}</p>
</div>

<!-- Quantity Field -->
<div class="form-group">
    {!! Form::label('quantity', __('focs.quantity')) !!}:
    <p>{{ $foc->quantity }}</p>
</div>

<!-- Free Product Id Field -->
<div class="form-group">
    {!! Form::label('free_product_id', __('focs.free_product')) !!}:
    <p>{{ $foc->product->name }}</p>
</div>

<!-- Free Quantity Field -->
<div class="form-group">
    {!! Form::label('free_quantity', __('focs.free_quantity')) !!}:
    <p>{{ $foc->free_quantity }}</p>
</div>

<!-- Startdate Field -->
<div class="form-group">
    {!! Form::label('startdate', __('focs.start_date')) !!}:
    <p>{{ $foc->startdate }}</p>
</div>

<!-- Enddate Field -->
<div class="form-group">
    {!! Form::label('enddate', __('focs.end_date')) !!}:
    <p>{{ $foc->enddate }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('focs.status')) !!}:
    <p>{{ $foc->status == 1 ? __('focs.active') : __('focs.unactive') }}</p>
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