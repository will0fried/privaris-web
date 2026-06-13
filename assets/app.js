import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// Masque l'écran de chargement dès que le CSS applicatif est appliqué.
if (typeof window !== 'undefined' && typeof window.__privarisHideSplash === 'function') {
    window.__privarisHideSplash();
}

console.info('[Privaris] UI bootstrapped.');
