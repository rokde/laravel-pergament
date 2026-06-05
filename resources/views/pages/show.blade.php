@extends('pergament::layouts.app')

@section('seo')
    <x-pergament::seo-head :seo="$seo" />
@endsection

@section('content')
@if($layout === 'landing')
    {{-- Landing page: full-width blocks --}}
    <div class="pergament-landing">
        @if(!empty($page['title']) && !($isHomepage ?? false))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ $page['title'] }}
                </h1>
            </div>
        @endif

        @if(config('pergament.pages.tts'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <x-pergament::tts-button />
            </div>
        @endif

        @if(config('pergament.page_actions.enabled'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <x-pergament::page-actions />
            </div>
        @endif

        @if($page['allowHtml'] ?? false)
            {!! $page['htmlContent'] !!}
        @else
            <div class="prose dark:prose-invert max-w-none">
                {!! $page['htmlContent'] !!}
            </div>
        @endif
    </div>
@else
    {{-- Standard page layout --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {{ $page['title'] }}
        </h1>

        @if(!empty($page['excerpt']))
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                {{ $page['excerpt'] }}
            </p>
        @endif

        @if(config('pergament.pages.tts'))
            <div class="mb-6">
                <x-pergament::tts-button />
            </div>
        @endif

        @if(config('pergament.page_actions.enabled'))
            <div class="mb-6">
                <x-pergament::page-actions />
            </div>
        @endif

        <x-pergament::content-statistics :statistics="$page['statistics'] ?? []" />

        @if($page['allowHtml'] ?? false)
            {!! $page['htmlContent'] !!}
        @else
            <div class="prose dark:prose-invert max-w-none">
                {!! $page['htmlContent'] !!}
            </div>
        @endif
    </div>
@endif

@push('styles')
<style>
    .pergament-block-hero {
        padding: 4rem 1rem;
        text-align: center;
    }
    .pergament-block-features {
        padding: 3rem 1rem;
    }
    .pergament-block-cta {
        padding: 3rem 1rem;
        text-align: center;
    }
</style>
@endpush
@endsection
