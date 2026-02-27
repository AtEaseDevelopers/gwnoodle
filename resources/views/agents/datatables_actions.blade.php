{!! Form::open(['route' => ['agents.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="#" onclick="get_attachment('{{ Crypt::encrypt($id) }}')" class='btn btn-ghost-primary'>
       <i class="fa fa-file"></i>
    </a>
    <a href="{{ route('agents.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('agents.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('agents.are_you_sure_to_delete_the_agent')."')"    
    ]) !!}
</div>
{!! Form::close() !!}
