import './bootstrap';

const menuToggle = document.querySelector('[data-site-menu-toggle]');
const siteMenu = document.querySelector('[data-site-menu]');

if (menuToggle && siteMenu) {
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
        siteMenu.classList.toggle('is-open', !isOpen);
    });

    siteMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Open navigation menu');
            siteMenu.classList.remove('is-open');
        });
    });
}

const heroSlideshow = document.querySelector('[data-hero-slideshow]');
if (heroSlideshow) {
    const slides = Array.from(heroSlideshow.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(heroSlideshow.querySelectorAll('[data-hero-dot]'));
    const toggle = heroSlideshow.querySelector('[data-hero-toggle]');
    const status = heroSlideshow.querySelector('[data-hero-status]');
    let current = 0;
    let paused = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let hovering = false;
    let focused = false;
    let timer;

    const show = (index, announce = false) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === current;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
        });
        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-pressed', String(active));
        });
        if (announce && status) status.textContent = `Photo ${current + 1} of ${slides.length}`;
    };

    const stop = () => window.clearInterval(timer);
    const start = () => {
        stop();
        if (!paused && !hovering && !focused && !document.hidden && slides.length > 1) {
            timer = window.setInterval(() => show(current + 1), 7000);
        }
    };

    const updateToggle = () => {
        if (!toggle) return;
        toggle.textContent = paused ? 'Play' : 'Pause';
        toggle.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
    };

    heroSlideshow.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
        show(current - 1, true);
        start();
    });
    heroSlideshow.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        show(current + 1, true);
        start();
    });
    dots.forEach((dot) => dot.addEventListener('click', () => {
        show(Number(dot.dataset.heroDot), true);
        start();
    }));
    toggle?.addEventListener('click', () => {
        paused = !paused;
        if (!paused) { hovering = false; focused = false; }
        updateToggle();
        start();
    });
    heroSlideshow.addEventListener('mouseenter', () => { hovering = true; stop(); });
    heroSlideshow.addEventListener('mouseleave', () => { hovering = false; start(); });
    heroSlideshow.addEventListener('focusin', () => { focused = true; stop(); });
    heroSlideshow.addEventListener('focusout', (event) => {
        if (!heroSlideshow.contains(event.relatedTarget)) { focused = false; start(); }
    });
    document.addEventListener('visibilitychange', start);
    updateToggle();
    start();
}

const photoLightbox = document.querySelector('[data-photo-lightbox]');
if (photoLightbox) {
    const lightboxImage = photoLightbox.querySelector('[data-lightbox-image]');
    const lightboxTitle = photoLightbox.querySelector('[data-lightbox-title]');

    document.querySelectorAll('[data-lightbox]').forEach((button) => {
        button.addEventListener('click', () => {
            lightboxImage.src = button.dataset.src;
            lightboxImage.alt = button.dataset.alt || '';
            lightboxTitle.textContent = button.dataset.title || '';
            photoLightbox.showModal();
        });
    });

    photoLightbox.querySelector('[data-lightbox-close]')?.addEventListener('click', () => photoLightbox.close());
    photoLightbox.addEventListener('click', (event) => {
        if (event.target === photoLightbox) photoLightbox.close();
    });
}
