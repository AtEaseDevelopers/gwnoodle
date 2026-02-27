{!! Form::open(['route' => ['roleHasPermissions.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('roleHasPermissions.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('roleHasPermissions.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('Are you sure to delete the Role Permission?')"
    ]) !!}
</div>
{!! Form::close() !!}
