# Déploiement — Privaris · Mon Labo (statique)

Le labo radar a **remplacé** l'ancien site dans ce projet. Comme c'est un site **statique**,
tout le dispositif Symfony du guide PlanetHoster (MySQL, PHP 8.3, Composer, npm, `.env.local`,
migrations, admin, Brevo) **n'est plus nécessaire**. Ton domaine pointe déjà sur `public/` :
il suffit d'y avoir les nouveaux fichiers.

## Ce qui est servi maintenant

```
public/
├── index.html          ← la page du labo (radar)
├── css/style.css
├── js/episodes.js      ← LE fichier où tu ajoutes un épisode
├── js/radar.js
├── favicon.svg
└── .htaccess           ← sert index.html
    .htaccess.symfony.bak  ← l'ancien .htaccess Symfony, conservé
```

Rien n'a été détruit : l'appli Symfony (src/, templates/, vendor/…) est toujours là, simplement
plus servie.

## Déployer — Git (comme avant)

En SSH sur PlanetHoster :

```bash
cd ~/www/privaris
git pull
```

C'est tout. Le domaine pointant déjà sur `public/`, le radar est en ligne. **Pas de composer,
pas de npm, pas de base.** (Avant : `git add -A && git commit -m "Labo radar" && git push` en local.)

## Déployer — SFTP (glisser-déposer)

Avec FileZilla / Cyberduck, envoie le contenu de `public/` dans `~/www/privaris/public/` :
`index.html`, `css/`, `js/`, `favicon.svg`, et le nouveau `.htaccess` (écrase l'ancien).

## Prévisualiser en local

Double-clique sur `public/index.html` — les chemins sont relatifs, ça marche tel quel.
(Plus propre : `cd public && python3 -m http.server`, puis http://localhost:8000)

## Ajouter un épisode

Édite `public/js/episodes.js` : un objet = une détection sur le radar. (Détaillé dans le
README du projet `privaris-site`.) Puis redéploie (`git pull` ou SFTP).

## Revenir en arrière

L'ancien site est intact : restaure `.htaccess.symfony.bak` → `.htaccess` et le Symfony d'origine
ressert.

## Deux finitions (optionnel, pour le « zéro traceur »)

- Auto-héberger les polices (Inter / Source Serif) au lieu de Google Fonts.
- Brancher la newsletter dans le `<form>` de `index.html`.
