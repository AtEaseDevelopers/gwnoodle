@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('activitylogs.index') }}">Activity Logs</a>
        </li>
        <li class="breadcrumb-item active">Activity Log Details</li>
    </ol>
    
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Activity Log Information</strong>
                            <a href="{{ route('activitylogs.index') }}" class="btn btn-light float-right">Back</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('created_at', 'Date Time') !!}:
                                        <p class="form-control-static">{{ $log->created_at->format('Y-m-d H:i:s') }}</p>
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('user', 'User') !!}:
                                        <p class="form-control-static">{{ $log->user_name ?? ($log->user->name ?? 'System') }}</p>
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('action', 'Action') !!}:
                                        <p class="form-control-static">
                                            @php
                                                $badgeClass = match($log->action) {
                                                    'create' => 'success',
                                                    'update' => 'info',
                                                    'delete' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}" style="padding: 5px 10px;">
                                                {{ ucfirst($log->action) }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('module', 'Module') !!}:
                                        <p class="form-control-static">{{ ucwords(str_replace('_', ' ', $log->module)) }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($log->old_data || $log->new_data)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Data Changes</strong>
                                <ul class="nav nav-tabs card-header-tabs float-right" id="dataTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="compare-tab" data-toggle="tab" href="#compare" role="tab">Compare View</a>
                                    </li>
                           
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="dataTabsContent">
                                    <!-- Compare View Tab -->
                                    <div class="tab-pane fade show active" id="compare" role="tabpanel">
                                        @if($log->old_data && $log->new_data)
                                            <table class="table table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="25%">Field</th>
                                                        <th width="37.5%">Old Value</th>
                                                        <th width="37.5%">New Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $allFields = array_unique(array_merge(
                                                            array_keys((array)$log->old_data), 
                                                            array_keys((array)$log->new_data)
                                                        ));
                                                        sort($allFields);
                                                    @endphp
                                                    
                                                    @foreach($allFields as $field)
                                                        @php
                                                            $oldValue = $log->old_data[$field] ?? null;
                                                            $newValue = $log->new_data[$field] ?? null;
                                                            $hasChanged = ($oldValue != $newValue);
                                                        @endphp
                                                        
                                                        <tr class="{{ $hasChanged ? 'table-warning' : '' }}">
                                                            <td><strong>{{ ucwords(str_replace('_', ' ', $field)) }}</strong></td>
                                                            <td>
                                                                @if(is_array($oldValue) || is_object($oldValue))
                                                                    <pre class="mb-0"><code>{{ json_encode($oldValue, JSON_PRETTY_PRINT) }}</code></pre>
                                                                @elseif($oldValue === null)
                                                                    <em class="text-muted">null</em>
                                                                @else
                                                                    {{ $oldValue }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(is_array($newValue) || is_object($newValue))
                                                                    <pre class="mb-0"><code>{{ json_encode($newValue, JSON_PRETTY_PRINT) }}</code></pre>
                                                                @elseif($newValue === null)
                                                                    <em class="text-muted">null</em>
                                                                @else
                                                                    {{ $newValue }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="alert alert-info">
                                                Cannot display compare view - both old and new data are required.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($log->user_agent)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Additional Information</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    {!! Form::label('user_agent', 'User Agent') !!}:
                                    <p class="form-control-static">{{ $log->user_agent }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                window.location.href = '{{ route("activitylogs.index") }}';
            }
        });
        
        $(document).ready(function () {
            HideLoad();
            
            // Initialize tabs
            $('#dataTabs a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
        });
    </script>
@endpush