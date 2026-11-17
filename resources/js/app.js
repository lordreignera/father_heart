import './bootstrap';
import './admin-permissions';

const serviceWorkerUrl = document.querySelector('meta[name="app-service-worker"]')?.content;
if (serviceWorkerUrl && 'serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(serviceWorkerUrl).catch(() => {});
    });
}

let installPrompt;
const installButton = document.querySelector('[data-install-app]');
window.addEventListener('beforeinstallprompt', (event) => {
    if (!installButton) return;
    event.preventDefault();
    installPrompt = event;
    installButton.hidden = false;
});
installButton?.addEventListener('click', async () => {
    if (!installPrompt) return;
    const prompt = installPrompt;
    installPrompt = null;
    installButton.hidden = true;
    await prompt.prompt();
});
window.addEventListener('appinstalled', () => {
    installPrompt = null;
    if (installButton) installButton.hidden = true;
});

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
        if (!paused && !focused && !document.hidden && slides.length > 1) {
            timer = window.setInterval(() => show(current + 1), 5000);
        }
    };

    const updateToggle = () => {
        if (!toggle) return;
        toggle.textContent = paused ? 'Play' : 'Pause';
        toggle.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
    };

    const restartAfterNavigation = (event) => {
        if (event.detail > 0) focused = false;
        start();
    };

    heroSlideshow.querySelector('[data-hero-prev]')?.addEventListener('click', (event) => {
        show(current - 1, true);
        restartAfterNavigation(event);
    });
    heroSlideshow.querySelector('[data-hero-next]')?.addEventListener('click', (event) => {
        show(current + 1, true);
        restartAfterNavigation(event);
    });
    dots.forEach((dot) => dot.addEventListener('click', (event) => {
        show(Number(dot.dataset.heroDot), true);
        restartAfterNavigation(event);
    }));
    toggle?.addEventListener('click', () => {
        paused = !paused;
        if (!paused) focused = false;
        updateToggle();
        start();
    });
    heroSlideshow.addEventListener('focusin', () => { focused = true; stop(); });
    heroSlideshow.addEventListener('focusout', (event) => {
        if (!heroSlideshow.contains(event.relatedTarget)) { focused = false; start(); }
    });
    document.addEventListener('visibilitychange', start);
    updateToggle();
    start();
}

document.querySelectorAll('[data-password-toggle]').forEach((passwordToggle) => {
    const passwordInput = document.getElementById(passwordToggle.getAttribute('aria-controls'));
    passwordToggle.addEventListener('click', () => {
        if (!passwordInput) return;
        const isVisible = passwordInput.type === 'text';
        passwordInput.type = isVisible ? 'password' : 'text';
        passwordToggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
    });
});

const openDonation = (id) => {
    if (!['donate-canada', 'donate-usa', 'donate-uk'].includes(id)) return;
    const country = document.getElementById(id);
    if (country) country.open = true;
};
window.addEventListener('hashchange', () => openDonation(window.location.hash.slice(1)));
document.querySelectorAll('.give-options a[href^="#donate-"]').forEach((link) => {
    link.addEventListener('click', () => openDonation(link.hash.slice(1)));
});
openDonation(window.location.hash.slice(1));

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
