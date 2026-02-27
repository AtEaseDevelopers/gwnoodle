@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('mobile_language_translation.mobile_app_language_translation') }}</h4>
                </div>
                @include('flash::message')

                <div class="card-body">
                    <div class="row">
                        <!-- Left Side - Available Languages -->
                        <div class="col-md-6">
                            <div class="border p-3 rounded bg-light">
                                <h5 class="mb-3">{{ __('mobile_language_translation.available_languages') }}</h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('mobile_language_translation.language') }}</th>
                                            <th>{{ __('mobile_language_translation.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($availableSystemLanguages as $language)
                                        <tr>
                                            <td>{{ $language->name }}</td>
                                            <td>
                                                <form action="{{ route('mobile_language.edit', $language->id) }}" method="GET" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary">{{ __('mobile_language_translation.edit') }}</button>
                                                </form>
                                                <form action="{{ route('mobile_language.destroy', $language->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this language?')">{{ __('mobile_language_translation.delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right Side - Import New Language -->
                        <div class="col-md-6">
                            <div class="border p-3 rounded bg-light">
                                <h5 class="mb-3">{{ __('mobile_language_translation.import_new_language') }}</h5>
                                <form method="POST" action="{{ route('mobile_language.import') }}" class="form-inline" id="import-language-form">
                                    @csrf
                                    <div class="form-group w-100">
                                        <label class="mr-2">{{ __('mobile_language_translation.select_language_to_import') }}:</label>
                                        <select name="language" class="form-control language-filter" id="import-language" required>
                                            <option value="">{{ __('mobile_language_translation.choose_language') }}</option>
                                            @foreach($languages as $language)
                                                @unless($availableSystemLanguages->contains('code', $language->code))
                                                    <option value="{{ $language->code }}">
                                                        {{ $language->name }}
                                                    </option>
                                                @endunless
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            Import
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted">{{ __('mobile_language_translation.only_shows_languages_not_already_imported') }}</small>
                            </div>
                        </div>

                        <!-- Export Translations -->
                        <div class="col-md-6" style="padding-top: 20px;">
                            <div class="border p-3 rounded bg-light">
                                <h5 class="mb-3">{{ __('mobile_language_translation.export_translations') }}</h5>
                                <form method="POST" action="{{ route('mobile_language.export') }}" class="form-inline">
                                    @csrf
                                    <div class="form-group w-100">
                                        <label class="mr-2">{{ __('mobile_language_translation.select_language_to_export') }}:</label>
                                        <select name="language" class="form-control" required>
                                            <option value="">{{ __('mobile_language_translation.choose_language') }}</option>
                                            <option value="en">
                                                English
                                            </option>
                                        </select>
                                        <select name="format" class="form-control ml-2" required>
                                            <option value="json">JSON</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary ml-2">
                                            {{ __('mobile_language_translation.export') }}
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted">{{ __('mobile_language_translation.export_all_translations_for_selected_language') }}</small>
                            </div>
                        </div>

                        <!-- Import Translations -->
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

    <form method="POST" action="{{ route('mobile_language.save') }}" id="translation-form">
        @csrf
        <input type="hidden" name="current_language" id="form-current-language" value="{{ $selectedLanguage }}">

        <!-- Search Field -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="translation-search" placeholder="{{ __('mobile_language_translation.search') }}">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>{{ __('mobile_language_translation.mobile_app_translation') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="50%">{{ __('mobile_language_translation.default_english_text') }}</th>
                                <th width="50%">{{ __('mobile_language_translation.translation') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($translations as $key => $value)
                                <tr class="translation-row" data-key="{{ $key }}">
                                    <td>{{ $defaultTranslations[$key] ?? '' }}</td>
                                    <td>
                                        <input type="text" 
                                               name="translations[{{ $key }}]" 
                                               value="{{ session('is_imported') ? '' : old('translations.' . $key, $value ?? '') }}"
                                               class="form-control" 
                                               placeholder="Enter translation">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __('mobile_language_translation.save_all_translations') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" />
<style>
    .select2-container {
        width: 50% !important;
    }
    .select2-container .select2-selection--single {
        height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }
    .translation-row.hidden {
        display: none;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.language-filter').select2({
        placeholder: '{{ __('mobile_language_translation.choose_language') }}',
        allowClear: true,
        width: '100%',
    });

    // Search filter for translations
    $('#translation-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            $('.translation-row').removeClass('hidden');
            return;
        }
        
        $('.translation-row').each(function() {
            var key = $(this).data('key').toLowerCase();
            var text = $(this).find('td:first').text().toLowerCase();
            
            if (key.includes(searchTerm) || text.includes(searchTerm)) {
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
            }
        });
    });
});

$(document).ready(function() {
    HideLoad();
});
</script>
@endpush