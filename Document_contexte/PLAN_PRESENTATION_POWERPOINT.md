# Structure PowerPoint — Présentation Individuelle BLOC 3
## Mickaël Dijoux — Lead Developer — Mars 2026
### Durée : 20 min + 15 min questions

---

> **Grille de lecture** : inspiré de la structure en 3 PARTIES de Bryan Joubert et du niveau de détail de Thibaut Jacquemin, adapté à ta stack réelle (session auth, CatalogueService, 8 jobs CI/CD).

---

## SLIDE 1 — Titre
**Temps : 30s**

- Titre : *"Superviser et assurer le développement d'une application — Approche DevSecOps"*
- Sous-titre : *La Petite Maison de l'Épouvante — V1 · POC validé · Lead Developer*
- Mickaël Dijoux · MAALSI 25-26 · Mars 2026

**Note orateur :**
> "Bonjour, je suis Mickaël Dijoux, Lead Developer sur La Petite Maison de l'Épouvante. En 20 minutes, je vais vous montrer comment j'ai structuré la qualité, la sécurité et le déploiement d'un prototype e-commerce via une approche DevSecOps de bout en bout."

---

## SLIDE 2 — Sommaire
**Temps : 30s**

**Structure visuelle en 3 blocs :**

| 01 | 02 | 03 |
|----|----|----|
| **Processus Qualité** | **Développement & Déploiement** | **Sécurité & Remédiation** |
| ▸ 4 métriques ISO 25010 | ▸ User Story & Backlog | ▸ Analyse OWASP Top 10 |
| ▸ Cycle DevSecOps | ▸ Architecture technique | ▸ Plan de remédiation |
| ▸ Pipeline CI/CD 8 jobs | ▸ Bac à sable POC | ▸ Mesures préventives V2 |
| ▸ Équipe & formation | ▸ Résultats & chiffres clés | |

---

---

## PARTIE 1 — PROCESSUS QUALITÉ
### ISO 25010 · DevSecOps · CI/CD · Compétences

---

## SLIDE 3 — Contexte & Mission
**Temps : 1 min 30s**

**L'entreprise :**
- La Petite Maison de l'Épouvante — e-commerce horreur/fantasy
- 3 magasins physiques · fanzine · festival annuel · collectif Evil Ed
- Contrainte : pics de trafic Halloween + festival → disponibilité critique
- SI hétérogène · CMS basique · aucun pipeline · aucun processus qualité

**Ma mission — Lead Developer :**
- Définir un processus qualité ISO 25010
- Développer la V1 avec pipeline CI/CD + déploiement automatisé
- Analyser la sécurité et proposer un plan de remédiation

**Note orateur :**
> "L'entreprise doit absorber des pics de charge importants. Mon rôle de Lead Dev implique d'encadrer deux juniors avec des lacunes en Docker et tests. J'ai adopté une approche DevSecOps dès le départ."

---

## SLIDE 4 — Qualité Logicielle — ISO 25010
**Temps : 2 min**

**4 métriques bloquantes pour éviter la dette technique**

| Attribut | Indicateur | Outil | Résultat | Prévention dette |
|----------|-----------|-------|---------|-----------------|
| **Fiabilité** | Couverture tests (%) | PHPUnit `--coverage-clover` | ✅ Mesuré | Zones non testées → bugs silencieux |
| **Maintenabilité** | Code smells / dette estimée | SonarQube SAST | ✅ Actif | Dégradation progressive du code |
| **Sécurité** | CVE critiques (cible : 0) | SonarQube Scan | ✅ 0 bloquant | Failles = dette sécurité critique |
| **Performance** | p95 < 200ms | k6 + Grafana Cloud | ✅ Validé | Dégradation silencieuse = coût futur |

> Ces 4 indicateurs sont mesurés automatiquement à chaque pipeline — toute régression bloque le déploiement.

**Note orateur :**
> "Chaque indicateur est mesuré sans intervention manuelle. SonarQube bloque le pipeline si un CVE critique est détecté. k6 échoue si le p95 dépasse 200ms. C'est l'ISO 25010 appliquée concrètement."

---

## SLIDE 5 — DevSecOps — Shift Left Security
**Temps : 1 min 30s**

**Principe : sécurité vérifiée à chaque étape, pas seulement en fin de cycle**

```
Plan        → Backlog · User Stories · critères d'acceptation
Code        → Secrets dans GitHub Secrets (jamais en clair)
Commit      → SonarQube SAST (vulnérabilités, injections, code smells)
Build       → Image Docker Alpine (surface d'attaque minimale)
Test        → Auth testée : /product sans session → redirect /login (302)
             + CSRF vérifié dans les tests fonctionnels ET par k6
Deploy      → HTTPS/TLS · Coolify webhook signé
Runtime     → Monolog → Grafana Cloud (logs centralisés, alertes)
```

**2 bonnes pratiques sécurité intégrées au POC :**
1. **HTTPS/TLS** — certificat SSL sur nginx, toutes les communications chiffrées
2. **Protection CSRF** — token sur le formulaire login, vérifié par Symfony Security et extrait dynamiquement par k6

**Note orateur :**
> "La sécurité est déplacée le plus tôt possible dans le cycle. Le test fonctionnel `testCatalogueRedirectsToLoginWhenNotAuthenticated` garantit qu'aucune route protégée n'est accessible sans session."

---

## SLIDE 6 — Pipeline CI/CD — 8 Jobs Bloquants
**Temps : 1 min 30s**

**GitHub Actions — déclencheur : `push` sur `déploiement-fonctionnelle`**

```
① INSTALL          PHP 8.4, Composer, cache vendor
        ↓
② SECURITY SCAN    SonarQube SAST — CVE, code smells, dette technique
        ↓
③ TESTS UNITAIRES  PHPUnit : CatalogueService (9 tests) + RecommendationService (3 tests)
        ↓
④ TESTS FONCT.     PHPUnit WebTestCase : CatalogueSecurityTest (4 tests) + coverage
        ↓
⑤ NON-RÉGRESSION   curl baseline preprod : / (200), /login (200), /product (302)
        ↓
⑥ DEPLOY PREPROD   Webhook Coolify → redéploiement Docker automatique
        ↓
⑦ NON-RÉGRESSION   Mêmes vérifications 30s après déploiement
        ↓
⑧ LOAD TEST        k6 cloud → Paris (amazon:fr:paris) → Grafana Cloud
```

**Note orateur :**
> "Chaque job dépend du précédent via `needs:`. Si les tests unitaires échouent, le déploiement ne se fait pas. La non-régression pré/post garantit qu'on ne dégrade pas l'état de la preprod."

---

## SLIDE 7 — Équipe & Montée en Compétences
**Temps : 1 min**

**Cartographie des compétences**

| Rôle | Profil | Maîtrisé | Lacune identifiée |
|------|--------|----------|-------------------|
| Lead Dev / Architecte | Dijoux M. | Symfony · Docker · CI/CD · Tests | Orchestration K8s |
| Dev Junior #1 | Dev J1 | PHP · Symfony back · Doctrine | Tests auto · SonarQube |
| Dev Junior #2 | Dev J2 | PHP · SQL · Composer | Docker · k6 |

**Action de formation proposée :**
> Atelier 2 jours : Docker + GitHub Actions → objectif : autonomie complète sur le pipeline pour les 2 juniors

**Note orateur :**
> "Les deux juniors peuvent contribuer mais ne peuvent pas encore maintenir le pipeline seuls. L'atelier leur permet de comprendre chaque job et d'intervenir en cas de blocage."

---

---

## PARTIE 2 — DÉVELOPPEMENT & DÉPLOIEMENT
### Backlog · Architecture · POC · Résultats · Disponibilité

---

## SLIDE 8 — Analyse des Exigences — User Story
**Temps : 1 min**

**Fonctionnalité implémentée — Backlog V1**

| ID | User Story | Statut |
|----|-----------|--------|
| US-01 | En tant qu'utilisateur authentifié, je veux parcourir le catalogue filtré et trié | ✅ IMPLEMENTED |
| US-02 | En tant qu'utilisateur authentifié, je veux recevoir des recommandations basées sur mes consultations | ✅ IMPLEMENTED |

**Critères d'acceptation US-01 :**
- `GET /product` sans session → redirect `/login` (302)
- `GET /product` avec session valide → catalogue (200)
- Produits filtrés par stock, catégorie, triés par prix
- Données persistées MySQL via Doctrine ORM

**Note orateur :**
> "La fonctionnalité couvre le cas d'usage central : accéder au catalogue de manière sécurisée. Les critères d'acceptation sont directement traduits en tests PHPUnit — ce n'est pas du texte, c'est du code exécutable."

---

## SLIDE 9 — Architecture Technique
**Temps : 1 min 30s**

```
Utilisateur (navigateur)
      │ HTTPS/TLS
      ▼
  [ Nginx Alpine ]        ← reverse proxy · SSL termination · routage
      │
      ▼
  [ PHP-FPM 8.4 ]         ← Symfony 7
      │   ├── CatalogueService      (filtre stock · catégorie · tri prix)
      │   ├── RecommendationService (recommandations + cache Symfony TTL 300s)
      │   └── Security              (session auth · CSRF · redirect /login)
      ▼
  [ MySQL 8.0 ]           ← Doctrine ORM · Migrations · Fixtures
      │
  [ phpMyAdmin ]          ← preprod uniquement ⚠️

  Observabilité : Monolog → Grafana Cloud · k6 Cloud → Grafana Cloud
```

**Stack Docker Compose :** php:8.4-fpm-alpine · nginx:alpine · mysql:8.0 · phpmyadmin · sonarqube
**Déploiement :** Coolify + Linux · **Monitoring :** Grafana Cloud

**Note orateur :**
> "Architecture découplée. Nginx gère le TLS, php-fpm exécute Symfony avec ses deux services métier, MySQL persiste les données. phpMyAdmin est uniquement en preprod — c'est une vulnérabilité identifiée dans mon plan de remédiation."

---

## SLIDE 10 — Bac à Sable — POC & Expérimentation
**Temps : 1 min 30s**

**Protocole : valider chaque technologie critique AVANT de l'intégrer**

| Technologie | Ce qui a été testé | Difficulté rencontrée | Résultat |
|-------------|-------------------|-----------------------|----------|
| **Coolify + Docker** | Déploiement automatisé via webhook | Config réseau Docker inter-conteneurs | ✅ Validé |
| **SonarQube** | Scan SAST PHP/Symfony | Project key · intégration GitHub Actions | ✅ Validé |
| **Grafana Cloud** | Logs Monolog + métriques k6 | Auth API Grafana · datasource config | ✅ Validé |
| **k6 cloud** | Auth session avec extraction CSRF | Parsing HTML token CSRF dynamique | ✅ Validé |
| **PHPUnit WebTestCase** | Tests fonctionnels avec MySQL réel | Service MySQL éphémère GitHub Actions | ✅ Validé |

**Note orateur :**
> "k6 devait extraire dynamiquement le token CSRF du HTML avant chaque connexion. Sans ce test préalable, j'aurais découvert ce problème directement en pipeline. C'est exactement l'utilité du bac à sable."

---

## SLIDE 11 — Qualité Mesurée — Résultats Chiffres Clés
**Temps : 1 min**

**Résultats concrets du POC**

| **16** | **9 + 3** | **4** | **0** |
|--------|-----------|-------|-------|
| tests PHPUnit au total | tests unitaires | tests fonctionnels | CVE bloquant |
| | CatalogueService + RecommendationService | sécurité session | SonarQube |

| **p95 < 200ms** | **< 1% erreurs** | **8 jobs ✅** |
|-----------------|-----------------|--------------|
| sous 50 VUs | k6 load test | pipeline complet |

**✅ Pipeline complet : install → sast → unit → functional → non-reg → deploy → non-reg → k6**

**Note orateur :**
> "Chaque chiffre est produit automatiquement par le pipeline. Ce ne sont pas des estimations : ce sont des mesures réelles issues des derniers runs GitHub Actions."

---

## SLIDE 12 — Mise en Production — Disponibilité
**Temps : 1 min**

**Orchestrer la production pour garantir la disponibilité**

| Mécanisme | Implémentation | Effet |
|-----------|---------------|-------|
| Restart automatique | `restart: unless-stopped` Docker Compose | Redémarrage auto si crash conteneur |
| Health check pipeline | Non-régression pré + post deploy | On ne déploie que si la preprod répond |
| Persistance des données | Volume Docker MySQL | Données conservées entre déploiements |
| Performance validée | k6 : p95 < 200ms · < 1% erreurs | Validé sous charge simulée |

**Limites V1 identifiées (par les tests) :**
- Single replica PHP-FPM → dégradation à partir de 100 VUs
- Pas de scaling automatique → intervention manuelle requise

**Roadmap V2 : Kubernetes HPA → scaling auto · rolling update · SLA 99,9%**

---

---

## SLIDE 13 — DÉMONSTRATION LIVE
**Temps : 4 min**

*(Voir `PLAN_DEMONSTRATION.md` pour le script détaillé)*

| # | Ce qu'on montre | Résultat attendu |
|---|----------------|-----------------|
| 1 | Pipeline GitHub Actions — dernier run | 8 jobs verts ✅ |
| 2 | Sortie testdox — tests unitaires | CatalogueService · RecommendationService ✅ |
| 3 | Sortie testdox — tests fonctionnels | CatalogueSecurityTest · LoginControllerTest ✅ |
| 4 | SonarQube dashboard | 0 CVE bloquant · dette mesurée |
| 5 | Application déployée sur Coolify | Conteneurs actifs · accès HTTPS |
| 6 | `GET /product` sans session | Redirect `/login` → sécurité prouvée |
| 7 | k6 + Grafana Cloud | p95 < 200ms · taux erreur < 1% |

---

---

## PARTIE 3 — SÉCURITÉ & REMÉDIATION
### OWASP Top 10 · Plan de remédiation · Mesures préventives V2

---

## SLIDE 14 — Analyse Sécurité — OWASP Top 10
**Temps : 1 min 30s**

**7 vulnérabilités identifiées en V1**

| ID | Vulnérabilité | Niveau |
|----|--------------|--------|
| V-01 | phpMyAdmin accessible en production | 🔴 CRITIQUE |
| V-02 | Secrets `.env` non chiffrés dans l'environnement | 🔴 CRITIQUE |
| V-03 | Absence de rate limiting sur `/login` | 🟠 HAUTE |
| V-04 | Catalogue sans cache — MySQL à chaque requête | 🟠 HAUTE |
| V-05 | Headers HTTP sécurité absents (HSTS · CSP) | 🟡 MOYENNE |
| V-06 | Logs non centralisés en production | 🟡 MOYENNE |
| V-07 | Image Docker non auditée (CVE potentiels système) | 🟡 BASSE |

**Note orateur :**
> "V-04 a été révélée par le test de charge : à 100 VUs, chaque requête frappe MySQL. V-03 est prouvée par le scénario k6 — sans rate limiting, n'importe qui peut faire du brute force sur `/login` sans limite."

---

## SLIDE 15 — Plan de Remédiation — 3 Sprints
**Temps : 1 min 30s**

**Du critique au préventif**

| Sprint | Délai | Actions |
|--------|-------|---------|
| **Sprint 1** | Immédiat | • Désactiver phpMyAdmin en prod (V-01) |
| Correctif critique | | • Secrets → Docker Secrets ou Vault (V-02) |
| | | • Rate limiting `/login` via `symfony/rate-limiter` (V-03) |
| **Sprint 2** | 1 mois | • Cache Symfony sur CatalogueService TTL 60s (V-04) |
| Correctif haute | | • Headers HSTS · CSP · X-Frame-Options via Nginx (V-05) |
| | | • Monolog → Grafana Cloud activé en production (V-06) |
| **Sprint 3** | 3 mois | • Trivy scan image Docker dans le pipeline (V-07) |
| Préparation V2 | | • OWASP ZAP intégré au job staging |
| | | • Dependabot sur `composer.json` (alertes CVE auto) |

**Note orateur :**
> "Le Sprint 1 couvre les risques immédiats. Le cache sur CatalogueService est la réponse directe au stress test — chaque requête catalogue frappe MySQL, c'est le premier goulot à supprimer."

---

## SLIDE 16 — Version 2 — DevSecOps Renforcé
**Temps : 1 min**

**3 mesures préventives pour la V2**

| # | Mesure | Détail |
|---|--------|--------|
| **01** | **Secrets Management** | HashiCorp Vault — rotation automatique des clés + audit trail |
| **02** | **DAST Pipeline** | OWASP ZAP sur conteneur éphémère — rapport SARIF → GitHub Security |
| **03** | **SCA Dépendances** | Dependabot activé sur `composer.json` — alertes CVE + PR automatique |

**Kubernetes V2 — réponse directe aux limites V1 :**

| Limite V1 (identifiée par k6) | Solution K8s |
|------------------------------|-------------|
| Single replica → saturation 100 VUs | HPA : scale si CPU > 80% (min 2 → max 10 réplicas) |
| MySQL sollicité à chaque requête | Redis Helm chart + cache Symfony |
| Pas de rate limiting | Ingress Nginx : `limit-rps` natif |
| Single host = SPOF | Multi-node · rolling update · 0 downtime |

---

## SLIDE 17 — Bilan & Synthèse
**Temps : 30s**

**Livraison complète de la V1**

| ✅ Livré | Preuve |
|---------|--------|
| Pipeline CI/CD automatisé | 8 jobs GitHub Actions + Coolify |
| Qualité mesurée en continu | 4 métriques ISO 25010 |
| Fonctionnalité métier testée | CatalogueService (9 tests) + RecommendationService (3 tests) |
| Sécurité validée | 4 tests fonctionnels (redirect · CSRF · auth) |
| Tests de charge | k6 : p95 < 200ms · scénario réel session + CSRF |
| Observabilité | Grafana Cloud : logs + métriques |

**Roadmap :**
- **V2** → Kubernetes · HPA · Rate limiting · Redis · OWASP ZAP · Dependabot
- **V3** → Sylius e-commerce modulaire (réutilise CatalogueService + RecommendationService)

---

## TIMING GLOBAL

| Slide | Contenu | Durée |
|-------|---------|-------|
| 1 | Titre | 30s |
| 2 | Sommaire | 30s |
| **— PARTIE 1 —** | | |
| 3 | Contexte & Mission | 1min30 |
| 4 | ISO 25010 | 2min |
| 5 | DevSecOps Shift Left | 1min30 |
| 6 | Pipeline 8 jobs | 1min30 |
| 7 | Équipe & formation | 1min |
| **— PARTIE 2 —** | | |
| 8 | User Story & Backlog | 1min |
| 9 | Architecture technique | 1min30 |
| 10 | Bac à sable POC | 1min30 |
| 11 | Résultats chiffres clés | 1min |
| 12 | Disponibilité | 1min |
| **— DÉMO —** | | |
| 13 | Démonstration live | 4min |
| **— PARTIE 3 —** | | |
| 14 | OWASP Top 10 | 1min30 |
| 15 | Plan remédiation 3 sprints | 1min30 |
| 16 | V2 DevSecOps renforcé | 1min |
| 17 | Bilan | 30s |
| **TOTAL** | | **~20min** |

---

## CE QUI A CHANGÉ PAR RAPPORT À L'ANCIENNE VERSION

| Élément | Ancienne version | Nouvelle version |
|---------|-----------------|-----------------|
| Structure | 14 slides linéaires | 3 PARTIES délimitées + sommaire visuel (comme Bryan) |
| Contexte | Slide 2 | Slide 3 — pose le cadre avant tout |
| User Story | Intégrée slide tests | Slide dédiée avec backlog (comme Bryan) |
| Résultats | Dispersés | Slide "Chiffres clés" grands nombres (comme Bryan) |
| Sécurité | Plan remédiation seul | OWASP 7 vulnérabilités + 3 sprints + V2 (comme Bryan) |
| V2 | Kubernetes seul | Kubernetes + DAST + Dependabot + Redis (comme Bryan) |
| Disponibilité | Dans slide K8s | Slide dédiée limites V1 (comme Thibaut) |
| Démo | Slide 9 | Slide 13 — charnière Partie 2 / Partie 3 |
