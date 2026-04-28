import { Controller } from '@hotwired/stimulus';

/**
 * mobile-menu — volet déroulant animé + lock-scroll.
 *
 * Le bouton porte data-controller="mobile-menu" et data-action="click->mobile-menu#toggle".
 * Le volet lui-même est identifié par son id "mobile-menu" (voir _nav.html.twig).
 *
 * Comportement :
 *   • ouvre / ferme via la classe `is-open` (animation CSS)
 *   • bloque le scroll du body pendant l'ouverture (overflow hidden + padding compensatoire)
 *   • se ferme auto sur : tap d'un lien interne, resize ≥ md, nav Turbo, Escape
 *   • aria-expanded sur le bouton, aria-hidden sur le panneau
 */
export default class extends Controller {
    connect() {
        this.panel = document.getElementById('mobile-menu');
        this.onResize = this.onResize.bind(this);
        this.onTurboBefore = this.close.bind(this);
        this.onKey = this.onKey.bind(this);
        this.onPanelClick = this.onPanelClick.bind(this);

        window.addEventListener('resize', this.onResize, { passive: true });
        document.addEventListener('turbo:before-visit', this.onTurboBefore);
        document.addEventListener('keydown', this.onKey);
        if (this.panel) this.panel.addEventListener('click', this.onPanelClick);

        this.element.setAttribute('aria-expanded', 'false');
        this.element.setAttribute('aria-controls', 'mobile-menu');
        if (this.panel) this.panel.setAttribute('aria-hidden', 'true');
    }

    disconnect() {
        window.removeEventListener('resize', this.onResize);
        document.removeEventListener('turbo:before-visit', this.onTurboBefore);
        document.removeEventListener('keydown', this.onKey);
        if (this.panel) this.panel.removeEventListener('click', this.onPanelClick);
        this._unlockBody();
    }

    toggle(event) {
        event.preventDefault();
        if (!this.panel) return;
        if (this.panel.classList.contains('is-open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (!this.panel) return;
        this.panel.classList.remove('hidden');
        // force reflow pour que la transition démarre
        // eslint-disable-next-line no-unused-expressions
        this.panel.offsetHeight;
        this.panel.classList.add('is-open');
        this.element.setAttribute('aria-expanded', 'true');
        this.panel.setAttribute('aria-hidden', 'false');
        this._lockBody();
    }

    close() {
        if (!this.panel) return;
        this.panel.classList.remove('is-open');
        this.element.setAttribute('aria-expanded', 'false');
        this.panel.setAttribute('aria-hidden', 'true');
        this._unlockBody();
        // masquer après la transition
        clearTimeout(this._hideTimer);
        this._hideTimer = setTimeout(() => {
            if (this.panel && !this.panel.classList.contains('is-open')) {
                this.panel.classList.add('hidden');
            }
        }, 280);
    }

    onResize() {
        if (window.innerWidth >= 768) this.close();
    }

    onKey(event) {
        if (event.key === 'Escape' && this.panel && this.panel.classList.contains('is-open')) {
            this.close();
        }
    }

    onPanelClick(event) {
        // ferme le menu si l'utilisateur tape un lien
        const link = event.target.closest('a');
        if (link) this.close();
    }

    _lockBody() {
        // bloque le scroll sans sauter : compense la scrollbar desktop (inutile sur mobile mais safe)
        const scrollbarW = window.innerWidth - document.documentElement.clientWidth;
        document.body.style.overflow = 'hidden';
        if (scrollbarW > 0) document.body.style.paddingRight = `${scrollbarW}px`;
    }

    _unlockBody() {
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}
