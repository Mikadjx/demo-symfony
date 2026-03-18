# Plan de Démonstration Live — BLOC 3
## Mickaël Dijoux — Durée démo : ~4 minutes dans le temps de passage

---

## PRÉPARATION AVANT LE PASSAGE

### Checklist à faire la veille

- [ ] Vérifier que la preprod est accessible : `https://demo-nginx.dev.fabdevlab.fr/`
- [ ] Vérifier que le pipeline GitHub Actions est vert sur `déploiement-fonctionnelle`
- [ ] Préparer les onglets navigateur :
  - GitHub Actions → dernier run pipeline
  - Coolify dashboard → état des conteneurs
  - Grafana Cloud → dashboard k6 + logs
  - Postman (ou Insomnia) avec les 3 requêtes pré-configurées
  - phpMyAdmin preprod
- [ ] Avoir le token JWT pré-copié en presse-papier (au cas où)
- [ ] **Enregistrer une vidéo de backup** de toute la démo (au cas où réseau défaillant)

### Comptes/accès à avoir ouverts
- GitHub : dépôt `Mikadjx/demo-symfony`, onglet Actions
- Coolify : dashboard preprod
- Grafana Cloud : dashboard métriques
- Postman : collection avec les 3 appels API pré-configurés

---

## DÉROULEMENT DE LA DÉMONSTRATION — 7 ÉTAPES

---

### ÉTAPE 1 — Pipeline GitHub Actions (30 s)

**Action :** Montrer le dernier run CI/CD sur GitHub Actions

**URL :** `https://github.com/Mikadjx/demo-symfony/actions`

**Ce qu'on montre :**
- Déclencheur : push sur `déploiement-fonctionnelle`
- 8 jobs tous verts ✅ : Build → Security Scan → Unit Tests → Functional Tests → Non-Régression Pré → Deploy → Non-Régression Post → Load Test
- Durée totale du pipeline (~5-7 min)

**Script oral :**
> "Voici le pipeline complet déclenché automatiquement à chaque push. 8 étapes séquentielles, toutes bloquantes. Si les tests échouent, le déploiement ne se fait pas."

---

### ÉTAPE 2 — Application déployée via Coolify (30 s)

**Action :** Montrer le dashboard Coolify (ou l'application)

**Ce qu'on montre :**
- Application déployée et active
- Conteneurs en cours d'exécution (php, nginx, mysql)
- URL de la preprod fonctionnelle

**Script oral :**
> "L'application est déployée automatiquement sur Coolify via webhook. Les conteneurs Docker sont actifs. Voici l'interface en production."

---

### ÉTAPE 3 — POST /api/login → Génération JWT (30 s)

**Action :** Appel API dans Postman

**Requête :**
```http
POST https://demo-nginx.dev.fabdevlab.fr/api/login
Content-Type: application/json

{
  "username": "[email utilisateur test]",
  "password": "[mot de passe test]"
}
```

**Résultat attendu :**
```json
{
  "token": "eyJhbGciOiJSUzI1NiJ9.eyJ..."
}
```
→ **HTTP 200 OK**

**Script oral :**
> "Je m'authentifie via l'endpoint /api/login. En retour, Symfony génère un token JWT signé avec Lexik JWT Bundle. Ce token va me servir pour accéder aux ressources protégées."

**Action :** Copier le token pour l'étape suivante.

---

### ÉTAPE 4 — GET /api/products sans token → 401 (20 s)

**Action :** Appel API dans Postman — sans header Authorization

**Requête :**
```http
GET https://demo-nginx.dev.fabdevlab.fr/api/products
```

**Résultat attendu :**
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```
→ **HTTP 401 Unauthorized**

**Script oral :**
> "Sans token, la route est protégée. On reçoit bien un 401. C'est le comportement attendu et testé automatiquement dans le pipeline via PHPUnit WebTestCase."

---

### ÉTAPE 5 — GET /api/products avec token → 200 OK (30 s)

**Action :** Appel API dans Postman — avec header Authorization Bearer

**Requête :**
```http
GET https://demo-nginx.dev.fabdevlab.fr/api/products
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9...
```

**Résultat attendu :**
```json
[
  {
    "id": 1,
    "name": "Figurine Dracula",
    "price": 29.99,
    "category": "Figurine"
  },
  ...
]
```
→ **HTTP 200 OK** + payload JSON

**Script oral :**
> "Avec un token valide, j'accède au catalogue complet. Les données proviennent de MySQL via Doctrine ORM. La liste est sérialisée en JSON par le ProductController Symfony."

---

### ÉTAPE 6 — phpMyAdmin — Vérification BDD (20 s)

**Action :** Ouvrir phpMyAdmin preprod

**Ce qu'on montre :**
- Table `product` avec les données fixtures
- Table `user` avec les utilisateurs de test
- Structure Doctrine (colonnes correspondant aux entités)

**Script oral :**
> "phpMyAdmin est accessible en preprod uniquement. On voit ici les données persistées en base. En production, cet accès est désactivé — c'est une vulnérabilité critique identifiée dans mon plan de remédiation."

---

### ÉTAPE 7 — k6 + Grafana Cloud — Métriques de charge (1 min)

**Action :** Ouvrir le dashboard Grafana Cloud → onglet k6

**Ce qu'on montre :**
- Dernier run k6 cloud (depuis le pipeline ou lancé manuellement)
- Métriques en temps réel : `http_req_duration` (p95, p99), `http_reqs` (req/s), `http_req_failed`
- Courbe de latence pendant la montée en charge

**Si k6 cloud non disponible :** montrer capture d'écran ou lancer un test local :
```bash
k6 run -e BASE_URL=https://demo-nginx.dev.fabdevlab.fr k6/load-test.js
```

**Script oral :**
> "Grafana Cloud reçoit les métriques k6 en temps réel. On voit ici le temps de réponse p95 et p99, le taux de requêtes par seconde, et le taux d'erreur. Ces métriques correspondent directement à mon indicateur IND-04 ISO 25010."

---

## PLAN DE CONTINGENCE (si problème technique)

| Problème | Solution |
|---------|---------|
| Réseau coupé | Lancer la vidéo de backup pré-enregistrée |
| Pipeline rouge au moment du passage | Montrer un run précédent vert + expliquer |
| Coolify inaccessible | Montrer les screenshots préparés |
| Postman ne répond pas | Utiliser `curl` en terminal comme alternative |
| Grafana vide | Montrer captures d'écran du dernier run |

---

## COMMANDES CURL DE BACKUP

Si Postman ne fonctionne pas, ces commandes `curl` donnent le même résultat :

```bash
# Étape 3 — Login
curl -X POST https://demo-nginx.dev.fabdevlab.fr/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user@test.fr","password":"password"}' \
  -k

# Étape 4 — Sans token (401)
curl -X GET https://demo-nginx.dev.fabdevlab.fr/api/products -k

# Étape 5 — Avec token (200)
curl -X GET https://demo-nginx.dev.fabdevlab.fr/api/products \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -k
```

---

## QUESTIONS JURY PROBABLES & RÉPONSES CLÉS

| Question | Réponse courte |
|---------|---------------|
| "Pourquoi Coolify plutôt qu'un déploiement K8s ?" | Coolify simplifie le déploiement Docker pour un POC. K8s est prévu en V2 pour le scaling automatique. |
| "Comment gères-tu les secrets ?" | Variables d'environnement injectées via GitHub Secrets → jamais en clair dans le code. En V2 : Docker Secrets ou Vault. |
| "Quelle est la couverture de tests ?" | Coverage générée par PHPUnit avec `--coverage-clover`. Accessible dans les artefacts GitHub Actions. |
| "Pourquoi JWT et pas sessions ?" | JWT est stateless, adapté aux APIs REST consommées par React. Pas de session serveur = scalabilité horizontale facilitée. |
| "Comment tu gères les pics Halloween ?" | Actuellement : single replica. Plan V2 : HPA Kubernetes qui scale les pods PHP-FPM automatiquement dès 80% CPU. |
| "SonarQube bloque vraiment le pipeline ?" | Oui, le job `security-scan` est en `needs: build` et bloquant. Si un CVE critique est détecté, les tests ne s'exécutent pas. |
| "Qu'est-ce que ISO 25010 ?" | Norme internationale de qualité logicielle. Je l'utilise pour définir 4 métriques mesurables : fiabilité, maintenabilité, sécurité, performance. |
