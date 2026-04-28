import { Controller } from '@hotwired/stimulus';

/**
 * web-share — utilise navigator.share() pour le partage natif.
 *
 * Sur mobile (iOS/Android), ouvre le menu système : Messages, Signal, Telegram,
 * Mail, AirDrop, etc. — l'utilisateur partage où il veut.
 *
 * Sur desktop, l'API n'est généralement pas supportée → on garde le bouton caché
 * et l'utilisateur passe par les boutons explicites (Facebook, LinkedIn, etc.).
 *
 * Markup minimum :
 *   <div data-controller="web-share"
 *        data-web-share-url-value="…"
 *        data-web-share-title-value="…"
 *        data-web-share-text-value="…">
 *     <button data-web-share-target="nativeBtn"
 *             data-action="click->web-share#share">Partager</button>
 *   </div>
 *
 * Le bouton est rendu avec `is-hidden` côté serveur. On le révèle seulement
 * si navigator.share existe — évite le flash sur les machines qui ne le
 * supportent pas.
 */
export default class extends Controller {
    static values = {
        url:   String,
        title: String,
        text:  String,
    };
    static targets = ['nativeBtn'];

    connect() {
        if (typeof navigator !== 'undefined' && typeof navigator.share === 'function' && this.hasNativeBtnTarget) {
            this.nativeBtnTarget.classList.remove('is-hidden');
        }
    }

    async share(event) {
        event.preventDefault();

        if (typeof navigator === 'undefined' || typeof navigator.share !== 'function') {
            return;
        }

        try {
            await navigator.share({
                url:   this.urlValue   || window.location.href,
                title: this.titleValue || document.title,
                text:  this.textValue  || '',
            });
        } catch (err) {
            // AbortError = l'utilisateur a fermé le menu, c'est OK silencieux.
            if (err && err.name !== 'AbortError') {
                // Autre erreur : on log mais on ne casse pas la page.
                console.warn('Web Share failed:', err);
            }
        }
    }
}
