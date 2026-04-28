/**
 * Privaris — Admin · Auto-décochage "À la une"
 *
 * Quand l'admin clique le toggle "À la une" sur un article dans le listing,
 * on décoche visuellement les autres toggles immédiatement (sans F5).
 *
 * Le backend (ArticleCrudController::enforceSingleFeatured) fait déjà le même
 * travail en base après chaque persist/update — ce JS n'est là que pour le
 * feedback visuel instantané.
 *
 * Heuristique d'identification de la colonne :
 *  - on inspecte les <th> du tableau EasyAdmin,
 *  - celui dont le texte contient "à la une" (insensible à la casse/accents)
 *    est la colonne ciblée.
 *
 * Robuste aux rechargements Turbo (délégation d'évènement au document).
 */
(function () {
    'use strict';

    var NEEDLE = 'a la une'; // normalisé, sans accent

    function normalize(s) {
        return (s || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    // Retourne l'index (0-based) de la colonne "À la une" d'un <table>.
    // null si on ne la trouve pas — dans ce cas on ne fait rien.
    function findFeaturedColumnIndex(table) {
        var headers = table.querySelectorAll('thead th');
        for (var i = 0; i < headers.length; i++) {
            if (normalize(headers[i].textContent).indexOf(NEEDLE) !== -1) {
                return i;
            }
        }
        return null;
    }

    // Récupère tous les checkboxes "featured" visibles (un par ligne).
    function collectFeaturedToggles(table, colIndex) {
        var inputs = [];
        var rows = table.querySelectorAll('tbody tr');
        for (var r = 0; r < rows.length; r++) {
            var cell = rows[r].children[colIndex];
            if (!cell) continue;
            var cb = cell.querySelector('input[type="checkbox"]');
            if (cb) inputs.push(cb);
        }
        return inputs;
    }

    // Pour une table donnée, câble la synchro sur la colonne featured.
    function wireTable(table) {
        if (table.dataset.featuredToggleWired === '1') return;

        var col = findFeaturedColumnIndex(table);
        if (col === null) return;

        var toggles = collectFeaturedToggles(table, col);
        if (toggles.length < 2) {
            // Pas besoin de synchro s'il n'y a qu'un seul article.
            table.dataset.featuredToggleWired = '1';
            return;
        }

        toggles.forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (!cb.checked) return;
                // L'utilisateur vient d'activer "à la une".
                // On décoche visuellement les autres pour refléter la règle
                // "une seule Une" que le backend enforce en base.
                toggles.forEach(function (other) {
                    if (other !== cb && other.checked) {
                        other.checked = false;
                        // Petit feedback visuel (le parent .form-switch d'EasyAdmin
                        // écoute déjà le change, mais on force aussi au cas où).
                        other.dispatchEvent(new Event('change', { bubbles: false }));
                    }
                });
            });
        });

        table.dataset.featuredToggleWired = '1';
    }

    function scan() {
        // On ne touche qu'au CRUD Article — heuristique sur l'URL
        // (la query-string EasyAdmin contient le FQCN du controller).
        var href = window.location.href;
        if (href.indexOf('ArticleCrudController') === -1) return;

        document.querySelectorAll('table').forEach(wireTable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }

    // Turbo (EasyAdmin 4 utilise Hotwired Turbo pour la navigation).
    document.addEventListener('turbo:load', scan);
    document.addEventListener('turbo:frame-load', scan);
})();
