<div class='btn-group'>
    @if(isset($einvoice))
        <a href="{{ route('einvoices.show', Crypt::encrypt($einvoice->id)) }}" class='btn btn-ghost-info' title="View Details">
            <i class="fa fa-eye"></i>
        </a>
        @if($einvoice->uuid)
            <a href="{{ route('einvoices.view-document', Crypt::encrypt($einvoice->id)) }}?format=PDF" target="_blank" class="btn btn-ghost-secondary" title="View PDF Document">
                <i class="fa fa-file-pdf-o"></i>
            </a>
            @if($einvoice->longId)
                <a href="{{ $einvoice->getValidationLink() }}" target="_blank" class="btn btn-ghost-success" title="View in MyInvois Portal">
                    <i class="fa fa-external-link"></i>
                </a>
            @endif
            @if($einvoice->status === 'Valid' && $einvoice->submission_date && $einvoice->submission_date->diffInHours(now()) <= 72)
                <button type="button" class="btn btn-ghost-danger" data-action="cancel-einvoice" data-id="{{ Crypt::encrypt($einvoice->id) }}" title="Cancel Document">
                    <i class="fa fa-times"></i>
                </button>
            @endif
        @else
            <span class="text-muted" title="No UUID available">-</span>
        @endif
    @endif
</div>

