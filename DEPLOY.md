# Déploiement de Privaris sur PlanetHoster (N0C)

Ce guide couvre le premier déploiement et les mises à jour suivantes. Il suppose que tu as déjà :

- Un compte PlanetHoster France actif (offre World ou Hybrid).
- Le domaine `privaris.fr` pointé sur ton hébergement.
- Un compte Brevo authentifié (DKIM, SPF, DMARC OK).
- Les enregistrements DNS Brevo en place.

---

## 1. Préparer la base de données dans N0C

Connecte-toi à N0C (mg.n0c.com), puis :

1. **Bases de données** → **Créer une base de données**
2. Nom : `privaris_prod`
3. Encodage : `utf8mb4_unicode_ci`
4. Crée un utilisateur dédié (par exemple `privaris_user`) avec un mot de passe fort.
5. Donne-lui tous les droits sur `privaris_prod` SAUF `GRANT OPTION`.
6. Note : utilisateur, mot de passe, nom de la DB, hôte (généralement `localhost`).

**Ne me transmets pas ces valeurs par chat.** Tu vas les utiliser localement sur le serveur.

## 2. Cloner le repo en SSH

Active SSH dans N0C : **Sécurité** → **Accès SSH** → activer.

Récupère tes credentials SSH (utilisateur, hôte, port). Connecte-toi :

```bash
ssh -p [PORT] [USER]@[HOST]
```

Place-toi dans le répertoire du projet web :

```bash
cd ~/public_html
```

Vide le contenu (s'il y a une page d'accueil PlanetHoster par défaut) :

```bash
rm -rf ~/public_html/*
```

Clone le repo :

```bash
git clone https://github.com/will0fried/privaris-web.git .
```

(Le `.` à la fin clone dans le dossier courant au lieu de créer un sous-dossier.)

## 3. Configurer le document root

Symfony sert depuis `public/`, pas depuis la racine. Dans N0C :

**Domaines** → **Gestion des domaines** → éditer `privaris.fr` → **Racine du document** : `/public_html/public`

(Si l'option n'existe pas, on peut aussi le faire avec un fichier `.htaccess` racine qui redirige vers `public/`. Mais l'option N0C est plus propre.)

## 4. Installer les dépendances

```bash
# Composer (PHP)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Node + Tailwind (génération du CSS final)
npm install
php bin/console asset-map:compile
```

**Note** : si `composer` n'est pas disponible globalement, télécharge-le :
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar ~/bin/composer  # ou utilise ./composer.phar partout
```

## 5. Configurer `.env.local`

```bash
cp .env.local.example .env.local
nano .env.local
```

Remplis chaque ligne avec tes vraies valeurs :

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET=` génère avec `php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"`
- `DATABASE_URL=` avec les credentials créés à l'étape 1
- `MAILER_DSN=` avec ta clé SMTP Brevo
- `BREVO_API_KEY=` avec ta clé API Brevo
- `BREVO_LIST_ID=` avec l'ID de ta liste Brevo
- `DEFAULT_URI=https://privaris.fr`
- `APP_ADMIN_INITIAL_PASSWORD=` un mot de passe long que tu mémorises (ou laisse vide, il sera généré)

Vérifie que les permissions empêchent la lecture publique :
```bash
chmod 600 .env.local
```

## 6. Lancer les migrations et fixtures

```bash
# Crée la structure de la DB
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Charge les articles depuis privaris-editorial/articles/
# Note : si privaris-editorial n'est pas sur le serveur, copie-le ou clone-le
# au même niveau que privaris-web, ou ajuste le chemin dans AppFixtures.php.
php bin/console doctrine:fixtures:load --no-interaction --env=prod
```

⚠️ Au premier `fixtures:load`, le mot de passe admin est affiché dans la sortie si tu as laissé `APP_ADMIN_INITIAL_PASSWORD` vide. **Note-le immédiatement**, il ne sera plus affiché.

## 7. Préparer le cache et les assets

```bash
php bin/console cache:warmup --env=prod
php bin/console asset-map:compile --env=prod
```

## 8. Activer SSL et forcer HTTPS

Dans N0C :

1. **Sécurité** → **SSL/TLS** → activer Let's Encrypt sur `privaris.fr` et `www.privaris.fr`
2. Une fois actif (5-10 min), activer **Forcer HTTPS**
3. Une semaine plus tard, après vérification que tout marche, ajouter HSTS

## 9. Vérifier en navigation privée

Ouvre `https://privaris.fr` en navigation privée :

- Page d'accueil charge correctement
- Articles s'affichent
- Le SOS teaser apparaît
- Le formulaire de contact fonctionne (envoie un message test, vérifie la réception sur ton mail perso)
- La newsletter fonctionne (inscris-toi avec une adresse de test, reçois le mail de confirmation, clique le lien, vérifie l'inscription dans Brevo)

## 10. Première connexion à l'admin

Va sur `https://privaris.fr/admin`.

Identifiant : `admin@privaris.fr`
Mot de passe : celui généré ou défini dans `APP_ADMIN_INITIAL_PASSWORD`.

Premier réflexe : **change le mot de passe immédiatement**, puis **active la 2FA** (scan QR avec Aegis ou Google Authenticator), puis **imprime les codes de récupération** et range-les en lieu sûr.

---

## Mises à jour suivantes

```bash
ssh -p [PORT] [USER]@[HOST]
cd ~/public_html

git pull origin main

# Si nouvelles dépendances
composer install --no-dev --optimize-autoloader

# Si nouvelles migrations
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Si CSS / assets ont changé
php bin/console asset-map:compile --env=prod

# Toujours
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

---

## Troubleshooting

**Erreur 500 sans message lisible.**
Active brièvement `APP_DEBUG=1` dans `.env.local`, recharge, regarde le message. Pense à remettre `APP_DEBUG=0` après diagnostic.

**Logs Symfony.**
`var/log/prod.log` côté serveur. Tail pendant un test :
```bash
tail -f var/log/prod.log
```

**Cache désynchronisé.**
```bash
php bin/console cache:clear --env=prod
```

**Permissions.**
Symfony a besoin d'écrire dans `var/`. Si erreur de permission :
```bash
chmod -R 755 var/
```

**Mailer qui ne marche pas.**
Vérifie sur Brevo que le domaine est bien "Authenticated" (vert partout). Vérifie la clé SMTP dans `.env.local`. Teste avec :
```bash
php bin/console messenger:consume async --env=prod
```
