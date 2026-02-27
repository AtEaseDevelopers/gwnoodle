<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', __('assign.driver')) !!}<span class="asterisk"> *</span>
    {!! Form::select('driver_id', $driverItems, null, [
        'class' => 'form-control select2-driver',
        'placeholder' => __('assign.placeholder_pick_driver'),
        'autofocus',
        'required' => true
    ]) !!}
</div>

<!-- Customer Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('group', __('assign.group')) !!}<span class="asterisk"> *</span>
    {!! Form::select('group', $groups, null, [
        'class' => 'form-control select2-group',
        'placeholder' => __('assign.placeholder_pick_group'),
        'required' => true
    ]) !!}
</div>

<div class="form-group col-sm-12" id="sequence_details">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('assign.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('assigns.index') }}" class="btn btn-secondary">{{ __('assign.cancel') }}</a>
</div>

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <style>
        /* Style the Select2 dropdowns to match your theme */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: .25rem;
            height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        
        /* Draggable table rows */
        #sortable-table tbody tr {
            cursor: move;
        }
        
        #sortable-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .drag-handle {
            cursor: move;
            color: #999;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .drag-handle:hover {
            color: #333;
        }
        
        .sequence-input {
            width: 80px;
            text-align: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        
        $(document).ready(function() {
            // Initialize Select2 for driver field
            $('.select2-driver').select2({
                placeholder: "Search for a driver...",
                allowClear: true,
                width: '100%'
            });
            
            // Initialize Select2 for group field
            $('.select2-group').select2({
                placeholder: "Search for a group...",
                allowClear: true,
                width: '100%'
            });
            
            // Load initial data if editing
            var driverId = $('select[name="driver_id"]').val();
            var groupId = $('#group').val();
            if (driverId && groupId) {
                loadSequenceData(groupId, driverId);
            }
            
            HideLoad();
        });

        $('#group').on('change', function() {
            var driverId = $('select[name="driver_id"]').val();
            if (!driverId) {
                noti('w', 'Warning', 'Please select a driver first');
                return;
            }
            loadSequenceData($(this).val(), driverId);
        });

        $('select[name="driver_id"]').on('change', function() {
            var groupId = $('#group').val();
            if (groupId) {
                loadSequenceData(groupId, $(this).val());
            }
        });

        function loadSequenceData(groupId, driverId) {
            if (!groupId || !driverId) return;
            
            ShowLoad();
            $('#sequence_details').html('');
            
            $.ajax({
                type: 'POST',
                url: '{{ route('assigns.customerfindgroup') }}',
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'group_id': groupId,
                    'driver_id': driverId
                },
                success: function(response) {
                    if (response.status) {
                        renderCustomerTable(response.data);
                        initializeDragAndDrop();
                    } else {
                        noti('w', 'Warning', response.message);
                    }
                    HideLoad();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    noti('e', 'Server Error', errorThrown);
                    HideLoad();
                }
            });
        }

        function renderCustomerTable(customers) {
            var result = `
                <div class="card">
                    <div class="card-header">
                        <h4>{{ __('Customer Sequence') }}</h4>
                        <p class="text-muted">{{ __('Drag to Reorder') }}</p>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-bordered" id="sortable-table" width="100%">
                            <thead>
                                <tr>
                                    <th width="50"></th>
                                    <th>{{ __('assign.company') }}</th>
                                    <th width="150">{{ __('assign.sequence') }}</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            $.each(customers, function(index, customer) {
                result = result + generateRow(customer, index + 1);
            });
            
            result = result + `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            $('#sequence_details').html(result);
        }

        function generateRow(customer, sequence) {
            return `
                <tr data-customer-id="${customer.id}" data-original-sequence="${sequence}">
                    <td class="text-center">
                        <span class="drag-handle">☰</span>
                    </td>
                    <td>
                        <input type="hidden" name="customer[]" value="${customer.id}">
                        ${customer.company}
                    </td>
                    <td>
                        <input type="number" 
                               class="form-control sequence-input" 
                               name="sequence[]" 
                               value="${sequence}" 
                               min="1" 
                               step="1"
                               readonly>
                    </td>
                </tr>
            `;
        }

        function initializeDragAndDrop() {
            $("#sortable-table tbody").sortable({
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function(event, ui) {
                    updateSequences();
                },
                cursor: 'move',
                opacity: 0.6,
                placeholder: 'sortable-placeholder',
                tolerance: 'pointer'
            }).disableSelection();
        }

        function updateSequences() {
            $('#sortable-table tbody tr').each(function(index) {
                var newSequence = index + 1;
                $(this).find('input[name="sequence[]"]').val(newSequence);
                $(this).attr('data-original-sequence', newSequence);
            });
            
            // Optional: Show a notification that sequences were updated
            noti('s', 'Success', 'Sequences updated successfully');
        }
    </script>
@endpush