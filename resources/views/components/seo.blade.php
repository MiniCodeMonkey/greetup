@props([
    'title',
    'description',
    'image' => null,
    'type' => 'website',
    'canonicalUrl' => null,
    'jsonLd' => null,
])

@php
    $ogImage = $image ?? asset('images/og-default.png');
    $siteName = config('app.name');
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">

@if($canonicalUrl)
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImage }}">
@if($canonicalUrl)
    <meta property="og:url" content="{{ $canonicalUrl }}">
@endif
<meta property="og:site_name" content="{{ $siteName }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@if($jsonLd)
    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
