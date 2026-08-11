{!! Form::open(['route' => ['productBatches.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('productBatches.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
   <a href="{{ route('productBatches.print-label', encrypt($id)) }}" class='btn btn-ghost-warning'>
       <i class="fa fa-print"></i>
    </a>

</div>
{!! Form::close() !!}
