<div class="btn-group" role="group">
    <!-- View Button (Modal Trigger) -->
    <button type="button" class="btn btn-ghost-success view-request-btn" title="View"
            data-id="{{ $request->id }}"
            data-request='{
                "id": {{ $request->id }},
                "driver_id": {{ $request->driver_id }},
                "driver_name": "{{ $request->driver->name ?? 'N/A' }}",
                "items": {!! json_encode($request->items) !!},
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
        $isAdmin = $user->hasRole('admin') || $user->hasRole('stock_manager');
        $driver = $request->driver;
    @endphp
    
    @if($request->status == 'pending' | $isAdmin)
        @if($driver->trip_id === $request->trip_id)
        <!-- Edit Count Button (NEW: For admin to fill counted quantities) -->
        <button type="button" class="btn btn-ghost-primary edit-count-btn" title="Edit Count"
                data-id="{{ $request->id }}"
                data-request='{
                    "id": {{ $request->id }},
                    "driver_id": {{ $request->driver_id }},
                    "driver_name": "{{ $request->driver->name ?? 'N/A' }}",
                    "items": {!! json_encode($request->items) !!},
                    "remarks": "{{ $request->remarks ?? '' }}",
                    "created_at": "{{ $request->created_at ? $request->created_at->format('d-m-Y H:i') : 'N/A' }}"
                }'>
            <i class="fa fa-edit"></i>
        </button>
        @endif
    @endif
    
    <!-- Remove old Approve/Reject buttons from here - they will be in the View modal -->
    
    @if($request->status == 'pending' && $isAdmin)
    <!-- Delete Button -->
    <form action="{{ route('inventoryCounts.destroy', $request->id) }}" method="POST" style="display: inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this stock count request?')">
            <i class="fa fa-trash"></i>
        </button>
    </form>
    @endif
</div>