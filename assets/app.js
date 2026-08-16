import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

function initEssentialsSliders() {
    document.querySelectorAll('[data-essentials-slider]:not([data-slider-ready])').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const cards = Array.from(track?.children ?? []);
        const current = slider.querySelector('[data-slider-current]');

        if (!track || cards.length < 2) {
            slider.dataset.sliderReady = 'true';
            return;
        }

        let index = 0;
        const show = (nextIndex) => {
            index = (nextIndex + cards.length) % cards.length;
            track.scrollTo({left: cards[index].offsetLeft - track.offsetLeft, behavior: 'smooth'});
            if (current) current.textContent = String(index + 1).padStart(2, '0');
        };

        slider.querySelector('[data-slider-previous]')?.addEventListener('click', () => show(index - 1));
        slider.querySelector('[data-slider-next]')?.addEventListener('click', () => show(index + 1));

        let timer = window.setInterval(() => show(index + 1), 4500);
        slider.addEventListener('mouseenter', () => window.clearInterval(timer));
        slider.addEventListener('mouseleave', () => { timer = window.setInterval(() => show(index + 1), 4500); });
        slider.dataset.sliderReady = 'true';
    });
}

function initProductPages() {
    document.querySelectorAll('[data-product-gallery]:not([data-gallery-ready])').forEach((gallery) => {
        const mainImage = gallery.querySelector('[data-gallery-main]');
        gallery.querySelectorAll('[data-gallery-view]').forEach((button) => {
            button.addEventListener('click', () => {
                gallery.querySelectorAll('[data-gallery-view]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                mainImage?.classList.toggle('detail-view', button.dataset.galleryView === 'detail');
            });
        });
        gallery.dataset.galleryReady = 'true';
    });

    document.querySelectorAll('[data-quantity-picker]:not([data-quantity-ready])').forEach((picker) => {
        const input = picker.querySelector('input[name="quantity"]');
        const change = (step) => {
            if (!input) return;
            const minimum = Number(input.min || 1);
            const maximum = Number(input.max || 20);
            input.value = String(Math.min(maximum, Math.max(minimum, Number(input.value || 1) + step)));
        };
        picker.querySelector('[data-quantity-minus]')?.addEventListener('click', () => change(-1));
        picker.querySelector('[data-quantity-plus]')?.addEventListener('click', () => change(1));
        picker.dataset.quantityReady = 'true';
    });
}

function closeMobileMenu(header) {
    const toggle = header?.querySelector('[data-menu-toggle]');
    if (!header || !toggle) return;
    header.classList.remove('menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', toggle.dataset.openLabel || 'Menu');
}

function initMobileNavigation() {
    document.querySelectorAll('.site-header:not([data-menu-ready])').forEach((header) => {
        const toggle = header.querySelector('[data-menu-toggle]');
        if (!toggle) return;
        toggle.addEventListener('click', () => {
            const opening = !header.classList.contains('menu-open');
            document.querySelectorAll('.site-header.menu-open').forEach(closeMobileMenu);
            header.classList.toggle('menu-open', opening);
            toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
            toggle.setAttribute('aria-label', opening ? toggle.dataset.closeLabel : toggle.dataset.openLabel);
        });
        header.querySelectorAll('#main-navigation a').forEach((link) => link.addEventListener('click', () => closeMobileMenu(header)));
        header.dataset.menuReady = 'true';
    });
}

if (!document.documentElement.dataset.mobileMenuEvents) {
    document.addEventListener('click', (event) => {
        document.querySelectorAll('.site-header.menu-open').forEach((header) => {
            if (!header.contains(event.target)) closeMobileMenu(header);
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') document.querySelectorAll('.site-header.menu-open').forEach(closeMobileMenu);
    });
    window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
        if (event.matches) document.querySelectorAll('.site-header.menu-open').forEach(closeMobileMenu);
    });
    document.documentElement.dataset.mobileMenuEvents = 'true';
}

document.addEventListener('DOMContentLoaded', initEssentialsSliders);
document.addEventListener('turbo:load', initEssentialsSliders);
initEssentialsSliders();
document.addEventListener('DOMContentLoaded', initProductPages);
document.addEventListener('turbo:load', initProductPages);
initProductPages();
document.addEventListener('DOMContentLoaded', initMobileNavigation);
document.addEventListener('turbo:load', initMobileNavigation);
initMobileNavigation();
