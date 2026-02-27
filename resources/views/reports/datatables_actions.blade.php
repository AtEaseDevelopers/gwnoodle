{{-- {!! Form::open(['route' => ['reports.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('reports.show', $id) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('reports.edit', $id) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('report.are_you_sure')."')"   

    ]) !!}
</div>
{!! Form::close() !!} --}}

<div class='btn-group'>
    <a href="{{ route('reports.show', $id) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
</div>