@extends('layouts.website')
@section('title', "Contact & Requests | Father's Heart Mobility Ministry")
@section('content')
<section class="page-hero page-hero-contact">
    <div class="page-hero-image" style="background-image:url('{{ asset('images/community-mobility.jpeg') }}')"></div>
    <div class="page-hero-shade"></div>
    <div class="page-hero-copy reveal"><p class="eyebrow eyebrow-light"><span></span> Contact &amp; requests</p><h1>We'd like to <em>connect with you.</em></h1><p>Ask about a free mobility device, arrange support for a group, or get in touch about the ministry.</p></div>
    <span class="page-marker">CONTACT / 05</span>
</section>

<section class="contact-notice section-wrap" aria-labelledby="free-support-title">
    <span class="contact-notice-icon" aria-hidden="true">!</span>
    <div><p class="eyebrow">Please know</p><h2 id="free-support-title">Mobility devices are provided <em>free of charge.</em></h2><p>Father's Heart does not charge people to request a wheelchair or other mobility device. Anyone asking for money to “help” submit a request is not associated with the ministry. Because of this, online requests are temporarily unavailable. Please contact the office directly.</p></div>
</section>

<section class="contact-channels section-wrap" aria-labelledby="contact-channels-title">
    <div class="contact-channels-heading"><p class="eyebrow">Reach the team</p><h2 id="contact-channels-title">Start with a <em>conversation.</em></h2><p>If you need a free mobility device or want to make arrangements for a group of people, call the office. You can also write by email.</p></div>
    <div class="contact-channel-grid">
        <article><span>01 / Telephone</span><h3>Call the office</h3><a href="tel:+256393242101">0393 242 101</a><a href="tel:+256772414944">0772 414 944</a><p>Ask about mobility requests or group arrangements.</p></article>
        <article><span>02 / Email</span><h3>Write to us</h3><a href="mailto:fathersheartmobility@gmail.com">fathersheartmobility@gmail.com</a><p>Please do not send private medical records in an unsecured email.</p></article>
        <article><span>03 / Visit</span><h3>By appointment</h3><p>Wheelchair fittings at the Ministry Centre are by appointment only. Please call before coming so the right staff can be there to assist you.</p></article>
    </div>
</section>

<section class="contact-journey"><div class="section-wrap contact-journey-inner"><div><p class="eyebrow eyebrow-light">Serving across Uganda</p><h2>From the Ministry Centre to <em>regional missions.</em></h2></div><p>The Ministry Centre is open. The ministry also travels to communities across Uganda. Contact the office before visiting or arranging a group request.</p></div></section>

<section class="closing-section"><div class="closing-inner"><div><p class="eyebrow eyebrow-light">Want to give?</p><h2>Explore ways to support the work.</h2><p>Find local and international giving information, including gifts of mobility equipment.</p></div><a class="button button-gold" href="{{ route('website.support') }}">Giving information <span aria-hidden="true">↗</span></a></div></section>
@endsection
