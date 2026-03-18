# Structure de présentation PowerPoint — Bloc 3 Dev
## "POC La Petite Maison de l'Épouvante"
### Présentation individuelle — DijouxM

> Durée cible : 15–20 min + questions jury

---

## SLIDE 1 — Titre

**Titre :** POC "La Petite Maison de l'Épouvante"
**Sous-titre :** Catalogue produits avec recommandations — Architecture microservice-ready

- Nom, date, formation
- Logo entreprise fictive (tête de mort / épouvante)

**Notes :**
> "Bonjour, je vais vous présenter mon POC individuel réalisé dans le cadre du Bloc 3. L'objectif était de valider la faisabilité d'une plateforme e-commerce moderne pour La Petite Maison de l'Épouvante, une entreprise spécialisée dans l'univers horrifique qui souhaite digitaliser ses ventes."

---

## SLIDE 2 — Contexte métier

**Titre :** Un besoin métier concret

- Schéma : Fanzine → Magasins physiques → Plateforme numérique
- Problème : Site vitrine sans vente en ligne → perte d'opportunités
- Objectif POC : valider la brique "catalogue produits + recommandations"
- Périmètre e-commerce : figurines, jeux, Blu-ray, fanzine numérique

**Notes :**
> "La Petite Maison de l'Épouvante possède 3 magasins, édite un fanzine et organise un festival annuel. Le SI est fragmenté, sans vente en ligne. Mon POC cible la fonctionnalité clé : le catalogue avec recommandations intelligentes, socle de tout e-commerce."

---

## SLIDE 3 — Architecture technique du POC

**Titre :** Stack technique & découpage applicatif

- Schéma d'architecture : Nginx → PHP-FPM (Symfony) → MySQL
- Tableau des services Docker :

| Service | Image | Rôle |
|---|---|---|
| php | php:8.4-fpm-alpine | Application Symfony |
| nginx | nginx:alpine + SSL | Reverse proxy / HTTPS |
| mysql | mysql:8.0 | Persistence données |
| phpmyadmin | phpmyadmin | Interface BDD (dev) |
| sonarqube | sonarqube:community | Analyse qualité code |

- Points clés : conteneurisation complète, séparation des responsabilités, healthcheck MySQL

**Notes :**
> "L'architecture s'articule autour de 5 conteneurs orchestrés par Docker Compose. PHP-FPM gère le runtime Symfony, Nginx est le point d'entrée HTTPS. MySQL est découplé avec un healthcheck pour garantir le bon ordre de démarrage. SonarQube tourne en sidecar pour l'analyse continue."

---

## SLIDE 4 — Protocole d'expérimentation (bac à sable)

**Titre :** Technologies testées & retours d'expérience

- Tableau des technologies testées :

| Technologie | Objectif testé | Résultat | Difficulté |
|---|---|---|---|
| Symfony 7 + PHP 8.4 | Framework applicatif | Validé | Attributs PHP8 (routes) |
| Docker + Coolify | Hébergement managé | Validé | Migration WSL → Coolify |
| JWT (LexikJWT) | Sécurité API | Validé | Génération clés RSA |
| SonarQube | Qualité code | Validé | Config CI GitHub Actions |
| k6 Cloud | Tests de charge | Validé | Auth JWT dans load test |
| Playwright | Tests E2E | Validé | Env variables secrets |

**Notes :**
> "J'ai adopté une démarche itérative : tester chaque brique séparément avant intégration. La migration WSL vers Coolify a été le défi le plus structurant : elle m'a forcé à industrialiser complètement le déploiement via Docker Compose et variables d'environnement injectées par Coolify."

---

## SLIDE 5 — Fonctionnalité métier : Catalogue & Recommandations

**Titre :** La fonctionnalité cœur : recommandations intelligentes

- Schéma du flux de recommandation :
  ```
  Produit consulté → RecommendationService
    ├─ Règle 1 : même catégorie (max 8 candidats)
    ├─ Règle 2 : exclusion achats déjà faits (user connecté)
    ├─ Règle 3 : limite à 4 résultats
    └─ Règle 4 : fallback produits récents si < 4
  ```
- Cache Symfony (TTL 5 min), clé différenciée anonyme/connecté
- Répond au besoin MOA : "système de recommandation en fonction des recherches/achats"

**Notes :**
> "Le service de recommandation implémente 4 règles métier : pertinence par catégorie, personnalisation par historique d'achats, limitation à 4 résultats, fallback sur les produits récents. Le tout est mis en cache 5 minutes avec une clé différenciée selon que l'utilisateur est connecté ou non."

---

## SLIDE 6 — API REST & Sécurité JWT

**Titre :** Sécurisation de l'API — Bonne pratique n°1

- Schéma flux JWT :
  ```
  Client → POST /api/login_check → Token JWT (RSA 4096)
  Client → GET /api/products + Bearer Token → 200 OK
  Client → GET /api/products (sans token) → 401 Unauthorized
  ```
- Bonnes pratiques implémentées :
  1. **JWT asymétrique** (clés RSA, secret non partagé)
  2. **Secrets externalisés** (GitHub Secrets / Coolify env vars, jamais en dur)
  3. **HTTPS forcé** sur Nginx (TLS)
  4. **Principe de moindre privilège** : API protégée par défaut

**Notes :**
> "La sécurité est une contrainte forte pour une plateforme commerciale. J'ai implémenté JWT avec des clés RSA asymétriques — même si le secret est compromis côté client, les clés serveur restent sûres. Les credentials ne sont jamais commités : ils transitent par GitHub Secrets en CI et Coolify en prod."

---

## SLIDE 7 — Pipeline CI/CD

**Titre :** Livraison continue — Du commit au déploiement

- Schéma linéaire du pipeline :
  ```
  git push main
      ↓
  GitHub Actions
      ├─ Setup PHP 8.4 + extensions
      ├─ composer install
      ├─ Migrations BDD (MySQL service)
      ├─ PHPUnit tests --testdox
      └─ SonarQube scan (demo-sonarcube.dev.fabdevlab.fr)
      ↓
  Coolify (déploiement auto via webhook Git)
      ↓
  Application en production
  ```
- Conformité : tests obligatoires avant merge, qualité contrôlée automatiquement

**Notes :**
> "Le pipeline est déclenché à chaque push sur main. Les tests PHPUnit s'exécutent en premier — un test en échec bloque le pipeline. Ensuite SonarQube analyse la qualité du code. Coolify se charge du déploiement automatique via webhook Git : zéro intervention manuelle entre le commit et la mise en production."

---

## SLIDE 8 — Stratégie de tests

**Titre :** Processus de test — 3 niveaux de couverture

- Tableau des types de tests :

| Type | Outil | Périmètre | Statut |
|---|---|---|---|
| **Unitaires** | PHPUnit | RecommendationService (6 cas) | PASS |
| **Fonctionnels/Intégration** | PHPUnit WebTestCase | API JWT (401/200/token) | PASS |
| **E2E** | Playwright | Login + Catalogue + Page d'accueil | PASS |
| **Performance** | k6 Cloud | Homepage + API (100 VU, p95<200ms) | PASS |

- Exécution CI : PHPUnit automatisé à chaque push
- Exécution manuelle : k6 depuis Grafana Cloud (Paris)

**Notes :**
> "J'ai mis en place 4 types de tests. Les tests unitaires couvrent les 6 règles du RecommendationService par mocks, sans BDD. Les tests fonctionnels valident le flux JWT complet : 401 sans token, login, 200 avec token. Playwright automatise les parcours utilisateur. k6 monte en charge jusqu'à 100 utilisateurs virtuels avec un seuil p95 sous 200ms."

---

## SLIDE 9 — Indicateurs Qualité (SonarQube)

**Titre :** 4 indicateurs qualité logicielle

| Indicateur | Outil | Mesure | Seuil |
|---|---|---|---|
| **Fonctionnalité** | PHPUnit | Taux de succès des tests | 100% |
| **Performance** | k6 | Temps réponse p95 | < 200ms |
| **Maintenabilité** | SonarQube | Code smells / dette technique | 0 critical |
| **Fiabilité** | SonarQube | Bugs détectés | 0 bug |
| **Sécurité** | SonarQube | Vulnérabilités OWASP | 0 critical |
| **Couverture** | PHPUnit/SonarQube | Coverage code | > 80% cible |

- Axes d'amélioration identifiés par SonarQube → plan de remédiation

**Notes :**
> "SonarQube analyse en continu 4 axes : fonctionnalité via le taux de succès des tests, performance via k6, maintenabilité et fiabilité via l'analyse statique du code. Les résultats sont visibles sur le serveur SonarQube hébergé sur Coolify. Chaque build génère un rapport qui alimente le plan de remédiation."

---

## SLIDE 10 — Environnement managé & Montée en charge

**Titre :** Infrastructure managée — Disponibilité démontrée

- Schéma Coolify :
  ```
  GitHub repo
      ↓ (webhook)
  Coolify (VPS cloud français)
      ├─ Docker Compose orchestration
      ├─ Gestion SSL automatique (Let's Encrypt)
      ├─ Reverse proxy intégré
      └─ Restart policies (unless-stopped)
  ```
- Démonstration montée en charge k6 :
  - Phase 1 : montée progressive 0→10 VU (30s)
  - Phase 2 : charge soutenue 50 VU (1min)
  - Phase 3 : pic 100 VU (30s)
  - Phase 4 : descente 0 VU (30s)
- Résultats : p95 < 200ms maintenu, taux d'erreur < 1%

**Notes :**
> "Coolify remplace une gestion manuelle de serveur : SSL automatique, redémarrage auto des conteneurs, déploiement via webhook. Pour la montée en charge, k6 Cloud simule 100 utilisateurs simultanés depuis Paris. Le seuil p95 sous 200ms et le taux d'erreur sous 1% valident la robustesse de l'application."

---

## SLIDE 11 — Plan de remédiation sécurité

**Titre :** Sécurité — Risques identifiés & remédiation

- Bonnes pratiques déjà implémentées :
  1. JWT asymétrique (RSA)
  2. Secrets externalisés (never hardcoded)
  3. HTTPS forcé
  4. Validation des formulaires (Symfony Forms + Constraints)

- Risques résiduels & plan de remédiation :

| Risque | Criticité | Action |
|---|---|---|
| Injection SQL | Critique | Déjà mitigé via Doctrine ORM (requêtes préparées) |
| XSS | Haute | Twig escaping automatique — audit templates |
| Brute force login | Haute | Ajouter rate limiting (Symfony RateLimiter) |
| Tokens JWT sans expiration courte | Moyenne | Réduire TTL + implémenter refresh token |
| phpMyAdmin exposé | Haute | Désactiver en production (service dev only) |

**Notes :**
> "L'analyse SonarQube et la revue manuelle ont identifié ces risques. Doctrine ORM élimine les injections SQL par requêtes préparées. Twig échappe automatiquement les sorties HTML. Les actions prioritaires pour la v2 sont le rate limiting sur l'authentification et la désactivation de phpMyAdmin en production."

---

## SLIDE 12 — Compétences & Plan de formation

**Titre :** Montée en compétences de l'équipe

- Compétences présentes dans l'équipe :
  - PHP / Symfony, Docker, Git/CI-CD, MySQL, Sécurité JWT

- Expertises à acquérir :
  - **Sylius** (e-commerce Symfony) → formation prioritaire
  - **Kubernetes** (orchestration à l'échelle)
  - **Redis** (cache distribué haute dispo)
  - **RGAA** (accessibilité réglementaire)

- Action de formation proposée :
  > Formation Sylius certifiante (2 jours) → permet de passer du POC catalogue à une plateforme e-commerce complète avec panier, paiement, gestion commandes

**Notes :**
> "L'équipe maîtrise la stack PHP/Symfony. L'expertise manquante la plus critique est Sylius, le framework e-commerce Symfony natif prévu pour la v2. Une formation de 2 jours permettrait d'accélérer significativement le développement de la plateforme complète avec panier, paiement et gestion des commandes."

---

## SLIDE 13 — Vision future & Évolution vers Sylius / Kubernetes

**Titre :** Roadmap — Du POC à la plateforme complète

```
POC actuel (v1)              v2 (Sylius)              v3
─────────────────────────────────────────────────────────
Catalogue produits     →  Panier + Paiement      →  Streaming Evil Ed
Recommandations        →  Système de commandes   →  Festival en ligne
API JWT                →  Espace communautaire   →  Système enchères
Auth utilisateur       →  Fanzine numérique      →  K8s (orchestration)
Docker Compose         →  Notifications          →  ...
+ Coolify (gestion)
```

**Évolution infra : Docker Compose → Kubernetes**

| Étape | Infra | Quand |
|---|---|---|
| v1 (POC) | Docker Compose + Coolify | Maintenant — simple, adapté POC |
| v2 | Docker Compose + Coolify (stable) | Sylius + montée en charge |
| v3 | Kubernetes + Coolify (interface K8s) | Si charge justifie la complexité |

- **Coolify + Kubernetes** : Coolify v4 permet de **connecter un cluster K8s existant** et de le piloter depuis la même interface — la migration sera progressive et non-disruptive
- Kubernetes apporte : auto-scaling horizontal, rolling deployments zéro-downtime, résilience multi-nœuds
- Sylius : framework e-commerce basé sur Symfony — migration naturelle depuis le POC

**Notes :**
> "L'architecture du POC a été pensée pour évoluer. En v2, on reste sur Docker Compose avec Coolify — c'est suffisant pour Sylius et la montée en charge initiale. En v3, si le trafic l'exige, on migre vers Kubernetes. Coolify v4 supporte nativement la connexion à un cluster K8s — on garde la même interface de gestion, on gagne l'orchestration K8s : auto-scaling, rolling deployments, haute disponibilité multi-nœuds."

---

## SLIDE 14 — Conclusion & Appel à la démonstration

**Titre :** Synthèse & Démonstration live

- Récapitulatif des critères validés (grille d'évaluation) :
  - Protocole bac à sable : Validé
  - POC technique fonctionnel : Validé
  - Fonctionnalité métier : Validé
  - CI/CD : Validé
  - Environnement managé : Validé
  - 4 indicateurs qualité : Validé
  - 2+ types de tests : Validé (4 types)
  - 2+ bonnes pratiques sécurité : Validé (4 BP)

**Notes :**
> "Pour conclure, le POC valide tous les critères de la grille d'évaluation. Je vais maintenant vous faire une démonstration live de l'application déployée sur Coolify, puis de l'exécution du pipeline CI/CD."

---

## Notes générales de présentation

- **Durée recommandée par slide** : ~1 à 1min30
- **Transition recommandée** : progressive, sobre
- **Palette** : noir, rouge sang, gris sombre (univers épouvante)
- **Police** : lisible à distance (min 24pt corps de texte, 36pt+ titres)
- **Schémas** : préférer les diagrammes simples (flèches → boîtes) aux captures d'écran
- **Démonstration** : prévoir un onglet navigateur ouvert sur l'URL Coolify ET le dashboard SonarQube
