@props(['seo'])
@php /** @var \Pergament\Data\SeoMeta $seo */ @endphp
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
    <link rel="canonical" href="{{ $seo->canonical }}">
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
