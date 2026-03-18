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
- Nom, date, rôle : Mickaël Dijoux — Lead Developer — Mars 2026
- Logo / visuel sobre (fantôme/horror discret ou fond sombre)

**Notes orateur :**
> "Bonjour, je suis Mickaël Dijoux, Lead Developer sur le projet La Petite Maison de l'Épouvante. En 20 minutes, je vais vous présenter comment j'ai structuré la qualité, la sécurité et le déploiement d'un prototype e-commerce, en adoptant une approche DevSecOps de bout en bout."

---

## SLIDE 2 — Contexte & Équipe
**Temps : 1 min 30 s** | Compétences : **[CI] [EXP]**

**Contenu :**
**Entreprise :** La Petite Maison de l'Épouvante
- E-commerce horreur/fantasy : figurines, films, fanzines, jeux
- Pic de trafic : Halloween, festival annuel → exigences de **disponibilité et scalabilité**
- Contrainte forte : hébergement européen, sécurité commerciale (RGPD)

**Équipe :**
| Rôle | Nom | Compétences | Gap identifié |
|------|-----|-------------|---------------|
| Lead Dev / Architecte | Dijoux M. | Symfony, Docker, CI/CD, Sécurité | Orchestration K8s |
| Développeur junior #1 | Dev J1 | PHP, Twig, Git basique | Docker, DevOps |
| Développeur junior #2 | Dev J2 | PHP, SQL, Composer | Tests automatisés |

**Action de formation proposée :**
- Atelier 2 jours : Docker + CI/CD GitHub Actions → autonomie sur le pipeline

**Notes orateur :**
> "L'entreprise doit gérer des pics de charge importants autour de l'Halloween et de son festival. Mon rôle de Lead Dev implique aussi d'accompagner deux juniors. J'ai identifié leurs lacunes en Docker et en tests automatisés, et proposé un atelier de 2 jours pour y remédier."

---

## SLIDE 3 — Processus Qualité — ISO 25010
**Temps : 2 min** | Compétences : **[QA]**

**Contenu :**
**Modèle qualité retenu : ISO 25010**

| # | Attribut | Indicateur | Outil | Prévention dette technique |
|---|----------|-----------|-------|---------------------------|
| IND-01 | **Fiabilité** | Couverture de tests (%) | PHPUnit | Zones non testées → bugs silencieux futurs |
| IND-02 | **Maintenabilité** | Code smells / dette estimée | SonarQube | Dégradation progressive du code Symfony |
| IND-03 | **Sécurité** | Vulnérabilités détectées (0 CVE cible) | SonarQube SAST | Failles non corrigées = dette sécurité critique |
| IND-04 | **Performance** | Temps de réponse API (ms) | k6 + Grafana Cloud | Dégradations non détectées = coût futur |

**Objectif :** mesure automatique à chaque push → tableau de bord continu

**Notes orateur :**
> "J'ai sélectionné 4 indicateurs ISO 25010 mesurés automatiquement dans le pipeline. La couverture PHPUnit prévient les régressions silencieuses, SonarQube bloque les failles et code smells avant le merge, et k6 avec Grafana détecte les dégradations de performance avant qu'elles n'atteignent la production."

---

## SLIDE 4 — DevSecOps : Sécurité Intégrée
**Temps : 1 min 30 s** | Compétences : **[QA] [CI]**

**Contenu :**
**Approche : Security by Design — sécurité à chaque étape du cycle**

```
Code          → Git secrets dans .env (jamais en clair dans le dépôt)
Commit        → SonarQube SAST (vulnérabilités, injections)
Build         → Image Docker PHP 8.4-FPM Alpine (surface d'attaque minimale)
Test          → JWT auth validée (401 sans token, 200 avec token valide)
Deploy        → HTTPS/TLS obligatoire, Coolify webhook token
Runtime       → Monolog → Grafana Cloud (logs centralisés, alertes)
```

**2 bonnes pratiques sécurité intégrées au POC :**
1. **JWT (Lexik Bundle)** — authentification stateless, TTL contrôlé, refresh token prévu en V2
2. **HTTPS/TLS** — certificat SSL nginx, toutes communications chiffrées

**Notes orateur :**
> "La sécurité n'est pas une phase finale, elle est intégrée à chaque étape. SonarQube bloque le pipeline si un CVE critique est détecté. Le déploiement ne se fait qu'en HTTPS. Les secrets ne transitent jamais en clair — ils sont injectés via les variables d'environnement GitHub Secrets."

---

## SLIDE 5 — Pipeline CI/CD — 8 Étapes
**Temps : 2 min** | Compétences : **[CI]**

**Contenu :**
**GitHub Actions — déclencheur : push sur `déploiement-fonctionnelle`**

```
[1] BUILD          → PHP 8.4, Composer, cache vendor
        ↓ bloquant
[2] SECURITY SCAN  → SonarQube SAST (CVE, code smells, dette)
        ↓ bloquant
[3] TESTS UNITAIRES → PHPUnit — services, entités, logique métier
        ↓ bloquant
[4] TESTS FONCTIONNELS → PHPUnit WebTestCase — routes JWT (401/200)
                          + coverage-clover.xml généré
        ↓ bloquant (push only)
[5] NON-RÉGRESSION PRÉ-DEPLOY → vérif baseline preprod (HTTP 200/302)
        ↓ bloquant
[6] DÉPLOIEMENT PREPROD → Coolify webhook + Docker
        ↓ bloquant
[7] NON-RÉGRESSION POST-DEPLOY → vérif preprod après déploiement
        ↓ bloquant
[8] LOAD TEST      → k6 cloud → Grafana Cloud (métriques temps réel)
```

**Environnement géré :** Coolify + Docker sur Linux (preprod & prod)

**Notes orateur :**
> "Chaque étape est bloquante — si les tests échouent, le déploiement ne se fait pas. La non-régression pré et post-déploiement garantit que l'état baseline de la preprod n'est pas dégradé. Le load test final est automatiquement envoyé à Grafana Cloud."

---

## SLIDE 6 — Architecture Technique
**Temps : 1 min 30 s** | Compétences : **[EXP]**

**Contenu :**
**Schéma d'architecture (à dessiner sur le slide) :**

```
Internet / Client
      │ HTTPS/TLS
      ▼
  [ Nginx Alpine ]  ← reverse proxy + SSL termination
      │
      ▼
  [ PHP-FPM 8.4 ]  ← Symfony 7 (Controllers, Services, Doctrine)
      │                  JWT via Lexik Bundle
      │                  NelmioCors (CORS)
      ▼
  [ MySQL 8.0 ]    ← Doctrine ORM, Migrations

  [ phpMyAdmin ]   ← preprod uniquement (désactivé prod)
  [ SonarQube ]    ← local / CI uniquement

  Observabilité :
  Monolog → Grafana Cloud (logs + métriques)
  k6 Cloud → Grafana Cloud (performance)
```

**Stack complète :**
- Backend : Symfony 7, Lexik JWT, Doctrine ORM
- Infrastructure : Docker Compose (php, nginx, mysql, phpmyadmin, sonarqube)
- Déploiement : Coolify + Linux
- Monitoring : Grafana Cloud, k6

**Notes orateur :**
> "L'architecture est volontairement découplée : nginx gère le TLS, php-fpm exécute Symfony, MySQL persiste les données. Chaque conteneur a un rôle unique. En production, phpMyAdmin est désactivé — c'est une vulnérabilité identifiée dans mon plan de remédiation."

---

## SLIDE 7 — Expérimentations Sandbox
**Temps : 2 min** | Compétences : **[EXP]**

**Contenu :**
**Protocole avant développement : valider les technologies critiques en isolation**

| Technologie | Objectif testé | Difficulté rencontrée | Résultat |
|-------------|---------------|----------------------|----------|
| **Coolify + Docker** | Déploiement automatisé via webhook | Configuration réseau Docker inter-conteneurs | ✅ Déploiement automatisé validé |
| **SonarQube** | Scan SAST PHP/Symfony | Analyse locale → configuration du projet key | ✅ Scan PHP opérationnel |
| **Grafana Cloud** | Collecte métriques Monolog + k6 | Authentification Grafana + config datasource | ✅ Métriques collectées en temps réel |
| **k6 Cloud** | Test de charge API REST JWT | Authentification JWT dans les scripts k6, envoi cloud | ✅ Load test sur `/api/products` fonctionnel |
| **Lexik JWT** | Auth stateless Symfony | Certificats SSL locaux auto-signés (TLS) | ✅ 401 sans token, 200 avec token valide |

**Notes orateur :**
> "Avant de coder la fonctionnalité, j'ai validé chaque technologie en sandbox. La difficulté principale était la configuration réseau Docker et les certificats TLS auto-signés en local. Ces expérimentations m'ont permis d'anticiper les problèmes plutôt que de les découvrir en intégration."

---

## SLIDE 8 — Fonctionnalité Métier & User Story
**Temps : 1 min 30 s** | Compétences : **[EXP]**

**Contenu :**
**User Story (backlog):**
> *"En tant qu'utilisateur authentifié, je veux accéder à la liste des produits de la plateforme pour parcourir le catalogue."*

**Critères d'acceptation :**
- ✅ `POST /api/login` → token JWT généré (200 OK)
- ✅ `GET /api/products` sans token → 401 Unauthorized
- ✅ `GET /api/products` avec token → 200 OK + payload JSON
- ✅ Données persistées via MySQL / Doctrine ORM
- ✅ Tests PHPUnit WebTestCase : couvrent les 3 cas

**Choix techniques justifiés :**
| Choix | Justification |
|-------|--------------|
| Symfony 7 | Framework PHP mature, DI native, ORM Doctrine intégré |
| Lexik JWT | Standard OAuth2/JWT, stateless, adapté API REST |
| MySQL 8.0 | SGBD relationnel robuste, compatible Doctrine |
| NelmioCors | Gestion CORS propre pour API consommée par React |

**Aperçu V2 :** Système de recommandations produits par filtrage collaboratif (historique achats)

**Notes orateur :**
> "La fonctionnalité choisie couvre le cœur métier : l'accès sécurisé au catalogue. Elle permet de valider l'authentification JWT de bout en bout, et les tests automatisés couvrent les cas nominaux et les cas d'erreur sécurité."

---

## SLIDE 9 — DÉMONSTRATION LIVE
**Temps : 4 min** | Compétences : **[CI] [EXP]**

> *(Voir le Plan de Démonstration dédié pour le détail pas-à-pas)*

**Contenu du slide (tableau récapitulatif affiché pendant la démo) :**

| Étape | Action | Résultat attendu |
|-------|--------|-----------------|
| 1 | Pipeline GitHub Actions | Tous les jobs verts ✅ |
| 2 | Application déployée (Coolify) | Conteneurs actifs |
| 3 | `POST /api/login` | JWT token généré |
| 4 | `GET /api/products` sans token | 401 Unauthorized |
| 5 | `GET /api/products` avec token | 200 OK + JSON |
| 6 | phpMyAdmin preprod | Données en BDD |
| 7 | k6 + Grafana Cloud | Métriques temps réel |

**Notes orateur :**
> "Je vais maintenant faire une démonstration en 7 étapes. En cas de problème réseau, j'ai une vidéo de backup prête."

---

## SLIDE 10 — Résultats Tests de Charge & Analyse
**Temps : 2 min** | Compétences : **[QA] [CI]**

**Contenu :**
**Test nominal (seuil de performance) :**
- Outil : k6 (script `k6/load-test.js`)
- Paramètres : 50 VUs × 30s → ~X req/s
- Résultat : temps de réponse < 200ms ✅, 0 erreur

**Test de stress (limite du système) :**
- Paramètres : montée progressive jusqu'à 500 VUs
- Résultat observé : dégradation des temps de réponse au-delà de X VUs
- Causes identifiées :
  - Pas de rate limiting (DDoS possible)
  - Single replica PHP-FPM (pas de scaling horizontal)
  - Pas de cache (MySQL sollicité à chaque requête)

**Graphique Grafana Cloud** : [insérer capture d'écran dashboard]

**Notes orateur :**
> "En conditions nominales, l'API répond sous 200ms. En conditions de stress, j'ai identifié trois causes de dégradation : l'absence de rate limiting, l'instance unique PHP-FPM, et l'absence de cache. Ce diagnostic alimente directement mon plan de remédiation."

---

## SLIDE 11 — Plan de Remédiation (Sécurité & Performance)
**Temps : 2 min** | Compétences : **[QA] [CI]**

**Contenu :**
**Vulnérabilités identifiées → Actions priorisées**

| Priorité | Vulnérabilité | Action | Justification |
|----------|---------------|--------|---------------|
| 🔴 **CRITIQUE** | phpMyAdmin exposé en prod | Désactiver en prod, restreindre IP en preprod | Accès DB direct sans auth forte |
| 🔴 **CRITIQUE** | Secrets `.env` non chiffrés | Docker Secrets ou Vault manager | Fuite credentials si dépôt compromis |
| 🟠 **HAUTE** | Absence de rate limiting | ThrottleBundle Symfony / Nginx limit_req | Protection brute force & DDoS |
| 🟠 **HAUTE** | TTL JWT trop long | Réduire TTL + refresh token | Limiter impact JWT volé |
| 🟡 **MOYENNE** | Headers HTTP sécurité manquants | HSTS, CSP, X-Frame-Options | OWASP Top 10, anti-XSS |
| 🟡 **MOYENNE** | Logs insuffisants | Centraliser Monolog → Grafana Cloud | Détection incidents, forensics |

**Notes orateur :**
> "Ce plan de remédiation est directement issu de l'analyse des tests. La priorité absolue est phpMyAdmin en production — c'est une porte d'entrée directe sur la base de données. En parallèle, la gestion des secrets doit passer par un vault manager pour éviter toute fuite."

---

## SLIDE 12 — Perspectives V2 : Migration Kubernetes
**Temps : 1 min 30 s** | Compétences : **[CI] [EXP]**

**Contenu :**
**Pourquoi Kubernetes pour la V2 ?**

Le pic de charge Halloween/festival nécessite une **scalabilité horizontale automatique** que Docker Compose seul ne permet pas.

**Architecture V2 cible :**

```
Internet → Ingress Traefik/Nginx
              ↓
         [ Kubernetes Cluster ]
              ├── Deployment: Symfony PHP-FPM  ← HPA (auto-scaling CPU/RAM)
              ├── Deployment: Nginx             ← 2+ replicas
              ├── StatefulSet: MySQL            ← PVC volume persistant
              └── Service: phpMyAdmin           ← preprod namespace seulement

         Monitoring : Prometheus + Grafana (Helm charts)
         CI/CD : kubectl apply (replace Coolify webhook)
```

**Bénéfices K8s vs Docker Compose :**
| Critère | Docker Compose (V1) | Kubernetes (V2) |
|---------|---------------------|-----------------|
| Scalabilité | Manuelle | HPA automatique (CPU > 80%) |
| Disponibilité | Single host | Multi-node, auto-restart |
| Rolling updates | Downtime | 0 downtime |
| Rate limiting | À configurer manuellement | Ingress natif |
| Observabilité | Grafana Cloud externe | Prometheus stack intégrée (Helm) |

**Plan de migration :**
1. POC K3d en local (iso-production, Docker-in-Docker)
2. Validation Traefik Ingress + TLS Let's Encrypt
3. HPA testé sous charge (objectif : 0 timeout @ 500 VUs)
4. Remplacement Coolify webhook par `kubectl apply` dans le pipeline

**Notes orateur :**
> "La migration vers Kubernetes est la prochaine étape naturelle. K3d permet de valider l'architecture en local avant de passer en production. L'HPA auto-scaling résoudrait directement les problèmes de saturation identifiés lors de mes stress tests."

---

## SLIDE 13 — Sylius : E-commerce Modulaire (V3)
**Temps : 30 s** | Compétences : **[EXP]**

**Contenu :**
**Sylius** = framework e-commerce PHP/Symfony, modulaire et API-first

| Critère | Sylius | PrestaShop |
|---------|--------|-----------|
| Architecture | Découplée, API-first | Monolithique |
| Compatibilité Docker/K8s | Native | Difficile |
| Personnalisation | Via bundles Symfony | Thèmes/modules fragiles |
| RGPD / hébergement EU | Compatible | Dépend config |

**Fonctionnalités ciblées :** panier, commandes, abonnements fanzine, catalogue e-reader

**Notes orateur :**
> "En V3, Sylius remplacerait le catalogue custom actuel. Son architecture Symfony-native le rend compatible avec notre stack Docker/Kubernetes."

---

## SLIDE 14 — Bilan & Synthèse
**Temps : 1 min** | Compétences : **[ORAL]**

**Contenu :**
**Ce qui a été livré :**

| ✅ Réalisé | Indicateur |
|-----------|-----------|
| Pipeline CI/CD automatisé | 8 jobs GitHub Actions + Coolify |
| Qualité mesurée en continu | 4 métriques ISO 25010 |
| Sécurité by design | DevSecOps, JWT, SonarQube, HTTPS |
| Observabilité | Grafana Cloud + k6 |
| Prototype fonctionnel | API produits JWT-sécurisée |
| Tests automatisés | Unitaires + fonctionnels + charge |

**Roadmap :**
- **V2** → Kubernetes (HPA, 0 downtime, auto-scaling)
- **V2** → Rate limiting + Redis cache
- **V3** → Sylius e-commerce modulaire

**Citation de clôture :**
> *"La qualité n'est pas une option, c'est une contrainte intégrée à chaque étape du développement."*

**Notes orateur :**
> "Pour résumer : j'ai structuré un processus DevSecOps complet, de la mesure de qualité ISO 25010 au déploiement automatisé via Coolify, en passant par 4 types de tests et un plan de remédiation priorisé. La prochaine étape est Kubernetes pour absorber les pics de charge du festival. Je suis disponible pour vos questions."

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
| 8 | User Story | 1min30 |
| 9 | DÉMO LIVE | 4min |
| 10 | Résultats charge | 2min |
| 11 | Remédiation | 2min |
| 12 | Kubernetes V2 | 1min30 |
| 13 | Sylius V3 | 30s |
| 14 | Bilan | 1min |
| **TOTAL** | | **~20min** |

---

## ÉLÉMENTS VISUELS RECOMMANDÉS

1. **Slide 5** : Schéma pipeline en cascade (boîtes colorées par type : vert=tests, rouge=sécu, bleu=deploy)
2. **Slide 6** : Diagramme d'architecture avec flèches HTTPS/JWT
3. **Slide 10** : Capture d'écran dashboard Grafana Cloud (courbe de latence)
4. **Slide 12** : Diagramme K8s avec pods, Ingress, HPA

---

## CE QUI MANQUAIT DANS LA VERSION PRÉCÉDENTE (vs Thibault - note A)

| Élément | Thibault ✅ | Version précédente | Corrigé ici |
|---------|-----------|-------------------|-------------|
| Test de stress avec échec analysé | ✅ 2000 users → crash détaillé | ❌ Absent | ✅ Slide 10 |
| Plan remédiation R1/R2/R3 priorisé | ✅ CRITICAL/HIGH | Partiel | ✅ Slide 11 |
| Cartographie compétences équipe | ✅ Détaillée | Mentionné | ✅ Slide 2 |
| Kubernetes comme évolution | ✅ K3d en prod | ❌ Absent | ✅ Slide 12 |
| Comparatif choix techniques justifiés | ✅ Next.js vs PrestaShop | Partiel | ✅ Slides 8,13 |
| Difficultés sandbox documentées | ✅ 5 problèmes résolus | Mentionné | ✅ Slide 7 |
