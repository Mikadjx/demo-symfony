# Plan de révision — Bloc 3 Dev
## "Superviser et assurer le développement des applications logicielles"
### DijouxM — La Petite Maison de l'Épouvante

> Guide de révision approfondi pour la soutenance, calé sur le pipeline réel (`déploiement-fonctionnelle`).

---

## 1. ISO 25010 — LES 4 MÉTRIQUES À MAÎTRISER

### Ce que le jury attend
Présenter au moins 4 indicateurs de qualité mesurables, couvrant fonctionnalité, performance, maintenabilité, fiabilité. Montrer qu'ils servent à prévenir la dette technique.

### Les 4 attributs choisis et pourquoi

**Fiabilité → PHPUnit (couverture de tests)**
- Définition ISO : capacité du logiciel à fonctionner sans défaillance
- Mesure : pourcentage de code couvert par les tests (rapport Xdebug → SonarQube)
- Prévention dette : les zones non testées sont des bugs silencieux futurs
- Dans le pipeline : étapes 3 (unit) et 4 (functional) génèrent `coverage.xml`

**Maintenabilité → SonarQube (code smells, dette technique)**
- Définition ISO : facilité à modifier le logiciel sans introduire de défauts
- Mesure : nombre de code smells, estimation de la dette technique en jours/heures
- Prévention dette : complexité excessive → coûts de maintenance exponentiels
- Dans le pipeline : étape 2 (Security Scan SonarQube) analyse `src/`

**Sécurité → SonarQube Scan (vulnérabilités OWASP)**
- Définition ISO : capacité à protéger les données contre les accès non autorisés
- Mesure : nombre de vulnérabilités critiques/hautes détectées
- Prévention dette : failles non corrigées → incidents production coûteux
- Dans le pipeline : étape 2, `sonarqube-scan-action` avec SONAR_TOKEN

**Efficacité performance → k6 + Grafana Cloud (temps de réponse p95)**
- Définition ISO : capacité à fournir des performances appropriées sous charge
- Mesure : percentile 95 du temps de réponse (< 200ms cible)
- Prévention dette : dégradation progressive des performances → abandon utilisateur
- Dans le pipeline : étape 8 (Load Tests, k6 cloud → Grafana Cloud)

---

## 2. PIPELINE CI/CD — 8 ÉTAPES À DÉTAILLER

### Ce que le jury attend
Schématiser le processus, montrer qu'il est conforme aux exigences, démontrer que le POC l'intègre.

### Détail des 8 étapes

**Étape 1 — Build**
- Setup PHP 8.4 + extensions (pdo_mysql, intl, zip, opcache) + Xdebug
- `composer install --prefer-dist --no-scripts`
- `cache:clear --env=test`
- Cache vendor via `actions/cache@v4` (hash du composer.lock) → accélère les jobs suivants

**Étape 2 — Security Scan (SonarQube)**
- `sonarsource/sonarqube-scan-action@master`
- Serveur : https://demo-sonarcube.dev.fabdevlab.fr (auto-hébergé sur Coolify)
- `SONAR_TOKEN` injecté via GitHub Secret
- Analyse : `sonar.sources=src`, `sonar.tests=tests`
- **Exécuté avant les tests** → le code unsafe ne passe pas à l'étape suivante

**Étape 3 — Unit Tests**
- `php vendor/bin/phpunit --testsuite=unit --testdox`
- Restaure le cache vendor (pas de re-download)
- Tests en isolation pure (mocks, pas de BDD)

**Étape 4 — Functional Tests**
- Service MySQL 8.0 dans le job CI (pas un conteneur Docker Compose)
- Migrations + fixtures chargées
- `phpunit --testsuite=functional --coverage-clover coverage.xml --log-junit test-report.xml`
- Xdebug génère la couverture → `coverage.xml` envoyé à SonarQube

**Étape 5 — Non-Régression Pré-Deploy**
- `curl` sur la préprod Coolify *avant* déploiement (vérification baseline)
- Homepage → HTTP 200
- Login → HTTP 200
- Catalogue (protégé) → HTTP 302 (redirection login)
- Si la préprod est déjà cassée → bloque le déploiement

**Étape 6 — Deploy**
- `curl -X GET "${{ secrets.COOLIFY_WEBHOOK_URL }}" -H "Authorization: Bearer ${{ secrets.COOLIFY_TOKEN }}"`
- Coolify pull le code → rebuild si nécessaire → redéploie les conteneurs
- `COOLIFY_WEBHOOK_URL` et `COOLIFY_TOKEN` = GitHub Secrets (jamais en dur)

**Étape 7 — Non-Régression Post-Deploy**
- `sleep 30` pour laisser Coolify finir le déploiement
- Même vérifications HTTP que l'étape 5
- Valide que le déploiement n'a pas cassé la préprod

**Étape 8 — Load Tests (k6 Cloud → Grafana Cloud)**
- Installation k6 depuis le dépôt officiel
- `k6 cloud run` → envoie les métriques à Grafana Cloud
- `K6_CLOUD_TOKEN` / `GRAFANA_TOKEN` = GitHub Secret
- Scénario : homepage + catalogue avec session (cookie PHPSESSID)
- Seuils : `p(95)<200`, `http_req_failed < 0.01`

### Savoir répondre à "pourquoi SonarQube avant les tests ?"
> La sécurité en étape 2 garantit qu'un code avec des vulnérabilités critiques ne passe jamais en déploiement, même si tous les tests fonctionnels passent. C'est le principe DevSecOps : la sécurité est une condition non négociable, pas une couche optionnelle.

---

## 3. PROCESSUS DE TEST — 4 TYPES

### Ce que le jury attend
Présenter les types de tests, outils, parties prenantes, montrer qu'au moins 2 types sont appliqués avec succès.

### Tests unitaires (PHPUnit, suite=unit)

**Périmètre :** `RecommendationService` — logique métier pure

Tests sur cette branche :
- `testGetRecommendationsReturnsSameCategoryProducts` : vérifie que la catégorie est bien filtrée
- `testGetRecommendationsReturnsEmptyArrayWhenNoProducts` : cas limite sans produits
- `testGetRecommendationsUsesCacheKey` : vérifie que la clé cache contient `recommendations_`

Technique : mocks PHPUnit (`createMock`) pour `ProductRepository` et `CacheInterface` — pas de BDD.

**Service actuel simplifié :**
```php
// Clé de cache : recommendations_{id}
// TTL : 5 minutes (expiresAfter(300))
// Logique : findByCategory(category, excludeCurrentId)
```

### Tests fonctionnels (PHPUnit WebTestCase, suite=functional)

**Périmètre :** `ApiAuthTest` — flux d'authentification complet

- `testAccesSansTokenRetourne401` : GET /api/products → 401
- `testLoginValideRetourneToken` : POST /api/login_check → 200 + token
- `testAccesAvecTokenValideRetourne200` : GET /api/products avec Bearer token → 200 + JSON

Technique : `WebTestCase` crée un client HTTP sur l'app complète avec BDD de test.

### Tests E2E (Playwright)

**Périmètre :** parcours utilisateur complets

- Page d'accueil accessible sans connexion (titre, navbar, hero)
- Flux de connexion → lien déconnexion visible
- Catalogue produits visible après connexion

Technique : Playwright pilote un vrai navigateur. Credentials via `process.env.E2E_USER_EMAIL`.

### Tests de charge (k6 Cloud)

**Périmètre :** homepage + catalogue sous charge

- Authentification via CSRF : `GET /login` → extraire `_csrf_token` → `POST /login` → cookie PHPSESSID
- Test homepage : HTTP 200, < 200ms
- Test catalogue : HTTP 200 avec cookie session, < 200ms
- Zone : `amazon:fr:paris` (100% des VU depuis Paris)

---

## 4. SÉCURITÉ — PLAN DE REMÉDIATION

### Ce que le jury attend
Plan priorisé des risques. Au moins 2 bonnes pratiques implémentées.

### Bonnes pratiques déjà implémentées
1. **Sessions Symfony** : CSRF automatique sur les formulaires, cookie HttpOnly + SameSite
2. **Secrets externalisés** : GitHub Secrets en CI, Coolify env vars en production — jamais en dur
3. **HTTPS forcé** : Nginx + SSL Let's Encrypt via Coolify
4. **SonarQube en CI** : détection automatique des vulnérabilités à chaque push

### Plan de remédiation priorisé

| Priorité | Vulnérabilité | Cause | Action |
|---|---|---|---|
| CRITIQUE | phpMyAdmin exposé | Accès direct BDD sans auth forte | Désactiver en prod, IP restreinte en préprod |
| CRITIQUE | Secrets .env non chiffrés | Fuite si repo compromis | Docker Secrets ou HashiCorp Vault |
| HAUTE | Absence de rate limiting | Brute force / DDoS possibles | ThrottleBundle Symfony sur `/login` |
| HAUTE | TTL JWT trop long | Impact étendu si token volé | Réduire TTL + refresh token |
| MOYENNE | Headers HTTP absents | Non-conformité OWASP A05 | NelmioSecurityBundle (HSTS, CSP, X-Frame) |
| MOYENNE | Logs non centralisés | Pas de traçabilité incidents | Monolog → Grafana Cloud |

### OWASP Top 10 à connaître

- **A01 Broken Access Control** → mitigé par `security.yaml` + roles Symfony
- **A02 Cryptographic Failures** → mitigé par HTTPS + secrets externalisés
- **A03 Injection** → mitigé par Doctrine ORM (requêtes préparées)
- **A05 Security Misconfiguration** → phpMyAdmin à sécuriser (risque résiduel)
- **A07 Identification & Auth Failures** → rate limiting à ajouter

---

## 5. ARCHITECTURE TECHNIQUE — ÉLÉMENTS À MAÎTRISER

### Docker & Coolify

**healthcheck MySQL**
```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 5s
  retries: 10
```
→ `depends_on: condition: service_healthy` garantit que PHP attend MySQL avant de démarrer

**Coolify webhook**
- Déclenché par `curl -X GET` avec Bearer token
- Coolify pull le code → `docker compose up -d --build` → recharge les conteneurs
- `COOLIFY_WEBHOOK_URL` et `COOLIFY_TOKEN` = GitHub Secrets

### Symfony architecture en couches
```
Controller (HTTP) → Service (métier) → Repository (données) → Entity (BDD)
```
- `ProductController` (web, sessions) vs `Api\ProductController` (REST)
- `RecommendationService` : injecté par DI, utilise `CacheInterface` et `ProductRepository`
- `EasyAdmin` : back-office CRUD automatique sur `Product` et `User`

### k6 — Authentification CSRF dans les load tests
Sur cette branche, k6 utilise l'auth par session (pas JWT) :
```js
// 1. GET /login → extraire _csrf_token depuis le HTML
const csrfToken = loginPage.html().find('input[name="_csrf_token"]').attr('value');
// 2. POST /login avec {_username, _password, _csrf_token}
// 3. Récupérer PHPSESSID depuis les cookies
// 4. Utiliser Cookie: PHPSESSID=... dans les requêtes suivantes
```
Savoir expliquer pourquoi le CSRF est nécessaire : protection contre les soumissions de formulaire depuis d'autres origines.

---

## 6. COMPÉTENCES & PLAN DE FORMATION

### Compétences de l'équipe

| Compétence | Niveau | Preuve dans le POC |
|---|---|---|
| PHP / Symfony 7 | Confirmé | Controllers, Services, Entities, Forms, EasyAdmin |
| Docker / Docker Compose | Confirmé | Dockerfile, 5 services, healthchecks |
| GitHub Actions | Confirmé | Pipeline 8 étapes avec cache, secrets, services |
| MySQL / Doctrine ORM | Intermédiaire | Entités, migrations, QueryBuilder |
| PHPUnit / Playwright / k6 | Intermédiaire | 4 types de tests implémentés |
| SonarQube | Débutant → Intermédiaire | Intégration CI, Quality Gate configuré |
| Coolify | Intermédiaire | Déploiement, webhook, SSL |

### Expertises à acquérir

| Compétence | Priorité | Justification |
|---|---|---|
| **Sylius** | HAUTE | Framework e-commerce Symfony — v2 |
| **Stripe / PSP** | HAUTE | Paiement en ligne — v2 |
| Redis / Cache distribué | Moyenne | Scalabilité du cache recommandations |
| **Kubernetes** | **Moyenne** | **Orchestration v3 — associable à Coolify v4** |
| RGAA | Moyenne | Accessibilité — obligation légale |

### Action de formation proposée
> **Formation Sylius certifiante (2 jours)**
> - Contenu : catalogue, panier, paiement, gestion commandes, plugins
> - ROI : accélère de 3-4 semaines le développement v2

---

## 7. QUESTIONS FRÉQUENTES DU JURY

**Q : Pourquoi votre pipeline a 8 étapes ? C'est beaucoup.**
> Chaque étape répond à un critère de qualité ou de sécurité. La sécurité en étape 2 (avant les tests) est la décision DevSecOps clé : un code vulnérable ne se déploie jamais. Les tests de non-régression pré/post déploiement garantissent qu'on ne casse pas la préprod. La richesse du pipeline est une force, pas une complexité.

**Q : Pourquoi k6 en étape 8 et pas avant le déploiement ?**
> Les tests de charge nécessitent une application déployée et fonctionnelle en conditions réelles. k6 cloud teste la vraie préprod Coolify, pas une instance de test — les résultats sont représentatifs des conditions de production.

**Q : La session Symfony vs JWT — pourquoi ce choix ?**
> Pour l'application web (browser), les sessions Symfony avec CSRF sont plus adaptées : elles sont gérées automatiquement par le framework, avec protection XSS (cookie HttpOnly) et CSRF intégrée. JWT est plus adapté pour des API consommées par des clients externes (mobile, microservices). Ce projet peut évoluer vers JWT pour l'API si nécessaire.

**Q : Pourquoi SonarQube auto-hébergé plutôt que SonarCloud ?**
> SonarCloud est hébergé par SonarSource — coût pour les repos privés. On auto-héberge SonarQube Community sur Coolify pour garder le contrôle des données et éviter les coûts sur un POC. SonarCloud serait plus adapté en production.

**Q : Coolify + Kubernetes, est-ce compatible ?**
> Oui. Coolify v4 permet de connecter un cluster Kubernetes existant à son interface. La stratégie est progressive : v1/v2 sur Docker Compose (suffisant), v3 sur K8s si la charge le justifie. K8s apporte l'auto-scaling horizontal, les rolling deployments et la résilience multi-nœuds — des besoins qui n'existent pas encore au stade POC.

**Q : Qu'est-ce que ISO 25010 ?**
> Norme internationale qui définit un modèle de qualité logicielle en 8 familles d'attributs (fonctionnalité, fiabilité, utilisabilité, efficacité performance, maintenabilité, sécurité, compatibilité, portabilité). J'en ai sélectionné 4 mesurables automatiquement dans le pipeline CI/CD.

**Q : Qu'est-ce que Sylius ?**
> Framework e-commerce open-source basé sur Symfony. Il fournit clé en main : catalogue, panier, paiement, commandes, promotions. Migration naturelle depuis le POC car même écosystème Symfony (bundles, entities, services). Évite de réinventer la roue pour les fonctionnalités e-commerce standards.

---

## 8. GLOSSAIRE TECHNIQUE RAPIDE

| Terme | Définition courte |
|---|---|
| **ISO 25010** | Norme qualité logicielle — 8 familles d'attributs |
| **DevSecOps** | Intégration de la sécurité dans le cycle DevOps |
| **PHPUnit** | Framework de tests unitaires PHP (xUnit) |
| **WebTestCase** | Classe Symfony pour tests fonctionnels HTTP |
| **Playwright** | Framework de tests E2E Node.js (Microsoft) |
| **k6** | Outil de tests de charge JavaScript (Grafana Labs) |
| **SonarQube** | Plateforme d'analyse statique de code |
| **Quality Gate** | Conditions SonarQube pour valider la qualité |
| **Coolify** | PaaS self-hostable basé sur Docker |
| **Webhook** | Appel HTTP déclenché automatiquement par un événement |
| **CSRF** | Cross-Site Request Forgery — protection formulaires Symfony |
| **p95** | Percentile 95 — 95% des requêtes respectent ce seuil |
| **VU** | Virtual User — utilisateur simulé dans k6 |
| **Xdebug** | Extension PHP pour le débogage et la couverture de code |
| **OWASP Top 10** | Les 10 risques de sécurité web les plus critiques |
| **CSP** | Content Security Policy — header HTTP anti-XSS |
| **HSTS** | HTTP Strict Transport Security — force HTTPS |
| **Sylius** | Framework e-commerce basé sur Symfony |
| **Kubernetes (K8s)** | Orchestrateur de conteneurs — auto-scaling, résilience |
| **Rolling deployment** | Mise à jour progressive sans coupure de service |
| **RGAA** | Référentiel Général d'Amélioration de l'Accessibilité |
