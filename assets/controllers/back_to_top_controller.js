import { Controller } from '@hotwired/stimulus';

/**
 * back-to-top — bouton flottant qui remonte en haut de la page.
 *
 * Apparaît après avoir scrollé ~60% d'une hauteur d'écran.
 * Disparaît vers le top. Lié à `prefers-reduced-motion` pour le scroll.
 *
 * Markup :
 *   <button data-controller="back-to-top"
 *           data-action="click->back-to-top#scrollToTop"
 *           class="back-to-top">…</button>
 */
export default class extends Controller {
    connect() {
        this.threshold = Math.max(window.innerHeight * 0.6, 400);
        this.onScroll = this.onScroll.bind(this);
        window.addEventListener('scroll', this.onScroll, { passive: true });
        this.element.classList.add('is-hidden');
        // Premier état après montée (au cas où on arrive sur une ancre)
        this.onScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
    }

    onScroll() {
        if (window.scrollY > this.threshold) {
            this.element.classList.remove('is-hidden');
        } else {
            this.element.classList.add('is-hidden');
        }
    }

    scrollToTop(event) {
        event.preventDefault();
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    }
}
