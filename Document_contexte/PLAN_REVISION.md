# Plan de Révision — Fond de la Présentation BLOC 3
## Mickaël Dijoux — Approfondissement technique pour le jury

---

## OBJECTIF

Ce document prépare les réponses aux questions du jury. Chaque section couvre un concept technique réellement présent dans le projet, avec les détails à maîtriser et les questions probables.

---

## 1. ISO 25010 — Les 4 Indicateurs du Projet

### À savoir par cœur : définition et mesure de chaque indicateur

**IND-01 — Fiabilité** (outil : PHPUnit `--coverage-clover`)
- Couverture de tests : % des lignes/branches de `src/` couvertes par les tests
- Pourquoi ça prévient la dette : une zone non couverte = comportement inconnu en production = future régression silencieuse
- Rapport généré : `coverage.xml` dans les artefacts GitHub Actions

**IND-02 — Maintenabilité** (outil : SonarQube)
- Code smells détectés : méthodes trop longues, duplication, nommage ambigu, couplage fort
- Dette technique estimée : temps pour corriger l'ensemble des code smells
- Quality Gate : seuil configuré → le pipeline échoue si dépasse le seuil

**IND-03 — Sécurité** (outil : SonarQube SAST)
- Vulnérabilités détectées : injections SQL, XSS, mauvaise gestion des secrets, CVE dans les dépendances
- SAST = Static Application Security Testing : analyse du code source sans exécuter l'application
- Objectif : 0 CVE critique ou haute

**IND-04 — Performance** (outil : k6 + Grafana Cloud)
- `http_req_duration p(95) < 200 ms` : 95% des requêtes sous 200ms
- `http_req_failed rate < 1%` : moins de 1% d'erreurs
- Seuil 200ms : référence UX (au-delà, l'utilisateur perçoit un délai — standard Google RAIL model)

### Question jury probable
> "Comment ces indicateurs préviennent concrètement la dette technique ?"

Réponse : IND-01 évite les régressions silencieuses (code non testé = dette latente). IND-02 signale la dégradation architecturale avant qu'elle devienne irréversible. IND-03 empêche des failles de s'accumuler. IND-04 détecte les dégradations de performance avant qu'elles atteignent la production.

---

## 2. CatalogueService — Logique Métier Testée

### Ce que fait le service (fichier : `src/Service/CatalogueService.php`)

```php
filterAvailableProducts(array $products): array
// → array_filter sur stock > 0
// Règle métier : un produit épuisé n'apparaît pas dans le catalogue

filterByCategory(array $products, string $category): array
// → array_filter sur category === $category
// Catégories présentes : figurines, blu-ray, fanzine, jeux

sortByPriceAsc(array $products): array
// → usort sur (float) price
// Résultat : du moins cher (7,99€ Fanzine) au plus cher (89,99€ Jeu Cthulhu Wars)
```

### Ce que testent les 9 tests unitaires (`CatalogueServiceTest`)

| Test | Ce qu'il vérifie |
|------|-----------------|
| `testAllFixtureProductsAreAvailable` | Les 8 fixtures (stock > 0) passent toutes le filtre |
| `testEpuisedProductIsExcludedFromCatalogue` | Produit stock=0 est exclu |
| `testReturnsEmptyWhenAllProductsAreOutOfStock` | Liste vide si tout épuisé |
| `testFilterFigurinesReturnsOnlyFigurines` | 2 figurines dans les fixtures |
| `testFilterBluRayReturnsOnlyBluRays` | 2 blu-ray dans les fixtures |
| `testFilterFanzineReturnsOnlyFanzines` | 2 fanzines dans les fixtures |
| `testFilterUnknownCategoryReturnsEmpty` | Catégorie inexistante → tableau vide |
| `testSortByPriceAscReturnsChepeastFirst` | Fanzine Heroic Fantasy (7,99€) en premier |
| `testSortByPriceAscIsSortedCorrectly` | Chaque prix ≤ au suivant (validation complète) |

### Pourquoi ces tests sont des "tests unitaires" ?
- Pas de base de données, pas de conteneur Docker
- `CatalogueService` n'a aucune dépendance externe (pas de constructeur avec injection)
- On crée les entités `Product` directement dans le test (`new Product()`)
- Exécution en millisecondes, aucun setup requis

---

## 3. RecommendationService — Tests avec Mocks

### Ce que fait le service (`src/Service/RecommendationService.php`)

```php
getRecommendations(Product $product): array
// → Consulte le cache avec clé "recommendations_{id}"
// → Si absent du cache : appelle ProductRepository::findByCategory($category, $currentId)
// → Met en cache TTL 300s
// → Retourne les produits de la même catégorie (hors le produit courant)
```

### Pourquoi utiliser des mocks dans les tests ?

`RecommendationService` dépend de 2 collaborateurs :
- `ProductRepository` : accès base de données
- `CacheInterface` : accès cache Symfony

En tests unitaires, on ne veut pas de vraie BDD ni de vrai cache. On **mock** ces dépendances :

```php
$productRepository = $this->createMock(ProductRepository::class);
$productRepository->expects($this->once())
    ->method('findByCategory')
    ->with('masques', 1)           // vérif des arguments passés
    ->willReturn([$recommended1, $recommended2]);
```

Le mock vérifie :
1. Que `findByCategory` est appelé exactement 1 fois
2. Avec les bons arguments (`'masques'`, id=1)
3. Et retourne ce qu'on lui dit de retourner

### Les 3 tests de `RecommendationServiceTest`

| Test | Ce qu'il vérifie |
|------|-----------------|
| `testGetRecommendationsReturnsSameCategoryProducts` | 2 produits de même catégorie retournés |
| `testGetRecommendationsReturnsEmptyArrayWhenNoProducts` | Tableau vide si aucun similaire |
| `testGetRecommendationsUsesCacheKey` | Clé cache contient `recommendations_` |

### Question jury probable
> "Pourquoi ne pas tester directement avec la base de données ?"

Réponse : Les tests unitaires doivent être rapides, isolés et déterministes. Avec une vraie BDD, le test dépend de l'état de la base, de la connexion réseau, et prend 10 à 100x plus de temps. Les mocks isolent le comportement du service de son infrastructure.

---

## 4. Tests Fonctionnels — Session Auth & WebTestCase

### Ce qu'est PHPUnit WebTestCase

`WebTestCase` est une classe Symfony qui simule un client HTTP complet, sans démarrer un vrai serveur :
- Lance le kernel Symfony en mode `test`
- Crée un client qui envoie de vraies requêtes HTTP internes
- Accède à la vraie base de données (MySQL service éphémère dans GitHub Actions)
- Peut suivre les redirections, soumettre des formulaires, inspecter le HTML

### Les 4 tests de `CatalogueSecurityTest`

```php
testCatalogueRedirectsToLoginWhenNotAuthenticated()
// GET /product → assertResponseRedirects('/login')
// Vérifie : route protégée sans auth = redirect 302

testLoginPageIsAccessible()
// GET /login → assertResponseIsSuccessful() + assertResponseStatusCodeSame(200)
// Vérifie : page de login accessible à tous

testLoginFailsWithInvalidCredentials()
// GET /login → submitForm('login', bad credentials) → followRedirect()
// assertSelectorExists('.alert-danger')
// Vérifie : mauvaises credentials → message d'erreur visible

testAuthenticatedUserCanAccessCatalogue()
// createUser() → login → GET /product → assertResponseStatusCodeSame(200)
// Vérifie : utilisateur authentifié accède au catalogue
```

### MySQL service éphémère dans GitHub Actions

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_DATABASE: demo_test
      MYSQL_USER: demo_user
      MYSQL_PASSWORD: demo_password
    options: --health-cmd="mysqladmin ping -h localhost"
```

Le job `test-functional` :
1. Démarre MySQL dans un conteneur de service
2. Exécute `doctrine:migrations:migrate` → crée le schéma
3. Exécute `doctrine:fixtures:load` → insère les données de test
4. Lance PHPUnit avec `--coverage-clover coverage.xml`

---

## 5. Pipeline CI/CD — Maîtrise de `ci.yml`

### Points techniques à connaître sur chaque job

**Job 1 — Build**
- Cache vendor avec `actions/cache@v4` sur `hashFiles('composer.lock')` → si composer.lock n'a pas changé, vendor est restauré depuis le cache (économise 30-60s)

**Job 2 — Security Scan**
- `sonarsource/sonarqube-scan-action@master` se connecte à `demo-sonarcube.dev.fabdevlab.fr`
- Arguments : `-Dsonar.projectKey=Demo-symfony -Dsonar.sources=src -Dsonar.tests=tests`
- Token dans `${{ secrets.SONAR_TOKEN }}` → jamais en clair dans le code

**Job 5 — Non-Régression Pré-Deploy**
- Vérifie l'état ACTUEL de la preprod avant de la modifier
- 3 checks : `/` → 200, `/login` → 200, `/product` → 302 (redirect non auth)
- S'exécute seulement sur `push` (pas sur `pull_request`)

**Job 6 — Deploy**
- Webhook Coolify : `curl -X GET "${{ secrets.COOLIFY_WEBHOOK_URL }}" -H "Authorization: Bearer ${{ secrets.COOLIFY_TOKEN }}"`
- Coolify reçoit le webhook → pull la nouvelle image → redémarre les conteneurs

**Job 8 — Load Test**
- `k6 cloud run` envoie les résultats à Grafana Cloud (token dans `${{ secrets.GRAFANA_TOKEN }}`)
- Zone : `amazon:fr:paris` (simulation depuis Paris)
- Variables injectées : `BASE_URL`, `K6_USERNAME`, `K6_PASSWORD`

### Question jury probable
> "Pourquoi 8 jobs séquentiels et pas en parallèle ?"

Réponse : La séquence est intentionnelle. On ne déploie que si les tests passent. On ne charge que si le déploiement a réussi. Les jobs en parallèle permettraient de déployer même si les tests échouent — ce qui est exactement ce qu'on veut éviter.

---

## 6. k6 — Scénario de Charge Réel

### Pourquoi k6 doit extraire le CSRF

Symfony génère un token CSRF unique par session sur le formulaire de login. k6 doit :
1. Faire un GET `/login` pour obtenir la page HTML
2. Parser le HTML pour extraire `<input name="_csrf_token" value="...">`
3. Inclure ce token dans le POST du formulaire

```javascript
const csrfToken = loginPage.html().find('input[name="_csrf_token"]').attr('value');
const res = http.post(`${BASE_URL}/login`, {
  _username: __ENV.K6_USERNAME,
  _password: __ENV.K6_PASSWORD,
  _csrf_token: csrfToken,
}, { redirects: 5 });
```

C'est la preuve que la protection CSRF est active : si k6 n'incluait pas le token, la connexion échouerait.

### Paramètres du test et leur signification

```javascript
stages: [
  { duration: '30s', target: 10  },   // montée progressive (warm-up)
  { duration: '1m',  target: 50  },   // charge nominale
  { duration: '30s', target: 100 },   // pic de charge (stress)
  { duration: '30s', target: 0   },   // descente (cool-down)
]
```

**Seuils (thresholds) :**
- `http_req_duration p(95) < 200` : 95% des requêtes doivent répondre en moins de 200ms
- `http_req_failed rate < 0.01` : moins de 1% d'erreurs HTTP

Si les seuils ne sont pas respectés → k6 retourne un code d'erreur → le job 8 échoue.

### Question jury probable
> "Quel est le lien entre k6 et ISO 25010 ?"

Réponse : k6 mesure directement IND-04 (Performance). Le seuil `p(95) < 200ms` est l'indicateur concret. Si Grafana Cloud montre une dégradation du p95 sur plusieurs runs, c'est un signal que la dette de performance s'accumule — ce qui justifie des actions correctives (cache, scaling).

---

## 7. Sécurité — Authentification Session & CSRF

### Session vs JWT — Pourquoi l'app utilise la session pour le web

| Critère | Session (web) | JWT (API) |
|---------|--------------|-----------|
| Cas d'usage | Navigateur, formulaire HTML | API REST, client mobile/React |
| Stockage | Côté serveur (BDD ou cache) | Côté client (localStorage/cookie) |
| CSRF | Protection nécessaire (Symfony l'intègre) | Pas de CSRF (pas de cookie) |
| Révocation | Immédiate (suppression session BDD) | Nécessite une liste de révocation |

L'application a les deux : session pour l'interface web (`/product`, `/admin`), JWT disponible pour l'API REST (`/api/products`).

### Protection CSRF dans Symfony

Symfony génère automatiquement un token CSRF pour tous les formulaires déclarés avec `FormType` ou la configuration security. Le token est :
- Généré par `csrf_token('authenticate')`
- Lié à la session utilisateur
- Vérifié par le firewall Symfony à chaque POST

### Question jury probable
> "Comment tu aurais fait si le frontend était React ?"

Réponse : React consommerait l'API REST avec JWT. Le flux serait : POST `/api/login` → token JWT → Authorization: Bearer dans chaque requête. C'est d'ailleurs prévu en V2 — l'API JWT est déjà disponible dans `src/Controller/Api/ProductController.php`.

---

## 8. Docker & Architecture Conteneurs

### Dépendances entre conteneurs

```yaml
php:
  depends_on:
    mysql:
      condition: service_healthy   # attend que MySQL réponde avant de démarrer
```

**Healthcheck MySQL :**
```yaml
options: --health-cmd="mysqladmin ping -h localhost" --health-interval=5s --health-retries=10
```
→ php-fpm ne démarre que quand MySQL est prêt. Évite les erreurs "Connection refused" au démarrage.

### PHP-FPM vs Apache

PHP-FPM (FastCGI Process Manager) : nginx reçoit la requête HTTP → la transmet à php-fpm via socket TCP → php-fpm exécute Symfony → renvoie le résultat à nginx. Avantages : nginx et PHP sont découplés, nginx peut servir les assets statiques directement, meilleure gestion de la concurrence.

### Alpine Linux

`php:8.4-fpm-alpine` : image ~30MB vs ~200MB pour la version Debian. Moins de paquets = moins de CVE potentiels. SonarQube peut scanner l'image pour détecter des CVE dans les dépendances système.

---

## 9. Kubernetes — Arguments pour la V2

### Problèmes actuels (V1 Docker Compose) identifiés par les tests

1. **Single replica PHP-FPM** → un seul processus gère toutes les requêtes → saturation CPU à 100 VUs
2. **Pas de cache catalogue** → MySQL sollicité à chaque requête → latence qui monte sous charge
3. **Pas de rate limiting** → `/login` peut être attaqué par brute force sans limite
4. **Single host** → si le serveur tombe, l'app est indisponible

### Comment K8s résout chaque problème

| Problème V1 | Solution K8s |
|-------------|-------------|
| Single replica | HPA : `minReplicas: 2, maxReplicas: 10, targetCPUUtilizationPercentage: 80` |
| Pas de cache | Helm chart Redis + annotation cache Symfony |
| Pas de rate limiting | Ingress Nginx/Traefik : `nginx.ingress.kubernetes.io/limit-rps` |
| Single host | Multi-node cluster, auto-restart des pods crashés |

### K3d — POC local

K3d = K3s (Kubernetes allégé) dans Docker. Permet de simuler un cluster K8s en local :
```bash
k3d cluster create maison-epouvante --agents 2
kubectl apply -f k8s/deployment.yml
```
Même configuration YAML qu'en production → "iso-production" validé avant déploiement.

### Question jury probable
> "Qu'est-ce que l'HPA et comment ça aide pour Halloween ?"

Réponse : HPA (Horizontal Pod Autoscaler) surveille les métriques CPU/RAM d'un Deployment K8s. Quand le CPU de php-fpm dépasse 80%, K8s crée automatiquement un nouveau pod (une nouvelle instance). Pour Halloween avec 10x le trafic normal, l'HPA créerait 5-10 répliques automatiquement, puis les supprimerait après le pic. Avec Docker Compose, c'est une intervention manuelle.

---

## 10. Plan de Remédiation — Justifications Techniques

### R1 — phpMyAdmin en production (CRITIQUE)

**Risque concret :** phpMyAdmin donne un accès direct à toute la base de données via une interface web. Si les credentials MySQL sont faibles ou si l'interface a une vulnérabilité (CVE connues sur phpMyAdmin), un attaquant peut lire les données utilisateurs, modifier les prix, supprimer le catalogue.

**Correction :** Dans `docker-compose.yml`, ne pas démarrer le service `phpmyadmin` en production. En preprod, restreindre via nginx :
```nginx
location /phpmyadmin {
    allow 1.2.3.4;   # IP autorisée uniquement
    deny all;
}
```

### R2 — Rate Limiting sur `/login` (HAUTE)

**Risque concret :** Sans limite, un attaquant peut envoyer 10 000 requêtes/seconde sur `/login` pour deviner les mots de passe (brute force) ou saturer le serveur (DDoS applicatif).

**Correction Symfony :**
```yaml
# config/packages/rate_limiter.yaml
login_limiter:
    policy: fixed_window
    limit: 5
    interval: '1 minute'
```
→ 5 tentatives de login par minute par IP → bloqué au-delà.

### R3 — Cache sur CatalogueService (HAUTE — identifié par k6)

**Problème observé :** À chaque requête `GET /product`, Symfony appelle la base MySQL pour récupérer la liste. Sous charge (100 VUs), chaque VU fait cette requête → MySQL reçoit 100 requêtes simultanées → saturation.

**Correction :** Mettre en cache la liste des produits (TTL 60s) :
```php
$products = $this->cache->get('catalogue_available', function(ItemInterface $item) {
    $item->expiresAfter(60);
    return $this->service->filterAvailableProducts($this->repository->findAll());
});
```
→ 100 VUs → 1 seule requête MySQL par minute au lieu de 100/seconde.

---

## CHECKLIST DE RÉVISION FINALE

### J-3
- [ ] Revoir les 4 indicateurs ISO 25010 + leur outil + leur justification
- [ ] Être capable d'expliquer chaque test (ce qu'il teste et pourquoi c'est un test unitaire ou fonctionnel)
- [ ] Connaître le scénario k6 (stages, thresholds, CSRF)
- [ ] Préparer la démo 5 fois minimum

### Veille
- [ ] Pipeline vert sur `déploiement-fonctionnelle`
- [ ] Tous les onglets préparés
- [ ] Vidéo de backup enregistrée

### Jour J
- [ ] Tester la connexion réseau sur site
- [ ] Vidéos de backup accessibles hors ligne

---

## VOCABULAIRE TECHNIQUE À MAÎTRISER

| Terme | Définition |
|-------|-----------|
| SAST | Static Application Security Testing — analyse code source sans exécution |
| Quality Gate | Seuil SonarQube au-delà duquel le pipeline échoue |
| Coverage | % du code couvert par les tests (lignes, branches) |
| Mock | Objet de remplacement qui simule un collaborateur dans les tests unitaires |
| WebTestCase | Classe Symfony pour tester les routes HTTP sans serveur réel |
| HPA | Horizontal Pod Autoscaler — scaling automatique K8s |
| VU | Virtual User — utilisateur simulé dans k6 |
| p95 | 95e percentile de latence : 95% des requêtes répondent en X ms ou moins |
| p99 | 99e percentile — plus strict, élimine presque tous les outliers |
| CSRF | Cross-Site Request Forgery — attaque par fausse requête, protégée par token |
| Session | Authentification côté serveur, identifiant stocké dans un cookie PHPSESSID |
| PHP-FPM | FastCGI Process Manager — exécute PHP séparement de nginx |
| Alpine | Distribution Linux minimale (~5MB), réduit la surface d'attaque Docker |
| Fixtures | Données de test insérées en BDD avant les tests fonctionnels |
| Webhook | Appel HTTP automatique déclenché par un événement externe |
| PVC | Persistent Volume Claim — stockage persistant dans Kubernetes |
| Rolling update | Mise à jour K8s sans downtime (nouveaux pods créés avant suppression des anciens) |
| Technical debt | Coût futur des mauvais choix actuels (code smells non corrigés, zones non testées) |
