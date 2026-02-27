<!-- Lorryno Field -->
<div class="form-group">
    {!! Form::label('lorryno',  __('lorries.lorry_no')) !!}:
    <p>{{ $lorry->lorryno }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status',  __('lorries.status')) !!}:
    <p>{{ $lorry->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('lorries.remark')) !!}:
    <p>{{ $lorry->remark }}</p>
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