<!-- Lorry Id Field -->
<div class="form-group">
    {!! Form::label('lorry_id', 'Lorry:') !!}
    <p>{{ $commissionByVendors->lorry->lorryno }}</p>
</div>

<!-- Vendor Id Field -->
<div class="form-group">
    {!! Form::label('vendor_id', 'Vendor:') !!}
    <p>{{ $commissionByVendors->vendor->name }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $commissionByVendors->description }}</p>
</div>

<!-- Commissionlimit Field -->
<div class="form-group">
    {!! Form::label('commissionlimit', 'Commission Limit (TON):') !!}
    <p>{{ number_format($commissionByVendors->commissionlimit,2,'.','') }}</p>
</div>

<!-- Commissionpercentage Field -->
<div class="form-group">
    {!! Form::label('commissionpercentage', 'Commission %:') !!}
    <p>{{ number_format($commissionByVendors->commissionpercentage,2,'.','') }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $commissionByVendors->status == 1 ? "Active" : "Unactive" }}</p>
</div>

<!-- remark Field -->
<div class="form-group">
    {!! Form::label('remark', 'Remark:') !!}
    <p>{{ $commissionByVendors->remark }}</p>
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    <p>{{ $commissionByVendors->STR_UDF1 }}</p>
</div>

<!-- Str Udf2 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    <p>{{ $commissionByVendors->STR_UDF2 }}</p>
</div>

<!-- Str Udf3 Field -->
<div class="form-group">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    <p>{{ $commissionByVendors->STR_UDF3 }}</p>
</div>

<!-- Int Udf1 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    <p>{{ $commissionByVendors->INT_UDF1 }}</p>
</div>

<!-- Int Udf2 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    <p>{{ $commissionByVendors->INT_UDF2 }}</p>
</div>

<!-- Int Udf3 Field -->
<div class="form-group">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    <p>{{ $commissionByVendors->INT_UDF3 }}</p>
</div> --}}

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