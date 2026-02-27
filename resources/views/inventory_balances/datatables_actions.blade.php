{{-- {!! Form::open(['route' => ['inventoryBalances.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('inventoryBalances.show', $id) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('inventoryBalances.edit', $id) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('inventory_balances.are_you_sure)."')"   
    ]) !!}
</div>
{!! Form::close() !!} --}}
