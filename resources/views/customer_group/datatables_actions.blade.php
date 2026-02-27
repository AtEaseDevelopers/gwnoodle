{!! Form::open(['route' => ['customer_group.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <!-- <a href="{{ route('customer_group.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a> -->
    <a href="{{ route('customer_group.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('customer_group.are_you_sure_to_delete_the_group')."')"    

    ]) !!}
</div>
{!! Form::close() !!}
