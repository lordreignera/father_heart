@extends('layouts.website')
@section('title', "Stories | Father's Heart Mobility Ministry")
@section('content')
<section class="page-hero page-hero-stories"><div class="page-hero-image" style="background-image:url('{{ asset('images/community-mobility.jpeg') }}')"></div><div class="page-hero-shade"></div><div class="page-hero-copy reveal"><p class="eyebrow eyebrow-light"><span></span> Stories with dignity</p><h1>Every person is <em>more than a case file.</em></h1><p>Stories belong to the people who live them. We believe they should be shared only with respect, consent, and care.</p></div><span class="page-marker">STORIES / 03</span></section>
<section class="story-feature section-wrap"><div class="story-quote-mark" aria-hidden="true">“</div><div><p class="eyebrow">A principle we hold</p><h2>Listen closely.<br><em>Support thoughtfully.</em><br>Keep showing up.</h2><p>Behind every mobility need is a whole person—with their own relationships, hopes, choices, and plans. We will not invent or publish personal stories without the person's permission.</p></div><figure class="story-photo"><img src="{{ asset('images/community-mobility.jpeg') }}" alt="A family gathered together"><figcaption>People, families, and community belong at the heart of the conversation.</figcaption></figure></section>
<section class="archive-section">
    <div class="section-wrap">
        <div class="archive-heading"><div><p class="eyebrow">From the old system's photo archive</p><h2>Moments from <em>the journey.</em></h2></div><p>These selected photographs are from earlier community visits. Select an image to view it larger. Personal stories and captions will be added only with approval.</p></div>
        <div class="archive-grid">
            <button class="archive-card" type="button" data-lightbox data-src="{{ asset('images/stories/butambala-visit.jpg') }}" data-alt="A child seated in a supportive wheelchair during a community visit in Butambala" data-title="Butambala">
                <span class="archive-photo"><img src="{{ asset('images/stories/butambala-visit.jpg') }}" alt="A child seated in a supportive wheelchair during a community visit in Butambala" loading="lazy"><span class="archive-expand" aria-hidden="true">↗</span></span><span class="archive-caption"><span>Community visit</span><strong>Butambala</strong></span>
            </button>
            <button class="archive-card" type="button" data-lightbox data-src="{{ asset('images/stories/kapchorwa-visit-2023.jpg') }}" data-alt="A child seated in a mobility chair outdoors during a Kapchorwa visit" data-title="Kapchorwa · 2023">
                <span class="archive-photo"><img src="{{ asset('images/stories/kapchorwa-visit-2023.jpg') }}" alt="A child seated in a mobility chair outdoors during a Kapchorwa visit" loading="lazy"><span class="archive-expand" aria-hidden="true">↗</span></span><span class="archive-caption"><span>Community visit · 2023</span><strong>Kapchorwa</strong></span>
            </button>
            <button class="archive-card" type="button" data-lightbox data-src="{{ asset('images/stories/sembabule-visit-2023.jpg') }}" data-alt="An older man seated in a wheelchair during a Sembabule visit" data-title="Sembabule · 2023">
                <span class="archive-photo"><img src="{{ asset('images/stories/sembabule-visit-2023.jpg') }}" alt="An older man seated in a wheelchair during a Sembabule visit" loading="lazy"><span class="archive-expand" aria-hidden="true">↗</span></span><span class="archive-caption"><span>Community visit · 2023</span><strong>Sembabule</strong></span>
            </button>
            <button class="archive-card" type="button" data-lightbox data-src="{{ asset('images/stories/ntoroko-visit.jpg') }}" data-alt="A woman seated in a wheelchair during a community visit in Ntoroko" data-title="Ntoroko">
                <span class="archive-photo"><img src="{{ asset('images/stories/ntoroko-visit.jpg') }}" alt="A woman seated in a wheelchair during a community visit in Ntoroko" loading="lazy"><span class="archive-expand" aria-hidden="true">↗</span></span><span class="archive-caption"><span>Community visit</span><strong>Ntoroko</strong></span>
            </button>
        </div>
    </div>
</section>
<dialog class="photo-lightbox" data-photo-lightbox aria-label="Photo viewer"><button class="lightbox-close" type="button" data-lightbox-close aria-label="Close image">×</button><img data-lightbox-image src="" alt=""><p data-lightbox-title></p></dialog>
<section class="principles-section"><div class="section-wrap principles-inner"><p class="eyebrow eyebrow-light">How we share stories</p><div class="principle-row"><span>01</span><div><h3>Consent comes first</h3><p>A person's story and image are theirs to share—or not share.</p></div></div><div class="principle-row"><span>02</span><div><h3>Show the whole person</h3><p>People are never reduced to a diagnosis, device, or moment of need.</p></div></div><div class="principle-row"><span>03</span><div><h3>Protect privacy</h3><p>Personal details should be handled thoughtfully and only when appropriate.</p></div></div></div></section>
<section class="closing-section"><div class="closing-inner"><div><p class="eyebrow eyebrow-light">Move forward with us</p><h2>Make room for dignity and belonging.</h2><p>Find out how to contribute to practical, person-centered mobility support.</p></div><a class="button button-gold" href="{{ route('website.support') }}">Get involved <span>↗</span></a></div></section>
@endsection
