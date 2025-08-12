<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- SEO Meta Tags --}}
    @if(isset($page['props']['seo']))
        <title>{{ $page['props']['seo']['title'] ?? 'Leading Digital Marketing Agency | ONE TEAM SOLUTION' }}</title>
        <meta name="description" content="{{ $page['props']['seo']['description'] ?? 'ONE TEAM SOLUTION is a One-Stop Solutions Marketing Agency founded in Kuala Lumpur, Malaysia.' }}">
        <meta name="keywords" content="{{ $page['props']['seo']['keywords'] ?? 'digital marketing agency malaysia, seo company' }}">
        
        {{-- Open Graph --}}
        <meta property="og:title" content="{{ $page['props']['seo']['title'] ?? 'Leading Digital Marketing Agency | ONE TEAM SOLUTION' }}">
        <meta property="og:description" content="{{ $page['props']['seo']['description'] ?? 'ONE TEAM SOLUTION is a One-Stop Solutions Marketing Agency founded in Kuala Lumpur, Malaysia.' }}">
        <meta property="og:image" content="{{ url($page['props']['seo']['image'] ?? '/images/logo.png') }}">
        <meta property="og:url" content="{{ $page['props']['seo']['url'] ?? url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ONE TEAM SOLUTION Digital Marketing Agency">
        
        {{-- Twitter Cards --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $page['props']['seo']['title'] ?? 'Leading Digital Marketing Agency | ONE TEAM SOLUTION' }}">
        <meta name="twitter:description" content="{{ $page['props']['seo']['description'] ?? 'ONE TEAM SOLUTION is a One-Stop Solutions Marketing Agency founded in Kuala Lumpur, Malaysia.' }}">
        <meta name="twitter:image" content="{{ url($page['props']['seo']['image'] ?? '/images/logo.png') }}">
        
        {{-- Additional SEO --}}
        <link rel="canonical" href="{{ $page['props']['seo']['url'] ?? url()->current() }}">
    @else
        <title inertia></title>
    @endif
    
    <meta name="robots" content="index, follow">
    <meta name="author" content="ONE TEAM SOLUTION Digital Marketing Agency">
    
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
