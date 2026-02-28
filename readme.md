# 💀 La Petite Maison de l'Épouvante — Demo Symfony
Application de démonstration développée dans le cadre du projet CESI.
Fonctionnalité implémentée : **Catalogue produits avec recommandations**.

---

## Prérequis
- [Docker Desktop](https://www.docker.com/)
- [WSL2](https://learn.microsoft.com/fr-fr/windows/wsl/) avec **Ubuntu** — obligatoire sur Windows
- [VS Code](https://code.visualstudio.com/) avec l'extension **Remote - WSL**
- Git

---

## Installation (première fois uniquement)

### 0 — Installer WSL2 et Ubuntu
Dans **PowerShell en administrateur** :
```powershell
wsl --install
```
Redémarre le PC si demandé. Puis ouvre **Ubuntu** depuis le menu Démarrer.

### 1 — Cloner le projet dans WSL2
```bash
cd ~
git clone https://github.com/Mikadjx/demo-symfony.git
cd demo-symfony
```

### 2 — Créer les fichiers de configuration locaux
```bash
touch .env.local
touch .env.dev.local
touch .env.test.local
```

### 3 — Remplir `.env.local`
```bash
nano .env.local
```
Colle le contenu suivant, puis sauvegarde avec `Ctrl+X` → `Y` → `Entrée` :
```dotenv
DATABASE_URL=mysql://MYSQL_USER:MYSQL_PASSWORD@mysql:3306/MYSQL_DATABASE?serverVersion=8.0
```

> ⚠️ Remplace `MYSQL_USER`, `MYSQL_PASSWORD` et `MYSQL_DATABASE` par les vraies valeurs. Contacte le mainteneur du projet pour les obtenir.

### 4 — Remplir `.env.dev.local`
```bash
nano .env.dev.local
```
Colle le contenu suivant, puis sauvegarde avec `Ctrl+X` → `Y` → `Entrée` :
```dotenv
APP_SECRET=VOTRE_SECRET_ICI
```

> ⚠️ Remplace `VOTRE_SECRET_ICI` par la vraie valeur. Contacte le mainteneur du projet pour l'obtenir.

### 5 — Lancer les conteneurs
```bash
docker compose up -d --build
```

### 6 — Installer les dépendances
```bash
docker exec symfony composer install
```

### 7 — Initialiser la base de données
```bash
docker exec symfony php bin/console doctrine:schema:create --no-interaction
docker exec symfony php bin/console doctrine:fixtures:load --no-interaction
```

### 8 — Ouvrir le projet dans VS Code
```bash
code .
```

> VS Code s'ouvre directement connecté à WSL2. Le terminal intégré est déjà dans le bon dossier. Tu peux tout faire depuis VS Code : modifier le code, lancer des commandes Docker, faire tes `git add`, `commit`, `push`.

---

## Démarrage rapide (sessions suivantes)
```bash
# 1. Ouvrir Ubuntu depuis le menu Démarrer
# 2. Aller dans le projet
cd ~/demo-symfony

# 3. Démarrer les conteneurs
docker compose up -d

# 4. Ouvrir VS Code
code .
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
| Utilisateur | voir `.env.local` |
| Mot de passe | voir `.env.local` |

---

## Conteneurs Docker
| Conteneur | Image | Rôle |
|---|---|---|
| `symfony` | php:8.4-fpm-alpine | Application PHP |
| `demo_symfony_nginx` | nginx:alpine | Serveur web |
| `mysql` | mysql:8.0 | Base de données |
| `phpmyadmin` | phpmyadmin | Interface BDD |

---

## Workflow Git
```bash
# Modifier le code dans VS Code
# Puis depuis le terminal VS Code :

git add .
git commit -m "description de la modification"
git push
```

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

# Installer les dépendances
docker exec symfony composer install

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
