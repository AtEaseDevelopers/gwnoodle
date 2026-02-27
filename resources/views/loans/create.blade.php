@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('loans.index') !!}">Loan</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Loan</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'loans.store']) !!}

                                   @include('loans.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection

@push('scripts')
    <script>
        $( document ).ready(function() {
            setAmount();
            $("#amount").change(function(){
                setAmount();
            });
            $("#period").change(function(){
                setAmount();
            });
            $("#rate").change(function(){
                setAmount();
            });
        });

        function setAmount(){
            if($('#amount').val() != '' && $('#period').val() != '' && $('#rate').val() != '' ){
                amount = parseFloat($('#amount').val());
                period = parseInt($('#period').val());
                rate = parseFloat($('#rate').val());
                monthlyamount = ((amount + (amount * (period*rate/12) / 100)) / period).toFixed(2);
                totalamount = (parseFloat(monthlyamount) * period).toFixed(2);
                interest = (totalamount - amount).toFixed(2);;
                $('#totalamount').val(totalamount);
                $('#monthlyamount').val(monthlyamount);
                $('#interest').val(interest);
            }
        }
    </script>
@endpush