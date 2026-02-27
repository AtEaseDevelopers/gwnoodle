{!! Form::open(['route' => ['invoicePayments.destroy', encrypt($id)], 'method' => 'delete']) !!}
<div class='btn-group'>
    @can('paymentapprove')
      @if($status == 0)
         <a href="#" onclick="check('{{encrypt($id)}}')" class='btn btn-ghost-success'>
            <i class="fa fa-check"></i>
         </a>
      @endif
    @endcan
    <a href="{{ route('invoicePayments.show', encrypt($id)) }}" class='btn btn-ghost-primary'>
       <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('invoicePayments.edit', encrypt($id)) }}" class='btn btn-ghost-info'>
       <i class="fa fa-edit"></i>
    </a>
    <a href="{{ route('invoicePayments.print', ['id' => encrypt($id), 'function' => 'view'] ) }}" class='btn btn-ghost-primary' target="_blank">
       <i class="fa fa-print"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-ghost-danger',
        'onclick' => "return confirm('".trans('invoice_payments.are_you_sure_to_delete_the_payment')."')"   
    ]) !!}
</div>
{!! Form::close() !!}
