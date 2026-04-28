import { Controller } from '@hotwired/stimulus';

/*
 * Newsletter Stimulus controller
 * Usage in template:
 *   <form data-controller="newsletter" data-action="submit->newsletter#submit">
 */
export default class extends Controller {
    static targets = ['email', 'submitBtn', 'message'];
    static values = { endpoint: String };

    async submit(event) {
        event.preventDefault();
        const email = this.emailTarget.value.trim();
        if (!email) return;

        this.submitBtnTarget.disabled = true;
        this.submitBtnTarget.textContent = '…';

        try {
            const response = await fetch(this.endpointValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: new URLSearchParams({ email }),
            });
            const data = await response.json();

            this.messageTarget.textContent = data.message || (response.ok ? 'Merci ! Un mail de confirmation vous a été envoyé.' : 'Une erreur est survenue.');
            this.messageTarget.classList.toggle('text-ok', response.ok);
            this.messageTarget.classList.toggle('text-alert', !response.ok);

            if (response.ok) this.emailTarget.value = '';
        } catch (e) {
            this.messageTarget.textContent = 'Erreur réseau. Réessayez dans un instant.';
            this.messageTarget.classList.add('text-alert');
        } finally {
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.textContent = 'S’abonner';
        }
    }
}
