@php
    $isInventoryManager = auth()->check() && auth()->user()->isInventoryManager();
@endphp
<div class='btn-group'>
    <a href="{{ route('lorries.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success' title="{{ trans('View') }}" data-toggle="tooltip">
       <i class="fa fa-eye"></i>
    </a>
    @unless($isInventoryManager)
    <a href="{{ route('lorries.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info' title="{{ trans('Edit') }}" data-toggle="tooltip">
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::open(['route' => ['lorries.destroy', Crypt::encrypt($id)], 'method' => 'delete', 'style' => 'display:inline']) !!}
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'title' => trans('Delete'),
        'data-toggle' => 'tooltip',
        'onclick' => "return confirm('".trans('lorries.are_you_sure_to_delete_the_lorry')."')"
    ]) !!}
    {!! Form::close() !!}
    @endunless
</div>
