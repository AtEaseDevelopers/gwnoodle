{!! Form::open(['route' => ['productBatches.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('productBatches.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('productBatches.edit', encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>

</div>
{!! Form::close() !!}
