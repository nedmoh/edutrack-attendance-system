<?php
// Test de connexion à la base de données
require_once 'config/db_connect.php';

try {
    $pdo = connectDB();
    echo "<h1 style='color: green;'>✅ Connexion réussie !</h1>";
    echo "<p>Base de données connectée avec succès.</p>";
    
    // Test de création de la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "<p>Base de données '" . DB_NAME . "' prête.</p>";
    
} catch(Exception $e) {
    echo "<h1 style='color: red;'>❌ Échec de connexion</h1>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}
?>