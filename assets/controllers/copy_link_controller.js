import { Controller } from '@hotwired/stimulus';

/**
 * copy-link — copie un lien dans le presse-papier et affiche "Copié !" 2s.
 *
 * Markup :
 *   <button data-controller="copy-link"
 *           data-copy-link-url-value="https://…"
 *           data-copy-link-label-value="Copier le lien"
 *           data-action="click->copy-link#copy">
 *       <span data-copy-link-target="label">Copier le lien</span>
 *   </button>
 */
export default class extends Controller {
    static values = {
        url: String,
        label: { type: String, default: 'Copier le lien' },
        successLabel: { type: String, default: '✓ Copié' },
    };
    static targets = ['label'];

    async copy(event) {
        event.preventDefault();
        const url = this.urlValue || window.location.href;

        try {
            await navigator.clipboard.writeText(url);
        } catch (_) {
            // Fallback vieux navigateur
            const el = document.createElement('textarea');
            el.value = url;
            el.setAttribute('readonly', '');
            el.style.position = 'absolute';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            el.select();
            try { document.execCommand('copy'); } catch (_) {}
            document.body.removeChild(el);
        }

        if (this.hasLabelTarget) {
            const previous = this.labelTarget.textContent;
            this.labelTarget.textContent = this.successLabelValue;
            this.element.classList.add('is-copied');
            clearTimeout(this._timer);
            this._timer = setTimeout(() => {
                this.labelTarget.textContent = previous;
                this.element.classList.remove('is-copied');
            }, 1800);
        }
    }

    disconnect() {
        clearTimeout(this._timer);
    }
}
