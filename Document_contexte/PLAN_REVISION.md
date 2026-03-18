# Plan de Révision — Fond de la Présentation BLOC 3
## Mickaël Dijoux — Approfondissement technique pour le jury

---

## OBJECTIF

Ce document est un guide de révision pour maîtriser les concepts techniques abordés dans la présentation. Le jury est spécialiste — la précision du vocabulaire et la capacité à expliquer les choix sont évaluées.

---

## 1. ISO 25010 — Qualité Logicielle

### À savoir par cœur
- ISO 25010 = norme internationale de qualité produit logiciel (remplace ISO 9126)
- 8 caractéristiques principales : **Functional Suitability, Performance Efficiency, Compatibility, Usability, Reliability, Security, Maintainability, Portability**
- On en a sélectionné 4 : Fiabilité, Maintenabilité, Sécurité, Performance

### Pour chaque indicateur, savoir expliquer :
1. **IND-01 Fiabilité (PHPUnit coverage)** : pourquoi la couverture de tests prévient la dette technique ? → Zone non testée = comportement inconnu en production → regression silencieuse
2. **IND-02 Maintenabilité (SonarQube code smells)** : qu'est-ce qu'un code smell ? → duplication, méthodes trop longues, couplage fort, nommage ambigu
3. **IND-03 Sécurité (SonarQube SAST)** : différence SAST vs DAST ? → SAST = analyse statique du code source (sans exécution) / DAST = analyse dynamique sur app en cours d'exécution
4. **IND-04 Performance (k6 + Grafana)** : que mesure k6 ? → `http_req_duration` (latence), `http_reqs` (throughput), `http_req_failed` (taux erreur), `vus` (virtual users)

### Question jury probable
> "Comment tu justifies le seuil de 200ms ?"
→ Référence UX : au-delà de 200ms, l'utilisateur perçoit un délai. Standard industry (Google RAIL model). Sur une API catalogue, le SLA cible est < 100ms p95.

---

## 2. DevSecOps — Concepts fondamentaux

### Définition à maîtriser
DevSecOps = intégration de la sécurité dans chaque phase du cycle DevOps, plutôt qu'en fin de développement ("Security by Design" vs "Security by Afterthought").

### Les 3 piliers à connaître
1. **Shift Left Security** : détecter les failles le plus tôt possible (coût de correction x100 entre dev et prod)
2. **Infrastructure as Code** : docker-compose.yml, Dockerfile, ci.yml → tout est versionné, reproductible, auditable
3. **Automated Gates** : chaque étape du pipeline est un contrôle bloquant (fail-fast)

### Pratiques DevSecOps dans le projet
| Stage | Pratique | Outil |
|-------|---------|-------|
| Code | Pas de secrets en clair | `.gitignore` du `.env`, GitHub Secrets |
| Commit | Analyse statique | SonarQube SAST |
| Build | Image minimale | Alpine Linux (surface d'attaque réduite) |
| Test | JWT testé (401/200) | PHPUnit WebTestCase |
| Deploy | HTTPS obligatoire | TLS Nginx, webhook token |
| Runtime | Logs centralisés | Monolog → Grafana Cloud |

### Question jury probable
> "Quelle est la différence entre SonarQube et OWASP ZAP ?"
→ SonarQube = SAST (analyse le code sans l'exécuter, avant déploiement). OWASP ZAP = DAST (attaque l'application déployée depuis l'extérieur). Les deux sont complémentaires. Dans ma V2, j'ajouterais OWASP ZAP en post-deploy.

---

## 3. Pipeline CI/CD — Maîtrise du fichier `ci.yml`

### Structure à expliquer
- **Déclencheur** : push/PR sur `déploiement-fonctionnelle` (branche de production)
- **8 jobs séquentiels** via `needs:` → chaque job attend le précédent
- **Cache vendor** : `actions/cache@v4` sur `composer.lock` → évite de télécharger les dépendances à chaque run

### Jobs à savoir expliquer en détail
1. **build** : setup PHP 8.4, composer install, clear cache test → prépare l'environnement
2. **security-scan** : SonarQube via `sonarsource/sonarqube-scan-action` → analyse `src/` et `tests/`
3. **test-unit** : PHPUnit suite `unit` → teste les services et entités en isolation
4. **test-functional** : PHPUnit suite `functional` avec MySQL service GitHub Actions → teste les routes HTTP réelles avec BDD
5. **non-regression-pre** : `curl` sur la preprod actuelle → vérif baseline avant déploiement
6. **deploy** : webhook Coolify → déclenche le redéploiement Docker
7. **non-regression-post** : même vérifications après 30s → garantit que le déploiement n'a pas cassé la prod
8. **load-test** : k6 cloud avec credentials depuis secrets → envoie les métriques à Grafana Cloud

### Question jury probable
> "Pourquoi une non-régression pré ET post-déploiement ?"
→ Pré = s'assurer que la preprod est dans un état stable AVANT de la modifier (baseline). Post = vérifier que le nouveau déploiement n'a pas introduit de régression. Sans la pré, on ne sait pas si un échec post est dû au déploiement ou à un état antérieur dégradé.

---

## 4. JWT (JSON Web Token) — Fonctionnement

### Structure d'un JWT
```
Header.Payload.Signature
eyJhbGciOiJSUzI1NiJ9 . eyJ1c2VybmFtZSI6InVzZXIifQ . [signature RSA]
```
- **Header** : algorithme (`RS256` = RSA asymétrique)
- **Payload** : claims (username, expiration `exp`, issued at `iat`)
- **Signature** : chiffrée avec la clé privée → vérifiée avec la clé publique

### Lexik JWT Bundle (Symfony)
- Génère le token à `POST /api/login` via `JWTTokenManagerInterface`
- Vérifie le token sur chaque requête sécurisée via `security.yaml` (firewall `api`)
- TTL configuré dans `config/packages/lexik_jwt_authentication.yaml`

### Pourquoi RSA (asymétrique) plutôt que HMAC (symétrique) ?
- RSA : clé privée signe, clé publique vérifie → un service peut vérifier sans avoir la clé secrète
- HMAC : même clé pour signer et vérifier → si partagée avec un service externe, risque de compromission

### Question jury probable
> "Comment gères-tu l'expiration du token ?"
→ TTL actuellement configuré à [valeur dans lexik_jwt.yaml]. En V2 : TTL court (15 min) + refresh token endpoint → limite l'impact d'un token volé.

---

## 5. Docker & Conteneurisation

### Architecture Docker Compose du projet
```
php (php:8.4-fpm-alpine)
    ↕ réseau Docker interne
nginx (nginx:alpine + SSL)     ← expose 80/443 vers l'extérieur
    ↕ réseau Docker interne
mysql (mysql:8.0)              ← healthcheck avant démarrage php
phpmyadmin                     ← preprod uniquement
sonarqube (community)          ← local / CI uniquement
```

### Points techniques à maîtriser
1. **`depends_on: service_healthy`** : php ne démarre que quand MySQL répond au `mysqladmin ping` → évite les erreurs de connexion au démarrage
2. **PHP-FPM** : FastCGI Process Manager → nginx délègue les requêtes PHP à php-fpm via socket/TCP → découplage web server / PHP engine
3. **Alpine Linux** : image minimale (~5MB vs ~100MB Debian) → moins de paquets = moins de CVE potentiels
4. **Multi-stage build** : non utilisé actuellement → amélioration V2 pour séparer build (with composer) et runtime (sans composer)

### Question jury probable
> "Pourquoi ne pas mettre SonarQube dans le pipeline plutôt qu'en conteneur ?"
→ SonarQube dans le docker-compose sert au développement local. Dans le pipeline, on utilise `sonarqube-scan-action` qui se connecte au SonarQube déployé sur `demo-sonarcube.dev.fabdevlab.fr` (instance hébergée). Les deux sont complémentaires.

---

## 6. Coolify — Plateforme de déploiement

### Qu'est-ce que Coolify ?
- PaaS self-hosted (alternative à Heroku, Vercel, Railway)
- Déploiement automatique via webhooks depuis GitHub/GitLab
- Gestion des conteneurs Docker, des certificats TLS, des variables d'environnement
- Dashboard de monitoring des conteneurs

### Avantages vs déploiement manuel
| Manuel | Coolify |
|--------|---------|
| `ssh` → `docker compose down && docker compose up` | Webhook HTTP déclenché automatiquement |
| Certificats SSL manuels (certbot) | TLS automatique Let's Encrypt |
| Variables d'env en `.env` sur le serveur | Interface sécurisée de gestion des secrets |
| Monitoring via `docker ps` | Dashboard web avec logs et état |

### Pourquoi Coolify plutôt que Kubernetes en V1 ?
→ Coolify est adapté à un prototype/POC : déploiement rapide, configuration simple, pas de cluster à gérer. K8s est prévu en V2 pour le scaling et la haute disponibilité nécessaires en production.

---

## 7. Tests — Types et Outils

### Tableau de synthèse
| Type | Outil | Ce qu'il teste | Métrique ISO 25010 |
|------|-------|---------------|-------------------|
| **Unitaires** | PHPUnit | Services, entités en isolation (mock des dépendances) | Fiabilité |
| **Fonctionnels** | PHPUnit WebTestCase | Routes HTTP, codes retour, authentification JWT | Fiabilité + Sécurité |
| **Non-régression** | curl (GitHub Actions) | Comportement baseline pre/post-déploiement | Fiabilité |
| **Charge** | k6 | Latence, throughput, taux d'erreur sous charge | Performance |

### PHPUnit WebTestCase
- Simule des requêtes HTTP dans Symfony sans démarrer un vrai serveur
- Accède à la BDD de test (MySQL service dans GitHub Actions)
- Exemple : `$this->client->request('GET', '/api/products')` → vérifie `$this->assertResponseStatusCodeSame(401)`

### k6 — Script de charge
- Scénario dans `k6/load-test.js` : login → récupère token → GET /api/products
- Paramètres : Virtual Users (VUs), duration, rampe (stages)
- Résultats envoyés à Grafana Cloud via `k6 cloud run`

---

## 8. Kubernetes — Concepts pour la V2

### Pourquoi K8s est nécessaire pour la scalabilité ?
Docker Compose = orchestration single-host. Kubernetes = orchestration multi-node avec :
- **HPA (Horizontal Pod Autoscaler)** : scale automatiquement les replicas en fonction du CPU/RAM
- **Rolling Updates** : déploiement sans downtime (nouveaux pods avant suppression des anciens)
- **Self-healing** : redémarre automatiquement les pods crashés
- **Ingress** : routing HTTP/HTTPS centralisé (remplace la config nginx manuelle)

### Architecture K8s pour le projet
```
Ingress (Nginx/Traefik)
  ├── /         → Service symfony-svc → Deployment symfony (3 replicas, HPA)
  └── /phpmyadmin → Service phpmyadmin-svc (namespace preprod seulement)

StatefulSet mysql → PVC (persistance des données indépendante des pods)

HPA: symfony
  minReplicas: 2
  maxReplicas: 10
  targetCPUUtilizationPercentage: 80
```

### K3d pour le POC local
- K3d = K3s (K8s léger) dans Docker → permet de simuler un cluster K8s en local
- Commande : `k3d cluster create mycluster --agents 2`
- Iso-production : même configuration que le cluster de production

### Question jury probable
> "Qu'est-ce que l'HPA et comment ça aurait aidé dans ton stress test ?"
→ HPA = Horizontal Pod Autoscaler. Il surveille les métriques CPU/RAM d'un Deployment et crée automatiquement des replicas supplémentaires quand un seuil est dépassé. Lors de mon stress test, le CPU PHP-FPM a saturé à 100%. Avec HPA configuré à 80% CPU, Kubernetes aurait créé 2, 3, puis N replicas pour absorber la charge.

---

## 9. Plan de Remédiation — Argumentaire

### Pour chaque vulnérabilité, savoir justifier la priorité

**🔴 CRITIQUE — phpMyAdmin exposé en prod**
- Risque : accès direct à la BDD sans authentification forte → lecture/modification/suppression des données
- Action : `PHPMYADMIN_ALLOW_ANY_ROOT=false`, restriction IP via Nginx, désactivation totale en prod
- Standard : CIS Benchmark Docker / OWASP API Security Top 10

**🔴 CRITIQUE — Secrets `.env` non chiffrés**
- Risque : si le dépôt est compromis (ou un collaborateur malveillant), les credentials DB et JWT privkeys sont exposés
- Action : Docker Secrets (swarm) ou HashiCorp Vault
- Amélioration immédiate : vérifier que `.env` est dans `.gitignore` + utiliser les GitHub Secrets pour le CI

**🟠 HAUTE — Rate limiting absent**
- Risque : brute force sur `/api/login`, DDoS sur `/api/products`
- Action : `Symfony RateLimiter` (composant natif Symfony 5.2+) ou Nginx `limit_req_zone`
- Config Symfony : `config/packages/rate_limiter.yaml`

**🟠 HAUTE — TTL JWT trop long**
- Risque : JWT volé (man-in-the-middle, XSS) → accès pendant toute la durée de vie du token
- Action : TTL = 15min + refresh token endpoint (`/api/token/refresh`)
- Lexik JWT Bundle : `config/packages/lexik_jwt_authentication.yaml` → `token_ttl: 900`

---

## 10. Sylius — E-commerce Modulaire

### Pourquoi Sylius pour la V3 ?
- Architecture Symfony native → compatible avec le code existant
- API Platform intégrée → headless e-commerce (React frontend)
- Bundles modulaires : panier, commandes, inventaire, fanzine subscription
- Adapté aux déploiements Docker/Kubernetes (contrairement à PrestaShop monolithique)

### Fonctionnalités V3 ciblées
- Catalogue produits (figurines, films, fanzines)
- Panier et commandes
- Abonnements fanzine (paper + digital)
- E-reader intégré pour fanzines digitaux (accès authentifié)
- Système de recommandations (filtrage collaboratif)

---

## CHECKLIST DE RÉVISION FINALE

### 3 jours avant le passage

- [ ] Revoir les 4 indicateurs ISO 25010 et leur justification
- [ ] Être capable d'expliquer chaque job du ci.yml
- [ ] Connaître la structure d'un JWT (header.payload.signature)
- [ ] Préparer la démo en conditions réelles (5 fois minimum)
- [ ] Enregistrer la vidéo de backup

### La veille

- [ ] Vérifier que le pipeline est vert sur `déploiement-fonctionnelle`
- [ ] Préparer tous les onglets navigateur
- [ ] Tester les 3 appels API dans Postman
- [ ] Réviser les questions jury probables (sections ci-dessus)

### Le jour J

- [ ] Arriver avec 15 min d'avance
- [ ] Tester la connexion réseau sur site
- [ ] Avoir les backups (vidéo + screenshots) accessibles hors connexion

---

## VOCABULAIRE TECHNIQUE À MAÎTRISER

| Terme | Définition courte |
|-------|------------------|
| SAST | Static Application Security Testing — analyse du code source sans exécution |
| DAST | Dynamic Application Security Testing — test sur l'application déployée |
| HPA | Horizontal Pod Autoscaler — scaling automatique K8s basé sur les métriques |
| TTL | Time To Live — durée de validité d'un token/cache |
| VU | Virtual User — utilisateur simulé dans un test de charge k6 |
| p95/p99 | 95e/99e percentile de latence — 95% des requêtes sont sous X ms |
| CVE | Common Vulnerabilities and Exposures — identifiant de vulnérabilité |
| PVC | Persistent Volume Claim — stockage persistant dans Kubernetes |
| Ingress | Contrôleur de routage HTTP dans Kubernetes |
| Shift Left | Approche DevSecOps : intégrer la sécurité le plus tôt possible dans le cycle |
| Code smell | Indicateur de problème de conception (pas un bug, mais une mauvaise pratique) |
| Technical debt | Coût futur lié aux mauvais choix actuels de développement |
| Stateless | Sans état serveur — chaque requête porte toutes les infos nécessaires (JWT) |
| ORM | Object-Relational Mapping — abstraction BDD (Doctrine dans Symfony) |
| Fixtures | Données de test insérées en BDD pour les tests automatisés |
| Webhook | Appel HTTP automatique déclenché par un événement (déploiement Coolify) |
