import { Controller } from '@hotwired/stimulus';

/**
 * reading-progress — barre fine en haut de page qui suit
 * la progression de lecture d'un article.
 *
 * Usage sur la <article> :
 *   data-controller="reading-progress"
 *   data-reading-progress-target="bar"
 *
 * La target "bar" est un <div> placé en haut de page, typiquement sticky.
 */
export default class extends Controller {
    static targets = ['bar'];

    connect() {
        this.update = this.update.bind(this);
        window.addEventListener('scroll', this.update, { passive: true });
        window.addEventListener('resize', this.update, { passive: true });
        this.update();
    }

    disconnect() {
        window.removeEventListener('scroll', this.update);
        window.removeEventListener('resize', this.update);
    }

    update() {
        if (!this.hasBarTarget) return;

        const rect = this.element.getBoundingClientRect();
        const articleHeight = this.element.offsetHeight;
        const viewportHeight = window.innerHeight;

        // Position du début de l'article par rapport au top du viewport
        const scrolled = -rect.top;
        // Zone totale de défilement : hauteur article - hauteur viewport
        const total = Math.max(articleHeight - viewportHeight, 1);

        let pct = (scrolled / total) * 100;
        pct = Math.max(0, Math.min(100, pct));

        this.barTarget.style.width = pct.toFixed(2) + '%';
    }
}
