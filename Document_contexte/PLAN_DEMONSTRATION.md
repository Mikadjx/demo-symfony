# Plan de Démonstration Live — BLOC 3
## Mickaël Dijoux — ~4 minutes dans le temps de passage

---

## PRINCIPE DE LA DÉMO

La démonstration suit le pipeline de bout en bout :
**push → tests → déploiement → application → charge**

On ne fait pas d'appels API manuels dans Postman. On montre les **résultats des tests automatisés** et le **comportement réel de l'application déployée**.

---

## PRÉPARATION AVANT LE PASSAGE

### Checklist veille

- [ ] Vérifier que le pipeline est vert sur `déploiement-fonctionnelle` (GitHub Actions)
- [ ] Vérifier que la preprod répond : `https://demo-nginx.dev.fabdevlab.fr/`
- [ ] Préparer les onglets navigateur dans l'ordre de la démo :
  1. GitHub Actions → dernier run pipeline
  2. GitHub Actions → détail job "3 — Unit Tests" (sortie `--testdox`)
  3. GitHub Actions → détail job "4 — Functional Tests" (sortie `--testdox`)
  4. SonarQube → `https://demo-sonarcube.dev.fabdevlab.fr` (dashboard projet)
  5. Coolify → dashboard preprod (état des conteneurs)
  6. Application déployée → `https://demo-nginx.dev.fabdevlab.fr/`
  7. Grafana Cloud → dashboard k6 (dernier run)
- [ ] **Enregistrer une vidéo de backup** de l'intégralité de la démo

---

## DÉROULEMENT — 7 ÉTAPES

---

### ÉTAPE 1 — Pipeline GitHub Actions : Vue d'ensemble (30 s)

**Action :** Montrer la liste des jobs du dernier run

**URL :** `https://github.com/Mikadjx/demo-symfony/actions`

**Ce qu'on montre :**
- Déclencheur : push sur `déploiement-fonctionnelle`
- 8 jobs séquentiels, tous verts ✅
- Jobs visibles : Build → Security Scan → Unit Tests → Functional Tests → Non-Régression Pré → Deploy → Non-Régression Post → Load Test

**Script oral :**
> "Voici le pipeline complet déclenché automatiquement à chaque push. 8 étapes séquentielles, toutes bloquantes. Si un test échoue, le déploiement ne se fait pas."

---

### ÉTAPE 2 — Sortie des tests unitaires (45 s)

**Action :** Cliquer sur le job "3 — Unit Tests", afficher les logs

**Ce qu'on montre :**
La sortie `--testdox` du job :

```
CatalogueService
 ✔ All fixture products are available
 ✔ Epuised product is excluded from catalogue
 ✔ Returns empty when all products are out of stock
 ✔ Filter figurines returns only figurines
 ✔ Filter blu ray returns only blu rays
 ✔ Filter fanzine returns only fanzines
 ✔ Filter unknown category returns empty
 ✔ Sort by price asc returns cheapeast first
 ✔ Sort by price asc is sorted correctly

RecommendationService
 ✔ Get recommendations returns same category products
 ✔ Get recommendations returns empty array when no products
 ✔ Get recommendations uses cache key
```

**Script oral :**
> "Les tests unitaires vérifient les deux services métier en isolation. CatalogueService : filtre de disponibilité (stock > 0), filtre par catégorie, tri par prix. RecommendationService : recommandations par catégorie avec mise en cache TTL 300 secondes. Aucun appel à la base de données ici — on utilise des mocks pour tester la logique pure."

---

### ÉTAPE 3 — Sortie des tests fonctionnels (45 s)

**Action :** Cliquer sur le job "4 — Functional Tests", afficher les logs

**Ce qu'on montre :**
La sortie `--testdox` du job :

```
CatalogueSecurityTest
 ✔ Catalogue redirects to login when not authenticated
 ✔ Login page is accessible
 ✔ Login fails with invalid credentials
 ✔ Authenticated user can access catalogue
 ✔ Admin can also access catalogue

LoginControllerTest
 ✔ Login
```

**Script oral :**
> "Les tests fonctionnels utilisent une vraie base MySQL éphémère. Ils valident le comportement de sécurité : sans authentification, `/product` redirige vers `/login` avec un code 302. Un login invalide reste sur la page de connexion avec un message d'erreur. Un utilisateur authentifié accède au catalogue. Ce n'est pas testé manuellement — c'est vérifié automatiquement à chaque push."

---

### ÉTAPE 4 — SonarQube — Scan de sécurité (30 s)

**Action :** Ouvrir le dashboard SonarQube

**URL :** `https://demo-sonarcube.dev.fabdevlab.fr`

**Ce qu'on montre :**
- Dernier scan : projet `Demo-symfony`, sources `src/`, tests `tests/`
- Métriques : code smells, bugs, vulnérabilités, dette technique estimée
- Statut global (Quality Gate)

**Script oral :**
> "SonarQube analyse statiquement tout le code source à chaque pipeline. Si un CVE critique est détecté ici, les jobs suivants ne s'exécutent pas — le déploiement est bloqué. C'est l'indicateur IND-02 (maintenabilité) et IND-03 (sécurité) de ma grille ISO 25010."

---

### ÉTAPE 5 — Application déployée + comportement sécurité (45 s)

**Action :** Ouvrir la preprod dans le navigateur

**URL :** `https://demo-nginx.dev.fabdevlab.fr/`

**Ce qu'on montre :**
1. Tenter d'accéder à `https://demo-nginx.dev.fabdevlab.fr/product` sans être connecté
   → **Redirect automatique vers `/login`** (ce que les tests vérifient : 302)
2. Montrer la page de login (formulaire avec protection CSRF visible dans le source)
3. Se connecter avec les credentials de test
   → **Accès au catalogue** : liste de produits (figurines, blu-ray, fanzines, jeux)

**Script oral :**
> "L'application est déployée automatiquement via Coolify. Voici exactement ce que les tests fonctionnels vérifient : `/product` sans auth redirige vers `/login`. Une fois connecté, le catalogue est accessible. On voit les produits filtrés par CatalogueService — seuls les produits avec stock > 0 apparaissent."

---

### ÉTAPE 6 — Coolify — État du déploiement (20 s)

**Action :** Montrer le dashboard Coolify

**Ce qu'on montre :**
- Conteneurs actifs : php, nginx, mysql
- Déploiement déclenché par le webhook du pipeline (job 6)
- phpMyAdmin : mentionner qu'il est désactivé en prod (vulnérabilité identifiée)

**Script oral :**
> "Coolify reçoit le webhook en fin de pipeline. Les conteneurs Docker sont redémarrés automatiquement. phpMyAdmin est désactivé ici — c'est une vulnérabilité critique que j'ai identifiée et qui figure en priorité R1 dans mon plan de remédiation."

---

### ÉTAPE 7 — k6 + Grafana Cloud — Métriques de charge (45 s)

**Action :** Ouvrir Grafana Cloud → dashboard k6

**Ce qu'on montre :**
- Scénario : montée 10 → 50 → 100 VUs sur ~2 min 30 s, depuis Paris
- Métriques : `http_req_duration` (p95, p99), `http_reqs` (req/s), `http_req_failed` (%)
- Seuils définis : p95 < 200ms, taux erreur < 1%
- Ce que k6 simule réellement : GET `/login` → extraire CSRF → POST formulaire → session cookie → GET `/` + GET `/product`

**Script oral :**
> "k6 simule des utilisateurs réels — pas de tokens JWT en dur. Chaque VU doit d'abord extraire le token CSRF du formulaire de login, s'authentifier, puis naviguer sur le catalogue. C'est le scénario le plus proche de la réalité. On voit ici le p95 en dessous de 200ms en charge nominale. Au-delà de 100 VUs, la latence monte — causes identifiées : pas de cache sur le catalogue, single replica PHP-FPM."

---

## PLAN DE CONTINGENCE

| Problème | Solution |
|---------|---------|
| Réseau hors service | Lancer la vidéo de backup enregistrée la veille |
| Pipeline rouge le jour J | Montrer un run précédent vert + expliquer pourquoi c'est attendu |
| SonarQube inaccessible | Capture d'écran préparée du dernier scan |
| Coolify inaccessible | Screenshots des conteneurs actifs |
| Grafana vide | Export CSV / screenshot du dernier run k6 |

---

## QUESTIONS JURY PROBABLES & RÉPONSES CLÉS

| Question | Réponse directe |
|---------|----------------|
| "Pourquoi session et pas JWT pour la démo ?" | L'application web utilise la session Symfony standard (CSRF inclus). JWT est disponible via l'API REST (`/api/products`) pour une future consommation React. On démontre ce qui est réellement testé. |
| "Comment k6 gère le CSRF ?" | Le script extrait dynamiquement le token via `loginPage.html().find('input[name="_csrf_token"]').attr('value')` avant chaque POST. |
| "Quelle couverture de tests ?" | Générée par PHPUnit avec `--coverage-clover coverage.xml` dans le job 4. Accessible dans les artefacts GitHub Actions. |
| "Pourquoi non-régression pré ET post-deploy ?" | Pré = baseline avant modification (si ça échoue après, on sait que c'est le déploiement). Post = vérification que le nouveau déploiement n'a rien cassé. |
| "Comment RecommendationService est testé sans BDD ?" | Mock de `ProductRepository` et `CacheInterface` via PHPUnit. On teste la logique pure, pas l'infrastructure. |
| "Que se passe-t-il si SonarQube trouve un CVE ?" | Le job `security-scan` échoue → les jobs suivants ne s'exécutent pas (bloquant via `needs:`). Le déploiement est impossible. |
| "Pourquoi Coolify plutôt que K8s ?" | Coolify est adapté au POC : déploiement simple, rapide à configurer. Kubernetes est prévu en V2 pour le scaling automatique nécessaire en production. |
