@php
    $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
@endphp

<div class='btn-group'>
    <a href="{{ route('productBatches.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('productBatches.print-label', encrypt($id)) }}" class='btn btn-ghost-warning'>
       <i class="fa fa-print"></i>
    </a>

    @if ($isAdmin)
        {!! Form::open(['route' => ['productBatches.destroy', encrypt($id)], 'method' => 'delete']) !!}
        <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Delete this product batch? This can only be done if it has never been stocked in, so this cannot be undone.')">
           <i class="fa fa-trash"></i>
        </button>
        {!! Form::close() !!}
    @endif
</div>
