import { Controller } from '@hotwired/stimulus';

/*
 * Radar « Mon Labo ».
 * Place chaque détection (un article) sur le scope : plus l'entrée est récente,
 * plus elle est proche du centre. L'angle d'or répartit les points régulièrement.
 * La pulsation de chaque blip est calée sur le balayage (variable --delay).
 */
export default class extends Controller {
    static targets = ['blip'];
    static values = { period: { type: Number, default: 6 } };

    connect() {
        const blips = this.blipTargets;
        const n = blips.length;
        const R = 40; // rayon max, en % du scope

        blips.forEach((blip, i) => {
            const r = n > 1 ? 0.30 + (i / (n - 1)) * 0.58 : 0;
            const angle = (i * 137.508) % 360; // angle d'or
            const rad = (angle * Math.PI) / 180;

            blip.style.left = (50 + r * R * Math.cos(rad)).toFixed(2) + '%';
            blip.style.top = (50 + r * R * Math.sin(rad)).toFixed(2) + '%';
            blip.style.setProperty('--delay', ((angle / 360) * this.periodValue).toFixed(2) + 's');
        });
    }
}
