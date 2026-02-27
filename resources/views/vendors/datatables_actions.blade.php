{!! Form::open(['route' => ['vendors.destroy', Crypt::encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="#" id="vendorprint" vendorid="{{ Crypt::encrypt($id) }}" vendorcode="{{ $code }}" vendorname="{{ $name }}" class='btn btn-ghost-warning'>
       <i class="fa fa-print"></i>
    </a>
    <a href="{{ route('vendors.show', Crypt::encrypt($id)) }}" class='btn btn-ghost-success'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('vendors.edit', Crypt::encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('Are you sure to delete the Vendor?')"
    ]) !!}
</div>
{!! Form::close() !!}
