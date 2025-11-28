<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    
define('DB_PASS', '');        // Laissez vide pour XAMPP
define('DB_NAME', 'edutrack_db');

// Désactiver l'affichage des erreurs en production
error_reporting(0);
ini_set('display_errors', 0);
?>