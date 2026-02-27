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
                                <form method="POST" action="../reports/run" accept-charset="UTF-8">
                                    @csrf {{ csrf_field() }}
                                    <input type="hidden" name="_report_id" value="{{ $report->id }}">
                                    @php
                                        $scripts = '';
                                        foreach($reportdetails as $reportdetail){
                                            $required = $reportdetail['name'] === 'p_agent' ? '' : 'required';
                                            
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
                                                        <select '.$required.' class="form-control selectpicker" id="'.$reportdetail['name'].'" name="'.$reportdetail['name'].'[]" tabindex="null" data-live-search="true" multiple>
                                                            '.$all_option.'
                                                            '.$option.'
                                                        </select>
                                                    </div>';
                                            }
                                        }
                                    @endphp
                                    <div class="form-group col-sm-12">
                                        <input class="btn btn-primary" type="submit" value="Run Report">
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