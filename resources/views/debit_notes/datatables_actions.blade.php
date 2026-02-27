<div class='btn-group'>
    @if(isset($debitNote))
        <a href="{{ route('debit-notes.show', Crypt::encrypt($debitNote->id)) }}" class='btn btn-ghost-info' title="View Details">
            <i class="fa fa-eye"></i>
        </a>
        @if($debitNote->uuid)
            @if($debitNote->longId)
                <a href="{{ $debitNote->getValidationLink() ?? '#' }}" target="_blank" class="btn btn-ghost-success" title="View in MyInvois Portal">
                    <i class="fa fa-external-link"></i>
                </a>
            @endif
            @if($debitNote->status === 'Valid' && $debitNote->submission_date && $debitNote->submission_date->diffInHours(now()) <= 72)
                <button type="button" class="btn btn-ghost-danger" data-action="cancel-debit-note" data-id="{{ Crypt::encrypt($debitNote->id) }}" title="Cancel Debit Note">
                    <i class="fa fa-times"></i>
                </button>
            @endif
        @else
            <span class="text-muted" title="No UUID available">-</span>
        @endif
    @endif
</div>

