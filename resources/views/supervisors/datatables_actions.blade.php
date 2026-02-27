{!! Form::open(['route' => ['supervisors.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('supervisors.show', encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('supervisors.edit', encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('operations.are_you_sure_to_delete_the_supervisor')."')"   

    ]) !!}
</div>
{!! Form::close() !!}
