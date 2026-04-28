# Privaris

> Cybersécurité au quotidien, expliquée sans jargon. Pour vous, vos proches,
> votre famille.

Privaris est un média indépendant français qui rend la cybersécurité accessible
au grand public. Pas d'expertise pro requise pour lire les articles, pas de
publicité, pas de tracker.

Site en ligne : [privaris.fr](https://privaris.fr) · Code source ouvert sous
licence AGPL-3.0.

## Stack

- **Symfony 7** (PHP 8.3)
- **Doctrine ORM 3** + MySQL 8
- **Twig** + **Tailwind CSS v4** via Asset Mapper
- **Stimulus** pour les interactions front
- **EasyAdmin** pour le back-office, avec **2FA TOTP** (`scheb/2fa-bundle`)
- **Symfony Mailer** + **Brevo** pour les emails et la newsletter

## Périmètre fonctionnel

- Articles, catégories, tags, alertes, "À la une"
- Pages SOS d'urgence (5 scénarios : compte piraté, phishing, virus,
  fuite de données, sextorsion)
- Podcast (épisodes, flux RSS iTunes-compatible, lecteur HTML5)
- Newsletter (double opt-in, Brevo, désinscription en un clic)
- Recherche dans les articles
- Boutons de partage social sans tracker (Facebook, LinkedIn, WhatsApp,
  X, Email, Copier le lien) + Web Share API natif sur mobile
- Pages corporate : à propos, contact, mentions légales, confidentialité
- Sitemap XML, schema.org, Open Graph
- En-têtes de sécurité (CSP, HSTS, X-Frame-Options, etc.)
- Rate limiting (login, contact, newsletter)

## Installation locale

Prérequis : PHP 8.3+, Composer 2, MySQL 8 (ou MariaDB 10.6+), Node 20+.

```bash
git clone https://github.com/will0fried/privaris-web.git
cd privaris-web

composer install
npm install

cp .env.local.example .env.local
# Édite .env.local avec tes valeurs (DB, Brevo, etc.)

php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

php bin/console asset-map:compile
symfony server:start
```

Le site répond sur [http://localhost:8000](http://localhost:8000).
L'admin est sur [/admin](http://localhost:8000/admin).

Le mot de passe admin est généré aléatoirement par les fixtures et affiché en
sortie de commande. Note-le, il ne sera plus jamais affiché. Tu peux aussi le
fixer toi-même via la variable `APP_ADMIN_INITIAL_PASSWORD` dans `.env.local`.

## Le contenu éditorial

Les articles vivent dans un dépôt séparé `privaris-editorial/articles/`,
au format Markdown avec frontmatter YAML. Les fixtures les chargent en base
au build. Ce dépôt n'est pas dans cette repo : c'est volontaire, les
contenus éditoriaux n'ont pas le même cycle de vie que le code.

## Déploiement en production

Voir [DEPLOY.md](./DEPLOY.md) pour le guide complet de déploiement chez
PlanetHoster (création de la DB, configuration `.env.local`, migrations,
SSL, vérifications).

## Vérifications avant push

```bash
php bin/console lint:twig templates/
php bin/console lint:yaml config/
php bin/console lint:container
php bin/console doctrine:schema:validate
composer audit
```

## Arborescence

```
privaris-web/
├── assets/              JS (Stimulus) + CSS (Tailwind entry)
├── bin/console          CLI Symfony
├── config/              Config framework, packages, routes
├── migrations/          Migrations Doctrine
├── public/              Racine web (index.php + .htaccess)
├── src/
│   ├── Controller/      Controllers publics + Admin EasyAdmin
│   ├── DataFixtures/    Seed data (admin, catégories, tags)
│   ├── Entity/          Entités Doctrine
│   ├── EventSubscriber/ Audit log login + en-têtes sécurité
│   ├── Form/            Form types
│   ├── Repository/      Query builders
│   ├── Security/        Handlers 2FA
│   └── Service/         SosCatalog, NewsletterService, etc.
├── templates/           Twig
├── DEPLOY.md            Guide de déploiement
└── LICENSE              AGPL-3.0
```

## Sécurité

Le site étant un média cyber, il applique la défense en profondeur :

- 2FA TOTP obligatoire pour l'admin
- Rate limiting (login, contact, newsletter)
- En-têtes HTTP stricts (CSP, HSTS, X-Frame-Options DENY, Referrer-Policy)
- Logs d'audit dédiés (`security_audit`, rotation 90 jours)
- Cookies `SameSite=Lax`, `HttpOnly`, `Secure` en prod
- CSRF actif sur tous les formulaires
- Aucun secret dans le repo, tout passe par `.env.local` (non versionné)

Si tu trouves une faille, écris à `contact@privaris.fr` plutôt que d'ouvrir
une issue publique. Réponse sous 7 jours.

## Contribuer

Les pull requests sont les bienvenues, surtout pour :

- Corrections de coquilles dans les contenus rendus côté code (textes des
  pages SOS, copies UI, mentions légales).
- Améliorations d'accessibilité (a11y).
- Optimisations de performance (cache, requêtes Doctrine, assets).
- Nouvelles intégrations sobres et privacy-friendly.

Pour des contributions plus larges (nouvelles entités, refactor d'une
fonctionnalité), ouvre d'abord une issue pour discuter du périmètre.

## Licence

[AGPL-3.0-or-later](./LICENSE).

Tu peux librement utiliser, modifier et redistribuer ce code, à condition
que toute version modifiée publiée ou hébergée publiquement reste sous
AGPL-3.0 et que les sources soient accessibles. C'est cohérent avec le
positionnement du média : transparent et auditables.

Le contenu éditorial (articles, podcast, illustrations) est protégé
séparément par le droit d'auteur classique : citation et lien autorisés,
reproduction soumise à autorisation. Voir
[mentions légales](https://privaris.fr/mentions-legales).
