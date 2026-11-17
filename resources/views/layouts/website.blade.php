<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-pwa-head />
    <meta name="description" content="Father's Heart Mobility Ministry in Uganda. {{ config('website.slogan') }}.">
    <title>@yield('title', "Father's Heart Mobility Ministry")</title>
    <link rel="icon" type="image/png" href="{{ asset('images/fathers-heart-logo.png') }}">
    @vite(['resources/css/website.css', 'resources/js/app.js'])
</head>
<body class="fh-site">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header"><div class="header-inner wrap">
        <a class="brand" href="{{ route('home') }}#home" aria-label="Father's Heart home"><img src="{{ asset('images/fathers-heart-logo.png') }}" alt=""><span><strong>Father's Heart</strong><small>Mobility Ministry</small></span></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" aria-label="Open navigation menu" data-site-menu-toggle><span class="menu-lines" aria-hidden="true"></span></button>
        <nav class="site-nav" id="site-menu" aria-label="Main navigation" data-site-menu><a href="{{ route('home') }}#home">Home</a><a href="{{ route('home') }}#about">About us</a><a href="{{ route('home') }}#work">Our work</a><a href="{{ route('home') }}#stories">Moments</a><a href="{{ route('home') }}#contact">Contact</a><a class="nav-cta" href="{{ route('home') }}#give">Get involved <span aria-hidden="true">↗</span></a><a class="nav-login" href="{{ route('login') }}">Staff login</a></nav>
    </div></header>
    <main id="main">@yield('content')</main>
    <footer class="site-footer"><div class="wrap footer-inner"><div><a class="footer-brand" href="{{ route('home') }}#home"><img src="{{ asset('images/fathers-heart-logo.png') }}" alt=""><span>Father's Heart<br><small>Mobility Ministry</small></span></a><p>{{ config('website.slogan') }}</p></div><nav aria-label="Footer navigation"><a href="{{ route('home') }}#about">About</a><a href="{{ route('home') }}#work">Our work</a><a href="{{ route('home') }}#stories">Stories</a><a href="{{ route('home') }}#give">Give</a><a href="{{ route('home') }}#contact">Contact</a></nav><div class="footer-contact"><a href="tel:{{ config('website.contact.phone_primary.dial') }}">{{ config('website.contact.phone_primary.display') }}</a><a href="mailto:{{ config('website.contact.email') }}">{{ config('website.contact.email') }}</a><small>Visits by appointment only.</small></div></div><div class="wrap footer-bottom"><small>© {{ date('Y') }} Father's Heart Mobility Ministry</small><a href="{{ route('login') }}">Staff login</a></div></footer>
</body>
</html>
