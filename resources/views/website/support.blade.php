@extends('layouts.website')
@section('title', "Give & Get Involved | Father's Heart Mobility Ministry")
@section('content')
<section class="page-hero page-hero-support"><div class="page-hero-image" style="background-image:url('{{ asset('images/stories/sembabule-visit-2023.jpg') }}')"></div><div class="page-hero-shade"></div><div class="page-hero-copy reveal"><p class="eyebrow eyebrow-light"><span></span> Give &amp; get involved</p><h1>Help give the <em>gift of mobility.</em></h1><p>Father's Heart is a nonprofit ministry registered in Uganda. Your support can help people receive mobility aids free of charge.</p></div><span class="page-marker">GIVE / 04</span></section>

<section class="giving-country-section section-wrap" aria-labelledby="giving-country-title">
    <div class="giving-country-heading"><div><p class="eyebrow">Ways to give</p><h2 id="giving-country-title">Choose your <em>location.</em></h2></div><p>The ministry's existing giving pages explain how to donate from each country. For tax receipt information, review the receiving partner's current instructions.</p></div>
    <div class="giving-country-grid">
        <a href="{{ route('website.contact') }}"><span class="giving-country-code">UG</span><strong>Uganda</strong><small>Contact the ministry to arrange a local donation.</small><span class="giving-country-arrow" aria-hidden="true">↗</span></a>
        <a href="https://fathersheartmobility.org/donate/canada" target="_blank" rel="noopener noreferrer"><span class="giving-country-code">CA</span><strong>Canada</strong><small>Read the ministry's Canadian giving instructions.</small><span class="giving-country-arrow" aria-hidden="true">↗</span></a>
        <a href="https://fathersheartmobility.org/donate/usa" target="_blank" rel="noopener noreferrer"><span class="giving-country-code">US</span><strong>United States</strong><small>Read the ministry's US giving instructions.</small><span class="giving-country-arrow" aria-hidden="true">↗</span></a>
        <a href="https://fathersheartmobility.org/donate/uk" target="_blank" rel="noopener noreferrer"><span class="giving-country-code">UK</span><strong>United Kingdom</strong><small>Read the ministry's UK giving instructions.</small><span class="giving-country-arrow" aria-hidden="true">↗</span></a>
    </div>
    <p class="giving-country-note">The Canada, United States, and United Kingdom links open the ministry's existing website in a new tab.</p>
</section>

<section class="giving-other"><div class="section-wrap giving-other-inner"><div><p class="eyebrow eyebrow-light">Gifts in kind</p><h2>Equipment can make <em>a difference.</em></h2><p>The ministry also welcomes gifts in kind, including restored wheelchairs and crutches. Please speak with the team before sending or bringing equipment so they can help arrange a suitable donation.</p><a class="button button-gold" href="{{ route('website.contact') }}">Contact the team <span aria-hidden="true">↗</span></a></div><div class="giving-other-photo"><img src="{{ asset('images/stories/butambala-visit.jpg') }}" alt="A child seated in a supportive wheelchair during a community visit" loading="lazy"></div></div></section>

<section class="support-section section-wrap"><div class="support-intro"><p class="eyebrow">More ways to help</p><h2>Bring what <em>you can.</em></h2><p>Partnership can also mean offering skills, time, and a willingness to serve alongside the ministry.</p></div><div class="support-options"><article><span>01</span><div><h3>Skills and time</h3><p>Ask about volunteer opportunities and the practical skills that would help the team.</p></div></article><article><span>02</span><div><h3>Church partnership</h3><p>Churches can help support local outreach and inclusion in their communities.</p></div></article><article><span>03</span><div><h3>Long-term support</h3><p>Talk with the ministry about a continuing partnership or equipment support.</p></div></article></div></section>

<section class="contact-safety-band"><div class="section-wrap"><strong>Wheelchairs and mobility devices are free for recipients.</strong><p>No one should charge you to request one. To ask for help, call the office directly.</p><a href="{{ route('website.contact') }}">Request and contact details <span aria-hidden="true">↗</span></a></div></section>
@endsection
