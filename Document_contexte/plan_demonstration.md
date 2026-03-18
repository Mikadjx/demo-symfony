# Plan de démonstration — POC La Petite Maison de l'Épouvante

> Durée cible : 5–7 minutes (après les slides)
> URL préprod : https://demo-nginx.dev.fabdevlab.fr

---

## Séquence de démonstration

### Étape 1 — Application en production sur Coolify (1 min 30)

1. Ouvrir https://demo-nginx.dev.fabdevlab.fr dans le navigateur
2. Montrer la **page d'accueil** → titre "Épouvante" visible, navbar, section hero
3. Cliquer **"Se connecter"** → formulaire de login Symfony
4. Se connecter avec le compte admin
5. Naviguer vers le **catalogue produits** → liste des produits (figurines, jeux, etc.)
6. Cliquer sur un produit → montrer les **recommandations** (produits de même catégorie)

**Message jury :** "L'application est déployée sur Coolify avec SSL automatique. Le système de recommandations est actif — on voit ici des produits suggérés dans la même catégorie avec cache Symfony 5 minutes."

---

### Étape 2 — Pipeline CI/CD GitHub Actions (1 min 30)

1. Ouvrir GitHub → onglet **Actions**
2. Sélectionner le dernier run **"CI/CD — Build, Tests & Deploy Preprod"**
3. Montrer les 8 étapes toutes en vert :
   - 1 Build, 2 Security Scan, 3 Unit Tests, 4 Functional Tests
   - 5 Non-Régression Pré-Deploy, 6 Deploy, 7 Non-Régression Post-Deploy, 8 Load Tests
4. Développer **"3 — Unit Tests"** → PHPUnit --testdox en vert
5. Développer **"6 — Deploy"** → appel webhook Coolify

**Message jury :** "8 étapes séquencées — la sécurité SonarQube est en étape 2, avant les tests. Un échec à n'importe quelle étape bloque le déploiement. Le webhook Coolify déclenche le redéploiement automatique."

---

### Étape 3 — SonarQube Quality Gate (1 min)

1. Ouvrir https://demo-sonarcube.dev.fabdevlab.fr
2. Projet **Demo-symfony** → Quality Gate : **Passed**
3. Montrer : Bugs (0), Vulnerabilities, Code Smells, Coverage

**Message jury :** "SonarQube est auto-hébergé sur Coolify. Le Quality Gate est passé — 0 bug critique. Les axes d'amélioration identifiés alimentent le plan de remédiation."

---

### Étape 4 — Grafana Cloud — Résultats k6 (1 min)

1. Ouvrir Grafana Cloud → dashboard k6
2. Montrer les courbes du dernier run de charge :
   - Temps de réponse p95 (seuil : < 200ms)
   - Taux d'erreur (seuil : < 1%)
   - Montée progressive 0 → 100 VU

**Message jury :** "k6 Cloud envoie les métriques en temps réel à Grafana depuis la zone Paris. Le seuil p95 sous 200ms est maintenu jusqu'à 100 utilisateurs simultanés — la disponibilité et la montée en charge sont démontrées."

---

### Étape 5 — EasyAdmin back-office (30 sec, optionnel)

1. Naviguer vers `/admin`
2. Montrer la gestion produits et utilisateurs (CRUD)

**Message jury :** "EasyAdmin fournit un back-office clé en main — les équipes métier gèrent le catalogue sans intervention technique."

---

## Ordre de priorité si le temps manque

| Priorité | Étape | Pourquoi indispensable |
|---|---|---|
| 1 | Application live | Prouve le fonctionnement du POC |
| 2 | GitHub Actions 8 étapes | Prouve le CI/CD complet |
| 3 | SonarQube | Prouve la qualité et la sécurité |
| 4 | Grafana Cloud / k6 | Prouve l'environnement managé et la montée en charge |
| 5 | EasyAdmin | Bonus si temps disponible |

---

## Checklist pré-démo (la veille)

- [ ] https://demo-nginx.dev.fabdevlab.fr accessible, certificat SSL valide
- [ ] Compte admin fonctionnel
- [ ] Fixtures BDD chargées (catalogue produits complet)
- [ ] Pipeline GitHub Actions au vert sur le dernier commit `déploiement-fonctionnelle`
- [ ] SonarQube dashboard accessible (https://demo-sonarcube.dev.fabdevlab.fr)
- [ ] Grafana Cloud : dernier run k6 visible avec métriques
- [ ] Onglets pré-ouverts : app + GitHub Actions + SonarQube + Grafana
- [ ] Mode "Ne pas déranger" activé
