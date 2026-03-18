# La Petite Maison de l'Épouvante — Demo Symfony

Application de démonstration développée dans le cadre du projet CESI (Bloc 3 Dev).

**Fonctionnalité implémentée :** Catalogue produits avec système de recommandations.

**Stack :** PHP 8.4, Symfony 7, MySQL 8.0, Nginx, Docker, GitHub Actions CI/CD, SonarQube.

**Déploiement :** Coolify — auto-déploiement via webhook Git sur push `déploiement-fonctionnelle`.

**Préprod :** https://demo-nginx.dev.fabdevlab.fr

---

## Architecture

```
GitHub (push déploiement-fonctionnelle)
    ↓ GitHub Actions (8 étapes)
    ↓ webhook Coolify
Coolify (PaaS Docker — fabdevlab.fr)
    ├─ php        → PHP 8.4-FPM (Symfony)
    ├─ nginx      → Reverse proxy HTTPS
    ├─ mysql      → Base de données MySQL 8.0
    ├─ phpmyadmin → Interface BDD (préprod)
    └─ sonarqube  → Analyse qualité code
```

---

## Développement local

### Prérequis
- [Docker Desktop](https://www.docker.com/)
- [Git](https://git-scm.com/)

### Installation

```bash
# 1. Cloner le projet
git clone https://github.com/Mikadjx/demo-symfony.git
cd demo-symfony
git checkout déploiement-fonctionnelle

# 2. Créer le fichier de configuration local
touch .env.local

# 3. Renseigner .env.local (contacter le mainteneur pour les valeurs)
# DATABASE_URL=mysql://USER:PASSWORD@mysql:3306/DATABASE?serverVersion=8.0
# APP_SECRET=...

# 4. Lancer les conteneurs
docker compose up -d --build

# 5. Installer les dépendances
docker compose exec php composer install

# 6. Initialiser la base de données
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

> Les credentials ne sont jamais commités. Contacte le mainteneur du projet pour les valeurs de configuration locale.

---

## Démarrage rapide (sessions suivantes)

```bash
docker compose up -d    # démarrer
docker compose down     # arrêter
```

---

## Pipeline CI/CD — 8 étapes

Le pipeline GitHub Actions se déclenche sur push/PR vers `déploiement-fonctionnelle` :

| # | Étape | Description |
|---|---|---|
| 1 | **Build** | Setup PHP 8.4, composer install, cache vendor |
| 2 | **Security Scan** | Analyse statique SonarQube (demo-sonarcube.dev.fabdevlab.fr) |
| 3 | **Unit Tests** | PHPUnit `--testsuite=unit --testdox` |
| 4 | **Functional Tests** | PHPUnit `--testsuite=functional` + coverage Xdebug |
| 5 | **Non-Régression Pré-Deploy** | Vérification baseline préprod (HTTP 200/302) |
| 6 | **Deploy** | Trigger webhook Coolify → déploiement préprod |
| 7 | **Non-Régression Post-Deploy** | Vérification préprod après déploiement |
| 8 | **Load Tests** | k6 cloud → Grafana Cloud (100 VU, Paris) |

---

## Tests

```bash
# Tests unitaires
docker compose exec php php vendor/bin/phpunit --testsuite=unit --testdox

# Tests fonctionnels
docker compose exec php php vendor/bin/phpunit --testsuite=functional --testdox

# Tests E2E (Playwright) — nécessite E2E_USER_EMAIL / E2E_USER_PASSWORD
npx playwright test

# Tests de charge (k6 cloud) — nécessite K6_CLOUD_TOKEN / GRAFANA_TOKEN
k6 cloud run k6/load-test.js
```

---

## Commandes utiles

```bash
# État des conteneurs
docker ps

# Logs Symfony
docker compose logs php -f

# Shell PHP
docker compose exec php sh

# Vider le cache
docker compose exec php php bin/console cache:clear

# Lister les routes
docker compose exec php php bin/console debug:router

# Valider le schéma BDD
docker compose exec php php bin/console doctrine:schema:validate

# Recharger les fixtures
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

---

## Technologies

| Catégorie | Technologie |
|---|---|
| Langage | PHP 8.4 |
| Framework | Symfony 7.x |
| ORM | Doctrine |
| Base de données | MySQL 8.0 |
| Serveur web | Nginx Alpine |
| Conteneurisation | Docker / Docker Compose |
| Hébergement | Coolify (PaaS self-hosted — fabdevlab.fr) |
| CI/CD | GitHub Actions (8 étapes) |
| Qualité code | SonarQube Community |
| Tests unitaires/fonctionnels | PHPUnit + Xdebug (coverage) |
| Tests E2E | Playwright |
| Tests de charge | k6 Cloud → Grafana Cloud |
| Authentification | Sessions Symfony (web) |
| Back-office | EasyAdmin |
