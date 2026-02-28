# 💀 La Petite Maison de l'Épouvante — Demo Symfony
Application de démonstration développée dans le cadre du projet CESI.
Fonctionnalité implémentée : **Catalogue produits avec recommandations**.

---

## Prérequis
- [Docker](https://www.docker.com/)
- [WSL2](https://learn.microsoft.com/fr-fr/windows/wsl/) — **obligatoire sur Windows** pour pouvoir lancer les conteneurs Docker
- Git

---

## Installation

### 1 — Cloner le projet
```bash
git clone https://github.com/TON_USERNAME/demo-symfony.git
cd demo-symfony
```

### 2 — Lancer les conteneurs

> ⚠️ **Windows uniquement** : cette commande doit être exécutée depuis un terminal **WSL2** (Ubuntu), pas depuis PowerShell ou CMD.
```bash
docker compose up -d --build
```

### 3 — Initialiser la base de données
Attendre que MySQL soit prêt puis :
```bash
docker exec symfony php bin/console doctrine:schema:create --no-interaction
docker exec symfony php bin/console doctrine:fixtures:load --no-interaction
```

---

## Lancer l'application (démarrage rapide)
Si les conteneurs ont déjà été buildés :
```bash
docker compose up -d
```

---

## URLs
| Service | URL |
|---|---|
| Application Symfony | http://localhost:8080 |
| API Produits | http://localhost:8080/api/products |
| API Produit par ID | http://localhost:8080/api/products/{id} |
| phpMyAdmin | http://localhost:8888 |

---

## Connexion phpMyAdmin
| Champ | Valeur |
|---|---|
| Serveur | mysql |
| Utilisateur | voir `.env` |
| Mot de passe | voir `.env` |

---

## Conteneurs Docker
| Conteneur | Image | Rôle |
|---|---|---|
| `symfony` | php:8.4-fpm-alpine | Application PHP |
| `demo_symfony_nginx` | nginx:alpine | Serveur web |
| `mysql` | mysql:8.0 | Base de données |
| `phpmyadmin` | phpmyadmin | Interface BDD |

---

## Commandes utiles

### Gestion des conteneurs
```bash
# Démarrer les conteneurs
docker compose up -d

# Démarrer et rebuilder les images
docker compose up -d --build

# Arrêter les conteneurs
docker compose down

# Arrêter et supprimer les volumes (repart de zéro)
docker compose down -v

# Voir l'état des conteneurs
docker ps

# Voir tous les conteneurs (même arrêtés)
docker ps -a
```

### Logs
```bash
# Logs du conteneur PHP (Symfony)
docker logs symfony

# Logs de Nginx
docker logs demo_symfony_nginx

# Logs en temps réel (suivre les logs)
docker logs -f symfony
docker logs -f demo_symfony_nginx

# Logs Symfony applicatifs
docker exec symfony cat var/log/dev.log
```

### Accès aux conteneurs
```bash
# Accéder au shell du conteneur PHP
docker exec -it symfony sh

# Accéder au shell MySQL
docker exec -it mysql mysql -u demo -p
```

### Symfony
```bash
# Vider le cache
docker exec symfony php bin/console cache:clear

# Recharger les fixtures
docker exec symfony php bin/console doctrine:fixtures:load --no-interaction

# Valider le schéma de base de données
docker exec symfony php bin/console doctrine:schema:validate

# Recréer le schéma de base de données
docker exec symfony php bin/console doctrine:schema:create --no-interaction

# Lister toutes les routes
docker exec symfony php bin/console debug:router
```

---

## Structure du projet
```
demo-symfony/
├── .github/
│   └── workflows/
│       └── ci.yml
├── src/
│   ├── Controller/
│   │   ├── HomeController.php
│   │   └── ProductController.php
│   ├── Entity/
│   │   └── Product.php
│   ├── Repository/
│   │   └── ProductRepository.php
│   └── Service/
│       └── RecommendationService.php
├── templates/
│   ├── base.html.twig
│   └── home/
│       ├── index.html.twig
│       └── product.html.twig
├── Dockerfile
├── docker-compose.yml
├── nginx.conf
└── .env
```

---

## Pipeline CI/CD
Le pipeline GitHub Actions se déclenche à chaque `git push` sur `main` :
1. Checkout du code
2. Installation PHP 8.4
3. Installation des dépendances Composer
4. Build de l'image Docker

---

## Technologies
- **PHP** 8.4
- **Symfony** 7.x
- **Doctrine ORM**
- **MySQL** 8.0
- **Nginx** Alpine
- **Docker** / Docker Compose
- **GitHub Actions** CI/CD
