# Plan de démonstration — POC La Petite Maison de l'Épouvante

> Durée cible démo : 5–7 minutes (après les slides)
> Prérequis : application déployée et accessible sur Coolify, pipeline CI/CD passant au vert

---

## Séquence de démonstration

### Étape 1 — L'application en production (2 min)

**Ce que tu montres :** l'URL publique hébergée sur Coolify

1. Ouvrir l'URL de production dans le navigateur
2. Montrer la **page d'accueil** → titre "Épouvante" visible, navbar
3. Cliquer sur **"Se connecter"** → formulaire de login Symfony
4. Se connecter avec le compte admin
5. Naviguer vers le **catalogue produits** → liste des produits (figurines, jeux, etc.)
6. Cliquer sur un produit → montrer le **bloc recommandations** (4 produits de même catégorie)

**Message jury :** "L'application est déployée en production sur Coolify. Le système de recommandations est fonctionnel — on voit ici 4 produits suggérés dans la même catégorie, avec fallback sur les produits récents si la catégorie est peu fournie."

---

### Étape 2 — L'API REST sécurisée par JWT (1 min)

**Ce que tu montres :** soit Postman/Bruno, soit curl dans le terminal

```bash
# Sans token → 401
curl -X GET https://[URL-PROD]/api/products

# Login → token JWT
curl -X POST https://[URL-PROD]/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "chezdijoux@gmail.com", "password": "Admin123456!"}'

# Avec token → 200 + JSON produits
curl -X GET https://[URL-PROD]/api/products \
  -H "Authorization: Bearer [TOKEN]"
```

**Message jury :** "L'API est protégée par JWT. Sans token on reçoit un 401. Après authentification, le token JWT RSA permet d'accéder aux données."

---

### Étape 3 — Le pipeline CI/CD en action (1 min 30)

**Ce que tu montres :** GitHub Actions sur le repo

1. Ouvrir GitHub → onglet **Actions**
2. Montrer le dernier run en vert (**CI/CD Pipeline**)
3. Développer le job **Tests & Quality** — montrer les steps :
   - Setup PHP 8.4
   - composer install
   - Migrations BDD
   - PHPUnit tests (--testdox → listing des tests en vert)
   - SonarQube scan

**Message jury :** "À chaque push sur main, le pipeline s'exécute automatiquement. On voit ici les 9 tests PHPUnit en vert, puis l'analyse SonarQube."

---

### Étape 4 — Tableau de bord SonarQube (1 min)

**Ce que tu montres :** https://demo-sonarcube.dev.fabdevlab.fr

1. Ouvrir le dashboard SonarQube → projet **Demo-symfony**
2. Montrer : Quality Gate (Passed ✓), Bugs (0), Vulnerabilities, Code Smells, Coverage

**Message jury :** "SonarQube tourne sur notre infrastructure Coolify. Le Quality Gate est passé — 0 bug critique, 0 vulnérabilité critique. Les code smells identifiés sont documentés dans le plan de remédiation."

---

### Étape 5 — Administration EasyAdmin (30 sec, optionnel)

**Ce que tu montres :** /admin

1. Naviguer vers `/admin`
2. Montrer la gestion des produits et des utilisateurs (CRUD complet)

**Message jury :** "EasyAdmin fourni par Symfony permet une gestion back-office sans développement spécifique — les équipes métier peuvent gérer le catalogue sans intervention technique."

---

## Ordre de priorité si le temps manque

| Priorité | Étape | Pourquoi indispensable |
|---|---|---|
| 1 | Application live | Prouve le fonctionnement du POC |
| 2 | Recommandations | Cœur de la fonctionnalité métier |
| 3 | API JWT (401/200) | Prouve la sécurité |
| 4 | GitHub Actions | Prouve le CI/CD |
| 5 | SonarQube | Prouve la qualité |
| 6 | EasyAdmin | Bonus si temps disponible |

---

## Checklist pré-démo (la veille)

- [ ] URL production accessible et certificat SSL valide
- [ ] Compte admin fonctionnel (email/password)
- [ ] Fixtures BDD chargées (catalogue produits complet)
- [ ] Pipeline GitHub Actions au vert sur le dernier commit
- [ ] SonarQube dashboard accessible et à jour
- [ ] Onglets pré-ouverts dans le navigateur (app + GitHub Actions + SonarQube)
- [ ] Terminal prêt avec les commandes curl préparées (ou Postman configuré)
- [ ] Mode "Ne pas déranger" activé sur le PC
