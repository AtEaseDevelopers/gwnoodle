@php
    $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
    $canEditQty = $isAdmin || (auth()->check() && auth()->user()->hasRole('Inventory Admin'));
    $isPending = $request->isPending();
@endphp

<div class="btn-group">
    @if ($isPending && $canEditQty)
        <button type="button" class="btn btn-ghost-primary edit-qty-btn"
                title="Edit Quantity"
                data-id="{{ $request->id }}"
                data-quantity="{{ $request->quantity }}"
                data-batch="{{ $request->batch ? $request->batch->batch_code : '' }}">
            <i class="fa fa-pencil"></i>
        </button>
    @endif

    @if ($isPending && $isAdmin)
        <button type="button" class="btn btn-ghost-success approve-btn"
                title="Approve"
                data-id="{{ $request->id }}"
                data-batch="{{ $request->batch ? $request->batch->batch_code : '' }}"
                data-quantity="{{ $request->quantity }}">
            <i class="fa fa-check"></i>
        </button>
        <button type="button" class="btn btn-ghost-danger reject-btn"
                title="Reject"
                data-id="{{ $request->id }}"
                data-batch="{{ $request->batch ? $request->batch->batch_code : '' }}">
            <i class="fa fa-times"></i>
        </button>
    @endif

    @if (!$isPending || (!$isAdmin && !$canEditQty))
        <span class="text-muted">&mdash;</span>
    @endif
</div>
