# Changelog
Toutes les modifications notables de ce projet seront consignées dans ce fichier.  

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),  
et ce projet respecte [Semantic Versioning](https://semver.org/lang/fr/).  

---

## [1.3.1] - 2025-07-25
### Corrigé
- Mise à jour des variables d’environnement pour simplifier la gestion entre environnement local et production :
  - Séparation des bases de données (développement vs production).
  - Gestion automatique des URLs différentes en fonction de l’environnement.
  - Déploiement facilité sans besoin de modifier manuellement les configurations.

---

## [1.3.0] - 2025-07-15
### Ajouté
- Mise en place du système d'enchères permettant aux membres de dépenser leurs DKP.
- Gestion des transactions et validation des enchères gagnantes.

---

## [1.2.0] - 2025-07-01
### Ajouté
- Création d’événements de guilde (réservée au propriétaire).
- Génération automatique d’un code unique par événement pour valider la présence.
- Attribution automatique de DKP aux joueurs présents.

---

## [1.1.0] - 2025-06-15
### Ajouté
- Fonction de création de guilde.
- Invitations à rejoindre une guilde par lien unique.
- Vérification des permissions (seul l’owner premium peut effectuer certaines actions).
- Tableau des membres de guilde avec :
  - Profil détaillé
  - Statistiques personnelles
  - Nombre d’événements rejoints
  - Points DKP

---

## [1.0.0] - 2025-06-01
### Ajouté
- Intégration de Stripe pour le paiement premium :
  - Webhook pour gérer le statut de paiement (success/failed).
  - Gestion des abonnements premium.
- Activation des fonctionnalités premium pour les owners.

---

## [0.2.0] - 2025-05-15
### Ajouté
- Création du dashboard utilisateur.
- Affichage des informations issues de Discord (pseudo, avatar).
- Possibilité de créer un profil joueur et visualiser ses statistiques personnelles.

---

## [0.1.0] - 2025-05-01
### Ajouté
- Connexion via Discord OAuth2 :
  - Récupération du token Discord
  - Génération d’un JWT côté back-end
  - Récupération des infos utilisateur via l’API Discord
  - Mise en place du callback correctement configuré dans le Discord Developer Portal
