<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', __('products.code')) !!}:
    <p>{{ $product->code }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', __('products.name')) !!}:
    <p>{{ $product->name }}</p>
</div>

<!-- Price Field -->
<div class="form-group">
    {!! Form::label('price', __('products.price')) !!}:
    <p>{{ number_format($product->price, 2) }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', __('products.type')) !!}:
    @switch($product->type)
        @case(1)
            <p>{{ __('products.type_coffee') }}</p>
            @break
        @case(2)
            <p>{{ __('products.type_tea') }}</p>
            @break
        @case(3)
            <p>{{ __('products.type_cocoa') }}</p>
            @break
        @case(4)
            <p>{{ __('products.type_ice') }}</p>
            @break
        @default
            <p>{{ __('products.type_other') }}</p>
    @endswitch
    <!--<p>{{ $product->status == 1 ? "Ice" : "Other" }}</p>-->
</div>


<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('products.status')) !!}:
    <p>{{ $product->status == 1 ? __('products.active') : __('products.unactive') }}</p>
</div>

@einvoice
<!-- Classification Code Field -->
<div class="form-group">
    {!! Form::label('classification_code', 'Classification Code:') !!}
    <p>{{ $product->classification_code ? $product->classification_code . ' - ' . (App\Models\ClassificationCode::getDescription($product->classification_code) ?? '') : '' }}</p>
</div>
@endeinvoice

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