import { Controller } from '@hotwired/stimulus';

/**
 * reveal — fade + slide léger au scroll quand l'élément entre dans le viewport.
 *
 * Usage :
 *   <div data-controller="reveal" class="reveal">contenu</div>
 *   Optionnel : data-reveal-delay-value="100" pour staggered.
 *
 * Si `prefers-reduced-motion` → pas d'animation, juste visible immédiatement.
 */
export default class extends Controller {
    static values = { delay: { type: Number, default: 0 } };

    connect() {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Si motion réduit : on marque visible sans anim
        if (reduced) {
            this.element.classList.add('is-visible');
            return;
        }

        // Pas de IntersectionObserver ? Fallback visible.
        if (!('IntersectionObserver' in window)) {
            this.element.classList.add('is-visible');
            return;
        }

        this.observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        this.element.classList.add('is-visible');
                    }, this.delayValue);
                    this.observer.unobserve(this.element);
                }
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });

        this.observer.observe(this.element);
    }

    disconnect() {
        if (this.observer) this.observer.disconnect();
    }
}
