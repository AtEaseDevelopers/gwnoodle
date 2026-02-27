<div class='btn-group'>
    @if(isset($consolidatedEinvoice))
        <a href="{{ route('consolidated-einvoices.show', Crypt::encrypt($consolidatedEinvoice->id)) }}" class="btn btn-ghost-secondary" title="View Details">
            <i class="fa fa-eye"></i>
        </a>
        @if($consolidatedEinvoice->uuid)
            @if($consolidatedEinvoice->longId)
                <a href="{{ $consolidatedEinvoice->getValidationLink() }}" target="_blank" class="btn btn-ghost-success" title="View in MyInvois Portal">
                    <i class="fa fa-external-link"></i>
                </a>
            @endif
            @if($consolidatedEinvoice->status === 'Valid' && $consolidatedEinvoice->submission_date && $consolidatedEinvoice->submission_date->diffInHours(now()) <= 72)
                <button type="button" class="btn btn-ghost-danger" data-action="cancel-consolidated" data-id="{{ Crypt::encrypt($consolidatedEinvoice->id) }}" title="Cancel Document">
                    <i class="fa fa-times"></i>
                </button>
            @endif
        @else
            <span class="text-muted" title="No UUID available">-</span>
        @endif
    @endif
</div>
