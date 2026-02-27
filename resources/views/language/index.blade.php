@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('language_translation.language_translation') }}</h4>
                </div>
                @include('flash::message')

                <div class="card-body">
                    <div class="row">
                        <!-- Left Side - System Language and Management -->
                        <div class="col-md-6">
                            <!-- System Language Selection -->
                            <div class="border p-3 rounded bg-light mb-3">
                                <h5 class="mb-3">{{ __('language_translation.system_language') }}</h5>
                                <form method="POST" action="{{ route('language.change') }}" class="form-inline">
                                    @csrf
                                    <div class="form-group w-100">
                                        <label class="mr-2">{{ __('language_translation.select_system_language') }}:</label>
                                        <select name="language" class="form-control" id="system-language" required>
                                            <option value="">{{ __('language_translation.choose_language') }}</option>
                                            @foreach($availableSystemLanguages as $language)
                                                <option value="{{ $language->code }}" 
                                                    {{ $language->code == app()->getLocale() ? 'selected' : '' }}>
                                                    {{ $language->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            {{ __('language_translation.apply') }}
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted">{{ __('language_translation.changes_will_affect_the_entire_system_interface') }}</small>
                            </div>
                            
                            <!-- Manage Languages -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>{{ __('language_translation.manage_languages') }}</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                        <table class="table table-bordered mb-0 ">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>{{ __('language_translation.language_name') }}</th>
                                                    <th>{{ __('language_translation.actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($availableSystemLanguages as $language)
                                                    <tr>
                                                        <td>{{ $language->name }}</td>
                                                        <td>
                                                            @if($language->code !== 'en')
                                                                <form action="{{ route('language.delete', $language->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('{{ __('language_translation.confirm_delete_language') }}')">
                                                                        <i class="fas fa-trash"></i> {{ __('language_translation.delete') }}
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <button class="btn btn-sm btn-secondary" disabled>
                                                                    <i class="fas fa-lock"></i> {{ __('language_translation.protected') }}
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Import/Export Operations -->
                        <div class="col-md-6">
                            <!-- Import New Language -->
                            <div class="border p-3 rounded bg-light mb-3">
                                <h5 class="mb-3">{{ __('language_translation.import_new_language') }}</h5>
                                <form method="POST" action="{{ route('language.import') }}" class="form-inline" id="import-language-form">
                                    @csrf
                                    <div class="form-group w-100">
                                        <label class="mr-2">{{ __('language_translation.select_language_to_import') }}:</label>
                                        <select name="language" class="form-control language-filter" id="import-language" required>
                                            <option value="">{{ __('language_translation.choose_language') }}</option>
                                            @foreach($languages as $language)
                                                @unless($availableSystemLanguages->contains('code', $language->code))
                                                    <option value="{{ $language->code }}">
                                                        {{ $language->name }}
                                                    </option>
                                                @endunless
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary ml-2">
                                        {{ __('language_translation.import') }}
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted">{{ __('language_translation.only_shows_languages_not_already_imported') }}</small>
                            </div>

                            <!-- Export Translations -->
                            <div class="border p-3 rounded bg-light mb-3">
                                <h5 class="mb-3">{{ __('language_translation.export_translations') }}</h5>
                                <form method="POST" action="{{ route('language.export') }}" class="form-inline">
                                    @csrf
                                    <div class="form-group w-100">
                                        <label class="mr-2">{{ __('language_translation.select_language_to_export') }}:</label>
                                        <select name="language" class="form-control" required>
                                            <option value="">{{ __('language_translation.choose_language') }}</option>
                                            <option value="en">
                                                English
                                            </option>
                                        </select>
                                        <select name="format" class="form-control ml-2" required>
                                            <option value="json">JSON</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            {{ __('language_translation.export') }}
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted">{{ __('language_translation.export_all_translations_for_selected_language') }}</small>
                            </div>

                            <!-- Bulk Import Translations -->
                            <div class="border p-3 rounded bg-light">
                                <h5 class="mb-3">{{ __('mobile_language_translation.bulk_import_translations') }}</h5>
                                <form method="POST" action="{{ route('mobile_language.import.file') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-row align-items-center">
                                        <div class="col-md-8 mb-2">
                                            <select name="language" class="form-control @error('language') is-invalid @enderror" required>
                                                <option value="">{{ __('mobile_language_translation.choose_language') }}</option>
                                                @foreach($languages as $language)
                                                    @unless($availableSystemLanguages->contains('code', $language->code))
                                                        <option value="{{ $language->code }}" {{ old('language') == $language->code ? 'selected' : '' }}>
                                                            {{ $language->name }}
                                                        </option>
                                                    @endunless
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <button type="submit" class="btn btn-primary w-100">
                                                {{ __('mobile_language_translation.upload') }}
                                            </button>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="input-group">
                                                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" id="bulkImportFile" required>
                                            </div>
                                            @error('file')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        {{ __('language_translation.accepted_formats') }}: JSON
                                    </small>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <form method="POST" action="{{ route('language.save') }}" id="translation-form">
        @csrf
        <input type="hidden" name="current_language" id="form-current-language" value="{{ $currentLanguage }}">

        <!-- Search Field -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="page-search" placeholder="Search pages or text...">
                </div>
            </div>
        </div>

        @foreach($pages as $page => $translations)
            <div class="card mb-4 page-section" data-page="{{ ucfirst(str_replace('_', ' ', $page)) . ' Page' }}" data-page-key="{{ $page }}">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5>{{ ucfirst(str_replace('_', ' ', $page)) }} Page</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary show-up" data-target="#collapse-{{ $page }}" style="display: none;" aria-expanded="true" title="Collapse">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary show-down" data-target="#collapse-{{ $page }}" aria-expanded="false" title="Expand">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="40%">Display Text (English)</th>
                                <th width="60%">Translation</th>
                            </tr>
                        </thead>
                        <tbody id="collapse-{{ $page }}" class="collapse">
                            @foreach($translations as $key => $value)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                    <td>
                                        <input type="text" name="translations[{{ $page }}][{{ $key }}]" 
                                            value="{{ session('is_imported') ? '' : old('translations.' . $page . '.' . $key, $value ?? '') }}"
                                            class="form-control" placeholder="">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="text-center mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __('language_translation.save_all_translations') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" /> <!-- Font Awesome CSS -->
<style>
    /* Ensure Select2 respects the width */
    .select2-container {
        width: 50% !important; /* Match the w-50 class */
    }
    .select2-container .select2-selection--single {
        height: 38px; /* Match Bootstrap form-control height */
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px; /* Center text vertically */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px; /* Align arrow */
    }

    /* Hide page sections that don't match the search */
    .page-section {
        display: block;
    }
    .page-section.hidden {
        display: none;
    }

    /* Collapse styling */
    .collapse {
        transition: all 0.3s ease;
    }

    /* Button styling */
    .btn-sm {
        padding: 0.25rem 0.5rem;
    }

   .table-responsive::-webkit-scrollbar {
        width: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS for collapse -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pass PHP $pages to JavaScript
        var pages = @json(array_keys($pages));

        // Initialize Select2 on the language dropdown
        $('.language-filter').select2({
            placeholder: 'Choose language',
            allowClear: true,
            width: '100%', // Ensure it fits the form layout
        });

        // Ensure the form submits the selected value correctly
        $('.language-filter').on('select2:select', function(e) {
            console.log('Selected language:', e.params.data.id);
        });

        // Handle import language submission
        $('#import-language-form').on('submit', function(e) {
            var selectedLanguage = $('#import-language').val();
            if (selectedLanguage) {
                $('#form-current-language').val(selectedLanguage); // Update hidden input with imported language
                console.log('Imported language set to:', selectedLanguage);
            }
        });

        // Handle system language change and reload form
        $('#system-language').on('change', function() {
            var selectedLanguage = $(this).val();
            if (selectedLanguage) {
                $('#form-current-language').val(selectedLanguage); // Update hidden input
                $.ajax({
                    url: '{{ route('language.change') }}',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        language: selectedLanguage
                    },
                    success: function(response) {
                        console.log('Language changed successfully:', response);
                        location.reload(); // Reload the page to re-render translations
                    },
                    error: function(xhr, status, error) {
                        console.error('Error changing language:', error);
                    }
                });
            }
        });

        // Responsive page search filter - fixed version
        $('#page-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase().trim();
            
            if (searchTerm === '') {
                $('.page-section').removeClass('hidden');
                return;
            }
            
            $('.page-section').each(function() {
                var pageName = $(this).data('page').toLowerCase();
                var pageKey = $(this).data('page-key').toLowerCase();
                
                // Only match against the page name and key (not the translation texts)
                if (pageName.includes(searchTerm) || pageKey.includes(searchTerm)) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        });

        // Toggle collapse for each page with accessibility
        $('.show-down').on('click', function() {
            var target = $(this).data('target');
            $(target).collapse('show');
            $(this).hide();
            $(this).siblings('.show-up').show();
            $(this).attr('aria-expanded', 'false');
            $(this).siblings('.show-up').attr('aria-expanded', 'true');
        });

        $('.show-up').on('click', function() {
            var target = $(this).data('target');
            $(target).collapse('hide');
            $(this).hide();
            $(this).siblings('.show-down').show();
            $(this).attr('aria-expanded', 'false');
            $(this).siblings('.show-down').attr('aria-expanded', 'true');
        });

        // Initialize all tables as collapsed except the first one
        $('.collapse').collapse('hide');
        if (pages.length > 0) {
            $('#collapse-' + pages[0]).collapse('show');
            $('[data-target="#collapse-' + pages[0] + '"].show-down').hide();
            $('[data-target="#collapse-' + pages[0] + '"].show-up').show();
        }

        // Ensure correct button visibility on collapse events
        $('.collapse').on('shown.bs.collapse', function() {
            var target = '#' + $(this).attr('id');
            $('[data-target="' + target + '"].show-down').hide();
            $('[data-target="' + target + '"].show-up').show();
        }).on('hidden.bs.collapse', function() {
            var target = '#' + $(this).attr('id');
            $('[data-target="' + target + '"].show-up').hide();
            $('[data-target="' + target + '"].show-down').show();
        });
    });

    $(document).ready(function() {
        HideLoad();
    });
</script>
@endpush