# La Petite Maison de l'Épouvante — Demo Symfony

Application de démonstration développée dans le cadre du projet CESI — BLOC 3.
Fonctionnalité implémentée : **Catalogue produits sécurisé par authentification session**.

---

## Présentation

Ce prototype illustre une approche **DevSecOps** complète :
- Application Symfony 7 avec authentification par session (formulaire de login + CSRF)
- Pipeline CI/CD automatisé (GitHub Actions)
- Déploiement continu via Coolify + Docker
- Qualité mesurée en continu (SonarQube, PHPUnit, k6, Grafana Cloud)

---

## Architecture

```
Internet (HTTPS/TLS)
      │
  [ Nginx ]  ← reverse proxy + SSL termination
      │
  [ PHP-FPM 8.4 ]  ← Symfony 7 + Security + Doctrine ORM
      │
  [ MySQL 8.0 ]  ← données persistantes
```

---

## Environnements

| Environnement | URL | Notes |
|---|---|---|
| Preprod | `https://demo-nginx.dev.fabdevlab.fr/` | Déploiement automatique sur push |
| SonarQube | `https://demo-sonarcube.dev.fabdevlab.fr` | Scan SAST (accès restreint) |

---

## Routes principales

| Méthode | Route | Auth requise | Description |
|---|---|---|---|
| `GET` | `/login` | Non | Page de connexion (formulaire + CSRF) |
| `POST` | `/login` | Non | Soumission du formulaire → session |
| `GET` | `/product` | Oui (session) | Catalogue produits → redirect `/login` si non connecté |
| `GET` | `/` | Non | Page d'accueil |

### Comportement de sécurité

- Accès à `/product` sans session active → redirection `302` vers `/login`
- Formulaire de login protégé par token CSRF (généré automatiquement par Symfony)
- Mauvaises credentials → retour sur `/login` avec message d'erreur `.alert-danger`
- Login valide → accès au catalogue (`200`)

---

## Pipeline CI/CD

Le pipeline GitHub Actions se déclenche sur chaque `push` sur la branche `déploiement-fonctionnelle`.

### Étapes séquentielles (toutes bloquantes)

| # | Job | Description |
|---|-----|-------------|
| 1 | **Build** | PHP 8.4, Composer install, cache vendor |
| 2 | **Security Scan** | SonarQube SAST — vulnérabilités, code smells |
| 3 | **Tests Unitaires** | PHPUnit — services, entités, logique métier |
| 4 | **Tests Fonctionnels** | PHPUnit WebTestCase — sécurité session (redirect, login, catalogue) + coverage |
| 5 | **Non-Régression Pré-Deploy** | Vérification baseline preprod (HTTP 200/302) |
| 6 | **Deploy Preprod** | Webhook Coolify → redéploiement Docker automatique |
| 7 | **Non-Régression Post-Deploy** | Vérification preprod après déploiement |
| 8 | **Load Test** | k6 cloud → métriques envoyées à Grafana Cloud |

---

## Conteneurs Docker

| Service | Image | Rôle |
|---|---|---|
| `php` | `php:8.4-fpm-alpine` | Application Symfony (PHP-FPM) |
| `nginx` | `nginx:alpine` | Serveur web + SSL |
| `mysql` | `mysql:8.0` | Base de données |
| `phpmyadmin` | `phpmyadmin` | Interface BDD (preprod uniquement) |
| `sonarqube` | `sonarqube:community` | Analyse statique (dev local) |

> **Sécurité** : phpMyAdmin est désactivé en production. SonarQube n'est exposé qu'en local ou via l'instance dédiée.

---

## Développement local

### Prérequis
- Docker Desktop (Linux, macOS ou Windows avec WSL2)
- Git

### Lancement

```bash
# 1. Cloner le projet
git clone https://github.com/Mikadjx/demo-symfony.git
cd demo-symfony

# 2. Configurer les variables d'environnement locales
# Créer .env.local avec les valeurs de votre environnement
# (contacter le mainteneur pour les valeurs — ne pas committer)

# 3. Démarrer les conteneurs
docker compose up -d --build

# 4. Initialiser la base de données (première fois)
docker exec php php bin/console doctrine:migrations:migrate --no-interaction
docker exec php php bin/console doctrine:fixtures:load --no-interaction
```

### Variables d'environnement requises

Créer un fichier `.env.local` (non versionné) avec :
```dotenv
APP_SECRET=<valeur secrète>
DATABASE_URL=mysql://<user>:<password>@mysql:3306/<database>?serverVersion=8.0
```

> Les valeurs de développement sont disponibles auprès du mainteneur du projet. Ne jamais committer de secrets dans le dépôt.

### Commandes utiles

```bash
# Conteneurs
docker compose up -d          # Démarrer
docker compose down           # Arrêter
docker compose down -v        # Arrêter + supprimer les volumes
docker compose up -d --build  # Reconstruire les images

# Symfony
docker exec php php bin/console cache:clear
docker exec php php bin/console doctrine:migrations:migrate --no-interaction
docker exec php php bin/console doctrine:fixtures:load --no-interaction
docker exec php php bin/console debug:router

# Logs
docker logs -f php
docker logs -f nginx

# Accès shell
docker exec -it php sh
```

---

## Tests

### Lancer les tests localement

```bash
# Tests unitaires
php vendor/bin/phpunit --testsuite=unit --testdox

# Tests fonctionnels (nécessite MySQL démarré)
php vendor/bin/phpunit --testsuite=functional --testdox

# Avec coverage
php -d memory_limit=512M vendor/bin/phpunit \
  --testsuite=functional \
  --coverage-clover coverage.xml
```

### Test de charge (k6)

```bash
# Local (sans cloud)
k6 run -e BASE_URL=https://demo-nginx.dev.fabdevlab.fr k6/load-test.js

# Via Grafana Cloud (pipeline CI)
k6 cloud run k6/load-test.js
```

---

## Technologies

| Couche | Technologie | Version |
|---|---|---|
| Framework | Symfony | 7.x |
| Langage | PHP | 8.4 |
| ORM | Doctrine | 3.x |
| Auth | Symfony Security (session + CSRF) | — |
| Base de données | MySQL | 8.0 |
| Serveur web | Nginx | Alpine |
| Conteneurisation | Docker / Docker Compose | — |
| CI/CD | GitHub Actions | — |
| Déploiement | Coolify | — |
| Qualité | SonarQube | Community |
| Tests charge | k6 | — |
| Observabilité | Grafana Cloud | — |

---

## Workflow Git

La branche de déploiement est `déploiement-fonctionnelle`. Tout push sur cette branche déclenche le pipeline complet.

```bash
git checkout déploiement-fonctionnelle
git add <fichiers modifiés>
git commit -m "description claire de la modification"
git push origin déploiement-fonctionnelle
```

> Ne pas pousser directement sur `main`. Les pull requests passent par la revue de code.
