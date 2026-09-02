@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ $report->name }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ $report->name }}</strong>
                                  <a href="{{ route('reports.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                <form method="POST" action="../reports/run" accept-charset="UTF-8" id="reportForm">
                                    @csrf {{ csrf_field() }}
                                    <input type="hidden" name="_report_id" value="{{ $report->id }}">
                                    @php
                                        $scripts = '';
                                        foreach($reportdetails as $reportdetail){
                                            // Check if the field should be optional based on title
                                            $isProductBatch = isset($reportdetail['title']) && $reportdetail['title'] == 'Product Batch';
                                            $required = $reportdetail['name'] === 'p_agent' ? '' : 'required';

                                            // Override required for Product Batch multiselect
                                            if($isProductBatch && $reportdetail['type'] == 'multiselect') {
                                                $required = '';
                                            }
                                            
                                            if($reportdetail['type'] == 'textbox'){
                                                echo '<div class="form-group col-sm-6">
                                                        <label for="'.$reportdetail['name'].'">'.$reportdetail['title'].':</label>
                                                        '.($required ? '<span class="asterisk"> *</span>' : '').'
                                                        <input '.$required.' class="form-control" name="'.$reportdetail['name'].'" type="text" id="'.$reportdetail['name'].'" value="'.$reportdetail['data'].'">
                                                    </div>';
                                            }else if($reportdetail['type'] == 'date'){
                                                echo '<div class="form-group col-sm-6">
                                                        <label for="'.$reportdetail['name'].'">'.$reportdetail['title'].':</label>
                                                        '.($required ? '<span class="asterisk"> *</span>' : '').'
                                                        <input '.$required.' class="form-control reportdate" id="'.$reportdetail['name'].'" name="'.$reportdetail['name'].'" type="text">
                                                    </div>';
                                            }else if($reportdetail['type'] == 'dropdown'){
                                                $option = '';
                                                try{
                                                    foreach ($reportdetail['data'] as $key => $value) {
                                                        $option = $option . '<option value="'.$value.'">'.$key.'</option>';
                                                    }
                                                }
                                                catch(Exception $e) {
                                                    echo "<script>console.log('%c ERROR: ".$reportdetail['name']." was missing','color: #FF0000')</script>";
                                                }
                                                echo '<div class="form-group col-sm-6">
                                                        <label for="'.$reportdetail['name'].'">'.$reportdetail['title'].':</label>
                                                        '.($required ? '<span class="asterisk"> *</span>' : '').'
                                                        <select '.$required.' class="form-control selectpicker" id="'.$reportdetail['name'].'" name="'.$reportdetail['name'].'" tabindex="null" data-live-search="true">
                                                            <option value="">Pick a '.$reportdetail['name'].'...</option>
                                                            '.$option.'
                                                        </select>
                                                    </div>';
                                            }else if($reportdetail['type'] == 'multiselect'){
                                                $option = '';
                                                $all_option = ($reportdetail['STR_UDF1'] == 'Y') ? '<option selected value="%">ALL</option>' : '';
                                                try{
                                                    foreach ($reportdetail['data'] as $key => $value) {
                                                        $option = $option . '<option value="'.$value.'">'.$key.'</option>';
                                                    }
                                                }
                                                catch(Exception $e) {
                                                    echo "<script>console.log('%c ERROR: ".$reportdetail['name']." was missing','color: #FF0000')</script>";
                                                }
                                                echo '<div class="form-group col-sm-6">
                                                        <label for="'.$reportdetail['name'].'">'.$reportdetail['title'].':</label>
                                                        '.($required ? '<span class="asterisk"> *</span>' : '').'
                                                        <select '.$required.' class="form-control selectpicker" id="'.$reportdetail['name'].'" name="'.$reportdetail['name'].'[]" tabindex="null" data-live-search="true" data-actions-box="true" data-select-all-text="Select All" data-deselect-all-text="Deselect All" multiple>
                                                            '.$all_option.'
                                                            '.$option.'
                                                        </select>
                                                        '.(!$required ? '<small class="form-text text-muted">This field is optional. Leave empty to include all batches.</small>' : '').'
                                                    </div>';
                                            }else if($reportdetail['type'] == 'singleselect'){
                                                // Single select dropdown (only one selection allowed, no "ALL" option by default)
                                                $option = '';
                                                $includeAllOption = ($reportdetail['STR_UDF1'] == 'Y') ? true : false;
                                                
                                                try{
                                                    foreach ($reportdetail['data'] as $key => $value) {
                                                        $selected = '';
                                                        // Check if this option should be preselected
                                                        if(isset($reportdetail['default_value']) && $reportdetail['default_value'] == $value) {
                                                            $selected = 'selected';
                                                        }
                                                        $option = $option . '<option value="'.$value.'" '.$selected.'>'.$key.'</option>';
                                                    }
                                                }
                                                catch(Exception $e) {
                                                    echo "<script>console.log('%c ERROR: ".$reportdetail['name']." was missing','color: #FF0000')</script>";
                                                }
                                                
                                                echo '<div class="form-group col-sm-6">
                                                        <label for="'.$reportdetail['name'].'">'.$reportdetail['title'].':</label>
                                                        '.($required ? '<span class="asterisk"> *</span>' : '').'
                                                        <select '.$required.' class="form-control selectpicker" id="'.$reportdetail['name'].'" name="'.$reportdetail['name'].'" tabindex="null" data-live-search="true">
                                                            '.(!$required ? '<option value="">None</option>' : '').'
                                                            '.($includeAllOption ? '<option value="%">ALL</option>' : '').'
                                                            '.$option.'
                                                        </select>
                                                    </div>';
                                            }
                                        }
                                    @endphp
                                    <div class="form-group col-sm-12">
                                        <input class="btn btn-primary" type="submit" value="Run Report" formtarget="_blank">
                                    </div>
                                </form>
                                 {{-- @include('reports.show_fields') --}}
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
            
            // Optional: Add client-side validation to remove 'required' attribute for Product Batch
            $('select[name="product_batch[]"]').each(function() {
                if ($(this).closest('.form-group').find('label').text().trim() === 'Product Batch') {
                    $(this).removeAttr('required');
                }
            });
        });
        
        // Add form submission handling to ensure empty multiselect sends empty value
        $('#reportForm').on('submit', function() {
            $('select[name="product_batch[]"]').each(function() {
                if ($(this).val() === null || $(this).val().length === 0) {
                    // Create a hidden input to submit empty value
                    $(this).after('<input type="hidden" name="' + $(this).attr('name') + '" value="">');
                    $(this).prop('disabled', true);
                }
            });
        });
        
        $('.form-control.reportdate').datetimepicker({
            format: 'YYYY-MM-DD',
            useCurrent: true,
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            },
            sideBySide: true
        })
    </script>
@endpush