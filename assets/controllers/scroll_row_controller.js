import { Controller } from '@hotwired/stimulus';

/**
 * scroll-row — rail horizontal avec flèches + progress bar.
 *
 * Attendu dans le DOM :
 *   <div data-controller="scroll-row" data-scroll-row>
 *     <button data-scroll-prev>←</button>
 *     <button data-scroll-next>→</button>
 *     <div data-scroll-track>...items...</div>
 *     <span data-scroll-progress></span>
 *   </div>
 *
 * Le controller s'attache en racine `[data-scroll-row]`, et trouve
 * ses enfants via data-attributes (pas besoin de targets Stimulus classiques).
 */
export default class extends Controller {
    connect() {
        this.track = this.element.querySelector('[data-scroll-track]');
        this.prevBtn = this.element.querySelector('[data-scroll-prev]');
        this.nextBtn = this.element.querySelector('[data-scroll-next]');
        this.progress = this.element.querySelector('[data-scroll-progress]');

        if (!this.track) return;

        this.onScroll = this.onScroll.bind(this);
        this.onPrev = this.onPrev.bind(this);
        this.onNext = this.onNext.bind(this);
        this.onResize = this.onResize.bind(this);

        this.track.addEventListener('scroll', this.onScroll, { passive: true });
        window.addEventListener('resize', this.onResize, { passive: true });
        if (this.prevBtn) this.prevBtn.addEventListener('click', this.onPrev);
        if (this.nextBtn) this.nextBtn.addEventListener('click', this.onNext);

        // État initial
        this.update();
    }

    disconnect() {
        if (this.track) this.track.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onResize);
        if (this.prevBtn) this.prevBtn.removeEventListener('click', this.onPrev);
        if (this.nextBtn) this.nextBtn.removeEventListener('click', this.onNext);
    }

    onScroll() {
        this.update();
    }

    onResize() {
        this.update();
    }

    onPrev() {
        this.track.scrollBy({ left: -this.step(), behavior: 'smooth' });
    }

    onNext() {
        this.track.scrollBy({ left: this.step(), behavior: 'smooth' });
    }

    /** Taille d'un "saut" : largeur d'un item + gap (approx) */
    step() {
        const first = this.track.querySelector('.scroll-row__item');
        if (!first) return this.track.clientWidth * 0.8;
        const gap = parseFloat(getComputedStyle(this.track).columnGap || '0') || 28;
        return first.offsetWidth + gap;
    }

    update() {
        const max = this.track.scrollWidth - this.track.clientWidth;
        const atStart = this.track.scrollLeft <= 2;
        const atEnd = this.track.scrollLeft >= max - 2;

        if (this.prevBtn) this.prevBtn.disabled = atStart;
        if (this.nextBtn) this.nextBtn.disabled = atEnd;

        if (this.progress && max > 0) {
            // largeur de la barre = ratio visible (clientWidth / scrollWidth)
            const ratio = this.track.clientWidth / this.track.scrollWidth;
            const pct = Math.min(1, Math.max(0.15, ratio)) * 100;
            this.progress.style.width = pct.toFixed(1) + '%';
            // position = scroll progress * (100% - pct)
            const pos = (this.track.scrollLeft / max) * (100 - pct);
            this.progress.style.left = pos.toFixed(1) + '%';
        }
    }
}
