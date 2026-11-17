@extends('layouts.website')
@section('title', "Father's Heart Mobility Ministry | Raising esteem and capability")

@section('content')
<section class="hero" id="home" aria-labelledby="hero-title">
    <div class="wrap hero-stage" data-hero-slideshow>
        <div class="hero-slides" aria-label="Father's Heart photo highlights">
            <div class="hero-slide is-active" data-hero-slide aria-hidden="false"><img src="{{ asset('images/slideshow/community1.jpg') }}" alt="A large community gathering with people using wheelchairs" fetchpriority="high"></div>
            <div class="hero-slide" data-hero-slide aria-hidden="true"><img src="{{ asset('images/slideshow/wheelchars.jpg') }}" alt="Rows of blue and black wheelchairs" decoding="async"></div>
            <div class="hero-slide" data-hero-slide aria-hidden="true"><img src="{{ asset('images/slideshow/wheelchars2.jpeg') }}" alt="A group of people standing together with mobility aids" decoding="async"></div>
        </div>
        <div class="hero-shade" aria-hidden="true"></div>
        <div class="hero-copy">
            <span class="pill"><span aria-hidden="true">●</span> Every journey matters</span>
            <h1 id="hero-title">Moving lives<br>forward, <span>together.</span></h1>
            <p class="hero-slogan">{{ config('website.slogan') }}</p>
            <div class="hero-buttons">
                <a class="button button-primary" href="#work">Discover our work <span aria-hidden="true">↗</span></a>
                <a class="button button-outline" href="#about">Our story <span aria-hidden="true">→</span></a>
            </div>
            <div class="hero-promise"><span aria-hidden="true">♡</span><div><strong>Mobility devices are free</strong><small>For the people who receive them in Uganda.</small></div></div>
        </div>
        <div class="hero-controls" aria-label="Slideshow controls">
            <button class="hero-arrow" type="button" data-hero-prev aria-label="Previous photo">←</button>
            <div class="hero-dots" aria-label="Choose a photo">
                <button class="is-active" type="button" data-hero-dot="0" aria-label="Show community gathering photo" aria-pressed="true"></button>
                <button type="button" data-hero-dot="1" aria-label="Show wheelchair photo" aria-pressed="false"></button>
                <button type="button" data-hero-dot="2" aria-label="Show group photo" aria-pressed="false"></button>
            </div>
            <button class="hero-arrow" type="button" data-hero-next aria-label="Next photo">→</button>
            <button class="hero-play" type="button" data-hero-toggle aria-label="Pause slideshow">Pause</button>
            <span class="sr-only" data-hero-status aria-live="polite"></span>
        </div>
    </div>
</section>

<section class="impact-strip" aria-label="Father's Heart at a glance"><div class="wrap impact-grid"><div><strong>2006</strong><span>Founded in Uganda</span></div><div><strong>25,000+</strong><span>People given mobility, as reported by the ministry</span></div><div><strong>Free</strong><span>Mobility devices for recipients</span></div></div></section>

<section class="about-section section-pad" id="about" aria-labelledby="about-title"><div class="wrap about-grid">
    <div class="about-visual"><img src="{{ asset('images/stories/butambala-visit.jpg') }}" alt="A child using a supportive wheelchair during an earlier community visit" loading="lazy"><span class="about-photo-caption">From our community archive</span><div class="about-year"><strong>2006</strong><small>Ministry founded</small></div></div>
    <div class="about-content"><span class="eyebrow">Who we are</span><h2 id="about-title">From small beginnings,<br><span>a ministry of mobility.</span></h2><p>Francis Mugwanya received his first wheelchair at age 12. Knowing Jesus and believing the Word of God transformed how he saw himself.</p><p>As a young adult, he saved from his earnings to buy wheelchairs for others. After giving away about a dozen, supporters including Free Wheelchair Mission and Joni and Friends joined the work.</p><p>Father's Heart was founded in 2006 under Gaba Community Church in Uganda. The ministry began full-time work in 2011 and received NGO registration in 2012.</p><div class="about-callout"><span aria-hidden="true">✦</span><strong>Sharing the love of Father God and affirming each person's true value and potential.</strong></div><p class="about-recognition">The ministry also notes Francis's service on Uganda's National Council for People with Disabilities and his receipt of the Diamond Jubilee Medal in 2025.</p></div>
</div></section>

<section class="work-section section-pad" id="work" aria-labelledby="work-title"><div class="wrap"><div class="section-heading"><div><span class="eyebrow">Our objectives</span><h2 id="work-title">What guides <span>our work.</span></h2></div><p>Five commitments shape the ministry's work through the Church and across Uganda.</p></div><div class="work-grid"><article><div class="work-card-top"><span class="work-icon" aria-hidden="true">♡</span><span class="work-index">01</span></div><h3>Share the love of Father God</h3><p>Help people with disabilities recognize their true value and potential.</p></article><article><div class="work-card-top"><span class="work-icon" aria-hidden="true">✦</span><span class="work-index">02</span></div><h3>Give mobility</h3><p>Provide aids to mobility so people can maximize their potential.</p></article><article><div class="work-card-top"><span class="work-icon" aria-hidden="true">✳</span><span class="work-index">03</span></div><h3>Train pastors</h3><p>Equip pastors to understand issues of disability.</p></article><article><div class="work-card-top"><span class="work-icon" aria-hidden="true">◎</span><span class="work-index">04</span></div><h3>Raise awareness</h3><p>Raise public awareness of the causes of disability and advocate for inclusion in mainstream society.</p></article><article><div class="work-card-top"><span class="work-icon" aria-hidden="true">↗</span><span class="work-index">05</span></div><h3>Advocate for access</h3><p>Speak up for accessibility, medical care, education, and employment for people with disabilities.</p></article></div></div></section>

<section class="moments-section section-pad" id="stories" aria-labelledby="moments-title"><div class="wrap"><div class="section-heading"><div><span class="eyebrow">Life at Father's Heart</span><h2 id="moments-title">Moments of care.<br><span>A community of hope.</span></h2></div><p>Real photographs from earlier community visits. Select a photo for a closer look.</p></div><div class="moments-grid">
    <button class="moment-card" type="button" data-lightbox data-src="{{ asset('images/stories/kapchorwa-visit-2023.jpg') }}" data-alt="A child using a mobility chair during a Kapchorwa community visit" data-title="Kapchorwa, 2023"><span class="moment-image"><img src="{{ asset('images/stories/kapchorwa-visit-2023.jpg') }}" alt="A child using a mobility chair during a Kapchorwa community visit" loading="lazy"></span><span class="moment-copy"><small>Kapchorwa · 2023</small><strong>A moment of mobility</strong><span>From our community photo archive.</span></span></button>
    <button class="moment-card" type="button" data-lightbox data-src="{{ asset('images/stories/ntoroko-visit.jpg') }}" data-alt="A woman using a wheelchair during a Ntoroko community visit" data-title="Ntoroko"><span class="moment-image"><img src="{{ asset('images/stories/ntoroko-visit.jpg') }}" alt="A woman using a wheelchair during a Ntoroko community visit" loading="lazy"></span><span class="moment-copy"><small>Ntoroko</small><strong>Across Uganda</strong><span>From an earlier regional visit.</span></span></button>
    <button class="moment-card" type="button" data-lightbox data-src="{{ asset('images/stories/sembabule-visit-2023.jpg') }}" data-alt="A man using a wheelchair during a Sembabule community visit" data-title="Sembabule, 2023"><span class="moment-image"><img src="{{ asset('images/stories/sembabule-visit-2023.jpg') }}" alt="A man using a wheelchair during a Sembabule community visit" loading="lazy"></span><span class="moment-copy"><small>Sembabule · 2023</small><strong>Showing up together</strong><span>A glimpse of the ministry at work.</span></span></button>
</div></div></section>

<section class="people-strip" aria-label="What the ministry stands for"><div class="wrap people-grid"><div><strong>01</strong><span><b>Value &amp; potential</b><small>Every person matters.</small></span></div><div><strong>02</strong><span><b>Mobility</b><small>Practical aids, free to recipients.</small></span></div><div><strong>03</strong><span><b>Inclusion</b><small>Access and opportunity in society.</small></span></div></div></section>

<section class="give-section section-pad" id="give" aria-labelledby="give-title">
    <div class="wrap give-grid">
        <div>
            <span class="eyebrow">Get involved</span>
            <h2 id="give-title">Help make mobility<br><span>possible for more people.</span></h2>
            <p>Support the ministry's work in Uganda. We also welcome restored wheelchairs and crutches.</p>
            <a class="button button-primary" href="#contact">Ask about giving in Uganda <span aria-hidden="true">↗</span></a>
        </div>
        <div class="give-options">
            <span>Giving from abroad</span>
            <a href="#donate-canada">Canada <span aria-hidden="true">↓</span></a>
            <a href="#donate-usa">USA <span aria-hidden="true">↓</span></a>
            <a href="#donate-uk">United Kingdom <span aria-hidden="true">↓</span></a>
            <small>Choose a country to read its giving options here on this website.</small>
        </div>
    </div>
    @include('website.partials.donation-details')
</section>

<section class="faq-section section-pad" aria-labelledby="faq-title"><div class="wrap faq-grid"><div><span class="eyebrow">Questions &amp; answers</span><h2 id="faq-title">Curious about<br><span>Father's Heart?</span></h2><p>Here are a few things to know before you get in touch.</p><a class="text-link" href="#contact">Ask us a question <span aria-hidden="true">↗</span></a></div><div class="faq-list"><details><summary>Are mobility devices free?</summary><p>Yes. Wheelchairs and other mobility devices are provided to recipients free of charge.</p></details><details><summary>Can I request a device online?</summary><p>Online requests are temporarily unavailable. Call the office to ask about a free mobility device or a group arrangement.</p></details><details><summary>Can I visit the Ministry Centre?</summary><p>Visits are by appointment only. Please call before coming.</p></details><details><summary>How can I give?</summary><p>Contact the office for giving in Uganda or gifts in kind. Choose Canada, USA, or the UK in the giving section to read the options on this website.</p></details></div></div></section>

<section class="contact-section section-pad" id="contact" aria-labelledby="contact-title"><div class="wrap"><div class="section-heading"><div><span class="eyebrow">Let's connect</span><h2 id="contact-title">Have a question<br><span>or want to help?</span></h2></div><p>For mobility assistance, a group visit, or a donation, reach our office directly.</p></div><div class="contact-grid"><a href="tel:{{ config('website.contact.phone_primary.dial') }}"><small>Call us</small><strong>{{ config('website.contact.phone_primary.display') }}</strong><span aria-hidden="true">↗</span></a><a href="tel:{{ config('website.contact.phone_secondary.dial') }}"><small>Or call</small><strong>{{ config('website.contact.phone_secondary.display') }}</strong><span aria-hidden="true">↗</span></a><a href="mailto:{{ config('website.contact.email') }}"><small>Email us</small><strong>{{ config('website.contact.email') }}</strong><span aria-hidden="true">↗</span></a></div><div class="safety-note"><span aria-hidden="true">!</span><p><strong>Mobility devices are free.</strong> Never pay someone to request one. Online requests are temporarily unavailable, and Ministry Centre visits are by appointment only.</p></div></div></section>

<dialog class="photo-lightbox" data-photo-lightbox aria-label="Photo viewer"><button type="button" data-lightbox-close aria-label="Close photo">×</button><img data-lightbox-image src="" alt=""><p data-lightbox-title></p></dialog>
@endsection
