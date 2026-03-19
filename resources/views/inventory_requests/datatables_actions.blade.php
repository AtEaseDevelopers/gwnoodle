<div class="btn-group" role="group">
    <!-- View Button (Modal Trigger) -->
    <button type="button" class="btn btn-ghost-success view-request-btn" title="View"
            data-id="{{ $request->id }}"
            data-request='{
                "id": "{{ $request->id }}",
                "driver_id": "{{ $request->driver_id }}",
                "driver_name": "{{ $request->driver->name ?? 'N/A' }}",
                "items": {{ json_encode($request->items ?? []) }},
                "total_quantity": "{{ $request->total_quantity }}",
                "item_count": "{{ $request->item_count }}",
                "status": "{{ $request->status }}",
                "remarks": "{{ $request->remarks ?? 'No remarks' }}",
                "created_at": "{{ $request->created_at ? $request->created_at->format('d-m-Y H:i') : 'N/A' }}",
                "approved_by": "{{ $request->approver->name ?? 'N/A' }}",
                "approved_at": "{{ $request->approved_at ? $request->approved_at->format('d-m-Y H:i') : 'N/A' }}",
                "rejected_by": "{{ $request->rejector->name ?? 'N/A' }}",
                "rejected_at": "{{ $request->rejected_at ? $request->rejected_at->format('d-m-Y H:i') : 'N/A' }}",
                "rejection_reason": "{{ $request->rejection_reason ?? 'N/A' }}"
            }'>
        <i class="fa fa-eye"></i>
    </button>
    
    @php
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin'); 
    @endphp
    
    @if($request->status == 'pending')
    <!-- Edit Button (Modal Trigger) -->
    <button type="button" class="btn btn-ghost-primary edit-request-btn" title="Edit"
            data-id="{{ $request->id }}"
            data-request='{
                "driver_id": "{{ $request->driver_id }}",
                "driver_name": "{{ $request->driver->name ?? 'N/A' }}",
                "items": {{ json_encode($request->items ?? []) }},
                "remarks": "{{ $request->remarks ?? '' }}"
            }'>
        <i class="fa fa-edit"></i>
    </button>
    @endif
    
    @if($request->status == 'pending' && auth()->user()->can('inventoryrequest'))
    <!-- Approve Button -->
    <form action="{{ route('inventoryRequests.approve', $request->id) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-ghost-success" title="Approve" onclick="return confirm('Are you sure you want to approve this request?')">
            <i class="fa fa-check"></i>
        </button>
    </form>
    
    <!-- Reject Button (with modal trigger) -->
    <button type="button" class="btn btn-ghost-danger" title="Reject" data-toggle="modal" data-target="#rejectModal{{ $request->id }}">
        <i class="fa fa-times"></i>
    </button>
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('inventoryRequests.reject', $request->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Request #{{ $request->id }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="rejection_reason">Rejection Reason *</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejection"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    @if($request->status == 'pending')
    <!-- Delete Button -->
    <form action="{{ route('inventoryRequests.destroy', $request->id) }}" method="POST" style="display: inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this request?')">
            <i class="fa fa-trash"></i>
        </button>
    </form>
    @endif
</div>