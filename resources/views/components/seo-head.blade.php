@props(['seo'])
@php /** @var \Pergament\Data\SeoMeta $seo */ @endphp
<meta name="text-scale" content="scale">
<title>{{ $seo->title }}</title>

@if($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

@if($seo->keywords)
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif

<meta property="og:title" content="{{ $seo->title }}">

@if($seo->description)
    <meta property="og:description" content="{{ $seo->description }}">
@endif

@if($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
@endif

@if($seo->ogType)
    <meta property="og:type" content="{{ $seo->ogType }}">
@endif

@if($seo->canonical)
    <meta property="og:url" content="{{ $seo->canonical }}">
    <link rel="canonical" href="{{ $seo->canonical }}" />
@endif

<meta name="twitter:card" content="{{ $seo->twitterCard }}">
<meta name="twitter:title" content="{{ $seo->title }}">

@if($seo->description)
    <meta name="twitter:description" content="{{ $seo->description }}">
@endif

@if($seo->ogImage)
    <meta name="twitter:image" content="{{ $seo->ogImage }}">
@endif

@if($seo->robots)
    <meta name="robots" content="{{ $seo->robots }}">
@endif

@php
    $pergamentSiteName = (string) config('pergament.site.name', '');
    $pergamentJsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                'name' => $pergamentSiteName,
                'url' => \Pergament\Support\UrlGenerator::url(''),
            ],
            array_filter([
                '@type' => $seo->ogType === 'article' ? 'Article' : 'WebPage',
                ($seo->ogType === 'article' ? 'headline' : 'name') => $seo->title,
                'description' => $seo->description ?: null,
                'url' => $seo->canonical,
                'image' => $seo->ogImage ?: null,
            ]),
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($pergamentJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
