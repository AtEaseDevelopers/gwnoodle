<div class="form-group">
    {!! Form::label('sales', __('trips.sales')) !!}:
    <p>{{ $trip->sales }}</p>
</div>

<div class="form-group">
    {!! Form::label('cash', __('trips.cash')) !!}:
    <p>{{ $trip->cash }}</p>
</div>

<div class="form-group">
    {!! Form::label('cashleft', __('trips.cash_left')) !!}:
    <p>{{ $trip->cash_left }}</p>
</div>

<div class="form-group">
    {!! Form::label('credit', __('trips.credit')) !!}:
    <p>{{ $trip->credit }}</p>
</div>

<div class="form-group">
    {!! Form::label('onlinebank', __('trips.online_bank')) !!}:
    <p>{{ $trip->onlinebank }}</p>
</div>

<div class="form-group">
    {!! Form::label('tng', __('trips.tng')) !!}:
    <p>{{ $trip->tng }}</p>
</div>

<div class="form-group">
    {!! Form::label('cheque', __('trips.cheque')) !!}:
    <p>{{ $trip->cheque }}</p>
</div>