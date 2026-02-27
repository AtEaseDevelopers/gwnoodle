{!! Form::open(['route' => ['userHasRoles.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('userHasRoles.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('userHasRoles.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('Are you sure to delete the User Role?')"
    ]) !!}
</div>
{!! Form::close() !!}
