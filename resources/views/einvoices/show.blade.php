@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('einvoices.index') }}">E-Invoice</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>E-Invoice Details</strong>
                                  <a href="{{ route('einvoices.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-6">
                                         <strong>SKU:</strong> {{ $einvoice->sku }}<br>
                                         <strong>Status:</strong> 
                                         <span class="badge badge-{{ $einvoice->status === 'Valid' ? 'success' : 'danger' }}">
                                             {{ $einvoice->status }}
                                         </span><br>
                                         <strong>Currency:</strong> {{ strtoupper($einvoice->currency) }}<br>
                                         <strong>With SG GST:</strong> {{ $einvoice->with_sg_gst ? 'Yes' : 'No' }}<br>
                                     </div>
                                     <div class="col-md-6">
                                         @if($einvoice->invoice)
                                             <strong>Invoice:</strong> {{ $einvoice->invoice->invoiceno ?? 'N/A' }}<br>
                                             @if($einvoice->invoice->customer)
                                                 <strong>Customer:</strong> {{ $einvoice->invoice->customer->company ?? 'N/A' }}<br>
                                             @endif
                                         @endif
                                         @if($einvoice->uuid)
                                             <strong>UUID:</strong> {{ $einvoice->uuid }}<br>
                                         @endif
                                         @if($einvoice->longId)
                                             <strong>Long ID:</strong> {{ $einvoice->longId }}<br>
                                         @endif
                                         @if($einvoice->submission_date)
                                             <strong>Submission Date:</strong> {{ $einvoice->submission_date->format('d-m-Y H:i:s') }}<br>
                                         @endif
                                         @if($einvoice->validated_time)
                                             <strong>Validated Time:</strong> {{ $einvoice->validated_time->format('d-m-Y H:i:s') }}<br>
                                         @endif
                                     </div>
                                 </div>
                                 @if($einvoice->uuid && $einvoice->longId)
                                     <hr>
                                     <div class="row">
                                         <div class="col-md-12">
                                             <a href="{{ $einvoice->getValidationLink() }}" target="_blank" class="btn btn-primary">
                                                 <i class="fa fa-external-link"></i> View in MyInvois Portal
                                             </a>
                                             <button type="button" class="btn btn-info" onclick="refreshStatus('{{ Crypt::encrypt($einvoice->id) }}')">
                                                 <i class="fa fa-refresh"></i> Refresh Status
                                             </button>
                                         </div>
                                     </div>
                                 @endif

                                 @if($einvoice->uuid)
                                     <hr>
                                     <div class="row">
                                         <div class="col-md-12">
                                             <h5>MyInvois API Response Details</h5>
                                             <div class="card">
                                                 <div class="card-body" id="api-details-container">
                                                     <div class="text-center">
                                                         <div class="spinner-border" role="status">
                                                             <span class="sr-only">Loading...</span>
                                                         </div>
                                                         <p class="mt-2">Loading API details...</p>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 @endif
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            HideLoad();
            @if($einvoice->uuid)
                loadApiDetails('{{ Crypt::encrypt($einvoice->id) }}');
            @endif
        });

        function loadApiDetails(id) {
            $.ajax({
                url: "{{ url('/einvoices') }}/" + id + "/details",
                type: "GET",
                timeout: 30000,
                success: function(response){
                    if(response.success && response.details){
                        renderApiDetails(response.details);
                    } else {
                        $('#api-details-container').html('<p class="text-muted">Failed to load API details. ' + (response.message || '') + '</p>');
                    }
                },
                error: function(error) {
                    var errorMsg = 'Unknown error';
                    if(error.status === 0) {
                        errorMsg = 'Request timeout or connection error';
                    } else if(error.responseJSON && error.responseJSON.message) {
                        errorMsg = error.responseJSON.message;
                    } else if(error.statusText) {
                        errorMsg = error.statusText;
                    }
                    $('#api-details-container').html('<p class="text-danger">Error loading API details: ' + errorMsg + '</p>');
                }
            });
        }

        function renderApiDetails(apiDetails) {
            var html = '<div class="row">';
            html += '<div class="col-md-6">';
            html += '<table class="table table-sm table-borderless">';
            html += '<tr><th width="40%">Submission UID:</th><td>' + (apiDetails.submissionUid || 'N/A') + '</td></tr>';
            html += '<tr><th>Internal ID:</th><td>' + (apiDetails.internalId || 'N/A') + '</td></tr>';
            html += '<tr><th>Type Name:</th><td>' + (apiDetails.typeName || 'N/A') + '</td></tr>';
            html += '<tr><th>Type Version:</th><td>' + (apiDetails.typeVersionName || 'N/A') + '</td></tr>';
            html += '<tr><th>Issuer TIN:</th><td>' + (apiDetails.issuerTin || 'N/A') + '</td></tr>';
            html += '<tr><th>Issuer Name:</th><td>' + (apiDetails.issuerName || 'N/A') + '</td></tr>';
            html += '<tr><th>Receiver ID:</th><td>' + (apiDetails.receiverId || 'N/A') + '</td></tr>';
            html += '<tr><th>Receiver Name:</th><td>' + (apiDetails.receiverName || 'N/A') + '</td></tr>';
            html += '<tr><th>Created By User ID:</th><td>' + (apiDetails.createdByUserId || 'N/A') + '</td></tr>';
            html += '</table></div>';
            html += '<div class="col-md-6">';
            html += '<table class="table table-sm table-borderless">';
            html += '<tr><th width="40%">Date Time Issued:</th><td>' + (apiDetails.dateTimeIssued || 'N/A') + '</td></tr>';
            html += '<tr><th>Date Time Received:</th><td>' + (apiDetails.dateTimeReceived || 'N/A') + '</td></tr>';
            html += '<tr><th>Date Time Validated:</th><td>' + (apiDetails.dateTimeValidated || 'N/A') + '</td></tr>';
            if(apiDetails.cancelDateTime) {
                html += '<tr><th>Cancel Date Time:</th><td>' + apiDetails.cancelDateTime + '</td></tr>';
            }
            if(apiDetails.rejectRequestDateTime) {
                html += '<tr><th>Reject Request Date Time:</th><td>' + apiDetails.rejectRequestDateTime + '</td></tr>';
            }
            if(apiDetails.documentStatusReason) {
                html += '<tr><th>Status Reason:</th><td>' + apiDetails.documentStatusReason + '</td></tr>';
            }
            html += '</table></div></div>';
            html += '<hr>';
            html += '<div class="row"><div class="col-md-12">';
            html += '<h6>Financial Details</h6>';
            html += '<table class="table table-sm table-borderless">';
            html += '<tr><th width="30%">Total Excluding Tax:</th><td>MYR ' + parseFloat(apiDetails.totalExcludingTax || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</td></tr>';
            html += '<tr><th>Total Discount:</th><td>MYR ' + parseFloat(apiDetails.totalDiscount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</td></tr>';
            html += '<tr><th>Total Net Amount:</th><td>MYR ' + parseFloat(apiDetails.totalNetAmount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</td></tr>';
            html += '<tr><th>Total Payable Amount:</th><td><strong>MYR ' + parseFloat(apiDetails.totalPayableAmount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</strong></td></tr>';
            html += '</table></div></div>';
            if(apiDetails.validationResults && apiDetails.validationResults.validationSteps) {
                html += '<hr>';
                html += '<div class="row"><div class="col-md-12">';
                html += '<h6>Validation Results</h6>';
                html += '<p><strong>Status:</strong> ';
                var statusClass = (apiDetails.validationResults.status === 'Valid') ? 'success' : 'danger';
                html += '<span class="badge badge-' + statusClass + '">' + (apiDetails.validationResults.status || 'N/A') + '</span></p>';
                if(apiDetails.validationResults.validationSteps.length > 0) {
                    html += '<table class="table table-sm table-bordered">';
                    html += '<thead><tr><th>Validation Step</th><th>Status</th>';
                    if(apiDetails.validationResults.validationSteps[0].error) {
                        html += '<th>Error</th>';
                    }
                    html += '</tr></thead><tbody>';
                    apiDetails.validationResults.validationSteps.forEach(function(step) {
                        html += '<tr><td>' + (step.name || 'N/A') + '</td>';
                        var stepStatusClass = (step.status === 'Valid') ? 'success' : 'danger';
                        html += '<td><span class="badge badge-' + stepStatusClass + '">' + (step.status || 'N/A') + '</span></td>';
                        if(apiDetails.validationResults.validationSteps[0].error) {
                            html += '<td>' + (step.error ? JSON.stringify(step.error, null, 2) : '-') + '</td>';
                        }
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div></div>';
            }
            $('#api-details-container').html(html);
        }

        function refreshStatus(id){
            ShowLoad();
            $.ajax({
                url: "{{ url('/einvoices') }}/" + id + "/refresh-status",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response){
                    HideLoad();
                    if(response.success){
                        location.reload();
                    } else {
                        noti('e','Error', response.message);
                    }
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Error', error.responseJSON?.message || 'Failed to refresh status');
                }
            });
        }
    </script>
@endpush

