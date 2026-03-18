# Structure de présentation PowerPoint — Bloc 3 Dev
## "POC La Petite Maison de l'Épouvante"
### Présentation individuelle — DijouxM

> Durée cible : 15–20 min + questions jury

---

## SLIDE 1 — Titre

**Titre :** POC "La Petite Maison de l'Épouvante"
**Sous-titre :** Catalogue produits · DevSecOps · Architecture Cloud-Native

- Nom, rôle (Lead Développeur), promotion MAALSI 25-26, date
- Logo / visuel univers épouvante

**Notes :**
> "Bonjour, je vais vous présenter mon POC individuel du Bloc 3. L'objectif était de valider la faisabilité d'une plateforme e-commerce pour La Petite Maison de l'Épouvante, en adoptant une approche DevSecOps : sécurité intégrée dès le début du cycle de développement."

---

## SLIDE 2 — Contexte métier

**Titre :** L'enjeu : digitaliser "La Petite Maison de l'Épouvante"

- Schéma : Fanzine horreur → 4 magasins physiques → Festival annuel → Collectif Evil Ed
- Problème : site vitrine CMS sans vente en ligne → perte d'opportunités commerciales
- SI existant : hétérogène, CSV manuels, pas de pipeline, pas de processus qualité
- Équipe IT : Lead Dev + 2 développeurs juniors (alternance 5 ans exp.)
- Objectif v1 : plateforme e-commerce sécurisée + espace communautaire

**Notes :**
> "La Petite Maison de l'Épouvante a 3 magasins, édite un fanzine, organise un festival annuel et produit des contenus via Evil Ed. Le SI est fragmenté — pas de vente en ligne, échanges CSV manuels. Mon rôle de Lead Dev est de piloter la transformation numérique avec une approche DevSecOps."

---

## SLIDE 3 — Approche DevSecOps

**Titre :** Approche DevSecOps — Sécurité intégrée dès le début

- Schéma du cycle DevSecOps :
  ```
  Code → Build → Test → Secure → Deploy → Monitor
    │       │       │       │         │        │
   Git   Composer PHPUnit SonarQube Coolify  Grafana
  ```
- Trois piliers :
  1. **Dev** : Symfony 7, PHP 8.4, Docker
  2. **Sec** : SonarQube, JWT/Sessions, HTTPS, secrets externalisés
  3. **Ops** : GitHub Actions 8 étapes, Coolify, k6 + Grafana Cloud

**Notes :**
> "J'ai structuré mon approche autour de DevSecOps : la sécurité n'est pas une étape finale, elle est intégrée à chaque phase. SonarQube analyse le code dès l'étape 2 du pipeline, avant même que les tests fonctionnels s'exécutent."

---

## SLIDE 4 — Modèle qualité ISO 25010

**Titre :** Qualité logicielle — 4 métriques ISO 25010

| Attribut qualité | Métrique | Outil | Prévention dette technique |
|---|---|---|---|
| **Fiabilité** | Couverture de tests (%) | PHPUnit | Zones non testées → bugs futurs silencieux |
| **Maintenabilité** | Code smells / dette | SonarQube | Complexité excessive → coûts maintenance |
| **Sécurité** | Vulnérabilités détectées | SonarQube Scan | Failles non corrigées → incidents prod |
| **Efficacité performance** | Temps réponse API (p95) | k6 + Grafana Cloud | Dégradation UX → abandon utilisateur |

- Critère de sélection : toutes mesurables **automatiquement dans le pipeline CI/CD**
- Référence : **ISO 25010** — norme internationale qualité logicielle (8 familles d'attributs)

**Notes :**
> "J'ai sélectionné ces 4 attributs ISO 25010 pour un critère clé : ils sont tous mesurables automatiquement dans le pipeline, sans intervention manuelle. SonarQube couvre fiabilité et maintenabilité, k6 + Grafana la performance, PHPUnit la couverture fonctionnelle."

---

## SLIDE 5 — Architecture technique du POC

**Titre :** Stack technique & découpage applicatif

- Schéma d'architecture : Nginx → PHP-FPM (Symfony) → MySQL
- Tableau des services Docker :

| Service | Image | Rôle |
|---|---|---|
| php | php:8.4-fpm-alpine | Application Symfony |
| nginx | nginx:alpine + SSL | Reverse proxy HTTPS |
| mysql | mysql:8.0 | Persistance données |
| phpmyadmin | phpmyadmin | Interface BDD (préprod) |
| sonarqube | sonarqube:community | Analyse qualité code |

- Hébergement : **Coolify** sur VPS fabdevlab.fr — SSL Let's Encrypt automatique
- URL préprod : https://demo-nginx.dev.fabdevlab.fr

**Notes :**
> "5 services orchestrés par Docker Compose, déployés sur Coolify. Nginx est le point d'entrée HTTPS, PHP-FPM gère le runtime Symfony, MySQL est isolé avec healthcheck pour garantir le bon ordre de démarrage. SonarQube tourne en sidecar pour l'analyse continue."

---

## SLIDE 6 — Protocole d'expérimentation (bac à sable)

**Titre :** Technologies testées & retours d'expérience

| Technologie | Objectif testé | Résultat | Difficulté rencontrée |
|---|---|---|---|
| Symfony 7 + PHP 8.4 | Framework applicatif | Validé | Attributs PHP 8 (routes) |
| Docker + Coolify | Hébergement managé | Validé | Migration WSL → Coolify |
| SonarQube auto-hébergé | Qualité code en CI | Validé | Config SONAR_TOKEN secrets |
| k6 Cloud + Grafana | Tests de charge | Validé | Auth session CSRF dans load test |
| Playwright | Tests E2E | Validé | Env variables secrets CI |
| GitHub Actions 8 étapes | Pipeline complet | Validé | Séquencement des jobs (needs:) |

**Notes :**
> "J'ai adopté une démarche itérative. La difficulté la plus structurante : gérer l'authentification CSRF dans k6 — le load test doit d'abord récupérer la page login, extraire le token CSRF, puis soumettre le formulaire. C'est plus réaliste qu'un simple POST JSON mais plus complexe à implémenter."

---

## SLIDE 7 — Fonctionnalité métier : Catalogue & Recommandations

**Titre :** User Story — API Produits sécurisée

- User Story :
  > "En tant qu'utilisateur authentifié, je veux accéder à la liste des produits disponibles sur la plateforme afin de parcourir le catalogue."

- Critères d'acceptation :
  - `GET /api/products` → 200 OK + liste JSON (session valide)
  - `GET /api/products` sans auth → redirection login
  - Données persistées MySQL via Doctrine ORM
  - Système de recommandations par catégorie (cache 5 min)

- Choix techniques : Symfony 7, Doctrine, MySQL, Sessions, EasyAdmin (back-office)
- V2 prévue : recommandations par filtrage collaboratif (historique achats)

**Notes :**
> "La fonctionnalité répond directement au besoin MOA : système de recommandation produits. Version actuelle : recommandations par catégorie avec cache Symfony 5 minutes. La v2 introduira le filtrage collaboratif basé sur l'historique d'achats quand la table Order sera implémentée."

---

## SLIDE 8 — Pipeline CI/CD — 8 étapes

**Titre :** Livraison continue — Du commit au déploiement

```
git push déploiement-fonctionnelle
        ↓
  1 Build          → PHP 8.4, composer, cache vendor
        ↓
  2 Security Scan  → SonarQube (demo-sonarcube.dev.fabdevlab.fr)
        ↓
  3 Unit Tests     → PHPUnit --testsuite=unit
        ↓
  4 Functional Tests → PHPUnit --testsuite=functional + coverage Xdebug
        ↓
  5 Non-Régressions Pré-Deploy → curl HTTP 200/302 sur préprod
        ↓
  6 Deploy         → webhook Coolify → redéploiement préprod
        ↓
  7 Non-Régressions Post-Deploy → vérification préprod après déploiement
        ↓
  8 Load Tests     → k6 cloud → Grafana Cloud (100 VU, Paris)
```

**Notes :**
> "Le pipeline séquence 8 jobs avec des dépendances strictes — chaque étape bloque la suivante en cas d'échec. La sécurité est en étape 2, avant les tests — un code avec des vulnérabilités critiques ne va pas plus loin. Les tests de non-régression vérifient la préprod AVANT et APRÈS le déploiement."

---

## SLIDE 9 — Stratégie de tests

**Titre :** 4 types de tests — Couverture complète

| Type | Outil | Périmètre | Statut CI |
|---|---|---|---|
| **Unitaires** | PHPUnit (suite=unit) | RecommendationService | PASS |
| **Fonctionnels** | PHPUnit WebTestCase (suite=functional) | API Catalogue + Auth | PASS + coverage |
| **E2E** | Playwright | Login, Catalogue, Homepage | PASS |
| **Performance** | k6 Cloud → Grafana Cloud | Homepage + Catalogue (100 VU) | PASS |

- Coverage : rapport XML Xdebug → envoyé à SonarQube
- Seuils k6 : `p95 < 200ms`, `http_req_failed < 1%`
- Zone de charge : `amazon:fr:paris` (proche des utilisateurs cibles)

**Notes :**
> "4 types de tests couvrent l'ensemble du spectre qualité. Les tests unitaires valident le RecommendationService en isolation. Les fonctionnels testent l'API avec une vraie BDD de test. Playwright automatise les parcours utilisateur. k6 monte à 100 utilisateurs virtuels depuis Paris avec un seuil p95 sous 200ms."

---

## SLIDE 10 — Environnement managé & Observabilité

**Titre :** Infrastructure managée — Disponibilité & Montée en charge

- Coolify — PaaS self-hosted (VPS cloud français — fabdevlab.fr) :
  - SSL Let's Encrypt automatique (renouvellement tous les 90 jours)
  - Déploiement via webhook Git (zéro intervention manuelle)
  - `restart: unless-stopped` sur tous les services
  - Préprod : https://demo-nginx.dev.fabdevlab.fr

- k6 Cloud + Grafana — Observabilité :
  ```
  Phase 1 : 0 → 10 VU  (30s)   warm-up
  Phase 2 : 10 → 50 VU (1min)  charge normale
  Phase 3 : 50 → 100 VU (30s)  pic de charge
  Phase 4 : 100 → 0 VU  (30s)  cooldown
  ```
  - Résultats visibles en temps réel dans Grafana Cloud
  - Métriques : latence, débit, taux d'erreur, saturation

**Notes :**
> "Coolify gère le cycle de vie de l'infrastructure : SSL automatique, redémarrage des conteneurs, déploiement webhook. Pour la montée en charge, k6 Cloud permet de lancer les tests depuis la région Paris et de visualiser les résultats dans Grafana en temps réel — latence, débit, taux d'erreur."

---

## SLIDE 11 — Plan de remédiation sécurité

**Titre :** Sécurité — Analyse des vulnérabilités & plan d'action

| Priorité | Vulnérabilité identifiée | Action recommandée | Justification |
|---|---|---|---|
| CRITIQUE | phpMyAdmin exposé en production | Désactiver en prod · restreindre IP en préprod | Accès direct BDD sans auth forte |
| CRITIQUE | Secrets dans .env non chiffrés | Docker Secrets ou Vault | Fuite credentials si repo compromis |
| HAUTE | Absence de rate limiting | ThrottleBundle Symfony / API Gateway | Protection brute force / DDoS |
| HAUTE | TTL token JWT trop long | Réduire TTL + refresh token | Limite l'impact d'un token volé |
| MOYENNE | Headers HTTP sécurité manquants | HSTS, CSP, X-Frame-Options (NelmioSecurityBundle) | Conformité OWASP Top 10, anti-XSS |
| MOYENNE | Logs insuffisants / non centralisés | Centraliser Monolog → Grafana Cloud | Détection incidents, forensique |

- Bonnes pratiques déjà implémentées : sessions Symfony sécurisées, HTTPS, secrets externalisés (GitHub Secrets + Coolify), SonarQube scan en CI

**Notes :**
> "L'analyse SonarQube et la revue manuelle ont produit ce plan de remédiation priorisé. Les deux actions critiques sont phpMyAdmin (à désactiver en prod) et la centralisation des logs vers Grafana pour la traçabilité. Le rate limiting est prioritaire pour une plateforme commerciale exposée à internet."

---

## SLIDE 12 — Compétences & Plan de formation

**Titre :** Compétences de l'équipe & expertises à acquérir

- Compétences présentes :

| Compétence | Niveau | Preuve |
|---|---|---|
| PHP / Symfony 7 | Confirmé | Controllers, Services, Entities |
| Docker / Coolify | Confirmé | 5 services, pipeline déploiement |
| GitHub Actions | Confirmé | Pipeline 8 étapes |
| PHPUnit / Playwright / k6 | Intermédiaire | 4 types de tests |
| SonarQube | Débutant → Intermédiaire | Intégration CI + Quality Gate |

- Expertises à acquérir : **Sylius** (e-commerce v2), **Kubernetes** (scalabilité v3), Stripe/PSP, RGAA

- Action de formation proposée :
  > **Formation Sylius certifiante (2 jours)** → maîtriser le framework e-commerce Symfony pour la v2 (catalogue, panier, paiement, commandes)

**Notes :**
> "L'expertise la plus critique à acquérir est Sylius, prévu pour la v2. En v3, si la charge le justifie, on migre vers Kubernetes — Coolify v4 supporte nativement la connexion à un cluster K8s, donc la transition sera progressive sans changer d'outillage."

---

## SLIDE 13 — Roadmap : Du POC à la plateforme complète

**Titre :** Vision future — v1 → v2 → v3

```
v1 POC (maintenant)          v2 (Sylius)              v3 (scalabilité)
──────────────────────────────────────────────────────────────────────
Catalogue produits       →  Panier + Paiement      →  Streaming Evil Ed
Recommandations          →  Système de commandes   →  Festival en ligne
Auth Symfony sessions    →  Espace communautaire   →  Système enchères
Docker Compose + Coolify →  Fanzine numérique      →  Kubernetes via Coolify
GitHub Actions 8 étapes  →  Notifications          →  Multi-région
SonarQube + k6 Cloud     →  Stripe/PSP             →  ...
```

- **Coolify + Kubernetes (v3)** : Coolify v4 permet de connecter un cluster K8s existant — même interface, orchestration K8s : auto-scaling, rolling deployments, résilience multi-nœuds

**Notes :**
> "La migration vers Sylius en v2 sera naturelle — même écosystème Symfony. En v3, si le trafic le justifie, on connecte un cluster Kubernetes à Coolify sans changer d'outillage. On monte en puissance progressivement plutôt que de sur-ingénier dès le départ."

---

## SLIDE 14 — Conclusion & Démonstration

**Titre :** Synthèse — Critères validés

| Critère grille | Statut |
|---|---|
| Protocole d'expérimentation bac à sable | Validé |
| POC technique (découpage, interservices, sécurité, hébergement) | Validé |
| Fonctionnalité métier (User Story + critères d'acceptation) | Validé |
| Processus CI/CD schématisé et intégré | Validé (8 étapes) |
| Compétences recensées + action de formation | Validé |
| Environnement managé + montée en charge démontrée | Validé (Coolify + k6) |
| 4 indicateurs qualité ISO 25010 | Validé |
| 2+ types de tests appliqués avec succès | Validé (4 types) |
| 2+ bonnes pratiques sécurité | Validé |
| Plan de remédiation priorisé | Validé |

**Notes :**
> "Le POC valide tous les critères de la grille d'évaluation avec une approche DevSecOps complète. Je vous propose maintenant une démonstration live."

---

## Notes générales de présentation

- **Durée recommandée** : ~1 min par slide, 14 slides = ~14 min, 6 min questions
- **Palette** : noir, rouge sang, gris sombre (univers épouvante) — cohérent avec le logo
- **Police** : min 24pt corps, 36pt+ titres
- **Schémas** : diagrammes simples (flèches → boîtes), pas de captures d'écran floues
- **Onglets pré-ouverts** : préprod Coolify + GitHub Actions + SonarQube + Grafana Cloud
