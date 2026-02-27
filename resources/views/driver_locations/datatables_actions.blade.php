{!! Form::open(['route' => ['driverLocations.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('driverLocations.show', $id) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('driverLocations.edit', $id) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('driver_locations.are_you_sure')."')"    
    ]) !!}
</div>
{!! Form::close() !!}
