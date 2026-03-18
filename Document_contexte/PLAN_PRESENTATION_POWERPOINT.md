# Structure PowerPoint — Présentation Individuelle BLOC 3
## Mickaël Dijoux — Lead Developer — Mars 2026
### Durée : 20 min (passage) + 15 min (questions jury)

---

> **Grille de lecture** : chaque slide indique le temps conseillé et la compétence évaluée couverte.
> Compétences : **[QA]** Qualité (5pts) · **[CI]** Pilotage CI/CD (8pts) · **[EXP]** Expertise (4pts) · **[ORAL]** (3pts)

---

## SLIDE 1 — Titre
**Temps : 30 s**

**Contenu :**
- Titre : *"Superviser et assurer le développement d'une application — Approche DevSecOps"*
- Sous-titre : *La Petite Maison de l'Épouvante — Demo Symfony*
- Mickaël Dijoux — Lead Developer — Mars 2026

**Notes orateur :**
> "Bonjour, je suis Mickaël Dijoux, Lead Developer sur le projet La Petite Maison de l'Épouvante. En 20 minutes, je vais vous montrer comment j'ai structuré la qualité, la sécurité et le déploiement d'un prototype e-commerce via une approche DevSecOps de bout en bout."

---

## SLIDE 2 — Contexte & Équipe
**Temps : 1 min 30 s** | Compétences : **[CI] [EXP]**

**Contenu :**

**Entreprise :** La Petite Maison de l'Épouvante
- E-commerce horreur/fantasy : figurines, films, fanzines, jeux de plateau
- Contrainte métier : **pics de trafic** Halloween + festival annuel → disponibilité et scalabilité
- Contrainte technique : hébergement européen, sécurité commerciale (RGPD)

**Équipe :**
| Rôle | Nom | Compétences maîtrisées | Gap identifié |
|------|-----|------------------------|---------------|
| Lead Dev / Architecte | Dijoux M. | Symfony, Docker, CI/CD, Tests | Orchestration K8s |
| Développeur junior #1 | Dev J1 | PHP, Twig, Git basique | Docker, pipeline CI/CD |
| Développeur junior #2 | Dev J2 | PHP, SQL, Composer | Tests automatisés |

**Action de formation proposée :**
- Atelier 2 jours : Docker + GitHub Actions → objectif : autonomie sur le pipeline pour les 2 juniors

**Notes orateur :**
> "L'entreprise doit absorber des pics de charge importants. Mon rôle de Lead Dev implique d'encadrer deux juniors avec des lacunes en Docker et en tests. J'ai proposé un atelier de 2 jours pour les rendre autonomes sur le pipeline."

---

## SLIDE 3 — Processus Qualité — ISO 25010
**Temps : 2 min** | Compétences : **[QA]**

**Contenu :**
**Modèle qualité retenu : ISO 25010 — 4 indicateurs mesurés automatiquement dans le pipeline**

| # | Attribut | Indicateur | Outil | Prévention dette technique |
|---|----------|-----------|-------|---------------------------|
| IND-01 | **Fiabilité** | Couverture de tests (%) | PHPUnit `--coverage-clover` | Zones non testées → bugs silencieux en prod |
| IND-02 | **Maintenabilité** | Code smells / dette estimée | SonarQube SAST | Dégradation progressive du code Symfony |
| IND-03 | **Sécurité** | Vulnérabilités détectées (cible : 0 CVE) | SonarQube scan | Failles non corrigées = dette sécurité critique |
| IND-04 | **Performance** | Temps de réponse (p95 < 200 ms) | k6 + Grafana Cloud | Dégradation silencieuse = coût futur |

**Notes orateur :**
> "Chaque indicateur est mesuré automatiquement à chaque push. La couverture PHPUnit révèle les zones non testées. SonarQube bloque le pipeline si un CVE critique est détecté avant même le déploiement. k6 vérifie que le p95 reste sous 200ms sous charge."

---

## SLIDE 4 — DevSecOps : Sécurité Intégrée
**Temps : 1 min 30 s** | Compétences : **[QA] [CI]**

**Contenu :**
**Principe : Security by Design — sécurité vérifiée à chaque étape, pas seulement en fin de cycle**

```
Code          → Secrets dans GitHub Secrets (jamais en clair dans le dépôt)
Commit        → SonarQube SAST (vulnérabilités, injections, code smells)
Build         → Image Docker Alpine (surface d'attaque minimale)
Test          → Sécurité testée : accès sans auth → redirect /login (302)
               + protection CSRF vérifiée dans les tests fonctionnels
Deploy        → HTTPS/TLS obligatoire, Coolify webhook signé
Runtime       → Monolog → Grafana Cloud (logs centralisés, alertes)
```

**2 bonnes pratiques sécurité intégrées au POC :**
1. **HTTPS/TLS** — certificat SSL sur nginx, toutes les communications chiffrées
2. **Protection CSRF** — token CSRF sur le formulaire de login, vérifié par Symfony Security (k6 l'extrait dynamiquement pour ses tests)

**Notes orateur :**
> "La sécurité est vérifiée automatiquement. Le test fonctionnel `testCatalogueRedirectsToLoginWhenNotAuthenticated` garantit qu'aucune route protégée n'est accessible sans authentification. k6 doit même récupérer le token CSRF avant de pouvoir se connecter, ce qui prouve que la protection est active en conditions réelles."

---

## SLIDE 5 — Pipeline CI/CD — 8 Étapes
**Temps : 2 min** | Compétences : **[CI]**

**Contenu :**
**GitHub Actions — déclencheur : `push` ou `pull_request` sur `déploiement-fonctionnelle`**

```
[1] BUILD               → PHP 8.4, Composer install, cache vendor
         ↓ bloquant
[2] SECURITY SCAN       → SonarQube SAST (CVE, code smells, dette technique)
         ↓ bloquant
[3] TESTS UNITAIRES     → PHPUnit suite=unit (CatalogueService, RecommendationService)
         ↓ bloquant
[4] TESTS FONCTIONNELS  → PHPUnit suite=functional (CatalogueSecurityTest, LoginControllerTest)
                          MySQL service éphémère + fixtures + coverage-clover.xml
         ↓ bloquant (push only)
[5] NON-RÉGRESSION PRÉ  → curl baseline preprod : / (200), /login (200), /product (302)
         ↓ bloquant
[6] DÉPLOIEMENT PREPROD → Webhook Coolify → redéploiement Docker automatique
         ↓ bloquant
[7] NON-RÉGRESSION POST → Même vérifications 30s après déploiement
         ↓ bloquant
[8] LOAD TEST           → k6 cloud → Paris (amazon:fr:paris) → Grafana Cloud
```

**Notes orateur :**
> "Chaque job est bloquant via `needs:`. Si les tests unitaires échouent, les tests fonctionnels ne s'exécutent pas — et le déploiement non plus. La non-régression pré/post garantit qu'on ne dégrade pas l'état de la preprod. C'est le pipeline complet que je vais vous montrer en démonstration."

---

## SLIDE 6 — Architecture Technique
**Temps : 1 min 30 s** | Compétences : **[EXP]**

**Contenu :**
**Schéma d'architecture :**

```
Utilisateur (navigateur)
      │ HTTPS/TLS
      ▼
  [ Nginx Alpine ]        ← reverse proxy, SSL termination, routage
      │
      ▼
  [ PHP-FPM 8.4 ]         ← Symfony 7
      │   ├── CatalogueService    (filtre stock, catégorie, tri prix)
      │   ├── RecommendationService (recommandations + cache Symfony)
      │   └── Security (session auth, CSRF, redirect /login)
      ▼
  [ MySQL 8.0 ]           ← Doctrine ORM, Migrations, Fixtures
      │
  [ phpMyAdmin ]          ← preprod uniquement

  Observabilité :
  Monolog → Grafana Cloud
  k6 Cloud → Grafana Cloud
  SonarQube → demo-sonarcube.dev.fabdevlab.fr
```

**Stack :**
Docker Compose : php (php:8.4-fpm-alpine), nginx, mysql, phpmyadmin, sonarqube
Déploiement : Coolify + Linux · Monitoring : Grafana Cloud + k6

**Notes orateur :**
> "L'architecture est découplée. Nginx gère le TLS, php-fpm exécute Symfony avec ses deux services métier, MySQL persiste les données. phpMyAdmin est désactivé en production — c'est une vulnérabilité identifiée dans mon plan de remédiation."

---

## SLIDE 7 — Expérimentations Sandbox
**Temps : 2 min** | Compétences : **[EXP]**

**Contenu :**
**Protocole : valider chaque technologie critique AVANT de l'intégrer au projet**

| Technologie | Ce qui a été testé | Difficulté rencontrée | Résultat |
|-------------|-------------------|-----------------------|----------|
| **Coolify + Docker** | Déploiement automatisé via webhook GitHub | Config réseau Docker inter-conteneurs, variables d'env | ✅ Déploiement automatisé validé |
| **SonarQube** | Scan SAST PHP/Symfony | Configuration project key, intégration avec GitHub Actions | ✅ Scan PHP/Symfony opérationnel |
| **Grafana Cloud** | Collecte logs Monolog + métriques k6 | Authentification API Grafana, configuration datasource | ✅ Métriques collectées en temps réel |
| **k6 cloud** | Test de charge avec auth session (CSRF + cookie) | Extraction dynamique du token CSRF, envoi cloud | ✅ Scénario login → catalogue fonctionnel |
| **PHPUnit WebTestCase** | Tests fonctionnels avec BDD MySQL | Configuration service MySQL éphémère GitHub Actions | ✅ 4 tests sécurité catalogue validés |

**Notes orateur :**
> "Ces expérimentations m'ont permis d'identifier les problèmes avant l'intégration. Par exemple, k6 devait extraire dynamiquement le token CSRF du HTML avant chaque connexion — sans ce test préalable, j'aurais découvert ce problème lors du pipeline en production."

---

## SLIDE 8 — Fonctionnalité Métier & Tests
**Temps : 2 min** | Compétences : **[EXP] [QA]**

**Contenu :**

**User Story :**
> *"En tant qu'utilisateur authentifié, je veux parcourir le catalogue de produits et recevoir des recommandations basées sur mes consultations."*

**Deux services métier implémentés et testés :**

**CatalogueService** — testé par `CatalogueServiceTest` (9 tests unitaires)
| Méthode | Comportement | Test |
|---------|-------------|------|
| `filterAvailableProducts` | Exclut les produits avec stock = 0 | ✅ `testEpuisedProductIsExcludedFromCatalogue` |
| `filterByCategory` | Filtre par catégorie (figurines, blu-ray, fanzine…) | ✅ `testFilterFigurinesReturnsOnlyFigurines` |
| `sortByPriceAsc` | Tri par prix croissant (7,99€ → 89,99€) | ✅ `testSortByPriceAscReturnsChepeastFirst` |

**RecommendationService** — testé par `RecommendationServiceTest` (3 tests unitaires)
| Comportement | Test |
|-------------|------|
| Retourne les produits de la même catégorie | ✅ `testGetRecommendationsReturnsSameCategoryProducts` |
| Tableau vide si aucun produit similaire | ✅ `testGetRecommendationsReturnsEmptyArrayWhenNoProducts` |
| Utilise le cache (TTL 300s, clé `recommendations_{id}`) | ✅ `testGetRecommendationsUsesCacheKey` |

**Sécurité testée — `CatalogueSecurityTest` (4 tests fonctionnels)**
- `/product` sans auth → redirect `/login` (302) ✅
- Login invalide → reste sur `/login` + `.alert-danger` ✅
- Login valide → accès `/product` (200) ✅
- Admin → accès `/product` (200) ✅

**Notes orateur :**
> "Les services métier sont testés en isolation via des mocks — RecommendationService ne touche jamais la base de données dans les tests unitaires, c'est le ProductRepository qui est mocké. Les tests fonctionnels, eux, utilisent une vraie base MySQL éphémère pour valider le comportement de sécurité de bout en bout."

---

## SLIDE 9 — DÉMONSTRATION LIVE
**Temps : 4 min** | Compétences : **[CI] [EXP]**

*(Voir `PLAN_DEMONSTRATION.md` pour le script détaillé pas-à-pas)*

**Tableau de bord affiché pendant la démo :**

| # | Ce qu'on montre | Résultat attendu |
|---|----------------|-----------------|
| 1 | Pipeline GitHub Actions — dernier run | 8 jobs verts ✅ |
| 2 | Sortie testdox — tests unitaires | `CatalogueService` · `RecommendationService` — tous ✅ |
| 3 | Sortie testdox — tests fonctionnels | `CatalogueSecurityTest` · `LoginControllerTest` — tous ✅ |
| 4 | SonarQube dashboard | 0 CVE bloquant, dette mesurée |
| 5 | Application déployée (Coolify) | Conteneurs actifs, accès HTTPS |
| 6 | Navigation : `/product` sans auth | Redirect `/login` → sécurité prouvée |
| 7 | k6 + Grafana Cloud | p95 < 200ms · taux erreur < 1% |

**Notes orateur :**
> "La démo suit exactement le pipeline. Je vais montrer les tests qui s'exécutent, puis l'application déployée, puis les métriques de charge."

---

## SLIDE 10 — Résultats Tests de Charge — Analyse
**Temps : 2 min** | Compétences : **[QA] [CI]**

**Contenu :**

**Scénario k6 (script `k6/load-test.js`) :**
```
Phase 1 : montée  30s → 10 VUs
Phase 2 : charge  1min → 50 VUs
Phase 3 : pic     30s → 100 VUs
Phase 4 : descente 30s → 0 VUs
Zone : amazon:fr:paris
```

**Seuils définis (thresholds) :**
- `http_req_duration p(95) < 200ms`
- `http_req_failed rate < 1%`

**Ce que teste le scénario :**
1. GET `/login` → vérif accessibilité + extraction token CSRF
2. POST `/login` formulaire → session cookie (PHPSESSID)
3. GET `/` — homepage (check : 200 + < 200ms)
4. GET `/product` avec session — catalogue (check : 200 + < 200ms)

**Analyse des résultats :**
- En dessous de 50 VUs : p95 < 200ms ✅, erreurs 0%
- Au-delà de 100 VUs : dégradation observée → causes identifiées :
  - Pas de cache applicatif (MySQL sollicité à chaque requête catalogue)
  - Single replica PHP-FPM (pas de scaling horizontal)
  - Pas de rate limiting sur `/login` (brute force possible)

→ Ces constats alimentent directement le **plan de remédiation**

**Notes orateur :**
> "k6 simule des utilisateurs réels : ils se connectent avec le formulaire, récupèrent la session, et naviguent sur le catalogue. À 100 VUs, on commence à voir une dégradation. J'ai identifié trois causes, chacune avec une action corrective dans le plan de remédiation."

---

## SLIDE 11 — Plan de Remédiation
**Temps : 2 min** | Compétences : **[QA] [CI]**

**Contenu :**

| Priorité | Vulnérabilité / Problème | Action corrective | Justification |
|----------|--------------------------|-------------------|---------------|
| 🔴 **CRITIQUE** | phpMyAdmin accessible en production | Désactiver en prod, restreindre IP en preprod | Accès DB direct sans auth forte |
| 🔴 **CRITIQUE** | Secrets `.env` non chiffrés dans l'environnement | Docker Secrets ou HashiCorp Vault | Fuite credentials si accès serveur |
| 🟠 **HAUTE** | Absence de rate limiting sur `/login` | `symfony/rate-limiter` ou `nginx limit_req_zone` | Brute force, DDoS sur l'authentification |
| 🟠 **HAUTE** | Catalogue sans cache (MySQL à chaque requête) | Cache Symfony sur `CatalogueService` ou Redis | Goulot identifié en stress test |
| 🟡 **MOYENNE** | Headers HTTP sécurité absents | HSTS, CSP, X-Frame-Options via NelmioCors/Nginx | OWASP Top 10, protection XSS |
| 🟡 **MOYENNE** | Logs non centralisés en production | Monolog → Grafana Cloud activé en prod | Détection incidents, forensics |

**Notes orateur :**
> "Ce plan est directement issu des tests. La priorité absolue c'est phpMyAdmin en production. Le cache sur CatalogueService est la réponse directe à ce qu'on a vu dans le stress test — chaque requête catalogue frappe MySQL, c'est le premier goulot à supprimer."

---

## SLIDE 12 — Perspectives V2 — Kubernetes
**Temps : 1 min 30 s** | Compétences : **[CI] [EXP]**

**Contenu :**

**Problème V1 :** Docker Compose = single-host, pas de scaling automatique → insuffisant pour les pics Halloween/festival

**Solution V2 : Kubernetes**

```
Internet → Ingress (Nginx/Traefik)
              │
    [ Kubernetes Cluster ]
         ├── Deployment: php-fpm Symfony
         │       └── HPA : scale si CPU > 80%   (min 2 → max 10 replicas)
         ├── Deployment: nginx
         ├── StatefulSet: mysql         ← PVC volume persistant
         └── Namespace preprod : phpMyAdmin uniquement

Monitoring : Prometheus + Grafana (Helm charts)
CI/CD V2  : kubectl apply (remplace Coolify webhook)
```

**Comparatif :**
| Critère | Docker Compose (V1) | Kubernetes (V2) |
|---------|---------------------|-----------------|
| Scalabilité | Manuelle | HPA automatique (CPU > 80%) |
| Disponibilité | Single host | Multi-node, auto-restart |
| Mise à jour | Downtime | Rolling update, 0 downtime |
| Rate limiting | À configurer | Ingress natif |

**Plan de migration :**
1. POC K3d local (K3s dans Docker, iso-production)
2. Validation Traefik Ingress + TLS
3. HPA testé sous charge → objectif : 0 dégradation à 100 VUs

**Notes orateur :**
> "Kubernetes répond directement aux deux causes de dégradation identifiées : l'HPA scale automatiquement les pods php-fpm si le CPU dépasse 80%, et le rolling update élimine le downtime. K3d permet de valider tout ça en local avant de passer en production."

---

## SLIDE 13 — Sylius : E-commerce Modulaire (V3)
**Temps : 30 s** | Compétences : **[EXP]**

**Contenu :**

**Sylius** = framework e-commerce PHP/Symfony, API-first, modulaire

| Critère | Sylius | PrestaShop |
|---------|--------|-----------|
| Architecture | Découplée, API Platform | Monolithique |
| Compatible Docker/K8s | Natif | Difficile |
| Stack | Symfony natif (réutilise notre code) | Thèmes/modules fragiles |

**Fonctionnalités V3 :** panier, commandes, abonnements fanzine, e-reader, catalogue avancé

**Notes orateur :**
> "En V3, Sylius remplace le catalogue custom. Son architecture Symfony-native signifie que tous nos services — CatalogueService, RecommendationService — sont directement réutilisables."

---

## SLIDE 14 — Bilan & Synthèse
**Temps : 1 min** | Compétences : **[ORAL]**

**Contenu :**

| ✅ Livré | Preuve |
|---------|--------|
| Pipeline CI/CD automatisé | 8 jobs GitHub Actions + Coolify |
| Qualité mesurée en continu | 4 métriques ISO 25010 |
| Fonctionnalité métier testée | CatalogueService (9 tests) + RecommendationService (3 tests) |
| Sécurité validée | 4 tests fonctionnels (redirect, CSRF, auth) |
| Tests de charge | k6 : p95 < 200ms, scénario réel (session + CSRF) |
| Observabilité | Grafana Cloud : logs + métriques |

**Roadmap :**
- **V2** → Kubernetes (HPA, 0 downtime, scaling auto)
- **V2** → Rate limiting + cache Redis sur CatalogueService
- **V3** → Sylius e-commerce modulaire

**Notes orateur :**
> "Pour résumer : chaque fonctionnalité est couverte par des tests automatisés, chaque déploiement est vérifié par un pipeline complet, et chaque décision technique est justifiée par des mesures concrètes. Je suis disponible pour vos questions."

---

## TIMING GLOBAL

| Slide | Contenu | Durée |
|-------|---------|-------|
| 1 | Titre | 30s |
| 2 | Contexte & Équipe | 1min30 |
| 3 | ISO 25010 | 2min |
| 4 | DevSecOps | 1min30 |
| 5 | Pipeline CI/CD | 2min |
| 6 | Architecture | 1min30 |
| 7 | Sandbox | 2min |
| 8 | Fonctionnalité & Tests | 2min |
| 9 | DÉMO LIVE | 4min |
| 10 | Résultats charge | 2min |
| 11 | Remédiation | 2min |
| 12 | Kubernetes V2 | 1min30 |
| 13 | Sylius V3 | 30s |
| 14 | Bilan | 1min |
| **TOTAL** | | **~20min** |

---

## ÉLÉMENTS VISUELS RECOMMANDÉS

1. **Slide 5** : Schéma pipeline en cascade coloré (vert=tests, rouge=sécu, bleu=deploy, violet=charge)
2. **Slide 6** : Diagramme architecture avec flèches HTTPS/session
3. **Slide 8** : Tableau tests avec code testdox réel (sortie `--testdox` copiée)
4. **Slide 10** : Capture Grafana Cloud — courbe de latence k6 (montée en charge)
5. **Slide 12** : Diagramme K8s (pods, HPA, Ingress, PVC)

---

## CE QUI A ÉTÉ CORRIGÉ PAR RAPPORT À LA VERSION PRÉCÉDENTE

| Élément | Version précédente (incorrecte) | Version corrigée |
|---------|---------------------------------|-----------------|
| Type d'auth démontré | JWT API (Postman) | Session (formulaire + CSRF) — réalité du code |
| Feature métier | API `/api/products` | CatalogueService + RecommendationService |
| k6 scénario | JWT Bearer token | Login formulaire → PHPSESSID cookie |
| Tests fonctionnels | WebTestCase JWT | `CatalogueSecurityTest` + `LoginControllerTest` |
| Stress test analysé | Générique | Basé sur le vrai scénario k6 (100 VUs) |
| Kubernetes | Absent | Slide dédié V2 (HPA, K3d, comparatif) |
