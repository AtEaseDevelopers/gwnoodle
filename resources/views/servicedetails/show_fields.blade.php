<!-- Lorry Id Field -->
<div class="form-group">
    {!! Form::label('lorry_id', __('lorry_service.lorry')) !!}:
    <p>{{ $servicedetails->lorry->lorryno }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', __('lorry_service.type')) !!}:
    <p>{{ __("lorry_service.type_" . strtolower($servicedetails->type)) }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('lorry_service.date')) !!}:
    <p>{{ $servicedetails->date }}</p>
</div>

<!-- Nextdate Field -->
<div class="form-group">
    {!! Form::label('nextdate', __('lorry_service.next_date')) !!}:
    <p>{{ $servicedetails->nextdate }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', __('lorry_service.amount')) !!}:
    <p>{{ number_format($servicedetails->amount, 2) }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('lorry_service.remark')) !!}:
    <p>{{ $servicedetails->remark }}</p>
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