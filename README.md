# Privaris — Blog & Podcast cybersécurité

Site web du média **Privaris**, construit en **Symfony 7** + **Tailwind CSS** + **EasyAdmin**.

---

## Stack

- **Symfony 7.1** (PHP 8.3)
- **Doctrine ORM 3** + MySQL 8
- **Twig** + **Tailwind CSS** (via `symfonycasts/tailwind-bundle`)
- **AssetMapper** + **Symfony UX** (Stimulus, Turbo, Live Components)
- **EasyAdmin 4** pour le back-office
- **scheb/2fa-bundle** pour la 2FA TOTP (Google Authenticator, 1Password…)
- **Symfony Mailer** (SMTP) + **Brevo API** pour la newsletter
- Hébergement cible : **PlanetHoster** (offre HybridCloud / World)

## Périmètre v1

- [x] Blog (home, liste, article, catégorie, tag)
- [x] Podcast (liste, épisode, flux RSS iTunes-compatible, lecteur HTML5)
- [x] Newsletter (double opt-in, Brevo, désinscription 1 clic, rate limiting)
- [x] Pages corporate (À propos, Contact, Mentions légales, RGPD)
- [x] Back-office admin sécurisé (login + 2FA TOTP + audit log + rate limiting)
- [x] Sitemap XML + robots.txt + Open Graph
- [x] Headers sécurité (CSP, HSTS, X-Frame-Options…)

## Installation locale

Prérequis : PHP 8.3+, Composer 2, MySQL 8 (ou MariaDB 10.6+), Node 20+ (pour Tailwind build seulement).

```bash
# 1. Cloner ou extraire le projet, puis :
cd privaris-web

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances front (plugin Tailwind typography)
npm install

# 4. Configurer l'environnement
cp .env.local.example .env.local
# → éditer .env.local avec :
#    - APP_SECRET (générer avec : php -r "echo bin2hex(random_bytes(16));")
#    - DATABASE_URL
#    - MAILER_DSN
#    - BREVO_API_KEY / BREVO_LIST_ID

# 5. Créer la base et les tables
php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Charger les données de démo (admin + catégories + articles + épisodes)
php bin/console doctrine:fixtures:load --no-interaction

# 7. Compiler Tailwind + importmap
php bin/console tailwind:build
php bin/console importmap:install

# 8. Lancer le serveur local
symfony server:start
# ou : php -S localhost:8000 -t public
```

Ouvrir [http://localhost:8000](http://localhost:8000).  
Admin : [http://localhost:8000/admin](http://localhost:8000/admin)  
**Compte par défaut (fixtures)** : `admin@privaris.fr` / `ChangeMe!2026` — à changer immédiatement.

## Créer un admin en prod

```bash
php bin/console app:create-admin votre@email.fr --name="Votre nom" --role=ROLE_SUPER_ADMIN
```

## Développement Tailwind en mode watch

```bash
php bin/console tailwind:build --watch
```

## Vérification du projet (à lancer en local après `composer install`)

Avant toute mise en prod, lancer cette batterie de vérifications :

```bash
# 1. Lint Twig / YAML / conteneur DI / Doctrine mapping
php bin/console lint:twig templates/
php bin/console lint:yaml config/
php bin/console lint:container
php bin/console doctrine:schema:validate

# 2. Analyse des dépendances (failles connues)
composer audit

# 3. Warmup du cache en prod pour détecter les erreurs de config
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup

# 4. Vérification des routes (aperçu)
php bin/console debug:router

# 5. (Optionnel) PHPStan / Psalm si installés
# vendor/bin/phpstan analyse src --level=6

# 6. Tests (à écrire dans tests/ — aucun livré par défaut)
# php bin/phpunit
```

Tous ces checks doivent passer sans erreur avant `git push` sur la branche de prod.

## Arborescence

```
privaris-web/
├── assets/              # JS (Stimulus) + CSS (Tailwind entry)
├── bin/console          # CLI Symfony
├── config/              # Config framework, packages, routes
├── migrations/          # Migrations Doctrine (générées)
├── public/              # Racine web (index.php + .htaccess)
├── src/
│   ├── Command/         # Commandes CLI custom
│   ├── Controller/      # Controllers publics + Admin (EasyAdmin)
│   ├── DataFixtures/    # Seed data
│   ├── Entity/          # Doctrine ORM entities
│   ├── EventSubscriber/ # Audit log login
│   ├── Form/            # Form types (Contact)
│   ├── Repository/      # Query builders
│   ├── Security/        # Handlers 2FA
│   └── Service/         # NewsletterService, PodcastFeedGenerator
├── templates/           # Twig
└── tailwind.config.js
```

## Sécurité

Le site étant thématique cybersécurité, il est **configuré défense-en-profondeur** :

- Authentification : Symfony Security + **2FA TOTP obligatoire** (activable au premier login via EasyAdmin)
- Rate limiting : login (5/min), newsletter (3/h), contact (5/h)
- Headers HTTP : CSP stricte, HSTS, X-Frame-Options DENY, X-Content-Type-Options, Referrer-Policy strict
- Logs d'audit : canal monolog dédié `security_audit`, rotation 90 jours
- Cookies : `SameSite=Lax`, `HttpOnly`, `Secure` en prod
- CSRF : actif sur tous les formulaires (login, contact, newsletter, admin)

## Déploiement PlanetHoster

Voir le **Guide de déploiement PlanetHoster** (PDF) dans le dossier parent du projet : `Guide-deploiement-PlanetHoster.pdf`.

## Licence

Propriétaire — tous droits réservés Privaris.
