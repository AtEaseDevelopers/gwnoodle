
<div class='btn-group'>
    <a href="{{ route('loans.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('loans.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::open(['route' => ['loans.start', Crypt::encrypt($id)], 'method' => 'post']) !!}
        {!! Form::button('<i class="fa fa-check"></i>', [
            'type' => 'submit',
            'class' => 'btn btn-ghost-warning',
            'onclick' => "return confirm('Are you sure to start the Loan? Once you start, you are not able to edit and delete the Loan.')"
        ]) !!}
    {!! Form::close() !!}
    {!! Form::open(['route' => ['loans.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
        {!! Form::button('<i class="fa fa-trash"></i>', [
            'type' => 'submit',
            'class' => 'btn btn-ghost-danger',
            'onclick' => "return confirm('Are you sure to delete the Loan?')"
        ]) !!}
    {!! Form::close() !!}
</div>
