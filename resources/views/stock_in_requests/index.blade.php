@extends('layouts.app')

@section('css')
    @include('layouts.datatables_css')
@endsection

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ $pageTitle ?? 'Stock-In Approvals' }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-check-square-o"></i>
                            {{ $pageTitle ?? 'Stock-In Approvals' }}
                        </div>
                        <div class="card-body">
                            @if (auth()->check() && auth()->user()->hasRole('admin'))
                                <div class="mb-2" id="bulk-actions-bar">
                                    <button type="button" class="btn btn-success btn-sm" id="bulk-approve-btn" disabled>
                                        <i class="fa fa-check"></i> Approve Selected
                                        <span class="badge badge-light" id="bulk-selected-count">0</span>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" id="bulk-reject-btn" disabled>
                                        <i class="fa fa-times"></i> Reject Selected
                                    </button>
                                </div>
                            @endif
                            {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-bordered'], true) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectStockInModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa fa-times"></i> Reject Stock-In Request</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Rejecting request for batch <strong id="reject-batch-code"></strong>.</p>
                    <div class="form-group">
                        <label for="reject-remark">Reason / Remark <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject-remark" rows="3" maxlength="255"
                                  placeholder="Enter reason for rejection"></textarea>
                    </div>
                    <input type="hidden" id="reject-request-id">
                    <div class="alert alert-danger py-2 mb-0 d-none" id="reject-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-reject-btn">
                        <i class="fa fa-times"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Reject Modal -->
    <div class="modal fade" id="bulkRejectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa fa-times"></i> Reject Selected Requests</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Rejecting <strong id="bulk-reject-count"></strong> selected request(s).</p>
                    <div class="form-group">
                        <label for="bulk-reject-remark">Reason / Remark <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk-reject-remark" rows="3" maxlength="255"
                                  placeholder="Enter reason for rejection (applied to all selected)"></textarea>
                    </div>
                    <div class="alert alert-danger py-2 mb-0 d-none" id="bulk-reject-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-bulk-reject-btn">
                        <i class="fa fa-times"></i> Reject All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter Modal (shared pattern with the Invoice listing) -->
    <div class="modal fade" id="dateModel" tabindex="-1" role="dialog" aria-labelledby="dateModelLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateModelLabel"><i class="fa fa-calendar"></i> Select a Date Range</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input id="columnid" type="hidden" value="">
                    <input id="datefrommodel" type="hidden" value="">
                    <input id="datetomodel" type="hidden" value="">
                    <div class="form-group col-sm-12">
                        <label for="reportrange">Date:</label>
                        <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                            <i class="fa fa-calendar"></i>&nbsp;
                            <span></span> <i class="fa fa-caret-down"></i>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="dateRange();">Search</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Quantity Modal -->
    <div class="modal fade" id="editQtyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-pencil"></i> Edit Quantity</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Batch <strong id="edit-qty-batch-code"></strong></p>
                    <div class="form-group">
                        <label for="edit-qty-input">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit-qty-input" min="1" step="1">
                    </div>
                    <input type="hidden" id="edit-qty-request-id">
                    <div class="alert alert-danger py-2 mb-0 d-none" id="edit-qty-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-edit-qty-btn">
                        <i class="fa fa-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}

    <script>
        // ---- Date column filter (daterangepicker), shared with Invoice listing ----
        function searchDateColumn(el) {
            $('#columnid').val(el.id);
            $('#dateModel').modal('show');
        }

        function parseDMY(str) {
            var parts = str.split("-");
            return new Date(+parts[2], parts[1] - 1, +parts[0]);
        }

        function dateRange(steps = 1) {
            if ($('#datefrommodel').val() == '') {
                noti('i', 'Date From cannot be empty', 'Please select the Date From');
                return;
            }
            if ($('#datetomodel').val() == '') {
                noti('i', 'Date To cannot be empty', 'Please select the Date To');
                return;
            }
            var currentDate = parseDMY($('#datefrommodel').val());
            var endDate = parseDMY($('#datetomodel').val());
            if (endDate < currentDate) {
                noti('i', 'Date From cannot be greater than Date To', 'Please select the Date again');
                return;
            }
            var dateArray = '';
            while (currentDate <= endDate) {
                // Server-side search runs a raw "date REGEXP ?" against the
                // stored datetime column, whose string form is ISO Y-m-d, so
                // the tokens must be YYYY-MM-DD joined by "|" (regex alternation).
                dateArray = dateArray + moment(currentDate).format("YYYY-MM-DD") + '|';
                currentDate.setUTCDate(currentDate.getUTCDate() + steps);
            }
            $('#' + $('#columnid').val()).val(dateArray.substring(0, dateArray.length - 1)).change();
            $('#dateModel').modal('hide');
        }

        (function () {
            var start = moment();
            var end = moment();
            function cb(start, end) {
                $('#reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                $('#datefrommodel').val(start.format('DD-MM-YYYY'));
                $('#datetomodel').val(end.format('DD-MM-YYYY'));
            }
            $('#reportrange').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);
            cb(start, end);
        })();

        $(document).ready(function () {
            var dt = $('#dataTableBuilder').DataTable();

            // ---- Bulk selection state ----
            window.checkboxid = [];

            // Hide the global loading overlay once the table draws (and immediately, as a fallback)
            dt.on('preDraw', function () { ShowLoad(); });
            dt.on('draw', function () {
                // Re-apply selection to the freshly drawn rows (survives paging/search).
                setcheckbox(window.checkboxid);
                checkcheckbox();
                updateBulkBar();
                HideLoad();
            });
            HideLoad();

            // ---- Reset button: clear the visible per-column filter inputs ----
            // (the reset button itself already clears the DataTable searches and redraws)
            $(document).on('click', '.buttons-reset', function () {
                $('#dataTableBuilder tfoot th input').val('');
                $('#dataTableBuilder tfoot th select').val('');
            });

            function reloadTable() {
                dt.ajax.reload(null, false);
            }

            // ---- Checkbox selection helpers (shared pattern) ----
            $(document).on('change', '.checkboxselect', function () {
                if (this.checked) {
                    addcheckboxid($(this).attr('checkboxid'));
                } else {
                    removecheckboxid($(this).attr('checkboxid'));
                }
                checkcheckbox();
                updateBulkBar();
            });

            $(document).on('change', '#selectallcheckbox', function () {
                var checkall = this.checked;
                $('.checkboxselect').each(function (i, obj) {
                    if (checkall && !obj.checked) {
                        addcheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop('checked', true);
                    } else if (!checkall && obj.checked) {
                        removecheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop('checked', false);
                    }
                });
                updateBulkBar();
            });

            function addcheckboxid(id) {
                if ($.inArray(id, window.checkboxid) === -1) {
                    window.checkboxid.push(id);
                }
            }

            function removecheckboxid(id) {
                window.checkboxid = $.grep(window.checkboxid, function (value) {
                    return value != id;
                });
            }

            function setcheckbox(ids) {
                for (var i = 0; i < ids.length; ++i) {
                    $('input.checkboxselect[checkboxid="' + ids[i] + '"]').prop('checked', true);
                }
            }

            function checkcheckbox() {
                var boxes = $('.checkboxselect');
                var checked = boxes.filter(':checked').length;
                $('#selectallcheckbox').prop('checked', boxes.length > 0 && checked === boxes.length);
            }

            function updateBulkBar() {
                var count = window.checkboxid.length;
                $('#bulk-selected-count').text(count);
                $('#bulk-approve-btn, #bulk-reject-btn').prop('disabled', count === 0);
            }

            function clearSelection() {
                window.checkboxid = [];
                $('#selectallcheckbox').prop('checked', false);
                updateBulkBar();
            }

            // ---- Approve ----
            $(document).on('click', '.approve-btn', function () {
                var id = $(this).data('id');
                var batch = $(this).data('batch');
                var qty = $(this).data('quantity');

                if (!confirm('Approve stock-in of ' + qty + ' unit(s) for batch ' + batch + '?\nThis will add the stock immediately.')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ url('stockInRequests') }}/" + id + "/approve",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        toastr.success(response.message, 'Success');
                        reloadTable();
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error approving request';
                        toastr.error(message, 'Error');
                        $btn.prop('disabled', false);
                    }
                });
            });

            // ---- Reject ----
            $(document).on('click', '.reject-btn', function () {
                $('#reject-request-id').val($(this).data('id'));
                $('#reject-batch-code').text($(this).data('batch'));
                $('#reject-remark').val('');
                $('#reject-error').addClass('d-none').text('');
                $('#rejectStockInModal').modal('show');
            });

            $('#confirm-reject-btn').on('click', function () {
                var id = $('#reject-request-id').val();
                var remark = $('#reject-remark').val().trim();

                if (!remark) {
                    $('#reject-error').removeClass('d-none').text('A remark is required to reject.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ url('stockInRequests') }}/" + id + "/reject",
                    type: "POST",
                    data: { approval_remark: remark, _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        $('#rejectStockInModal').modal('hide');
                        toastr.success(response.message, 'Success');
                        reloadTable();
                        $btn.prop('disabled', false);
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error rejecting request';
                        $('#reject-error').removeClass('d-none').text(message);
                        $btn.prop('disabled', false);
                    }
                });
            });

            // ---- Bulk Approve ----
            $('#bulk-approve-btn').on('click', function () {
                var ids = window.checkboxid.slice();
                if (!ids.length) {
                    return;
                }

                if (!confirm('Approve ' + ids.length + ' selected stock-in request(s)?\nThis will add the stock immediately.')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('stockInRequests.bulk-approve') }}",
                    type: "POST",
                    data: { ids: ids, _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        toastr.success(response.message, 'Success');
                        clearSelection();
                        reloadTable();
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error approving requests';
                        toastr.error(message, 'Error');
                        $btn.prop('disabled', false);
                    }
                });
            });

            // ---- Bulk Reject ----
            $('#bulk-reject-btn').on('click', function () {
                if (!window.checkboxid.length) {
                    return;
                }
                $('#bulk-reject-count').text(window.checkboxid.length);
                $('#bulk-reject-remark').val('');
                $('#bulk-reject-error').addClass('d-none').text('');
                $('#bulkRejectModal').modal('show');
            });

            $('#confirm-bulk-reject-btn').on('click', function () {
                var ids = window.checkboxid.slice();
                var remark = $('#bulk-reject-remark').val().trim();

                if (!ids.length) {
                    $('#bulk-reject-error').removeClass('d-none').text('No requests selected.');
                    return;
                }
                if (!remark) {
                    $('#bulk-reject-error').removeClass('d-none').text('A remark is required to reject.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('stockInRequests.bulk-reject') }}",
                    type: "POST",
                    data: { ids: ids, approval_remark: remark, _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        $('#bulkRejectModal').modal('hide');
                        toastr.success(response.message, 'Success');
                        clearSelection();
                        reloadTable();
                        $btn.prop('disabled', false);
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error rejecting requests';
                        $('#bulk-reject-error').removeClass('d-none').text(message);
                        $btn.prop('disabled', false);
                    }
                });
            });

            // ---- Edit Quantity ----
            $(document).on('click', '.edit-qty-btn', function () {
                $('#edit-qty-request-id').val($(this).data('id'));
                $('#edit-qty-batch-code').text($(this).data('batch'));
                $('#edit-qty-input').val($(this).data('quantity'));
                $('#edit-qty-error').addClass('d-none').text('');
                $('#editQtyModal').modal('show');
            });

            $('#confirm-edit-qty-btn').on('click', function () {
                var id = $('#edit-qty-request-id').val();
                var qty = parseInt($('#edit-qty-input').val(), 10);

                if (!qty || qty < 1) {
                    $('#edit-qty-error').removeClass('d-none').text('Enter a quantity of at least 1.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ url('stockInRequests') }}/" + id + "/update-qty",
                    type: "POST",
                    data: { quantity: qty, _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        $('#editQtyModal').modal('hide');
                        toastr.success(response.message, 'Success');
                        reloadTable();
                        $btn.prop('disabled', false);
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error updating quantity';
                        $('#edit-qty-error').removeClass('d-none').text(message);
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
