# 🎮 GuildSavior – Plateforme de gestion de guilde pour MMO

## 📌 Présentation
GuildSavior est une application web permettant aux guildes de MMO d’organiser facilement leurs activités.  
Elle repose sur un système de **DKP (Dragon Kill Points)** et propose une gestion complète des membres, des événements et des récompenses.  

L’objectif est d’automatiser les processus souvent réalisés manuellement (Google Sheets, tableurs), afin de gagner en efficacité et en transparence.  

---

## ✨ Fonctionnalités principales

### 🔑 Authentification
- Connexion via **Discord OAuth2**  
- Récupération automatique du profil Discord et génération d’un JWT côté backend  

### 🖥️ Dashboard utilisateur
- Affichage des informations Discord et du profil joueur  
- Création et gestion de son personnage en jeu  
- Visualisation des statistiques personnelles (DKP, participation aux raids, etc.)  

### 💳 Paiement Premium
- Intégration **Stripe** pour la gestion des abonnements  
- Webhooks pour vérifier le statut du paiement (success/failed)  
- Accès aux fonctionnalités avancées réservé aux comptes premium (guild owners)  

### 🛡️ Gestion de guilde
- Création d’une guilde par un utilisateur premium  
- Invitations à rejoindre la guilde via lien unique  
- Tableau des membres : profil, DKP, participation aux événements  

### 📅 Événements de guilde
- Création d’événements réservée au propriétaire de la guilde  
- Génération automatique d’un code unique pour valider la présence des joueurs  
- Attribution automatique de DKP aux participants  

### 💰 Système d’enchères
- Mise en place d’un marché où les membres dépensent leurs DKP  
- Gestion des transactions et attribution des objets  

---

## ⚙️ Technologies utilisées

### Frontend
- **Angular** (TypeScript, RxJS)  
- Gestion des dépendances : **npm**  

### Backend
- **Laravel (PHP)**  
- Authentification via **OAuth2 / JWT**  
- Gestion des dépendances : **Composer**  

### Base de données
- **MySQL**  

### Intégrations externes
- **Discord API** (connexion + futur bot d’annonce)  
- **Stripe API** (paiement premium)  

### DevOps
- Hébergement : **Hostinger**  
- CI/CD : **GitHub Actions**  
- Sécurité : **HTTPS (SSL)**  

---

## 🛠️ Installation & Lancement

### 1. Cloner le projet
```bash
git clone https://github.com/ton-compte/guildsavior.git
cd guildsavior
