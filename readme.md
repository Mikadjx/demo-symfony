# La Petite Maison de l'Épouvante — Demo Symfony

Application de démonstration développée dans le cadre du projet CESI (Bloc 3 Dev).

**Fonctionnalité implémentée :** Catalogue produits avec système de recommandations.

**Stack :** PHP 8.4, Symfony 7, MySQL 8.0, Nginx, Docker, GitHub Actions CI/CD, SonarQube.

**Déploiement :** Coolify (auto-déploiement via webhook Git sur push `main`).

---

## Architecture

```
GitHub (push main)
    ↓ webhook
Coolify (PaaS Docker)
    ├─ php       → PHP 8.4-FPM (Symfony)
    ├─ nginx     → Reverse proxy HTTPS
    ├─ mysql     → Base de données MySQL 8.0
    ├─ phpmyadmin → Interface BDD (dev)
    └─ sonarqube → Analyse qualité code
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

# 2. Créer les fichiers de configuration locaux
touch .env.local

# 3. Renseigner .env.local (contacter le mainteneur pour les valeurs)
# DATABASE_URL=mysql://USER:PASSWORD@mysql:3306/DATABASE?serverVersion=8.0
# APP_SECRET=...
# JWT_SECRET_KEY=...
# JWT_PUBLIC_KEY=...
# JWT_PASSPHRASE=...

# 4. Lancer les conteneurs
docker compose up -d --build

# 5. Installer les dépendances
docker exec demo-symfony-php-1 composer install

# 6. Initialiser la base de données
docker exec demo-symfony-php-1 php bin/console doctrine:migrations:migrate --no-interaction
docker exec demo-symfony-php-1 php bin/console doctrine:fixtures:load --no-interaction
```

> Les credentials ne sont jamais commités. Contacte le mainteneur du projet pour obtenir les valeurs de configuration locale.

---

## Démarrage rapide (sessions suivantes)

```bash
# Démarrer les conteneurs
docker compose up -d

# Arrêter les conteneurs
docker compose down
```

---

## URLs locales

| Service | URL |
|---|---|
| Application Symfony | http://localhost |
| phpMyAdmin | http://localhost (port dédié, voir docker-compose.yml) |
| SonarQube | http://localhost:9000 |

---

## Pipeline CI/CD

Le pipeline GitHub Actions se déclenche automatiquement à chaque `push` sur `main` :

1. **Checkout** du code
2. **Setup PHP 8.4** + extensions (pdo_mysql, intl, zip, opcache)
3. **composer install** (dépendances sans scripts)
4. **Migrations BDD** (MySQL service GitHub Actions)
5. **PHPUnit** (tests unitaires + fonctionnels, mode `--testdox`)
6. **SonarQube scan** (analyse qualité — serveur hébergé sur Coolify)
7. **Déploiement automatique** via webhook Coolify sur `main`

---

## Tests

```bash
# Tests unitaires et fonctionnels (PHPUnit)
docker exec demo-symfony-php-1 php vendor/bin/phpunit --testdox

# Tests E2E (Playwright) — nécessite les variables E2E_USER_EMAIL / E2E_USER_PASSWORD
npx playwright test

# Tests de charge (k6) — nécessite k6 installé et les variables BASE_URL / K6_USERNAME / K6_PASSWORD
k6 run k6/load-test.js
```

---

## Commandes utiles

### Conteneurs
```bash
# État des conteneurs
docker ps

# Logs Symfony
docker compose logs php -f

# Logs Nginx
docker compose logs nginx -f

# Shell PHP
docker compose exec php sh
```

### Symfony
```bash
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
| Hébergement | Coolify (PaaS self-hosted) |
| CI/CD | GitHub Actions |
| Qualité code | SonarQube Community |
| Tests unitaires/fonctionnels | PHPUnit |
| Tests E2E | Playwright |
| Tests de charge | k6 (Grafana Cloud) |
| Authentification API | JWT (LexikJWTAuthenticationBundle) |
| Back-office | EasyAdmin |
