{{-- {!! Form::open(['route' => ['taskTransfers.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('taskTransfers.show', $id) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('taskTransfers.edit', $id) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('task_transfers.are_you_sure')."')"   
    ]) !!}
</div>
{!! Form::close() !!} --}}
