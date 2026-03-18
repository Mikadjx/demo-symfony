# Plan de révision — Bloc 3 Dev
## "Superviser et assurer le développement des applications logicielles"
### DijouxM — La Petite Maison de l'Épouvante

> Ce document est un guide de révision approfondi pour préparer la soutenance.
> Il couvre chaque critère de la grille d'évaluation avec les éléments techniques du POC.

---

## 1. PROTOCOLE D'EXPÉRIMENTATION EN BAC À SABLE

### Ce que le jury attend
- Présenter les technologies testées, leurs interactions, les difficultés rencontrées et les résultats validant l'adoption.

### Éléments à maîtriser

**Symfony 7 + PHP 8.4**
- Attributs PHP 8 pour les routes (`#[Route(...)]`) — rupture avec les annotations Doctrine
- PHP-FPM : rôle de gestionnaire de processus FastCGI, communication avec Nginx via socket Unix/TCP
- Composer autoload PSR-4 : comprendre le `dump-autoload --optimize` dans le Dockerfile

**Docker & Docker Compose**
- Différence `build` vs `image` dans docker-compose.yml
- `healthcheck` MySQL : pourquoi `condition: service_healthy` sur `depends_on` est crucial (évite les race conditions au démarrage)
- `restart: unless-stopped` : comportement en cas de crash vs redémarrage serveur
- Volumes nommés (`demo_data`) vs volumes anonymes : persistance des données

**Migration WSL → Coolify**
- WSL2 : Windows Subsystem for Linux, I/O natifs Linux dans un environnement Windows
- Coolify : PaaS self-hostable basé sur Docker, gestion SSL Let's Encrypt automatique, reverse proxy Traefik/Caddy intégré
- Variables d'environnement : gestion dans Coolify (équivalent des secrets GitHub en production)
- Webhook Git : comment Coolify détecte un push et redéploie automatiquement

**SonarQube**
- Analyse statique : détecte bugs, vulnérabilités, code smells SANS exécuter le code
- Quality Gate : ensemble de conditions (0 bug critique, couverture > seuil) à respecter pour "passer"
- Métriques : Reliability Rating, Security Rating, Maintainability Rating
- Dans le CI : `sonarsource/sonarqube-scan-action@master` + `SONAR_TOKEN` secret

**JWT (LexikJWTAuthenticationBundle)**
- Cryptographie asymétrique RSA : clé privée signe le token, clé publique vérifie
- Structure JWT : header.payload.signature (base64url encodé)
- `lexik_jwt_authentication.yaml` : configuration TTL, routes publiques/privées
- Génération des clés : `openssl genrsa -out private.pem -aes256 4096`

**k6 & Grafana Cloud**
- Virtual Users (VU) : simulent des utilisateurs concurrents
- Stages : montée progressive en charge (ramping)
- Thresholds : conditions de succès (`p(95)<200` = 95% des requêtes sous 200ms)
- `http_req_failed: ['rate<0.01']` = moins de 1% d'erreurs
- Cloud distribution : `amazon:fr:paris` — zone de charge proche des utilisateurs cibles

**Playwright**
- Framework de test E2E Node.js (Microsoft)
- `page.goto()`, `page.fill()`, `page.click()`, `expect()` : API de base
- Variables d'environnement pour credentials : `process.env.E2E_USER_EMAIL`
- `test.skip()` : skip conditionnel si les credentials ne sont pas définis

---

## 2. IMPLÉMENTATION TECHNIQUE DU POC

### Ce que le jury attend
- Découpage applicatif, communication interservice, sécurité, hébergement, orchestration — tout doit être démontrable.

### Éléments à maîtriser

**Architecture en couches Symfony**
```
Controller → Service → Repository → Entity → BDD
```
- `ProductController` (web) vs `Api\ProductController` (REST) : séparation des contextes
- `RecommendationService` : service métier injecté par dependency injection
- `ProductRepository` : accès données via Doctrine QueryBuilder
- `Product` / `User` : entités ORM mappées sur MySQL

**Dependency Injection Symfony**
- `services.yaml` : autowiring, autoconfigure
- Injection par constructeur (recommandée) vs par propriété
- Savoir expliquer pourquoi `CacheInterface` est injecté dans `RecommendationService`

**Cache Symfony**
- `CacheInterface` : abstraction Symfony sur PSR-6
- `$cache->get($key, callback)` : pattern lazy computation
- `$item->expiresAfter(300)` : TTL de 5 minutes
- Clé de cache différenciée : `recommendations_{id}_{userId|anonymous}` → évite la collision entre utilisateurs

**Communication interservice (Docker)**
- Réseau Docker interne : les conteneurs communiquent par nom de service (`mysql`, `php`, `nginx`)
- `DATABASE_URL: mysql://user:pass@mysql:3306/db` : `mysql` = nom du service Docker, pas localhost

**Sécurité applicative**
- `security.yaml` : firewalls, access_control, role hierarchy
- `ROLE_ADMIN` vs `ROLE_USER` : hiérarchie des rôles
- API firewall stateless (JWT) vs main firewall session (form login)
- `JWTSubscriber.php` : event subscriber sur les événements JWT

**EasyAdmin**
- Bundle d'administration Symfony : CRUD automatique sur les entités
- `DashboardController` : point d'entrée admin
- `UserCrudController` / `ProductCrudController` : personnalisation des CRUD

---

## 3. FONCTIONNALITÉ MÉTIER — RECOMMANDATIONS

### Ce que le jury attend
- La fonctionnalité répond aux besoins exprimés par la MOA.

### Éléments à maîtriser

**Besoins MOA couverts**
- "Système de recommandation de produits en fonction des recherches/achats" → RecommendationService
- Catalogue produits avec catégories → entité Product (name, description, price, category, stock)
- Authentification utilisateur → SecurityController + LoginController

**Logique du RecommendationService (4 règles)**
1. **Même catégorie** : `findByCategory($category, $excludeId, limit: 8)` → produits pertinents
2. **Exclusion historique** : `getPurchasedIds($user)` → prêt pour la table Order (v2)
3. **Limite 4 résultats** : `array_slice($candidates, 0, 4)`
4. **Fallback récents** : `findRecent(limit: 4 - count($candidates), excludeIds: [...])`

**ProductRepository**
- `findByCategory(category, excludeId, limit)` : QueryBuilder Doctrine avec `andWhere`, `setMaxResults`
- `findRecent(limit, excludeIds)` : `orderBy('id', 'DESC')` + `NOT IN` pour les exclusions

**Savoir démontrer**
- Consulter un produit de la catégorie "masques" → voir 4 autres masques recommandés
- Si la catégorie a moins de 4 produits → voir le fallback avec des produits d'autres catégories

---

## 4. PROCESSUS DE LIVRAISON CONTINUE (CI/CD)

### Ce que le jury attend
- Schématiser le processus, montrer sa conformité aux exigences, démontrer l'intégration.

### Pipeline GitHub Actions détaillé

```yaml
Trigger: push/PR sur main

Job: tests
  ├─ actions/checkout@v4         → clone le repo
  ├─ shivammathur/setup-php@v2   → PHP 8.4 + extensions (pdo_mysql, intl, zip, opcache)
  ├─ composer install            → dépendances sans scripts
  ├─ cache:clear --env=test      → cache Symfony test
  ├─ doctrine:migrations:migrate → schéma BDD à jour
  ├─ rm -rf .phpunit.cache       → évite les caches obsolètes
  ├─ phpunit --testdox           → tests en format lisible
  └─ sonarqube-scan-action       → analyse qualité
```

**Service MySQL dans le CI**
- Le job CI démarre un conteneur MySQL 8.0 en healthcheck
- `DATABASE_URL` pointe sur `127.0.0.1:3306` (pas `mysql` car pas de réseau Docker ici)

**Secrets GitHub**
- `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` : clés RSA
- `SONAR_TOKEN` : token d'authentification SonarQube

**Coolify webhook**
- Après un push sur main, Coolify détecte le changement via webhook GitHub
- Redéploiement automatique : pull image → docker compose up → zero-downtime (si configuré)

---

## 5. COMPÉTENCES & PLAN DE FORMATION

### Ce que le jury attend
- Recenser les compétences de l'équipe, identifier les expertises à acquérir, proposer au moins une action de formation.

### Compétences présentes
| Compétence | Niveau | Preuve dans le POC |
|---|---|---|
| PHP / Symfony 7 | Confirmé | Controllers, Services, Entities, Forms |
| Docker / Docker Compose | Confirmé | Dockerfile multi-stage, 5 services, healthchecks |
| Git / GitHub Actions | Confirmé | Pipeline CI/CD complet |
| MySQL / Doctrine ORM | Intermédiaire | Entités, migrations, QueryBuilder |
| JWT / Sécurité web | Intermédiaire | LexikJWT, RSA, firewalls Symfony |
| Tests (PHPUnit, Playwright, k6) | Intermédiaire | 4 types de tests implémentés |
| SonarQube | Débutant | Intégration CI fonctionnelle |

### Expertises à acquérir
| Compétence | Priorité | Justification |
|---|---|---|
| **Sylius** | HAUTE | Framework e-commerce prévu pour la v2 |
| Redis / Cache distribué | Moyenne | Scalabilité du cache recommandations |
| Kubernetes | Basse | Orchestration à l'échelle (v3+) |
| RGAA | Moyenne | Obligation légale (accessibilité) |
| Stripe / PSP | Haute | Paiement en ligne (v2) |

### Action de formation proposée
> **Formation Sylius certifiante (2 jours)**
> - Objectif : maîtriser le framework e-commerce Symfony pour la v2
> - Contenu : catalogue, panier, paiement, gestion commandes, plugins
> - Retour sur investissement : accélère de 3-4 semaines le développement v2

---

## 6. ENVIRONNEMENT MANAGÉ & MONTÉE EN CHARGE

### Ce que le jury attend
- Démontrer que le POC s'exécute dans un environnement managé avec disponibilité et montée en charge.

### Coolify — Environnement managé
- **Self-hosted PaaS** : hébergé sur VPS cloud (hébergeur français)
- **Gestion SSL** : Let's Encrypt automatique (renouvellement tous les 90 jours)
- **Reverse proxy** : Traefik ou Caddy intégré (routing HTTPS → conteneur)
- **Orchestration Docker** : Coolify gère le cycle de vie des conteneurs
- **Restart policies** : `unless-stopped` garantit la haute disponibilité en cas de crash

### k6 — Tests de charge
```
Stage 1 : 0→10 VU en 30s   (warm-up)
Stage 2 : 10→50 VU en 1min  (charge normale)
Stage 3 : 50→100 VU en 30s  (pic de charge)
Stage 4 : 100→0 VU en 30s   (cooldown)
```
- **Seuils validés** :
  - `http_req_duration: p(95) < 200ms` → 95% des requêtes répondent en moins de 200ms
  - `http_req_failed: rate < 0.01` → moins de 1% d'erreurs

### Métriques à connaître
- **p95 (percentile 95)** : le temps de réponse que 95% des requêtes respectent — plus représentatif que la moyenne car insensible aux outliers
- **VU (Virtual User)** : utilisateur simulé avec son propre contexte de session
- **RPS (Requests Per Second)** : débit de l'application sous charge

---

## 7. PROCESSUS D'ASSURANCE QUALITÉ (4 INDICATEURS)

### Ce que le jury attend
- Au moins 4 indicateurs de qualité mesurant fonctionnalité, performance, maintenabilité, fiabilité. Axes d'amélioration identifiés.

### Les 4 indicateurs + outils

| # | Indicateur | Dimension | Outil | Mesure concrète |
|---|---|---|---|---|
| 1 | Taux de succès des tests | Fonctionnalité | PHPUnit | 9/9 tests passent (100%) |
| 2 | Temps de réponse p95 | Performance | k6 Cloud | < 200ms sous 100 VU |
| 3 | Code smells / dette | Maintenabilité | SonarQube | 0 critical, A rating |
| 4 | Bugs détectés | Fiabilité | SonarQube | 0 bug, A rating |
| 5 | Vulnérabilités OWASP | Sécurité | SonarQube | 0 critical |
| 6 | Couverture de code | Maintenabilité | PHPUnit/SonarQube | Cible > 80% |

### Axes d'amélioration (à présenter)
- Augmenter la couverture de code (actuellement : tests service + API, pas les controllers web)
- Ajouter des métriques d'infrastructure (CPU, RAM) via Grafana Cloud
- Mettre en place des alertes automatiques sur les seuils k6

---

## 8. PLAN DE REMÉDIATION SÉCURITÉ

### Ce que le jury attend
- Priorisation des actions pour réduire les risques critiques. Au moins 2 bonnes pratiques implémentées.

### Bonnes pratiques déjà implémentées (à citer)
1. **JWT asymétrique RSA** : même si la clé publique est connue, impossible de forger un token sans la clé privée
2. **Secrets externalisés** : jamais de credentials dans le code (GitHub Secrets + Coolify env vars)
3. **HTTPS forcé** : Nginx avec TLS, Let's Encrypt via Coolify
4. **ORM Doctrine** : requêtes préparées → immunité contre les injections SQL

### Risques identifiés & remédiation priorisée

| Rang | Risque | OWASP | Mitigation actuelle | Action corrective |
|---|---|---|---|---|
| 1 | Brute force authentification | A07 | Aucune | Symfony RateLimiter sur `/login` |
| 2 | phpMyAdmin exposé en prod | A05 | Désactiver manuellement | Profil Docker Compose (dev only) |
| 3 | Token JWT TTL trop long | A07 | TTL configuré | Refresh token + TTL court (15min) |
| 4 | Absence de CSP headers | A05 | Aucune | NelmioSecurityBundle (headers HTTP) |
| 5 | Logs sans rotation | A09 | Monolog configuré | Logrotate + centralisation Grafana |

### OWASP Top 10 à connaître (pour les questions jury)
- **A01 Broken Access Control** : accès non autorisé à des ressources → mitigé par `security.yaml`
- **A02 Cryptographic Failures** : mauvaise crypto → mitigé par JWT RSA + HTTPS
- **A03 Injection** : SQL/commande → mitigé par Doctrine ORM
- **A05 Security Misconfiguration** : config par défaut dangereuse → phpMyAdmin à sécuriser
- **A07 Authentication Failures** : brute force, sessions faibles → RateLimiter à ajouter

---

## 9. QUESTIONS FRÉQUENTES DU JURY

**Q : Pourquoi Symfony plutôt que Laravel ou Node.js ?**
> Symfony est le framework PHP le plus mature et modulaire. Il est aussi la base de Sylius (e-commerce) prévu en v2, ce qui garantit la cohérence technique. La MOA a des besoins de robustesse et de maintenabilité long terme — Symfony excelle là-dedans.

**Q : Comment assures-tu la haute disponibilité ?**
> Coolify gère le redémarrage automatique des conteneurs (`restart: unless-stopped`). Pour une vraie HA, la v2 prévoirait du load balancing et de la réplication MySQL. Le POC valide la faisabilité, pas la production à grande échelle.

**Q : Quelle est la différence entre les tests unitaires et fonctionnels ?**
> Les tests unitaires (`RecommendationServiceTest`) testent le service en isolation avec des mocks — sans BDD, sans HTTP. Les tests fonctionnels (`ApiAuthTest`) utilisent `WebTestCase` et font de vraies requêtes HTTP sur l'application complète avec une BDD de test.

**Q : Pourquoi un token JWT et pas une session classique ?**
> L'API doit être stateless pour être scalable. Une session serveur crée un état côté serveur incompatible avec plusieurs instances (load balancing). JWT est auto-porteur : toutes les informations nécessaires sont dans le token, le serveur ne stocke rien.

**Q : Comment Coolify déploie-t-il automatiquement ?**
> GitHub envoie un webhook HTTP à Coolify à chaque push sur main. Coolify pull le code, rebuild les images Docker si nécessaire, et relance les conteneurs. Le pipeline CI/CD s'exécute d'abord sur GitHub Actions — si les tests passent, le push sur main déclenche le webhook.

**Q : Pourquoi SonarQube sur votre infrastructure plutôt que SonarCloud ?**
> SonarCloud est la version cloud hébergée par SonarSource. Ici on auto-héberge SonarQube Community sur Coolify pour garder le contrôle des données de code et éviter les coûts pour un POC. En production, SonarCloud serait plus adapté.

**Q : Qu'est-ce que Sylius et pourquoi le prévoir en v2 ?**
> Sylius est un framework e-commerce open-source basé sur Symfony. Il fournit clé en main : catalogue, panier, paiement, gestion des commandes, promotions. La migration depuis le POC sera naturelle car Sylius partage les mêmes concepts Symfony (bundles, entities, services). Cela évite de réinventer la roue pour les fonctionnalités e-commerce standards.

---

## 10. GLOSSAIRE TECHNIQUE RAPIDE

| Terme | Définition courte |
|---|---|
| **PHPUnit** | Framework de tests unitaires PHP (xUnit) |
| **Playwright** | Framework de tests E2E Node.js (Microsoft) |
| **k6** | Outil de tests de charge JavaScript open-source (Grafana Labs) |
| **SonarQube** | Plateforme d'analyse statique de code |
| **Quality Gate** | Ensemble de conditions SonarQube pour valider la qualité |
| **JWT** | JSON Web Token — token d'authentification stateless |
| **RSA** | Cryptographie asymétrique (clé publique/privée) |
| **Coolify** | PaaS self-hostable basé sur Docker |
| **PaaS** | Platform as a Service — plateforme d'hébergement managée |
| **Docker Compose** | Outil d'orchestration multi-conteneurs en local/dev |
| **PHP-FPM** | FastCGI Process Manager — gestionnaire de processus PHP |
| **Doctrine ORM** | Object-Relational Mapper pour PHP |
| **EasyAdmin** | Bundle d'administration Symfony |
| **Sylius** | Framework e-commerce basé sur Symfony |
| **RGAA** | Référentiel Général d'Amélioration de l'Accessibilité |
| **p95** | Percentile 95 — seuil sous lequel 95% des mesures se situent |
| **VU** | Virtual User — utilisateur simulé dans k6 |
| **CSP** | Content Security Policy — header HTTP anti-XSS |
| **OWASP** | Open Web Application Security Project — référentiel sécurité |
