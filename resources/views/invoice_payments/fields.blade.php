<!-- Customer Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('customer_id', 'Customer:') !!}<span class="asterisk"> *</span>
    {!! Form::select('customer_id', $customerItems, null, [
        'class' => 'form-control selectpicker', 
        'data-live-search' => 'true', 
        'placeholder' => 'Pick a Customer...',
        'autofocus',
        
    ]) !!}
    
    @if(isset($invoicePayment))
        <input type="hidden" name="customer_id" value="{{ $invoicePayment->customer_id }}">
    @endif
</div>

<!-- Invoice Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('invoice_id', 'Invoice:') !!}
    <select name="invoice_id[]" id="invoice_id" class="form-control selectpicker" multiple data-live-search="true" 
        {{ isset($invoicePayment) ? 'disabled' : '' }}>
        <option disabled>Pick an Invoice...</option>

        @if (isset($invoices))
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" {{ isset($selectedInvoices) && in_array($invoice->id, $selectedInvoices) ? 'selected' : '' }}>
                    {{ $invoice->invoiceno }} - RM {{ number_format($invoice->total_amount, 2) }} - {{ $invoice->date }}
                </option>
            @endforeach
        @endif
    </select>
    
    @if(isset($invoicePayment) && isset($selectedInvoices))
        @foreach($selectedInvoices as $selectedInvoice)
            <input type="hidden" name="invoice_id[]" value="{{ $selectedInvoice }}">
        @endforeach
    @endif
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}<span class="asterisk"> *</span>
    {{ Form::select('type', array(1 => 'Cash' , 3 => 'Online BankIn' , 4 => 'E-wallet', 5 => 'Cheque'), null, ['class' => 'form-control']) }}
</div>

<!-- ChequeNo Field -->
<div class="form-group col-sm-6" id='cheque-container' style='display:none;'>
    {!! Form::label('chequeno', 'Cheque No.:') !!}
    {!! Form::text('chequeno', null, ['class' => 'form-control','maxlength' => 20,'maxlength' => 20]) !!}
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:') !!}<span class="asterisk"> *</span>
    {!! Form::text('amount', null, ['class' => 'form-control','min' => 0, 'step' => 0.01]) !!}
</div>

@can('paymentapprove')
<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {{ Form::select('status', array(0 => 'New', 1 => 'Completed', 2 => 'Cancelled'), null, ['class' => 'form-control']) }}
</div>
@endcan

<!-- Attachment Field -->
<div class="form-group col-sm-6">
    {!! Form::label('attachment', 'Attachment:') !!}
    
    @if(isset($invoicePayment) && $invoicePayment->attachment)
    <div class="mb-2">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    @php
                        $extension = pathinfo($invoicePayment->attachment, PATHINFO_EXTENSION);
                    @endphp
                    
                    @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                        <a href="{{ asset('/' . $invoicePayment->attachment) }}" target="_blank">
                            <img src="{{ asset('/' . $invoicePayment->attachment) }}" alt="Attachment" style="max-width: 100px; max-height: 100px;" class="img-thumbnail">
                        </a>
                    @elseif(strtolower($extension) == 'pdf')
                        <i class="fa fa-file-pdf-o text-danger" style="font-size: 48px;"></i>
                    @else
                        <i class="fa fa-file-o text-secondary" style="font-size: 48px;"></i>
                    @endif
                    
                    <div class="ml-3">
                        <h6 class="mb-1">Current Attachment:</h6>
                        <a href="{{ asset('/' . $invoicePayment->attachment) }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fa fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="custom-file">
        <input type="file" class="custom-file-input" name="attachment" id="attachment" enctype="multipart/form-data" accept=".jpg, .jpeg, .png, .pdf">
        <label id="attachment-label" class="custom-file-label" for="attachment">
            {{ isset($invoicePayment) && $invoicePayment->attachment ? 'Choose new file...' : 'Choose file' }}
        </label>
    </div>
    
    @if(isset($invoicePayment) && $invoicePayment->attachment)
    <small class="text-muted">
        <i class="fa fa-info-circle"></i> Leave empty to keep current attachment
    </small>
    @endif
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', 'Remark:') !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('invoicePayments.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        
        $(document).ready(function () {
            HideLoad();
            
            // Handle cheque container visibility on page load
            if ($('#type').val() == "5") {
                $('#cheque-container').show();
            }
        });
        
        $("#attachment").on("change", function(){
            if(this.files && this.files[0]) {
                var fileName = this.files[0].name;
                $('#attachment-label').html(fileName);
            } else {
                $('#attachment-label').html('{{ isset($invoicePayment) && $invoicePayment->attachment ? "Choose new file..." : "Choose file" }}');
            }
        });
        
        var isEditMode = @json(isset($invoicePayment));
        
        $(document).ready(function () {
            if (isEditMode) {
                // In edit mode, disable the customer and invoice fields
                $('#customer_id').prop('disabled', true);
                $('#invoice_id').prop('disabled', true);
                $('#customer_id').selectpicker('refresh');
                $('#invoice_id').selectpicker('refresh');
                
                // Show cheque container if cheque type is selected
                if ($('#type').val() == "5") {
                    $('#cheque-container').show();
                }
            }
        });
        
        // Only run these functions in create mode
        @if(!isset($invoicePayment))
        $("#invoice_id").on("change", function(){
            getinvoice();
        });

        $("#customer_id").change(function(){
            ShowLoad();
            let customerId = $('#customer_id').val();

            if (customerId === '') {
                var o = '<option disabled>Pick an Invoice...</option>';
                $('select[name="invoice_id[]"]').html(o);
                $('select[name="invoice_id[]"]').selectpicker('refresh');
                HideLoad();
            } else {
                var url = '/invoicePayments/customer-invoices/' + customerId;
                $.get(url, function(data, status){
                    if (status === 'success') {
                    if (data.status) {
                            var o = '<option disabled>Pick an Invoice...</option>';
                            $.each(data.data, function(key, invoice) {
                                o += `<option value="${invoice.id}">
                                        ${invoice.invoiceno} - RM ${invoice.total_amount.toFixed(2)} - ${invoice.date}
                                    </option>`;
                            });

                            $('select[name="invoice_id[]"]').html(o);
                            $('select[name="invoice_id[]"]').selectpicker('refresh');
                        } else {
                            noti('e', 'Please contact your administrator', data.message);
                        }
                    } else {
                        noti('e', 'Please contact your administrator', '');
                    }
                    HideLoad();
                });
            }
        });

        function getinvoice(){
            var invoice_ids = $('#invoice_id').val();
            if(invoice_ids.length > 0){
                ShowLoad();
                var url = '/invoicePayments/getinvoice';
                var params = $.param({invoice_ids: invoice_ids});
                $.get(url + '?' + params, function(data, status){
                    if(status == 'success'){
                        if(data.status){
                            var customer_id = data.data[0].customer_id;
                            var totalAmount = 0;
                            data.data.forEach((invoice) => {
                                totalAmount += invoice.invoicedetail.reduce((sum, item) => sum + item.totalprice, 0);
                            });
                            $('#customer_id').val(customer_id);
                            $('#customer_id').selectpicker('refresh');
                            $('#amount').val(totalAmount.toFixed(2));
                        }else{
                            noti('e','Please contact your administrator',data.message);
                        }
                        HideLoad();
                    }else{
                        noti('e','Please contact your administrator','')
                        HideLoad();
                    }
                });

            }
        }
        @endif
        
        $('#type').change(function(){
            if($(this).val() == "5")
            {
                $('#cheque-container').show();
            }
            else
            {
                $('#cheque-container').hide();
            }
        });
    </script>
@endpush