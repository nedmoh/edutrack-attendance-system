# 🎓 EduTrack - Student Attendance Management System

Un système complet de gestion de présence étudiante développé en PHP/MySQL avec interface moderne.

## 📋 Fonctionnalités

### 🎯 Backend (Exercices demandés)
- ✅ **Connexion DB sécurisée** avec try/catch (Exercice 3)
- ✅ **CRUD complet étudiants** (Exercice 4)
- ✅ **Système de sessions et présence** (Exercice 5)
- ✅ **Validation des données** côté serveur
- ✅ **Gestion des erreurs** avec journalisation

### 🚀 Frontend Moderne
- ✅ Interface responsive avec design coloré
- ✅ Navigation par onglets fluide
- ✅ Tableaux interactifs avec cases à cocher
- ✅ Graphiques dynamiques (Chart.js)
- ✅ Recherche et tri en temps réel
- ✅ API RESTful pour extensibilité

### 👨‍💼 Panel d'Administration
- ✅ Interface unifiée pour toutes les opérations
- ✅ Dashboard avec statistiques
- ✅ Gestion centralisée des étudiants et sessions
- ✅ Prise de présence intuitive

## 🛠️ Installation

### Prérequis
- XAMPP (Apache + MySQL)
- PHP 7.4+
- MySQL 5.7+

### Étapes d'installation
1. Cloner le projet dans `htdocs/`
2. Démarrer Apache et MySQL dans XAMPP
3. Accéder à `http://localhost/edutrack/setup_database.php`
4. La base de données et les données de test seront créées automatiquement

## 🎮 Utilisation

### Interface Moderne
http://localhost/edutrack/


### Panel d'Administration  
http://localhost/edutrack/admin.php




### Pages Backend Séparées
- Gestion étudiants : `http://localhost/edutrack/students/list_students.php`
- Sessions présence : `http://localhost/edutrack/attendance/list_sessions.php`

## 🗃️ Structure de la Base de Données

- `students` - Informations des étudiants
- `attendance_sessions` - Sessions de présence
- `attendance_records` - Enregistrements de présence
- `groups` - Groupes d'étudiants

## 🔧 API Endpoints

- `GET /api/students.php` - Liste des étudiants
- `POST /api/students.php` - Ajouter un étudiant
- `GET /api/attendance.php` - Données de présence
- `GET /api/reports.php` - Statistiques et rapports

## 📸 Captures d'écran

*Interface moderne avec design coloré*
*Panel d'administration professionnel*
*Base de données dans phpMyAdmin*

## 👨‍💻 Développement

Ce projet démontre la maîtrise de :
- PHP procédural avec MySQL
- Architecture MVC simplifiée
- APIs RESTful
- Frontend moderne (jQuery, Chart.js)
- Design responsive
- Gestion d'erreurs et validation

## 📄 Licence

Projet développé pour [TD PAW]