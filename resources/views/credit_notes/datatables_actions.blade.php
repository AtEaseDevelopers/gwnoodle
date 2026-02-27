<div class='btn-group'>
    @if(isset($creditNote))
        <a href="{{ route('credit-notes.show', Crypt::encrypt($creditNote->id)) }}" class='btn btn-ghost-info' title="View Details">
            <i class="fa fa-eye"></i>
        </a>
        @if($creditNote->uuid)
            @if($creditNote->longId)
                <a href="{{ $creditNote->getValidationLink() ?? '#' }}" target="_blank" class="btn btn-ghost-success" title="View in MyInvois Portal">
                    <i class="fa fa-external-link"></i>
                </a>
            @endif
            @if($creditNote->status === 'Valid' && $creditNote->submission_date && $creditNote->submission_date->diffInHours(now()) <= 72)
                <button type="button" class="btn btn-ghost-danger" data-action="cancel-credit-note" data-id="{{ Crypt::encrypt($creditNote->id) }}" title="Cancel Credit Note">
                    <i class="fa fa-times"></i>
                </button>
            @endif
        @else
            <span class="text-muted" title="No UUID available">-</span>
        @endif
    @endif
</div>

