@extends('layouts.website')
@section('title', "Our Story | Father's Heart Mobility Ministry")
@section('content')
<section class="page-hero page-hero-about">
    <div class="page-hero-image" style="background-image:url('{{ asset('images/community-mobility.jpeg') }}')"></div>
    <div class="page-hero-shade"></div>
    <div class="page-hero-copy reveal">
        <p class="eyebrow eyebrow-light"><span></span> Our story</p>
        <h1>From small <em>beginnings.</em></h1>
        <p>A personal experience of receiving a wheelchair grew into a ministry serving people with disabilities across Uganda.</p>
    </div>
    <span class="page-marker">ABOUT / 01</span>
</section>

<section class="about-origin section-wrap" id="our-beginnings">
    <div class="about-origin-heading">
        <p class="eyebrow">The founder's journey</p>
        <h2>Francis Mugwanya knows what <em>mobility means.</em></h2>
        <p class="about-origin-aside">His own experience became the beginning of Father's Heart Mobility Ministry.</p>
    </div>
    <div class="about-origin-story">
        <p>Francis Mugwanya knows firsthand what it is like to crawl and be looked down upon. His life changed when he received his first wheelchair at the age of 12. Knowing Jesus and believing the Word of God transformed how Francis thought about himself.</p>
        <p>As a young adult, Francis saved from his modest earnings to buy wheelchairs for others whenever he could. One wheelchair cost about one and a half months of his salary. After he had given away about a dozen, supporters including Free Wheelchair Mission and Joni and Friends began to help.</p>
    </div>
</section>

<section class="about-milestones" aria-labelledby="milestones-title">
    <div class="section-wrap">
        <div class="about-milestones-heading"><p class="eyebrow eyebrow-light">How the ministry grew</p><h2 id="milestones-title">A calling that <em>kept moving.</em></h2></div>
        <div class="about-milestones-grid">
            <article><span>2006</span><p>Father's Heart Mobility Ministry was officially founded under Gaba Community Church in Uganda.</p></article>
            <article><span>2011</span><p>The ministry's work was launched full-time.</p></article>
            <article><span>2012</span><p>Father's Heart received NGO registration.</p></article>
            <article><span>25,000+</span><p>The ministry reports helping more than 25,000 people in Uganda gain mobility.</p></article>
        </div>
    </div>
</section>

<section class="about-leadership section-wrap">
    <div><p class="eyebrow">The founder today</p><h2>Continuing to advocate <em>for inclusion.</em></h2></div>
    <div><p>The ministry's account says Francis Mugwanya serves on Uganda's National Council for People with Disabilities. In 2025, he received the Diamond Jubilee Medal from President Y.K. Museveni for his contribution to Uganda's development.</p><p>His story continues to shape the ministry's commitment to dignity, participation, and practical support.</p></div>
</section>

<section class="about-team" aria-labelledby="about-team-title"><div class="section-wrap about-team-inner"><div><p class="eyebrow eyebrow-light">Who we are</p><h2 id="about-team-title">A family, a team, <em>and a community.</em></h2><p>Founder and director Francis Mugwanya, his wife Adrienne, and their children serve together as a family. Staff and volunteers from Gaba Community Church form the core team. Volunteers are trained on disability issues and experienced in wheelchair fitting.</p><p>When the ministry partners with churches in each district, local volunteers join the work. International teams also take part.</p></div><aside><span>Our conviction</span><p>People with disabilities are treasured by God and deserve dignity, inclusion, and a seat of honour.</p></aside></div></section>

<section class="about-objectives" id="objectives" aria-labelledby="objectives-title">
    <div class="section-wrap">
        <div class="about-objectives-heading"><p class="eyebrow">Our objectives</p><h2 id="objectives-title">Faith expressed <em>through action.</em></h2><p>Father's Heart works through the Church to raise the esteem and capability of people with disabilities in Uganda.</p></div>
        <div class="about-objectives-grid">
            <article><span>01</span><h3>Share God's love</h3><p>Share the love of Father God with people with disabilities and affirm their true value and potential.</p></article>
            <article><span>02</span><h3>Provide mobility aids</h3><p>Give mobility aids to people with disabilities so they can pursue their potential.</p></article>
            <article><span>03</span><h3>Train pastors</h3><p>Help pastors understand issues of disability and respond with care.</p></article>
            <article><span>04</span><h3>Raise awareness</h3><p>Build public understanding of the causes of disability and advocate for people with disabilities to be included in mainstream society.</p></article>
            <article><span>05</span><h3>Advocate for access</h3><p>Speak up for accessibility, medical care, education, and employment for people with disabilities.</p></article>
        </div>
    </div>
</section>

<section class="closing-section"><div class="closing-inner"><div><p class="eyebrow eyebrow-light">The work continues</p><h2>See how the mission takes shape today.</h2><p>Explore the ministry's approach to mobility support and community care.</p></div><a class="button button-gold" href="{{ route('website.work') }}">Explore our work <span aria-hidden="true">↗</span></a></div></section>
@endsection
