{!! Form::open(['route' => ['productBatches.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('productBatches.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('productBatches.edit', encrypt($id)) }}" class='btn btn-ghost-primary'>
       <i class="fa fa-edit"></i>
    </a>
   <a href="{{ route('productBatches.print-label', encrypt($id)) }}" class='btn btn-ghost-warning'>
       <i class="fa fa-print"></i>
    </a>
    <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Delete this product batch? This cannot be undone.')">
       <i class="fa fa-trash"></i>
    </button>

</div>
{!! Form::close() !!}
